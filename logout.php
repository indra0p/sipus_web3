<?php 
// Memulai session
session_start();

// Menghapus semua session yang ada
session_destroy();

// Mengalihkan halaman ke index.php
header("location:index.php?pesan=logout");
exit;
?>