<?php
session_start();
include '../config/koneksi.php';
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("location: ../login/login.php?pesan=belum_login"); 
    exit; 
}

$id_user = $_SESSION['id_user'] ?? '';
$nama_user = $_SESSION['nama'] ?? 'Pengguna';
$nim_user = $_SESSION['username'] ?? 'Member';

$query_user = mysqli_query($koneksi, "SELECT foto FROM users WHERE id = '$id_user'");
$data_user = mysqli_fetch_assoc($query_user);
$foto_user = $data_user['foto'] ?? '';
$path_foto = (!empty($foto_user) && file_exists("../assets/img/profil/" . $foto_user)) ? "../assets/img/profil/" . $foto_user : "https://ui-avatars.com/api/?name=" . urlencode($nama_user) . "&background=3b82f6&color=fff&bold=true";

// ==========================================
// PROSES LOGIKA AKSI (MARK READ & HAPUS)
// ==========================================

if (isset($_GET['markread'])) {
    mysqli_query($koneksi, "UPDATE notifications SET is_read = 1 WHERE id_user = '$id_user'");
    header("location: notifikasi.php"); 
    exit;
}

if (isset($_GET['hapus'])) {
    $id_notif = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM notifications WHERE id = '$id_notif' AND id_user = '$id_user'");
    header("location: notifikasi.php"); 
    exit;
}

if (isset($_GET['hapus_semua'])) {
    mysqli_query($koneksi, "DELETE FROM notifications WHERE id_user = '$id_user'");
    header("location: notifikasi.php"); 
    exit;
}

// ==========================================
// QUERY DATA NOTIFIKASI
// ==========================================
$q_notif = mysqli_query($koneksi, "SELECT * FROM notifications WHERE id_user = '$id_user' ORDER BY created_at DESC LIMIT 50");
$unread = 0;
$notifications = [];
if ($q_notif) { 
    while ($r = mysqli_fetch_assoc($q_notif)) { 
        if (!$r['is_read']) $unread++; 
        $notifications[] = $r; 
    } 
}

$iconMap = ['info'=>'fa-circle-info','approval'=>'fa-check-circle','overdue'=>'fa-clock','fine'=>'fa-coins','checkin'=>'fa-door-open','system'=>'fa-gear'];
$colorMap = ['info'=>'#3498db','approval'=>'#27ae60','overdue'=>'#e74c3c','fine'=>'#f39c12','checkin'=>'#9b59b6','system'=>'#636e72'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/users-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Perbaikan Navigasi Sidebar */
        .sidebar nav a { display: flex !important; align-items: center; padding: 12px 20px; gap: 15px; text-decoration: none; transition: all 0.3s; }
        .sidebar nav a i { width: 20px; text-align: center; }

        .notif-item { 
            background: white; border-radius: 12px; padding: 15px 20px; margin-bottom: 10px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; gap: 15px; 
            align-items: flex-start; transition: 0.2s; position: relative; 
        }
        .notif-item:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); transform: translateY(-1px); }
        .notif-item.unread { background: #f0f7ff; border-left: 3px solid #3498db; }
        .notif-icon { 
            width: 40px; height: 40px; border-radius: 12px; display: flex; 
            align-items: center; justify-content: center; color: white; 
            font-size: 16px; flex-shrink: 0; 
        }
        .notif-body { flex-grow: 1; padding-right: 35px; cursor: pointer; }
        .notif-body h4 { font-size: 14px; margin-bottom: 3px; color: #333; }
        .notif-body p { font-size: 13px; color: #666; line-height: 1.4; }
        .notif-body small { font-size: 11px; color: #aaa; }
        
        .notif-actions { display: flex; justify-content: space-between; margin-bottom: 20px; align-items: center; flex-wrap: wrap; gap: 10px; }
        .btn-group { display: flex; gap: 10px; }
        .btn-markread { background: #3498db; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .btn-danger-all { background: #e74c3c; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        
        .btn-delete-item { 
            position: absolute; right: 20px; top: 50%; transform: translateY(-50%); 
            color: #ccc; border: none; background: none; font-size: 14px; 
            cursor: pointer; transition: 0.2s; text-decoration: none; padding: 8px;
        }
        .btn-delete-item:hover { color: #e74c3c; }
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
            <a href="kartu_perpustakaan.php"><i class="fa-solid fa-id-card"></i> <span>Kartu</span></a>
            <a href="notifikasi.php" class="active"><i class="fa-solid fa-bell"></i> <span>Notifikasi</span></a>
            <a href="profil.php"><i class="fa-solid fa-user-gear"></i> <span>Profil</span></a>
            <a href="kontak_petugas.php"><i class="fa-solid fa-headset"></i> <span>Hubungi Petugas</span></a>
            <a href="../logout.php" class="logout" onclick="return confirm('Keluar?')"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header><h1 class="page-title">Notifikasi</h1></header>
        
        <div class="notif-actions">
            <span style="color:#888; font-size:13px;"><?php echo $unread; ?> belum dibaca dari <?php echo count($notifications); ?> total</span>
            <div class="btn-group">
                <?php if ($unread > 0): ?>
                    <a href="?markread=1" class="btn-markread"><i class="fa-solid fa-check-double"></i> Tandai Semua Dibaca</a>
                <?php endif; ?>
                <?php if (count($notifications) > 0): ?>
                    <a href="?hapus_semua=1" class="btn-danger-all" onclick="return confirm('Apakah Anda yakin ingin menghapus seluruh riwayat notifikasi?')"><i class="fa-solid fa-trash-can"></i> Hapus Semua</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($notifications) > 0): foreach ($notifications as $n): ?>
            <div class="notif-item <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                <div class="notif-icon" style="background:<?php echo $colorMap[$n['tipe']] ?? '#888'; ?>">
                    <i class="fa-solid <?php echo $iconMap[$n['tipe']] ?? 'fa-bell'; ?>"></i>
                </div>
                
                <div class="notif-body" <?php if($n['link']): ?>onclick="window.location='<?php echo $n['link']; ?>'"<?php endif; ?>>
                    <h4><?php echo htmlspecialchars($n['judul']); ?></h4>
                    <p><?php echo htmlspecialchars($n['pesan']); ?></p>
                    <small><i class="fa-regular fa-clock"></i> <?php echo date('d M Y H:i', strtotime($n['created_at'])); ?></small>
                </div>

                <a href="?hapus=<?php echo $n['id']; ?>" class="btn-delete-item" onclick="return confirm('Hapus notifikasi ini?')" title="Hapus">
                    <i class="fa-solid fa-trash"></i>
                </a>
            </div>
        <?php endforeach; else: ?>
            <div style="text-align:center; padding:50px; color:#aaa;">
                <i class="fa-solid fa-bell-slash" style="font-size:40px; margin-bottom:10px;"></i>
                <h3>Belum Ada Notifikasi</h3>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>