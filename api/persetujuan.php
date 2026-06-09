<?php
/**
 * POST /api/persetujuan.php - Admin: Approve/Reject peminjaman, pengembalian, atau perpanjangan
 * GET  /api/persetujuan.php - Admin: Ambil daftar pending approval
 *   ?tipe=peminjaman|pengembalian|perpanjangan|all
 * Requires: Bearer token (admin/karyawan only)
 */
require_once 'config.php';
$id_user = requireAuth();
$baseUrl = getBaseUrl();
$role = requireStaff($koneksi, $id_user);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tipe = isset($_GET['tipe']) ? $_GET['tipe'] : 'all';
    $where = "";
    if ($tipe === 'peminjaman') $where = "AND p.status = 'Menunggu'";
    elseif ($tipe === 'pengembalian') $where = "AND p.status = 'Pengajuan_Kembali'";
    elseif ($tipe === 'perpanjangan') $where = "AND p.perpanjangan_status = 'requested'";
    else $where = "AND (p.status IN ('Menunggu', 'Pengajuan_Kembali') OR p.perpanjangan_status = 'requested')";

    $sql = "SELECT p.*, u.nama, u.username, u.role as user_role, b.judul, b.pengarang, b.sampul, b.jenis_buku, b.stok
            FROM peminjaman p
            JOIN users u ON p.id_user = u.id
            JOIN buku b ON p.id_buku = b.id_buku
            WHERE 1=1 $where
            ORDER BY p.id_peminjaman DESC";
    $query = mysqli_query($koneksi, $sql);
    $approvals = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $sampul_url = !empty($row['sampul']) ? $baseUrl . "/assets/img/sampul/" . $row['sampul'] : null;
        $denda = 0;
        if ($row['status'] === 'Pengajuan_Kembali') {
            $tgl_now = date('Y-m-d');
            if ($tgl_now > $row['tgl_kembali_seharusnya']) {
                $denda = (new DateTime($tgl_now))->diff(new DateTime($row['tgl_kembali_seharusnya']))->days * 2000;
            }
        }
        $approvals[] = [
            "id_peminjaman" => (int)$row['id_peminjaman'],
            "id_buku" => (int)$row['id_buku'],
            "user" => [
                "nama" => $row['nama'],
                "username" => $row['username'],
                "role" => $row['user_role']
            ],
            "buku" => [
                "judul" => $row['judul'],
                "pengarang" => $row['pengarang'],
                "sampul" => $sampul_url,
                "jenis_buku" => $row['jenis_buku'],
                "stok" => (int)$row['stok']
            ],
            "tgl_pinjam" => $row['tgl_pinjam'],
            "tgl_kembali_seharusnya" => $row['tgl_kembali_seharusnya'],
            "status" => $row['status'],
            "perpanjangan_status" => $row['perpanjangan_status'] ?? 'none',
            "kondisi_kembali" => $row['kondisi_kembali'] ?? null,
            "denda_estimasi" => $denda
        ];
    }
    sendOk($approvals, 200, ["total" => count($approvals)]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getJsonBody();
    $id_peminjaman = mysqli_real_escape_string($koneksi, $body['id_peminjaman'] ?? '');
    $aksi = $body['aksi'] ?? '';
    $tipe = $body['tipe'] ?? '';
    $catatan = mysqli_real_escape_string($koneksi, $body['catatan'] ?? '');
    $waktu = date('Y-m-d H:i:s');

    if (empty($id_peminjaman) || empty($aksi) || empty($tipe)) {
        sendError("Parameter tidak lengkap", 422);
    }
    if (!in_array($aksi, ['approve', 'reject'])) {
        sendError("Aksi tidak valid", 422);
    }

    $q_loan = mysqli_query($koneksi, "SELECT p.*, b.stok FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku WHERE p.id_peminjaman = '$id_peminjaman'");
    $loan = mysqli_fetch_assoc($q_loan);
    if (!$loan) {
        sendError("Peminjaman tidak ditemukan", 404);
    }

    if ($tipe === 'peminjaman') {
        if ($loan['status'] !== 'Menunggu') {
            sendError("Peminjaman ini sudah diproses", 409);
        }
        if ($aksi === 'approve') {
            if ($loan['stok'] <= 0) {
                sendError("Stok buku habis, tidak dapat disetujui", 422);
            }
            $tgl_pinjam_baru = date('Y-m-d');
            $q_user_role = mysqli_query($koneksi, "SELECT role FROM users WHERE id = '{$loan['id_user']}'");
            $user_role_data = mysqli_fetch_assoc($q_user_role);
            $urole = $user_role_data['role'] ?? 'mahasiswa';
            $durasi = '+7 days';
            if ($urole === 'karyawan') $durasi = '+14 days';
            elseif ($urole === 'dosen') $durasi = '+30 days';
            $tgl_kembali_baru = date('Y-m-d', strtotime($durasi, strtotime($tgl_pinjam_baru)));
            $update = mysqli_query($koneksi, "UPDATE peminjaman SET status='Dipinjam', tgl_pinjam='$tgl_pinjam_baru', tgl_kembali_seharusnya='$tgl_kembali_baru', approved_by='$id_user', approved_at='$waktu', catatan_admin='$catatan' WHERE id_peminjaman='$id_peminjaman'");
            if ($update) {
                mysqli_query($koneksi, "UPDATE buku SET stok = stok - 1 WHERE id_buku = '{$loan['id_buku']}'");
                sendOk(["id_peminjaman" => (int)$id_peminjaman, "status" => "Dipinjam", "tgl_kembali" => $tgl_kembali_baru, "message" => "Peminjaman disetujui"]);
            } else {
                sendError("Gagal menyetujui", 500);
            }
        } else {
            mysqli_query($koneksi, "UPDATE peminjaman SET status='Ditolak', approved_by='$id_user', approved_at='$waktu', catatan_admin='$catatan' WHERE id_peminjaman='$id_peminjaman'");
            sendOk(["id_peminjaman" => (int)$id_peminjaman, "status" => "Ditolak", "message" => "Peminjaman ditolak"]);
        }

    } elseif ($tipe === 'pengembalian') {
        if ($loan['status'] !== 'Pengajuan_Kembali') {
            sendError("Pengembalian ini sudah diproses", 409);
        }
        if ($aksi === 'approve') {
            $tgl_sekarang = date('Y-m-d');
            $denda = 0;
            if ($tgl_sekarang > $loan['tgl_kembali_seharusnya']) {
                $denda = (new DateTime($tgl_sekarang))->diff(new DateTime($loan['tgl_kembali_seharusnya']))->days * 2000;
            }
            mysqli_query($koneksi, "UPDATE peminjaman SET tgl_kembali_asli='$tgl_sekarang', status='Kembali', denda='$denda', approved_by='$id_user', approved_at='$waktu', catatan_admin='$catatan' WHERE id_peminjaman='$id_peminjaman'");
            mysqli_query($koneksi, "UPDATE buku SET stok = stok + 1 WHERE id_buku = '{$loan['id_buku']}'");
            sendOk(["id_peminjaman" => (int)$id_peminjaman, "status" => "Kembali", "denda" => $denda, "message" => "Pengembalian disetujui. Denda: Rp " . number_format($denda, 0, ',', '.')]);
        } else {
            mysqli_query($koneksi, "UPDATE peminjaman SET status='Dipinjam', approved_by='$id_user', approved_at='$waktu', catatan_admin='$catatan' WHERE id_peminjaman='$id_peminjaman'");
            sendOk(["id_peminjaman" => (int)$id_peminjaman, "status" => "Dipinjam", "message" => "Pengajuan pengembalian ditolak"]);
        }

    } elseif ($tipe === 'perpanjangan') {
        if (($loan['perpanjangan_status'] ?? '') !== 'requested') {
            sendError("Tidak ada pengajuan perpanjangan", 409);
        }
        if ($aksi === 'approve') {
            $q_user_role = mysqli_query($koneksi, "SELECT role FROM users WHERE id = '{$loan['id_user']}'");
            $user_role_data = mysqli_fetch_assoc($q_user_role);
            $urole = $user_role_data['role'] ?? 'mahasiswa';
            $tambah = ($urole == 'dosen') ? '+30 days' : '+7 days';
            $tgl_baru = date('Y-m-d', strtotime($tambah, strtotime($loan['tgl_kembali_seharusnya'])));
            mysqli_query($koneksi, "UPDATE peminjaman SET tgl_kembali_seharusnya = '$tgl_baru', perpanjangan_status = 'approved' WHERE id_peminjaman = '$id_peminjaman'");
            sendOk(["id_peminjaman" => (int)$id_peminjaman, "perpanjangan_status" => "approved", "tgl_kembali_baru" => $tgl_baru, "message" => "Perpanjangan disetujui"]);
        } else {
            mysqli_query($koneksi, "UPDATE peminjaman SET perpanjangan_status = 'rejected' WHERE id_peminjaman = '$id_peminjaman'");
            sendOk(["id_peminjaman" => (int)$id_peminjaman, "perpanjangan_status" => "rejected", "message" => "Perpanjangan ditolak"]);
        }

    } else {
        sendError("Tipe tidak valid. Gunakan 'peminjaman', 'pengembalian', atau 'perpanjangan'", 422);
    }

} else {
    sendError("Method not allowed", 405);
}
?>
