<?php
/**
 * GET /api/riwayat.php - Riwayat peminjaman lengkap user
 * Termasuk semua status (selesai, ditolak, dll)
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();
$baseUrl = getBaseUrl();

$sql = "SELECT p.*, b.judul, b.pengarang, b.sampul, b.jenis_buku
        FROM peminjaman p 
        JOIN buku b ON p.id_buku = b.id_buku 
        WHERE p.id_user = '$id_user'
        ORDER BY p.id_peminjaman DESC";
$query = mysqli_query($koneksi, $sql);
$history = [];

while ($row = mysqli_fetch_assoc($query)) {
    $sampul_url = null;
    if (!empty($row['sampul'])) {
        $sampul_url = $baseUrl . "/assets/img/sampul/" . $row['sampul'];
    }
    
    $history[] = [
        "id_peminjaman" => (int)$row['id_peminjaman'],
        "id_buku" => (int)$row['id_buku'],
        "judul" => $row['judul'],
        "pengarang" => $row['pengarang'],
        "jenis_buku" => $row['jenis_buku'],
        "sampul" => $sampul_url,
        "tgl_pinjam" => $row['tgl_pinjam'],
        "tgl_kembali_seharusnya" => $row['tgl_kembali_seharusnya'],
        "tgl_kembali_asli" => $row['tgl_kembali_asli'],
        "status" => $row['status'],
        "kondisi_kembali" => $row['kondisi_kembali'] ?? null,
        "denda" => (int)$row['denda'],
        "catatan_admin" => $row['catatan_admin'] ?? null
    ];
}

sendOk($history, 200, ["total" => count($history)]);
?>
