<?php
session_start();
include '../config/koneksi.php';

// Proteksi Halaman
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['role'] != "akademik") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

$tgl_now = date('Y-m-d');

// Statistik Utama
$q_anggota = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
$total_anggota = mysqli_fetch_assoc($q_anggota)['total'] ?? 0;

$q_buku = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM buku");
$total_buku = mysqli_fetch_assoc($q_buku)['total'] ?? 0;

$q_pinjam = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Dipinjam'");
$total_pinjam = $q_pinjam ? (mysqli_fetch_assoc($q_pinjam)['total'] ?? 0) : 0;

$q_denda = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah - jumlah_dibayar), 0) as total FROM penalties WHERE status IN ('unpaid','partial')");
$total_denda = $q_denda ? (mysqli_fetch_assoc($q_denda)['total'] ?? 0) : 0;

// Statistik Tambahan
$q_visitors = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_user) as t FROM checkin_log WHERE DATE(waktu_checkin) = '$tgl_now' AND tipe='checkin'");
$today_visitors = $q_visitors ? (mysqli_fetch_assoc($q_visitors)['t'] ?? 0) : 0;

$q_overdue = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE status='Dipinjam' AND tgl_kembali_seharusnya < '$tgl_now'");
$overdue_count = $q_overdue ? (mysqli_fetch_assoc($q_overdue)['t'] ?? 0) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Akademik - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .welcome-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.05); margin-bottom: 25px; border-left: 5px solid #B46932; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .menu-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.05); display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: inherit; transition: .2s; }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,.1); }
        .card-info h3 { font-size: 22px; font-weight: 800; color: #333; margin: 0; }
        .card-info p { font-size: 12px; color: #666; margin: 0; }
        .card-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        
        .today-stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px; }
        .today-stat-box { background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
        .today-stat-box .val { font-size: 24px; font-weight: 800; }
        .today-stat-box .lbl { font-size: 12px; color: #888; }

        /* Akses Cepat - Dibuat 4 Kolom Rapi */
        .quick-links { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .quick-link { background: white; padding: 20px; border-radius: 12px; text-align: center; text-decoration: none; color: #333; box-shadow: 0 2px 10px rgba(0,0,0,.05); transition: .2s; }
        .quick-link:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.1); }
        .quick-link i { font-size: 24px; color: #B46932; margin-bottom: 10px; display: block; }
        .quick-link span { font-size: 12px; font-weight: 600; display: block; }

        @media (max-width: 992px) { 
            .menu-grid, .quick-links { grid-template-columns: repeat(2, 1fr); } 
        }
        @media (max-width: 576px) { 
            .menu-grid, .today-stats-grid, .quick-links { grid-template-columns: 1fr; } 
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i> <span>SIPUS POLSA</span></div>
        <nav>
            <a href="akademik_dashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php"><i class="fa-solid fa-users"></i> Melihat Anggota</a>
            <a href="transaksi_buku.php"><i class="fa-solid fa-exchange-alt"></i> Transaksi Buku</a>
            <a href="denda.php"><i class="fa-solid fa-coins"></i> Mengubah &amp; Rekap Denda</a>
            <a href="statistik_pengunjung.php"><i class="fa-solid fa-chart-line"></i> Statistika Pengunjung</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Yakin ingin keluar?')"><i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <div class="header-title">
                <h1>Dashboard Akademik</h1>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?></p>
            </div>
        </header>

        <div class="welcome-box">
            <h2>Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>! 👋</h2>
            <p>Hari ini: <strong><?php echo date('l, d F Y'); ?></strong>. Pantau aktivitas sirkulasi perpustakaan di bawah ini.</p>
        </div>

        <div class="menu-grid">
            <a href="anggota.php" class="menu-card">
                <div class="card-info"><h3><?php echo $total_anggota; ?></h3><p>Total Anggota</p></div>
                <div class="card-icon" style="background:#e3f2fd; color:#2196f3;"><i class="fa-solid fa-users"></i></div>
            </a>
            <a href="transaksi_buku.php" class="menu-card">
                <div class="card-info"><h3><?php echo $total_buku; ?></h3><p>Koleksi Buku</p></div>
                <div class="card-icon" style="background:#e8f5e9; color:#4caf50;"><i class="fa-solid fa-book"></i></div>
            </a>
            <a href="transaksi_buku.php" class="menu-card">
                <div class="card-info"><h3><?php echo $total_pinjam; ?></h3><p>Pinjam Aktif</p></div>
                <div class="card-icon" style="background:#fff8e1; color:#ffb300;"><i class="fa-solid fa-clock"></i></div>
            </a>
            <a href="denda.php" class="menu-card">
                <div class="card-info"><h3 style="font-size:16px;">Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></h3><p>Tunggakan</p></div>
                <div class="card-icon" style="background:#fdf2f2; color:#ef5350;"><i class="fa-solid fa-wallet"></i></div>
            </a>
        </div>

        <h3 style="margin:25px 0 12px;color:#333;font-size:15px;"><i class="fa-solid fa-chart-bar"></i> Statistik Hari Ini</h3>
        <div class="today-stats-grid">
            <div class="today-stat-box"><div class="val" style="color:#27ae60;"><?php echo $today_visitors; ?></div><div class="lbl">Pengunjung</div></div>
            <div class="today-stat-box"><div class="val" style="color:#e74c3c;"><?php echo $overdue_count; ?></div><div class="lbl">Terlambat</div></div>
        </div>

        <h3 style="margin:25px 0 12px;color:#333;font-size:15px;"><i class="fa-solid fa-bolt"></i> Akses Cepat Cetak Laporan</h3>
        <div class="quick-links">
            <a href="laporan_anggota.php" target="_blank" class="quick-link"><i class="fa-solid fa-print"></i><span>Cetak Anggota</span></a>
            <a href="laporan_buku.php" target="_blank" class="quick-link"><i class="fa-solid fa-print"></i><span>Cetak Buku</span></a>
            <a href="laporan_peminjaman.php" target="_blank" class="quick-link"><i class="fa-solid fa-print"></i><span>Cetak Pinjam</span></a>
            <a href="laporan_denda.php" target="_blank" class="quick-link"><i class="fa-solid fa-file-invoice-dollar"></i><span>Rekap Denda</span></a>
        </div>
    </div>

</body>
</html>