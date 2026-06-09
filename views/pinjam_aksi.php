<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['proses_pinjam'])) {
    // 1. Ambil data dari form
    $id_buku     = mysqli_real_escape_string($koneksi, $_POST['id_buku']);
    $tgl_kembali = mysqli_real_escape_string($koneksi, $_POST['tgl_kembali']);
    $tgl_pinjam  = date('Y-m-d');
    
    // 2. Ambil ID USER dari session
    $id_user     = $_SESSION['id_user']; 

    // 3. Check if already borrowing or pending
    $cek_pinjam = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id_user='$id_user' AND id_buku='$id_buku' AND status IN ('Dipinjam','Menunggu')");
    if (mysqli_num_rows($cek_pinjam) > 0) {
        echo "<script>alert('Anda sudah meminjam atau mengajukan buku ini!'); window.history.back();</script>";
        exit;
    }

    // 4. Query INSERT - status Menunggu (pending approval)
    // NO stock reduction - wait for admin approval
    $query_pinjam = "INSERT INTO peminjaman (id_user, id_buku, tgl_pinjam, tgl_kembali_seharusnya, status) 
                     VALUES ('$id_user', '$id_buku', '$tgl_pinjam', '$tgl_kembali', 'Menunggu')";
    
    if (mysqli_query($koneksi, $query_pinjam)) {
        echo "<script>alert('Pengajuan peminjaman berhasil dikirim! Menunggu persetujuan admin.'); window.location='peminjaman_saya.php';</script>";
    } else {
        die("Gagal Simpan: " . mysqli_error($koneksi));
    }
} else {
    header("location: cari_buku.php");
}
?>