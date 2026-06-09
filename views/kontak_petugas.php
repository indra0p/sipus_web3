<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

$id_user = $_SESSION['id_user'] ?? '';

// Ambil data user untuk sidebar
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$id_user'");
$user = mysqli_fetch_assoc($query_user);

$nama_user = $user['nama'] ?? $_SESSION['nama'] ?? 'Pengguna';
$nim_user  = $user['username'] ?? $_SESSION['username'] ?? '-';
$role_user = $user['role'] ?? 'Mahasiswa';
$foto_user = $user['foto'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hubungi Petugas - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/users-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Perbaikan Navigasi Sidebar agar Presisi */
        .sidebar nav a { display: flex !important; align-items: center; padding: 12px 20px; gap: 15px; text-decoration: none; transition: all 0.3s; }
        .sidebar nav a i { width: 20px; text-align: center; }
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
                <!-- Role User -->
                <div style="font-size: 10px; color: #3b82f6; font-weight: 700; text-transform: uppercase; margin-top: 2px;">
                    <?php echo htmlspecialchars($role_user); ?>
                </div>
            </div>
        </div>

        <nav>
            <a href="users_dashboard.php"><i class="fa-solid fa-house"></i> <span>Dashboard</span></a>
            <a href="cari_buku.php"><i class="fa-solid fa-magnifying-glass"></i> <span>Cari Buku</span></a>
            <a href="ebook.php"><i class="fa-solid fa-laptop-code"></i> <span>E-Book Digital</span></a>
            <a href="peminjaman_saya.php"><i class="fa-solid fa-book-reader"></i> <span>Pinjaman</span></a>
            <a href="denda_saya.php"><i class="fa-solid fa-coins"></i> <span>Denda</span></a>
            <a href="kartu_perpustakaan.php"><i class="fa-solid fa-id-card"></i> <span>Kartu</span></a>
            <a href="notifikasi.php"><i class="fa-solid fa-bell"></i> <span>Notifikasi</span></a>
            <a href="profil.php"><i class="fa-solid fa-user-gear"></i> <span>Profil</span></a>
            <a href="kontak_petugas.php" class="active"><i class="fa-solid fa-headset"></i> <span>Hubungi Petugas</span></a>
            <a href="../logout.php" class="logout" onclick="return confirm('Keluar?')">
                <i class="fa-solid fa-power-off"></i> <span>Logout</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="breadcrumb">Bantuan & Layanan</div>
        <header>
            <h1 class="page-title">Hubungi Kami</h1>
            <p class="subtitle">Punya pertanyaan mengenai denda, buku hilang, atau perpanjangan? Kami siap membantu.</p>
        </header>

        <div class="contact-grid">
            <!-- WhatsApp -->
            <div class="contact-card shadow-sm">
                <div class="icon-box whatsapp-bg"><i class="fa-brands fa-whatsapp"></i></div>
                <h3>WhatsApp Layanan</h3>
                <p>Respon cepat melalui chat untuk menanyakan stok buku atau info denda.</p>
                <a href="https://wa.me/6289505184707" class="btn-contact btn-wa" target="_blank">
                    <i class="fa-brands fa-whatsapp"></i> Chat Sekarang
                </a>
            </div>

            <!-- Email -->
            <div class="contact-card shadow-sm">
                <div class="icon-box email-bg"><i class="fa-regular fa-envelope"></i></div>
                <h3>Email Resmi</h3>
                <p>Kirimkan keluhan resmi atau surat permohonan keanggotaan melalui email kami.</p>
                <a href="https://mail.google.com/mail/?view=cm&fs=1&to=kholidmashuri2@gmail.com&su=Tanya%20Layanan%20SIPUS%20POLSA" 
                   target="_blank" class="btn-contact btn-mail">
                    <i class="fa-regular fa-paper-plane"></i> Kirim via Gmail
                </a>
            </div>

            <!-- Lokasi -->
            <div class="contact-card shadow-sm">
                <div class="icon-box location-bg"><i class="fa-solid fa-location-dot"></i></div>
                <h3>Kantor Fisik</h3>
                <p>Gedung Perpustakaan POLSA, Lantai 1. Buka Senin - Jumat pukul 08:00 - 16:00 WIB.</p>
                <a href="https://www.google.com/maps/search/?api=1&query=Politeknik+Sawunggalih+Aji+Kutoarjo" 
                   target="_blank" class="btn-contact btn-map">
                    <i class="fa-solid fa-map-location-dot"></i> Lihat Lokasi
                </a>
            </div>
        </div>
    </main>

</body>
</html>