<?php
session_start();
include '../config/koneksi.php';

// 1. Proteksi Halaman
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php?pesan=belum_login");
    exit;
}

$id_buku = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');

// 2. Ambil Data Buku
$query = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku = '$id_buku' AND file_ebook IS NOT NULL AND file_ebook != ''");
$buku = mysqli_fetch_array($query);

if (!$buku) {
    echo "<script>alert('E-Book tidak valid atau belum memiliki file PDF!'); window.location='ebook.php';</script>";
    exit;
}

$file_path = "../assets/docs/ebook/" . $buku['file_ebook'];

// Jika menggunakan ephemeral storage di server, file mungkin hilang
if (!file_exists($file_path)) {
    echo "<div style='font-family:sans-serif; text-align:center; padding: 50px; color:#555;'>";
    echo "<h2><span style='color:red;'>Oops!</span> Dokumen PDF Tidak Ditemukan</h2>";
    echo "<p>Mohon maaf, file dokumen untuk e-book ini belum tersedia di dalam server (kemungkinan terhapus karena penyimpanan sementara).</p>";
    echo "<a href='ebook.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#3b82f6; color:white; text-decoration:none; border-radius:5px;'>Kembali ke Daftar E-Book</a>";
    echo "</div>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membaca: <?php echo htmlspecialchars($buku['judul']); ?></title>
    
    <!-- Load library pdf.js via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #333; /* Dark mode untuk viewer */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            
            /* Anti-copy (tidak bisa nge-blok tulisan apa pun) */
            -webkit-user-select: none; /* Safari */
            -moz-user-select: none; /* Firefox */
            -ms-user-select: none; /* IE10+/Edge */
            user-select: none; /* Standard */
        }
        
        .toolbar {
            background-color: #1e1e1e;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
            z-index: 10;
        }

        .toolbar-title {
            font-weight: bold;
            font-size: 16px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 40%;
        }

        .controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            background-color: #444;
            color: white;
            border: 1px solid #555;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn:hover:not(:disabled) {
            background-color: #555;
            border-color: #777;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-close {
            background-color: #e74c3c;
            border-color: #c0392b;
        }

        .btn-close:hover {
            background-color: #c0392b;
        }

        .viewer-container {
            flex: 1;
            overflow: auto;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px 0;
            background-color: #525659;
            position: relative;
        }

        canvas {
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            margin-bottom: 20px;
            background-color: white;
        }
        
        #page-info {
            font-size: 14px;
            color: #ddd;
        }

        /* Overlay Watermark Halus agar makin susah dicopy secara ilegal */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(0, 0, 0, 0.03);
            pointer-events: none;
            white-space: nowrap;
            z-index: 5;
            user-select: none;
        }
    </style>
</head>

