<?php
/**
 * QTPass - database access (PDO, MySQL 5.0 InnoDB)
 */

require_once dirname(__FILE__) . '/../config.php';
require_once dirname(__FILE__) . '/hotp.php';

function qt_db()
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(QT_DB_DSN, QT_DB_USER, QT_DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET NAMES utf8");
        $pdo->exec("SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE");
    }
    return $pdo;
}

/** Fetch one row or null. */
function qt_row($sql, $params)
{
    $st = qt_db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : null;
}

/** Log to syslog; never log codes or secrets. */
function qt_log($msg)
{
    openlog('qtpass', LOG_PID, LOG_LOCAL0);
    syslog(LOG_INFO, $msg);
    closelog();
}

/**
 * Secret at rest: AES-128-CBC with a key outside the DB (2008: mcrypt).
 * On current PHP the mcrypt extension no longer exists; this draft keeps the
 * 2008 call and documents the modern replacement (openssl_encrypt).
 */
function qt_encrypt_secret($raw)
{
    $hotp = new QT_Hotp();
    $iv   = substr($hotp->newSecret(), 0, 16);
    $raw  = str_pad($raw, 32, "\0"); // 20-byte secret -> two AES blocks
    if (function_exists('mcrypt_encrypt')) {
        $ct = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, QT_SECRET_KEY, $raw, MCRYPT_MODE_CBC, $iv);
    } else {
        $ct = openssl_encrypt($raw, 'aes-128-cbc', QT_SECRET_KEY, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    }
    return base64_encode($iv . $ct);
}

function qt_decrypt_secret($stored)
{
    $bin = base64_decode($stored);
    $iv  = substr($bin, 0, 16);
    $ct  = substr($bin, 16);
    if (function_exists('mcrypt_decrypt')) {
        $raw = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, QT_SECRET_KEY, $ct, MCRYPT_MODE_CBC, $iv);
    } else {
        $raw = openssl_decrypt($ct, 'aes-128-cbc', QT_SECRET_KEY, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    }
    return substr($raw, 0, 20); // secrets are always 20 bytes, strip padding
}
