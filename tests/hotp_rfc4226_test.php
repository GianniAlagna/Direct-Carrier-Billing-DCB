<?php
/**
 * QTPass reference tests (no database needed).
 *  1. HOTP against the official RFC 4226 Appendix D test vectors
 *  2. Look-ahead window and one-time behaviour
 *  3. Door decision table (docs/03 section 7.3)
 *
 * Run: php tests/hotp_rfc4226_test.php
 */

require_once dirname(__FILE__) . '/../src/server/lib/hotp.php';
require_once dirname(__FILE__) . '/../src/server/lib/door.php';

$fail = 0;
$pass = 0;
function check($name, $cond)
{
    global $fail, $pass;
    if ($cond) {
        $pass++;
        echo "  ok    $name\n";
    } else {
        $fail++;
        echo "  FAIL  $name\n";
    }
}

$hotp   = new QT_Hotp();
$secret = '12345678901234567890'; // RFC 4226 Appendix D key (ASCII)

echo "1. RFC 4226 test vectors\n";
$expected = array('755224', '287082', '359152', '969429', '338314',
                  '254676', '287922', '162583', '399871', '520489');
foreach ($expected as $c => $code) {
    check("counter $c -> $code", $hotp->generate($secret, $c) === $code);
}

echo "2. Window behaviour\n";
check('accepts code at counter+1',       $hotp->verify($secret, '287082', 0) === 1);
check('accepts code at counter+10 (window edge)', $hotp->verify($secret, $hotp->generate($secret, 10), 0) === 10);
check('rejects code at counter+11',      $hotp->verify($secret, $hotp->generate($secret, 11), 0) === false);
check('rejects already used code',       $hotp->verify($secret, '287082', 1) === false);
check('accepts code with space "287 082"', $hotp->verify($secret, '287 082', 0) === 1);
check('rejects wrong length',            $hotp->verify($secret, '28708', 0) === false);
check('base32 of RFC key',               $hotp->base32Encode($secret) === 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ');
check('cache hashes: 20 entries',        count($hotp->cacheHashes($secret, 4711, 0, 'salt', 20)) === 20);

echo "3. Door decision table\n";
$door  = new QT_Door();
$event = array('date' => '2008-11-15', 'min_age' => 18, 'door_fee_rappen' => 2500);
$base  = array('status' => 'ACTIVE', 'birth_date' => '1986-02-14', 'inside' => 0,
               'balance_rappen' => 3700, 'has_entitlement' => 0);

function with($base, $k, $v) { $base[$k] = $v; return $base; }

$r = $door->decide($base, $event, true);
check('active + balance -> GREEN, debit 2500', $r['colour'] == QT_GREEN && $r['debit_rappen'] == 2500);
$r = $door->decide(with($base, 'has_entitlement', 1), $event, true);
check('presale -> GREEN, no debit', $r['colour'] == QT_GREEN && $r['debit_rappen'] == 0);
$r = $door->decide(with($base, 'balance_rappen', 1000), $event, true);
check('low balance -> ORANGE', $r['colour'] == QT_ORANGE);
$r = $door->decide(with($base, 'inside', 1), $event, true);
check('already inside -> RED', $r['colour'] == QT_RED);
$r = $door->decide($base, $event, false);
check('invalid code -> RED', $r['colour'] == QT_RED);
$r = $door->decide(with($base, 'status', 'BLOCKED'), $event, true);
check('blocked -> RED', $r['colour'] == QT_RED);
$r = $door->decide(with($base, 'status', 'PENDING_ID'), $event, true);
check('pending ID -> ORANGE', $r['colour'] == QT_ORANGE);
$r = $door->decide(with($base, 'birth_date', '1990-11-16'), $event, true);
check('17 y on event day (18th birthday tomorrow) -> RED', $r['colour'] == QT_RED);
$r = $door->decide(with($base, 'birth_date', '1990-11-15'), $event, true);
check('18th birthday on event day -> GREEN', $r['colour'] == QT_GREEN);

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
