<?php
function createNotification($koneksi, $id_user, $judul, $pesan, $tipe = 'info', $link = null) {
    $id_user = mysqli_real_escape_string($koneksi, $id_user);
    $judul   = mysqli_real_escape_string($koneksi, $judul);
    $pesan   = mysqli_real_escape_string($koneksi, $pesan);
    $tipe    = mysqli_real_escape_string($koneksi, $tipe);
    $link    = $link ? "'" . mysqli_real_escape_string($koneksi, $link) . "'" : "NULL";
    $sql = "INSERT INTO notifications (id_user, judul, pesan, tipe, link) VALUES ('$id_user', '$judul', '$pesan', '$tipe', $link)";
    return mysqli_query($koneksi, $sql);
}

function getUnreadCount($koneksi, $id_user) {
    $q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM notifications WHERE id_user = '$id_user' AND is_read = 0");
    if ($q) { $r = mysqli_fetch_assoc($q); return (int)($r['total'] ?? 0); }
    return 0;
}

function getOverdueRate($koneksi) {
    $q = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'overdue' AND is_active = 1 LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) { $r = mysqli_fetch_assoc($q); return (float)$r['tarif']; }
    return 2000;
}
?>
