<?php 
include '../config/koneksi.php';

// Pastikan parameter aksi ada
$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : "";

if($aksi == "tambah"){
    // Tangkap data dari form
    $judul     = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $pengarang = mysqli_real_escape_string($koneksi, $_POST['pengarang']);
    $penerbit  = mysqli_real_escape_string($koneksi, $_POST['penerbit']);
    $jenis     = mysqli_real_escape_string($koneksi, $_POST['jenis_buku']);
    $sinopsis  = mysqli_real_escape_string($koneksi, $_POST['sinopsis']);
    $stok      = mysqli_real_escape_string($koneksi, $_POST['stok']);
    $barcode   = mysqli_real_escape_string($koneksi, $_POST['barcode'] ?? '');
    
    // Proses Upload Gambar
    $rand = rand();
    $filename = $_FILES['sampul']['name'];

    if($filename == ""){
        $query = "INSERT INTO buku (judul, pengarang, penerbit, jenis_buku, sinopsis, stok, barcode) 
                  VALUES ('$judul', '$pengarang', '$penerbit', '$jenis', '$sinopsis', '$stok', '$barcode')";
    } else {
        $nama_file = $rand.'_'.$filename;
        move_uploaded_file($_FILES['sampul']['tmp_name'], '../assets/img/sampul/'.$nama_file);
        
        $query = "INSERT INTO buku (judul, pengarang, penerbit, jenis_buku, sinopsis, stok, sampul, barcode) 
                  VALUES ('$judul', '$pengarang', '$penerbit', '$jenis', '$sinopsis', '$stok', '$nama_file', '$barcode')";
    }

    if(mysqli_query($koneksi, $query)){
        // Auto-generate barcode if empty
        if (empty($barcode)) {
            $new_id = mysqli_insert_id($koneksi);
            $auto_barcode = 'BK' . str_pad($new_id, 6, '0', STR_PAD_LEFT);
            mysqli_query($koneksi, "UPDATE buku SET barcode = '$auto_barcode' WHERE id_buku = '$new_id'");
        }
        header("location:buku.php?pesan=tambah");
    } else {
        echo "Error Tambah Data: " . mysqli_error($koneksi);
    }

} elseif($aksi == "edit"){
    $id        = mysqli_real_escape_string($koneksi, $_POST['id_buku']);
    $judul     = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $pengarang = mysqli_real_escape_string($koneksi, $_POST['pengarang']);
    $penerbit  = mysqli_real_escape_string($koneksi, $_POST['penerbit']);
    $jenis     = mysqli_real_escape_string($koneksi, $_POST['jenis_buku']);
    $sinopsis  = mysqli_real_escape_string($koneksi, $_POST['sinopsis']);
    $stok      = mysqli_real_escape_string($koneksi, $_POST['stok']);
    
    $filename = $_FILES['sampul']['name'];

    if($filename == ""){
        $query = "UPDATE buku SET judul='$judul', pengarang='$pengarang', penerbit='$penerbit', 
                  jenis_buku='$jenis', sinopsis='$sinopsis', stok='$stok' WHERE id_buku='$id'";
    } else {
        // Hapus sampul lama sebelum ganti yang baru agar folder bersih
        $cari = mysqli_query($koneksi, "SELECT sampul FROM buku WHERE id_buku='$id'");
        $d_lama = mysqli_fetch_array($cari);
        if(!empty($d_lama['sampul']) && file_exists('../assets/img/sampul/'.$d_lama['sampul'])){
            unlink('../assets/img/sampul/'.$d_lama['sampul']);
        }

        $rand = rand();
        $nama_file = $rand.'_'.$filename;
        move_uploaded_file($_FILES['sampul']['tmp_name'], '../assets/img/sampul/'.$nama_file);
        
        $query = "UPDATE buku SET judul='$judul', pengarang='$pengarang', penerbit='$penerbit', 
                  jenis_buku='$jenis', sinopsis='$sinopsis', stok='$stok', sampul='$nama_file' WHERE id_buku='$id'";
    }

    if(mysqli_query($koneksi, $query)){
        header("location:buku.php?pesan=update");
    } else {
        echo "Error Edit Data: " . mysqli_error($koneksi);
    }

} elseif($aksi == "hapus"){
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Ambil data buku untuk hapus file gambarnya
    $pilih = mysqli_query($koneksi, "SELECT sampul FROM buku WHERE id_buku='$id'");
    $data = mysqli_fetch_array($pilih);
    
    // Cek apakah file benar-benar ada sebelum dihapus
    if(!empty($data['sampul']) && file_exists("../assets/img/sampul/".$data['sampul'])){
        unlink("../assets/img/sampul/".$data['sampul']);
    }

    if(mysqli_query($koneksi, "DELETE FROM buku WHERE id_buku='$id'")){
        header("location:buku.php?pesan=hapus");
    } else {
        // Pesan error jika buku gagal dihapus (misal karena relasi database/dipinjam)
        echo "<script>
                alert('Gagal Hapus! Buku ini mungkin sedang dalam status dipinjam.');
                window.location='buku.php';
              </script>";
    }
} else {
    header("location:buku.php");
}
?>