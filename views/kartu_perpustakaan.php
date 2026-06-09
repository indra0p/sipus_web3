<?php
session_start();
include '../config/koneksi.php';
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("location: ../login/login.php?pesan=belum_login"); exit; }
$id_user = $_SESSION['id_user'] ?? '';
$nama_user = $_SESSION['nama'] ?? 'Pengguna';
$nim_user = $_SESSION['username'] ?? 'Member';
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$id_user'");
$data_user = mysqli_fetch_assoc($query_user);
$foto_user = $data_user['foto'] ?? '';
$role_user = $data_user['role'] ?? 'mahasiswa';
$email_user = $data_user['email'] ?? '';
$path_foto = (!empty($foto_user) && file_exists("../assets/img/profil/" . $foto_user)) ? "../assets/img/profil/" . $foto_user : "https://ui-avatars.com/api/?name=" . urlencode($nama_user) . "&background=3b82f6&color=fff&bold=true";
$qr_url = "https://quickchart.io/qr?text=" . urlencode($nim_user) . "&size=200&dark=3E2723&margin=2";
$barcode_url = "https://barcodeapi.org/api/128/" . urlencode($nim_user);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Perpustakaan - SIPUS POLSA</title>
    <link class="no-print" rel="stylesheet" href="../assets/css/users-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Perbaikan Navigasi Sidebar */
        .sidebar nav a { display: flex !important; align-items: center; padding: 12px 20px; gap: 15px; text-decoration: none; transition: all 0.3s; }
        .sidebar nav a i { width: 20px; text-align: center; }

        .card-wrapper { display: flex; justify-content: center; padding: 20px 0; }
        .lib-card { background: linear-gradient(135deg, #B46932 0%, #8E5124 100%); color: white; border-radius: 20px; width: 420px; padding: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); position: relative; overflow: hidden; }
        .lib-card::before { content: ''; position: absolute; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%; top: -60px; right: -60px; }
        .lib-card::after { content: ''; position: absolute; width: 150px; height: 150px; background: rgba(255,255,255,0.03); border-radius: 50%; bottom: -40px; left: -40px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: relative; z-index: 1; }
        .card-header h3 { font-size: 18px; font-weight: 800; letter-spacing: 1px; }
        .card-header small { font-size: 10px; opacity: 0.7; text-transform: uppercase; letter-spacing: 2px; }
        .card-body { display: flex; gap: 20px; align-items: center; position: relative; z-index: 1; }
        .card-photo { width: 80px; height: 80px; border-radius: 15px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3); }
        .card-info h4 { font-size: 16px; margin-bottom: 3px; }
        .card-info p { font-size: 12px; opacity: 0.8; margin-bottom: 2px; }
        .card-info .role-badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 20px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }
        .card-codes { display: flex; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.15); position: relative; z-index: 1; align-items: center; justify-content: center; }
        .qr-box, .barcode-box { background: white; border-radius: 12px; padding: 8px; }
        .qr-box img { width: 100px; height: 100px; border-radius: 8px; }
        .barcode-box { flex: 1; text-align: center; padding: 10px; }
        .barcode-box img { width: 100%; max-height: 50px; }
        .barcode-box p { color: #333; font-size: 11px; font-weight: 700; margin-top: 4px; letter-spacing: 2px; }
        .btn-print { display: inline-flex; align-items: center; gap: 8px; background: #B46932; color: white; padding: 12px 25px; border-radius: 12px; text-decoration: none; font-weight: 600; margin-top: 20px; transition: 0.3s; border: none; cursor: pointer; font-size: 14px; }
        .btn-print:hover { background: #8E5124; transform: translateY(-2px); }
        @media print { .sidebar, .no-print { display: none !important; } .main-content { margin: 0 !important; padding: 20px !important; } }
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
            <a href="denda_saya.php"><i class="fa-solid fa-coins"></i> <span>Denda</span></a>
            <a href="kartu_perpustakaan.php" class="active"><i class="fa-solid fa-id-card"></i> <span>Kartu</span></a>
            <a href="notifikasi.php"><i class="fa-solid fa-bell"></i> <span>Notifikasi</span></a>
            <a href="profil.php"><i class="fa-solid fa-user-gear"></i> <span>Profil</span></a>
            <a href="kontak_petugas.php"><i class="fa-solid fa-headset"></i> <span>Hubungi Petugas</span></a>
            <a href="../logout.php" class="logout" onclick="return confirm('Keluar?')"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a>
        </nav>
    </aside>
    <main class="main-content">
        <header>
            <h1 class="page-title">Kartu Perpustakaan Digital</h1>
            <p style="color: #64748b;">Tunjukkan kartu ini kepada petugas saat check-in atau peminjaman buku.</p>
        </header>

        <div class="card-wrapper">
            <div class="lib-card" id="libCard">
                <div class="card-header">
                    <div><h3>SIPUS POLSA</h3><small>Kartu Anggota Perpustakaan</small></div>
                    <i class="fa-solid fa-book-open" style="font-size: 28px; opacity: 0.3;"></i>
                </div>
                <div class="card-body">
                    <img src="<?php echo $path_foto; ?>" class="card-photo" alt="Foto">
                    <div class="card-info">
                        <h4><?php echo htmlspecialchars($nama_user); ?></h4>
                        <p><i class="fa-solid fa-id-badge"></i> <?php echo htmlspecialchars($nim_user); ?></p>
                        <?php if(!empty($email_user)): ?><p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($email_user); ?></p><?php endif; ?>
                        <span class="role-badge"><?php echo ucfirst($role_user); ?></span>
                    </div>
                </div>
                <div class="card-codes">
                    <div class="qr-box"><img src="<?php echo $qr_url; ?>" alt="QR Code"></div>
                    <div class="barcode-box">
                        <img src="<?php echo $barcode_url; ?>" alt="Barcode">
                        <p><?php echo htmlspecialchars($nim_user); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center;" class="no-print">
            <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Cetak Kartu</button>
        </div>
    </main>
</body>
</html>