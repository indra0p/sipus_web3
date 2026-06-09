<?php
session_start();
include '../config/koneksi.php';

// 1. Proteksi Halaman
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

// 2. Ambil data User untuk Sidebar
$id_user   = $_SESSION['id_user'] ?? '';
$nama_user = $_SESSION['nama'] ?? 'User';
$nim_user  = $_SESSION['username'] ?? 'Member';

// Ambil foto profil terbaru dari database
$query_user = mysqli_query($koneksi, "SELECT foto FROM users WHERE id = '$id_user'");
$data_user  = mysqli_fetch_assoc($query_user);
$foto_user  = $data_user['foto'] ?? '';

// 3. Logika Pencarian E-Book
$keyword = "";
if (isset($_POST['cari'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_POST['keyword']);
    $sql = "SELECT * FROM buku WHERE 
            file_ebook IS NOT NULL AND file_ebook != '' AND 
            (judul LIKE '%$keyword%' OR 
            pengarang LIKE '%$keyword%' OR 
            jenis_buku LIKE '%$keyword%') 
            ORDER BY judul ASC";
} else {
    $sql = "SELECT * FROM buku WHERE file_ebook IS NOT NULL AND file_ebook != '' ORDER BY judul ASC";
}
try {
    $query = mysqli_query($koneksi, $sql);
} catch (Throwable $e) {
    die("Kesalahan Query Database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book Digital - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/users-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS untuk merapikan navigasi sidebar */
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
                <div style="font-size: 10px; color: #3b82f6; font-weight: 700; text-transform: uppercase; margin-top: 2px;">
                    <?php echo htmlspecialchars($_SESSION['role'] ?? 'Mahasiswa'); ?>
                </div>
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
            <a href="../logout.php" class="logout" onclick="return confirm('Apakah anda yakin ingin keluar?')"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header>
            <div class="header-title">
                <h1>Koleksi E-Book Digital</h1>
                <p>Temukan dan baca buku digital secara instan untuk mendukung perkuliahanmu.</p>
            </div>
        </header>

        <section class="search-section">
            <form action="" method="POST" class="search-container">
                <input type="text" name="keyword" class="search-input" placeholder="Cari Judul, Penulis, atau Kategori E-Book..." value="<?php echo htmlspecialchars($keyword); ?>">
                <button type="submit" name="cari" class="btn-search">
                    <i class="fa-solid fa-magnifying-glass"></i> Cari
                </button>
            </form>

            <p class="result-count">
                <i class="fa-solid fa-layer-group"></i> Menampilkan <strong><?php echo mysqli_num_rows($query); ?></strong> koleksi e-book.
            </p>
        </section>

        <div class="book-grid">
            <?php 
            if (mysqli_num_rows($query) > 0) {
                while($row = mysqli_fetch_assoc($query)) : 
                    $sampul = !empty($row['sampul']) ? "../assets/img/sampul/" . $row['sampul'] : "../assets/img/no-cover.jpg";
            ?>
            <article class="book-card">
                <div class="book-cover-wrapper">
                    <img src="<?php echo $sampul; ?>" alt="Cover" class="book-cover">
                    <div class="stock-badge" style="background: #10b981; color: white;">
                        <i class="fa-solid fa-cloud-arrow-down"></i> Ready Digital
                    </div>
                </div>
                
                <div class="book-info">
                    <span class="book-category"><?php echo htmlspecialchars($row['jenis_buku'] ?? 'E-Book'); ?></span>
                    <h3 class="book-title"><?php echo htmlspecialchars($row['judul']); ?></h3>
                    <p class="book-author">Oleh: <?php echo htmlspecialchars($row['pengarang']); ?></p>
                    
                    <a href="detail_ebook.php?id=<?php echo $row['id_buku']; ?>" class="btn-detail-card">
                        Lihat Detail
                    </a>
                    
                    <a href="../assets/docs/ebook/<?php echo $row['file_ebook']; ?>" 
                       target="_blank"
                       style="display: block; margin-top: 8px; text-align: center; background: #10b981; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold;">
                        <i class="fa-solid fa-book-open-reader"></i> Baca Sekarang
                    </a>
                </div>
            </article>
            <?php 
                endwhile; 
            } else {
                echo "<div class='empty-search'>
                        <i class='fa-solid fa-search-minus'></i>
                        <p>E-Book tidak ditemukan. Coba gunakan kata kunci lain.</p>
                      </div>";
            }
            ?>
        </div>
    </main>

</body>
</html>