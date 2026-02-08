<?php
/**
 * ======================================================
 * READIWORK AI – RENDER DEPLOYMENT CONFIG
 * ======================================================
 * Reads secrets from environment variables, then includes
 * config.app.php for all application logic.
 *
 * On Render, the Dockerfile copies this to config.php
 * ======================================================
 */

/* ======================================================
 * SESSION (SAFE INIT)
 * ====================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/* ======================================================
 * METROPOL CRB - from env vars
 * ====================================================== */
define('METROPOL_BASE_URL',    getenv('METROPOL_BASE_URL') ?: 'https://api.metropol.co.ke');
define('METROPOL_PORT',        getenv('METROPOL_PORT') ?: '5555');
define('METROPOL_VERSION',     getenv('METROPOL_VERSION') ?: 'v2_1');
define('METROPOL_PUBLIC_KEY',  getenv('METROPOL_PUBLIC_KEY') ?: '');
define('METROPOL_PRIVATE_KEY', getenv('METROPOL_PRIVATE_KEY') ?: '');
define('METROPOL_DEBUG',       getenv('METROPOL_DEBUG') === 'true');

/* ======================================================
 * YOUVERIFY - from env vars
 * ====================================================== */
define('YOUVERIFY_BASE_URL',       getenv('YOUVERIFY_BASE_URL') ?: 'https://api.sandbox.youverify.co');
define('YOUVERIFY_PUBLIC_KEY',     getenv('YOUVERIFY_PUBLIC_KEY') ?: '');
define('YOUVERIFY_WEBHOOK_SECRET', getenv('YOUVERIFY_WEBHOOK_SECRET') ?: '');
define('YOUVERIFY_WEBHOOK_URL',    getenv('YOUVERIFY_WEBHOOK_URL') ?: 'https://readiwork.onrender.com/youverify_webhook.php');
define('YOUVERIFY_DEBUG',          getenv('YOUVERIFY_DEBUG') === 'true');

/* ======================================================
 * ENVIRONMENT
 * ====================================================== */
define('APP_NAME', 'Readiwork AI');
define('APP_ENV',  getenv('APP_ENV') ?: 'production');
define('APP_URL',  getenv('APP_URL') ?: 'https://readiwork.onrender.com');

/* ======================================================
 * DATABASE - from env vars
 * ====================================================== */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'readiwork');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

/* ======================================================
 * TWILIO - from env vars
 * ====================================================== */
$TWILIO_SID           = getenv('TWILIO_SID') ?: '';
$TWILIO_TOKEN         = getenv('TWILIO_TOKEN') ?: '';
$TWILIO_WHATSAPP_FROM = getenv('TWILIO_WHATSAPP_FROM') ?: 'whatsapp:+14155238886';

/* ======================================================
 * M-PESA - from env vars
 * ====================================================== */
define('MPESA_CONSUMER_KEY',    getenv('MPESA_CONSUMER_KEY') ?: '');
define('MPESA_CONSUMER_SECRET', getenv('MPESA_CONSUMER_SECRET') ?: '');
define('MPESA_SHORTCODE',       getenv('MPESA_SHORTCODE') ?: '');
define('MPESA_PASSKEY',         getenv('MPESA_PASSKEY') ?: '');
define('MPESA_CALLBACK_URL',    getenv('MPESA_CALLBACK_URL') ?: 'https://readiwork.onrender.com/callback.php');
define('MPESA_ENVIRONMENT',     getenv('MPESA_ENVIRONMENT') ?: 'production');

/* ======================================================
 * SECURITY & ADMIN SETTINGS
 * ====================================================== */
define('ADMIN_SESSION_NAME', 'readiwork_admin');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCK_TIME', 300);
define('ADMIN_SESSION_TIMEOUT', 1800);

/* ======================================================
 * TIMEZONE
 * ====================================================== */
date_default_timezone_set('Africa/Nairobi');

/* ======================================================
 * LOAD APPLICATION LOGIC
 * (DB connection, helpers, services, API functions)
 * ====================================================== */
require_once __DIR__ . '/config.app.php';
