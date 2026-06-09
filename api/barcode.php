<?php
/**
 * GET /api/barcode.php - Ambil data barcode user
 * Mengembalikan username (NIM) yang digunakan sebagai nilai barcode
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();

$query = mysqli_query($koneksi, "SELECT id, username, nama, role FROM users WHERE id = '$id_user'");
$user = mysqli_fetch_assoc($query);

if (!$user) {
    sendError("User tidak ditemukan", 404);
}

sendOk([
    "barcode_value" => $user['username'],
    "nama" => $user['nama'],
    "role" => $user['role']
]);
?>
