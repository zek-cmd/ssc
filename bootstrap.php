<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Create log directory
$logDir = __DIR__ . '/storage/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// ===== LOAD ENV FILE (phpdotenv) =====
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Required variables (non-empty)
$dotenv->required([
    'APP_NAME',
    'APP_ENV',
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'SESSION_ENCRYPTION_KEY',
])->notEmpty();

// Integer variables
$dotenv->required([
    'SESSION_LIFETIME',
    'SESSION_GC_MAXLIFETIME',
    'SESSION_COOKIE_LIFETIME',
    'CSRF_TOKEN_LENGTH',
    'NONCE_LENGTH',
    'MAX_LOGIN_ATTEMPTS',
    'LOCKOUT_DURATION',
    'SMTP_PORT',
])->isInteger();

// Boolean variables
$dotenv->required([
    'FORCE_HTTPS',
])->isBoolean();

// Autoloader (fallback if Composer autoload doesn't include App)
spl_autoload_register(function ($class) {
    // Handle App\ namespace
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    // Handle PHPMailer namespace
    if (strpos($class, 'PHPMailer\\') === 0) {
        $relativeClass = str_replace('PHPMailer\\PHPMailer\\', '', $class);
        $file = __DIR__ . '/PHPMailer/' . $relativeClass . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});

// Load config
require_once __DIR__ . '/config/app.php';

// Initialize database
$dbConfigFile = __DIR__ . '/config/database.php';
if (file_exists($dbConfigFile)) {
    require_once $dbConfigFile;
    if (isset($dbConfig)) {
        \App\Core\Model::initDatabase($dbConfig);
    }
}

// Set error log
// ini_set('error_log', $logDir . '/php_errors.log');