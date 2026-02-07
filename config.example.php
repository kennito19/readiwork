<?php
/**
 * ======================================================
 * READIWORK AI – CONFIGURATION TEMPLATE
 * ======================================================
 * Copy this file to config.php and fill in your values
 * ======================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/* Metropol CRB */
define('METROPOL_BASE_URL',    'https://api.metropol.co.ke');
define('METROPOL_PORT',        '5555');
define('METROPOL_VERSION',     'v2_1');
define('METROPOL_PUBLIC_KEY',  'YOUR_METROPOL_PUBLIC_KEY');
define('METROPOL_PRIVATE_KEY', 'YOUR_METROPOL_PRIVATE_KEY');
define('METROPOL_DEBUG', false);

/* YouVerify */
define('YOUVERIFY_BASE_URL',       'https://api.sandbox.youverify.co');
define('YOUVERIFY_PUBLIC_KEY',      'YOUR_YOUVERIFY_KEY');
define('YOUVERIFY_WEBHOOK_SECRET',  'YOUR_WEBHOOK_SECRET');
define('YOUVERIFY_WEBHOOK_URL',     'https://yourdomain.com/youverify_webhook.php');
define('YOUVERIFY_DEBUG', false);

/* Environment */
define('APP_NAME', 'Readiwork AI');
define('APP_ENV', 'production');
define('APP_URL', 'https://yourdomain.com');

/* Database */
define('DB_HOST', 'localhost');
define('DB_NAME', 'readiwork');
define('DB_USER', 'root');
define('DB_PASS', '');

/* Twilio */
$TWILIO_SID = "YOUR_TWILIO_SID";
$TWILIO_TOKEN = "YOUR_TWILIO_TOKEN";
$TWILIO_WHATSAPP_FROM = "whatsapp:+14155238886";

/* M-Pesa */
define('MPESA_CONSUMER_KEY',    'YOUR_MPESA_KEY');
define('MPESA_CONSUMER_SECRET', 'YOUR_MPESA_SECRET');
define('MPESA_SHORTCODE',       '000000');
define('MPESA_PASSKEY',         'YOUR_PASSKEY');
define('MPESA_CALLBACK_URL',    'https://yourdomain.com/callback.php');
define('MPESA_ENVIRONMENT',     'sandbox');

/* Security */
define('ADMIN_SESSION_NAME', 'readiwork_admin');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCK_TIME', 300);
define('ADMIN_SESSION_TIMEOUT', 1800);

date_default_timezone_set('Africa/Nairobi');

// ... rest of config (DB connection, helpers, services array) remains the same
