<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login Admin/Petugas
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

// Generate Barcode Otomatis untuk E-Book
$barcode_otomatis = "EB-" . substr(time(), 2);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah E-Book Baru - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset & Layout Khusus Tanpa Sidebar */
        body { 
            background-color: #f4f6f9; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .wrapper {
            width: 100%;
            max-width: 680px;
            padding: 20px;
            box-sizing: border-box;
        }
        .form-container { 
            background: white; 
            padding: 35px; 
            border-radius: 16px; 
            box-shadow: 0 10px 25px rgba(0,0,0,.05); 
            border: 1px solid #eef2f6;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header h1 {
            font-size: 24px;
            color: #333;
            margin: 0 0 8px 0;
            font-weight: 700;
        }
        .form-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        .form-group { 
            margin-bottom: 20px; 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
        }
        .form-group label { 
            font-weight: 600; 
            color: #4a5568; 
            font-size: 14px; 
        }
        .form-group input, .form-group select, .form-group textarea { 
            padding: 12px 14px; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            font-size: 14px; 
            font-family: inherit;
            transition: all 0.2s;
            background-color: #fff;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #B46932;
            outline: none;
            box-shadow: 0 0 0 3px rgba(180, 105, 50, 0.1);
        }
        .form-group input[readonly] {
            background-color: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }
        .form-group textarea { 
            resize: vertical; 
            min-height: 110px; 
        }
        .row-flex { 
            display: flex; 
            gap: 15px; 
        }
        .row-flex .form-group { 
            flex: 1; 
        }
        
        /* Area Upload PDF Ringkas */
        .upload-box {
            background: #f0fdf4; 
            padding: 18px; 
            border-radius: 10px; 
            border: 2px dashed #bbf7d0;
            margin-top: 10px;
        }
        
        /* Tombol Kontrol Bawah */
        .button-group {
            display: flex; 
            gap: 12px; 
            margin-top: 30px;
        }
        .btn-submit { 
            flex: 2;
            background: #10b981; 
            color: white; 
            border: none; 
            padding: 14px; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 15px; 
            transition: 0.2s; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            gap: 8px; 
        }
        .btn-submit:hover { 
            background: #059669; 
        }
        .btn-back { 
            flex: 1;
            background: #6b7280; 
            color: white; 
            padding: 14px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-size: 15px; 
            font-weight: bold; 
            text-align: center; 
            display: inline-flex; 
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: 0.2s;
        }
        .btn-back:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <div class="form-container">
            
            <div class="form-header">
                <h1><i class="fa-solid fa-cloud-arrow-up" style="color: #10b981;"></i> Tambah E-Book</h1>
                <p>Silakan lengkapi berkas digital dokumen pustaka SIPUS POLSA</p>
            </div>

            <!-- Form Eksekusi -->
            <form action="ebook_proses.php" method="POST" enctype="multipart/form-data">
                
                <!-- Barcode Otomatis Readonly -->
                <div class="form-group">
                    <label><i class="fa-solid fa-barcode"></i> Kode Barcode (Otomatis)</label>
                    <input type="text" name="barcode" value="<?php echo $barcode_otomatis; ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Judul E-Book</label>
                    <input type="text" name="judul" required placeholder="Masukkan judul lengkap e-book">
                </div>

                <div class="row-flex">
                    <div class="form-group">
                        <label>Penulis / Pengarang</label>
                        <input type="text" name="pengarang" required placeholder="Nama penulis">
                    </div>
                    <div class="form-group">
                        <label>Penerbit</label>
                        <input type="text" name="penerbit" required placeholder="Nama instansi penerbit">
                    </div>
                </div>

                <div class="form-group">
                    <label>Jenis / Kategori Buku</label>
                    <input type="text" name="jenis_buku" required placeholder="Contoh: Pemrograman, Akuntansi, Novel">
                </div>

                <div class="form-group">
                    <label>Sinopsis</label>
                    <textarea name="sinopsis" placeholder="Tulis deskripsi ringkas atau sinopsis e-book di sini..."></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fa-regular fa-image"></i> File Sampul Gambar (JPG/PNG)</label>
                    <input type="file" name="sampul" accept="image/*">
                </div>

                <div class="form-group upload-box">
                    <label style="color: #155724; font-weight: 700;"><i class="fa-solid fa-file-pdf"></i> Unggah Berkas Buku Digital (Format: PDF)</label>
                    <input type="file" name="file_ebook" accept=".pdf" required style="background: white; margin-top: 5px;">
                </div>

                <!-- Tombol Navigasi Keluar & Simpan -->
                <div class="button-group">
                    <a href="buku.php" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                    <button type="submit" name="simpan" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan E-Book
                    </button>
                </div>
            </form>
            
        </div>
    </div>

</body>
</html>