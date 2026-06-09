<?php
session_start();
include '../config/koneksi.php';

// Cek file notifikasi
if (file_exists('notifikasi_helper.php')) {
    include 'notifikasi_helper.php';
}

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("location: ../login/login.php"); 
    exit; 
}

// --- LOGIKA PERHITUNGAN STATISTIK ---
$tarif = 3000; 
$q_tarif = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'overdue' LIMIT 1");
if($q_tarif && mysqli_num_rows($q_tarif) > 0) {
    $tarif = (int)mysqli_fetch_assoc($q_tarif)['tarif'];
}

// 1. Total Belum Lunas
$q_penalties_db = mysqli_query($koneksi, "SELECT SUM(jumlah - jumlah_dibayar) as t FROM penalties WHERE status IN ('unpaid','partial')");
$total_unpaid = mysqli_fetch_assoc($q_penalties_db)['t'] ?? 0;

// 2. Total Terkumpul
$q_total_collected = mysqli_query($koneksi, "SELECT SUM(jumlah) as t FROM payments");
$total_collected = mysqli_fetch_assoc($q_total_collected)['t'] ?? 0;

// Fetch data tabel
$q_penalties = mysqli_query($koneksi, "SELECT pen.*, u.nama, u.username, b.judul FROM penalties pen JOIN users u ON pen.id_user = u.id LEFT JOIN buku b ON pen.id_buku = b.id_buku ORDER BY pen.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring & Rekap Denda - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stats-row { display: flex; gap: 20px; margin-bottom: 25px; }
        .stat-box { flex: 1; background: white; padding: 22px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.04); border-left: 5px solid #B46932; text-align: center; }
        .stat-box.unpaid { border-left-color: #e74c3c; }
        .stat-box.collected { border-left-color: #27ae60; }
        .stat-box h2 { font-size: 26px; margin: 0 0 5px 0; font-weight: 700; }
        .stat-box p { font-size: 13px; color: #777; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .content-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.04); margin-bottom: 30px; }
        .content-title { margin-top: 0; margin-bottom: 20px; font-size: 18px; color: #333; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i> <span>SIPUS POLSA</span></div>
        <nav>
            <a href="akademik_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php"><i class="fa-solid fa-users"></i> Melihat Anggota</a>
            <a href="transaksi_buku.php"><i class="fa-solid fa-exchange-alt"></i> Transaksi Buku</a>
            <a href="denda.php" class="active"><i class="fa-solid fa-coins"></i> Mengubah &amp; Rekap Denda</a>
            <a href="statistik_pengunjung.php"><i class="fa-solid fa-chart-line"></i> Statistika Pengunjung</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Yakin ingin keluar?')"><i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <div class="header-title">
                <h1>Monitoring & Rekap Denda</h1>
                <p>Peninjauan rekapitulasi sanksi keterlambatan, kerusakan, dan kehilangan buku mahasiswa</p>
            </div>
            <a href="aturan_denda.php" class="btn-add" style="background:#B46932; color:white; padding:10px 18px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:500;"><i class="fa-solid fa-gear"></i> Konfigurasi Aturan</a>
        </header>

        <div class="stats-row">
            <div class="stat-box unpaid">
                <h2 style="color:#e74c3c;">Rp <?php echo number_format($total_unpaid,0,',','.'); ?></h2>
                <p>Total Belum Lunas</p>
            </div>
            <div class="stat-box collected">
                <h2 style="color:#27ae60;">Rp <?php echo number_format($total_collected,0,',','.'); ?></h2>
                <p>Total Terkumpul</p>
            </div>
        </div>

        <div class="content-box">
            <div class="content-title">
                <i class="fa-solid fa-list" style="color: #B46932;"></i> Semua Daftar &amp; Status Denda Mahasiswa
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Anggota / NIM</th>
                            <th>Item Buku</th>
                            <th>Tipe Sanksi</th>
                            <th>Jumlah Denda</th>
                            <th>Telah Dibayar</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $no=1; 
                    if($q_penalties && mysqli_num_rows($q_penalties)>0): 
                        while($p=mysqli_fetch_assoc($q_penalties)):
                            $statusLabel = ['unpaid'=>'Belum Lunas','partial'=>'Cicilan','paid'=>'Lunas','waived'=>'Dihapuskan','disputed'=>'Keberatan'];
                            $statusColor = ['unpaid'=>'#e74c3c','partial'=>'#3498db','paid'=>'#27ae60','waived'=>'#9b59b6','disputed'=>'#f39c12'];
                    ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['nama']); ?></strong><br>
                                <small style="color:#777;"><?php echo $p['username']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($p['judul'] ?? '-'); ?></td>
                            <td><?php echo ucfirst($p['tipe_denda']); ?></td>
                            <td><strong>Rp <?php echo number_format($p['jumlah'],0,',','.'); ?></strong></td>
                            <td style="color: #27ae60;">Rp <?php echo number_format($p['jumlah_dibayar'],0,',','.'); ?></td>
                            <td style="text-align: center;">
                                <span style="padding:4px 10px; border-radius:50px; font-size:11px; font-weight:600; color:white; background:<?php echo $statusColor[$p['status']]??'#888'; ?>">
                                    <?php echo $statusLabel[$p['status']]??$p['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr><td colspan="7" style="text-align:center; padding: 20px; color:#888;">Belum ada data denda.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>