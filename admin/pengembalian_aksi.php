<?php 
session_start();
include '../config/koneksi.php';
include_once 'notifikasi_helper.php';

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';
$id   = isset($_GET['id']) ? $_GET['id'] : '';

// Fungsi untuk menambah stok buku
function kembalikanStok($koneksi, $id_peminjaman) {
    // Ambil id_buku terlebih dahulu
    $get_buku = mysqli_query($koneksi, "SELECT id_buku FROM peminjaman WHERE id_peminjaman = '$id_peminjaman'");
    $data = mysqli_fetch_assoc($get_buku);
    if ($data) {
        $id_buku = $data['id_buku'];
        mysqli_query($koneksi, "UPDATE buku SET stok = stok + 1 WHERE id_buku = '$id_buku'");
    }
}

// Mulai Transaksi
mysqli_begin_transaction($koneksi);

try {
    if ($aksi == "kembali" || $aksi == "approve_kembali") {
        $id_buku = mysqli_real_escape_string($koneksi, $_GET['buku'] ?? '');
        $denda   = mysqli_real_escape_string($koneksi, $_GET['denda'] ?? '0');
        $tgl_sekarang = date('Y-m-d');
        $id_admin = $_SESSION['id_user'] ?? null;
        $waktu = date('Y-m-d H:i:s');

        // Update Peminjaman
        $query = "UPDATE peminjaman SET 
                  tgl_kembali_asli = '$tgl_sekarang', 
                  status = 'Kembali', 
                  denda = '$denda',
                  approved_by = '$id_admin',
                  approved_at = '$waktu'
                  WHERE id_peminjaman = '$id' AND (status = 'Dipinjam' OR status = 'Pengajuan_Kembali')";

        if (mysqli_query($koneksi, $query) && mysqli_affected_rows($koneksi) > 0) {
            // LOGIKA TAMBAH STOK DI SINI
            kembalikanStok($koneksi, $id);

            // Jika approve, kirim notifikasi
            if ($aksi == "approve_kembali") {
                $u = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id_user FROM peminjaman WHERE id_peminjaman = '$id'"));
                if ($u) {
                    createNotification($koneksi, $u['id_user'], 'Pengembalian Disetujui', 'Buku berhasil dikembalikan. Denda: Rp ' . number_format($denda,0,',','.'), 'approval', 'peminjaman_saya.php');
                }
            }
            mysqli_commit($koneksi);
            header("location: pengembalian.php?pesan=" . ($aksi == "kembali" ? "kembali_berhasil" : "approve_kembali_berhasil"));
        } else {
            throw new Exception("sudah_diproses");
        }
    } 
    // Bagian lain (reject_kembali, perpanjang, dll tidak perlu merubah stok)
    elseif ($aksi == "reject_kembali") {
        mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Dipinjam', catatan_admin = 'Pengajuan pengembalian ditolak' WHERE id_peminjaman = '$id' AND status = 'Pengajuan_Kembali'");
        mysqli_commit($koneksi);
        header("location: pengembalian.php?pesan=reject_kembali_berhasil");
    }
    // ... (Logika perpanjang tetap sama karena tidak mempengaruhi stok)
    else {
        // Handle aksi perpanjang di sini...
        mysqli_commit($koneksi);
    }

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    header("location: pengembalian.php?pesan=" . $e->getMessage());
}
exit();
?>