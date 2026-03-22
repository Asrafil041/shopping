<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Database connection
$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: "localhost");
$username = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: "root");
$password = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: "123223");
$dbname = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: "db_kasir");

// Log connection attempt to stderr for Railway debug
error_log("[APP] Attempting MySQL connection to {$host}/{$dbname} as user {$username}");

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
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
