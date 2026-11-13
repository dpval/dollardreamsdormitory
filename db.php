<?php
/*
 * Database connection + shared helpers. Every page includes this file.
 */
ini_set('display_errors', '0');   // errors go to the server log, never to visitors
date_default_timezone_set('Asia/Manila');

// config.local.php (your own computer only) wins over config.php (live site)
$configFile = file_exists(__DIR__ . '/config.local.php') ? __DIR__ . '/config.local.php' : __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    exit('Missing config.php - copy config.example.php to config.php and fill it in.');
}
$config = require $configFile;

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name'], (int)($config['db_port'] ?? 3306));

require_once __DIR__ . '/functions.php';

if ($conn->connect_error) {
    error_log('DB connection failed: ' . $conn->connect_error);
    // A page that can live without the database (the landing page) sets $DB_OPTIONAL = true first
    if (!empty($DB_OPTIONAL)) {
        $conn = null;
        return;
    }
    http_response_code(500);
    exit('Database connection failed. Please try again later.');
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'");   // NOW() in Manila time
