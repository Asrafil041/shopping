<?php
$host = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "123223";
$dbname = getenv('DB_NAME') ?: "db_kasir";

$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn){
        die("Connection Failed:".mysqli_connect_error());
    }

mysqli_set_charset($conn, 'utf8mb4');
    
date_default_timezone_set('Asia/Jakarta');   
?>