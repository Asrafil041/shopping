<?php
if (!headers_sent()) {
    header('Content-Type: application/json');
}
mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
$username = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$password = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: '123223');
$dbname = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'db_kasir');

$host = trim($host, " \t\n\r\0\x0B\"'");
$username = trim($username, " \t\n\r\0\x0B\"'");
$dbname = trim($dbname, " \t\n\r\0\x0B\"'");

$response = [
    'status' => 'ok',
    'message' => 'Schema already initialized',
    'database' => [
        'host' => $host,
        'name' => $dbname,
    ],
    'executed_statements' => 0,
    'errors' => [],
];

$sqlFile = __DIR__ . '/db_kasir.sql';
if (!file_exists($sqlFile)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'db_kasir.sql not found in deployment root',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$mysqli = mysqli_init();
mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$connected = @mysqli_real_connect($mysqli, $host, $username, $password, $dbname);

if (!$connected) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB connection failed',
        'error' => mysqli_connect_error(),
        'database' => [
            'host' => $host,
            'name' => $dbname,
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$check = mysqli_query($mysqli, 'SHOW TABLES');
if ($check && mysqli_num_rows($check) > 0) {
    $response['message'] = 'Schema already exists';
    $response['table_count'] = mysqli_num_rows($check);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$sql = file_get_contents($sqlFile);
$statements = [];
$buffer = '';

foreach (preg_split('/\R/', $sql) as $line) {
    $trimmed = trim($line);

    if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*/') || str_starts_with($trimmed, '/*!')) {
        continue;
    }

    $buffer .= $line . "\n";

    if (str_ends_with(rtrim($line), ';')) {
        $statements[] = trim($buffer);
        $buffer = '';
    }
}

foreach ($statements as $statement) {
    if ($statement === '') {
        continue;
    }

    if (!mysqli_query($mysqli, $statement)) {
        $response['errors'][] = [
            'error' => mysqli_error($mysqli),
            'statement' => substr($statement, 0, 120) . (strlen($statement) > 120 ? '...' : ''),
        ];
    } else {
        $response['executed_statements']++;
    }
}

$finalCheck = mysqli_query($mysqli, 'SHOW TABLES');
$response['table_count'] = $finalCheck ? mysqli_num_rows($finalCheck) : 0;

if (!empty($response['errors'])) {
    $response['status'] = 'warning';
    $response['message'] = 'Schema import completed with some errors';
    http_response_code(207);
} elseif ($response['table_count'] > 0) {
    $response['status'] = 'ok';
    $response['message'] = 'Schema import successful';
} else {
    $response['status'] = 'error';
    $response['message'] = 'No tables created';
    http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
