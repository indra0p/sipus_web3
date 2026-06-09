<?php
session_start();
include '../config/koneksi.php';
if (!isset($_SESSION['status'])) { header("location: ../login/login.php"); }

$id = mysqli_real_escape_string($koneksi, $_GET['id']);
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE id='$id'");
$d = mysqli_fetch_array($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Anggota - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="main-content no-sidebar">
        <div class="form-card">
            <h2><i class="fa-solid fa-user-pen"></i> Edit Data Anggota</h2>
            <p>Ubah informasi akun anggota di bawah ini.</p>
            <br>
            <form action="anggota_aksi_edit.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                
                <div class="form-group">
                    <label>User ID / Username</label>
                    <input type="text" name="username" value="<?php echo $d['username']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?php echo $d['nama']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenkel">
                        <option value="Laki-laki" <?php echo ($d['jenkel'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo ($d['jenkel'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                    <a href="anggota.php" class="btn-back">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>