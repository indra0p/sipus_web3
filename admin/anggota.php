<?php
session_start();
// Menghubungkan ke database
include '../config/koneksi.php';

// Proteksi Halaman: Hanya admin yang bisa akses
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Anggota - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-book-open"></i>
            <span>SIPUS POLSA</span>
        </div>
    
        <nav>
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php" class="active"><i class="fa-solid fa-users"></i> Kelola Anggota</a>
            <a href="buku.php"><i class="fa-solid fa-book"></i> Stok Buku</a>
            <a href="peminjaman.php"><i class="fa-solid fa-right-left"></i> Peminjaman</a>
            <a href="pengembalian.php"><i class="fa-solid fa-rotate-left"></i> Pengembalian</a>
            <a href="kunjungan.php"><i class="fa-solid fa-door-open"></i> Kunjungan</a>
            <a href="denda.php"><i class="fa-solid fa-coins"></i> Denda</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Apakah anda yakin ingin keluar?')">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <div class="header-title">
                <h1>Data Anggota</h1>
                <p>Manajemen Keanggotaan Perpustakaan</p>
            </div>
            <a href="tambah_anggota.php" class="btn-add">
                <i class="fa-solid fa-user-plus"></i> Tambah Anggota Baru
            </a>
        </header>

        <?php 
        if(isset($_GET['pesan'])){
            if($_GET['pesan'] == "berhasil"){
                echo "<div class='alert-msg' style='background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 5px;'>Anggota baru berhasil ditambahkan!</div>";
            } else if($_GET['pesan'] == "hapus"){
                echo "<div class='alert-msg' style='background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 5px;'>Data anggota berhasil dihapus.</div>";
            } else if($_GET['pesan'] == "update"){
                echo "<div class='alert-msg' style='background: #d1ecf1; color: #0c5460; padding: 10px; margin-bottom: 20px; border-radius: 5px;'>Data anggota berhasil diperbarui.</div>";
            }
        }
        ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">User ID</th>
                        <th width="30%">Nama Lengkap</th>
                        <th width="20%">Jenis Kelamin</th>
                        <th width="15%">Role</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    // Mengambil data dari tabel users (kecuali admin)
                    $data = mysqli_query($koneksi, "SELECT * FROM users WHERE role != 'admin' ORDER BY id DESC");
                    
                    if(mysqli_num_rows($data) > 0){
                        while($d = mysqli_fetch_array($data)){
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo $d['username']; ?></strong></td>
                                <td><?php echo $d['nama']; ?></td>
                                <td><?php echo $d['jenkel']; ?></td>
                                <td><span class="badge" style="background: #eee; padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?php echo $d['role']; ?></span></td>
                                <td>
                                    <a href="anggota_edit.php?id=<?php echo $d['id']; ?>" class="btn-edit-table" title="Edit" style="color: #3498db; margin-right: 10px;">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="anggota_hapus.php?id=<?php echo $d['id']; ?>" 
                                       class="btn-delete-table" 
                                       title="Hapus"
                                       style="color: #e74c3c;"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus anggota <?php echo $d['nama']; ?>?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php 
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>Belum ada data anggota.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>