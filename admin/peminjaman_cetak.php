<?php
include '../config/koneksi.php';
$id = $_GET['id'];
$query = mysqli_query($koneksi, "SELECT peminjaman.*, users.nama, buku.judul 
                                 FROM peminjaman 
                                 JOIN users ON peminjaman.id_user = users.id 
                                 JOIN buku ON peminjaman.id_buku = buku.id_buku 
                                 WHERE id_peminjaman = '$id'");
$d = mysqli_fetch_array($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Bukti Peminjaman - SIPUS POLSA</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .content { line-height: 1.6; }
        .footer { margin-top: 50px; text-align: right; }
        .box { border: 1px solid #ccc; padding: 15px; border-radius: 8px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>PERPUSTAKAAN SIPUS POLSA</h2>
        <p>Jl. Raya Kutoarjo-Kebumen, Jawa Tengah</p>
    </div>

    <center><h3>BUKTI PEMINJAMAN BUKU</h3></center>

    <div class="box">
        <table width="100%">
            <tr>
                <td width="30%">ID Transaksi</td>
                <td>: TR-00<?php echo $d['id_peminjaman']; ?></td>
            </tr>
            <tr>
                <td>Nama Peminjam</td>
                <td>: <?php echo $d['nama']; ?></td>
            </tr>
            <tr>
                <td>Judul Buku</td>
                <td>: <?php echo $d['judul']; ?></td>
            </tr>
            <tr>
                <td>Tanggal Pinjam</td>
                <td>: <?php echo $d['tgl_pinjam']; ?></td>
            </tr>
            <tr>
                <td>Batas Pengembalian</td>
                <td>: <strong><?php echo $d['tgl_kembali_seharusnya']; ?></strong></td>
            </tr>
            <tr>
                <td>Status</td>
                <td>: <?php echo $d['status']; ?></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Purworejo, <?php echo date('d F Y'); ?></p>
        <br><br>
        <p>( Petugas Perpustakaan )</p>
    </div>

    <script>
        window.print();
    </script>

</body>
</html>