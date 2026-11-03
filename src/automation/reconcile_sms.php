<?php
/**
 * Weekly reconciliation: aggregator billing CSV vs. TOPUP_SMS ledger entries.
 * Expected CSV (aggregator portal export, 2008 style):
 *   msgid;msisdn;shortno;tariff_rappen;status;timestamp
 * Usage: php reconcile_sms.php /var/qtpass/import/aggregator-2008-W46.csv
 */

require_once dirname(__FILE__) . '/../server/lib/db.php';

$file = isset($argv[1]) ? $argv[1] : '/var/qtpass/import/aggregator-latest.csv';
$fh = fopen($file, 'r');
if (!$fh) {
    fwrite(STDERR, "cannot open $file\n");
    exit(1);
}
fgetcsv($fh, 0, ';'); // header

$billedNotCredited = array();
$billedTotal = 0;
$n = 0;
while (($r = fgetcsv($fh, 0, ';')) !== false) {
    list($msgid, $msisdn, $shortNo, $tariff, $status) = $r;
    if ($status != 'BILLED') {
        continue;
    }
    $n++;
    $billedTotal += (int) $tariff;
    $l = qt_row('SELECT id FROM ledger_entry WHERE type = "TOPUP_SMS" AND ref = ?', array('SMS-' . $msgid));
    if (!$l) {
        $billedNotCredited[] = $msgid . ' ' . $msisdn . ' CHF ' . number_format($tariff / 100, 2);
    }
}
fclose($fh);

$out  = "SMS reconciliation " . basename($file) . "\n";
$out .= "Billed messages: $n, gross CHF " . number_format($billedTotal / 100, 2) . "\n";
$out .= "Billed but not credited: " . count($billedNotCredited) . "\n  " . implode("\n  ", $billedNotCredited) . "\n";
$out .= "Action: credit manually after check (guest was charged) - docs/03 E06.\n";

mail(QT_REPORT_MAIL, 'QTPass SMS reconciliation', $out, "From: qtpass@qtpass.example\r\n");
echo $out;
