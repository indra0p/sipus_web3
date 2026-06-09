<?php 
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php");
    exit;
}

$form_token = bin2hex(random_bytes(32));
$_SESSION['form_token'] = $form_token;

$q_rules = mysqli_query($koneksi, "SELECT * FROM loan_rules");
$rules_json = [];
while($row = mysqli_fetch_assoc($q_rules)) {
    $rules_json[$row['role']] = $row['max_days'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Catat Peminjaman - SIPUS POLSA</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <style>
        .buku-container { background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px dashed #ccc; margin-bottom: 15px; }
        .judul-buku { font-weight: bold; color: #B46932; margin-bottom: 10px; display: block; }
        .form-card { max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>
<body>
    <div class="main-content no-sidebar">
        <div class="form-card">
            <h2>Catat Peminjaman Baru (Maks 2 Buku)</h2>
            <form action="peminjaman_aksi.php?aksi=tambah" method="POST" onsubmit="return validasiSebelumKirim()">
                
                <input type="hidden" name="form_token" value="<?php echo $form_token; ?>">
                
                <div class="form-group">
                    <label>Ketik NIM / Nama Anggota</label>
                    <input type="text" name="id_user" id="nim_peminjam" list="list_anggota" placeholder="Cari NIM atau Nama Anggota..." autocomplete="off" required>
                    <datalist id="list_anggota">
                        <?php 
                        $u = mysqli_query($koneksi, "SELECT * FROM users ORDER BY nama ASC");
                        while($user = mysqli_fetch_array($u)){
                            echo "<option data-role='".$user['role']."' value='".$user['id']."'>".$user['nama']." - ".$user['username']." (".$user['role'].")</option>";
                        }
                        ?>
                    </datalist>
                    <small id="info_durasi" style="color: #b46932; font-weight: bold; display: block; margin-top: 5px;"></small>
                </div>

                <div class="buku-container">
                    <span class="judul-buku">Buku Pertama (Wajib)</span>
                    <input type="text" name="judul_buku_1" class="buku-input" list="list_buku" placeholder="Ketik Judul Buku 1..." autocomplete="off" required>
                    <input type="hidden" name="id_buku_1" class="id-buku-hidden">
                </div>

                <div class="buku-container">
                    <span class="judul-buku">Buku Kedua (Opsional)</span>
                    <input type="text" name="judul_buku_2" class="buku-input" list="list_buku" placeholder="Ketik Judul Buku 2..." autocomplete="off">
                    <input type="hidden" name="id_buku_2" class="id-buku-hidden">
                </div>

                <datalist id="list_buku">
                    <?php 
                    $b = mysqli_query($koneksi, "SELECT * FROM buku WHERE stok > 0");
                    while($buku = mysqli_fetch_array($b)){
                        echo "<option data-id='".$buku['id_buku']."' value='".$buku['judul']."'>Stok: ".$buku['stok']."</option>";
                    }
                    ?>
                </datalist>

                <div class="form-group">
                    <label>Tanggal Pinjam</label>
                    <input type="date" name="tgl_pinjam" id="tgl_pinjam" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Estimasi Tanggal Kembali</label>
                    <input type="date" name="tgl_kembali" id="tgl_kembali" readonly style="background: #f0f0f0;">
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-save" style="background: #B46932; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Proses Pinjam</button>
                    <a href="peminjaman.php" class="btn-back" style="text-decoration: none; color: #666; margin-left: 10px;">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const rules = <?php echo json_encode($rules_json); ?>;
        const inputNim = document.getElementById('nim_peminjam');
        const listAnggota = document.getElementById('list_anggota');
        const tglPinjam = document.getElementById('tgl_pinjam');
        const tglKembali = document.getElementById('tgl_kembali');
        const infoDurasi = document.getElementById('info_durasi');

        function hitungTanggal() {
            const val = inputNim.value;
            const options = listAnggota.options;
            let role = "";
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === val) {
                    role = options[i].getAttribute('data-role');
                    break;
                }
            }
            if (role !== "" && rules[role]) {
                let hari = parseInt(rules[role]);
                let date = new Date(tglPinjam.value);
                date.setDate(date.getDate() + hari);
                tglKembali.value = date.toISOString().split('T')[0];
                infoDurasi.innerText = "Durasi pinjam (" + role + "): " + hari + " hari.";
            } else {
                tglKembali.value = "";
                infoDurasi.innerText = "";
            }
        }

        inputNim.addEventListener('input', hitungTanggal);
        tglPinjam.addEventListener('change', hitungTanggal);
        
        document.querySelectorAll('.buku-input').forEach((input) => {
            input.addEventListener('input', function() {
                const val = this.value;
                const opts = document.getElementById('list_buku').options;
                let foundId = "";
                for (let i = 0; i < opts.length; i++) {
                    if (opts[i].value === val) { foundId = opts[i].getAttribute('data-id'); break; }
                }
                this.parentElement.querySelector('.id-buku-hidden').value = foundId;
            });
        });

        function validasiSebelumKirim() { return true; }
    </script>
</body>
</html>