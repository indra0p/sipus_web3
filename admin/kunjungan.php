<?php
session_start();
include '../config/koneksi.php';

// Pastikan Zona Waktu disetel ke Asia/Jakarta agar singkron antara PHP, MySQL, dan Jam Nyata
date_default_timezone_set('Asia/Jakarta');
mysqli_query($koneksi, "SET time_zone = '+07:00'");

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { header("location: ../login/login.php"); exit; }
include 'notifikasi_helper.php';

// Manual check-in/out
if (isset($_POST['manual_scan'])) {
    $nim = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $tipe = $_POST['tipe_scan'];
    $q = mysqli_query($koneksi, "SELECT id, nama, username FROM users WHERE username = '$nim'");
    if (mysqli_num_rows($q) > 0) {
        $u = mysqli_fetch_assoc($q);
        $waktu = date('Y-m-d H:i:s');
        mysqli_query($koneksi, "INSERT INTO checkin_log (id_user, waktu_checkin, metode, tipe) VALUES ('{$u['id']}', '$waktu', 'manual', '$tipe')");
        $label = ($tipe == 'checkin') ? 'Check-in' : 'Check-out';
        createNotification($koneksi, $u['id'], "$label Berhasil", "$label perpustakaan tercatat pada " . date('d M Y H:i'), 'checkin');
        $msg = urlencode("$label berhasil untuk {$u['nama']} ({$u['username']})");
        header("location: kunjungan.php?pesan=berhasil&detail=$msg"); exit;
    } else {
        header("location: kunjungan.php?pesan=not_found"); exit;
    }
}

$tgl_hari_ini = date('Y-m-d');

// Today's visitors (checked in today)
$q_today = mysqli_query($koneksi, "SELECT cl.*, u.nama, u.username, u.role FROM checkin_log cl JOIN users u ON cl.id_user = u.id WHERE DATE(cl.waktu_checkin) = '$tgl_hari_ini' ORDER BY cl.waktu_checkin DESC");
$visitors_today = [];
while ($r = mysqli_fetch_assoc($q_today)) { $visitors_today[] = $r; }

