<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

$id_user   = $_SESSION['id_user'] ?? ''; 
$nama_user = $_SESSION['nama'] ?? 'Pengguna'; 
$nim_user  = $_SESSION['username'] ?? 'Member';

$query_user = mysqli_query($koneksi, "SELECT foto FROM users WHERE id = '$id_user'");
$data_user  = mysqli_fetch_assoc($query_user);
$foto_user  = $data_user['foto'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Peminjaman Saya - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/users-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Navigasi */
        .sidebar nav a { display: flex !important; align-items: center; padding: 12px 20px; gap: 15px; text-decoration: none; transition: all 0.3s; }
        .sidebar nav a i { width: 20px; text-align: center; }

        /* PERBAIKAN TABEL AGAR TIDAK TERPOTONG */
        .table-container { 
            width: 100%; 
            overflow-x: auto; /* Ini yang membuat tabel bisa di-scroll ke samping */
            -webkit-overflow-scrolling: touch; 
            margin-top: 15px; 
            background: #fff;
            padding: 10px;
        }

        .custom-table { 
            width: 100%; 
            min-width: 600px; /* Memaksa tabel memiliki lebar minimal agar tidak hancur */
            border-collapse: collapse; 
        }

        /* Styling Badge & Button */
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; display: inline-block; }
        .status-badge.menunggu { background: #dbeafe; color: #1d4ed8; }
        .status-badge.ditolak { background: #fee2e2; color: #dc2626; }
        .btn-ajukan-kembali { background: #9b59b6; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 11px; display: inline-block; }
        
        .catatan-admin { font-size: 10px; color: #e74c3c; margin-top: 2px; }
        .date-box { font-size: 12px; white-space: nowrap; }
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
            <a href="ebook.php"><i class="fa-solid fa-laptop-code"></i> <span>E-Book Digital</span></a>
            <a href="peminjaman_saya.php" class="active"><i class="fa-solid fa-book-reader"></i> <span>Pinjaman</span></a>
            <a href="denda_saya.php"><i class="fa-solid fa-coins"></i> <span>Denda</span></a>
            <a href="kartu_perpustakaan.php"><i class="fa-solid fa-id-card"></i> <span>Kartu</span></a>
            <a href="notifikasi.php"><i class="fa-solid fa-bell"></i> <span>Notifikasi</span></a>
            <a href="profil.php"><i class="fa-solid fa-user-gear"></i> <span>Profil</span></a>
            <a href="kontak_petugas.php"><i class="fa-solid fa-headset"></i> <span>Hubungi Petugas</span></a>
            <a href="../logout.php" class="logout" onclick="return confirm('Keluar dari sistem?')"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header>
            <h1 class="page-title">Peminjaman Saya</h1>
            <p class="subtitle">Pantau status pengajuan dan buku yang sedang kamu pinjam.</p>
        </header>

        <?php
        $sql = "SELECT p.*, b.judul, b.sampul, p.tgl_kembali_seharusnya 
                FROM peminjaman p 
                JOIN buku b ON p.id_buku = b.id_buku 
                WHERE p.id_user = '$id_user' 
                ORDER BY p.id_peminjaman DESC";
        $result = mysqli_query($koneksi, $sql);
        $jumlah = ($result) ? mysqli_num_rows($result) : 0;
        ?>

        <div class="summary-info">
            <i class="fa-solid fa-circle-info"></i>
            <span>Kamu memiliki <strong><?php echo $jumlah; ?></strong> peminjaman aktif/pending.</span>
        </div>

        <?php if ($jumlah > 0): ?>
            <div class="table-container shadow-sm">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Koleksi Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Batas Kembali</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($d = mysqli_fetch_array($result)): ?>
                            <tr>
                                <td>
                                    <div class="book-flex" style="display: flex; align-items: center; gap: 10px;">
                                        <?php $sampul = !empty($d['sampul']) ? "../assets/img/sampul/" . $d['sampul'] : "../assets/img/no-cover.jpg"; ?>
                                        <img src="<?php echo $sampul; ?>" class="table-thumb" style="width: 40px;" alt="Sampul">
                                        <div class="table-book-info">
                                            <span style="font-size: 13px; font-weight: 600;"><?php echo htmlspecialchars($d['judul']); ?></span>
                                            <?php if(!empty($d['catatan_admin']) && $d['status'] == 'Ditolak'): ?>
                                                <div class="catatan-admin"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($d['catatan_admin']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><div class="date-box"><?php echo date('d M Y', strtotime($d['tgl_pinjam'])); ?></div></td>
                                <td><div class="date-box"><?php echo (!empty($d['tgl_kembali_seharusnya']) && $d['tgl_kembali_seharusnya'] != '0000-00-00') ? date('d M Y', strtotime($d['tgl_kembali_seharusnya'])) : "Menunggu"; ?></div></td>
                                <td class="text-center">
                                    <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $d['status'])); ?>">
                                        <?php echo $d['status']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if($d['status'] == 'Dipinjam'): ?>
                                        <a href="pinjam_proses.php?aksi=ajukan_kembali&id=<?php echo $d['id_peminjaman']; ?>" class="btn-ajukan-kembali" onclick="return confirm('Ajukan pengembalian?')">Kembalikan</a>
                                    <?php else: ?> - <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state-card">
                <h3>Belum Ada Pinjaman</h3>
                <p>Sepertinya kamu belum meminjam buku apa pun saat ini.</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>