<?php
// 1. Set zona waktu ke Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');
include '../config/koneksi.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Rekap Denda - SIPUS POLSA</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #f2f2f2; text-transform: uppercase; }
        .header { text-align: center; margin-bottom: 20px; }
        hr { border: 1px solid #000; }
        .no-print { margin-bottom: 20px; }
        
        /* Efek Coret */
        .waived-row { text-decoration: line-through; color: #888; }
        
        .footer-container { margin-top: 40px; }
        .signature-section { display: flex; justify-content: space-between; align-items: flex-start; }
        .sig-box { text-align: center; width: 30%; font-size: 13px; }
        .spacer-box { width: 30%; }

        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="header">
        <h2>LAPORAN REKAP DENDA PERPUSTAKAAN</h2>
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
                <th>No</th>
                <th>Nama Anggota</th>
                <th>Tipe Denda</th>
                <th>Status</th>
                <th>Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_keseluruhan = 0;
            $query = "SELECT p.*, u.nama FROM penalties p JOIN users u ON p.id_user = u.id ORDER BY p.created_at DESC";
            $res = mysqli_query($koneksi, $query);
            
            while($d = mysqli_fetch_array($res)){
                $is_waived = ($d['status'] == 'waived');
                $row_class = $is_waived ? 'waived-row' : '';
                
                // Hanya tambahkan ke total jika status bukan 'waived'
                if (!$is_waived) {
                    $total_keseluruhan += $d['jumlah'];
                }

                echo "<tr class='$row_class'>
                    <td>".$no++."</td>
                    <td>".htmlspecialchars($d['nama'])."</td>
                    <td>".ucfirst($d['tipe_denda'])."</td>
                    <td>".ucfirst($d['status'])."</td>
                    <td align='right'>".number_format($d['jumlah'], 0, ',', '.')."</td>
                </tr>";
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align: right;">TOTAL KESELURUHAN (AKTIF)</th>
                <th style="text-align: right;">Rp <?php echo number_format($total_keseluruhan, 0, ',', '.'); ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="footer-container">
        <div class="signature-section">
            <div class="sig-box">
                <p>Mengetahui,</p>
                <p>Bagian Akademik</p>
                <br><br><br><br>
                <p><strong>( ___________________ )</strong></p>
            </div>
            
            <div class="spacer-box"></div>
            
            <div class="sig-box">
                <p>Purworejo, <span id="footer-date"></span></p>
                <p>Petugas Perpustakaan</p>
                <br><br><br><br>
                <p><strong>( ___________________ )</strong></p>
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
        
        updateClock();
        setInterval(updateClock, 1000);
        window.onload = function() { window.print(); };
    </script>
</body>
</html>