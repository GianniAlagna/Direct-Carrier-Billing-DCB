<?php
/**
 * POST /api/activate.php  (iPhone app, step 1 of P2 device activation)
 * in:  msisdn
 * out: plist { status }
 * Sends a 6-digit activation code by SMS to the registered number.
 */

require_once dirname(__FILE__) . '/../lib/db.php';
require_once dirname(__FILE__) . '/../lib/sms.php';
require_once dirname(__FILE__) . '/../lib/plist.php';

$sms    = new QT_Sms();
$msisdn = $sms->normalise(isset($_POST['msisdn']) ? $_POST['msisdn'] : '');

$m = qt_row('SELECT member_no, status FROM member WHERE msisdn = ?', array($msisdn));

// Same answer whether or not the number exists (no member enumeration).
if ($m && in_array($m['status'], array('PENDING_ID', 'ACTIVE'))) {
    $code = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $st = qt_db()->prepare('INSERT INTO device (member_no, kind, secret_enc, activation_code)
                            VALUES (?, "IPHONE", "", ?)');
    $st->execute(array($m['member_no'], $code));
    $sms->send($msisdn, 'QT activation code: ' . substr($code, 0, 3) . '-' . substr($code, 3)
        . '. Never share this code.', 0, QT_SHORTNO_FREE);
}

qt_plist_out(array('status' => 'SENT'));
