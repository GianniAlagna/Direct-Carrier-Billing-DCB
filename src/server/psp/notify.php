<?php
/**
 * Server-to-server notification from the PSP after a card top-up.
 * Card data never reaches QTPass (hosted payment page).
 * in: refno (= "QT<member_no>-<timestamp>"), amount (Rappen), currency, status, sign
 */

require_once dirname(__FILE__) . '/../lib/db.php';
require_once dirname(__FILE__) . '/../lib/ledger.php';
require_once dirname(__FILE__) . '/../lib/sms.php';

$refno    = isset($_POST['refno'])    ? $_POST['refno']    : '';
$amount   = isset($_POST['amount'])   ? (int) $_POST['amount'] : 0;
$currency = isset($_POST['currency']) ? $_POST['currency'] : '';
$status   = isset($_POST['status'])   ? $_POST['status']   : '';
$sign     = isset($_POST['sign'])     ? $_POST['sign']     : '';

$expected = hash_hmac('sha256', QT_PSP_MERCHANT_ID . $amount . $currency . $refno . $status, QT_PSP_SIGN_KEY);
$hotp = new QT_Hotp();
if (!$hotp->constantTimeEquals($expected, $sign) || $currency != 'CHF') {
    qt_log('psp notify rejected refno=' . $refno);
    header('HTTP/1.0 400 Bad Request');
    exit;
}
if ($status != 'success' || !preg_match('/^QT(\d+)-\d+$/', $refno, $mm)) {
    echo 'OK';
    exit;
}

$memberNo = (int) $mm[1];
$ledger   = new QT_Ledger();
$id = $ledger->book($memberNo, 'TOPUP_CARD', $amount, array('ref' => $refno)); // idempotent on refno
if ($id) {
    // +10 % bonus on card top-ups (cheapest channel, docs/07)
    $ledger->book($memberNo, 'BONUS', (int) round($amount * 0.10), array('ref' => 'BONUS-' . $refno));
    $m = qt_row('SELECT msisdn FROM member WHERE member_no = ?', array($memberNo));
    $sms = new QT_Sms();
    $sms->send($m['msisdn'], 'QT: CHF ' . number_format($amount / 100, 2) . ' + 10% bonus credited. Balance CHF '
        . number_format($ledger->balance($memberNo) / 100, 2) . '. Ref T' . $id, 0, QT_SHORTNO_FREE);
}
echo 'OK';
