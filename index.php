<?php
session_start();
if (isset($_SESSION['username'])) {
    header("location: admin/dashboard.php");
    exit;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$login_url = $protocol . $host . dirname($_SERVER['PHP_SELF']) . "/login/login.php";

// QR Code dengan warna yang lebih deep (Espresso Brown) agar kontrasnya bagus
$qr_api_url = "https://quickchart.io/qr?text=" . urlencode($login_url) . "&size=250&dark=3E2723&margin=2";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPUS POLSA | Digital Library Ecosystem</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Lottie Player untuk Animasi Perpustakaan -->
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    
    <style>
        :root {
            --primary: #B46932;
            --primary-light: #D4A373;
            --glass: rgba(255, 255, 255, 0.9);
            --text-main: #2D3436;
            --text-muted: #636E72;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #A99086 0%, #D4A373 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Animasi Background Floating */
        body::before {
            content: "";
            position: absolute;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            top: -100px; left: -100px;
            z-index: -1;
            animation: float 10s infinite alternate;
        }

        @keyframes float {
            from { transform: translate(0, 0); }
            to { transform: translate(50px, 100px); }
        }

        .wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .main-card {
            background: var(--glass);
            backdrop-filter: blur(15px);
            width: 100%;
            max-width: 950px;
            padding: 60px;
            border-radius: 40px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.15);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            border: 1px solid rgba(255,255,255,0.3);
        }

        /* Bagian Kiri: Animasi & Text */
        .brand-side {
            text-align: left;
        }

        .lottie-container {
            width: 100%;
            max-width: 320px;
            margin-bottom: 20px;
        }

        .brand-side h1 {
            font-size: 42px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.1;
            margin-bottom: 15px;
        }

        .brand-side h1 span {
            color: var(--primary);
        }

        .brand-side p {
            color: var(--text-muted);
            font-size: 18px;
            line-height: 1.6;
        }

        /* Bagian Kanan: Login Methods */
        .login-side {
            background: white;
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }

        .method-group {
            margin-bottom: 30px;
        }

        .method-label {
            display: block;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .btn-modern {
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 18px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 20px rgba(180, 105, 50, 0.2);
        }

        .btn-modern:hover {
            transform: translateY(-5px);
            background: #8E5124;
            box-shadow: 0 15px 30px rgba(180, 105, 50, 0.3);
        }

        .qr-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .qr-card {
            background: #FDFCFB;
            padding: 12px;
            border-radius: 24px;
            border: 2px solid #F1F2F6;
            transition: all 0.3s ease;
        }

        .qr-card:hover {
            border-color: var(--primary-light);
            transform: scale(1.02);
        }

        .qr-card img {
            width: 160px;
            border-radius: 15px;
        }

        /* Footer */
        footer {
            padding: 40px;
            text-align: center;
            color: white;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 20px;
        }

        .social-links a {
            color: white;
            font-size: 22px;
            opacity: 0.8;
            transition: 0.3s;
        }

        .social-links a:hover {
            opacity: 1;
            transform: translateY(-3px);
            color: var(--primary-light);
        }

        .copyright {
            font-size: 13px;
            opacity: 0.7;
            letter-spacing: 0.5px;
        }

        /* Mobile View */
        @media (max-width: 900px) {
            .main-card {
                grid-template-columns: 1fr;
                padding: 40px 25px;
                text-align: center;
            }
            .brand-side { text-align: center; }
            .lottie-container { margin: 0 auto 20px; }
            .brand-side h1 { font-size: 32px; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="main-card">
        <!-- Kiri: Visual -->
        <div class="brand-side">
            <div class="lottie-container">
                <!-- Animasi Buku/Perpustakaan Lottie -->
                <lottie-player src="https://assets10.lottiefiles.com/packages/lf20_al8p6zpe.json" background="transparent" speed="1" loop autoplay></lottie-player>
            </div>
            <h1>SIPUS <span>POLSA.</span></h1>
            <p>Jelajahi ribuan ilmu dalam genggaman. Sistem Informasi Perpustakaan masa kini untuk Politeknik Sawunggalih Aji.</p>
        </div>

        <!-- Kanan: Akses -->
        <div class="login-side">
            <div class="method-group">
                <span class="method-label">Direct Access</span>
                <a href="login/login.php" class="btn-modern">
                    <i class="fa-solid fa-bolt-lightning"></i>
                    Masuk ke Sistem
                </a>
            </div>

            <div style="display: flex; align-items: center; margin: 25px 0;">
                <hr style="flex:1; opacity: 0.1;">
                <span style="padding: 0 15px; color: #ccc; font-size: 12px; font-weight: 700;">ATAU SCAN</span>
                <hr style="flex:1; opacity: 0.1;">
            </div>

            <div class="qr-area">
                <div class="qr-card">
                    <img src="<?php echo $qr_api_url; ?>" alt="QR Login Mobile">
                </div>
                <p style="font-size: 12px; color: var(--text-muted); font-weight: 500;">
                    <i class="fa-solid fa-mobile-screen-button"></i> Login cepat via Smartphone
                </p>
            </div>
        </div>
    </div>
</div>

<footer>
    <div class="social-links">
        <a href="https://facebook.com/polsa"><i class="fa-brands fa-facebook-f"></i></a>
        <a href="https://instagram.com/politeknikkutoarjo"><i class="fa-brands fa-instagram"></i></a>
        <a href="https://tiktok.com/@pmb.polsa"><i class="fa-brands fa-tiktok"></i></a>
        <a href="https://youtube.com/@politeknikkutoarjo"><i class="fa-brands fa-youtube"></i></a>
    </div>
    <p class="copyright">
        &copy; <?php echo date('Y'); ?> <strong>POLSA</strong> BOCAH GABUT <i class="fa-solid fa-heart" style="color: #e74c3c;"></i> for POLSA.
    </p>
</footer>

</body>
</html>