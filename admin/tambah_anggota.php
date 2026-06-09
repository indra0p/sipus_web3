<?php
session_start();
include '../config/koneksi.php';
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Anggota - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i> <span>SIPUS POLSA</span></div>
        <nav>
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php" class="active"><i class="fa-solid fa-users"></i> Kelola Anggota</a>
            <a href="buku.php"><i class="fa-solid fa-book"></i> Stok Buku</a>
            <a href="../logout.php" class="logout"><i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <h1>Tambah Anggota Baru</h1>
        </header>
        <div class="table-container" style="padding: 20px;">
            <form action="anggota_aksi_tambah.php" method="POST">
                <div style="margin-bottom: 15px;">
                    <label>Username (User ID)</label>
                    <input type="text" name="username" class="form-control" required style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Jenis Kelamin</label>
                    <select name="jenkel" style="width: 100%; padding: 8px; margin-top: 5px;">
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Role</label>
                    <select name="role" style="width: 100%; padding: 8px; margin-top: 5px;">
                        <option value="admin">Admin</option>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                        <option value="karyawan">Karyawan</option>
                    </select>
                </div>
                <button type="submit" name="simpan" class="btn-add">Simpan Anggota</button>
                <a href="anggota.php" style="margin-left: 10px; color: #666;">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>