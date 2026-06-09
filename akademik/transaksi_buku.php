<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login & Role Akses Akademik
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || !in_array($_SESSION['role'], ["akademik", "karyawan"])) {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi & Koleksi Buku - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.05); margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        table th { background-color: #f8f9fa; color: #333; font-weight: 600; }
        
        /* Badge Kustomisasi */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; display: inline-block; }
        .badge-total { background: #e0f2f1; color: #00796b; }
        .badge-populer { background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }
        .badge-status { background: #e67e22; color: white; border-radius: 4px; padding: 3px 6px; font-size: 11px; }

        /* Struktur Kontrol Navigasi Tab Murni CSS */
        .tab-wrapper { margin-top: 25px; }
        .tab-nav { display: flex; gap: 5px; border-bottom: 2px solid #e0e0e0; margin-bottom: 15px; flex-wrap: wrap; }
        .tab-btn { padding: 12px 20px; font-weight: bold; font-size: 14px; color: #666; cursor: pointer; border: none; background: none; border-bottom: 3px solid transparent; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
        .tab-btn:hover { color: #B46932; }
        
        .tab-radio { display: none; }
        .tab-content { display: none; }

        /* Logika Interaksi Tab Terpilih */
        #tab-stok:checked ~ .tab-nav label[for="tab-stok"],
        #tab-tren:checked ~ .tab-nav label[for="tab-tren"],
        #tab-ebook:checked ~ .tab-nav label[for="tab-ebook"],
        #tab-aktif:checked ~ .tab-nav label[for="tab-aktif"] {
            color: #B46932;
            border-bottom-color: #B46932;
        }

        #tab-stok:checked ~ #content-stok,
        #tab-tren:checked ~ #content-tren,
        #tab-ebook:checked ~ #content-ebook,
        #tab-aktif:checked ~ #content-aktif {
            display: block;
        }
        
        .empty-row { text-align: center; color: #999; padding: 30px !important; font-style: italic; }
        .text-accent { color: var(--accent-color, #B46932); font-weight: 600; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i> <span>SIPUS POLSA</span></div>
        <nav>
            <a href="akademik_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php"><i class="fa-solid fa-users"></i> Melihat Anggota</a>
            <a href="transaksi_buku.php" class="active"><i class="fa-solid fa-exchange-alt"></i> Transaksi Buku</a>
            <a href="denda.php"><i class="fa-solid fa-coins"></i> Mengubah &amp; Rekap Denda</a>
            <a href="statistik_pengunjung.php"><i class="fa-solid fa-chart-line"></i> Statistika Pengunjung</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Yakin ingin keluar?')"><i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <div class="header-title">
                <h1>Analisis &amp; Transaksi Koleksi Buku</h1>
                <p>Pemantauan volume sirkulasi peminjaman, tren buku terpopuler, pelacakan dokumen e-book, dan unit aktif.</p>
            </div>
        </header>

        <div class="tab-wrapper">
            <input type="radio" name="transaksi_tabs" id="tab-stok" class="tab-radio" checked>
            <input type="radio" name="transaksi_tabs" id="tab-tren" class="tab-radio">
            <input type="radio" name="transaksi_tabs" id="tab-ebook" class="tab-radio">
            <input type="radio" name="transaksi_tabs" id="tab-aktif" class="tab-radio">

            <div class="tab-nav">
                <label for="tab-stok" class="tab-btn"><i class="fa-solid fa-book"></i> Semua Buku &amp; Total Transaksi</label>
                <label for="tab-tren" class="tab-btn"><i class="fa-solid fa-fire"></i> Buku Sering Dipinjam (Koleksi Terpopuler)</label>
                <label for="tab-ebook" class="tab-btn"><i class="fa-solid fa-laptop-code"></i> E-Book Digital &amp; Berkas PDF</label>
                <label for="tab-aktif" class="tab-btn"><i class="fa-solid fa-hourglass-half"></i> Daftar Peminjam Aktif Saat Ini</label>
            </div>

            <div class="tab-content" id="content-stok">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Sampul</th>
                                <th width="45%">Informasi Buku</th>
                                <th width="20%">Penerbit</th>
                                <th width="20%">Total Transaksi Pinjam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $query_buku = mysqli_query($koneksi, "SELECT b.*, 
                                          (SELECT COUNT(*) FROM peminjaman p WHERE p.id_buku = b.id_buku) AS total_transaksi 
                                          FROM buku b ORDER BY b.id_buku DESC");
                            
                            if(mysqli_num_rows($query_buku) > 0) {
                                while($d = mysqli_fetch_array($query_buku)){
                            ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $no++; ?></td>
                                <td style="text-align: center;">
                                    <?php if(!empty($d['sampul']) && file_exists("../assets/img/sampul/".$d['sampul'])): ?>
                                        <img src="../assets/img/sampul/<?php echo $d['sampul']; ?>" style="width: 50px; height: 68px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 68px; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #999; border-radius: 4px;">No Cover</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="font-size: 15px; color: #333;"><?php echo htmlspecialchars($d['judul']); ?></strong><br>
                                    <small class="text-accent">Penulis: <?php echo htmlspecialchars($d['pengarang']); ?></small>
                                    <?php if(!empty($d['barcode'])): ?>
                                        <br><small style="color:#888;"><i class="fa-solid fa-barcode"></i> <?php echo htmlspecialchars($d['barcode']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($d['penerbit']); ?></td>
                                <td>
                                    <span class="badge badge-total">
                                        <i class="fa-solid fa-arrows-retweet"></i> <?php echo $d['total_transaksi']; ?> Kali Dipinjam
                                    </span>
                                </td>
                            </tr>
                            <?php }} else { echo "<tr><td colspan='5' class='empty-row'>Belum ada pustaka buku terdaftar.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-content" id="content-tren">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="6%">Peringkat</th>
                                <th width="10%">Sampul</th>
                                <th width="50%">Judul Pustaka</th>
                                <th width="19%">Penulis / Pengarang</th>
                                <th width="15%">Frekuensi Pinjam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            $query_populer = mysqli_query($koneksi, "SELECT b.*, COUNT(p.id_peminjaman) AS total_pinjam 
                                             FROM peminjaman p 
                                             JOIN buku b ON p.id_buku = b.id_buku 
                                             GROUP BY p.id_buku 
                                             ORDER BY total_pinjam DESC 
                                             LIMIT 10");
                            
                            if(mysqli_num_rows($query_populer) > 0) {
                                while($p = mysqli_fetch_array($query_populer)){
                            ?>
                            <tr>
                                <td style="text-align: center;">
                                    <?php if($rank == 1): ?>
                                        <i class="fa-solid fa-trophy" style="font-size: 18px; color: #ffc107;"></i>
                                    <?php elseif($rank == 2): ?>
                                        <i class="fa-solid fa-medal" style="font-size: 16px; color: #b0bec5;"></i>
                                    <?php elseif($rank == 3): ?>
                                        <i class="fa-solid fa-medal" style="font-size: 16px; color: #bcaaa4;"></i>
                                    <?php else: ?>
                                        <strong><?php echo $rank; ?></strong>
                                    <?php endif; $rank++; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if(!empty($p['sampul']) && file_exists("../assets/img/sampul/".$p['sampul'])): ?>
                                        <img src="../assets/img/sampul/<?php echo $p['sampul']; ?>" style="width: 50px; height: 68px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 68px; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #999; border-radius: 4px;">No Cover</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong style="color: #2c3e50; font-size: 15px;"><?php echo htmlspecialchars($p['judul']); ?></strong></td>
                                <td><?php echo htmlspecialchars($p['pengarang']); ?></td>
                                <td>
                                    <span class="badge badge-populer">
                                        <i class="fa-solid fa-fire"></i> <?php echo $p['total_pinjam']; ?> Transaksi
                                    </span>
                                </td>
                            </tr>
                            <?php }} else { echo "<tr><td colspan='5' class='empty-row'>Belum ada histori transaksi untuk menentukan tren buku.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-content" id="content-ebook">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Sampul</th>
                                <th width="50%">Informasi E-Book</th>
                                <th width="35%">Berkas PDF Digital</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no_ebook = 1;
                            
                            // Query fleksibel mencari data berdasarkan kriteria string jenis_buku atau ketersediaan berkas fisik
                            $query_ebook = mysqli_query($koneksi, "SELECT * FROM buku WHERE jenis_buku LIKE '%book%' OR (file_ebook IS NOT NULL AND file_ebook != '') ORDER BY id_buku DESC");
                            
                            if($query_ebook && mysqli_num_rows($query_ebook) > 0) {
                                while($eb = mysqli_fetch_array($query_ebook)){
                            ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $no_ebook++; ?></td>
                                <td style="text-align: center;">
                                    <?php if(!empty($eb['sampul']) && file_exists("../assets/img/sampul/".$eb['sampul'])): ?>
                                        <img src="../assets/img/sampul/<?php echo $eb['sampul']; ?>" style="width: 50px; height: 68px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 68px; background: #e8f0fe; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #1a73e8; border-radius: 4px;"><i class="fa-solid fa-file-pdf"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="font-size: 15px; color: #2c3e50;"><?php echo htmlspecialchars($eb['judul']); ?></strong><br>
                                    <small class="text-accent">Penulis: <?php echo htmlspecialchars($eb['pengarang'] ?? '-'); ?></small>
                                    <br><small style="color: #666; font-style: italic;">Kategori: <?php echo htmlspecialchars($eb['jenis_buku'] ?? 'E-Book'); ?></small>
                                </td>
                                <td>
                                    <?php if(!empty($eb['file_ebook'])): ?>
                                        <small style="color: #27ae60; font-weight: 600; font-size: 13.5px;"><i class="fa-solid fa-paperclip"></i> <?php echo htmlspecialchars($eb['file_ebook']); ?></small>
                                    <?php else: ?>
                                        <small style="color: #c0392b;"><i class="fa-solid fa-circle-exclamation"></i> Link berkas belum terunggah</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php }} else { echo "<tr><td colspan='4' class='empty-row'>Belum ada koleksi Digital E-Book terdaftar di database.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-content" id="content-aktif">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">Nama Anggota (Peminjam)</th>
                                <th width="35%">Buku Yang Dipinjam</th>
                                <th width="15%">Tanggal Pinjam</th>
                                <th width="10%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no_aktif = 1;
                            $query_aktif = mysqli_query($koneksi, "SELECT p.*, u.nama, u.username AS identitas, b.judul 
                                           FROM peminjaman p
                                           JOIN users u ON p.id_user = u.id
                                           JOIN buku b ON p.id_buku = b.id_buku
                                           WHERE p.status = 'Dipinjam' OR p.status = 'Pengajuan_Kembali'
                                           ORDER BY p.id_peminjaman DESC");
                            
                            if(mysqli_num_rows($query_aktif) > 0) {
                                while($a = mysqli_fetch_array($query_aktif)){
                            ?>
                            <tr>
                                <td><?php echo $no_aktif++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($a['nama']); ?></strong><br>
                                    <small style="color: #7f8c8d;"><i class="fa-solid fa-id-card"></i> ID/NIM: <?php echo htmlspecialchars($a['identitas']); ?></small>
                                </td>
                                <td><i class="fa-solid fa-book" style="color: #7f8c8d; margin-right: 5px;"></i> <?php echo htmlspecialchars($a['judul']); ?></td>
                                <td><?php echo date('d M Y', strtotime($a['tgl_pinjam'])); ?></td>
                                <td>
                                    <span class="badge-status">
                                        <?php echo ($a['status'] == 'Pengajuan_Kembali') ? 'Proses Retur' : 'Dipinjam'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php }} else { echo "<tr><td colspan='5' class='empty-row'>Bersih! Tidak ada anggota yang sedang memegang atau meminjam buku saat ini.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</body>
</html>