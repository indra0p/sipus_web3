<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php");
    exit;
}

// =========================================================================
// AMBIL ATURAN DENDA DARI DATABASE (Tabel: penalty_rules, Tipe: overdue)
// =========================================================================
$tarif_denda = 0;
$is_rules_active = false;

$q_rules = mysqli_query($koneksi, "SELECT * FROM penalty_rules WHERE tipe_denda = 'overdue' LIMIT 1");
if ($q_rules && mysqli_num_rows($q_rules) > 0) {
    $rule = mysqli_fetch_assoc($q_rules);
    // Cek kolom is_active (1 = aktif, 0 = nonaktif)
    if (isset($rule['is_active']) && $rule['is_active'] == 1) {
        $is_rules_active = true;
        $tarif_denda = (int)$rule['tarif']; // Nominal tarif dinamis (misal: 3000)
    }
} else {
    // Fallback jika aturan belum dibuat di database (Default: Aktif, 2000)
    $is_rules_active = true;
    $tarif_denda = 2000;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Peminjaman - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .denda-telat { color: #d9534f; font-weight: bold; }
        .denda-lunas { color: #27ae60; font-weight: bold; }
        .status-dipinjam { background: #e67e22; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .status-selesai { background: #27ae60; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .status-menunggu { background: #3498db; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .status-ditolak { background: #e74c3c; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .status-pengajuan { background: #9b59b6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; font-size: 13px; }
        .btn-approve { background: #27ae60; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; display: inline-block; margin: 2px; }
        .btn-approve:hover { background: #219150; }
        .btn-reject { background: #e74c3c; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; display: inline-block; margin: 2px; }
        .btn-reject:hover { background: #c0392b; }
        .tab-container { margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; border: none; cursor: pointer; font-size: 13px; border-radius: 5px 5px 0 0; background: #eee; color: #555; margin-right: 2px; }
        .tab-btn.active { background: #B46932; color: white; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .badge-count { background: #e74c3c; color: white; padding: 2px 6px; border-radius: 50%; font-size: 10px; margin-left: 5px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i> <span>SIPUS POLSA</span></div>
        <nav>
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php"><i class="fa-solid fa-users"></i> Kelola Anggota</a>
            <a href="buku.php"><i class="fa-solid fa-book"></i> Stok Buku</a>
            <a href="peminjaman.php" class="active"><i class="fa-solid fa-right-left"></i> Peminjaman</a>
            <a href="pengembalian.php"><i class="fa-solid fa-rotate-left"></i> Pengembalian</a>
            <a href="kunjungan.php"><i class="fa-solid fa-door-open"></i> Kunjungan</a>
            <a href="denda.php"><i class="fa-solid fa-coins"></i> Denda</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Apakah anda yakin ingin keluar?')">
            <i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <div class="header-title">
                <h1>Data Peminjaman</h1>
                <p>
                    Manajemen peminjaman, approval, dan 
                    <strong>
                        <?php echo $is_rules_active ? "Kalkulasi Denda Aktif: Rp " . number_format($tarif_denda, 0, ',', '.') . "/hari" : "Aturan Denda Keterlambatan: NONAKTIF"; ?>
                    </strong>
                </p>
            </div>
            <a href="peminjaman_tambah.php" class="btn-add"><i class="fa-solid fa-plus"></i> Pinjam Buku Baru</a>
        </header>

        <?php if (!$is_rules_active): ?>
            <div class="alert alert-info">
                <i class="fa-solid fa-circle-info"></i> <strong>Informasi Akademik:</strong> Aturan denda keterlambatan saat ini dinonaktifkan. Seluruh keterlambatan peminjaman berjalan dihitung Rp 0.
            </div>
        <?php endif; ?>

        <?php 
        if(isset($_GET['pesan'])){
            $pesan_map = [
                'berhasil' => 'Peminjaman berhasil dicatat!',
                'hapus_berhasil' => 'Riwayat selesai berhasil dibersihkan!',
                'kembali_berhasil' => 'Buku telah dikembalikan dan denda dilunasi!',
                'approve_berhasil' => 'Peminjaman berhasil disetujui!',
                'reject_berhasil' => 'Peminjaman berhasil ditolak.',
            ];
            $pesan_text = $pesan_map[$_GET['pesan']] ?? '';
            if (!empty($pesan_text)) {
                echo "<div class='alert alert-success'>$pesan_text</div>";
            }
        }
        ?>

        <?php
        // Count pending approvals
        $q_pending = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Menunggu'");
        $pending_count = mysqli_fetch_assoc($q_pending)['total'] ?? 0;
        ?>

        <div class="tab-container">
            <button class="tab-btn active" onclick="showTab('pending')">
                <i class="fa-solid fa-clock"></i> Menunggu Approval
                <?php if($pending_count > 0): ?>
                    <span class="badge-count"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-btn" onclick="showTab('all')">
                <i class="fa-solid fa-list"></i> Semua Peminjaman
            </button>
        </div>

        <div id="tab-pending" class="tab-content active">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="1%">No</th>
                            <th>Pemohon</th>
                            <th>NIM</th>
                            <th>Judul Buku</th>
                            <th>Tgl Pengajuan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $query_pending = "SELECT peminjaman.*, users.nama, users.username as nim, buku.judul 
                                          FROM peminjaman 
                                          JOIN users ON peminjaman.id_user = users.id 
                                          JOIN buku ON peminjaman.id_buku = buku.id_buku 
                                          WHERE peminjaman.status = 'Menunggu'
                                          ORDER BY id_peminjaman DESC";
                        $data_pending = mysqli_query($koneksi, $query_pending);

                        if ($data_pending && mysqli_num_rows($data_pending) > 0) {
                            while($d = mysqli_fetch_array($data_pending)){
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo $d['nama']; ?></strong></td>
                                    <td><?php echo $d['nim']; ?></td>
                                    <td><?php echo $d['judul']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($d['tgl_pinjam'])); ?></td>
                                    <td><span class="status-menunggu"><i class="fa-solid fa-clock"></i> Menunggu</span></td>
                                    <td>
                                        <a href="peminjaman_aksi.php?aksi=approve&id=<?php echo $d['id_peminjaman']; ?>" 
                                           class="btn-approve"
                                           onclick="return confirm('Setujui peminjaman buku ini?')">
                                            <i class="fa-solid fa-check"></i> Setujui
                                        </a>
                                        <a href="peminjaman_aksi.php?aksi=reject&id=<?php echo $d['id_peminjaman']; ?>" 
                                           class="btn-reject"
                                           onclick="return confirm('Tolak peminjaman buku ini?')">
                                            <i class="fa-solid fa-xmark"></i> Tolak
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center;'>Tidak ada pengajuan yang menunggu approval.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-all" class="tab-content">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="1%">No</th>
                            <th>Nama Peminjam</th>
                            <th>Judul Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Batas Kembali</th>
                            <th>Denda (Rp)</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $tgl_sekarang = date('Y-m-d');
                        $query = "SELECT peminjaman.*, users.nama, buku.judul 
                                  FROM peminjaman 
                                  JOIN users ON peminjaman.id_user = users.id 
                                  JOIN buku ON peminjaman.id_buku = buku.id_buku 
                                  ORDER BY id_peminjaman DESC";
                        
                        $data = mysqli_query($koneksi, $query);

                        if ($data && mysqli_num_rows($data) > 0) {
                            while($d = mysqli_fetch_array($data)){
                                $nominal_denda = 0;
                                $class_denda = "";
                                $label_lunas = "";

                                if($d['status'] == "Dipinjam") {
                                    // Hitung denda real-time HANYA jika rules status dinilai AKTIF (1)
                                    if ($is_rules_active) {
                                        $tgl_batas = new DateTime($d['tgl_kembali_seharusnya']);
                                        $tgl_skrg = new DateTime($tgl_sekarang);
                                        if($tgl_skrg > $tgl_batas) {
                                            $selisih = $tgl_skrg->diff($tgl_batas)->days;
                                            // Menggunakan $tarif_denda dari database secara dinamis
                                            $nominal_denda = $selisih * $tarif_denda;
                                            $class_denda = "class='denda-telat'";
                                        }
                                    } else {
                                        // Jika denda dimatikan, nominal denda keterlambatan berjalan di-force ke 0
                                        $nominal_denda = 0;
                                    }
                                } elseif($d['status'] == "Kembali") {
                                    // Untuk status selesai, tetap ambil rekaman historis dari field denda
                                    $nominal_denda = $d['denda']; 
                                    if($nominal_denda > 0) {
                                        $class_denda = "class='denda-lunas'";
                                        $label_lunas = " <small>(Lunas)</small>";
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo $d['nama']; ?></strong></td>
                                    <td><?php echo $d['judul']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($d['tgl_pinjam'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($d['tgl_kembali_seharusnya'])); ?></td>
                                    <td <?php echo $class_denda; ?>>
                                        Rp <?php echo number_format($nominal_denda, 0, ',', '.'); ?>
                                        <?php echo $label_lunas; ?>
                                    </td>
                                    <td>
                                        <?php if($d['status'] == "Menunggu"): ?>
                                            <span class="status-menunggu"><i class="fa-solid fa-clock"></i> Menunggu</span>
                                        <?php elseif($d['status'] == "Dipinjam"): ?>
                                            <span class="status-dipinjam">Dipinjam</span>
                                        <?php elseif($d['status'] == "Ditolak"): ?>
                                            <span class="status-ditolak"><i class="fa-solid fa-xmark"></i> Ditolak</span>
                                        <?php elseif($d['status'] == "Pengajuan_Kembali"): ?>
                                            <span class="status-pengajuan"><i class="fa-solid fa-rotate-left"></i> Pengajuan Kembali</span>
                                        <?php else: ?>
                                            <span class="status-selesai">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td> 
                                        <a href="peminjaman_cetak.php?id=<?php echo $d['id_peminjaman']; ?>" 
                                           target="_blank" 
                                           class="btn-edit" 
                                           style="background: #a38f85; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 11px;">
                                           <i class="fa-solid fa-print"></i> Cetak
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align:center;'>Belum ada data peminjaman.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <?php if ($data && mysqli_num_rows($data) > 0) { ?>
                <div style="margin-top: 30px; text-align: right;">
                    <p style="font-size: 11px; color: #888; margin-bottom: 5px;">*Data yang masih 'Dipinjam' or 'Menunggu' tidak akan terhapus demi keamanan laporan.</p>
                    <a href="peminjaman_aksi.php?aksi=hapus_semua" 
                       style="background: #d9534f; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-size: 13px; font-weight: bold;"
                       onclick="return confirm('Hapus semua riwayat yang sudah SELESAI? Tindakan ini permanen.')">
                        <i class="fa-solid fa-broom"></i> Bersihkan Riwayat Selesai
                    </a>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.closest('.tab-btn').classList.add('active');
        }
    </script>
</body>
</html>