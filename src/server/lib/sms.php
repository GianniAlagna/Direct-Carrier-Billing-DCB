<?php
/**
 * QTPass - SMS aggregator client (HTTP API, 2008 style).
 * Every outgoing message is logged in sms_message for reconciliation.
 */

require_once dirname(__FILE__) . '/db.php';

class QT_Sms
{
    /**
     * Send an MT message.
     * @param string $msisdn   +41...
     * @param string $text     max 160 GSM-7 characters
     * @param int    $tariff   Rappen; 0 = standard, >0 = premium billed MT
     * @param string $shortNo  sender short number
     * @return string|false aggregator message id
     */
    function send($msisdn, $text, $tariff, $shortNo)
    {
        $text = substr($text, 0, 160);
        $post = http_build_query(array(
            'user'   => QT_SMS_USER,
            'pass'   => QT_SMS_PASS,
            'to'     => $msisdn,
            'from'   => $shortNo,
            'text'   => $text,
            'tariff' => $tariff,
            'dlr'    => 1,
        ));

        $ch = curl_init(QT_SMS_SEND_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Typical 2008 aggregator answer: "OK 1234567890" or "ERR 17 reason"
        if ($http != 200 || strpos($resp, 'OK ') !== 0) {
            qt_log('sms send failed http=' . $http . ' resp=' . substr($resp, 0, 40));
            return false;
        }
        $msgid = trim(substr($resp, 3));

        $st = qt_db()->prepare('INSERT INTO sms_message
            (msgid, direction, msisdn, short_no, text, tariff_rappen, dlr_status, created_at)
            VALUES (?, "MT", ?, ?, ?, ?, "PENDING", NOW())');
        $st->execute(array($msgid, $msisdn, $shortNo, $text, (int) $tariff));
        return $msgid;
    }

    /** Callback authentication: aggregator IP allow-list + HMAC signature. */
    function authenticCallback($params, $signature)
    {
        $ips = explode(',', QT_SMS_ALLOWED_IPS);
        if (!in_array($_SERVER['REMOTE_ADDR'], $ips)) {
            return false;
        }
        ksort($params);
        $expected = hash_hmac('sha1', http_build_query($params), QT_SMS_SHARED_KEY);
        $hotp = new QT_Hotp();
        return $hotp->constantTimeEquals($expected, (string) $signature);
    }

    /** Normalise Swiss numbers to E.164. 079 123 45 67 -> +41791234567 */
    function normalise($n)
    {
        $n = preg_replace('/[^0-9+]/', '', $n);
        if (strpos($n, '0041') === 0) {
            $n = '+' . substr($n, 2);
        } elseif (strpos($n, '0') === 0) {
            $n = '+41' . substr($n, 1);
        } elseif (strpos($n, '+') !== 0) {
            $n = '+' . $n;
        }
        return $n;
    }
}
