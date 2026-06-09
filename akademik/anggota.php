<?php
session_start();
include '../config/koneksi.php';

// Proteksi Halaman: Hanya user dengan role akademik yang bisa akses halaman ini
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['role'] != "akademik") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

// 1. Query Data Mahasiswa
$query_mahasiswa = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'mahasiswa' OR role = 'member' ORDER BY id DESC");

// 2. Query Data Dosen
$query_dosen = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'dosen' ORDER BY id DESC");

// 3. Query Data Karyawan
$query_karyawan = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'karyawan' ORDER BY id DESC");

// 4. Query Data Akademik & Admin (Tambahan manajemen internal)
$query_internal = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'akademik' OR role = 'admin' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Anggota - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.05); margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        table th { background-color: #f8f9fa; color: #333; font-weight: 600; }
        
        /* Pewarnaan Badge berdasarkan Role */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; display: inline-block; }
        .badge-mahasiswa { background: #e3f2fd; color: #2196f3; }
        .badge-dosen { background: #e8f5e9; color: #2e7d32; }
        .badge-karyawan { background: #fff3e0; color: #e65100; }
        .badge-akademik { background: #f3e5f5; color: #8e24aa; }
        .badge-admin { background: #ffebee; color: #c62828; }

        /* Struktur CSS Pembuat Tab Navigasi Murni */
        .tab-wrapper { margin-top: 25px; }
        .tab-nav { display: flex; gap: 5px; border-bottom: 2px solid #e0e0e0; margin-bottom: 15px; }
        .tab-btn { padding: 12px 20px; font-weight: bold; font-size: 14px; color: #666; cursor: pointer; border: none; background: none; border-bottom: 3px solid transparent; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
        .tab-btn:hover { color: #B46932; }
        
        /* Logika Sembunyikan & Tampilkan Tab via Radio Button */
        .tab-radio { display: none; }
        .tab-content { display: none; }

        #tab-mhs:checked ~ .tab-nav label[for="tab-mhs"],
        #tab-dsn:checked ~ .tab-nav label[for="tab-dsn"],
        #tab-krw:checked ~ .tab-nav label[for="tab-krw"],
        #tab-itn:checked ~ .tab-nav label[for="tab-itn"] {
            color: #B46932;
            border-bottom-color: #B46932;
        }

        #tab-mhs:checked ~ #content-mhs,
        #tab-dsn:checked ~ #content-dsn,
        #tab-krw:checked ~ #content-krw,
        #tab-itn:checked ~ #content-itn {
            display: block;
        }
        
        .alert-msg { padding: 10px 15px; margin-bottom: 20px; border-radius: 6px; font-size: 14px; font-weight: 500; }
        .empty-row { text-align: center; color: #999; padding: 25px !important; font-style: italic; }
    </style>
</head>
<body>
    <!-- SIDEBAR UTAMA AKADEMIK -->
    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-book-open"></i> <span>SIPUS POLSA</span></div>
        <nav>
            <a href="akademik_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php" class="active"><i class="fa-solid fa-users"></i> Melihat Anggota</a>
            <a href="transaksi_buku.php"><i class="fa-solid fa-exchange-alt"></i> Transaksi Buku</a>
            <a href="denda.php"><i class="fa-solid fa-coins"></i> Mengubah &amp; Rekap Denda</a>
            <a href="statistik_pengunjung.php"><i class="fa-solid fa-chart-line"></i> Statistika Pengunjung</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Yakin ingin keluar?')"><i class="fa-solid fa-power-off"></i> Logout</a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <header>
            <div class="header-title">
                <h1>Daftar Anggota Perpustakaan</h1>
                <p>Memantau dan memanajemen klasifikasi keanggotaan aktif civitas akademika</p>
            </div>
        </header>

        <!-- NOTIFIKASI PESAN AKSI -->
        <?php 
        if(isset($_GET['pesan'])){
            if($_GET['pesan'] == "berhasil"){
                echo "<div class='alert-msg' style='background: #d4edda; color: #155724;'><i class='fa-solid fa-circle-check'></i> Anggota baru berhasil ditambahkan!</div>";
            } else if($_GET['pesan'] == "hapus"){
                echo "<div class='alert-msg' style='background: #f8d7da; color: #721c24;'><i class='fa-solid fa-trash-can'></i> Data anggota berhasil dihapus dari sistem.</div>";
            } else if($_GET['pesan'] == "update"){
                echo "<div class='alert-msg' style='background: #d1ecf1; color: #0c5460;'><i class='fa-solid fa-circle-info'></i> Data anggota berhasil diperbarui.</div>";
            }
        }
        ?>

        <!-- STRUKTUR TAB CONTAINER -->
        <div class="tab-wrapper">
            <!-- Radio Trigger Kontrol Tab -->
            <input type="radio" name="anggota_tabs" id="tab-mhs" class="tab-radio" checked>
            <input type="radio" name="anggota_tabs" id="tab-dsn" class="tab-radio">
            <input type="radio" name="anggota_tabs" id="tab-krw" class="tab-radio">
            <input type="radio" name="anggota_tabs" id="tab-itn" class="tab-radio">

            <!-- Tombol Navigasi Atas Tab -->
            <div class="tab-nav">
                <label for="tab-mhs" class="tab-btn"><i class="fa-solid fa-graduation-cap"></i> Mahasiswa / Member</label>
                <label for="tab-dsn" class="tab-btn"><i class="fa-solid fa-chalkboard-user"></i> Dosen</label>
                <label for="tab-krw" class="tab-btn"><i class="fa-solid fa-briefcase"></i> Karyawan</label>
                <label for="tab-itn" class="tab-btn"><i class="fa-solid fa-user-shield"></i> Internal (Akademik &amp; Admin)</label>
            </div>

            <!-- ========================================== -->
            <!-- TAB PANEL 1: MAHASISWA -->
            <!-- ========================================== -->
            <div class="tab-content" id="content-mhs">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">NIM / Username</th>
                                <th width="35%">Nama Lengkap</th>
                                <th width="20%">Jenis Kelamin</th>
                                <th width="20%">Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($query_mahasiswa) > 0){
                                while($row = mysqli_fetch_assoc($query_mahasiswa)) { 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                <td><?php echo isset($row['jenkel']) ? htmlspecialchars($row['jenkel']) : '-'; ?></td>
                                <td><span class="badge badge-mahasiswa"><?php echo htmlspecialchars($row['role']); ?></span></td>
                            </tr>
                            <?php }} else { echo "<tr><td colspan='5' class='empty-row'>Belum ada data mahasiswa atau member.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB PANEL 2: DOSEN -->
            <!-- ========================================== -->
            <div class="tab-content" id="content-dsn">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">NIDN / Username</th>
                                <th width="35%">Nama Lengkap</th>
                                <th width="20%">Jenis Kelamin</th>
                                <th width="20%">Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($query_dosen) > 0){
                                while($row = mysqli_fetch_assoc($query_dosen)) { 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                <td><?php echo isset($row['jenkel']) ? htmlspecialchars($row['jenkel']) : '-'; ?></td>
                                <td><span class="badge badge-dosen"><?php echo htmlspecialchars($row['role']); ?></span></td>
                            </tr>
                            <?php }} else { echo "<tr><td colspan='5' class='empty-row'>Belum ada data dosen terdaftar.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB PANEL 3: KARYAWAN -->
            <!-- ========================================== -->
            <div class="tab-content" id="content-krw">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">NIK / Username</th>
                                <th width="35%">Nama Lengkap</th>
                                <th width="20%">Jenis Kelamin</th>
                                <th width="20%">Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($query_karyawan) > 0){
                                while($row = mysqli_fetch_assoc($query_karyawan)) { 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                <td><?php echo isset($row['jenkel']) ? htmlspecialchars($row['jenkel']) : '-'; ?></td>
                                <td><span class="badge badge-karyawan"><?php echo htmlspecialchars($row['role']); ?></span></td>
                            </tr>
                            <?php }} else { echo "<tr><td colspan='5' class='empty-row'>Belum ada data karyawan terdaftar.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB PANEL 4: INTERNAL (AKADEMIK & ADMIN) -->
            <!-- ========================================== -->
            <div class="tab-content" id="content-itn">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Username</th>
                                <th width="35%">Nama Petugas</th>
                                <th width="20%">Jenis Kelamin</th>
                                <th width="20%">Role Jabatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($query_internal) > 0){
                                while($row = mysqli_fetch_assoc($query_internal)) { 
                                    $badge_class = ($row['role'] == 'admin') ? 'badge-admin' : 'badge-akademik';
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                <td><?php echo isset($row['jenkel']) ? htmlspecialchars($row['jenkel']) : '-'; ?></td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['role']); ?></span></td>
                            </tr>
                            <?php }} else { echo "<tr><td colspan='5' class='empty-row'>Belum ada data internal sistem.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</body>
</html>