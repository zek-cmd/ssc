<?php
// config/database.php

// Define constants for backward compatibility
if (!defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', $_ENV['DB_NAME'] ?? '');
}

if (!defined('DB_USER')) {
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
}

// Create $dbConfig array for Model::initDatabase()
$dbConfig = [
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => DB_CHARSET,
    'collation' => 'utf8mb4_general_ci'
];