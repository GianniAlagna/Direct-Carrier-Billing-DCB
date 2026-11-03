<?php
/**
 * POST /api/activate_confirm.php  (P2 step 2)
 * in:  msisdn, code, device
 * out: plist { status, member_no, secret (Base32), counter, first_name }
 *
 * The secret leaves the server exactly once, over HTTPS, and is stored in the
 * iPhone Keychain. A new activation revokes all previous devices.
 */

require_once dirname(__FILE__) . '/../lib/db.php';
require_once dirname(__FILE__) . '/../lib/sms.php';
require_once dirname(__FILE__) . '/../lib/plist.php';

if (empty($_SERVER['HTTPS'])) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$sms    = new QT_Sms();
$hotp   = new QT_Hotp();
$msisdn = $sms->normalise(isset($_POST['msisdn']) ? $_POST['msisdn'] : '');
$code   = preg_replace('/[^0-9]/', '', isset($_POST['code']) ? $_POST['code'] : '');
$name   = substr(isset($_POST['device']) ? $_POST['device'] : 'iPhone', 0, 64);

$dev = qt_row('SELECT d.device_id, d.member_no, d.activation_code, d.failed_tries, m.first_name
               FROM device d JOIN member m ON m.member_no = d.member_no
               WHERE m.msisdn = ? AND d.kind = "IPHONE" AND d.activated_at IS NULL AND d.revoked_at IS NULL
               ORDER BY d.device_id DESC LIMIT 1', array($msisdn));

if (!$dev || $dev['failed_tries'] >= 3) {
    qt_plist_out(array('status' => 'ERROR', 'message' => 'Please request a new code.'));
}
if (!$hotp->constantTimeEquals($dev['activation_code'], $code)) {
    qt_db()->prepare('UPDATE device SET failed_tries = failed_tries + 1 WHERE device_id = ?')
           ->execute(array($dev['device_id']));
    qt_plist_out(array('status' => 'ERROR', 'message' => 'Wrong code.'));
}

$secret = $hotp->newSecret();
$db = qt_db();
$db->beginTransaction();
$db->prepare('UPDATE device SET revoked_at = NOW() WHERE member_no = ? AND device_id <> ? AND revoked_at IS NULL')
   ->execute(array($dev['member_no'], $dev['device_id']));
$db->prepare('UPDATE device SET secret_enc = ?, counter = 0, device_name = ?, activation_code = NULL,
              activated_at = NOW() WHERE device_id = ?')
   ->execute(array(qt_encrypt_secret($secret), $name, $dev['device_id']));
$db->commit();

qt_log('device activated member=' . $dev['member_no']);

qt_plist_out(array(
    'status'     => 'OK',
    'member_no'  => (int) $dev['member_no'],
    'first_name' => $dev['first_name'],
    'secret'     => $hotp->base32Encode($secret),
    'counter'    => 0,
));
