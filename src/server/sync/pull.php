<?php
/**
 * Central -> venue node: cache refresh.
 * GET  venue_id, ts, sig   (sig = HMAC-SHA1(venue_id|ts, venue sync key))
 * out: JSON { event, members[], codes[], entitlements[] }
 *
 * Only data for the venue's current event and members who were active in the
 * last 12 months is sent. HOTP secrets never leave the central server; the
 * venue receives hashes of the next 20 codes per device.
 */

require_once dirname(__FILE__) . '/../lib/db.php';
require_once dirname(__FILE__) . '/../lib/ledger.php';

$venueId = isset($_GET['venue_id']) ? (int) $_GET['venue_id'] : 0;
$ts      = isset($_GET['ts']) ? (int) $_GET['ts'] : 0;
$sig     = isset($_GET['sig']) ? $_GET['sig'] : '';

$venue = qt_row('SELECT * FROM venue WHERE venue_id = ?', array($venueId));
$hotp  = new QT_Hotp();
if (!$venue || abs(time() - $ts) > 300 ||
    !$hotp->constantTimeEquals(hash_hmac('sha1', $venueId . '|' . $ts, QT_VENUE_SYNC_KEY), $sig)) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$event = qt_row('SELECT * FROM event WHERE venue_id = ? AND state IN ("PLANNED","OPEN")
                 AND event_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) ORDER BY event_date LIMIT 1',
                array($venueId));

$members = array();
$codes   = array();
$st = qt_db()->query('SELECT m.member_no, m.first_name, m.birth_date, m.status, m.photo_path,
                      COALESCE(b.balance_rappen, 0) AS balance_rappen
                      FROM member m LEFT JOIN member_balance b ON b.member_no = m.member_no
                      WHERE m.status IN ("ACTIVE","PENDING_ID","BLOCKED")');
while ($m = $st->fetch(PDO::FETCH_ASSOC)) {
    $members[] = $m;
    $ds = qt_db()->prepare('SELECT device_id, secret_enc, counter FROM device
                            WHERE member_no = ? AND activated_at IS NOT NULL AND revoked_at IS NULL');
    $ds->execute(array($m['member_no']));
    while ($d = $ds->fetch(PDO::FETCH_ASSOC)) {
        $hashes = $hotp->cacheHashes(qt_decrypt_secret($d['secret_enc']), $m['member_no'],
                                     (int) $d['counter'], $venue['cache_salt'], 20);
        foreach ($hashes as $c => $h) {
            $codes[] = array($m['member_no'], (int) $d['device_id'], $c, $h);
        }
    }
}

$ents = array();
if ($event) {
    $es = qt_db()->prepare('SELECT member_no, event_id, kind FROM entitlement WHERE event_id = ? AND consumed_at IS NULL');
    $es->execute(array($event['event_id']));
    $ents = $es->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode(array('event' => $event, 'members' => $members, 'codes' => $codes, 'entitlements' => $ents));
