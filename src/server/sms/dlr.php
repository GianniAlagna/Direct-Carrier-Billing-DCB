<?php
/**
 * Delivery report callback. For premium MT messages, status BILLED means the
 * carrier has charged the guest -> only now the wallet is credited.
 * in: msgid, status (DELIVERED|BILLED|FAILED), sig
 */

require_once dirname(__FILE__) . '/../lib/db.php';
require_once dirname(__FILE__) . '/../lib/sms.php';
require_once dirname(__FILE__) . '/../lib/ledger.php';

$sms = new QT_Sms();
$p = array(
    'msgid'  => isset($_REQUEST['msgid'])  ? $_REQUEST['msgid']  : '',
    'status' => isset($_REQUEST['status']) ? $_REQUEST['status'] : '',
);
if (!$sms->authenticCallback($p, isset($_REQUEST['sig']) ? $_REQUEST['sig'] : '')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$mt = qt_row('SELECT * FROM sms_message WHERE direction = "MT" AND msgid = ?', array($p['msgid']));
if (!$mt) {
    echo 'OK';
    exit;
}

$status = in_array($p['status'], array('DELIVERED', 'BILLED', 'FAILED')) ? $p['status'] : 'FAILED';
qt_db()->prepare('UPDATE sms_message SET dlr_status = ? WHERE id = ?')->execute(array($status, $mt['id']));

if ($mt['tariff_rappen'] > 0 && $status == 'BILLED') {
    $m = qt_row('SELECT member_no FROM member WHERE msisdn = ?', array($mt['msisdn']));
    if ($m) {
        // Credit policy (docs/07 variant C): first SMS top-up 100 %, then 80 %
        $prev = qt_row('SELECT COUNT(*) AS n FROM ledger_entry WHERE member_no = ? AND type = "TOPUP_SMS"',
                       array($m['member_no']));
        $pct    = ((int) $prev['n'] == 0) ? QT_SMS_CREDIT_FIRST : QT_SMS_CREDIT_NEXT;
        $credit = (int) round($mt['tariff_rappen'] * $pct / 100);

        $ledger = new QT_Ledger();
        $id = $ledger->book($m['member_no'], 'TOPUP_SMS', $credit, array('ref' => 'SMS-' . $mt['msgid']));
        if ($id) {
            $sms->send($mt['msisdn'], 'QT: CHF ' . number_format($credit / 100, 2) . ' credited. Balance CHF '
                . number_format($ledger->balance($m['member_no']) / 100, 2) . '. Ref S' . $id, 0, QT_SHORTNO_FREE);
        }
    }
}

echo 'OK';
