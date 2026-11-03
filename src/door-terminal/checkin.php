<?php
/**
 * Venue node: POST /door/checkin.php
 * in:  member_no, code, terminal_id, staff_id
 * out: JSON { colour, message, first_name, age, photo, debit }
 *
 * Works fully offline: verification against code_cache hashes, decision via
 * the shared QT_Door table, booking into the local journal. The central
 * server is updated by automation/sync_venue.php every 60 seconds.
 */

require_once dirname(__FILE__) . '/venue_config.php';
require_once dirname(__FILE__) . '/../server/lib/door.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$db = new PDO(VENUE_DB_DSN, VENUE_DB_USER, VENUE_DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function venue_row($db, $sql, $params)
{
    $st = $db->prepare($sql);
    $st->execute($params);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ? $r : null;
}

$memberNo   = isset($_POST['member_no'])   ? (int) $_POST['member_no'] : 0;
$code       = preg_replace('/[^0-9]/', '', isset($_POST['code']) ? $_POST['code'] : '');
$terminalId = isset($_POST['terminal_id']) ? substr($_POST['terminal_id'], 0, 16) : 'DOOR?';
$staffId    = isset($_POST['staff_id'])    ? (int) $_POST['staff_id'] : 0;

$salt  = venue_row($db, 'SELECT value FROM settings WHERE name = "venue_salt"', array());
$event = venue_row($db, 'SELECT * FROM current_event LIMIT 1', array());

$db->beginTransaction();
$m = venue_row($db, 'SELECT * FROM member_cache WHERE member_no = ? FOR UPDATE', array($memberNo));

if (!$m || !$event) {
    $db->rollBack();
    echo json_encode(array('colour' => QT_RED, 'message' => 'Unknown member no.'));
    exit;
}
if ($m['locked_until'] && strtotime($m['locked_until']) > time()) {
    $db->rollBack();
    echo json_encode(array('colour' => QT_RED, 'message' => 'Too many wrong codes - wait 10 min'));
    exit;
}

// Look up the typed code among the cached next-20 hashes of all active devices
$hash = sha1($memberNo . '|' . $code . '|' . $salt['value']);
$hit  = venue_row($db, 'SELECT device_id, counter FROM code_cache
                        WHERE member_no = ? AND code_hash = ? AND used_at IS NULL', array($memberNo, $hash));

if ($hit) {
    // Burn this counter and all lower ones of that device (one-time use)
    $db->prepare('UPDATE code_cache SET used_at = NOW() WHERE member_no = ? AND device_id = ? AND counter <= ?')
       ->execute(array($memberNo, $hit['device_id'], $hit['counter']));
    $db->prepare('UPDATE member_cache SET failed_tries = 0 WHERE member_no = ?')->execute(array($memberNo));
} else {
    $tries = (int) $m['failed_tries'] + 1;
    $lock  = $tries >= 3 ? date('Y-m-d H:i:s', time() + 600) : null;
    $db->prepare('UPDATE member_cache SET failed_tries = ?, locked_until = ? WHERE member_no = ?')
       ->execute(array($lock ? 0 : $tries, $lock, $memberNo));
}

$ent = venue_row($db, 'SELECT * FROM entitlement_cache WHERE member_no = ? AND event_id = ? AND consumed = 0',
                 array($memberNo, $event['event_id']));

$door = new QT_Door();
$res  = $door->decide(array(
    'status'          => $m['status'],
    'birth_date'      => $m['birth_date'],
    'inside'          => $m['inside'],
    'balance_rappen'  => $m['balance_rappen'],
    'has_entitlement' => $ent ? 1 : 0,
), array(
    'date'            => $event['event_date'],
    'min_age'         => $event['min_age'],
    'door_fee_rappen' => $event['door_fee_rappen'],
), (bool) $hit);

if ($res['colour'] == QT_GREEN) {
    $db->prepare('UPDATE member_cache SET inside = 1, balance_rappen = balance_rappen - ? WHERE member_no = ?')
       ->execute(array($res['debit_rappen'], $memberNo));
    if ($ent) {
        $db->prepare('UPDATE entitlement_cache SET consumed = 1 WHERE member_no = ? AND event_id = ?')
           ->execute(array($memberNo, $event['event_id']));
    }
}

$db->prepare('INSERT INTO journal (member_no, type, amount_rappen, event_id, terminal_id, staff_id,
              device_id, counter_used, decision, created_at) VALUES (?, "ENTRY", ?, ?, ?, ?, ?, ?, ?, NOW())')
   ->execute(array($memberNo, -$res['debit_rappen'], $event['event_id'], $terminalId, $staffId,
                   $hit ? $hit['device_id'] : null, $hit ? $hit['counter'] : null, $res['colour']));
$db->commit();

echo json_encode(array(
    'colour'     => $res['colour'],
    'message'    => $res['message'],
    'first_name' => $m['first_name'],
    'age'        => $door->age($m['birth_date'], $event['event_date']),
    'photo'      => $m['photo_file'] ? '/photos/' . $m['photo_file'] : null,
    'debit'      => $res['debit_rappen'],
));
