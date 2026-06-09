<?php
$host = getenv('MYSQLHOST') ?: "localhost";
$user = getenv('MYSQLUSER') ?: "root";
$pass = getenv('MYSQLPASSWORD') ?: "";
$db   = getenv('MYSQLDATABASE') ?: "db_perpus2";
$port = getenv('MYSQLPORT') ?: 3306;

try {
    $koneksi = mysqli_connect($host, $user, $pass, $db, $port);
} catch (Throwable $e) {
    die("Koneksi gagal atau Error: " . $e->getMessage());
}

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>