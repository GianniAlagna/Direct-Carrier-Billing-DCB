<?php
/**
 * MO callback from the SMS aggregator.
 * GET/POST: from, to, text, msgid, carrier, sig
 *
 * Keywords (docs/03):
 *   JOIN <first> <last> <dd.mm.yyyy>   register
 *   PIN <5 digits>                      verify number
 *   IN <member_no>                      get a door/bar code (15 min)
 *   TOP           (on premium number)   top-up, credited after billed DLR
 *   YES           (on premium number)   confirms a top-up > CHF 10 (PBV Art. 11a Abs. 4)
 *   BAL                                 balance
 *   STOP <member_no>                    block account (free)
 *   HELP                                info (free)
 */

require_once dirname(__FILE__) . '/../lib/db.php';
require_once dirname(__FILE__) . '/../lib/sms.php';
require_once dirname(__FILE__) . '/../lib/ledger.php';

$sms = new QT_Sms();
$p   = array();
foreach (array('from', 'to', 'text', 'msgid', 'carrier') as $k) {
    $p[$k] = isset($_REQUEST[$k]) ? $_REQUEST[$k] : '';
}
if (!$sms->authenticCallback($p, isset($_REQUEST['sig']) ? $_REQUEST['sig'] : '')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$from = $sms->normalise($p['from']);
$text = strtoupper(trim(preg_replace('/\s+/', ' ', $p['text'])));
$args = explode(' ', $text);
$kw   = $args[0];

// Log MO (idempotent on msgid: aggregators retry callbacks)
try {
    qt_db()->prepare('INSERT INTO sms_message (msgid, direction, msisdn, short_no, carrier, text, created_at)
                      VALUES (?, "MO", ?, ?, ?, ?, NOW())')
           ->execute(array($p['msgid'], $from, $p['to'], $p['carrier'], substr($p['text'], 0, 160)));
} catch (PDOException $e) {
    echo 'OK'; // duplicate delivery, already processed
    exit;
}

$m = qt_row('SELECT * FROM member WHERE msisdn = ?', array($from));

switch ($kw) {

case 'JOIN':
    // JOIN MAX MUSTER 14.02.1986
    if ($m) {
        $sms->send($from, 'QT: This number is already registered. Member no. ' . $m['member_no'], 0, QT_SHORTNO_FREE);
        break;
    }
    if (count($args) < 4 || !preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $args[count($args) - 1], $d)) {
        $sms->send($from, 'QT: Format: JOIN Firstname Lastname DD.MM.YYYY', 0, QT_SHORTNO_FREE);
        break;
    }
    $pin = str_pad((string) mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
    qt_db()->prepare('INSERT INTO member (msisdn, first_name, last_name, birth_date, status,
                      verify_pin, verify_pin_expires, created_at)
                      VALUES (?, ?, ?, ?, "NEW", ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())')
           ->execute(array($from, ucfirst(strtolower($args[1])),
                           ucfirst(strtolower(implode(' ', array_slice($args, 2, -1)))),
                           $d[3] . '-' . $d[2] . '-' . $d[1], $pin));
    $sms->send($from, 'Welcome to QT! PIN ' . $pin . '. Reply PIN ' . $pin
        . ' to confirm. T&C: qtpass.example/terms. Std. SMS fee.', 0, QT_SHORTNO_FREE);
    break;

case 'PIN':
    if (!$m || $m['status'] != 'NEW') {
        break;
    }
    $ok = isset($args[1]) && $args[1] === $m['verify_pin'] && strtotime($m['verify_pin_expires']) > time()
          && $m['verify_tries'] < 3;
    if ($ok) {
        qt_db()->prepare('UPDATE member SET status = "PENDING_ID", verify_pin = NULL, tc_version = "2008-1",
                          tc_accepted_at = NOW() WHERE member_no = ?')->execute(array($m['member_no']));
        $sms->send($from, 'QT: Confirmed. Member no. ' . $m['member_no']
            . '. Show your ID at your first visit.', 0, QT_SHORTNO_FREE);
    } else {
        qt_db()->prepare('UPDATE member SET verify_tries = verify_tries + 1 WHERE member_no = ?')
               ->execute(array($m['member_no']));
        $sms->send($from, 'QT: Wrong or expired PIN.', 0, QT_SHORTNO_FREE);
    }
    break;

case 'IN':
    // Server-generated code from a dedicated SMS device (kind = SMS)
    if (!$m || !in_array($m['status'], array('ACTIVE', 'PENDING_ID'))) {
        break;
    }
    $hotp = new QT_Hotp();
    $dev  = qt_row('SELECT * FROM device WHERE member_no = ? AND kind = "SMS" AND revoked_at IS NULL',
                   array($m['member_no']));
    if (!$dev) {
        $secret = $hotp->newSecret();
        qt_db()->prepare('INSERT INTO device (member_no, kind, secret_enc, counter, activated_at)
                          VALUES (?, "SMS", ?, 0, NOW())')
               ->execute(array($m['member_no'], qt_encrypt_secret($secret)));
        $dev = array('counter' => 0);
        $raw = $secret;
    } else {
        $raw = qt_decrypt_secret($dev['secret_enc']);
    }
    // Next counter; accepted by the same verify() window at the door.
    // Expiry (15 min) is enforced by the door via sms_message.created_at.
    $code = $hotp->generate($raw, (int) $dev['counter'] + 1);
    $ledger = new QT_Ledger();
    $sms->send($from, 'QT door code ' . $code . ' (valid 15 min). Member ' . $m['member_no']
        . '. Balance CHF ' . number_format($ledger->balance($m['member_no']) / 100, 2), 0, QT_SHORTNO_FREE);
    break;

case 'TOP':
case 'YES':
case 'JA':
case 'OUI':
case 'SI':
    // Arrives on a premium short number. Do NOT credit here: credit only on
    // billed DLR (sms/dlr.php). The billed MT carries the tariff.
    //
    // PBV Art. 11a Abs. 4 (2007): fixed fees above CHF 10 may only be charged
    // after the consumer has expressly confirmed. CHF 20 (9392) therefore
    // needs a second SMS: TOP -> free confirmation request -> YES -> billing.
    if (!$m || $m['status'] != 'ACTIVE') {
        $sms->send($from, 'QT: Top-up only for active members. Nothing was charged.', 0, QT_SHORTNO_FREE);
        break;
    }
    $tariffs = array(QT_SHORTNO_TOP10 => 1000, QT_SHORTNO_TOP20 => 2000);
    if (!isset($tariffs[$p['to']])) {
        break;
    }
    $tariff = $tariffs[$p['to']];
    $needsConfirmation = $tariff > 1000;

    if ($needsConfirmation && $kw == 'TOP') {
        $sms->send($from, 'QT: Top-up CHF ' . ($tariff / 100) . '.- charged to your phone bill. To confirm reply YES to '
            . $p['to'] . ' within 15 min. Otherwise nothing is charged.', 0, $p['to']);
        break;
    }
    if ($kw != 'TOP') {
        // Confirmation is valid only after a TOP on the same number in the last 15 min
        $req = qt_row('SELECT id FROM sms_message WHERE direction = "MO" AND msisdn = ? AND short_no = ?
                       AND text LIKE "TOP%" AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                       ORDER BY id DESC LIMIT 1', array($from, $p['to']));
        if (!$needsConfirmation || !$req) {
            $sms->send($from, 'QT: No open top-up. Send TOP to ' . $p['to'] . ' first. Nothing was charged.',
                0, QT_SHORTNO_FREE);
            break;
        }
    }

    $sms->send($from, 'QT: CHF ' . ($tariff / 100) . '.- top-up in progress for member '
        . $m['member_no'] . '.', $tariff, $p['to']);
    qt_db()->prepare('UPDATE sms_message SET member_no = ? WHERE direction = "MO" AND msgid = ?')
           ->execute(array($m['member_no'], $p['msgid']));
    break;

case 'BAL':
    if ($m) {
        $ledger = new QT_Ledger();
        $sms->send($from, 'QT: Balance CHF ' . number_format($ledger->balance($m['member_no']) / 100, 2),
            0, QT_SHORTNO_FREE);
    }
    break;

case 'STOP':
    if ($m) {
        qt_db()->prepare('UPDATE member SET status = "BLOCKED" WHERE member_no = ?')->execute(array($m['member_no']));
        qt_db()->prepare('UPDATE device SET revoked_at = NOW() WHERE member_no = ? AND revoked_at IS NULL')
               ->execute(array($m['member_no']));
        $sms->send($from, 'QT: Account ' . $m['member_no'] . ' blocked. Balance is safe. Call the hotline to unblock.',
            0, QT_SHORTNO_FREE);
    }
    break;

default:
    $sms->send($from, 'QT Pass: JOIN, BAL, IN <no>, STOP <no>. Top-up: TOP to ' . QT_SHORTNO_TOP10
        . ' (CHF 10.-/SMS) or ' . QT_SHORTNO_TOP20 . ' (CHF 20.-/SMS).', 0, QT_SHORTNO_FREE);
}

echo 'OK';
