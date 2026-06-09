<?php
/**
 * API Pengembalian — Endpoint baru untuk cek kondisi buku + denda otomatis
 * 
 * GET  /api/pengembalian.php
 *   ?action=check&id_peminjaman=N  → Cek kondisi buku + estimasi denda otomatis
 *   ?action=my_returns             → Daftar pengembalian saya
 * 
 * POST /api/pengembalian.php
 *   { "action": "process", "id_peminjaman": N, "kondisi": "baik|rusak_ringan|rusak_berat|hilang" }
 *     → Proses pengembalian oleh admin (cek kondisi + denda otomatis)
 *   { "action": "request", "id_peminjaman": N, "kondisi": "baik" }
 *     → Patron mengajukan pengembalian
 * 
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();
$tgl_now = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'check';

    if ($action === 'check') {
        // Cek kondisi buku + estimasi denda — bisa dipanggil oleh siapa saja (owner atau admin)
        $id_p = mysqli_real_escape_string($koneksi, $_GET['id_peminjaman'] ?? '');
        if (empty($id_p)) sendError("id_peminjaman diperlukan", 422);

        $q = mysqli_query($koneksi, "SELECT p.*, b.judul, b.pengarang, u.nama as nama_peminjam, u.username 
            FROM peminjaman p 
            JOIN buku b ON p.id_buku = b.id_buku 
            JOIN users u ON p.id_user = u.id 
            WHERE p.id_peminjaman = '$id_p'");
        $loan = mysqli_fetch_assoc($q);
        if (!$loan) sendError("Peminjaman tidak ditemukan", 404);

        // Cek apakah user adalah pemilik atau staff
        $q_role = mysqli_query($koneksi, "SELECT role FROM users WHERE id = '$id_user'");
        $me = mysqli_fetch_assoc($q_role);
        $is_staff = in_array($me['role'] ?? '', ['admin', 'karyawan']);
        if ($loan['id_user'] != $id_user && !$is_staff) {
            sendError("Akses ditolak", 403);
        }

        // Hitung denda keterlambatan
        $denda_terlambat = 0;
        $hari_terlambat = 0;
        if ($tgl_now > $loan['tgl_kembali_seharusnya']) {
            $hari_terlambat = (new DateTime($tgl_now))->diff(new DateTime($loan['tgl_kembali_seharusnya']))->days;
            // Ambil tarif dari penalty_rules
            $q_rate = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'overdue' AND is_active = 1 LIMIT 1");
            $rate = ($q_rate && mysqli_num_rows($q_rate) > 0) ? (float)mysqli_fetch_assoc($q_rate)['tarif'] : 2000;
            $denda_terlambat = $hari_terlambat * $rate;
        }

        // Estimasi denda berdasarkan kondisi
        $estimasi_kondisi = [];
        $kondisi_options = ['baik','rusak_ringan','rusak_berat','hilang'];
        foreach ($kondisi_options as $k) {
            $denda_k = 0;
            if ($k === 'rusak_ringan') {
                $q_r = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'damage' AND nama_aturan LIKE '%Ringan%' AND is_active = 1 LIMIT 1");
                $denda_k = ($q_r && mysqli_num_rows($q_r) > 0) ? (float)mysqli_fetch_assoc($q_r)['tarif'] : 25000;
            } elseif ($k === 'rusak_berat') {
                $q_r = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'damage' AND nama_aturan LIKE '%Berat%' AND is_active = 1 LIMIT 1");
                $denda_k = ($q_r && mysqli_num_rows($q_r) > 0) ? (float)mysqli_fetch_assoc($q_r)['tarif'] : 75000;
            } elseif ($k === 'hilang') {
                $q_r = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'lost' AND is_active = 1 LIMIT 1");
                $denda_k = ($q_r && mysqli_num_rows($q_r) > 0) ? (float)mysqli_fetch_assoc($q_r)['tarif'] : 0;
                $denda_k += 25000; // biaya admin
            }
            $estimasi_kondisi[] = ["kondisi"=>$k,"denda_kondisi"=>$denda_k,"total_estimasi"=>$denda_terlambat+$denda_k];
        }

        sendOk([
            "id_peminjaman"=>(int)$loan['id_peminjaman'],
            "judul"=>$loan['judul'],
            "pengarang"=>$loan['pengarang'],
            "peminjam"=>["nama"=>$loan['nama_peminjam'],"username"=>$loan['username']],
            "tgl_pinjam"=>$loan['tgl_pinjam'],
            "tgl_kembali_seharusnya"=>$loan['tgl_kembali_seharusnya'],
            "status"=>$loan['status'],
            "kondisi_kembali"=>$loan['kondisi_kembali'] ?? null,
            "hari_terlambat"=>$hari_terlambat,
            "denda_terlambat"=>$denda_terlambat,
            "estimasi_per_kondisi"=>$estimasi_kondisi
        ]);

    } elseif ($action === 'my_returns') {
        // Riwayat pengembalian user
        $baseUrl = getBaseUrl();
        $q = mysqli_query($koneksi, "SELECT p.*, b.judul, b.sampul FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku WHERE p.id_user = '$id_user' AND p.status IN ('Kembali','Pengajuan_Kembali') ORDER BY p.id_peminjaman DESC");
        $returns = [];
        while ($r = mysqli_fetch_assoc($q)) {
            $sampul = !empty($r['sampul']) ? $baseUrl."/assets/img/sampul/".$r['sampul'] : null;
            $returns[] = ["id_peminjaman"=>(int)$r['id_peminjaman'],"judul"=>$r['judul'],"sampul"=>$sampul,"tgl_pinjam"=>$r['tgl_pinjam'],"tgl_kembali_seharusnya"=>$r['tgl_kembali_seharusnya'],"tgl_kembali_asli"=>$r['tgl_kembali_asli'],"status"=>$r['status'],"kondisi_kembali"=>$r['kondisi_kembali'],"denda"=>(int)($r['denda']??0)];
        }
        sendOk($returns, 200, ["total"=>count($returns)]);
    } else {
        sendError("Action tidak valid", 422);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? '';

    if ($action === 'request') {
        // Patron mengajukan pengembalian
        $id_p = mysqli_real_escape_string($koneksi, $body['id_peminjaman'] ?? '');
        $kondisi = mysqli_real_escape_string($koneksi, $body['kondisi'] ?? 'baik');
        if (empty($id_p)) sendError("id_peminjaman wajib diisi", 422);
        $valid = ['baik','rusak_ringan','rusak_berat','hilang'];
        if (!in_array($kondisi, $valid)) sendError("Kondisi tidak valid", 422);
        $cek = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id_peminjaman='$id_p' AND id_user='$id_user' AND status='Dipinjam'");
        if (mysqli_num_rows($cek)==0) sendError("Peminjaman tidak ditemukan atau sudah diproses", 404);
        mysqli_query($koneksi, "UPDATE peminjaman SET status='Pengajuan_Kembali', kondisi_kembali='$kondisi' WHERE id_peminjaman='$id_p'");
        sendOk(["id_peminjaman"=>(int)$id_p,"status"=>"Pengajuan_Kembali","kondisi"=>$kondisi,"message"=>"Pengajuan pengembalian berhasil"]);

    } elseif ($action === 'process') {
        // Admin: proses pengembalian + denda otomatis
        $role = requireStaff($koneksi, $id_user);
        $id_p = mysqli_real_escape_string($koneksi, $body['id_peminjaman'] ?? '');
        $kondisi = mysqli_real_escape_string($koneksi, $body['kondisi'] ?? 'baik');
        $catatan = mysqli_real_escape_string($koneksi, $body['catatan'] ?? '');
        if (empty($id_p)) sendError("id_peminjaman wajib diisi", 422);

        $q = mysqli_query($koneksi, "SELECT p.*, b.id_buku FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku WHERE p.id_peminjaman = '$id_p' AND p.status IN ('Dipinjam','Pengajuan_Kembali')");
        $loan = mysqli_fetch_assoc($q);
        if (!$loan) sendError("Peminjaman tidak ditemukan atau sudah dikembalikan", 404);

        $waktu = date('Y-m-d H:i:s');
        $penalties_created = [];

        // 1. Denda keterlambatan
        $denda_terlambat = 0;
        if ($tgl_now > $loan['tgl_kembali_seharusnya']) {
            $hari = (new DateTime($tgl_now))->diff(new DateTime($loan['tgl_kembali_seharusnya']))->days;
            $q_rate = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'overdue' AND is_active = 1 LIMIT 1");
            $rate = ($q_rate && mysqli_num_rows($q_rate)>0) ? (float)mysqli_fetch_assoc($q_rate)['tarif'] : 2000;
            $denda_terlambat = $hari * $rate;
            if ($denda_terlambat > 0) {
                mysqli_query($koneksi, "INSERT INTO penalties (id_user, id_peminjaman, id_buku, tipe_denda, jumlah, catatan, created_by) VALUES ('{$loan['id_user']}','$id_p','{$loan['id_buku']}','overdue','$denda_terlambat','Terlambat $hari hari','$id_user')");
                $penalties_created[] = ["tipe"=>"overdue","jumlah"=>$denda_terlambat,"hari"=>$hari];
            }
        }

        // 2. Denda kondisi buku
        $denda_kondisi = 0;
        if ($kondisi === 'rusak_ringan') {
            $q_r = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'damage' AND nama_aturan LIKE '%Ringan%' AND is_active = 1 LIMIT 1");
            $denda_kondisi = ($q_r && mysqli_num_rows($q_r)>0) ? (float)mysqli_fetch_assoc($q_r)['tarif'] : 25000;
        } elseif ($kondisi === 'rusak_berat') {
            $q_r = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'damage' AND nama_aturan LIKE '%Berat%' AND is_active = 1 LIMIT 1");
            $denda_kondisi = ($q_r && mysqli_num_rows($q_r)>0) ? (float)mysqli_fetch_assoc($q_r)['tarif'] : 75000;
        } elseif ($kondisi === 'hilang') {
            $q_r = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'lost' AND is_active = 1 LIMIT 1");
            $denda_kondisi = ($q_r && mysqli_num_rows($q_r)>0) ? (float)mysqli_fetch_assoc($q_r)['tarif'] : 0;
            $denda_kondisi += 25000;
        }
        if ($denda_kondisi > 0) {
            $tipe_d = ($kondisi === 'hilang') ? 'lost' : 'damage';
            mysqli_query($koneksi, "INSERT INTO penalties (id_user, id_peminjaman, id_buku, tipe_denda, jumlah, catatan, created_by) VALUES ('{$loan['id_user']}','$id_p','{$loan['id_buku']}','$tipe_d','$denda_kondisi','Kondisi: $kondisi','$id_user')");
            $penalties_created[] = ["tipe"=>$tipe_d,"jumlah"=>$denda_kondisi,"kondisi"=>$kondisi];
        }

        $total_denda = $denda_terlambat + $denda_kondisi;

        // Update peminjaman
        mysqli_query($koneksi, "UPDATE peminjaman SET tgl_kembali_asli='$tgl_now', status='Kembali', denda='$total_denda', kondisi_kembali='$kondisi', approved_by='$id_user', approved_at='$waktu', catatan_admin='$catatan' WHERE id_peminjaman='$id_p'");

        // Restore stock (kecuali hilang)
        if ($kondisi !== 'hilang') {
            mysqli_query($koneksi, "UPDATE buku SET stok = stok + 1 WHERE id_buku = '{$loan['id_buku']}'");
        }

        // Kirim notifikasi ke user
        $msg = "Buku telah dikembalikan. Kondisi: $kondisi.";
        if ($total_denda > 0) $msg .= " Denda: Rp " . number_format($total_denda, 0, ',', '.');
        mysqli_query($koneksi, "INSERT INTO notifications (id_user, judul, pesan, tipe) VALUES ('{$loan['id_user']}', 'Pengembalian Diproses', '$msg', 'approval')");

        sendOk(["id_peminjaman"=>(int)$id_p,"status"=>"Kembali","kondisi"=>$kondisi,"total_denda"=>$total_denda,"penalties"=>$penalties_created,"message"=>"Pengembalian berhasil diproses"]);

    } else {
        sendError("Action tidak valid. Gunakan 'request' atau 'process'", 422);
    }
} else {
    sendError("Method not allowed", 405);
}
?>
