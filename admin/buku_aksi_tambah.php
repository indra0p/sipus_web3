<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['simpan'])) {
    // Menangkap data dan mengamankannya
    $judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $pengarang  = mysqli_real_escape_string($koneksi, $_POST['pengarang']);
    $jenis_buku = mysqli_real_escape_string($koneksi, $_POST['jenis_buku']);
    $stok       = mysqli_real_escape_string($koneksi, $_POST['stok']);

    // Query INSERT sesuai dengan nama kolom di databasemu
    $query = "INSERT INTO buku (judul, pengarang, jenis_buku, stok) 
              VALUES ('$judul', '$pengarang', '$jenis_buku', '$stok')";

    if (mysqli_query($koneksi, $query)) {
        // Jika berhasil, kembali ke halaman stok buku
        header("location: buku.php?pesan=berhasil");
    } else {
        // Jika gagal, tampilkan error
        echo "Gagal menyimpan buku: " . mysqli_error($koneksi);
    }
} else {
    header("location: buku.php");
}
?>