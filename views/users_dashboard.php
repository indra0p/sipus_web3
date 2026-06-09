<?php
session_start();
include '../config/koneksi.php';

// 1. Proteksi Halaman
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

// 2. Ambil data session
$id_user   = $_SESSION['id_user'] ?? '';
$nim_user  = $_SESSION['username'] ?? 'User';
$nama_user = $_SESSION['nama'] ?? 'Member';

// Ambil data foto profil
$query_user = mysqli_query($koneksi, "SELECT foto FROM users WHERE id = '$id_user'");
$data_user  = mysqli_fetch_assoc($query_user);
$foto_user  = $data_user['foto'] ?? '';

// --- LOGIKA HITUNG STATISTIK ---

// A. Total Koleksi Buku
$buku_tersedia = 0;
$q_buku = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM buku");
if ($q_buku) {
    $res_buku = mysqli_fetch_assoc($q_buku);
    $buku_tersedia = $res_buku['total'];
}

// B. Buku yang sedang dipinjam
$total_dipinjam = 0;
if (!empty($id_user)) {
    $q_pinjam = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman WHERE id_user = '$id_user' AND status = 'Dipinjam'");
    if ($q_pinjam) {
        $res_pinjam = mysqli_fetch_assoc($q_pinjam);
        $total_dipinjam = $res_pinjam['total'];
    }
}

// C. LOGIKA DENDA
$tarif_denda = 3000;
$is_rules_active = true;
$q_rules = mysqli_query($koneksi, "SELECT * FROM penalty_rules WHERE tipe_denda = 'overdue' LIMIT 1");
if ($q_rules && mysqli_num_rows($q_rules) > 0) {
    $rule = mysqli_fetch_assoc($q_rules);
    $is_rules_active = (isset($rule['is_active']) && $rule['is_active'] == 1);
    $tarif_denda = (int)$rule['tarif'];
}

$total_overdue_live = 0;
$tgl_now = date('Y-m-d');

