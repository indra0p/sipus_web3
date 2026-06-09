<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

// Ambil ID User dari session
$id_user = $_SESSION['id_user'] ?? '';

// Query ambil data user terbaru (termasuk kolom 'role')
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$id_user'");
$data  = mysqli_fetch_assoc($query);

// Variabel untuk tampilan
$nama_user   = $data['nama'] ?? $_SESSION['nama'] ?? 'Pengguna';
$nim_user    = $data['username'] ?? $_SESSION['username'] ?? '-';
$jenkel      = $data['jenkel'] ?? '-';
$role_user   = $data['role'] ?? 'Pengguna'; 
$foto_user   = $data['foto'] ?? '';

// Path Foto Profil
$path_foto = (!empty($foto_user) && file_exists("../assets/img/profil/" . $foto_user)) 
             ? "../assets/img/profil/" . $foto_user 
             : "https://ui-avatars.com/api/?name=" . urlencode($nama_user) . "&background=3b82f6&color=fff&bold=true";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - SIPUS POLSA</title>
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
            <img src="<?php echo $path_foto; ?>" alt="User Profile">
            <div class="user-text">
                <p><?php echo htmlspecialchars($nama_user); ?></p>
                <small><?php echo htmlspecialchars($nim_user); ?></small>
                <!-- Tambahan Role User -->
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
            <a href="profil.php" class="active"><i class="fa-solid fa-user-gear"></i> <span>Profil</span></a>
            <a href="kontak_petugas.php"><i class="fa-solid fa-headset"></i> <span>Hubungi Petugas</span></a>
            <a href="../logout.php" class="logout" onclick="return confirm('Keluar?')"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header>
            <h1 class="page-title">Pengaturan Profil</h1>
            <p style="color: #64748b;">Kelola informasi akun dan keamanan password Anda.</p>
        </header>

        <!-- (Konten form profil tetap sama seperti kode Anda sebelumnya) -->
        <?php if(isset($_GET['pesan'])): ?>
            <div class="alert-container">
                <?php if($_GET['pesan'] == 'berhasil'): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Profil dan Password berhasil diperbarui!</div>
                <?php elseif($_GET['pesan'] == 'pass_salah'): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> Konfirmasi password baru tidak cocok!</div>
                <?php elseif($_GET['pesan'] == 'ukuran_besar'): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> Gagal: Ukuran foto maksimal 2MB.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="profile-layout">
            <div class="profile-sidebar-card shadow-sm">
                <div class="profile-avatar-wrapper">
                    <img src="<?php echo $path_foto; ?>" alt="Foto Profil" class="main-avatar">
                </div>
                <h3><?php echo htmlspecialchars($nama_user); ?></h3>
                <p class="user-badge"><?php echo htmlspecialchars($nim_user); ?></p>
                <div class="profile-status">
                    <span class="status-dot"></span> <?php echo ucfirst(htmlspecialchars($role_user)); ?>
                </div>
            </div>

            <div class="profile-main-card shadow-sm">
                <form action="update_profil.php" method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fa-solid fa-address-card"></i> Informasi Dasar
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Ganti Foto Profil</label>
                                <input type="file" name="foto" class="form-input-file" accept="image/*">
                                <small class="input-note">*Gunakan format JPG/PNG, maks 2MB.</small>
                            </div>
                            <div class="form-group">
                                <label>NIM/ID (Username)</label>
                                <input type="text" class="form-control readonly" value="<?php echo htmlspecialchars($nim_user); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($nama_user); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select name="jenkel" class="form-control">
                                    <option value="Laki-laki" <?php echo ($jenkel == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="Perempuan" <?php echo ($jenkel == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- (Sisa form password tetap sama) -->
                    <div class="form-section mt-4">
                        <div class="section-header">
                            <i class="fa-solid fa-shield-halved"></i> Keamanan Akun
                        </div>
                        <p class="section-note">Biarkan kosong jika Anda tidak ingin mengganti password.</p>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Password Baru</label>
                                <div class="input-with-icon"><i class="fa-solid fa-lock"></i><input type="password" name="password_baru" class="form-control" placeholder="Password Baru"></div>
                            </div>
                            <div class="form-group">
                                <label>Konfirmasi Password</label>
                                <div class="input-with-icon"><i class="fa-solid fa-key"></i><input type="password" name="konfirmasi_pass" class="form-control" placeholder="Ulangi Password"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" name="submit" class="btn-save-profile"><i class="fa-solid fa-cloud-arrow-up"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>