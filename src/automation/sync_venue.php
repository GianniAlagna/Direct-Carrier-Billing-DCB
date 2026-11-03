<?php
/**
 * Venue node sync job (cron, every minute). docs/03 P10.
 *  1. push un-uploaded journal rows (idempotent on seq)
 *  2. pull fresh cache (members, balances, code hashes, entitlements)
 *  3. write status.txt for the terminal offline indicator
 *
 * If the central server is unreachable the job exits quietly: terminals keep
 * working from the local cache.
 */

require_once dirname(__FILE__) . '/../door-terminal/venue_config.php';

$db = new PDO(VENUE_DB_DSN, VENUE_DB_USER, VENUE_DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$statusFile = dirname(__FILE__) . '/../door-terminal/status.txt';

function http($url, $post)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CAINFO, '/etc/qtpass/central-ca.pem'); // own CA over VPN
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code == 200 ? $body : false;
}

// ---- 1. push -------------------------------------------------------------
$rows = $db->query('SELECT * FROM journal WHERE uploaded_at IS NULL ORDER BY seq LIMIT 500')
           ->fetchAll(PDO::FETCH_ASSOC);
if (count($rows) > 0) {
    $ts   = time();
    $body = json_encode($rows);
    $resp = http(VENUE_CENTRAL_URL . '/push.php', array(
        'venue_id' => VENUE_ID, 'ts' => $ts, 'rows' => $body,
        'sig'      => hash_hmac('sha1', VENUE_ID . '|' . $ts . '|' . $body, VENUE_SYNC_KEY),
    ));
    if ($resp === false) {
        file_put_contents($statusFile, 'OFFLINE ' . date('c'));
        exit(0);
    }
    $ack = json_decode($resp, true);
    $st  = $db->prepare('UPDATE journal SET uploaded_at = NOW() WHERE seq = ?');
    foreach ($ack['acked'] as $seq) {
        $st->execute(array($seq));
    }
}

// ---- 2. pull -------------------------------------------------------------
$ts   = time();
$resp = http(VENUE_CENTRAL_URL . '/pull.php?' . http_build_query(array(
    'venue_id' => VENUE_ID, 'ts' => $ts, 'sig' => hash_hmac('sha1', VENUE_ID . '|' . $ts, VENUE_SYNC_KEY),
)), null);
if ($resp === false) {
    file_put_contents($statusFile, 'OFFLINE ' . date('c'));
    exit(0);
}
$data = json_decode($resp, true);

$db->beginTransaction();

// Pending (not yet uploaded) local debits must survive the refresh:
// local balance = central balance + sum of pending local amounts.
$pending = array();
foreach ($db->query('SELECT member_no, SUM(amount_rappen) AS s FROM journal
                     WHERE uploaded_at IS NULL GROUP BY member_no') as $p) {
    $pending[$p['member_no']] = (int) $p['s'];
}
$inside = array();
if ($data['event']) {
    foreach ($db->query('SELECT member_no FROM member_cache WHERE inside = 1') as $i) {
        $inside[$i['member_no']] = 1;
    }
}

$up = $db->prepare('REPLACE INTO member_cache (member_no, first_name, birth_date, status, balance_rappen,
                    photo_file, inside, synced_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
foreach ($data['members'] as $m) {
    $no = (int) $m['member_no'];
    $up->execute(array($no, $m['first_name'], $m['birth_date'], $m['status'],
        (int) $m['balance_rappen'] + (isset($pending[$no]) ? $pending[$no] : 0),
        $m['photo_path'] ? basename($m['photo_path']) : null, isset($inside[$no]) ? 1 : 0));
}

// Keep used flags for codes burnt locally but not yet known centrally
$db->exec('DELETE FROM code_cache WHERE used_at IS NULL');
$ins = $db->prepare('INSERT IGNORE INTO code_cache (member_no, device_id, counter, code_hash) VALUES (?, ?, ?, ?)');
foreach ($data['codes'] as $c) {
    $ins->execute($c);
}

if ($data['event']) {
    $e = $data['event'];
    $db->exec('DELETE FROM current_event');
    $db->prepare('INSERT INTO current_event VALUES (?, ?, ?, ?, ?, ?)')->execute(array(
        $e['event_id'], $e['name'], $e['event_date'], $e['door_fee_rappen'], $e['min_age'], $e['night_colour']));
    $en = $db->prepare('INSERT IGNORE INTO entitlement_cache (member_no, event_id, kind) VALUES (?, ?, ?)');
    foreach ($data['entitlements'] as $t) {
        $en->execute(array($t['member_no'], $t['event_id'], $t['kind']));
    }
}

$db->commit();
file_put_contents($statusFile, 'ONLINE ' . date('c'));
