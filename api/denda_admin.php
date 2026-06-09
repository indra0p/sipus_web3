<?php
/**
 * API Denda untuk Admin/Petugas
 * POST /api/denda_admin.php
 *   { "action": "calculate", "id_peminjaman": 5 }  → Hitung denda
 *   { "action": "apply", "id_user": 2, "id_buku": 5, "tipe_denda": "damage", "jumlah": 25000, "catatan": "..." }
 *   { "action": "waive", "id_penalty": 3 }          → Hapuskan denda
 *   { "action": "resolve_dispute", "id_penalty": 3, "keputusan": "waive|reject" }
 * Requires: Bearer token (admin/karyawan only)
 */
require_once 'config.php';
$id_user = requireAuth();
$role = requireStaff($koneksi, $id_user);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Method not allowed", 405);
}

$body = getJsonBody();
$action = $body['action'] ?? '';
$waktu = date('Y-m-d H:i:s');

if ($action === 'calculate') {
    $id_peminjaman = mysqli_real_escape_string($koneksi, $body['id_peminjaman'] ?? '');
    if (empty($id_peminjaman)) sendError("ID peminjaman diperlukan", 422);
    $q = mysqli_query($koneksi, "SELECT p.*, b.judul FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku WHERE p.id_peminjaman = '$id_peminjaman'");
    $loan = mysqli_fetch_assoc($q);
    if (!$loan) sendError("Peminjaman tidak ditemukan", 404);
    $denda = 0; $hari = 0; $tgl_now = date('Y-m-d'); $rate = 2000;
    if ($tgl_now > $loan['tgl_kembali_seharusnya']) {
        $hari = (new DateTime($tgl_now))->diff(new DateTime($loan['tgl_kembali_seharusnya']))->days;
        $q_rate = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'overdue' AND is_active = 1 LIMIT 1");
        $rate = ($q_rate && mysqli_num_rows($q_rate) > 0) ? (float)mysqli_fetch_assoc($q_rate)['tarif'] : 2000;
        $denda = $hari * $rate;
    }
    sendOk(["id_peminjaman"=>(int)$id_peminjaman,"judul"=>$loan['judul'],"tgl_kembali_seharusnya"=>$loan['tgl_kembali_seharusnya'],"hari_terlambat"=>$hari,"tarif_per_hari"=>$rate,"total_denda"=>$denda]);

} elseif ($action === 'apply') {
    $target_user = mysqli_real_escape_string($koneksi, $body['id_user'] ?? '');
    $id_buku = mysqli_real_escape_string($koneksi, $body['id_buku'] ?? '');
    $tipe_denda = mysqli_real_escape_string($koneksi, $body['tipe_denda'] ?? '');
    $jumlah = (float)($body['jumlah'] ?? 0);
    $catatan = mysqli_real_escape_string($koneksi, $body['catatan'] ?? '');
    $id_peminjaman = mysqli_real_escape_string($koneksi, $body['id_peminjaman'] ?? '');
    if (empty($target_user) || empty($tipe_denda) || $jumlah <= 0) sendError("id_user, tipe_denda, dan jumlah wajib diisi", 422);
    $id_buku_val = empty($id_buku) ? "NULL" : "'$id_buku'";
    $id_pinjam_val = empty($id_peminjaman) ? "NULL" : "'$id_peminjaman'";
    $sql = "INSERT INTO penalties (id_user, id_peminjaman, id_buku, tipe_denda, jumlah, catatan, created_by) VALUES ('$target_user', $id_pinjam_val, $id_buku_val, '$tipe_denda', '$jumlah', '$catatan', '$id_user')";
    if (mysqli_query($koneksi, $sql)) {
        sendOk(["id"=>(int)mysqli_insert_id($koneksi),"message"=>"Denda berhasil diterapkan"], 201);
    } else {
        sendError("Gagal menerapkan denda", 500);
    }

} elseif ($action === 'waive') {
    $id_penalty = mysqli_real_escape_string($koneksi, $body['id_penalty'] ?? '');
    if (empty($id_penalty)) sendError("ID penalty diperlukan", 422);
    $q = mysqli_query($koneksi, "SELECT id_user, jumlah FROM penalties WHERE id = '$id_penalty'");
    $pen = mysqli_fetch_assoc($q);
    if (!$pen) sendError("Denda tidak ditemukan", 404);
    mysqli_query($koneksi, "UPDATE penalties SET status = 'waived', resolved_by = '$id_user', resolved_at = '$waktu' WHERE id = '$id_penalty'");
    sendOk(["id_penalty"=>(int)$id_penalty,"status"=>"waived","message"=>"Denda berhasil dihapuskan"]);

} elseif ($action === 'resolve_dispute') {
    $id_penalty = mysqli_real_escape_string($koneksi, $body['id_penalty'] ?? '');
    $keputusan = $body['keputusan'] ?? '';
    if (empty($id_penalty) || !in_array($keputusan, ['waive', 'reject'])) sendError("id_penalty dan keputusan (waive/reject) wajib diisi", 422);
    $q = mysqli_query($koneksi, "SELECT * FROM penalties WHERE id = '$id_penalty' AND status = 'disputed'");
    $pen = mysqli_fetch_assoc($q);
    if (!$pen) sendError("Dispute tidak ditemukan", 404);
    if ($keputusan === 'waive') {
        mysqli_query($koneksi, "UPDATE penalties SET status = 'waived', resolved_by = '$id_user', resolved_at = '$waktu' WHERE id = '$id_penalty'");
        sendOk(["id_penalty"=>(int)$id_penalty,"status"=>"waived","message"=>"Dispute diterima, denda dihapuskan"]);
    } else {
        mysqli_query($koneksi, "UPDATE penalties SET status = 'unpaid', resolved_by = '$id_user', resolved_at = '$waktu' WHERE id = '$id_penalty'");
        sendOk(["id_penalty"=>(int)$id_penalty,"status"=>"unpaid","message"=>"Dispute ditolak, denda tetap berlaku"]);
    }

} else {
    sendError("Action tidak valid. Gunakan 'calculate', 'apply', 'waive', atau 'resolve_dispute'", 422);
}
?>
