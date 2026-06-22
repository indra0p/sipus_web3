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
} else {
    // Auto-create loan_rules table if it doesn't exist to fix production errors
    try {
        $check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'loan_rules'");
        if ($check_table && mysqli_num_rows($check_table) == 0) {
            $create_table = "CREATE TABLE loan_rules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role VARCHAR(50) NOT NULL UNIQUE,
                max_days INT NOT NULL DEFAULT 7
            )";
            mysqli_query($koneksi, $create_table);
            
            // Insert default data based on typical roles
            mysqli_query($koneksi, "INSERT IGNORE INTO loan_rules (role, max_days) VALUES ('mahasiswa', 7), ('dosen', 14), ('staff', 14)");
        }
    } catch (Throwable $e) {
        // Silently ignore creation errors so it doesn't break the whole app if permissions are missing
    }
}
?>