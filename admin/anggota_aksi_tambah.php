<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['simpan'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']); 
    $jenkel   = mysqli_real_escape_string($koneksi, $_POST['jenkel']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    // Query INSERT ke tabel users (Pastikan kolom nama dan jenkel sudah ditambah di DB)
    $query = "INSERT INTO users (username, nama, password, jenkel, role) 
              VALUES ('$username', '$nama', '$password', '$jenkel', '$role')";

    if (mysqli_query($koneksi, $query)) {
        header("location: anggota.php?pesan=berhasil");
    } else {
        echo "Gagal simpan: " . mysqli_error($koneksi);
    }
} else {
    header("location: anggota.php");
}
?>