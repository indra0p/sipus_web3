<?php
session_start();
include '../config/koneksi.php';
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("location: ../login/login.php"); exit; }

$query_anggota = "SELECT * FROM users WHERE role IN ('mahasiswa', 'dosen', 'karyawan')";
$jml_anggota = mysqli_num_rows(mysqli_query($koneksi, $query_anggota));
$jml_buku = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM buku"));
$jml_pinjam = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE status='Dipinjam'"));

// NEW: Pending approvals
$q_pending_borrow = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE status='Menunggu'");
$pending_borrow = mysqli_fetch_assoc($q_pending_borrow)['t'] ?? 0;
$q_pending_return = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE status='Pengajuan_Kembali'");
$pending_return = mysqli_fetch_assoc($q_pending_return)['t'] ?? 0;
$q_pending_extend = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE perpanjangan_status='requested'");
$pending_extend = mysqli_fetch_assoc($q_pending_extend)['t'] ?? 0;

// NEW: Overdue count
$tgl_now = date('Y-m-d');
$q_overdue = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE status='Dipinjam' AND tgl_kembali_seharusnya < '$tgl_now'");
$overdue_count = mysqli_fetch_assoc($q_overdue)['t'] ?? 0;

// NEW: Today's visitors
$q_visitors = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_user) as t FROM checkin_log WHERE DATE(waktu_checkin) = '$tgl_now' AND tipe='checkin'");
$today_visitors = mysqli_fetch_assoc($q_visitors)['t'] ?? 0;

// NEW: Current occupancy
$q_occ = mysqli_query($koneksi, "SELECT COUNT(DISTINCT cl.id_user) as t FROM checkin_log cl WHERE DATE(cl.waktu_checkin) = '$tgl_now' AND cl.tipe='checkin' AND cl.id_user NOT IN (SELECT id_user FROM checkin_log WHERE DATE(waktu_checkin)='$tgl_now' AND tipe='checkout')");
$occupancy = mysqli_fetch_assoc($q_occ)['t'] ?? 0;

// NEW: Disputed penalties
$q_disputed = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM penalties WHERE status='disputed'");
$disputed_count = mysqli_fetch_assoc($q_disputed)['t'] ?? 0;

