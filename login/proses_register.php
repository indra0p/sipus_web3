<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $jenkel   = mysqli_real_escape_string($koneksi, $_POST['jenkel']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $konfirmasi = $_POST['konfirmasi'];

    if ($password !== $konfirmasi) {
        header("location: register.php?pesan=pass_tidak_cocok");
        exit;
    }

    $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        header("location: register.php?pesan=sudah_ada");
        exit;
    }

    $sql = "INSERT INTO users (username, nama, email, password, jenkel, role, status) 
            VALUES ('$username', '$nama', '$email', '$password', '$jenkel', 'mahasiswa', 'active')";

    if (mysqli_query($koneksi, $sql)) {
        header("location: login.php?pesan=register_berhasil");
    } else {
        header("location: register.php?pesan=gagal");
    }
    exit;
}
?>
