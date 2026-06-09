<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - SIPUS POLSA</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #B46932; --primary-dark: #8E5124; --bg-gradient: linear-gradient(135deg, #A99086 0%, #D4A373 100%); --glass: rgba(255,255,255,0.85); --text-main: #2D3436; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-gradient); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .register-page { width: 100%; max-width: 450px; }
        .register-card { background: var(--glass); backdrop-filter: blur(15px); padding: 45px 35px; border-radius: 35px; box-shadow: 0 25px 50px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.4); animation: slideUp 0.8s cubic-bezier(0.175,0.885,0.32,1.275); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .header-icon { width: 70px; height: 70px; background: var(--primary); color: white; border-radius: 22px; display: flex; justify-content: center; align-items: center; font-size: 30px; margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(180,105,50,0.3); }
        h2 { text-align: center; color: var(--text-main); font-weight: 800; font-size: 22px; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #636E72; font-size: 13px; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select { width: 100%; padding: 14px 18px; background: rgba(255,255,255,0.7); border: 2px solid transparent; border-radius: 15px; outline: none; font-family: inherit; font-size: 14px; transition: all 0.3s; color: var(--text-main); }
        .form-group input:focus, .form-group select:focus { background: white; border-color: var(--primary); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .btn-register { width: 100%; padding: 15px; background: var(--primary); color: white; border: none; border-radius: 15px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all 0.3s; box-shadow: 0 10px 20px rgba(180,105,50,0.2); margin-top: 10px; }
        .btn-register:hover { background: var(--primary-dark); transform: translateY(-3px); }
        .login-link { text-align: center; margin-top: 20px; font-size: 13px; color: #636E72; }
        .login-link a { color: var(--primary); font-weight: 700; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
        .alert { font-size: 13px; padding: 12px; border-radius: 12px; margin-bottom: 15px; text-align: center; }
        .alert-danger { background: #ffe9e9; color: #d9534f; border: 1px solid #ffcfcf; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .row { display: flex; gap: 12px; }
        .row .form-group { flex: 1; }
    </style>
</head>
<body>
    <div class="register-page">
        <div class="register-card">
            <div class="header-icon"><i class="fa-solid fa-user-plus"></i></div>
            <h2>Daftar Akun Baru</h2>
            <p class="subtitle">Buat akun untuk mengakses layanan perpustakaan SIPUS POLSA</p>

            <?php
            if(isset($_GET['pesan'])){
                $p = $_GET['pesan'];
                if($p == "pass_tidak_cocok") echo "<div class='alert alert-danger'><i class='fa-solid fa-xmark'></i> Konfirmasi password tidak cocok!</div>";
                if($p == "sudah_ada") echo "<div class='alert alert-danger'><i class='fa-solid fa-xmark'></i> NIM/Username sudah terdaftar!</div>";
                if($p == "gagal") echo "<div class='alert alert-danger'><i class='fa-solid fa-xmark'></i> Registrasi gagal. Silakan coba lagi.</div>";
            }
            ?>

            <form action="proses_register.php" method="POST">
                <div class="form-group">
                    <label>NIM / Username</label>
                    <input type="text" name="username" placeholder="Masukkan NIM Anda" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" placeholder="Masukkan nama lengkap" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@domain.com (opsional)">
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenkel" required>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Buat password" required minlength="4">
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="konfirmasi" placeholder="Ulangi password" required minlength="4">
                </div>
                <button type="submit" name="register" class="btn-register"><i class="fa-solid fa-user-check"></i> Daftar Sekarang</button>
            </form>

            <div class="login-link">
                Sudah punya akun? <a href="login.php">Masuk di sini</a>
            </div>
        </div>
    </div>
</body>
</html>
