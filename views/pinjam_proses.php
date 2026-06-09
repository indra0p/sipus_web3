<?php
session_start();
include '../config/koneksi.php';

// 1. CEK LOGIN
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

$id_user = $_SESSION['id_user'] ?? null;
if (!$id_user) {
    die("Error: Sesi pengguna tidak ditemukan.");
}

$aksi = $_GET['aksi'] ?? '';

// AKSI: Ajukan perpanjangan
if ($aksi == 'ajukan_perpanjang') {
    $id_peminjaman = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');
    if (empty($id_peminjaman)) { header("location: peminjaman_saya.php"); exit; }
    $update = mysqli_query($koneksi, "UPDATE peminjaman SET perpanjangan_status='requested' WHERE id_peminjaman='$id_peminjaman' AND id_user='$id_user'");
    if ($update) { header("location: peminjaman_saya.php?pesan=perpanjang_berhasil"); }
    exit;
}

// AKSI: Ajukan pengembalian
if ($aksi == 'ajukan_kembali') {
    $id_peminjaman = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');
    if (empty($id_peminjaman)) { header("location: peminjaman_saya.php"); exit; }
    $update = mysqli_query($koneksi, "UPDATE peminjaman SET status='Pengajuan_Kembali' WHERE id_peminjaman='$id_peminjaman' AND id_user='$id_user'");
    if ($update) { header("location: peminjaman_saya.php?pesan=ajukan_kembali_berhasil"); }
    exit;
}

// AKSI: Proses peminjaman buku
if (isset($_GET['id'])) {
    $id_buku = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 1. Validasi Maksimal 2 Buku
    $query_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman 
                        WHERE id_user = '$id_user' AND status IN ('Dipinjam', 'Menunggu', 'Pengajuan_Kembali')");
    $data_count  = mysqli_fetch_assoc($query_count);
    if ($data_count['total'] >= 2) {
        echo "<script>alert('Batas maksimal peminjaman adalah 2 buku.'); window.location='peminjaman_saya.php';</script>";
        exit;
    }

    // 2. Cek Stok Buku
    $cek_stok = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id_buku = '$id_buku'");
    $data_buku = mysqli_fetch_assoc($cek_stok);

    if ($data_buku && $data_buku['stok'] > 0) {
        // 3. AMBIL DURASI DARI LOAN_RULES BERDASARKAN ROLE USER
        $q_user = mysqli_query($koneksi, "SELECT role FROM users WHERE id = '$id_user'");
        $u = mysqli_fetch_assoc($q_user);
        $role = $u['role'] ?? 'mahasiswa';

        $q_rules = mysqli_query($koneksi, "SELECT max_days FROM loan_rules WHERE role = '$role'");
        $d = mysqli_fetch_assoc($q_rules);
        $max_days = $d['max_days'] ?? 7; // Default 7 hari jika aturan tidak ditemukan

        // 4. Hitung tanggal (Sinkron dengan database)
        $tgl_pinjam = date('Y-m-d');
        $tgl_kembali = date('Y-m-d', strtotime($tgl_pinjam . " + $max_days days"));

        // 5. Cek duplikasi pengajuan
        $cek_pinjam = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id_user='$id_user' AND id_buku='$id_buku' AND status IN ('Dipinjam','Menunggu')");
        if (mysqli_num_rows($cek_pinjam) > 0) {
            echo "<script>alert('Anda sudah meminjam atau mengajukan buku ini!'); window.location='cari_buku.php';</script>";
            exit;
        }
        
        // 6. Simpan
        $query_pinjam = "INSERT INTO peminjaman (id_user, id_buku, tgl_pinjam, tgl_kembali_seharusnya, status) 
                         VALUES ('$id_user', '$id_buku', '$tgl_pinjam', '$tgl_kembali', 'Menunggu')";
        
        if (mysqli_query($koneksi, $query_pinjam)) {
            echo "<script>alert('Pengajuan berhasil! Batas kembali: $tgl_kembali'); window.location='peminjaman_saya.php';</script>";
        } else {
            echo "Gagal: " . mysqli_error($koneksi);
        }
    } else {
        header("location: cari_buku.php?pesan=stok_habis");
    }
} else {
    header("location: cari_buku.php");
}
?>