<!-- Mencegah fungsi klik kanan dan copy pada seluruh body -->
<body oncontextmenu="return false;" oncopy="return false;" oncut="return false;" onpaste="return false;">

    <div class="toolbar">
        <div class="toolbar-title">
            <i class="fa-solid fa-lock" style="color:#f1c40f; margin-right:5px;" title="Dokumen ini diproteksi"></i> 
            <?php echo htmlspecialchars($buku['judul']); ?>
        </div>
        
        <div class="controls">
            <button class="btn" id="prev-page"><i class="fa-solid fa-chevron-left"></i> Sebelumnya</button>
            <span id="page-info">Halaman <span id="page-num">0</span> dari <span id="page-count">0</span></span>
            <button class="btn" id="next-page">Selanjutnya <i class="fa-solid fa-chevron-right"></i></button>
            <button class="btn" id="zoom-in" title="Perbesar"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
            <button class="btn" id="zoom-out" title="Perkecil"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
        </div>

        <div>
            <a href="detail_ebook.php?id=<?php echo $buku['id_buku']; ?>" class="btn btn-close">
                <i class="fa-solid fa-xmark"></i> Tutup
            </a>
        </div>
    </div>

    <div class="viewer-container" id="pdf-viewer">
        <!-- Watermark Proteksi -->
        <div class="watermark">SIPUS POLSA - HANYA BACA</div>
        
        <canvas id="pdf-render"></canvas>
    </div>

    <script>
        // Mencegah jalan pintas (Ctrl+S, Ctrl+P, Ctrl+U, Ctrl+C, F12)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && (e.key === 's' || e.key === 'S' || e.key === 'p' || e.key === 'P' || e.key === 'u' || e.key === 'U' || e.key === 'c' || e.key === 'C')) {
                e.preventDefault();
                alert('Tindakan ini tidak diizinkan pada dokumen yang diproteksi.');
            }
            if (e.key === 'F12') {
                e.preventDefault();
            }
        });

        // -------------------------
        // LOGIKA PDF.JS
        // -------------------------
        const url = '<?php echo $file_path; ?>';

        let pdfDoc = null,
            pageNum = 1,
            pageIsRendering = false,
            pageNumIsPending = null,
            scale = 1.2;

        const canvas = document.getElementById('pdf-render'),
              ctx = canvas.getContext('2d');

        // Pastikan path worker untuk pdf.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

        // Render suatu halaman
        const renderPage = num => {
            pageIsRendering = true;

            // Dapatkan halaman PDF
            pdfDoc.getPage(num).then(page => {
                // Skalakan PDF (Bisa diperbesar/diperkecil)
                const viewport = page.getViewport({ scale: scale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const renderCtx = {
                    canvasContext: ctx,
                    viewport: viewport
                };

                // Proses render ke Canvas
                page.render(renderCtx).promise.then(() => {
                    pageIsRendering = false;

                    if (pageNumIsPending !== null) {
                        renderPage(pageNumIsPending);
                        pageNumIsPending = null;
                    }
                });

                // Perbarui Status Halaman
                document.getElementById('page-num').textContent = num;
                
                // Atur status tombol prev/next
                document.getElementById('prev-page').disabled = num <= 1;
                document.getElementById('next-page').disabled = num >= pdfDoc.numPages;
            });
        };

        // Cek halaman pending
        const queueRenderPage = num => {
            if (pageIsRendering) {
                pageNumIsPending = num;
            } else {
                renderPage(num);
            }
        };

        // Ke halaman sebelumnya
        const showPrevPage = () => {
            if (pageNum <= 1) return;
            pageNum--;
            queueRenderPage(pageNum);
        };

        // Ke halaman selanjutnya
        const showNextPage = () => {
            if (pageNum >= pdfDoc.numPages) return;
            pageNum++;
            queueRenderPage(pageNum);
        };

        // Perbesar tampilan (Zoom In)
        const zoomIn = () => {
            if (scale >= 3.0) return;
            scale += 0.2;
            queueRenderPage(pageNum);
        };

        // Perkecil tampilan (Zoom Out)
        const zoomOut = () => {
            if (scale <= 0.6) return;
            scale -= 0.2;
            queueRenderPage(pageNum);
        };

        // Dapatkan Dokumen PDF 
        pdfjsLib.getDocument(url).promise.then(pdfDoc_ => {
            pdfDoc = pdfDoc_;
            document.getElementById('page-count').textContent = pdfDoc.numPages;
            renderPage(pageNum);
        }).catch(err => {
            // Tampilkan error jika PDF gagal diakses (misal korup)
            const div = document.createElement('div');
            div.className = 'error';
            div.style.color = '#fff';
            div.style.textAlign = 'center';
            div.style.marginTop = '20%';
            div.appendChild(document.createTextNode('Dokumen PDF Gagal Dimuat. Kemungkinan file hilang atau rusak.'));
            document.querySelector('.viewer-container').insertBefore(div, canvas);
            canvas.style.display = 'none';
        });

        // Events Button
        document.getElementById('prev-page').addEventListener('click', showPrevPage);
        document.getElementById('next-page').addEventListener('click', showNextPage);
        document.getElementById('zoom-in').addEventListener('click', zoomIn);
        document.getElementById('zoom-out').addEventListener('click', zoomOut);

    </script>
</body>
</html>
