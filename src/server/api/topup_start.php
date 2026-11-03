<?php
/**
 * POST /api/topup_start.php
 * in:  member_no, amount (CHF: 20|50|100|200)
 * out: plist { status, psp_url }  -> app opens the URL in Mobile Safari
 *      ([[UIApplication sharedApplication] openURL:]), the PSP hosted page
 *      takes the card data. Result arrives via psp/notify.php.
 */

require_once dirname(__FILE__) . '/../lib/db.php';
require_once dirname(__FILE__) . '/../lib/ledger.php';
require_once dirname(__FILE__) . '/../lib/plist.php';

$memberNo = isset($_POST['member_no']) ? (int) $_POST['member_no'] : 0;
$chf      = isset($_POST['amount']) ? (int) $_POST['amount'] : 0;

if (!in_array($chf, array(20, 50, 100, 200))) {
    qt_plist_out(array('status' => 'ERROR', 'message' => 'Invalid amount'));
}
$m = qt_row('SELECT status, created_at FROM member WHERE member_no = ?', array($memberNo));
if (!$m || $m['status'] != 'ACTIVE') {
    qt_plist_out(array('status' => 'ERROR', 'message' => 'Account not active'));
}

$amount = $chf * 100;
$ledger = new QT_Ledger();
if ($ledger->balance($memberNo) + $amount > QT_MAX_BALANCE_RAPPEN) {
    qt_plist_out(array('status' => 'ERROR', 'message' => 'Maximum balance CHF 300 reached'));
}

$month = qt_row('SELECT COALESCE(SUM(amount_rappen),0) AS s FROM ledger_entry
                 WHERE member_no = ? AND type IN ("TOPUP_CARD","TOPUP_SMS","TOPUP_CASH")
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)', array($memberNo));
if ((int) $month['s'] + $amount > QT_MAX_TOPUP_MONTH_RAPPEN) {
    qt_plist_out(array('status' => 'ERROR', 'message' => 'Monthly top-up limit reached'));
}

if (strtotime($m['created_at']) > time() - 30 * 86400) {
    $week = qt_row('SELECT COALESCE(SUM(amount_rappen),0) AS s FROM ledger_entry
                    WHERE member_no = ? AND type = "TOPUP_CARD"
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)', array($memberNo));
    if ((int) $week['s'] + $amount > QT_NEW_MEMBER_CARD_WEEK_RAPPEN) {
        qt_plist_out(array('status' => 'ERROR', 'message' => 'New members: max CHF 100 per week by card'));
    }
}

$refno = 'QT' . $memberNo . '-' . time();
$sign  = hash_hmac('sha256', QT_PSP_MERCHANT_ID . $amount . 'CHF' . $refno, QT_PSP_SIGN_KEY);
$url   = QT_PSP_URL . '?' . http_build_query(array(
    'merchantId' => QT_PSP_MERCHANT_ID,
    'amount'     => $amount,
    'currency'   => 'CHF',
    'refno'      => $refno,
    'sign'       => $sign,
    'successUrl' => 'https://pass.qtpass.example/topup_ok.php',
    'errorUrl'   => 'https://pass.qtpass.example/topup_error.php',
));

qt_plist_out(array('status' => 'OK', 'psp_url' => $url));
