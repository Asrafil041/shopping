<?php
/**
 * Health check endpoint untuk diagnosa aplikasi
 * Akses: https://shopping-production-xxx.up.railway.app/health.php
 */

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'environment' => getenv('ENVIRONMENT') ?: 'unknown',
];

// Cek Database connection
$health['database'] = [
    'host' => getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost'),
    'name' => getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'db_kasir'),
];

try {
    $host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
    $username = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
    $password = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: '123223');
    $dbname = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'db_kasir');

    error_log("[HEALTH] Attempting DB connection to {$host}");
    
    $conn = mysqli_connect($host, $username, $password, $dbname);
    
    if ($conn) {
        $health['database']['status'] = 'connected';
        $health['database']['tables'] = [];
        
        $result = mysqli_query($conn, "SHOW TABLES");
        if ($result) {
            while ($row = mysqli_fetch_row($result)) {
                $health['database']['tables'][] = $row[0];
            }
            $health['database']['table_count'] = count($health['database']['tables']);
        }
        
        mysqli_close($conn);
    } else {
        $health['status'] = 'error';
        $health['database']['status'] = 'failed';
        $health['database']['error'] = mysqli_connect_error();
        error_log("[HEALTH] DB connection failed: " . mysqli_connect_error());
    }
} catch (Exception $e) {
    $health['status'] = 'error';
    $health['error'] = $e->getMessage();
    error_log("[HEALTH] Exception: " . $e->getMessage());
}

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
