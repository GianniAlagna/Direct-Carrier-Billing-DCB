<?php
/**
 * QTPass configuration - copy to config.php (never commit config.php).
 * File permissions on the server: 0640, owner root, group www-data.
 */

define('QT_DB_DSN',  'mysql:host=localhost;dbname=qtpass');
define('QT_DB_USER', 'qtpass');
define('QT_DB_PASS', 'CHANGE_ME');

// 16-byte AES key for HOTP secrets at rest (generate: head -c16 /dev/urandom | xxd -p)
define('QT_SECRET_KEY', 'CHANGE_ME_16BYTE');

// SMS aggregator (HTTP API). Values are placeholders.
define('QT_SMS_SEND_URL',   'https://sms-aggregator.example.ch/api/send');
define('QT_SMS_USER',       'qt_records');
define('QT_SMS_PASS',       'CHANGE_ME');
define('QT_SMS_SHARED_KEY', 'CHANGE_ME');          // signs MO/DLR callbacks
define('QT_SMS_ALLOWED_IPS', '192.0.2.10,192.0.2.11'); // aggregator callback IPs

// Short numbers (examples). One premium number per price point.
define('QT_SHORTNO_FREE',  '939');    // standard-rate keyword number
define('QT_SHORTNO_TOP10', '9391');   // CHF 10.- per SMS
define('QT_SHORTNO_TOP20', '9392');   // CHF 20.- per SMS

// Credit policy for SMS top-ups (docs/07 variant C): percent credited
define('QT_SMS_CREDIT_FIRST', 100);
define('QT_SMS_CREDIT_NEXT',  80);

// PSP (hosted payment page)
define('QT_PSP_MERCHANT_ID', 'CHANGE_ME');
define('QT_PSP_SIGN_KEY',    'CHANGE_ME');
define('QT_PSP_URL',         'https://psp.example.ch/pay');

// Venue node sync (HMAC key shared with venue_config.php)
define('QT_VENUE_SYNC_KEY', 'CHANGE_ME');

// Reporting
define('QT_REPORT_MAIL', 'management@qtpass.example');

// Wallet caps (docs/06 section 2)
define('QT_MAX_BALANCE_RAPPEN',       30000); // CHF 300
define('QT_MAX_TOPUP_MONTH_RAPPEN',   50000); // CHF 500
define('QT_NEW_MEMBER_CARD_WEEK_RAPPEN', 10000); // CHF 100 during first 30 days
