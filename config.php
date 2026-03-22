<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
mysqli_report(MYSQLI_REPORT_OFF);

// Database connection
$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: "localhost");
$username = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: "root");
$password = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: "123223");

$host = trim($host, " \t\n\r\0\x0B\"'");
$username = trim($username, " \t\n\r\0\x0B\"'");

$isRailwayHost = stripos($host, 'railway') !== false;
$dbname = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: ($isRailwayHost ? '' : 'db_kasir'));
$dbname = trim($dbname, " \t\n\r\0\x0B\"'");

if (strpos($host, '${{') !== false || strpos($host, 'RAILWAY_PRIVATE_DOMAIN') !== false) {
    error_log("[ERROR] Invalid DB_HOST placeholder detected: {$host}");
    http_response_code(503);
    if (!headers_sent()) {
        header('Content-Type: text/plain');
    }
    echo "Application Error: Invalid DB_HOST value\n";
    echo "Use MySQL host from Railway MySQL service (MYSQLHOST), not RAILWAY_PRIVATE_DOMAIN.\n";
    exit(1);
}

if ($isRailwayHost && $dbname === '') {
    error_log('[ERROR] DB_NAME/MYSQLDATABASE is empty for Railway host');
    http_response_code(503);
    if (!headers_sent()) {
        header('Content-Type: text/plain');
    }
    echo "Application Error: Database name is not configured\n";
    echo 'Set DB_NAME to ${{MySQL.MYSQLDATABASE}} in Railway Variables.' . "\n";
    exit(1);
}

// Log connection attempt to stderr for Railway debug
error_log("[APP] Attempting MySQL connection to {$host}/{$dbname} as user {$username}");

$mysqli = mysqli_init();
mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$connected = @mysqli_real_connect($mysqli, $host, $username, $password, $dbname);

if ($connected) {
    $conn = $mysqli;
}

if (!$connected) {
    $error = mysqli_connect_error();
    error_log("[ERROR] MySQL Connection Failed: " . $error);
    error_log("[ERROR] Host: {$host}, DB: {$dbname}, User: {$username}");
    
    // Don't die immediately, show error page instead
    http_response_code(503);
    if (!headers_sent()) {
        header('Content-Type: text/plain');
    }
    echo "Application Error: Database Connection Failed\n";
    echo "Error: " . htmlspecialchars($error) . "\n";
    echo "Host: " . htmlspecialchars($host) . "\n";
    echo "Database: " . htmlspecialchars($dbname) . "\n";
    echo "\nPlease check Railway logs or contact admin.\n";
    exit(1);
}

if ($isRailwayHost) {
    $tableCount = 0;
    $tableResult = mysqli_query($conn, 'SHOW TABLES');
    if ($tableResult) {
        $tableCount = mysqli_num_rows($tableResult);
    }

    if ($tableCount === 0 && $dbname !== 'db_kasir') {
        $fallbackMysqli = mysqli_init();
        mysqli_options($fallbackMysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        $fallbackConnected = @mysqli_real_connect($fallbackMysqli, $host, $username, $password, 'db_kasir');

        if ($fallbackConnected) {
            $fallbackTables = mysqli_query($fallbackMysqli, 'SHOW TABLES');
            $fallbackCount = $fallbackTables ? mysqli_num_rows($fallbackTables) : 0;

            if ($fallbackCount > 0) {
                mysqli_close($conn);
                $conn = $fallbackMysqli;
                $dbname = 'db_kasir';
                error_log('[APP] Auto-switched database from empty default DB to db_kasir');
            } else {
                mysqli_close($fallbackMysqli);
            }
        }
    }
}

error_log("[APP] MySQL connection successful");

mysqli_set_charset($conn, 'utf8mb4');
date_default_timezone_set('Asia/Jakarta');
?>
