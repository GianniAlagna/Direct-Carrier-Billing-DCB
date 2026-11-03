<?php
/**
 * POST /api/balance.php  (iPhone app "Wallet" tab)
 * in:  member_no, otp   (a fresh code proves possession of the device)
 * out: plist { status, balance_rappen, entitlements[], last_tx[], counter }
 *
 * Checking the balance consumes one counter value, like a door check-in;
 * the response returns the new server counter so the app stays in sync.
 */

require_once dirname(__FILE__) . '/../lib/db.php';
require_once dirname(__FILE__) . '/../lib/ledger.php';
require_once dirname(__FILE__) . '/../lib/plist.php';

$memberNo = isset($_POST['member_no']) ? (int) $_POST['member_no'] : 0;
$otp      = isset($_POST['otp']) ? $_POST['otp'] : '';

$dev = qt_row('SELECT * FROM device WHERE member_no = ? AND kind = "IPHONE"
               AND activated_at IS NOT NULL AND revoked_at IS NULL', array($memberNo));
$hotp = new QT_Hotp();
$c = $dev ? $hotp->verify(qt_decrypt_secret($dev['secret_enc']), $otp, (int) $dev['counter']) : false;
if ($c === false) {
    qt_plist_out(array('status' => 'ERROR', 'message' => 'Code not accepted. Open the pass again.'));
}
qt_db()->prepare('UPDATE device SET counter = ? WHERE device_id = ?')->execute(array($c, $dev['device_id']));

$ledger = new QT_Ledger();
$tx = array();
$st = qt_db()->prepare('SELECT type, amount_rappen, created_at FROM ledger_entry
                        WHERE member_no = ? ORDER BY id DESC LIMIT 10');
$st->execute(array($memberNo));
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $tx[] = array('type' => $r['type'], 'amount' => (int) $r['amount_rappen'], 'date' => $r['created_at']);
}
$ent = array();
$st = qt_db()->prepare('SELECT e.name, e.event_date, t.kind FROM entitlement t JOIN event e ON e.event_id = t.event_id
                        WHERE t.member_no = ? AND t.consumed_at IS NULL AND e.event_date >= CURDATE()');
$st->execute(array($memberNo));
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $ent[] = array('event' => $r['name'], 'date' => $r['event_date'], 'kind' => $r['kind']);
}

qt_plist_out(array(
    'status'         => 'OK',
    'balance_rappen' => $ledger->balance($memberNo),
    'counter'        => (int) $c,
    'entitlements'   => $ent,
    'last_tx'        => $tx,
));
