<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="main-content no-sidebar">
        <div class="form-card">
            <header>
                <h2><i class="fa-solid fa-book-medical"></i> Tambah Koleksi Buku</h2>
                <p>Silakan lengkapi data buku di bawah ini untuk menambah koleksi perpustakaan.</p>
            </header>
            
            <hr>

            <form action="buku_aksi.php?aksi=tambah" method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Judul Buku</label>
                    <input type="text" name="judul" placeholder="Masukkan judul buku lengkap" required>
                </div>

                <div class="form-group">
                    <label>Barcode / ISBN</label>
                    <input type="text" name="barcode" placeholder="Kode barcode atau ISBN (opsional, auto-generate jika kosong)">
                    <small>* Kosongkan untuk auto-generate barcode.</small>
                </div>
                
                <div class="form-group">
                    <label>Pengarang</label>
                    <input type="text" name="pengarang" placeholder="Nama penulis/pengarang" required>
                </div>

                <div class="form-group">
                    <label>Penerbit</label>
                    <input type="text" name="penerbit" placeholder="Nama penerbit buku" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori / Jenis Buku</label>
                    <select name="jenis_buku" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Informatika">Informatika</option>
                        <option value="Sains">Sains</option>
                        <option value="Ekonomi">Ekonomi</option>
                        <option value="Novel">Novel</option>
                        <option value="Umum">Umum</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Sinopsis / Deskripsi</label>
                    <textarea name="sinopsis" placeholder="Tuliskan ringkasan isi buku secara singkat..."></textarea>
                </div>

                <div class="form-group">
                    <label>Jumlah Stok</label>
                    <input type="number" name="stok" min="1" value="1" required>
                </div>

                <div class="form-group">
                    <label>Sampul Buku</label>
                    <input type="file" name="sampul" accept="image/png, image/jpeg, image/jpg" required>
                    <small>* Gunakan format gambar (JPG/PNG) untuk sampul buku.</small>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Koleksi
                    </button>
                    <a href="buku.php" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>