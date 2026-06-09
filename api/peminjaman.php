<?php
/**
 * API Peminjaman - Endpoint lengkap untuk peminjaman buku
 * 
 * GET  /api/peminjaman.php
 *   ?filter=requests    → Pengajuan saja (Menunggu, Ditolak)
 *   ?filter=active      → Pinjaman aktif (Dipinjam)
 *   ?filter=returns     → Pengajuan kembali (Pengajuan_Kembali)
 *   ?action=status&id=N → Status pengembalian spesifik
 *   (no filter)         → Semua status aktif
 * 
 * POST /api/peminjaman.php - Ajukan peminjaman baru (status = Menunggu)
 *   Body: { "id_buku": 25 }
 * 
 * PUT  /api/peminjaman.php - Ajukan pengembalian (request return)
 *   Body: { "id_peminjaman": 108 }
 *   Body: { "id_peminjaman": 108, "kondisi": "baik|rusak_ringan|rusak_berat|hilang" }
 * 
 * PATCH /api/peminjaman.php - Ajukan perpanjangan
 *   Body: { "id_peminjaman": 108 }
 * 
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();
$baseUrl = getBaseUrl();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    // === Action: status pengembalian spesifik ===
    if ($action === 'status') {
        $id = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');
        if (empty($id)) {
            sendError("ID peminjaman diperlukan", 422);
        }

        $q = mysqli_query($koneksi, "SELECT p.*, b.judul, b.pengarang, b.sampul 
            FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku 
            WHERE p.id_peminjaman = '$id' AND p.id_user = '$id_user'");
        $row = mysqli_fetch_assoc($q);

        if (!$row) {
            sendError("Peminjaman tidak ditemukan", 404);
        }

        $sampul_url = !empty($row['sampul']) ? $baseUrl . "/assets/img/sampul/" . $row['sampul'] : null;

        // Hitung denda berjalan
        $denda_berjalan = 0;
        $hari_terlambat = 0;
        $tgl_now = date('Y-m-d');
        if (in_array($row['status'], ['Dipinjam', 'Pengajuan_Kembali']) && $tgl_now > $row['tgl_kembali_seharusnya']) {
            $hari_terlambat = (new DateTime($tgl_now))->diff(new DateTime($row['tgl_kembali_seharusnya']))->days;
            $denda_berjalan = $hari_terlambat * 2000;
        }

        // Status pengembalian detail
        $status_label = match($row['status']) {
            'Menunggu' => 'Menunggu persetujuan peminjaman',
            'Dipinjam' => 'Sedang dipinjam',
            'Pengajuan_Kembali' => 'Menunggu persetujuan pengembalian',
            'Kembali' => 'Sudah dikembalikan',
            'Ditolak' => 'Pengajuan ditolak',
            default => $row['status']
        };

        sendOk([
            "id_peminjaman" => (int)$row['id_peminjaman'],
            "judul" => $row['judul'],
            "pengarang" => $row['pengarang'],
            "sampul" => $sampul_url,
            "tgl_pinjam" => $row['tgl_pinjam'],
            "tgl_kembali_seharusnya" => $row['tgl_kembali_seharusnya'],
            "tgl_kembali_asli" => $row['tgl_kembali_asli'],
            "status" => $row['status'],
            "status_label" => $status_label,
            "kondisi_kembali" => $row['kondisi_kembali'] ?? null,
            "perpanjangan_status" => $row['perpanjangan_status'] ?? 'none',
            "denda_tercatat" => (int)($row['denda'] ?? 0),
            "denda_berjalan" => $denda_berjalan,
            "hari_terlambat" => $hari_terlambat,
            "catatan_admin" => $row['catatan_admin']
        ]);
    }

    // === Default: daftar peminjaman user ===
    $filter = $_GET['filter'] ?? '';
    $statusFilter = "('Menunggu','Dipinjam','Pengajuan_Kembali','Ditolak')";
    if ($filter === 'requests') $statusFilter = "('Menunggu','Ditolak')";
    elseif ($filter === 'active') $statusFilter = "('Dipinjam')";
    elseif ($filter === 'returns') $statusFilter = "('Pengajuan_Kembali')";

    $sql = "SELECT p.*, b.judul, b.pengarang, b.sampul, b.jenis_buku
            FROM peminjaman p 
            JOIN buku b ON p.id_buku = b.id_buku 
            WHERE p.id_user = '$id_user' AND p.status IN $statusFilter
            ORDER BY p.id_peminjaman DESC";
    $query = mysqli_query($koneksi, $sql);
    $loans = [];
    
    while ($row = mysqli_fetch_assoc($query)) {
        $sampul_url = null;
        if (!empty($row['sampul'])) {
            $sampul_url = $baseUrl . "/assets/img/sampul/" . $row['sampul'];
        }
        
        // Calculate running fine (only for Dipinjam status)
        $denda_berjalan = 0;
        $sisa_hari = 0;
        $tgl_now = date('Y-m-d');
        
        if ($row['status'] === 'Dipinjam') {
            if ($tgl_now > $row['tgl_kembali_seharusnya']) {
                $tgl1 = new DateTime($row['tgl_kembali_seharusnya']);
                $tgl2 = new DateTime($tgl_now);
                $denda_berjalan = $tgl2->diff($tgl1)->days * 2000;
            }
            
            // Days remaining
            $tgl_kembali = new DateTime($row['tgl_kembali_seharusnya']);
            $tgl_sekarang = new DateTime($tgl_now);
            $sisa_hari = (int)$tgl_sekarang->diff($tgl_kembali)->format('%r%a');
        }
        
        $loans[] = [
            "id_peminjaman" => (int)$row['id_peminjaman'],
            "id_buku" => (int)$row['id_buku'],
            "judul" => $row['judul'],
            "pengarang" => $row['pengarang'],
            "jenis_buku" => $row['jenis_buku'],
            "sampul" => $sampul_url,
            "tgl_pinjam" => $row['tgl_pinjam'],
            "tgl_kembali_seharusnya" => $row['tgl_kembali_seharusnya'],
            "status" => $row['status'],
            "perpanjangan_status" => $row['perpanjangan_status'] ?? 'none',
            "kondisi_kembali" => $row['kondisi_kembali'] ?? null,
            "sisa_hari" => $sisa_hari,
            "denda_berjalan" => $denda_berjalan,
            "catatan_admin" => $row['catatan_admin']
        ];
    }
    
    sendOk($loans, 200, ["total" => count($loans)]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new loan request (Menunggu approval)
    $body = getJsonBody();
    $id_buku = mysqli_real_escape_string($koneksi, $body['id_buku'] ?? '');
    
    if (empty($id_buku)) {
        sendError("ID buku harus diisi", 422);
    }
    
    // Check if already borrowing or pending
    $cek_pinjam = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id_user='$id_user' AND id_buku='$id_buku' AND status IN ('Dipinjam','Menunggu')");
    if (mysqli_num_rows($cek_pinjam) > 0) {
        sendError("Anda sudah meminjam atau mengajukan buku ini", 409);
    }
    
    // Check stock
    $cek_stok = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id_buku = '$id_buku'");
    $data_buku = mysqli_fetch_assoc($cek_stok);
    
    if (!$data_buku) {
        sendError("Buku tidak ditemukan", 404);
    }
    
    if ($data_buku['stok'] <= 0) {
        sendError("Stok buku habis", 422);
    }
    
    // Create loan with status Menunggu (NO stock reduction yet - wait for approval)
    $tgl_pinjam = date('Y-m-d');
    $tgl_kembali = date('Y-m-d', strtotime('+7 days'));
    
    $sql = "INSERT INTO peminjaman (id_user, id_buku, tgl_pinjam, tgl_kembali_seharusnya, status) 
            VALUES ('$id_user', '$id_buku', '$tgl_pinjam', '$tgl_kembali', 'Menunggu')";
    
    if (mysqli_query($koneksi, $sql)) {
        sendOk([
            "id_peminjaman" => (int)mysqli_insert_id($koneksi),
            "tgl_pinjam" => $tgl_pinjam,
            "tgl_kembali_seharusnya" => $tgl_kembali,
            "message" => "Pengajuan peminjaman berhasil dikirim. Menunggu persetujuan admin."
        ], 201);
    } else {
        sendError("Gagal mengajukan peminjaman", 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Request return for an active loan (with optional book condition)
    $body = getJsonBody();
    $id_peminjaman = mysqli_real_escape_string($koneksi, $body['id_peminjaman'] ?? '');
    $kondisi = mysqli_real_escape_string($koneksi, $body['kondisi'] ?? '');
    
    if (empty($id_peminjaman)) {
        sendError("ID peminjaman harus diisi", 422);
    }
    
    // Validate kondisi if provided
    $valid_kondisi = ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'];
    if (!empty($kondisi) && !in_array($kondisi, $valid_kondisi)) {
        sendError("Kondisi tidak valid. Gunakan: " . implode(', ', $valid_kondisi), 422);
    }
    
    // Verify ownership and status
    $cek = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id_peminjaman='$id_peminjaman' AND id_user='$id_user' AND status='Dipinjam'");
    if (mysqli_num_rows($cek) == 0) {
        sendError("Peminjaman tidak ditemukan atau sudah diproses", 404);
    }
    
    // Update status to Pengajuan_Kembali + kondisi
    $kondisi_sql = !empty($kondisi) ? ", kondisi_kembali='$kondisi'" : "";
    $update = mysqli_query($koneksi, "UPDATE peminjaman SET status='Pengajuan_Kembali'$kondisi_sql WHERE id_peminjaman='$id_peminjaman'");
    
    if ($update) {
        sendOk([
            "id_peminjaman" => (int)$id_peminjaman,
            "status" => "Pengajuan_Kembali",
            "kondisi" => !empty($kondisi) ? $kondisi : null,
            "message" => "Pengajuan pengembalian berhasil. Menunggu persetujuan admin."
        ]);
    } else {
        sendError("Gagal mengajukan pengembalian", 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    // Request renewal for an active loan
    $body = getJsonBody();
    $id_peminjaman = mysqli_real_escape_string($koneksi, $body['id_peminjaman'] ?? '');
    if (empty($id_peminjaman)) {
        sendError("ID peminjaman harus diisi", 422);
    }
    $cek = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id_peminjaman='$id_peminjaman' AND id_user='$id_user' AND status='Dipinjam'");
    if (mysqli_num_rows($cek) == 0) {
        sendError("Peminjaman tidak ditemukan atau tidak dapat diperpanjang", 404);
    }
    $loan = mysqli_fetch_assoc($cek);
    $perp = $loan['perpanjangan_status'] ?? 'none';
    if ($perp === 'requested') {
        sendError("Pengajuan perpanjangan sudah terkirim, menunggu approval", 409);
    }
    mysqli_query($koneksi, "UPDATE peminjaman SET perpanjangan_status='requested' WHERE id_peminjaman='$id_peminjaman'");
    sendOk([
        "id_peminjaman" => (int)$id_peminjaman,
        "perpanjangan_status" => "requested",
        "message" => "Pengajuan perpanjangan berhasil dikirim. Menunggu persetujuan admin."
    ]);

} else {
    sendError("Method not allowed", 405);
}
?>
