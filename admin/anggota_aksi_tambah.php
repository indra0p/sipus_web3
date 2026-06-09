<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['simpan'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']); 
    $jenkel   = mysqli_real_escape_string($koneksi, $_POST['jenkel']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    // Cek apakah username sudah ada
    $cek = mysqli_query($koneksi, "SELECT username FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Username sudah digunakan! Silakan gunakan username lain.'); window.history.back();</script>";
        exit;
    }

    // Query INSERT ke tabel users (Pastikan kolom nama dan jenkel sudah ditambah di DB)
    $query = "INSERT INTO users (username, nama, password, jenkel, role) 
              VALUES ('$username', '$nama', '$password', '$jenkel', '$role')";

    try {
        if (mysqli_query($koneksi, $query)) {
            header("location: anggota.php?pesan=berhasil");
        } else {
            echo "Gagal simpan: " . mysqli_error($koneksi);
        }
    } catch (Exception $e) {
        echo "<script>alert('Error Database: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
} else {
    header("location: anggota.php");
}
?>