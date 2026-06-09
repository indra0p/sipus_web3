<?php
session_start();
include '../config/koneksi.php';

// Cek apakah ada ID yang dikirim dari buku.php
if (!isset($_GET['id'])) {
    header("location: buku.php");
    exit;
}

$id_buku = mysqli_real_escape_string($koneksi, $_GET['id']);
$query = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku='$id_buku'");
$b = mysqli_fetch_assoc($query);

// Jika ID salah atau tidak ada di DB
if (!$b) {
    die("Error: Data buku tidak ditemukan di database.");
}

$tgl_min = date('Y-m-d');
$tgl_max = date('Y-m-d', strtotime('+7 days'));
?>