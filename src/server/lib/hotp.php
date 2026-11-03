<?php
/**
 * QTPass - HOTP one-time codes (RFC 4226, December 2005)
 *
 * Written for PHP 5.2 (2008): no namespaces, no closures, dirname(__FILE__)
 * instead of __DIR__. hash_hmac() is available since PHP 5.1.2.
 *
 * @package QTPass
 * @version 0.1-draft (concept reconstruction)
 */

class QT_Hotp
{
    /** Number of digits shown to the door staff. */
    var $digits = 6;

    /** How many future counter values the server accepts. */
    var $window = 10;

    /**
     * Generate the HOTP value for a raw binary secret and a counter.
     *
     * @param string $secret  raw binary key (20 bytes)
     * @param int    $counter moving factor
     * @return string zero-padded code
     */
    function generate($secret, $counter)
    {
        // 8-byte big-endian counter. Two 32-bit words keep this safe on
        // 32-bit PHP builds, which were common in 2008.
        $hi  = (int) floor($counter / 4294967296);
        $lo  = (int) ($counter - $hi * 4294967296);
        $msg = pack('NN', $hi, $lo);

        $hash = hash_hmac('sha1', $msg, $secret, true);

        // Dynamic truncation (RFC 4226, section 5.3)
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset])     & 0x7F) << 24)
                | ((ord($hash[$offset + 1]) & 0xFF) << 16)
                | ((ord($hash[$offset + 2]) & 0xFF) << 8)
                |  (ord($hash[$offset + 3]) & 0xFF);

        $mod  = (int) pow(10, $this->digits);
        $code = $binary % $mod;

        return str_pad((string) $code, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a code against the look-ahead window.
     *
     * @param string $secret       raw binary key
     * @param string $code         code typed by door staff
     * @param int    $lastCounter  last counter value accepted for this device
     * @return int|false  the matched counter (store it!) or false
     */
    function verify($secret, $code, $lastCounter)
    {
        $code = preg_replace('/[^0-9]/', '', (string) $code);
        if (strlen($code) != $this->digits) {
            return false;
        }
        for ($c = $lastCounter + 1; $c <= $lastCounter + $this->window; $c++) {
            if ($this->constantTimeEquals($this->generate($secret, $c), $code)) {
                return $c;
            }
        }
        return false;
    }

    /**
     * Hashes of the next N codes for the venue node offline cache.
     * The venue node never receives the secret itself.
     */
    function cacheHashes($secret, $memberNo, $lastCounter, $venueSalt, $n)
    {
        $out = array();
        for ($c = $lastCounter + 1; $c <= $lastCounter + $n; $c++) {
            $out[$c] = sha1($memberNo . '|' . $this->generate($secret, $c) . '|' . $venueSalt);
        }
        return $out;
    }

    /** String compare without early exit (timing-safe). */
    function constantTimeEquals($a, $b)
    {
        if (strlen($a) != strlen($b)) {
            return false;
        }
        $r = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $r |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $r === 0;
    }

    /** 20 random bytes from the kernel. */
    function newSecret()
    {
        $fp = @fopen('/dev/urandom', 'rb');
        if (!$fp) {
            trigger_error('QTPass: /dev/urandom not readable', E_USER_ERROR);
        }
        $s = fread($fp, 20);
        fclose($fp);
        return $s;
    }

    /** Base32 (RFC 3548) so the secret can travel inside a plist string. */
    function base32Encode($bin)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        for ($i = 0; $i < strlen($bin); $i++) {
            $bits .= str_pad(decbin(ord($bin[$i])), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }
        return $out;
    }
}
