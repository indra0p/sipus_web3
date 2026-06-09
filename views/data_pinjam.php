<?php
session_start();
include '../config/koneksi.php';

// Cek login...
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Peminjaman - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h2>Riwayat Peminjaman Anda</h2>
    <table border="1">
        <tr>
            <th>No</th>
            <th>Judul Buku</th>
            <th>Tgl Pinjam</th>
            <th>Batas Kembali</th>
            <th>Denda (2k/hari)</th>
            <th>Status</th>
        </tr>
        <?php 
        $no = 1;
        $id_user = $_SESSION['id_user'];
        // Join tabel peminjaman dan buku
        $query = mysqli_query($koneksi, "SELECT * FROM peminjaman JOIN buku ON peminjaman.id_buku=buku.id_buku WHERE id_user='$id_user'");
        
        while($d = mysqli_fetch_array($query)){
            // LOGIKA HITUNG DENDA
            $tgl_sekarang = date('Y-m-d');
            $batas_kembali = $d['tgl_kembali_seharusnya'];
            
            $denda = 0;
            if(strtotime($tgl_sekarang) > strtotime($batas_kembali) && $d['status'] == 'Dipinjam'){
                $selisih = strtotime($tgl_sekarang) - strtotime($batas_kembali);
                $hari = $selisih / (60 * 60 * 24); // Konversi detik ke hari
                $denda = $hari * 2000;
            }
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $d['judul']; ?></td>
            <td><?php echo $d['tgl_pinjam']; ?></td>
            <td><?php echo $d['tgl_kembali_seharusnya']; ?></td>
            <td>
                <?php 
                if($denda > 0){
                    echo "<span style='color:red;'>Rp " . number_format($denda, 0, ',', '.') . "</span>";
                } else {
                    echo "0";
                }
                ?>
            </td>
            <td><?php echo $d['status']; ?></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>