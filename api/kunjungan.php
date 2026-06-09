<?php
/**
 * GET /api/kunjungan.php - Manajemen kunjungan perpustakaan
 *   ?action=current         → Status kunjungan user saat ini
 *   ?action=history         → Riwayat kunjungan user
 *   ?action=current_visitors → Daftar pengunjung saat ini (admin/karyawan)
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();

$action = $_GET['action'] ?? 'current';
$tgl_hari_ini = date('Y-m-d');

if ($action === 'current') {
    $q_in = mysqli_query($koneksi, "SELECT waktu_checkin FROM checkin_log WHERE id_user = '$id_user' AND DATE(waktu_checkin) = '$tgl_hari_ini' AND tipe = 'checkin' ORDER BY id DESC LIMIT 1");
    $q_out = mysqli_query($koneksi, "SELECT waktu_checkin FROM checkin_log WHERE id_user = '$id_user' AND DATE(waktu_checkin) = '$tgl_hari_ini' AND tipe = 'checkout' ORDER BY id DESC LIMIT 1");
    $last_checkin = mysqli_fetch_assoc($q_in);
    $last_checkout = mysqli_fetch_assoc($q_out);
    $is_inside = false; $checkin_time = null; $duration_minutes = 0;
    if ($last_checkin) {
        $checkin_time = $last_checkin['waktu_checkin'];
        if (!$last_checkout || strtotime($last_checkin['waktu_checkin']) > strtotime($last_checkout['waktu_checkin'])) {
            $is_inside = true;
            $now = new DateTime(); $masuk = new DateTime($checkin_time);
            $duration_minutes = (int)$now->diff($masuk)->i + ($now->diff($masuk)->h * 60);
        }
    }
    sendOk(["is_inside"=>$is_inside,"last_checkin"=>$checkin_time,"last_checkout"=>$last_checkout['waktu_checkin']??null,"duration_minutes"=>$duration_minutes]);

} elseif ($action === 'history') {
    $limit = (int)($_GET['limit'] ?? 30);
    $q = mysqli_query($koneksi, "SELECT id, waktu_checkin, tipe, metode FROM checkin_log WHERE id_user = '$id_user' ORDER BY id DESC LIMIT $limit");
    $visits = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $visits[] = ["id"=>(int)$r['id'],"waktu"=>$r['waktu_checkin'],"tipe"=>$r['tipe'],"metode"=>$r['metode']];
    }
    sendOk($visits, 200, ["total"=>count($visits)]);

} elseif ($action === 'current_visitors') {
    // Staff only
    $role = requireStaff($koneksi, $id_user);
    $q = mysqli_query($koneksi, "SELECT DISTINCT cl.id_user, u.nama, u.username, u.role, MIN(cl.waktu_checkin) as waktu_masuk FROM checkin_log cl JOIN users u ON cl.id_user = u.id WHERE DATE(cl.waktu_checkin) = '$tgl_hari_ini' AND cl.tipe = 'checkin' AND cl.id_user NOT IN (SELECT id_user FROM checkin_log WHERE DATE(waktu_checkin) = '$tgl_hari_ini' AND tipe = 'checkout') GROUP BY cl.id_user");
    $visitors = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $masuk = new DateTime($r['waktu_masuk']); $now = new DateTime(); $dur = $now->diff($masuk);
        $visitors[] = ["id_user"=>(int)$r['id_user'],"nama"=>$r['nama'],"username"=>$r['username'],"role"=>$r['role'],"waktu_masuk"=>$r['waktu_masuk'],"durasi"=>$dur->h." jam ".$dur->i." menit"];
    }
    $q_cin = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM checkin_log WHERE DATE(waktu_checkin) = '$tgl_hari_ini' AND tipe = 'checkin'");
    $q_cout = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM checkin_log WHERE DATE(waktu_checkin) = '$tgl_hari_ini' AND tipe = 'checkout'");
    sendOk(["occupancy"=>count($visitors),"total_checkin_today"=>(int)(mysqli_fetch_assoc($q_cin)['t']??0),"total_checkout_today"=>(int)(mysqli_fetch_assoc($q_cout)['t']??0),"visitors"=>$visitors]);

} else {
    sendError("Action tidak valid", 422);
}
?>
