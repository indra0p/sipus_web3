<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

$id_user = $_SESSION['id_user'] ?? '';
$id_buku = mysqli_real_escape_string($koneksi, $_GET['id']);

// 1. Ambil Detail Buku
$query_buku = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku = '$id_buku'");
$buku = mysqli_fetch_array($query_buku);

if (!$buku) {
    echo "<script>alert('Buku tidak ditemukan!'); window.location='cari_buku.php';</script>";
    exit;
}

// 2. Cek apakah User sedang meminjam atau menunggu approval buku ini
$cek_pinjam = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id_user = '$id_user' AND id_buku = '$id_buku' AND status IN ('Dipinjam','Menunggu')");
$sedang_pinjam = mysqli_num_rows($cek_pinjam) > 0;

$nama_user = $_SESSION['nama'] ?? 'Pengguna';
$nim_user  = $_SESSION['username'] ?? 'Member';

// Ambil foto profil terbaru
$query_user = mysqli_query($koneksi, "SELECT foto FROM users WHERE id = '$id_user'");
$data_user  = mysqli_fetch_assoc($query_user);
$foto_user  = $data_user['foto'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail - <?php echo htmlspecialchars($buku['judul']); ?></title>
    <!-- Tambahkan ?v=time untuk menghindari cache -->
    <link rel="stylesheet" href="../assets/css/users-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-book-open"></i>
            <span>SIPUS POLSA</span>
        </div>
        
        <div class="user-info">
            <?php 
            $path_foto = (!empty($foto_user) && file_exists("../assets/img/profil/" . $foto_user)) 
                         ? "../assets/img/profil/" . $foto_user 
                         : "https://ui-avatars.com/api/?name=" . urlencode($nama_user) . "&background=3b82f6&color=fff&bold=true";
            ?>
            <img src="<?php echo $path_foto; ?>" alt="User Profile">
            <div class="user-text">
                <p><?php echo htmlspecialchars($nama_user); ?></p>
                <small><?php echo htmlspecialchars($nim_user); ?></small>
            </div>
        </div>

        <nav>
            <a href="users_dashboard.php"><i class="fa-solid fa-house"></i> <span>Dashboard</span></a>
            <a href="cari_buku.php" class="active"><i class="fa-solid fa-magnifying-glass"></i> <span>Cari Buku</span></a>
            <a href="peminjaman_saya.php"><i class="fa-solid fa-book-reader"></i> <span>Pinjaman</span></a>
            <a href="denda_saya.php"><i class="fa-solid fa-coins"></i> <span>Denda</span></a>
            <a href="kartu_perpustakaan.php"><i class="fa-solid fa-id-card"></i> <span>Kartu</span></a>
            <a href="notifikasi.php"><i class="fa-solid fa-bell"></i> <span>Notifikasi</span></a>
            <a href="profil.php"><i class="fa-solid fa-user-gear"></i> <span>Profil</span></a>
            <a href="kontak_petugas.php"><i class="fa-solid fa-headset"></i> <span>Hubungi Petugas</span></a>
            <a href="../logout.php" class="logout" onclick="return confirm('Keluar?')"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="breadcrumb">
            <a href="cari_buku.php" style="color: var(--biru-utama); text-decoration:none;">Koleksi</a> 
            <i class="fa-solid fa-chevron-right" style="font-size: 10px; margin: 0 10px;"></i> 
            Detail Buku
        </div>

        <header>
            <h1 class="page-title">Informasi Detail Buku</h1>
        </header>

        <div class="detail-container">
            <div class="detail-card">
                <div class="cover-section">
                    <?php $sampul = !empty($buku['sampul']) ? "../assets/img/sampul/" . $buku['sampul'] : "../assets/img/no-cover.jpg"; ?>
                    <img src="<?php echo $sampul; ?>" alt="Cover" class="detail-cover">
                    
                    <div class="detail-stock-badge <?php echo ($buku['stok'] <= 0) ? 'out' : ''; ?>">
                        <i class="fa-solid fa-box-archive"></i> <?php echo $buku['stok']; ?> Tersedia
                    </div>
                </div>

                <div class="info-section">
                    <span class="detail-category"><?php echo htmlspecialchars($buku['jenis_buku']); ?></span>
                    <h2 class="detail-title"><?php echo htmlspecialchars($buku['judul']); ?></h2>
                    <p class="detail-author">Ditulis oleh <strong><?php echo htmlspecialchars($buku['pengarang']); ?></strong></p>

                    <div class="info-grid">
                        <div class="info-item">
                            <small>Penerbit</small>
                            <p><?php echo htmlspecialchars($buku['penerbit'] ?? '-'); ?></p>
                        </div>
                        <div class="info-item">
                            <small>ID Buku</small>
                            <p>#<?php echo htmlspecialchars($buku['id_buku']); ?></p>
                        </div>
                    </div>

                    <div class="synopsis-section">
                        <h3><i class="fa-solid fa-align-left"></i> Sinopsis</h3>
                        <div class="synopsis-content">
                            <?php echo nl2br(htmlspecialchars($buku['sinopsis'] ?? 'Sinopsis belum tersedia untuk buku ini.')); ?>
                        </div>
                    </div>

                    <?php if ($sedang_pinjam): ?>
                        <div class="loan-alert">
                            <i class="fa-solid fa-info-circle"></i>
                            <p>Anda sedang meminjam buku ini. Cek status di <strong>Peminjaman Saya</strong>.</p>
                        </div>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <a href="cari_buku.php" class="btn-back">
                            <i class="fa-solid fa-arrow-left"></i> Kembali ke Koleksi
                        </a>

                        <?php if ($sedang_pinjam): ?>
                            <button class="btn-pinjam disabled" disabled style="background: #9ca3af; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; cursor: not-allowed;">
                                <i class="fa-solid fa-check"></i> Sudah Dipinjam / Menunggu Approval
                            </button>
                        <?php elseif ($buku['stok'] <= 0): ?>
                            <button class="btn-pinjam disabled" disabled style="background: #ef4444; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; cursor: not-allowed;">
                                <i class="fa-solid fa-ban"></i> Stok Habis
                            </button>
                        <?php else: ?>
                            <a href="pinjam_proses.php?id=<?php echo $buku['id_buku']; ?>" 
                               class="btn-pinjam" 
                               onclick="return confirm('Ajukan peminjaman buku ini? Pengajuan akan menunggu persetujuan admin.')"
                               style="background: #B46932; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; text-decoration: none; display: inline-block;">
                                <i class="fa-solid fa-book-open"></i> Ajukan Peminjaman
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>