<?php
session_start();
include '../config/koneksi.php'; 

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Query mencari user dengan teks biasa (Plain Text)
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    $cek = mysqli_num_rows($query);

    if ($cek > 0) {
        $data = mysqli_fetch_assoc($query);

        // Set Session
        $_SESSION['id_user']  = $data['id']; 
        $_SESSION['username'] = $data['username'];
        $_SESSION['nama']     = $data['nama'];
        $_SESSION['role']     = $data['role']; 
        $_SESSION['status']   = "login";

        // Redirect berdasarkan role
        if ($data['role'] == "admin") {
            header("location: ../admin/dashboard.php");
        } elseif ($data['role'] == "akademik") {
            // Arahkan ke folder akademik
            header("location: ../akademik/akademik_dashboard.php");
        } else {
            // Mahasiswa, Dosen, atau Karyawan ke user view
            header("location: ../views/users_dashboard.php");
        }
        exit;

    } else {
        // Jika tidak ketemu, lempar balik ke login.php dengan pesan gagal
        header("location: login.php?pesan=gagal");
        exit;
    }
}
?>