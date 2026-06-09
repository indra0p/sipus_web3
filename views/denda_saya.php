<?php
session_start();
include '../config/koneksi.php';

// Proteksi halaman, pastikan user sudah login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("location: ../login/login.php?pesan=belum_login"); 
    exit; 
}

$id_user = $_SESSION['id_user'] ?? '';
$nama_user = $_SESSION['nama'] ?? 'Pengguna';
$nim_user = $_SESSION['username'] ?? 'Member';

// 1. Ambil foto profil user
$query_user = mysqli_query($koneksi, "SELECT foto FROM users WHERE id = '$id_user'");
$data_user = mysqli_fetch_assoc($query_user);
$foto_user = $data_user['foto'] ?? '';
$path_foto = (!empty($foto_user) && file_exists("../assets/img/profil/" . $foto_user)) ? "../assets/img/profil/" . $foto_user : "https://ui-avatars.com/api/?name=" . urlencode($nama_user) . "&background=3b82f6&color=fff&bold=true";

// 2. Ambil data denda riwayat dari tabel penalties (Urutkan dari yang terbaru)
$q_penalties = mysqli_query($koneksi, "SELECT pen.*, b.judul FROM penalties pen LEFT JOIN buku b ON pen.id_buku = b.id_buku WHERE pen.id_user = '$id_user' ORDER BY pen.created_at DESC");

// 3. Logika Aturan Denda
$tarif_denda = 0;
$is_rules_active = false;
$q_rules = mysqli_query($koneksi, "SELECT * FROM penalty_rules WHERE tipe_denda = 'overdue' LIMIT 1");
if ($q_rules && mysqli_num_rows($q_rules) > 0) {
    $rule = mysqli_fetch_assoc($q_rules);
    if (isset($rule['is_active']) && $rule['is_active'] == 1) {
        $is_rules_active = true;
        $tarif_denda = (int)$rule['tarif'];
    }
} else {
    $is_rules_active = true; 
    $tarif_denda = 3000;
}

// 4. Hitung Denda Berjalan
$total_overdue_live = 0;
$tgl_now = date('Y-m-d');
$overdue_loans = [];
if ($is_rules_active) {
    $q_overdue = mysqli_query($koneksi, "SELECT p.*, b.judul FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku WHERE p.id_user = '$id_user' AND p.status = 'Dipinjam' AND p.tgl_kembali_seharusnya < '$tgl_now'");
    if ($q_overdue) {
        while ($row = mysqli_fetch_assoc($q_overdue)) {
            $tgl1 = new DateTime($row['tgl_kembali_seharusnya']);
            $tgl2 = new DateTime($tgl_now);
            $days = $tgl2->diff($tgl1)->days;
            $denda = $days * $tarif_denda; 
            $total_overdue_live += $denda;
            $overdue_loans[] = array_merge($row, ['hari_terlambat' => $days, 'denda_hitung' => $denda]);
        }
    }
}

// 5. Ambil total sisa denda akumulatif
$q_total = mysqli_query($koneksi, "SELECT SUM(jumlah - jumlah_dibayar) as sisa FROM penalties WHERE id_user = '$id_user' AND status IN ('unpaid','partial')");
$sisa_denda = 0;
if ($q_total) { 
    $r = mysqli_fetch_assoc($q_total); 
    $sisa_denda = (float)($r['sisa'] ?? 0); 
}

