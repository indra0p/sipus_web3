<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location: ../login/login.php");
    exit;
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

// Helper untuk validasi ID
function cekDataAda($koneksi, $tabel, $kolom, $id) {
    $q = mysqli_query($koneksi, "SELECT $kolom FROM $tabel WHERE $kolom = '$id'");
    return mysqli_num_rows($q) > 0;
}

mysqli_begin_transaction($koneksi);

try {
    // 1. TAMBAH PEMINJAMAN
    if ($aksi == 'tambah') {
        if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
            throw new Exception("Token tidak valid.");
        }

        $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        $tgl_pinjam = mysqli_real_escape_string($koneksi, $_POST['tgl_pinjam']);
        $tgl_kembali = mysqli_real_escape_string($koneksi, $_POST['tgl_kembali']);
        
        if (!cekDataAda($koneksi, 'users', 'id', $id_user)) {
            throw new Exception("User tidak ditemukan di database.");
        }

        $buku_ids = [$_POST['id_buku_1'] ?? '', $_POST['id_buku_2'] ?? ''];
        foreach (array_filter($buku_ids) as $id_buku) {
            if (!cekDataAda($koneksi, 'buku', 'id_buku', $id_buku)) {
                throw new Exception("Buku dengan ID $id_buku tidak terdaftar.");
            }

            $res = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id_buku = '$id_buku' FOR UPDATE");
            $data = mysqli_fetch_assoc($res);
            
            if (!$data || $data['stok'] <= 0) {
                throw new Exception("Stok buku tidak tersedia.");
            }

            mysqli_query($koneksi, "INSERT INTO peminjaman (id_user, id_buku, tgl_pinjam, tgl_kembali_seharusnya, status) VALUES ('$id_user', '$id_buku', '$tgl_pinjam', '$tgl_kembali', 'Dipinjam')");
            mysqli_query($koneksi, "UPDATE buku SET stok = stok - 1 WHERE id_buku = '$id_buku'");
        }
        unset($_SESSION['form_token']);
        mysqli_commit($koneksi);
        header("location: peminjaman.php?pesan=berhasil");
    }

    // 2. APPROVE
    elseif ($aksi == 'approve') {
        $id = mysqli_real_escape_string($koneksi, $_GET['id']);
        $data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id_buku, status FROM peminjaman WHERE id_peminjaman = '$id' FOR UPDATE"));

        if ($data && $data['status'] == 'Menunggu') {
            $stok_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok FROM buku WHERE id_buku = '{$data['id_buku']}'"));
            if ($stok_data && $stok_data['stok'] > 0) {
                mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Dipinjam' WHERE id_peminjaman = '$id'");
                mysqli_query($koneksi, "UPDATE buku SET stok = stok - 1 WHERE id_buku = '{$data['id_buku']}'");
                mysqli_commit($koneksi);
                header("location: peminjaman.php?pesan=approve_berhasil");
            } else {
                throw new Exception("Stok buku habis!");
            }
        } else {
            throw new Exception("Peminjaman tidak ditemukan atau sudah diproses.");
        }
    }

    // 3. KEMBALIKAN
    elseif ($aksi == 'kembalikan') {
        $id = mysqli_real_escape_string($koneksi, $_GET['id']);
        $data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id_buku, status FROM peminjaman WHERE id_peminjaman = '$id'"));

        if ($data && $data['status'] == 'Dipinjam') {
            mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Kembali', tgl_kembali_asli = NOW() WHERE id_peminjaman = '$id'");
            mysqli_query($koneksi, "UPDATE buku SET stok = stok + 1 WHERE id_buku = '{$data['id_buku']}'");
            mysqli_commit($koneksi);
            header("location: peminjaman.php?pesan=kembali_berhasil");
        }
    }

    // 4. HAPUS SATUAN
    elseif ($aksi == 'hapus') {
        $id = mysqli_real_escape_string($koneksi, $_GET['id']);
        $data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id_buku, status FROM peminjaman WHERE id_peminjaman = '$id'"));

        if ($data && $data['status'] == 'Dipinjam') {
            mysqli_query($koneksi, "UPDATE buku SET stok = stok + 1 WHERE id_buku = '{$data['id_buku']}'");
        }
        mysqli_query($koneksi, "DELETE FROM peminjaman WHERE id_peminjaman = '$id'");
        mysqli_commit($koneksi);
        header("location: peminjaman.php?pesan=hapus_berhasil");
    }

    // 5. HAPUS SEMUA RIWAYAT (KEMBALI DAN DITOLAK)
    elseif ($aksi == 'hapus_semua') {
        $query_hapus = "DELETE FROM peminjaman WHERE status IN ('Kembali', 'Ditolak')";
        
        if (mysqli_query($koneksi, $query_hapus)) {
            mysqli_commit($koneksi);
            header("location: peminjaman.php?pesan=hapus_berhasil");
        } else {
            throw new Exception("Gagal membersihkan riwayat: " . mysqli_error($koneksi));
        }
    }

    else {
        throw new Exception("Aksi tidak valid.");
    }

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    echo "<script>alert('Error: " . $e->getMessage() . "'); window.location='peminjaman.php';</script>";
}
?>