if ($is_rules_active) {
    // Perbaikan: Menambahkan NOT EXISTS agar denda yang sudah terdaftar di tabel penalties tidak terhitung dobel
    $q_overdue = mysqli_query($koneksi, "SELECT p.tgl_kembali_seharusnya, p.id_peminjaman 
                                         FROM peminjaman p 
                                         WHERE p.id_user = '$id_user' 
                                         AND p.status = 'Dipinjam' 
                                         AND p.tgl_kembali_seharusnya < '$tgl_now'
                                         AND NOT EXISTS (
                                             SELECT 1 FROM penalties 
                                             WHERE id_user = '$id_user' 
                                             AND catatan LIKE CONCAT('%ID Pinjam: ', p.id_peminjaman, '%')
                                         )");
    
    while ($row = mysqli_fetch_assoc($q_overdue)) {
        $tgl1 = new DateTime($row['tgl_kembali_seharusnya']);
        $tgl2 = new DateTime($tgl_now);
        $days = $tgl2->diff($tgl1)->days;
        $total_overdue_live += ($days * $tarif_denda);
    }
}

// Ambil total sisa denda yang sudah tercatat
$q_total_p = mysqli_query($koneksi, "SELECT SUM(jumlah - jumlah_dibayar) as sisa FROM penalties 
                                     WHERE id_user = '$id_user' AND status IN ('unpaid','partial')");
$r_p = mysqli_fetch_assoc($q_total_p);
$sisa_denda = (float)($r_p['sisa'] ?? 0);

// Hasil akhir denda adalah penjumlahan denda berjalan dan denda tercatat
$total_denda_aktif = $total_overdue_live + $sisa_denda;
$label_denda = ($total_denda_aktif > 0) ? "Ada Denda Belum Lunas" : "Bebas Denda";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/users-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-ebook {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .sidebar nav a {
            display: flex !important;
            align-items: center;
            padding: 12px 20px;
            gap: 15px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar nav a i {
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i> <span>SIPUS POLSA</span></div>
        <div class="user-info">
            <?php 
            $path_foto = (!empty($foto_user) && file_exists("../assets/img/profil/" . $foto_user)) 
                         ? "../assets/img/profil/" . $foto_user 
                         : "https://ui-avatars.com/api/?name=" . urlencode($nama_user) . "&background=3b82f6&color=fff&bold=true";
            ?>
            <img src="<?php echo $path_foto; ?>" alt="Profile">
            <div class="user-text">
                <p><?php echo htmlspecialchars($nama_user); ?></p>
                <small><?php echo htmlspecialchars($nim_user); ?></small>
                <div style="font-size: 10px; color: #3b82f6; font-weight: 700; text-transform: uppercase; margin-top: 2px;">
                    <?php echo htmlspecialchars($_SESSION['role']); ?>
                </div>
            </div>
        </div>
        <nav>
            <a href="users_dashboard.php" class="active"><i class="fa-solid fa-house"></i> <span>Dashboard</span></a>
            <a href="cari_buku.php"><i class="fa-solid fa-magnifying-glass"></i> <span>Cari Buku</span></a>
            <a href="ebook.php"><i class="fa-solid fa-laptop-code"></i> <span>E-Book Digital</span></a>
            <a href="peminjaman_saya.php"><i class="fa-solid fa-book-reader"></i> <span>Pinjaman</span></a>
            <a href="denda_saya.php"><i class="fa-solid fa-coins"></i> <span>Denda</span></a>
            <a href="kartu_perpustakaan.php"><i class="fa-solid fa-id-card"></i> <span>Kartu</span></a>
            <a href="notifikasi.php"><i class="fa-solid fa-bell"></i> <span>Notifikasi</span></a>
            <a href="profil.php"><i class="fa-solid fa-user-gear"></i> <span>Profil</span></a>
            <a href="kontak_petugas.php"><i class="fa-solid fa-headset"></i> <span>Hubungi Petugas</span></a>
            <a href="../logout.php" class="logout" onclick="return confirm('Apakah anda yakin ingin keluar?')"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header>
            <div class="header-title">
                <h1>Member Area</h1>
                <p>Selamat datang di layanan mandiri Perpustakaan</p>
            </div>
            <div class="date-info"><i class="fa-regular fa-calendar-days"></i> <?php echo date('l, d F Y'); ?></div>
        </header>

        <section class="stats-grid">
            <div class="card card-1">
                <div class="card-body"><h3><?php echo $buku_tersedia; ?></h3><p>Total Koleksi Buku</p></div>
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div class="card card-2">
                <div class="card-body"><h3><?php echo $total_dipinjam; ?></h3><p>Buku Sedang Dipinjam</p></div>
                <i class="fa-solid fa-bookmark"></i>
            </div>
            <div class="card card-3">
                <div class="card-body">
                    <h3 style="color: <?php echo ($total_denda_aktif > 0) ? '#ef4444' : '#10b981'; ?>;">
                        Rp <?php echo number_format($total_denda_aktif, 0, ',', '.'); ?>
                    </h3>
                    <p style="font-weight: 600; color: <?php echo ($total_denda_aktif > 0) ? '#b91c1c' : '#4b5563'; ?>;">
                        <?php echo $label_denda; ?>
                    </p>
                </div>
                <i class="fa-solid fa-file-invoice-dollar" style="color: <?php echo ($total_denda_aktif > 0) ? '#ef4444' : '#10b981'; ?>;"></i>
            </div>
        </section>

        <h2 class="section-title">Layanan Utama</h2>
        <div class="menu-grid">
            <a href="cari_buku.php" class="menu-link"><div class="menu-item bg-cari"><i class="fa-solid fa-search"></i><span>Cari &amp; Pinjam Buku</span></div></a>
            <a href="ebook.php" class="menu-link"><div class="menu-item bg-ebook"><i class="fa-solid fa-laptop-code"></i><span>Baca E-Book</span></div></a>
            <a href="peminjaman_saya.php" class="menu-link"><div class="menu-item bg-status"><i class="fa-solid fa-book-open-reader"></i><span>Status Peminjaman</span></div></a>
            <a href="profil.php" class="menu-link"><div class="menu-item bg-profil"><i class="fa-solid fa-user-gear"></i><span>Pengaturan Profil</span></div></a>
            <a href="kontak_petugas.php" class="menu-link"><div class="menu-item bg-kontak"><i class="fa-solid fa-headset"></i><span>Hubungi Petugas</span></div></a>
        </div>
    </main>
</body>
</html>