// 6. Proses Pengajuan Keberatan
if (isset($_POST['dispute'])) {
    $id_penalty = mysqli_real_escape_string($koneksi, $_POST['id_penalty']);
    $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan']);
    mysqli_query($koneksi, "UPDATE penalties SET status = 'disputed', catatan_dispute = '$alasan' WHERE id = '$id_penalty' AND id_user = '$id_user'");
    header("location: denda_saya.php?pesan=dispute_sent"); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denda Saya - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/users-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Perbaikan Navigasi Sidebar */
        .sidebar nav a { display: flex !important; align-items: center; padding: 12px 20px; gap: 15px; text-decoration: none; transition: all 0.3s; }
        .sidebar nav a i { width: 20px; text-align: center; }

        .fine-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); border-left: 4px solid #e74c3c; }
        .fine-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .fine-amount { font-size: 20px; font-weight: 800; color: #e74c3c; }
        .fine-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-unpaid { background: #fee2e2; color: #dc2626; }
        .badge-partial { background: #dbeafe; color: #2563eb; }
        .badge-paid { background: #dcfce7; color: #16a34a; }
        .badge-waived { background: #f3e8ff; color: #7c3aed; }
        .badge-disputed { background: #fef3c7; color: #d97706; }
        .sum-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); text-align: center; }
        .btn-dispute { background: #f39c12; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 11px; cursor: pointer; }
        .modal-bg { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-bg.active { display: flex; }
        .modal-box { background: white; border-radius: 16px; padding: 30px; width: 90%; max-width: 400px; }
        .modal-box textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; min-height: 80px; margin: 10px 0; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i><span>SIPUS POLSA</span></div>
        <div class="user-info">
            <img src="<?php echo $path_foto; ?>" alt="Profile">
            <div class="user-text">
                <p><?php echo htmlspecialchars($nama_user); ?></p>
                <small><?php echo htmlspecialchars($nim_user); ?></small>
                <div style="font-size: 10px; color: #3b82f6; font-weight: 700; text-transform: uppercase; margin-top: 2px;">
                    <?php echo htmlspecialchars($_SESSION['role'] ?? 'Mahasiswa'); ?>
                </div>
            </div>
        </div>
        <nav>
            <a href="users_dashboard.php"><i class="fa-solid fa-house"></i> <span>Dashboard</span></a>
            <a href="cari_buku.php"><i class="fa-solid fa-magnifying-glass"></i> <span>Cari Buku</span></a>
            <a href="ebook.php"><i class="fa-solid fa-laptop-code"></i> <span>E-Book Digital</span></a>
            <a href="peminjaman_saya.php"><i class="fa-solid fa-book-reader"></i> <span>Pinjaman</span></a>
            <a href="denda_saya.php" class="active"><i class="fa-solid fa-coins"></i> <span>Denda</span></a>
            <a href="kartu_perpustakaan.php"><i class="fa-solid fa-id-card"></i> <span>Kartu</span></a>
            <a href="notifikasi.php"><i class="fa-solid fa-bell"></i> <span>Notifikasi</span></a>
            <a href="profil.php"><i class="fa-solid fa-user-gear"></i> <span>Profil</span></a>
            <a href="kontak_petugas.php"><i class="fa-solid fa-headset"></i> <span>Hubungi Petugas</span></a>
            <a href="../logout.php" class="logout" onclick="return confirm('Keluar?')"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header><h1 class="page-title">Denda Saya</h1><p style="color:#64748b;">Riwayat denda dan status pembayaran.</p></header>

        <!-- (Konten lainnya tetap sama seperti kode Anda sebelumnya) -->
        <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'dispute_sent'): ?>
            <div style="background:#fef3c7; color:#92400e; padding:12px; border-radius:8px; margin-bottom:15px; border:1px solid #fde68a;">
                <i class="fa-solid fa-gavel"></i> Keberatan denda berhasil dikirim.
            </div>
        <?php endif; ?>

<div class="summary-cards" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px;">
    
    <!-- Kartu 1: Total Denda Anda -->
    <div class="sum-card">
        <h3 style="color:#e74c3c;">Rp <?php echo number_format($sisa_denda, 0, ',', '.'); ?></h3>
        <p>Total Denda Belum Lunas</p>
    </div> <!-- Tag penutup ini yang tadi kurang -->
    
    <!-- Kartu 2: Denda Berjalan -->
    <div class="sum-card">
        <h3 style="color:#f39c12;">Rp <?php echo number_format($total_overdue_live, 0, ',', '.'); ?></h3>
        <p>Denda Berjalan</p>
    </div>
    
    <!-- Kartu 3: Denda Tercatat -->
    <div class="sum-card">
        <h3 style="color:#3498db;">Rp <?php echo number_format($sisa_denda, 0, ',', '.'); ?></h3>
        <p>Denda Tercatat</p>
    </div>
    
</div>

        <!-- Bagian Riwayat dan lainnya... -->
        <?php if (count($overdue_loans) > 0): ?>
        <div class="overdue-section" style="background: #fff5f5; border: 1px solid #fecaca; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            <h3><i class="fa-solid fa-clock"></i> Denda Keterlambatan Berjalan</h3>
            <?php foreach ($overdue_loans as $ol): ?>
            <div style="display:flex; justify-content:space-between; padding:8px 0; font-size:13px;">
                <div><strong><?php echo htmlspecialchars($ol['judul']); ?></strong><br><small>Terlambat <?php echo $ol['hari_terlambat']; ?> hari</small></div>
                <div style="color:#dc2626; font-weight:700;">Rp <?php echo number_format($ol['denda_hitung'], 0, ',', '.'); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </main>
</body>
</html>

