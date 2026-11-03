<?php
/**
 * Night settlement (cron 05:30). docs/03 P9.
 *  - closes OPEN events of the night
 *  - checks ledger integrity (balance cache == SUM(ledger))
 *  - builds the management report (CSV + plain-text mail)
 */

require_once dirname(__FILE__) . '/../server/lib/db.php';

$db = qt_db();
$events = $db->query('SELECT * FROM event WHERE state IN ("OPEN","CLOSED")
                      AND event_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->fetchAll(PDO::FETCH_ASSOC);

$report = "QTPass night settlement " . date('d.m.Y H:i') . "\n" . str_repeat('=', 48) . "\n\n";
$csv    = "event_id;type;count;sum_chf\n";

foreach ($events as $e) {
    $report .= $e['name'] . ' (' . $e['event_date'] . ")\n";

    $st = $db->prepare('SELECT type, COUNT(*) AS n, SUM(amount_rappen) AS s FROM ledger_entry
                        WHERE event_id = ? GROUP BY type ORDER BY type');
    $st->execute(array($e['event_id']));
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        $report .= sprintf("  %-16s %6d   CHF %10s\n", $r['type'], $r['n'], number_format($r['s'] / 100, 2, '.', "'"));
        $csv    .= $e['event_id'] . ';' . $r['type'] . ';' . $r['n'] . ';' . number_format($r['s'] / 100, 2, '.', '') . "\n";
    }

    $a = qt_row('SELECT COUNT(*) AS n FROM attendance WHERE event_id = ?', array($e['event_id']));
    $report .= "  QTPass check-ins: " . $a['n'] . "\n";

    // Arrival curve per 15 minutes
    $st = $db->prepare('SELECT DATE_FORMAT(checked_in_at, "%H:") AS h,
                        FLOOR(MINUTE(checked_in_at) / 15) * 15 AS q, COUNT(*) AS n
                        FROM attendance WHERE event_id = ? GROUP BY h, q ORDER BY checked_in_at');
    $st->execute(array($e['event_id']));
    $report .= "  Arrival curve:\n";
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        $report .= sprintf("   %s%02d  %s %d\n", $r['h'], $r['q'], str_repeat('#', (int) ceil($r['n'] / 5)), $r['n']);
    }

    // Reversals need a second look (four-eyes, docs/05 T6)
    $st = $db->prepare('SELECT l.id, l.member_no, l.amount_rappen, l.staff_id FROM ledger_entry l
                        WHERE l.event_id = ? AND l.type IN ("ENTRY_REVERSAL","BAR_REVERSAL")');
    $st->execute(array($e['event_id']));
    $rev = $st->fetchAll(PDO::FETCH_ASSOC);
    $report .= "  Reversals: " . count($rev) . "\n";
    foreach ($rev as $r) {
        $report .= sprintf("   #%d member %d CHF %s by staff %d\n", $r['id'], $r['member_no'],
                           number_format($r['amount_rappen'] / 100, 2), $r['staff_id']);
    }

    $db->prepare('UPDATE event SET state = "SETTLED" WHERE event_id = ?')->execute(array($e['event_id']));
    $report .= "\n";
}

// Integrity: cached balance must equal ledger sum for every member
$diff = $db->query('SELECT b.member_no, b.balance_rappen, COALESCE(SUM(l.amount_rappen),0) AS s
                    FROM member_balance b LEFT JOIN ledger_entry l ON l.member_no = b.member_no
                    GROUP BY b.member_no, b.balance_rappen
                    HAVING b.balance_rappen <> s')->fetchAll(PDO::FETCH_ASSOC);
$report .= "Ledger integrity: " . (count($diff) == 0 ? "OK" : count($diff) . " MISMATCHES - investigate!") . "\n";
foreach ($diff as $d) {
    $report .= sprintf("  member %d cache %d ledger %d\n", $d['member_no'], $d['balance_rappen'], $d['s']);
}

$total = qt_row('SELECT COALESCE(SUM(balance_rappen),0) AS s FROM member_balance', array());
$report .= "Outstanding customer balances (float): CHF " . number_format($total['s'] / 100, 2, '.', "'") . "\n";

$file = '/var/qtpass/reports/settlement-' . date('Ymd') . '.csv';
file_put_contents($file, $csv);

mail(QT_REPORT_MAIL, 'QTPass settlement ' . date('d.m.Y'), $report, "From: qtpass@qtpass.example\r\n");
echo $report;
