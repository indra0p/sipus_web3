<?php
/**
 * GET /api/aturan_denda.php - Ambil konfigurasi aturan denda aktif
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();

$q = mysqli_query($koneksi, "SELECT * FROM penalty_rules WHERE is_active = 1 ORDER BY tipe_denda, id");
$rules = [];
while ($r = mysqli_fetch_assoc($q)) {
    $rules[] = [
        "id" => (int)$r['id'],
        "tipe_denda" => $r['tipe_denda'],
        "nama_aturan" => $r['nama_aturan'],
        "tarif" => (float)$r['tarif'],
        "satuan" => $r['satuan'],
        "deskripsi" => $r['deskripsi']
    ];
}

sendOk($rules, 200, ["total" => count($rules)]);
?>
