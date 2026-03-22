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
    'instructions' => [],
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
        $health['instructions'][] = "SUCCESS: Database connection successful!";
    } else {
        $health['status'] = 'error';
        $health['database']['status'] = 'failed';
        $error = mysqli_connect_error();
        $health['database']['error'] = $error;
        error_log("[HEALTH] DB connection failed: " . $error);
        
        // Provide detailed diagnosis and instructions
        if (strpos($error, 'Connection refused') !== false) {
            $health['instructions'][] = "ERROR: Connection refused - MySQL service is not accessible";
            $health['instructions'][] = "ACTION: You need to add a MySQL service in Railway:";
            $health['instructions'][] = "  1. Go to your Railway project dashboard";
            $health['instructions'][] = "  2. Click '+ Add Service' or 'New'";
            $health['instructions'][] = "  3. Select 'MySQL' from the options";
            $health['instructions'][] = "  4. Wait for MySQL service to start (status shows green)";
            $health['instructions'][] = "  5. Open MySQL service  Variables tab";
            $health['instructions'][] = "  6. Copy the connection details";
            $health['instructions'][] = "  7. In 'shopping' service  Variables, set: DB_HOST, DB_USER, DB_PASS";
            $health['instructions'][] = "  8. Redeploy shopping service";
        } elseif (strpos($error, 'Access denied') !== false) {
            $health['instructions'][] = "ERROR: Access denied - Wrong MySQL credentials";
            $health['instructions'][] = "ACTION: Update credentials in environment variables:";
            $health['instructions'][] = "  1. Go to 'shopping' service  Variables tab";
            $health['instructions'][] = "  2. Update these variables (get values from MySQL service):";
            $health['instructions'][] = "     - DB_HOST = your_railway_mysql_host";
            $health['instructions'][] = "     - DB_USER = your_mysql_user";
            $health['instructions'][] = "     - DB_PASS = your_mysql_password";
            $health['instructions'][] = "     - DB_NAME = db_kasir";
            $health['instructions'][] = "  3. Redeploy shopping service";
        } elseif (strpos($error, 'Unknown database') !== false) {
            $health['instructions'][] = "ERROR: Unknown database 'db_kasir' - Database not created yet";
            $health['instructions'][] = "ACTION: Import database schema:";
            $health['instructions'][] = "  1. Download or get db_kasir.sql file";
            $health['instructions'][] = "  2. Use MySQL client to connect (DBeaver, MySQL Workbench, etc)";
            $health['instructions'][] = "  3. Host: " . $host . " | User: " . $username;
            $health['instructions'][] = "  4. Execute SQL: CREATE DATABASE db_kasir;";
            $health['instructions'][] = "  5. Import: mysql -h " . $host . " -u " . $username . " -p db_kasir < db_kasir.sql";
        } else {
            $health['instructions'][] = "ERROR: Database connection failed";
            $health['instructions'][] = "Host: {$host}";
            $health['instructions'][] = "Database: {$dbname}";
            $health['instructions'][] = "User: {$username}";
            $health['instructions'][] = "Error: {$error}";
        }
    }
} catch (Exception $e) {
    $health['status'] = 'error';
    $health['error'] = $e->getMessage();
    $health['instructions'][] = "EXCEPTION: " . $e->getMessage();
    error_log("[HEALTH] Exception: " . $e->getMessage());
}

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
