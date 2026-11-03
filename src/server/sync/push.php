<?php
/**
 * Venue node -> central: upload journal rows.
 * POST venue_id, ts, sig, rows (JSON array of journal rows)
 * Idempotent on (venue_id, venue_seq). Advances device counters so that a
 * code used offline can never be accepted again online.
 */

require_once dirname(__FILE__) . '/../lib/db.php';
require_once dirname(__FILE__) . '/../lib/ledger.php';

$venueId = isset($_POST['venue_id']) ? (int) $_POST['venue_id'] : 0;
$ts      = isset($_POST['ts']) ? (int) $_POST['ts'] : 0;
$body    = isset($_POST['rows']) ? $_POST['rows'] : '[]';
$sig     = isset($_POST['sig']) ? $_POST['sig'] : '';

$hotp = new QT_Hotp();
if (abs(time() - $ts) > 300 ||
    !$hotp->constantTimeEquals(hash_hmac('sha1', $venueId . '|' . $ts . '|' . $body, QT_VENUE_SYNC_KEY), $sig)) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$rows   = json_decode($body, true);
$ledger = new QT_Ledger();
$acked  = array();

foreach ($rows as $r) {
    try {
        qt_db()->prepare('INSERT INTO journal_sync (venue_id, venue_seq, received_at) VALUES (?, ?, NOW())')
               ->execute(array($venueId, (int) $r['seq']));
    } catch (PDOException $e) {
        $acked[] = (int) $r['seq']; // already received
        continue;
    }

    if ($r['counter_used'] !== null) {
        qt_db()->prepare('UPDATE device SET counter = GREATEST(counter, ?) WHERE device_id = ?')
               ->execute(array((int) $r['counter_used'], (int) $r['device_id']));
    }
    if ($r['decision'] == 'GREEN') {
        qt_db()->prepare('INSERT IGNORE INTO attendance (member_no, event_id, checked_in_at, terminal_id)
                          VALUES (?, ?, ?, ?)')
               ->execute(array($r['member_no'], $r['event_id'], $r['created_at'], $r['terminal_id']));
        if ((int) $r['amount_rappen'] != 0) {
            // Server is master: a refused debit (balance changed meanwhile) is
            // reported in the night settlement as an exception, not lost.
            $ok = $ledger->book($r['member_no'], $r['type'], (int) $r['amount_rappen'], array(
                'event_id' => $r['event_id'], 'venue_id' => $venueId, 'terminal_id' => $r['terminal_id'],
                'staff_id' => $r['staff_id'], 'ref' => 'V' . $venueId . '-' . $r['seq'],
            ));
            if (!$ok) {
                qt_log('SYNC EXCEPTION venue=' . $venueId . ' seq=' . $r['seq'] . ' member=' . $r['member_no']);
            }
        }
    }
    $acked[] = (int) $r['seq'];
}

header('Content-Type: application/json');
echo json_encode(array('acked' => $acked));
