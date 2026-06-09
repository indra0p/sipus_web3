<?php
include '../config/koneksi.php';

$id       = $_POST['id'];
$username = mysqli_real_escape_string($koneksi, $_POST['username']);
$nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
$jenkel   = $_POST['jenkel'];

$query = "UPDATE users SET username='$username', nama='$nama', jenkel='$jenkel' WHERE id='$id'";

if(mysqli_query($koneksi, $query)){
    header("location: anggota.php?pesan=update");
} else {
    echo "Gagal mengupdate data: " . mysqli_error($koneksi);
}
?>