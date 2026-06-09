<?php
// Script ini digunakan untuk menambahkan kolom yang kurang di database secara otomatis
include 'config/koneksi.php';

echo "<h2>Proses Update Database...</h2>";

// Cek apakah kolom file_ebook sudah ada
$check = mysqli_query($koneksi, "SHOW COLUMNS FROM `buku` LIKE 'file_ebook'");
if (mysqli_num_rows($check) == 0) {
    // Jika belum ada, tambahkan kolom file_ebook
    $sql = "ALTER TABLE `buku` ADD COLUMN `file_ebook` VARCHAR(255) DEFAULT NULL AFTER `sampul`";
    if (mysqli_query($koneksi, $sql)) {
        echo "<p style='color:green;'>Berhasil menambahkan kolom <b>file_ebook</b> ke tabel buku!</p>";
    } else {
        echo "<p style='color:red;'>Gagal menambahkan kolom file_ebook: " . mysqli_error($koneksi) . "</p>";
    }
} else {
    echo "<p style='color:blue;'>Kolom <b>file_ebook</b> sudah ada di database, tidak perlu diubah.</p>";
}

echo "<hr><p>Update database selesai. Silakan kembali ke halaman utama.</p>";
echo "<a href='index.php'>Kembali ke Beranda</a>";
?>
