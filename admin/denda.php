<?php
session_start();
include '../config/koneksi.php';
include 'notifikasi_helper.php';
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("location: ../login/login.php"); exit; }

// --- LOGIKA PERBAIKAN OTOMATIS DENDA KETERLAMBATAN ---
$tarif = 3000; 
$q_tarif = mysqli_query($koneksi, "SELECT tarif FROM penalty_rules WHERE tipe_denda = 'overdue' LIMIT 1");
if($q_tarif && mysqli_num_rows($q_tarif) > 0) {
    $tarif = (int)mysqli_fetch_assoc($q_tarif)['tarif'];
}

$q_overdue_live = mysqli_query($koneksi, "SELECT id_peminjaman, id_user, id_buku, tgl_kembali_seharusnya FROM peminjaman WHERE status = 'Dipinjam' AND tgl_kembali_seharusnya < CURDATE()");
$today = new DateTime();
while($row = mysqli_fetch_assoc($q_overdue_live)){
    $due = new DateTime($row['tgl_kembali_seharusnya']);
    $diff = $today->diff($due)->days;
    $jumlah_denda = $diff * $tarif;
    $id_peminjaman = $row['id_peminjaman'];

    $q_check = mysqli_query($koneksi, "SELECT id FROM penalties WHERE catatan LIKE '%ID Pinjam: $id_peminjaman%'");
    if(mysqli_num_rows($q_check) == 0) {
        $catatan = "Denda Keterlambatan - ID Pinjam: $id_peminjaman";
        mysqli_query($koneksi, "INSERT INTO penalties (id_user, id_buku, tipe_denda, jumlah, catatan, status, created_by) 
                               VALUES ('{$row['id_user']}', '{$row['id_buku']}', 'overdue', '$jumlah_denda', '$catatan', 'unpaid', 'SYSTEM')");
    } else {
        $p = mysqli_fetch_assoc($q_check);
        mysqli_query($koneksi, "UPDATE penalties SET jumlah = '$jumlah_denda' WHERE id = '{$p['id']}' AND status IN ('unpaid', 'partial')");
    }
}

// --- Handle actions ---
if (isset($_GET['aksi'])) {
    $aksi = $_GET['aksi'];
    $id = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');
    $admin_id = $_SESSION['id_user'];
    $waktu = date('Y-m-d H:i:s');

    if ($aksi == 'waive' && $id) {
        $q = mysqli_query($koneksi, "SELECT id_user, jumlah FROM penalties WHERE id = '$id'");
        $pen = mysqli_fetch_assoc($q);
        mysqli_query($koneksi, "UPDATE penalties SET status='waived', resolved_by='$admin_id', resolved_at='$waktu' WHERE id='$id'");
        if ($pen) createNotification($koneksi, $pen['id_user'], 'Denda Dihapuskan', 'Denda sebesar Rp ' . number_format($pen['jumlah'],0,',','.') . ' telah dihapuskan.', 'fine', 'denda_saya.php');
        header("location: denda.php?pesan=waived"); exit;
    }
    if ($aksi == 'mark_paid' && $id) {
        $q = mysqli_query($koneksi, "SELECT id_user, jumlah FROM penalties WHERE id = '$id'");
        $pen = mysqli_fetch_assoc($q);
        if ($pen) {
            mysqli_query($koneksi, "UPDATE penalties SET status='paid', jumlah_dibayar=jumlah, resolved_by='$admin_id', resolved_at='$waktu' WHERE id='$id'");
            mysqli_query($koneksi, "INSERT INTO payments (id_penalty, id_user, jumlah, metode_bayar, verified_by) VALUES ('$id', '{$pen['id_user']}', '{$pen['jumlah']}', 'cash', '$admin_id')");
            createNotification($koneksi, $pen['id_user'], 'Pembayaran Denda Dikonfirmasi', 'Pembayaran denda Rp ' . number_format($pen['jumlah'],0,',','.') . ' telah dikonfirmasi.', 'fine', 'denda_saya.php');
        }
        header("location: denda.php?pesan=paid"); exit;
    }
    if ($aksi == 'delete_history') {
        // Hapus hanya denda yang sudah 'paid' atau 'waived'
        mysqli_query($koneksi, "DELETE FROM penalties WHERE status IN ('paid', 'waived')");
        header("location: denda.php?pesan=deleted"); exit;
    }
    if ($aksi == 'resolve_dispute' && $id) {
        $resolve = $_GET['resolve'] ?? 'reject';
        $q = mysqli_query($koneksi, "SELECT id_user, jumlah FROM penalties WHERE id = '$id'");
        $pen = mysqli_fetch_assoc($q);
        if ($resolve == 'accept') {
            mysqli_query($koneksi, "UPDATE penalties SET status='waived', resolved_by='$admin_id', resolved_at='$waktu' WHERE id='$id'");
            if ($pen) createNotification($koneksi, $pen['id_user'], 'Keberatan Diterima', 'Keberatan denda Anda diterima.', 'fine', 'denda_saya.php');
        } else {
            mysqli_query($koneksi, "UPDATE penalties SET status='unpaid', catatan_dispute=NULL, resolved_by='$admin_id' WHERE id='$id'");
            if ($pen) createNotification($koneksi, $pen['id_user'], 'Keberatan Ditolak', 'Keberatan denda Anda ditolak.', 'fine', 'denda_saya.php');
        }
        header("location: denda.php?pesan=dispute_resolved"); exit;
    }
}

if (isset($_POST['add_penalty'])) {
    $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user']);
    $id_buku = mysqli_real_escape_string($koneksi, $_POST['id_buku']);
    $tipe = mysqli_real_escape_string($koneksi, $_POST['tipe_denda']);
    $jumlah = mysqli_real_escape_string($koneksi, $_POST['jumlah']);
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan']);
    $id_buku_val = empty($id_buku) ? "NULL" : "'$id_buku'";
    mysqli_query($koneksi, "INSERT INTO penalties (id_user, id_buku, tipe_denda, jumlah, catatan, created_by) VALUES ('$id_user', $id_buku_val, '$tipe', '$jumlah', '$catatan', '{$_SESSION['id_user']}')");
    header("location: denda.php?pesan=added"); exit;
}

if (isset($_POST['partial_pay'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id_penalty']);
    $bayar = mysqli_real_escape_string($koneksi, $_POST['jumlah_bayar']);
    $q = mysqli_query($koneksi, "SELECT * FROM penalties WHERE id = '$id'");
    $pen = mysqli_fetch_assoc($q);
    if ($pen) {
        $new_paid = $pen['jumlah_dibayar'] + $bayar;
        $new_status = ($new_paid >= $pen['jumlah']) ? 'paid' : 'partial';
        mysqli_query($koneksi, "UPDATE penalties SET jumlah_dibayar='$new_paid', status='$new_status' WHERE id='$id'");
        mysqli_query($koneksi, "INSERT INTO payments (id_penalty, id_user, jumlah, metode_bayar, verified_by) VALUES ('$id', '{$pen['id_user']}', '$bayar', 'cash', '{$_SESSION['id_user']}')");
    }
    header("location: denda.php?pesan=payment_recorded"); exit;
}

// Fetch data
$q_penalties = mysqli_query($koneksi, "SELECT pen.*, u.nama, u.username, b.judul FROM penalties pen JOIN users u ON pen.id_user = u.id LEFT JOIN buku b ON pen.id_buku = b.id_buku ORDER BY pen.created_at DESC");
$q_disputed = mysqli_query($koneksi, "SELECT pen.*, u.nama, u.username, b.judul FROM penalties pen JOIN users u ON pen.id_user = u.id LEFT JOIN buku b ON pen.id_buku = b.id_buku WHERE pen.status = 'disputed' ORDER BY pen.created_at DESC");
$disputed_count = $q_disputed ? mysqli_num_rows($q_disputed) : 0;
$q_total_collected = mysqli_query($koneksi, "SELECT SUM(jumlah) as t FROM payments");
$total_collected = mysqli_fetch_assoc($q_total_collected)['t'] ?? 0;
$q_penalties_db = mysqli_query($koneksi, "SELECT SUM(jumlah - jumlah_dibayar) as t FROM penalties WHERE status IN ('unpaid','partial')");
$total_unpaid = mysqli_fetch_assoc($q_penalties_db)['t'] ?? 0;
$q_users = mysqli_query($koneksi, "SELECT id, nama, username FROM users WHERE role != 'admin' ORDER BY nama");
$q_books = mysqli_query($koneksi, "SELECT id_buku, judul FROM buku ORDER BY judul");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Kelola Denda - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stats-row{display:flex;gap:15px;margin-bottom:20px}.stat-box{flex:1;background:white;padding:20px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05);text-align:center}.stat-box h2{font-size:24px;margin-bottom:5px}.stat-box p{font-size:12px;color:#888}
        .tab-container{margin-bottom:15px}.tab-btn{padding:10px 20px;border:none;cursor:pointer;font-size:13px;border-radius:5px 5px 0 0;background:#eee;color:#555;margin-right:2px}.tab-btn.active{background:#B46932;color:white;font-weight:bold}.tab-content{display:none}.tab-content.active{display:block}
        .badge-count{background:#e74c3c;color:white;padding:2px 6px;border-radius:50%;font-size:10px;margin-left:5px}
        .btn-sm{padding:4px 8px;border-radius:4px;text-decoration:none;color:white;font-size:11px;display:inline-block;margin:2px}
        .btn-green{background:#27ae60}.btn-red{background:#e74c3c}.btn-blue{background:#3498db}.btn-orange{background:#f39c12}.btn-purple{background:#9b59b6}
        .add-form{background:white;padding:20px;border-radius:12px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
        .add-form select,.add-form input,.add-form textarea{padding:10px;border:1px solid #ddd;border-radius:6px;font-family:inherit;width:100%;margin-bottom:10px}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .alert-msg{padding:10px;border-radius:5px;margin-bottom:15px}
        .alert-success{background:#d4edda;color:#155724}
        .modal-bg{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center}.modal-bg.active{display:flex}
        .modal-box{background:white;border-radius:16px;padding:25px;width:90%;max-width:400px}
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i> <span>SIPUS POLSA</span></div>
        <nav>
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php"><i class="fa-solid fa-users"></i> Kelola Anggota</a>
            <a href="buku.php"><i class="fa-solid fa-book"></i> Stok Buku</a>
            <a href="peminjaman.php"><i class="fa-solid fa-right-left"></i> Peminjaman</a>
            <a href="pengembalian.php"><i class="fa-solid fa-rotate-left"></i> Pengembalian</a>
            <a href="kunjungan.php"><i class="fa-solid fa-door-open"></i> Kunjungan</a>
            <a href="denda.php" class="active"><i class="fa-solid fa-coins"></i> Denda</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Logout?')"><i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header><div class="header-title"><h1>Kelola Denda & Sanksi</h1><p>Atur denda keterlambatan, kerusakan, dan kehilangan buku</p></div>
        <div style="display:flex;gap:10px;">
           <a href="denda.php?aksi=delete_history" class="btn-sm btn-red" onclick="return confirm('PERINGATAN: Pastikan Anda sudah mencetak atau membackup laporan data denda sebelum menghapusnya. Lanjutkan hapus riwayat yang SUDAH LUNAS/DIHAPUSKAN?')"><i class="fa-solid fa-trash"></i> Hapus Riwayat denda</a>
            <a href="aturan_denda.php" class="btn-add"><i class="fa-solid fa-gear"></i> Konfigurasi Aturan</a>
        </div></header>

        <?php if(isset($_GET['pesan'])): ?>
        <div class="alert-msg alert-success"><i class="fa-solid fa-check"></i>
        <?php
        $pm = $_GET['pesan'];
        if($pm=='added') echo 'Denda berhasil ditambahkan.';
        elseif($pm=='waived') echo 'Denda berhasil dihapuskan.';
        elseif($pm=='paid') echo 'Pembayaran lunas dikonfirmasi.';
        elseif($pm=='payment_recorded') echo 'Pembayaran berhasil dicatat.';
        elseif($pm=='dispute_resolved') echo 'Keberatan telah diselesaikan.';
        elseif($pm=='deleted') echo 'Riwayat denda selesai berhasil dibersihkan.';
        ?>
        </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-box"><h2 style="color:#e74c3c;">Rp <?php echo number_format($total_unpaid,0,',','.'); ?></h2><p>Total Belum Lunas</p></div>
            <div class="stat-box"><h2 style="color:#27ae60;">Rp <?php echo number_format($total_collected,0,',','.'); ?></h2><p>Total Terkumpul</p></div>
            <div class="stat-box"><h2 style="color:#f39c12;"><?php echo $disputed_count; ?></h2><p>Keberatan Pending</p></div>
        </div>

        <div class="tab-container">
            <button class="tab-btn active" onclick="showTab('all')"><i class="fa-solid fa-list"></i> Semua Denda</button>
            <button class="tab-btn" onclick="showTab('disputed')"><i class="fa-solid fa-gavel"></i> Keberatan<?php if($disputed_count>0) echo "<span class='badge-count'>$disputed_count</span>"; ?></button>
            <button class="tab-btn" onclick="showTab('add')"><i class="fa-solid fa-plus"></i> Tambah Denda</button>
        </div>

        <div id="tab-all" class="tab-content active">
            <div class="table-container"><table><thead><tr><th>No</th><th>Anggota</th><th>Buku</th><th>Tipe</th><th>Jumlah</th><th>Dibayar</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            <?php $no=1; if($q_penalties && mysqli_num_rows($q_penalties)>0): while($p=mysqli_fetch_assoc($q_penalties)):
                $sisa = $p['jumlah'] - $p['jumlah_dibayar'];
                $statusLabel = ['unpaid'=>'Belum Lunas','partial'=>'Cicilan','paid'=>'Lunas','waived'=>'Dihapuskan','disputed'=>'Keberatan'];
                $statusColor = ['unpaid'=>'#e74c3c','partial'=>'#3498db','paid'=>'#27ae60','waived'=>'#9b59b6','disputed'=>'#f39c12'];
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><strong><?php echo htmlspecialchars($p['nama']); ?></strong><br><small><?php echo $p['username']; ?></small></td>
                <td><?php echo htmlspecialchars($p['judul'] ?? '-'); ?></td>
                <td><?php echo ucfirst($p['tipe_denda']); ?></td>
                <td><strong>Rp <?php echo number_format($p['jumlah'],0,',','.'); ?></strong></td>
                <td>Rp <?php echo number_format($p['jumlah_dibayar'],0,',','.'); ?></td>
                <td><span style="padding:3px 8px;border-radius:4px;font-size:11px;color:white;background:<?php echo $statusColor[$p['status']]??'#888'; ?>"><?php echo $statusLabel[$p['status']]??$p['status']; ?></span></td>
                <td>
                    <?php if($p['status']=='unpaid'||$p['status']=='partial'): ?>
                    <a href="denda.php?aksi=mark_paid&id=<?php echo $p['id']; ?>" class="btn-sm btn-green" onclick="return confirm('Konfirmasi lunas?')"><i class="fa-solid fa-check"></i> Lunas</a>
                    <button class="btn-sm btn-blue" onclick="openPartial(<?php echo $p['id']; ?>,<?php echo $sisa; ?>)"><i class="fa-solid fa-coins"></i> Cicil</button>
                    <a href="denda.php?aksi=waive&id=<?php echo $p['id']; ?>" class="btn-sm btn-purple" onclick="return confirm('Hapuskan denda ini?')"><i class="fa-solid fa-eraser"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8" style="text-align:center;">Belum ada data denda.</td></tr>
            <?php endif; ?>
            </tbody></table></div>
        </div>

        <div id="tab-disputed" class="tab-content">
            <div class="table-container"><table><thead><tr><th>No</th><th>Anggota</th><th>Denda</th><th>Alasan Keberatan</th><th>Aksi</th></tr></thead><tbody>
            <?php $no=1; mysqli_data_seek($q_disputed,0); while($d=mysqli_fetch_assoc($q_disputed)): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><strong><?php echo htmlspecialchars($d['nama']); ?></strong></td>
                <td>Rp <?php echo number_format($d['jumlah'],0,',','.'); ?> (<?php echo ucfirst($d['tipe_denda']); ?>)</td>
                <td><em><?php echo htmlspecialchars($d['catatan_dispute'] ?? '-'); ?></em></td>
                <td>
                    <a href="denda.php?aksi=resolve_dispute&id=<?php echo $d['id']; ?>&resolve=accept" class="btn-sm btn-green" onclick="return confirm('Terima keberatan? Denda akan dihapuskan.')"><i class="fa-solid fa-check"></i> Terima</a>
                    <a href="denda.php?aksi=resolve_dispute&id=<?php echo $d['id']; ?>&resolve=reject" class="btn-sm btn-red" onclick="return confirm('Tolak keberatan?')"><i class="fa-solid fa-xmark"></i> Tolak</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody></table></div>
        </div>

        <div id="tab-add" class="tab-content">
            <div class="add-form">
                <h3 style="margin-bottom:15px;"><i class="fa-solid fa-plus-circle"></i> Tambah Denda Manual</h3>
                <form method="POST">
                    <div class="form-grid">
                        <div><label>Anggota</label><select name="id_user" required><option value="">-- Pilih Anggota --</option>
                        <?php mysqli_data_seek($q_users,0); while($u=mysqli_fetch_assoc($q_users)): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nama']); ?> (<?php echo $u['username']; ?>)</option>
                        <?php endwhile; ?></select></div>
                        <div><label>Buku (opsional)</label><select name="id_buku"><option value="">-- Pilih Buku --</option>
                        <?php mysqli_data_seek($q_books,0); while($b=mysqli_fetch_assoc($q_books)): ?>
                        <option value="<?php echo $b['id_buku']; ?>"><?php echo htmlspecialchars($b['judul']); ?></option>
                        <?php endwhile; ?></select></div>
                    </div>
                    <div class="form-grid">
                        <div><label>Tipe Denda</label><select name="tipe_denda" required><option value="overdue">Overdue (Keterlambatan)</option><option value="damage">Damage (Kerusakan)</option><option value="lost">Lost (Kehilangan)</option></select></div>
                        <div><label>Jumlah (Rp)</label><input type="number" name="jumlah" min="1000" step="1000" required placeholder="25000"></div>
                    </div>
                    <label>Catatan</label><textarea name="catatan" rows="2" placeholder="Keterangan denda..."></textarea>
                    <button type="submit" name="add_penalty" style="background:#B46932;color:white;padding:12px 20px;border:none;border-radius:8px;cursor:pointer;font-weight:600;"><i class="fa-solid fa-plus"></i> Tambah Denda</button>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-bg" id="partialModal">
        <div class="modal-box">
            <h3><i class="fa-solid fa-coins"></i> Pembayaran Cicilan</h3>
            <form method="POST">
                <input type="hidden" name="id_penalty" id="partialId">
                <p style="font-size:13px;color:#888;margin:10px 0;">Sisa: Rp <span id="partialSisa">0</span></p>
                <input type="number" name="jumlah_bayar" min="1000" step="1000" required placeholder="Jumlah bayar" style="padding:10px;border:1px solid #ddd;border-radius:6px;width:100%;margin-bottom:10px;">
                <div style="display:flex;gap:10px;">
                    <button type="submit" name="partial_pay" style="flex:1;background:#3498db;color:white;border:none;padding:10px;border-radius:8px;cursor:pointer;font-weight:600;">Bayar</button>
                    <button type="button" onclick="document.getElementById('partialModal').classList.remove('active')" style="flex:1;background:#eee;border:none;padding:10px;border-radius:8px;cursor:pointer;">Batal</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function showTab(t){document.querySelectorAll('.tab-content').forEach(e=>e.classList.remove('active'));document.querySelectorAll('.tab-btn').forEach(e=>e.classList.remove('active'));document.getElementById('tab-'+t).classList.add('active');event.target.closest('.tab-btn').classList.add('active')}
    function openPartial(id,sisa){document.getElementById('partialId').value=id;document.getElementById('partialSisa').textContent=new Intl.NumberFormat('id-ID').format(sisa);document.getElementById('partialModal').classList.add('active')}
    </script>
</body>
</html>