$total_pending = $pending_borrow + $pending_return + $pending_extend;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Dashboard - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:25px}
        .card-stat{padding:20px;border-radius:12px;color:white;position:relative;overflow:hidden}
        .card-stat h3{font-size:13px;opacity:.9;margin-bottom:8px;display:flex;align-items:center;gap:8px}
        .card-stat .val{font-size:28px;font-weight:800}
        .card-stat .icon-bg{position:absolute;right:15px;bottom:10px;font-size:40px;opacity:.15}
        .alert-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px;margin-bottom:25px}
        .alert-card{background:white;border-radius:12px;padding:18px;box-shadow:0 2px 10px rgba(0,0,0,.05);display:flex;align-items:center;gap:15px;transition:.2s;cursor:pointer;text-decoration:none;color:#333}
        .alert-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,.1)}
        .alert-icon{width:45px;height:45px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:white;flex-shrink:0}
        .alert-info h4{font-size:14px;margin-bottom:2px}
        .alert-info p{font-size:12px;color:#888}
        .alert-count{font-size:24px;font-weight:800;margin-left:auto}
        .quick-links{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px}
        .quick-link{background:white;padding:20px;border-radius:12px;text-align:center;text-decoration:none;color:#333;box-shadow:0 2px 10px rgba(0,0,0,.05);transition:.2s}
        .quick-link:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,.1)}
        .quick-link i{font-size:24px;color:#B46932;margin-bottom:8px;display:block}
        .quick-link span{font-size:13px;font-weight:600}
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i> <span>SIPUS POLSA</span></div>
        <nav>
            <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php"><i class="fa-solid fa-users"></i> Kelola Anggota</a>
            <a href="buku.php"><i class="fa-solid fa-book"></i> Stok Buku</a>
            <a href="peminjaman.php"><i class="fa-solid fa-right-left"></i> Peminjaman</a>
            <a href="pengembalian.php"><i class="fa-solid fa-rotate-left"></i> Pengembalian</a>
            <a href="kunjungan.php"><i class="fa-solid fa-door-open"></i> Kunjungan</a>
            <a href="denda.php"><i class="fa-solid fa-coins"></i> Denda</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Logout?')"><i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header><h1>Dashboard Utama</h1><p>Selamat datang kembali, Admin. <span style="color:#888;font-size:12px;"><?php echo date('l, d M Y'); ?></span></p></header>

        <div class="stats-grid">
            <div class="card-stat" style="background:linear-gradient(135deg,#a38f85,#8e7c72);">
                <h3><i class="fa-solid fa-users"></i> Total Anggota</h3>
                <div class="val"><?php echo $jml_anggota; ?></div>
                <i class="fa-solid fa-users icon-bg"></i>
            </div>
            <div class="card-stat" style="background:linear-gradient(135deg,#8e7c72,#7a6a61);">
                <h3><i class="fa-solid fa-book"></i> Koleksi Buku</h3>
                <div class="val"><?php echo $jml_buku; ?></div>
                <i class="fa-solid fa-book icon-bg"></i>
            </div>
            <div class="card-stat" style="background:linear-gradient(135deg,#B46932,#8E5124);">
                <h3><i class="fa-solid fa-right-left"></i> Aktif Pinjam</h3>
                <div class="val"><?php echo $jml_pinjam; ?></div>
                <i class="fa-solid fa-right-left icon-bg"></i>
            </div>
            <div class="card-stat" style="background:linear-gradient(135deg,#27ae60,#1e8449);">
                <h3><i class="fa-solid fa-door-open"></i> Pengunjung Saat Ini</h3>
                <div class="val"><?php echo $occupancy; ?></div>
                <i class="fa-solid fa-door-open icon-bg"></i>
            </div>
        </div>

        <?php if ($total_pending > 0 || $overdue_count > 0 || $disputed_count > 0): ?>
        <h3 style="margin-bottom:12px;color:#333;font-size:15px;"><i class="fa-solid fa-bell"></i> Perlu Perhatian</h3>
        <div class="alert-cards">
            <?php if($pending_borrow > 0): ?>
            <a href="peminjaman.php" class="alert-card">
                <div class="alert-icon" style="background:#3498db;"><i class="fa-solid fa-clock"></i></div>
                <div class="alert-info"><h4>Pengajuan Pinjam</h4><p>Menunggu approval</p></div>
                <div class="alert-count" style="color:#3498db;"><?php echo $pending_borrow; ?></div>
            </a>
            <?php endif; ?>
            <?php if($pending_return > 0): ?>
            <a href="pengembalian.php" class="alert-card">
                <div class="alert-icon" style="background:#9b59b6;"><i class="fa-solid fa-rotate-left"></i></div>
                <div class="alert-info"><h4>Pengajuan Kembali</h4><p>Menunggu approval</p></div>
                <div class="alert-count" style="color:#9b59b6;"><?php echo $pending_return; ?></div>
            </a>
            <?php endif; ?>
            <?php if($pending_extend > 0): ?>
            <a href="pengembalian.php" class="alert-card">
                <div class="alert-icon" style="background:#2ecc71;"><i class="fa-solid fa-calendar-plus"></i></div>
                <div class="alert-info"><h4>Perpanjangan</h4><p>Menunggu approval</p></div>
                <div class="alert-count" style="color:#2ecc71;"><?php echo $pending_extend; ?></div>
            </a>
            <?php endif; ?>
            <?php if($overdue_count > 0): ?>
            <a href="pengembalian.php" class="alert-card">
                <div class="alert-icon" style="background:#e74c3c;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="alert-info"><h4>Terlambat Kembali</h4><p>Buku melewati batas</p></div>
                <div class="alert-count" style="color:#e74c3c;"><?php echo $overdue_count; ?></div>
            </a>
            <?php endif; ?>
            <?php if($disputed_count > 0): ?>
            <a href="denda.php" class="alert-card">
                <div class="alert-icon" style="background:#f39c12;"><i class="fa-solid fa-gavel"></i></div>
                <div class="alert-info"><h4>Keberatan Denda</h4><p>Perlu review</p></div>
                <div class="alert-count" style="color:#f39c12;"><?php echo $disputed_count; ?></div>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <h3 style="margin:25px 0 12px;color:#333;font-size:15px;"><i class="fa-solid fa-chart-bar"></i> Statistik Hari Ini</h3>
        <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
            <div style="background:white;padding:15px;border-radius:12px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.04);">
                <div style="font-size:22px;font-weight:800;color:#27ae60;"><?php echo $today_visitors; ?></div>
                <div style="font-size:11px;color:#888;">Pengunjung Hari Ini</div>
            </div>
            <div style="background:white;padding:15px;border-radius:12px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.04);">
                <div style="font-size:22px;font-weight:800;color:#e74c3c;"><?php echo $overdue_count; ?></div>
                <div style="font-size:11px;color:#888;">Peminjaman Terlambat</div>
            </div>
        </div>

        <h3 style="margin:25px 0 12px;color:#333;font-size:15px;"><i class="fa-solid fa-bolt"></i> Akses Cepat</h3>
        <div class="quick-links">
            <a href="laporan_anggota.php" target="_blank" class="quick-link"><i class="fa-solid fa-print"></i><span>Cetak Daftar Anggota</span></a>
            <a href="laporan_buku.php" target="_blank" class="quick-link"><i class="fa-solid fa-print"></i><span>Cetak Laporan Buku</span></a>
            <a href="laporan_peminjaman.php" target="_blank" class="quick-link"><i class="fa-solid fa-print"></i><span>Cetak Riwayat Pinjam</span></a>
            <a href="laporan_denda.php" target="_blank" class="quick-link" style="border: 1px solid #B46932;"><i class="fa-solid fa-file-invoice-dollar"></i><span>Cetak Rekap Denda</span></a>
            <a href="kunjungan.php" class="quick-link"><i class="fa-solid fa-door-open"></i><span>Kelola Kunjungan</span></a>
        </div>
    </div>
</body>
</html>