<?php
// config/app.php

// Application Configuration
if (!defined('APP_NAME')) {
    define('APP_NAME', $_ENV['APP_NAME'] ?? 'SSC Election Management');
}

if (!defined('FORCE_HTTPS')) {
    define('FORCE_HTTPS', filter_var($_ENV['FORCE_HTTPS'] ?? 'false', FILTER_VALIDATE_BOOLEAN));
}

if (!defined('APP_URL')) {
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    if (FORCE_HTTPS) {
        $protocol = 'https';
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $protocol = 'https';
        }
    }
    
    define('APP_URL', $protocol . '://' . $host);
}

if (!defined('APP_ENV')) {
    define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
}

// Session Encryption Key
if (!defined('SESSION_ENCRYPTION_KEY')) {
    define('SESSION_ENCRYPTION_KEY', $_ENV['SESSION_ENCRYPTION_KEY'] ?? '');
}

// Session Configuration
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 1800));
}

if (!defined('SESSION_GC_MAXLIFETIME')) {
    define('SESSION_GC_MAXLIFETIME', (int)($_ENV['SESSION_GC_MAXLIFETIME'] ?? 2100));
}

if (!defined('SESSION_COOKIE_LIFETIME')) {
    define('SESSION_COOKIE_LIFETIME', (int)($_ENV['SESSION_COOKIE_LIFETIME'] ?? 2100));
}


// Security
if (!defined('CSRF_TOKEN_LENGTH')) {
    define('CSRF_TOKEN_LENGTH', (int)($_ENV['CSRF_TOKEN_LENGTH'] ?? 32));
}

if (!defined('NONCE_LENGTH')) {
    define('NONCE_LENGTH', (int)($_ENV['NONCE_LENGTH'] ?? 32));
}

// Rate Limiting
if (!defined('MAX_LOGIN_ATTEMPTS')) {
    define('MAX_LOGIN_ATTEMPTS', (int)($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5));
}

if (!defined('LOCKOUT_DURATION')) {
    define('LOCKOUT_DURATION', (int)($_ENV['LOCKOUT_DURATION'] ?? 30));
}

// Cloudflare Turnstile
if (!defined('TURNSTILE_SITE_KEY')) {
    define('TURNSTILE_SITE_KEY', $_ENV['TURNSTILE_SITE_KEY'] ?? '');
}

if (!defined('TURNSTILE_SECRET_KEY')) {
    define('TURNSTILE_SECRET_KEY', $_ENV['TURNSTILE_SECRET_KEY'] ?? '');
}

if (!defined('TURNSTILE_OFFLINE')) {
    define(
        'TURNSTILE_OFFLINE',
        filter_var($_ENV['TURNSTILE_OFFLINE'] ?? false, FILTER_VALIDATE_BOOLEAN)
    );
}



// Paths
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', BASE_PATH . '/public');
}

if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}

if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', BASE_PATH . '/storage');
}

if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', PUBLIC_PATH . '/assets');
}

// Candidate Images Path (inside public folder)
if (!defined('CANDIDATE_IMAGES_PATH')) {
    define('CANDIDATE_IMAGES_PATH', PUBLIC_PATH . '/candidate_images');
}

if (!defined('CANDIDATE_IMAGES_URL')) {
    define('CANDIDATE_IMAGES_URL', APP_URL . '/candidate_images');
}

// SMTP Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? '');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? APP_NAME);

// SendGrid Configuration
define('SENDGRID_API_KEY', $_ENV['SENDGRID_API_KEY'] ?? '');
define('SENDGRID_FROM_EMAIL', $_ENV['SENDGRID_FROM_EMAIL'] ?? '');
define('SENDGRID_FROM_NAME', $_ENV['SENDGRID_FROM_NAME'] ?? APP_NAME);

// Cron Webhook
if (!defined('CRON_WEBHOOK_TOKEN')) {
    define('CRON_WEBHOOK_TOKEN', $_ENV['CRON_WEBHOOK_TOKEN'] ?? '');
}

date_default_timezone_set('Asia/Manila');