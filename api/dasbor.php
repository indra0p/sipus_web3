<?php
/**
 * GET /api/dasbor.php - Statistik dashboard user (patron)
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();

// Total koleksi buku
$q_buku = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM buku");
$buku_tersedia = mysqli_fetch_assoc($q_buku)['total'] ?? 0;

// Buku sedang dipinjam user ini
$q_pinjam = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman WHERE id_user = '$id_user' AND status = 'Dipinjam'");
$total_dipinjam = mysqli_fetch_assoc($q_pinjam)['total'] ?? 0;

// Total riwayat peminjaman
$q_riwayat = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman WHERE id_user = '$id_user'");
$total_riwayat = mysqli_fetch_assoc($q_riwayat)['total'] ?? 0;

// Denda aktif
$total_denda = 0;
$tgl_now = date('Y-m-d');
$q_denda = mysqli_query($koneksi, "SELECT tgl_kembali_seharusnya FROM peminjaman WHERE id_user = '$id_user' AND status = 'Dipinjam'");
while ($row = mysqli_fetch_assoc($q_denda)) {
    if ($tgl_now > $row['tgl_kembali_seharusnya']) {
        $tgl1 = new DateTime($row['tgl_kembali_seharusnya']);
        $tgl2 = new DateTime($tgl_now);
        $total_denda += ($tgl2->diff($tgl1)->days * 2000);
    }
}

sendOk([
    "total_buku" => (int)$buku_tersedia,
    "total_dipinjam" => (int)$total_dipinjam,
    "total_riwayat" => (int)$total_riwayat,
    "total_denda" => (int)$total_denda
]);
?>
