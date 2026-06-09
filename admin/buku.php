<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

// 1. Query Data Buku Fisik (buku yang tidak memiliki file digital)
$query_buku_fisik = mysqli_query($koneksi, "SELECT * FROM buku WHERE file_ebook IS NULL OR file_ebook = '' ORDER BY id_buku DESC");

// 2. Query Data E-Book Digital (buku yang memiliki file digital PDF)
$query_ebook = mysqli_query($koneksi, "SELECT * FROM buku WHERE file_ebook IS NOT NULL AND file_ebook != '' ORDER BY id_buku DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok & E-Book - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.05); margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        table th { background-color: #f8f9fa; color: #333; font-weight: 600; }
        
        /* Badge Status */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; text-transform: uppercase; font-weight: bold; display: inline-block; }
        .badge.available { background: #dcfce7; color: #166534; }
        .badge.empty { background: #fee2e2; color: #991b1b; }
        .badge-digital { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

        /* Struktur CSS Pembuat Tab Navigasi Murni */
        .tab-wrapper { margin-top: 25px; }
        .tab-nav { display: flex; gap: 5px; border-bottom: 2px solid #e0e0e0; margin-bottom: 15px; }
        .tab-btn { padding: 12px 20px; font-weight: bold; font-size: 14px; color: #666; cursor: pointer; border: none; background: none; border-bottom: 3px solid transparent; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
        .tab-btn:hover { color: #B46932; }
        
        /* Logika Sembunyikan & Tampilkan Tab via Radio Button */
        .tab-radio { display: none; }
        .tab-content { display: none; }

        #tab-fisik:checked ~ .tab-nav label[for="tab-fisik"],
        #tab-digital:checked ~ .tab-nav label[for="tab-digital"] {
            color: #B46932;
            border-bottom-color: #B46932;
        }

        #tab-fisik:checked ~ #content-fisik,
        #tab-digital:checked ~ #content-digital {
            display: block;
        }
        
        .empty-row { text-align: center; color: #999; padding: 25px !important; font-style: italic; }
        
        /* Tombol Aksi Tambah Atas */
        .action-buttons { display: flex; gap: 10px; }
        .btn-add-ebook { background: #10b981; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
        .btn-add-ebook:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-book-open"></i> 
            <span>SIPUS POLSA</span>
        </div>
        <nav>
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="anggota.php"><i class="fa-solid fa-users"></i> Kelola Anggota</a>
            <a href="buku.php" class="active"><i class="fa-solid fa-book"></i> Stok Buku</a>
            <a href="peminjaman.php"><i class="fa-solid fa-right-left"></i> Peminjaman</a>
            <a href="pengembalian.php"><i class="fa-solid fa-rotate-left"></i> Pengembalian</a>
            <a href="kunjungan.php"><i class="fa-solid fa-door-open"></i> Kunjungan</a>
            <a href="denda.php"><i class="fa-solid fa-coins"></i> Denda</a>
            <a href="../logout.php" class="logout" onclick="return confirm('Apakah Anda yakin ingin logout?')">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1>Katalog Koleksi Pustaka</h1>
                <p style="color: #666; margin-top: 5px;">Manajemen database buku cetak fisik maupun buku elektronik digital</p>
            </div>
            <div class="action-buttons">
                <a href="ebook_tambah.php" class="btn-add-ebook">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Tambah E-Book Baru
                </a>
                <a href="buku_tambah.php" class="btn-add" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-plus"></i> Tambah Buku Baru
                </a>
            </div>
        </header>

        <?php if(isset($_GET['pesan'])): ?>
            <div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">
                <i class="fa-solid fa-circle-check"></i> 
                <?php 
                    if($_GET['pesan'] == "hapus") echo "Data koleksi berhasil dihapus.";
                    if($_GET['pesan'] == "tambah") echo "Data koleksi berhasil ditambahkan.";
                    if($_GET['pesan'] == "update") echo "Data koleksi berhasil diperbarui.";
                ?>
            </div>
        <?php endif; ?>

        <div class="tab-wrapper">
            <input type="radio" name="buku_tabs" id="tab-fisik" class="tab-radio" checked>
            <input type="radio" name="buku_tabs" id="tab-digital" class="tab-radio">

            <div class="tab-nav">
                <label for="tab-fisik" class="tab-btn"><i class="fa-solid fa-book-bookmark"></i> Buku Fisik (Koleksi Cetak)</label>
                <label for="tab-digital" class="tab-btn"><i class="fa-solid fa-laptop-code"></i> E-Book Digital (Koleksi PDF)</label>
            </div>

            <div class="tab-content" id="content-fisik">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Sampul</th>
                                <th width="40%">Info Buku Cetak</th>
                                <th width="15%">Penerbit</th>
                                <th width="15%">Stok</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($query_buku_fisik) > 0):
                                while($d = mysqli_fetch_array($query_buku_fisik)):
                                    $img_path = (!empty($d['sampul']) && file_exists("../assets/img/sampul/".$d['sampul'])) ? "../assets/img/sampul/".$d['sampul'] : "";
                            ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $no++; ?></td>
                                <td style="text-align: center;">
                                    <?php if(!empty($img_path)): ?>
                                        <img src="<?php echo $img_path; ?>" style="width: 60px; height: 80px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 80px; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999; border-radius: 4px;">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="font-size: 16px; color: #333;"><?php echo htmlspecialchars($d['judul']); ?></strong><br>
                                    <small style="color: #B46932; font-weight: 600;">Penulis: <?php echo htmlspecialchars($d['pengarang']); ?></small>
                                    <span style="font-size: 11px; background:#f0f0f0; padding:2px 6px; border-radius:4px; margin-left:5px;"><?php echo htmlspecialchars($d['jenis_buku'] ?? 'Umum'); ?></span>
                                    <?php if(!empty($d['barcode'])): ?>
                                    <br><small style="color:#888;"><i class="fa-solid fa-barcode"></i> <?php echo htmlspecialchars($d['barcode']); ?></small>
                                    <?php endif; ?>
                                    <div style="font-size: 12px; color: #666; margin-top: 8px; line-height: 1.4; max-height: 50px; overflow: hidden;">
                                        <?php echo (!empty($d['sinopsis'])) ? htmlspecialchars($d['sinopsis']) : "<i style='color:#ccc;'>Tidak ada sinopsis.</i>"; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($d['penerbit']); ?></td>
                                <td style="text-align: center;">
                                    <strong style="font-size: 18px;"><?php echo $d['stok']; ?></strong><br>
                                    <span class="badge <?php echo ($d['stok'] > 0) ? 'available' : 'empty'; ?>">
                                        <?php echo ($d['stok'] > 0) ? 'Tersedia' : 'Habis'; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="buku_edit.php?id=<?php echo $d['id_buku']; ?>" style="color: #3498db; margin-right: 10px;" title="Edit"><i class="fa fa-edit"></i></a>
                                    <a href="buku_aksi.php?aksi=hapus&id=<?php echo $d['id_buku']; ?>" style="color: #e74c3c;" onclick="return confirm('Yakin ingin menghapus buku ini?')" title="Hapus"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                                echo "<tr><td colspan='6' class='empty-row'>Data koleksi buku cetak belum tersedia.</td></tr>";
                            endif; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-content" id="content-digital">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Sampul</th>
                                <th width="40%">Info E-Book & Dokumen</th>
                                <th width="15%">Penerbit</th>
                                <th width="15%">File PDF</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no_e = 1;
                            if(mysqli_num_rows($query_ebook) > 0):
                                while($e = mysqli_fetch_array($query_ebook)):
                                    $img_path_e = (!empty($e['sampul']) && file_exists("../assets/img/sampul/".$e['sampul'])) ? "../assets/img/sampul/".$e['sampul'] : "";
                            ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $no_e++; ?></td>
                                <td style="text-align: center;">
                                    <?php if(!empty($img_path_e)): ?>
                                        <img src="<?php echo $img_path_e; ?>" style="width: 60px; height: 80px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 80px; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999; border-radius: 4px;">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="font-size: 16px; color: #10b981;"><?php echo htmlspecialchars($e['judul']); ?></strong><br>
                                    <small style="color: #B46932; font-weight: 600;">Penulis: <?php echo htmlspecialchars($e['pengarang']); ?></small>
                                    <span class="badge badge-digital" style="margin-left:5px;"><?php echo htmlspecialchars($e['jenis_buku'] ?? 'E-Book'); ?></span>
                                    <div style="font-size: 12px; color: #666; margin-top: 8px; line-height: 1.4; max-height: 50px; overflow: hidden;">
                                        <?php echo (!empty($e['sinopsis'])) ? htmlspecialchars($e['sinopsis']) : "<i style='color:#ccc;'>Tidak ada sinopsis.</i>"; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($e['penerbit']); ?></td>
                                <td style="text-align: center;">
                                    <a href="../assets/docs/ebook/<?php echo $e['file_ebook']; ?>" target="_blank" style="color: #ef4444; font-size: 13px; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-file-pdf" style="font-size: 18px;"></i> Lihat PDF
                                    </a>
                                </td>
                                <td style="text-align: center;">
                                    <a href="ebook_edit.php?id=<?php echo $e['id_buku']; ?>" style="color: #3498db; margin-right: 10px;" title="Edit E-Book"><i class="fa fa-edit"></i></a>
                                    <a href="buku_aksi.php?aksi=hapus&id=<?php echo $e['id_buku']; ?>" style="color: #e74c3c;" onclick="return confirm('Yakin ingin menghapus e-book ini?')" title="Hapus"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                                echo "<tr><td colspan='6' class='empty-row'>Data e-book digital belum tersedia.</td></tr>";
                            endif; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</body>
</html>