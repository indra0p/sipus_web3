<?php
session_start();
include '../config/koneksi.php';

// Pastikan Zona Waktu disetel ke Asia/Jakarta agar singkron antara PHP, MySQL, dan Jam Nyata
date_default_timezone_set('Asia/Jakarta');
mysqli_query($koneksi, "SET time_zone = '+07:00'");

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") { 
    header("location: ../login/login.php"); 
    exit; 
}

if (file_exists('notifikasi_helper.php')) {
    include 'notifikasi_helper.php';
}

$tgl_hari_ini = date('Y-m-d');

// ==========================================
// 1. DATA STATISTIK UTAMA
// ==========================================

// Menghitung pengunjung yang sedang di dalam perpus berdasarkan log status TERAKHIR (MAX id)
$q_occupancy = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM checkin_log cl 
    WHERE cl.id IN (
        SELECT MAX(id) 
        FROM checkin_log 
        WHERE DATE(waktu_checkin) = '$tgl_hari_ini' 
        GROUP BY id_user
    ) 
    AND cl.tipe = 'checkin'
");
$occupancy = mysqli_fetch_assoc($q_occupancy)['total'] ?? 0;

// Total Check-in hari ini
$q_cin = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM checkin_log WHERE DATE(waktu_checkin) = '$tgl_hari_ini' AND tipe = 'checkin'");
$total_checkin = mysqli_fetch_assoc($q_cin)['t'] ?? 0;

// Total Buku yang sedang dipinjam saat ini
$q_borrowed = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE status = 'Dipinjam'");
$total_borrowed = mysqli_fetch_assoc($q_borrowed)['t'] ?? 0;


// ==========================================
// 2. DATA UNTUK GRAFIK KUNJUNGAN (30 HARI TERAKHIR)
// ==========================================
$grafik_label = [];
$grafik_data = [];
for ($i = 29; $i >= 0; $i--) {
    $tgl_target = date('Y-m-d', strtotime("-$i days"));
    $label_target = date('d M', strtotime($tgl_target));
    
    // Hitung jumlah checkin pada tanggal tersebut
    $q_grafik = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM checkin_log WHERE DATE(waktu_checkin) = '$tgl_target' AND tipe = 'checkin'");
    $h_grafik = mysqli_fetch_assoc($q_grafik);
    
    $grafik_label[] = $label_target;
    $grafik_data[] = $h_grafik['total'] ?? 0;
}


// ==========================================
// 3. DAFTAR ANGGOTA DI DALAM PERPUSTAKAAN
// ==========================================
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


