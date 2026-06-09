<?php
/**
 * API Petugas/Admin
 * GET  ?action=search_patrons&keyword=xxx → Cari anggota by nama/ID
 *      ?action=patron_profile&id=5        → Profil anggota + riwayat
 *      ?action=dashboard                  → Live stats dashboard
 *      ?action=find_patron&id=5           → Cari patron by exact ID
 * POST { "action": "scan_book"|"issue" }
 * Requires: Bearer token (admin/karyawan only)
 */
require_once 'config.php';
$id_user = requireAuth();
$role = requireStaff($koneksi, $id_user);
$baseUrl = getBaseUrl();
$tgl_now = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'search_patrons') {
        $keyword = mysqli_real_escape_string($koneksi, $_GET['keyword'] ?? '');
        if (empty($keyword)) sendError("Keyword pencarian diperlukan", 422);
        $q = mysqli_query($koneksi, "SELECT id, username, nama, email, role, jenkel, status FROM users WHERE role != 'admin' AND (nama LIKE '%$keyword%' OR username LIKE '%$keyword%' OR id = '$keyword') ORDER BY nama LIMIT 20");
        $patrons = [];
        while ($r = mysqli_fetch_assoc($q)) { $r['id']=(int)$r['id']; $patrons[]=$r; }
        sendOk($patrons, 200, ["total"=>count($patrons)]);

    } elseif ($action === 'find_patron') {
        // Cari patron by exact ID
        $pid = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');
        if (empty($pid)) sendError("ID diperlukan", 422);
        $q = mysqli_query($koneksi, "SELECT id, username, nama, email, role, jenkel, status FROM users WHERE id = '$pid'");
        $patron = mysqli_fetch_assoc($q);
        if (!$patron) sendError("User tidak ditemukan", 404);
        $patron['id'] = (int)$patron['id'];
        sendOk($patron);

    } elseif ($action === 'patron_profile') {
        $pid = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');
        if (empty($pid)) sendError("ID diperlukan", 422);
        $q = mysqli_query($koneksi, "SELECT id, username, nama, email, role, jenkel, foto, status, borrowing_limit FROM users WHERE id = '$pid'");
        $patron = mysqli_fetch_assoc($q);
        if (!$patron) sendError("User tidak ditemukan", 404);
        $foto_url = !empty($patron['foto']) ? $baseUrl."/assets/img/profil/".$patron['foto'] : null;
        $q_aktif = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE id_user = '$pid' AND status = 'Dipinjam'");
        $q_total = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE id_user = '$pid'");
        $q_denda = mysqli_query($koneksi, "SELECT SUM(jumlah - jumlah_dibayar) as t FROM penalties WHERE id_user = '$pid' AND status IN ('unpaid','partial')");
        // Riwayat pinjaman terbaru
        $q_hist = mysqli_query($koneksi, "SELECT p.id_peminjaman, b.judul, p.status, p.tgl_pinjam, p.tgl_kembali_seharusnya FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku WHERE p.id_user = '$pid' ORDER BY p.id_peminjaman DESC LIMIT 10");
        $history = [];
        while ($r = mysqli_fetch_assoc($q_hist)) { $r['id_peminjaman']=(int)$r['id_peminjaman']; $history[]=$r; }
        sendOk(["id"=>(int)$patron['id'],"username"=>$patron['username'],"nama"=>$patron['nama'],"email"=>$patron['email'],"role"=>$patron['role'],"jenkel"=>$patron['jenkel'],"foto"=>$foto_url,"status"=>$patron['status'],"borrowing_limit"=>(int)$patron['borrowing_limit'],"stats"=>["pinjaman_aktif"=>(int)(mysqli_fetch_assoc($q_aktif)['t']??0),"total_pinjam"=>(int)(mysqli_fetch_assoc($q_total)['t']??0),"total_denda_belum_lunas"=>(float)(mysqli_fetch_assoc($q_denda)['t']??0)],"riwayat_terbaru"=>$history]);

    } elseif ($action === 'dashboard') {
        // Live stats dashboard
        $q_members = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role IN ('mahasiswa','dosen','karyawan')");
        $q_books = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM buku");
        $q_active = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE status = 'Dipinjam'");
        $q_pending_b = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE status = 'Menunggu'");
        $q_pending_r = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE status = 'Pengajuan_Kembali'");
        $q_pending_e = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE perpanjangan_status = 'requested'");
        $q_overdue = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE status = 'Dipinjam' AND tgl_kembali_seharusnya < '$tgl_now'");
        $q_occ = mysqli_query($koneksi, "SELECT COUNT(DISTINCT cl.id_user) as t FROM checkin_log cl WHERE DATE(cl.waktu_checkin) = '$tgl_now' AND cl.tipe = 'checkin' AND cl.id_user NOT IN (SELECT id_user FROM checkin_log WHERE DATE(waktu_checkin)='$tgl_now' AND tipe='checkout')");
        $q_visitors = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_user) as t FROM checkin_log WHERE DATE(waktu_checkin) = '$tgl_now' AND tipe = 'checkin'");
        $q_disputed = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM penalties WHERE status = 'disputed'");
        $q_unpaid = mysqli_query($koneksi, "SELECT SUM(jumlah - jumlah_dibayar) as t FROM penalties WHERE status IN ('unpaid','partial')");
        // Buku paling banyak dipinjam
        $q_popular = mysqli_query($koneksi, "SELECT b.judul, COUNT(*) as total FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku GROUP BY p.id_buku ORDER BY total DESC LIMIT 5");
        $popular = [];
        while ($r = mysqli_fetch_assoc($q_popular)) { $popular[] = $r; }

        sendOk(["total_anggota"=>(int)(mysqli_fetch_assoc($q_members)['t']??0),"total_buku"=>(int)(mysqli_fetch_assoc($q_books)['t']??0),"pinjaman_aktif"=>(int)(mysqli_fetch_assoc($q_active)['t']??0),"pending_peminjaman"=>(int)(mysqli_fetch_assoc($q_pending_b)['t']??0),"pending_pengembalian"=>(int)(mysqli_fetch_assoc($q_pending_r)['t']??0),"pending_perpanjangan"=>(int)(mysqli_fetch_assoc($q_pending_e)['t']??0),"peminjaman_terlambat"=>(int)(mysqli_fetch_assoc($q_overdue)['t']??0),"occupancy"=>(int)(mysqli_fetch_assoc($q_occ)['t']??0),"pengunjung_hari_ini"=>(int)(mysqli_fetch_assoc($q_visitors)['t']??0),"dispute_pending"=>(int)(mysqli_fetch_assoc($q_disputed)['t']??0),"total_denda_belum_lunas"=>(float)(mysqli_fetch_assoc($q_unpaid)['t']??0),"buku_populer"=>$popular,"timestamp"=>date('Y-m-d H:i:s')]);
    } else {
        sendError("Action tidak valid", 422);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? '';

    if ($action === 'scan_book') {
        $barcode = mysqli_real_escape_string($koneksi, $body['barcode'] ?? '');
        if (empty($barcode)) sendError("Barcode diperlukan", 422);
        $q = mysqli_query($koneksi, "SELECT * FROM buku WHERE barcode = '$barcode' OR id_buku = '$barcode'");
        $book = mysqli_fetch_assoc($q);
        if (!$book) sendError("Buku tidak ditemukan", 404);
        $sampul_url = !empty($book['sampul']) ? $baseUrl."/assets/img/sampul/".$book['sampul'] : null;
        $q_loan = mysqli_query($koneksi, "SELECT p.*, u.nama, u.username FROM peminjaman p JOIN users u ON p.id_user = u.id WHERE p.id_buku = '{$book['id_buku']}' AND p.status = 'Dipinjam'");
        $borrowers = [];
        while ($r = mysqli_fetch_assoc($q_loan)) {
            $borrowers[] = ["id_peminjaman"=>(int)$r['id_peminjaman'],"nama"=>$r['nama'],"username"=>$r['username'],"tgl_pinjam"=>$r['tgl_pinjam'],"tgl_kembali_seharusnya"=>$r['tgl_kembali_seharusnya']];
        }
        sendOk(["id_buku"=>(int)$book['id_buku'],"barcode"=>$book['barcode'],"judul"=>$book['judul'],"pengarang"=>$book['pengarang'],"penerbit"=>$book['penerbit'],"jenis_buku"=>$book['jenis_buku'],"stok"=>(int)$book['stok'],"sampul"=>$sampul_url,"sedang_dipinjam_oleh"=>$borrowers]);

    } elseif ($action === 'issue') {
        $username = mysqli_real_escape_string($koneksi, $body['username'] ?? '');
        $id_buku = mysqli_real_escape_string($koneksi, $body['id_buku'] ?? '');
        if (empty($username) || empty($id_buku)) sendError("Username dan id_buku wajib diisi", 422);
        $q_u = mysqli_query($koneksi, "SELECT id, role FROM users WHERE username = '$username'");
        $patron = mysqli_fetch_assoc($q_u);
        if (!$patron) sendError("User tidak ditemukan", 404);
        $q_b = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id_buku = '$id_buku'");
        $book = mysqli_fetch_assoc($q_b);
        if (!$book) sendError("Buku tidak ditemukan", 404);
        if ($book['stok'] <= 0) sendError("Stok habis", 422);
        $prole = $patron['role'];
        $durasi = ($prole=='dosen')?'+30 days':(($prole=='karyawan')?'+14 days':'+7 days');
        $tgl_pinjam = date('Y-m-d'); $tgl_kembali = date('Y-m-d', strtotime($durasi)); $waktu = date('Y-m-d H:i:s');
        $sql = "INSERT INTO peminjaman (id_user, id_buku, tgl_pinjam, tgl_kembali_seharusnya, status, approved_by, approved_at) VALUES ('{$patron['id']}', '$id_buku', '$tgl_pinjam', '$tgl_kembali', 'Dipinjam', '$id_user', '$waktu')";
        if (mysqli_query($koneksi, $sql)) {
            mysqli_query($koneksi, "UPDATE buku SET stok = stok - 1 WHERE id_buku = '$id_buku'");
            sendOk(["id_peminjaman"=>(int)mysqli_insert_id($koneksi),"tgl_pinjam"=>$tgl_pinjam,"tgl_kembali"=>$tgl_kembali,"message"=>"Buku berhasil dipinjamkan"], 201);
        } else {
            sendError("Gagal memproses", 500);
        }
    } else {
        sendError("Action tidak valid", 422);
    }
} else {
    sendError("Method not allowed", 405);
}
?>
