<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Perpustakaan - SIPUS POLSA</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #B46932;
            --primary-light: rgba(180, 105, 50, 0.2);
            --primary-dark: #8E5124;
            --bg-gradient: linear-gradient(135deg, #A99086 0%, #D4A373 100%);
            --glass: rgba(255, 255, 255, 0.82);
            --text-main: #1E2329;
            --text-muted: #656E7B;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .login-page {
            position: relative;
            width: 100%;
            max-width: 420px;
            padding: 24px;
            z-index: 1;
        }

        /* Efek Tumpukan Kertas Estetik & Presisi */
        .paper-stack {
            position: absolute;
            width: 90%;
            height: 100%;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 40px;
            top: 12px;
            left: 5%;
            z-index: -1;
            transform: rotate(-3.5deg);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .paper-stack.second {
            top: 22px;
            transform: rotate(2.5deg);
            background: rgba(255, 255, 255, 0.12);
            z-index: -2;
        }

        .login-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 55px 40px;
            border-radius: 40px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.08), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.5);
            text-align: center;
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-icon-wrapper {
            margin-bottom: 20px;
        }

        .profile-icon {
            width: 84px;
            height: 84px;
            background: var(--primary);
            color: white;
            border-radius: 28px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
            margin: 0 auto;
            box-shadow: 0 12px 24px rgba(180, 105, 50, 0.3);
            transform: rotate(-6deg);
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .login-card:hover .profile-icon {
            transform: rotate(0deg) scale(1.05);
        }

        .brand-section h2 {
            color: var(--text-main);
            font-weight: 800;
            font-size: 26px;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .brand-section p {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 35px;
        }

        /* Pesan Notifikasi/Gagal Modern */
        .error-msg {
            font-size: 13.5px;
            font-weight: 600;
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 25px;
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FCA5A5;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-3px, 0, 0); }
            40%, 60% { transform: translate3d(3px, 0, 0); }
        }

        /* Input Group Presisi dengan Inner Icon */
        .input-group {
            margin-bottom: 18px;
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
            transition: color 0.3s;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px 16px 52px;
            background: rgba(255, 255, 255, 0.65);
            border: 2px solid transparent;
            border-radius: 20px;
            outline: none;
            font-family: inherit;
            font-size: 14.5px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: var(--text-main);
        }

        .input-group input::placeholder {
            color: #94A3B8;
            font-weight: 500;
        }

        .input-group input:focus {
            background: #FFFFFF;
            border-color: var(--primary);
            box-shadow: 0 12px 24px rgba(180, 105, 50, 0.08);
        }

        .input-group input:focus + i {
            color: var(--primary);
        }

        /* Button Modern */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: #FFFFFF;
            border: none;
            border-radius: 20px;
            font-weight: 700;
            font-size: 15.5px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 12px 24px rgba(180, 105, 50, 0.25);
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 16px 32px rgba(180, 105, 50, 0.35);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Footer Bantuan/Support */
        .support-footer {
            margin-top: 35px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .support-footer a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 12.5px;
            font-weight: 600;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .support-footer a:hover {
            color: #25D366; /* Hover ganti warna WA */
        }

        .support-footer i {
            font-size: 15px;
            color: #25D366;
        }
    </style>
</head>
<body>

    <div class="login-page">
        <div class="paper-stack"></div>
        <div class="paper-stack second"></div>

        <div class="login-card">
            <div class="profile-icon-wrapper">
                <div class="profile-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>

            <div class="brand-section">
                <h2>SIPUS POLSA</h2>
                <p>Jelajahi ribuan ilmu dalam genggaman. Sistem Informasi Perpustakaan masa kini untuk Politeknik Sawunggalih Aji</p>
            </div>
            
            <?php 
            if(isset($_GET['pesan'])){
                $p = $_GET['pesan'];
                if($p == "gagal") {
                    echo "<div class='error-msg'><i class='fa-solid fa-circle-exclamation'></i> NIM atau Password Salah!</div>";
                } else if($p == "logout") {
                    echo "<div class='error-msg' style='background:#E8F5E9; color:#2E7D32; border-color:#A5D6A7;'><i class='fa-solid fa-circle-check'></i> Berhasil keluar dari sistem.</div>";
                } else if($p == "belum_login") {
                    echo "<div class='error-msg'><i class='fa-solid fa-lock'></i> Akses ditolak, silakan login!</div>";
                } else if($p == "pembuatan_akun_berhasil") {
                    echo "<div class='error-msg' style='background:#E8F5E9; color:#2E7D32; border-color:#A5D6A7;'><i class='fa-solid fa-circle-check'></i> Akun berhasil dibuat! Silakan masuk.</div>";
                }
            }
            ?>

            <form action="proses_login.php" method="POST">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Masukkan Nomor Induk Mahasiswa (NIM)" required autocomplete="off" autofocus>
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="input-group">
                    <input type="password" name="password" placeholder="Masukkan Password Anda" required>
                    <i class="fa-solid fa-lock"></i>
                </div>

                <button type="submit" name="login" class="btn-submit">Masuk</button>
            </form>

            <div class="support-footer">
                <a href="https://wa.me/6289505184707?text=Halo%20Admin%20SIPUS%20POLSA,%20saya%20mahasiswa%20lupa%20password%20SIAKAD%20untuk%20login%20perpustakaan." target="_blank">
                    <i class="fa-brands fa-whatsapp"></i> Lupa password atau butuh bantuan? Hubung Admin
                </a>
            </div>
        </div>
    </div>

</body>
</html>