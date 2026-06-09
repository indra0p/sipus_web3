<?php
/**
 * GET /api/profil.php - Ambil profil user yang sedang login
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();
$baseUrl = getBaseUrl();

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$id_user'");
$user = mysqli_fetch_assoc($query);

if (!$user) {
    sendError("User tidak ditemukan", 404);
}

$foto_url = null;
if (!empty($user['foto'])) {
    $foto_url = $baseUrl . "/assets/img/profil/" . $user['foto'];
}

// Stats
$q_total = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE id_user='$id_user'");
$total_pinjam = mysqli_fetch_assoc($q_total)['t'] ?? 0;

$q_aktif = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE id_user='$id_user' AND status='Dipinjam'");
$aktif = mysqli_fetch_assoc($q_aktif)['t'] ?? 0;

$q_selesai = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE id_user='$id_user' AND status='Kembali'");
$selesai = mysqli_fetch_assoc($q_selesai)['t'] ?? 0;

// Denda belum lunas
$q_denda = mysqli_query($koneksi, "SELECT SUM(jumlah - jumlah_dibayar) as t FROM penalties WHERE id_user='$id_user' AND status IN ('unpaid','partial')");
$denda = (float)(mysqli_fetch_assoc($q_denda)['t'] ?? 0);

sendOk([
    "id" => (int)$user['id'],
    "username" => $user['username'],
    "nama" => $user['nama'] ?? '',
    "email" => $user['email'] ?? '',
    "jenkel" => $user['jenkel'] ?? '',
    "role" => $user['role'],
    "status" => $user['status'] ?? 'active',
    "borrowing_limit" => (int)($user['borrowing_limit'] ?? 2),
    "foto" => $foto_url,
    "stats" => [
        "total_pinjam" => (int)$total_pinjam,
        "pinjaman_aktif" => (int)$aktif,
        "selesai" => (int)$selesai,
        "denda_belum_lunas" => $denda
    ]
]);
?>
