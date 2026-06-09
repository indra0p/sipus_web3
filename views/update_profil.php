<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['submit'])) {
    $id_user = $_SESSION['id_user'];
    $nama    = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email   = mysqli_real_escape_string($koneksi, $_POST['email']);
    $jenkel  = $_POST['jenkel'];
    
    $pass_baru  = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_pass'];

    // 1. Ambil data lama untuk cek foto
    $cek_db = mysqli_query($koneksi, "SELECT foto FROM users WHERE id = '$id_user'");
    $data_db = mysqli_fetch_assoc($cek_db);
    $foto_lama = $data_db['foto'];

    // 2. Logika Ganti Password (Tanpa Hash agar bisa login dengan proses_login.php kamu)
    $sql_password = "";
    if (!empty($pass_baru)) {
        if ($pass_baru === $konfirmasi) {
            $sql_password = ", password = '$pass_baru'";
        } else {
            header("location: profil.php?pesan=pass_salah");
            exit;
        }
    }

    // 3. Logika Upload Foto
    $foto_final = $foto_lama;
    if ($_FILES['foto']['name'] != "") {
        $ekstensi_boleh = array('png', 'jpg', 'jpeg');
        $nama_file = $_FILES['foto']['name'];
        $x = explode('.', $nama_file);
        $ekstensi = strtolower(end($x));
        $ukuran = $_FILES['foto']['size'];
        $file_tmp = $_FILES['foto']['tmp_name'];

        $foto_final = "user_" . $id_user . "_" . time() . "." . $ekstensi;

        if (in_array($ekstensi, $ekstensi_boleh)) {
            if ($ukuran < 2000000) {
                move_uploaded_file($file_tmp, '../assets/img/profil/' . $foto_final);
                // Hapus foto lama jika ada
                if (!empty($foto_lama) && file_exists('../assets/img/profil/' . $foto_lama)) {
                    unlink('../assets/img/profil/' . $foto_lama);
                }
            } else {
                header("location: profil.php?pesan=ukuran_besar");
                exit;
            }
        }
    }

    // 4. Update Database
    $query = "UPDATE users SET 
                nama = '$nama', 
                email = '$email', 
                jenkel = '$jenkel', 
                foto = '$foto_final' 
                $sql_password 
              WHERE id = '$id_user'";

    if (mysqli_query($koneksi, $query)) {
        $_SESSION['nama'] = $nama; // Update session nama
        header("location: profil.php?pesan=berhasil");
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>