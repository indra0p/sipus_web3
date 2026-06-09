<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php");
    exit;
}

// Ambil ID dari URL
$id = mysqli_real_escape_string($koneksi, $_GET['id']);
$data = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku='$id'");
$d = mysqli_fetch_array($data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Buku - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <style>
        .form-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 700px; margin: 40px auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-save { background: #B46932; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-batal { text-decoration: none; color: #666; font-size: 14px; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="form-card">
            <h2>Edit Koleksi Buku</h2>
            <form action="buku_aksi.php?aksi=edit" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_buku" value="<?php echo $d['id_buku']; ?>">

                <div class="form-group">
                    <label>Judul Buku</label>
                    <input type="text" name="judul" value="<?php echo $d['judul']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Pengarang</label>
                    <input type="text" name="pengarang" value="<?php echo $d['pengarang']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Penerbit</label>
                    <input type="text" name="penerbit" value="<?php echo $d['penerbit']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="jenis_buku">
                        <option value="Informatika" <?php if($d['jenis_buku']=="Informatika") echo "selected"; ?>>Informatika</option>
                        <option value="Novel" <?php if($d['jenis_buku']=="Novel") echo "selected"; ?>>Novel</option>
                        <option value="Lainnya" <?php if($d['jenis_buku']=="Lainnya") echo "selected"; ?>>Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Sinopsis</label>
                    <textarea name="sinopsis" rows="5"><?php echo $d['sinopsis']; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Jumlah Stok</label>
                    <input type="number" name="stok" value="<?php echo $d['stok']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Ganti Sampul (Kosongkan jika tidak diganti)</label>
                    <input type="file" name="sampul">
                    <br><small>Sampul saat ini: <?php echo $d['sampul']; ?></small>
                </div>
                
                <button type="submit" class="btn-save">Update Buku</button>
                <a href="buku.php" class="btn-batal">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>