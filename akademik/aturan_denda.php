<?php
session_start();
include '../config/koneksi.php';
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("location: ../login/login.php"); exit; }

// 1. LOGIKA UPDATE DURASI
if (isset($_POST['update_durasi'])) {
    foreach ($_POST['durasi'] as $role => $days) {
        $days = (int)$days;
        mysqli_query($koneksi, "UPDATE loan_rules SET max_days='$days' WHERE role='$role'");
    }
    header("location: aturan_denda.php?pesan=updated"); exit;
}

// 2. LOGIKA UPDATE DENDA
if (isset($_POST['update_rule'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id']);
    $tarif = mysqli_real_escape_string($koneksi, $_POST['tarif']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    mysqli_query($koneksi, "UPDATE penalty_rules SET tarif='$tarif', is_active='$is_active', deskripsi='$deskripsi' WHERE id='$id'");
    header("location: aturan_denda.php?pesan=updated"); exit;
}

$q_durasi = mysqli_query($koneksi, "SELECT * FROM loan_rules");
$q_rules = mysqli_query($koneksi, "SELECT * FROM penalty_rules WHERE tipe_denda IN ('overdue', 'damage', 'lost') ORDER BY tipe_denda, id");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfigurasi - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .rule-card{background:white;border-radius:12px;padding:20px;margin-bottom:15px;box-shadow:0 2px 10px rgba(0,0,0,.05);border-left:4px solid #B46932}
        .add-section{background:white;padding:20px;border-radius:12px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
        .btn-save{background:#B46932;color:white;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-weight:600;}
        .form-row{display:grid;grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));gap:15px;margin-bottom:15px}
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i> <span>SIPUS POLSA</span></div>
        <nav>
            <a href="akademik_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php"><i class="fa-solid fa-users"></i> Melihat Anggota</a>
            <a href="transaksi_buku.php"><i class="fa-solid fa-exchange-alt"></i> Transaksi Buku</a>
            <a href="denda.php"><i class="fa-solid fa-coins"></i> Mengubah &amp; Rekap Denda</a>
            <a href="statistik_pengunjung.php"><i class="fa-solid fa-chart-line"></i> Statistika Pengunjung</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Yakin ingin keluar?')"><i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header><h1>Konfigurasi Aturan</h1></header>

        <div class="add-section">
            <h3><i class="fa-solid fa-calendar-days"></i> Aturan Maksimal Hari Peminjaman</h3>
            <form method="POST">
                <div class="form-row">
                    <?php while($d = mysqli_fetch_assoc($q_durasi)): ?>
                        <div>
                            <label style="font-weight:bold;"><?php echo ucfirst($d['role']); ?></label>
                            <input type="number" name="durasi[<?php echo $d['role']; ?>]" value="<?php echo $d['max_days']; ?>" required>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button type="submit" name="update_durasi" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Simpan Durasi</button>
            </form>
        </div>

        <h3>Aturan Denda</h3>
        <?php while($r = mysqli_fetch_assoc($q_rules)): ?>
        <div class="rule-card">
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <div style="display:flex; justify-content:space-between;">
                    <strong><?php echo htmlspecialchars($r['nama_aturan']); ?></strong>
                    <label><input type="checkbox" name="is_active" <?php echo $r['is_active']?'checked':''; ?>> Aktif</label>
                </div>
                <div class="form-row" style="margin-top:10px;">
                    <div><label>Tarif (Rp)</label><input type="number" name="tarif" value="<?php echo $r['tarif']; ?>"></div>
                    <div><label>Deskripsi</label><input type="text" name="deskripsi" value="<?php echo htmlspecialchars($r['deskripsi']); ?>"></div>
                </div>
                <button type="submit" name="update_rule" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            </form>
        </div>
        <?php endwhile; ?>
    </div>
</body>
</html>