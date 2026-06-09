<?php
/**
 * POST /api/daftar.php
 * Body: { "username": "...", "nama": "...", "password": "...", "email": "...", "jenkel": "Laki-laki|Perempuan" }
 * 
 * Endpoint registrasi — TIDAK memerlukan Bearer token
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Method not allowed", 405);
}

$body = getJsonBody();
$username = mysqli_real_escape_string($koneksi, $body['username'] ?? '');
$nama     = mysqli_real_escape_string($koneksi, $body['nama'] ?? '');
$password = mysqli_real_escape_string($koneksi, $body['password'] ?? '');
$email    = mysqli_real_escape_string($koneksi, $body['email'] ?? '');
$jenkel   = mysqli_real_escape_string($koneksi, $body['jenkel'] ?? 'Laki-laki');

if (empty($username) || empty($nama) || empty($password)) {
    sendError("Username, nama, dan password wajib diisi", 422);
}

if (strlen($password) < 4) {
    sendError("Password minimal 4 karakter", 422);
}

$cek = mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username'");
if (mysqli_num_rows($cek) > 0) {
    sendError("Username/NIM sudah terdaftar", 409);
}

$sql = "INSERT INTO users (username, nama, email, password, jenkel, role, status) 
        VALUES ('$username', '$nama', '$email', '$password', '$jenkel', 'mahasiswa', 'active')";

if (mysqli_query($koneksi, $sql)) {
    $newId = mysqli_insert_id($koneksi);
    $token = generateToken($newId);
    sendOk([
        "token" => $token,
        "user" => [
            "id" => $newId,
            "username" => $username,
            "nama" => $nama,
            "email" => $email,
            "role" => "mahasiswa"
        ]
    ], 201);
} else {
    sendError("Registrasi gagal: " . mysqli_error($koneksi), 500);
}
?>
