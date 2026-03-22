<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Database connection
$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: "localhost");
$username = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: "root");
$password = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: "123223");
$dbname = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: "db_kasir");

$host = trim($host, " \t\n\r\0\x0B\"'");
$username = trim($username, " \t\n\r\0\x0B\"'");
$dbname = trim($dbname, " \t\n\r\0\x0B\"'");

if (strpos($host, '${{') !== false || strpos($host, 'RAILWAY_PRIVATE_DOMAIN') !== false) {
    error_log("[ERROR] Invalid DB_HOST placeholder detected: {$host}");
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "Application Error: Invalid DB_HOST value\n";
    echo "Use MySQL host from Railway MySQL service (MYSQLHOST), not RAILWAY_PRIVATE_DOMAIN.\n";
    exit(1);
}

// Log connection attempt to stderr for Railway debug
error_log("[APP] Attempting MySQL connection to {$host}/{$dbname} as user {$username}");

$mysqli = mysqli_init();
mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$connected = mysqli_real_connect($mysqli, $host, $username, $password, $dbname);

if ($connected) {
    $conn = $mysqli;
}

if (!$connected) {
    $error = mysqli_connect_error();
    error_log("[ERROR] MySQL Connection Failed: " . $error);
    error_log("[ERROR] Host: {$host}, DB: {$dbname}, User: {$username}");
    
    // Don't die immediately, show error page instead
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "Application Error: Database Connection Failed\n";
    echo "Error: " . htmlspecialchars($error) . "\n";
    echo "Host: " . htmlspecialchars($host) . "\n";
    echo "Database: " . htmlspecialchars($dbname) . "\n";
    echo "\nPlease check Railway logs or contact admin.\n";
    exit(1);
}

error_log("[APP] MySQL connection successful");

mysqli_set_charset($conn, 'utf8mb4');
date_default_timezone_set('Asia/Jakarta');
?>
