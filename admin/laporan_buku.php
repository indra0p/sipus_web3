<?php
// 1. Set zona waktu ke Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');
include '../config/koneksi.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Data Buku - SIPUS POLSA</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #f2f2f2; text-transform: uppercase; }
        .header { text-align: center; margin-bottom: 20px; }
        hr { border: 1px solid #000; }
        .no-print { margin-bottom: 20px; }
        
        /* Layout area tanda tangan */
        .footer-container { margin-top: 40px; }
        .signature-section { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
        }
        .sig-box { text-align: center; width: 30%; font-size: 13px; }
        .spacer-box { width: 30%; } /* Penyeimbang tengah */

        @media print { 
            .no-print { display: none; } 
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>LAPORAN DATA KOLEKSI BUKU</h2>
        <h3>SIPUS POLSA</h3>
        <p style="font-size: 12px;">Waktu Cetak: <span id="clock"></span> WIB</p>
        <hr>
    </div>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 15px; cursor: pointer;">Cetak Laporan</button>
    </div>

    <table>
        <thead>
            <tr>
                <th width="1%">No</th>
                <th>Judul Buku</th>
                <th>Pengarang</th>
                <th>Kategori</th>
                <th width="5%">Stok</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $res = mysqli_query($koneksi, "SELECT * FROM buku ORDER BY judul ASC");
            while($d = mysqli_fetch_array($res)){
                echo "<tr>
                    <td>".$no++."</td>
                    <td>".htmlspecialchars($d['judul'])."</td>
                    <td>".htmlspecialchars($d['pengarang'])."</td>
                    <td>".$d['jenis_buku']."</td>
                    <td style='text-align:center;'>".$d['stok']."</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="footer-container">
        <div class="signature-section">
            <div class="sig-box">
                <p>Mengetahui,</p>
                <p>Bagian Akademik</p>
                <br><br><br><br>
                <p><strong>( ___________________ )</strong></p>
                <p style="font-size: 11px; color: #555; margin-top: 2px;">.</p>
            </div>
            
            <div class="spacer-box"></div>
            
            <div class="sig-box">
                <p>Purworejo, <span id="footer-date"></span></p>
                <p>Kepala Perpustakaan</p>
                <br><br><br><br>
                <p><strong>( ___________________ )</strong></p>
                <p style="font-size: 11px; color: #555; margin-top: 2px;"></p>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            const day = String(now.getDate()).padStart(2, '0');
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            
            document.getElementById('clock').textContent = `${day}/${now.getMonth()+1}/${now.getFullYear()} ${h}:${m}:${s}`;
            document.getElementById('footer-date').textContent = `${day} ${months[now.getMonth()]} ${now.getFullYear()}`;
        }
        
        // Jalankan fungsi penunjuk waktu
        updateClock();
        setInterval(updateClock, 1000);

        // Otomatis memicu fungsi print bawaan browser saat halaman selesai dimuat
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>