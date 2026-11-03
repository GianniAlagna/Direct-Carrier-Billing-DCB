<?php
// Venue node configuration - copy to venue_config.php
define('VENUE_DB_DSN',  'mysql:host=localhost;dbname=qtpass_venue');
define('VENUE_DB_USER', 'qtvenue');
define('VENUE_DB_PASS', 'CHANGE_ME');

// Central server over IPsec VPN
define('VENUE_CENTRAL_URL', 'https://10.8.0.1/sync');
define('VENUE_ID',          1);
define('VENUE_SYNC_KEY',    'CHANGE_ME');   // HMAC key for sync requests