// ==========================================
// 4. TOP PENGUNJUNG SERING KE PERPUS
// ==========================================
$q_frequent_visitors = mysqli_query($koneksi, "SELECT u.nama, u.username, u.role, COUNT(cl.id) as total_kunjungan 
    FROM checkin_log cl 
    JOIN users u ON cl.id_user = u.id 
    WHERE cl.tipe = 'checkin'
    GROUP BY cl.id_user 
    ORDER BY total_kunjungan DESC 
    LIMIT 10");


// ==========================================
// 5. TOP ANGGOTA SERING PINJAM BUKU
// ==========================================
$q_frequent_borrowers = mysqli_query($koneksi, "SELECT u.nama, u.username, u.role, COUNT(p.id_peminjaman) as total_pinjam 
    FROM peminjaman p 
    JOIN users u ON p.id_user = u.id 
    GROUP BY p.id_user 
    ORDER BY total_pinjam DESC 
    LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Pengunjung - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-row { display: flex; gap: 20px; margin-bottom: 25px; }
        .stat-box { flex: 1; background: white; padding: 22px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.04); border-left: 5px solid #B46932; text-align: center; }
        .stat-box.inside { border-left-color: #3498db; }
        .stat-box.today { border-left-color: #27ae60; }
        .stat-box.books { border-left-color: #e67e22; }
        .stat-box h2 { font-size: 32px; margin: 0 0 5px 0; font-weight: 700; }
        .stat-box p { font-size: 13px; color: #777; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .grid-statistik { display: grid; grid-template-columns: 1fr; gap: 25px; margin-bottom: 30px; }
        @media (min-width: 992px) { .grid-statistik { grid-template-columns: 1fr 1fr; } .full-width { grid-column: span 2; } }

        .content-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.04); }
        .content-title { margin-top: 0; margin-bottom: 18px; font-size: 16px; color: #333; font-weight: 600; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #eef2f5; padding-bottom: 10px; }
        
        .occupancy-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; margin-top: 5px; }
        .occ-green { background: #dcfce7; color: #16a34a; }
        .occ-yellow { background: #fef3c7; color: #d97706; }
        .occ-red { background: #fee2e2; color: #dc2626; }
        
        .rank-number { font-weight: 700; color: #B46932; background: #fdf5f0; padding: 4px 10px; border-radius: 50%; font-size: 13px; }
        
        .chart-container { position: relative; height: 280px; width: 100%; }
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
            <a href="statistik_pengunjung.php" class="active"><i class="fa-solid fa-chart-line"></i> Statistika Pengunjung</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Yakin ingin keluar?')"><i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <div class="header-title">
                <h1>Statistik &amp; Analisis Aktivitas Perpustakaan</h1>
                <p>Monitoring data real-time, tren kunjungan bulanan, serta performa sirkulasi buku</p>
            </div>
        </header>

        <div class="stats-row">
            <div class="stat-box inside">
                <h2 style="color:#3498db;"><?php echo $occupancy; ?></h2>
                <p>Sedang Di Dalam Perpus</p>
                <span class="occupancy-badge <?php echo $occupancy > 30 ? 'occ-red' : ($occupancy > 10 ? 'occ-yellow' : 'occ-green'); ?>">
                    <?php echo $occupancy > 30 ? 'Kondisi Ramai' : ($occupancy > 10 ? 'Kondisi Sedang' : 'Kondisi Sepi'); ?>
                </span>
            </div>
            <div class="stat-box today">
                <h2 style="color:#27ae60;"><?php echo $total_checkin; ?></h2>
                <p>Total Check-In Hari Ini</p>
            </div>
            <div class="stat-box books">
                <h2 style="color:#e67e22;"><?php echo $total_borrowed; ?></h2>
                <p>Buku Sedang Dipinjam</p>
            </div>
        </div>

        <div class="grid-statistik">
            
            <div class="content-box full-width">
                <div class="content-title">
                    <i class="fa-solid fa-chart-area" style="color: #27ae60;"></i> Tren Grafik Kunjungan Bulanan (30 Hari Terakhir)
                </div>
                <div class="chart-container">
                    <canvas id="kunjunganChart"></canvas>
                </div>
            </div>
            
            <div class="content-box full-width">
                <div class="content-title">
                    <i class="fa-solid fa-door-open" style="color: #3498db;"></i> Anggota yang Saat Ini Masih Berada di Ruangan
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Nama Lengkap</th>
                                <th>NIM / Username</th>
                                <th>Status / Role</th>
                                <th>Jam Masuk</th>
                                <th>Durasi Menetap</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($current_visitors) > 0): $no=1; foreach ($current_visitors as $cv): ?>
                            <tr class="pengunjung-row" data-masuk="<?php echo $cv['waktu_masuk']; ?>">
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($cv['nama']); ?></strong></td>
                                <td><code><?php echo $cv['username']; ?></code></td>
                                <td><span class="badge"><?php echo ucfirst($cv['role'] == 'user' ? 'mahasiswa' : $cv['role']); ?></span></td>
                                <td><i class="fa-regular fa-clock"></i> <?php echo date('H:i', strtotime($cv['waktu_masuk'])); ?> WIB</td>
                                <td class="durasi-live" style="font-weight: 500; color:#3498db;">Menghitung...</td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:20px; color:#999;">Tidak ada anggota di dalam perpustakaan saat ini.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="content-box">
                <div class="content-title">
                    <i class="fa-solid fa-crown" style="color: #f1c40f;"></i> 10 Anggota Paling Sering Berkunjung (Top Kunjungan)
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="10%">Rank</th>
                                <th>Nama / NIM</th>
                                <th>Role</th>
                                <th style="text-align: center;">Frekuensi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $rank=1;
                        if($q_frequent_visitors && mysqli_num_rows($q_frequent_visitors) > 0):
                            while($fv = mysqli_fetch_assoc($q_frequent_visitors)):
                        ?>
                            <tr>
                                <td style="text-align: center;"><span class="rank-number"><?php echo $rank++; ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($fv['nama']); ?></strong><br>
                                    <small style="color:#777;"><?php echo $fv['username']; ?></small>
                                </td>
                                <td><?php echo ucfirst($fv['role'] == 'user' ? 'mahasiswa' : $fv['role']); ?></td>
                                <td style="text-align: center;"><strong style="color:#27ae60; font-size:15px;"><?php echo $fv['total_kunjungan']; ?></strong> Kali</td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <tr><td colspan="4" style="text-align:center; padding:15px; color:#888;">Belum ada log aktivitas check-in.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="content-box">
                <div class="content-title">
                    <i class="fa-solid fa-book-reader" style="color: #e67e22;"></i> 10 Anggota Paling Sering Pinjam Buku (Top Borrowers)
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="10%">Rank</th>
                                <th>Nama / NIM</th>
                                <th>Role</th>
                                <th style="text-align: center;">Total Pinjam</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $rank_b=1;
                        if($q_frequent_borrowers && mysqli_num_rows($q_frequent_borrowers) > 0):
                            while($fb = mysqli_fetch_assoc($q_frequent_borrowers)):
                        ?>
                            <tr>
                                <td style="text-align: center;"><span class="rank-number" style="background:#fdf6f0; color:#e67e22;"><?php echo $rank_b++; ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($fb['nama']); ?></strong><br>
                                    <small style="color:#777;"><?php echo $fb['username']; ?></small>
                                </td>
                                <td><?php echo ucfirst($fb['role'] == 'user' ? 'mahasiswa' : $fb['role']); ?></td>
                                <td style="text-align: center;"><strong style="color:#e67e22; font-size:15px;"><?php echo $fb['total_pinjam']; ?></strong> Buku</td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <tr><td colspan="4" style="text-align:center; padding:15px; color:#888;">Belum ada riwayat transaksi peminjaman.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        function updateLiveDurations() {
            const sekarang = new Date();
            const barisPengunjung = document.querySelectorAll('.pengunjung-row');

            barisPengunjung.forEach(row => {
                const stringWaktuMasuk = row.getAttribute('data-masuk');
                if (!stringWaktuMasuk) return;

                const waktuMasuk = new Date(stringWaktuMasuk.replace(' ', 'T'));
                const selisihMilidetik = sekarang - waktuMasuk;

                if (selisihMilidetik < 0) {
                    row.querySelector('.durasi-live').innerText = '0 menit';
                    return;
                }

                const totalMenit = Math.floor(selisihMilidetik / (1000 * 60));
                const jam = Math.floor(totalMenit / 60);
                const sisaMenit = totalMenit % 60;

                let outputTeks = '';
                if (jam > 0) { outputTeks += jam + ' jam '; }
                outputTeks += sisaMenit + ' menit';

                row.querySelector('.durasi-live').innerText = outputTeks;
            });
        }

        const ctx = document.getElementById('kunjunganChart').getContext('2d');
        const labelsHari = <?php echo json_encode($grafik_label); ?>;
        const dataKunjungan = <?php echo json_encode($grafik_data); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labelsHari,
                datasets: [{
                    label: 'Jumlah Kunjungan (Orang)',
                    data: dataKunjungan,
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    borderColor: 'rgba(39, 174, 96, 1)',
                    borderWidth: 3,
                    pointBackgroundColor: 'rgba(39, 174, 96, 1)',
                    pointRadius: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            updateLiveDurations();
            setInterval(updateLiveDurations, 1000);
        });
    </script>
</body>
</html>