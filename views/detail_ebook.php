<?php
session_start();
include '../config/koneksi.php';

// 1. Proteksi Halaman
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

$id_user = $_SESSION['id_user'] ?? '';
$id_buku = mysqli_real_escape_string($koneksi, $_GET['id']);

// 2. Ambil Detail E-Book (Memastikan file digitalnya ada)
$query_buku = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku = '$id_buku' AND file_ebook IS NOT NULL AND file_ebook != ''");
$buku = mysqli_fetch_array($query_buku);

if (!$buku) {
    echo "<script>alert('E-Book tidak ditemukan!'); window.location='ebook.php';</script>";
    exit;
}

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
    <title>Detail E-Book - <?php echo htmlspecialchars($buku['judul']); ?></title>
    <link rel="stylesheet" href="../assets/css/users-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Gaya tambahan khusus lencana digital */
        .badge-digital-detail {
            background: #e0f2fe; 
            color: #0369a1; 
            padding: 4px 10px; 
            border-radius: 6px; 
            font-size: 11px; 
            font-weight: bold;
            border: 1px solid #bae6fd;
            display: inline-block;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .btn-read-now {
            background: #10b981; 
            color: white; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 8px; 
            font-size: 14px; 
            text-decoration: none; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-read-now:hover {
            background: #059669;
        }
    </style>
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
            <a href="cari_buku.php"><i class="fa-solid fa-magnifying-glass"></i> <span>Cari Buku</span></a>
            <a href="ebook.php" class="active"><i class="fa-solid fa-laptop-code"></i> <span>E-Book Digital</span></a>
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
            <a href="ebook.php" style="color: var(--biru-utama); text-decoration:none;">E-Book Digital</a> 
            <i class="fa-solid fa-chevron-right" style="font-size: 10px; margin: 0 10px;"></i> 
            Detail Koleksi Digital
        </div>

        <header>
            <h1 class="page-title">Informasi Detail E-Book</h1>
        </header>

        <div class="detail-container">
            <div class="detail-card">
                
                <div class="cover-section">
                    <?php $sampul = !empty($buku['sampul']) ? "../assets/img/sampul/" . $buku['sampul'] : "../assets/img/no-cover.jpg"; ?>
                    <img src="<?php echo $sampul; ?>" alt="Cover" class="detail-cover">
                </div>

                <div class="info-section">
                    <span class="badge-digital-detail">
                        <i class="fa-solid fa-cloud"></i> <?php echo htmlspecialchars($buku['jenis_buku'] ?? 'E-Book'); ?>
                    </span>
                    
                    <h2 class="detail-title" style="color: #10b981;"><?php echo htmlspecialchars($buku['judul']); ?></h2>
                    <p class="detail-author">Karya: <strong><?php echo htmlspecialchars($buku['pengarang']); ?></strong></p>

                    <div class="info-grid">
                        <div class="info-item">
                            <small>Penerbit Digital</small>
                            <p><?php echo htmlspecialchars($buku['penerbit'] ?? '-'); ?></p>
                        </div>
                        <div class="info-item">
                            <small>Kode Dokumen</small>
                            <p><?php echo !empty($buku['barcode']) ? htmlspecialchars($buku['barcode']) : "#EP-".$buku['id_buku']; ?></p>
                        </div>
                    </div>

                    <div class="synopsis-section">
                        <h3><i class="fa-solid fa-align-left"></i> Sinopsis E-Book</h3>
                        <div class="synopsis-content">
                            <?php echo nl2br(htmlspecialchars($buku['sinopsis'] ?? 'Sinopsis ringkas belum diunggah untuk koleksi berkas digital ini.')); ?>
                        </div>
                    </div>

                    <div class="action-buttons" style="margin-top: 30px; display: flex; gap: 12px; align-items: center;">
                        <a href="ebook.php" class="btn-back" style="display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-arrow-left"></i> Kembali ke E-Book
                        </a>

                        <a href="baca_ebook.php?id=<?php echo $buku['id_buku']; ?>" class="btn-read-now">
                            <i class="fa-solid fa-file-pdf" style="font-size: 16px;"></i> Baca Sekarang
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </main>

</body>
</html>