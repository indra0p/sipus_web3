<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status'])) { 
    header("location: ../login/login.php"); 
    exit;
}

// ==========================================
// AMBIL KONFIGURASI DENDA DARI DATABASE
// ==========================================
$query_rule = mysqli_query($koneksi, "SELECT tarif, is_active FROM penalty_rules LIMIT 1");
$rule = mysqli_fetch_assoc($query_rule);

// Jika master rule ditemukan, ambil datanya. Jika tidak, set default.
$status_rule = isset($rule['is_active']) ? $rule['is_active'] : 1; 
$tarif_denda = isset($rule['tarif']) ? $rule['tarif'] : 2000; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengembalian Buku - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-size: 13px;
            display: inline-block;
            margin: 2px;
            transition: 0.3s;
        }
        .btn-selesai { background: #27ae60; }
        .btn-selesai:hover { background: #219150; }
        .btn-perpanjang { background: #3498db; }
        .btn-perpanjang:hover { background: #2980b9; }
        .btn-approve { background: #27ae60; }
        .btn-approve:hover { background: #219150; }
        .btn-reject { background: #e74c3c; }
        .btn-reject:hover { background: #c0392b; }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .tab-container { margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; border: none; cursor: pointer; font-size: 13px; border-radius: 5px 5px 0 0; background: #eee; color: #555; margin-right: 2px; }
        .tab-btn.active { background: #B46932; color: white; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .badge-count { background: #e74c3c; color: white; padding: 2px 6px; border-radius: 50%; font-size: 10px; margin-left: 5px; }
        .status-pengajuan { background: #9b59b6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
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
            <a href="pengembalian.php" class="active"><i class="fa-solid fa-rotate-left"></i> Pengembalian</a>
            <a href="kunjungan.php"><i class="fa-solid fa-door-open"></i> Kunjungan</a>
            <a href="denda.php"><i class="fa-solid fa-coins"></i> Denda</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Apakah anda yakin ingin keluar?')">
            <i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <div class="header-title">
                <h1>Pengembalian & Perpanjangan</h1>
                <p>Kelola masa pinjam buku, approval pengembalian, dan hitung denda otomatis secara dinamis</p>
                <p>*syarat perpanjangan wajib membawa buku ke meja pelayanan ketika sudah jatuh tempo</p>
            </div>
        </header>

        <?php if(isset($_GET['pesan'])): ?>
            <div class="alert-success">
                <i class="fa-solid fa-check-circle"></i> 
                <?php 
                    if($_GET['pesan'] == 'perpanjang_berhasil') echo "Masa pinjam berhasil diperpanjang!";
                    if($_GET['pesan'] == 'kembali_berhasil') echo "Buku telah berhasil dikembalikan!";
                    if($_GET['pesan'] == 'approve_kembali_berhasil') echo "Pengajuan pengembalian disetujui!";
                    if($_GET['pesan'] == 'reject_kembali_berhasil') echo "Pengajuan pengembalian ditolak. Buku masih dipinjam.";
                ?>
            </div>
        <?php endif; ?>

        <?php
        // Count pending return requests
        $q_pending_return = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Pengajuan_Kembali'");
        $pending_return_count = mysqli_fetch_assoc($q_pending_return)['total'] ?? 0;
        ?>
        <?php
        // Count pending perpanjangan
        $q_pending_extend = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman WHERE perpanjangan_status = 'requested'");
        $pending_extend_count = mysqli_fetch_assoc($q_pending_extend)['total'] ?? 0;
        ?>

        <div class="tab-container">
            <button class="tab-btn active" onclick="showTab('pending')">
                <i class="fa-solid fa-clock"></i> Pengajuan Pengembalian
                <?php if($pending_return_count > 0): ?>
                    <span class="badge-count"><?php echo $pending_return_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-btn" onclick="showTab('extend')">
                <i class="fa-solid fa-calendar-plus"></i> Perpanjangan
                <?php if($pending_extend_count > 0): ?>
                    <span class="badge-count"><?php echo $pending_extend_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-btn" onclick="showTab('active')">
                <i class="fa-solid fa-book-open-reader"></i> Buku Dipinjam
            </button>
        </div>

        <div id="tab-pending" class="tab-content active">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="1%">No</th>
                            <th>Peminjam</th>
                            <th>Judul Buku</th>
                            <th>Batas Kembali</th>
                            <th>Terlambat</th>
                            <th>Denda (Rp)</th>
                            <th>Status</th>
                            <th width="200px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $tgl_sekarang = date('Y-m-d');
                        
                        $query_return = "SELECT peminjaman.*, users.nama, buku.judul 
                                         FROM peminjaman 
                                         JOIN users ON peminjaman.id_user = users.id 
                                         JOIN buku ON peminjaman.id_buku = buku.id_buku 
                                         WHERE peminjaman.status = 'Pengajuan_Kembali'
                                         ORDER BY id_peminjaman DESC";
                        $data_return = mysqli_query($koneksi, $query_return);

                        if ($data_return && mysqli_num_rows($data_return) > 0) {
                            while($d = mysqli_fetch_array($data_return)){
                                $batas_kembali = $d['tgl_kembali_seharusnya'];
                                $tgl1 = new DateTime($batas_kembali);
                                $tgl2 = new DateTime($tgl_sekarang);
                                
                                $denda = 0;
                                $selisih_hari = 0;

                                if ($tgl2 > $tgl1) {
                                    $diff = $tgl2->diff($tgl1);
                                    $selisih_hari = $diff->days;
                                    
                                    // Hitung denda secara dinamis berdasarkan konfigurasi database
                                    if ($status_rule == 1) {
                                        $denda = $selisih_hari * $tarif_denda;
                                    } else {
                                        $denda = 0;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($d['nama']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($d['judul']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($batas_kembali)); ?></td>
                                    <td>
                                        <?php 
                                        if($selisih_hari > 0) {
                                            echo "<span style='color: #d9534f; font-weight: bold;'>$selisih_hari Hari</span>";
                                        } else {
                                            echo "<span style='color: #27ae60;'>Tepat Waktu</span>";
                                        }
                                        ?>
                                    </td>
                                    <td><strong><?php echo number_format($denda, 0, ',', '.'); ?></strong></td>
                                    <td><span class="status-pengajuan"><i class="fa-solid fa-rotate-left"></i> Pengajuan</span></td>
                                    <td>
                                        <a href="pengembalian_aksi.php?aksi=approve_kembali&id=<?php echo $d['id_peminjaman']; ?>&buku=<?php echo $d['id_buku']; ?>&denda=<?php echo $denda; ?>" 
                                           class="btn-action btn-approve"
                                           onclick="return confirm('Setujui pengembalian buku ini? Denda: Rp <?php echo number_format($denda, 0, ',', '.'); ?>')">
                                            <i class="fa-solid fa-check"></i> Setujui
                                        </a>
                                        <a href="pengembalian_aksi.php?aksi=reject_kembali&id=<?php echo $d['id_peminjaman']; ?>" 
                                           class="btn-action btn-reject"
                                           onclick="return confirm('Tolak pengajuan pengembalian? Buku akan tetap berstatus dipinjam.')">
                                            <i class="fa-solid fa-xmark"></i> Tolak
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align:center;'>Tidak ada pengajuan pengembalian.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-extend" class="tab-content">
            <div class="table-container">
                <table>
                    <thead><tr><th>No</th><th>Peminjam</th><th>Judul Buku</th><th>Batas Kembali</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php
                    $no = 1;
                    $q_ext = mysqli_query($koneksi, "SELECT p.*, u.nama, b.judul FROM peminjaman p JOIN users u ON p.id_user = u.id JOIN buku b ON p.id_buku = b.id_buku WHERE p.perpanjangan_status = 'requested' ORDER BY p.id_peminjaman DESC");
                    if ($q_ext && mysqli_num_rows($q_ext) > 0):
                        while($e = mysqli_fetch_assoc($q_ext)):
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo htmlspecialchars($e['nama']); ?></strong></td>
                        <td><?php echo htmlspecialchars($e['judul']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($e['tgl_kembali_seharusnya'])); ?></td>
                        <td><span style="background:#3498db;color:white;padding:4px 8px;border-radius:4px;font-size:11px;"><i class="fa-solid fa-clock"></i> Menunggu</span></td>
                        <td>
                            <a href="pengembalian_aksi.php?aksi=approve_perpanjang&id=<?php echo $e['id_peminjaman']; ?>" class="btn-action btn-approve" onclick="return confirm('Setujui perpanjangan?')"><i class="fa-solid fa-check"></i> Setujui</a>
                            <a href="pengembalian_aksi.php?aksi=reject_perpanjang&id=<?php echo $e['id_peminjaman']; ?>" class="btn-action btn-reject" onclick="return confirm('Tolak perpanjangan?')"><i class="fa-solid fa-xmark"></i> Tolak</a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="6" style="text-align:center;">Tidak ada pengajuan perpanjangan.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-active" class="tab-content">
            <div class="search-wrapper" style="margin-bottom: 20px;">
                <input type="text" id="searchInput" placeholder="Cari nama peminjam atau judul buku..." 
                       style="width: 100%; max-width: 400px; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="1%">No</th>
                            <th>Peminjam</th>
                            <th>Judul Buku</th>
                            <th>Batas Kembali</th>
                            <th>Terlambat</th>
                            <th>Denda (Rp)</th>
                            <th width="200px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php 
                        $no = 1;
                        
                        $query_active = "SELECT peminjaman.*, users.nama, buku.judul 
                                         FROM peminjaman 
                                         JOIN users ON peminjaman.id_user = users.id 
                                         JOIN buku ON peminjaman.id_buku = buku.id_buku 
                                         WHERE peminjaman.status = 'Dipinjam'
                                         ORDER BY id_peminjaman DESC";
                        
                        $data_active = mysqli_query($koneksi, $query_active);

                        if ($data_active && mysqli_num_rows($data_active) > 0) {
                            while($d = mysqli_fetch_array($data_active)){
                                
                                $batas_kembali = $d['tgl_kembali_seharusnya'];
                                $tgl1 = new DateTime($batas_kembali);
                                $tgl2 = new DateTime($tgl_sekarang);
                                
                                $denda = 0;
                                $selisih_hari = 0;

                                if ($tgl2 > $tgl1) {
                                    $diff = $tgl2->diff($tgl1);
                                    $selisih_hari = $diff->days;
                                    
                                    // Hitung denda secara dinamis berdasarkan konfigurasi database
                                    if ($status_rule == 1) {
                                        $denda = $selisih_hari * $tarif_denda;
                                    } else {
                                        $denda = 0;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td class="target-peminjam"><strong><?php echo htmlspecialchars($d['nama']); ?></strong></td>
                                    <td class="target-buku"><?php echo htmlspecialchars($d['judul']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($batas_kembali)); ?></td>
                                    <td>
                                        <?php 
                                        if($selisih_hari > 0) {
                                            echo "<span style='color: #d9534f; font-weight: bold;'>$selisih_hari Hari</span>";
                                        } else {
                                            echo "<span style='color: #27ae60;'>Tepat Waktu</span>";
                                        }
                                        ?>
                                    </td>
                                    <td><strong><?php echo number_format($denda, 0, ',', '.'); ?></strong></td>
                                    <td>
                                        <a href="pengembalian_aksi.php?aksi=kembali&id=<?php echo $d['id_peminjaman']; ?>&buku=<?php echo $d['id_buku']; ?>&denda=<?php echo $denda; ?>" 
                                           class="btn-action btn-selesai" 
                                           onclick="return confirm('Proses pengembalian buku? Denda: Rp <?php echo number_format($denda, 0, ',', '.'); ?>')">
                                             <i class="fa-solid fa-rotate-left"></i> Selesai
                                        </a>

                                        <a href="pengembalian_aksi.php?aksi=perpanjang&id=<?php echo $d['id_peminjaman']; ?>" 
                                           class="btn-action btn-perpanjang"
                                           onclick="return confirm('Apakah Anda yakin ingin memperpanjang masa pinjam buku ini?')">
                                             <i class="fa-solid fa-calendar-plus"></i> Perpanjang
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center;'>Tidak ada buku yang sedang dipinjam.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
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

        // Fitur Pencarian
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let kataKunci = this.value.toLowerCase();
            let barisTabel = document.querySelectorAll('#tableBody tr');

            barisTabel.forEach(baris => {
                let namaPeminjam = baris.querySelector('.target-peminjam')?.textContent.toLowerCase() || "";
                let judulBuku = baris.querySelector('.target-buku')?.textContent.toLowerCase() || "";
                
                if (namaPeminjam.indexOf(kataKunci) > -1 || judulBuku.indexOf(kataKunci) > -1) {
                    baris.style.display = "";
                } else {
                    baris.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>