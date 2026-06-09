<?php
include '../config/koneksi.php';

$id_buku    = $_POST['id_buku'];
$judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
$pengarang  = mysqli_real_escape_string($koneksi, $_POST['pengarang']);
$jenis_buku = mysqli_real_escape_string($koneksi, $_POST['jenis_buku']);
$stok       = $_POST['stok'];

$query = "UPDATE buku SET judul='$judul', pengarang='$pengarang', jenis_buku='$jenis_buku', stok='$stok' WHERE id_buku='$id_buku'";

if(mysqli_query($koneksi, $query)){
    header("location: buku.php?pesan=update");
} else {
    echo "Error: " . mysqli_error($koneksi);
}
?>