// Current occupancy
$q_in = mysqli_query($koneksi, "
    SELECT cl.id_user, u.nama, u.username, u.role, cl.waktu_checkin as waktu_masuk 
    FROM checkin_log cl 
    JOIN users u ON cl.id_user = u.id 
    WHERE cl.id IN (
        SELECT MAX(id) 
        FROM checkin_log 
        WHERE DATE(waktu_checkin) = '$tgl_hari_ini' 
        GROUP BY id_user
    ) 
    AND cl.tipe = 'checkin'
    ORDER BY cl.waktu_checkin DESC
");

$current_visitors = [];
while ($r = mysqli_fetch_assoc($q_in)) { $current_visitors[] = $r; }
$occupancy = count($current_visitors);

// Count today check-ins and check-outs
$q_cin = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM checkin_log WHERE DATE(waktu_checkin) = '$tgl_hari_ini' AND tipe = 'checkin'");
$total_checkin = mysqli_fetch_assoc($q_cin)['t'] ?? 0;
$q_cout = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM checkin_log WHERE DATE(waktu_checkin) = '$tgl_hari_ini' AND tipe = 'checkout'");
$total_checkout = mysqli_fetch_assoc($q_cout)['t'] ?? 0;

// =========================================================================
// QUERY DATA GRAFIK: 30 HARI TERAKHIR (KHUSUS CHECK-IN)
// =========================================================================
$days = [];
$checkin_data = [];

for ($i = 29; $i >= 0; $i--) {
    $date_target = date('Y-m-d', strtotime("-$i days"));
    $days[] = date('d M', strtotime($date_target));
    
    $q_g_in = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM checkin_log WHERE DATE(waktu_checkin) = '$date_target' AND tipe = 'checkin'");
    $checkin_data[] = mysqli_fetch_assoc($q_g_in)['t'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Kunjungan - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-row { display:flex; gap:15px; margin-bottom:25px; }
        .stat-box { flex:1; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05); text-align:center; }
        .stat-box h2 { font-size:28px; margin-bottom:5px; }
        .stat-box p { font-size:12px; color:#888; }
        
        .chart-card { background:white; padding:20px; border-radius:12px; margin-bottom:25px; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        
        .scan-form { background:white; padding:25px; border-radius:12px; margin-bottom:25px; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        .scan-form input[type=text] { padding:12px; border:2px solid #ddd; border-radius:8px; font-size:14px; width:250px; margin-right:10px; }
        .scan-form input:focus { border-color:#B46932; outline:none; }
        .scan-form select { padding:12px; border:2px solid #ddd; border-radius:8px; margin-right:10px; }
        .btn-scan { padding:12px 20px; background:#B46932; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
        .btn-scan:hover { background:#8E5124; }
        .occupancy-badge { display:inline-block; padding:6px 14px; border-radius:20px; font-weight:700; font-size:13px; }
        .occ-green { background:#dcfce7; color:#16a34a; }
        .occ-yellow { background:#fef3c7; color:#d97706; }
        .occ-red { background:#fee2e2; color:#dc2626; }
        .alert-msg { padding:12px; border-radius:8px; margin-bottom:15px; }
        .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
        .tab-container { margin-bottom:15px; }
        .tab-btn { padding:10px 20px; border:none; cursor:pointer; font-size:13px; border-radius:5px 5px 0 0; background:#eee; color:#555; margin-right:2px; }
        .tab-btn.active { background:#B46932; color:white; font-weight:bold; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
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
            <a href="kunjungan.php" class="active"><i class="fa-solid fa-door-open"></i> Kunjungan</a>
            <a href="denda.php"><i class="fa-solid fa-coins"></i> Denda</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Logout?')"><i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header><h1>Manajemen Kunjungan</h1><p>Check-in pengunjung dan monitoring real-time</p></header>

        <?php if(isset($_GET['pesan'])):
            if($_GET['pesan'] == 'berhasil') echo "<div class='alert-msg alert-success'><i class='fa-solid fa-check-circle'></i> " . htmlspecialchars(urldecode($_GET['detail'] ?? 'Berhasil')) . "</div>";
            if($_GET['pesan'] == 'not_found') echo "<div class='alert-msg alert-error'><i class='fa-solid fa-xmark'></i> NIM tidak ditemukan dalam sistem.</div>";
        endif; ?>

        <div class="stats-row">
            <div class="stat-box">
                <h2 style="color:#B46932;"><?php echo $occupancy; ?></h2>
                <p>Pengunjung Saat Ini</p>
                <span class="occupancy-badge <?php echo $occupancy > 50 ? 'occ-red' : ($occupancy > 20 ? 'occ-yellow' : 'occ-green'); ?>">
                    <?php echo $occupancy > 50 ? 'Ramai' : ($occupancy > 20 ? 'Sedang' : 'Sepi'); ?>
                </span>
            </div>
            <div class="stat-box"><h2 style="color:#27ae60;"><?php echo $total_checkin; ?></h2><p>Check-in Hari Ini</p></div>
            <div class="stat-box"><h2 style="color:#e74c3c;"><?php echo $total_checkout; ?></h2><p>Check-out Hari Ini</p></div>
        </div>

        <div class="chart-card">
            <h3 style="margin-bottom:15px; color:#333;"><i class="fa-solid fa-chart-line"></i> Grafik Kunjungan (Check-in) 30 Hari Terakhir</h3>
            <div style="width: 100%; height: 300px;">
                <canvas id="monthlyCheckinChart"></canvas>
            </div>
        </div>

        <div class="scan-form">
            <h3 style="margin-bottom:15px;"><i class="fa-solid fa-qrcode"></i> Manual Check-in / Check-out</h3>
            <form method="POST" style="display:flex; align-items:center; flex-wrap:wrap; gap:10px;">
                <input type="text" name="nim" placeholder="Masukkan NIM / Scan Barcode" required autofocus>
                <select name="tipe_scan">
                    <option value="checkin">Check-in (Masuk)</option>
                    <option value="checkout">Check-out (Keluar)</option>
                </select>
                <button type="submit" name="manual_scan" class="btn-scan"><i class="fa-solid fa-arrow-right-to-bracket"></i> Proses</button>
            </form>
        </div>

        <div class="tab-container">
            <button class="tab-btn active" onclick="showTab('current')"><i class="fa-solid fa-users"></i> Sedang di Perpustakaan (<?php echo $occupancy; ?>)</button>
            <button class="tab-btn" onclick="showTab('today')"><i class="fa-solid fa-calendar-day"></i> Log Hari Ini (<?php echo count($visitors_today); ?>)</button>
        </div>

        <div id="tab-current" class="tab-content active">
            <div class="table-container"><table><thead><tr><th>No</th><th>Nama</th><th>NIM</th><th>Role</th><th>Waktu Masuk</th><th>Durasi</th></tr></thead><tbody>
            <?php if (count($current_visitors) > 0): $no=1; foreach ($current_visitors as $cv): ?>
            <tr class="pengunjung-row" data-masuk="<?php echo $cv['waktu_masuk']; ?>">
                <td><?php echo $no++; ?></td>
                <td><strong><?php echo htmlspecialchars($cv['nama']); ?></strong></td>
                <td><?php echo $cv['username']; ?></td>
                <td><?php echo ucfirst($cv['role'] == 'user' ? 'mahasiswa' : $cv['role']); ?></td>
                <td><i class="fa-regular fa-clock" style="color: #888;"></i> <?php echo date('H:i', strtotime($cv['waktu_masuk'])); ?> WIB</td>
                <td class="durasi-live">Menghitung...</td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center;">Tidak ada pengunjung saat ini.</td></tr>
            <?php endif; ?>
            </tbody></table></div>
        </div>

        <div id="tab-today" class="tab-content">
            <div class="table-container"><table><thead><tr><th>No</th><th>Nama</th><th>NIM</th><th>Tipe</th><th>Waktu</th><th>Metode</th></tr></thead><tbody>
            <?php if (count($visitors_today) > 0): $no=1; foreach ($visitors_today as $vt): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($vt['nama']); ?></td>
                <td><?php echo $vt['username']; ?></td>
                <td><span style="padding:3px 8px; border-radius:4px; font-size:11px; color:white; background:<?php echo $vt['tipe']=='checkin'?'#27ae60':'#e74c3c'; ?>"><?php echo ucfirst($vt['tipe']); ?></span></td>
                <td><?php echo date('H:i:s', strtotime($vt['waktu_checkin'])); ?> WIB</td>
                <td><?php echo ucfirst($vt['metode']); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center;">Belum ada kunjungan hari ini.</td></tr>
            <?php endif; ?>
            </tbody></table></div>
        </div>
    </div>

    <script>
    function showTab(tab) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        event.target.closest('.tab-btn').classList.add('active');
    }

    function updateLiveDurations() {
        const sekarang = new Date();
        const barisPengunjung = document.querySelectorAll('.pengunjung-row');
        barisPengunjung.forEach(row => {
            const stringWaktuMasuk = row.getAttribute('data-masuk');
            if (!stringWaktuMasuk) return;
            const waktuMasuk = new Date(stringWaktuMasuk.replace(' ', 'T'));
            const selisihMilidetik = sekarang - waktuMasuk;
            if (selisihMilidetik < 0) { row.querySelector('.durasi-live').innerText = '0 jam 0 menit'; return; }
            const totalMenit = Math.floor(selisihMilidetik / (1000 * 60));
            const jam = Math.floor(totalMenit / 60);
            const sisaMenit = totalMenit % 60;
            row.querySelector('.durasi-live').innerText = (jam > 0 ? jam + ' jam ' : '') + sisaMenit + ' menit';
        });
    }

    const ctx = document.getElementById('monthlyCheckinChart').getContext('2d');
    const monthlyCheckinChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($days); ?>,
            datasets: [{
                label: 'Jumlah Kunjungan (Check-in)',
                data: <?php echo json_encode($checkin_data); ?>,
                borderColor: '#B46932',
                backgroundColor: 'rgba(180, 105, 50, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        updateLiveDurations();
        setInterval(updateLiveDurations, 1000);
    });
    </script>
</body>
</html>