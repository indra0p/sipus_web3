<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['simpan'])) {
    $barcode    = mysqli_real_escape_string($koneksi, $_POST['barcode']);
    $judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $pengarang  = mysqli_real_escape_string($koneksi, $_POST['pengarang']);
    $penerbit   = mysqli_real_escape_string($koneksi, $_POST['penerbit']);
    $jenis_buku = mysqli_real_escape_string($koneksi, $_POST['jenis_buku']);
    $sinopsis   = mysqli_real_escape_string($koneksi, $_POST['sinopsis']);
    $stok       = intval($_POST['stok']); // Mengambil nilai stok dari inputan form

    // 1. Proses Upload Sampul
    $nama_sampul = "";
    if (!empty($_FILES['sampul']['name'])) {
        $ext_img     = pathinfo($_FILES['sampul']['name'], PATHINFO_EXTENSION);
        $nama_sampul = "cover_" . time() . "." . $ext_img;
        move_uploaded_file($_FILES['sampul']['tmp_name'], "../assets/img/sampul/" . $nama_sampul);
    }

    // 2. Proses Upload PDF E-Book
    $nama_pdf = "";
    if (!empty($_FILES['file_ebook']['name'])) {
        $ext_pdf  = pathinfo($_FILES['file_ebook']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext_pdf) == 'pdf') {
            $nama_pdf = "doc_" . time() . ".pdf";
            
            if (!is_dir("../assets/docs/ebook/")) {
                mkdir("../assets/docs/ebook/", 0777, true);
            }
            
            move_uploaded_file($_FILES['file_ebook']['tmp_name'], "../assets/docs/ebook/" . $nama_pdf);
        } else {
            header("location: buku.php?pesan=gagal_ekstensi");
            exit;
        }
    }

    // 3. Query Insert data ke Database (Kolom stok sekarang diisi dinamis)
    $query_insert = "INSERT INTO buku (barcode, judul, pengarang, penerbit, jenis_buku, sinopsis, stok, sampul, file_ebook) 
                     VALUES ('$barcode', '$judul', '$pengarang', '$penerbit', '$jenis_buku', '$sinopsis', '$stok', '$nama_sampul', '$nama_pdf')";

    if (mysqli_query($koneksi, $query_insert)) {
        header("location: buku.php?pesan=tambah");
    } else {
        echo "Gagal menyimpan data ke database: " . mysqli_error($koneksi);
    }
} else {
    header("location: buku.php");
}
?>