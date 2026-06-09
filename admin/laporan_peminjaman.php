<?php
// 1. Set zona waktu ke Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');
include '../config/koneksi.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Peminjaman - SIPUS POLSA</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #f2f2f2; text-transform: uppercase; }
        .header { text-align: center; margin-bottom: 20px; }
        .text-danger { color: #d9534f; font-weight: bold; }
        .status-pinjam { color: #e67e22; font-weight: bold; }
        .status-kembali { color: #27ae60; font-weight: bold; }
        .status-ditolak { color: #dc2626; font-weight: bold; }
        hr { border: 1px solid #000; }
        .no-print { margin-bottom: 20px; }
        
        /* Layout area tanda tangan dan keterangan */
        .footer-container { margin-top: 30px; }
        .signature-section { 
            margin-top: 25px; 
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
        <h2>LAPORAN RIWAYAT PEMINJAMAN BUKU</h2>
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
                <th>Nama Peminjam</th>
                <th>Judul Buku</th>
                <th>Tgl Pinjam</th>
                <th>Batas Kembali</th>
                <th>Denda (Rp)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $tgl_sekarang = date('Y-m-d');
            
            $query = "SELECT peminjaman.*, users.nama, buku.judul 
                      FROM peminjaman 
                      JOIN users ON peminjaman.id_user = users.id 
                      JOIN buku ON peminjaman.id_buku = buku.id_buku 
                      ORDER BY id_peminjaman DESC";
            
            $res = mysqli_query($koneksi, $query);
            
            while($d = mysqli_fetch_array($res)){
                $nominal_denda = 0;
                
                if($d['status'] == "Dipinjam") {
                    $tgl_batas = new DateTime($d['tgl_kembali_seharusnya']);
                    $tgl_skrg = new DateTime($tgl_sekarang);
                    
                    if($tgl_skrg > $tgl_batas) {
                        $selisih = $tgl_skrg->diff($tgl_batas)->days;
                        $nominal_denda = $selisih * 2000;
                    }
                    $label_status = "<span class='status-pinjam'>Dipinjam</span>";
                } elseif($d['status'] == "Ditolak") {
                    $nominal_denda = 0;
                    $label_status = "<span class='status-ditolak'>Ditolak</span>";
                } else {
                    $nominal_denda = isset($d['denda']) ? $d['denda'] : 0;
                    $label_status = "<span class='status-kembali'>Kembali</span>";
                }

                $tampil_denda = ($nominal_denda > 0) ? "Rp " . number_format($nominal_denda, 0, ',', '.') : "-";
                $class_denda = ($nominal_denda > 0) ? "class='text-danger'" : "";
                
                echo "<tr>
                    <td>".$no++."</td>
                    <td>".htmlspecialchars($d['nama'])."</td>
                    <td>".htmlspecialchars($d['judul'])."</td>
                    <td>".date('d/m/Y', strtotime($d['tgl_pinjam']))."</td>
                    <td>".date('d/m/Y', strtotime($d['tgl_kembali_seharusnya']))."</td>
                    <td $class_denda>".$tampil_denda."</td>
                    <td>".$label_status."</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="footer-container">
        <div style="font-size: 11px; width: 60%;">
            <p><strong>Keterangan:</strong></p>
            <ul style="margin: 5px 0; padding-left: 20px;">
                <li>Status <b>Dipinjam</b>: Buku masih berada di tangan anggota.</li>
                <li>Status <b>Kembali</b>: Buku sudah dikembalikan ke perpustakaan.</li>
                <li>Status <b>Ditolak</b>: Pengajuan peminjaman ditolak oleh pihak perpustakaan.</li>
                <li>Denda pada status 'Dipinjam' adalah estimasi jika dikembalikan hari ini.</li>
            </ul>
        </div>
        
        <div class="signature-section">
            <div class="sig-box">
                <p>Mengetahui,</p>
                <p>Bagian Akademik</p>
                <br><br><br><br>
                <p><strong>( ___________________ )</strong></p>
                <p style="font-size: 11px; color: #555; margin-top: 2px;">. </p>
            </div>
            
            <div class="spacer-box"></div>
            
            <div class="sig-box">
                <p>Purworejo, <span id="footer-date"></span></p>
                <p>Petugas Perpustakaan</p>
                <br><br><br><br>
                <p><strong>( ___________________ )</strong></p>
                <p style="font-size: 11px; color: #555; margin-top: 2px;"></p>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            
            // Format Jam Lengkap
            const day = String(now.getDate()).padStart(2, '0');
            const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            const month = months[now.getMonth()];
            const year = now.getFullYear();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            
            // Jam untuk Header
            document.getElementById('clock').textContent = `${day}/${now.getMonth()+1}/${year} ${h}:${m}:${s}`;
            
            // Tanggal untuk Tanda Tangan
            document.getElementById('footer-date').textContent = `${day} ${month} ${year}`;
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>

</body>
</html>