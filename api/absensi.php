<?php
/**
 * POST /api/absensi.php - Check-in/Check-out via scan barcode
 * Body: { "barcode": "...", "tipe": "checkin" | "checkout" }
 * Barcode value = username (NIM) user
 * Requires: Bearer token (admin/karyawan only)
 */
require_once 'config.php';
$id_user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Method not allowed", 405);
}

// Check if the scanner is admin or karyawan
$role = requireStaff($koneksi, $id_user);

$body = getJsonBody();
$barcode = mysqli_real_escape_string($koneksi, $body['barcode'] ?? '');
$tipe = $body['tipe'] ?? 'checkin';

if (empty($barcode)) {
    sendError("Data barcode kosong", 422);
}

if (!in_array($tipe, ['checkin', 'checkout'])) {
    sendError("Tipe tidak valid. Gunakan 'checkin' atau 'checkout'", 422);
}

// Barcode berisi username/NIM user
$query = mysqli_query($koneksi, "SELECT id, username, nama, role FROM users WHERE username = '$barcode'");

if (mysqli_num_rows($query) > 0) {
    $user_data = mysqli_fetch_assoc($query);
    
    // Create checkin_log table if not exists (with tipe column)
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS checkin_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        waktu_checkin DATETIME DEFAULT CURRENT_TIMESTAMP,
        metode VARCHAR(20) DEFAULT 'barcode',
        tipe ENUM('checkin','checkout') DEFAULT 'checkin',
        FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Ensure tipe column exists (for existing tables)
    mysqli_query($koneksi, "ALTER TABLE checkin_log ADD COLUMN IF NOT EXISTS tipe ENUM('checkin','checkout') DEFAULT 'checkin' AFTER metode");
    
    $checkin_user_id = $user_data['id'];
    $waktu = date('Y-m-d H:i:s');
    
    // For checkout: check if user has checked in today
    if ($tipe === 'checkout') {
        $tgl_hari_ini = date('Y-m-d');
        $cek_checkin = mysqli_query($koneksi, "SELECT * FROM checkin_log WHERE id_user = '$checkin_user_id' AND DATE(waktu_checkin) = '$tgl_hari_ini' AND tipe = 'checkin'");
        if (mysqli_num_rows($cek_checkin) == 0) {
            sendError("User belum check-in hari ini. Tidak dapat check-out.", 422);
        }
    }
    
    // Check if already checked in/out today
    $tgl_hari_ini = date('Y-m-d');
    $cek_today = mysqli_query($koneksi, "SELECT * FROM checkin_log WHERE id_user = '$checkin_user_id' AND DATE(waktu_checkin) = '$tgl_hari_ini' AND tipe = '$tipe'");
    $sudah_ada = mysqli_num_rows($cek_today) > 0;
    
    // Insert log
    mysqli_query($koneksi, "INSERT INTO checkin_log (id_user, waktu_checkin, metode, tipe) VALUES ('$checkin_user_id', '$waktu', 'barcode', '$tipe')");
    
    // Get active loans for this user
    $q_loans = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman WHERE id_user = '$checkin_user_id' AND status = 'Dipinjam'");
    $active_loans = mysqli_fetch_assoc($q_loans)['total'] ?? 0;
    
    $label = ($tipe === 'checkin') ? 'Check-in' : 'Check-out';
    $message = $sudah_ada ? "$label ulang berhasil" : "$label berhasil!";
    
    sendOk([
        "user" => [
            "id" => (int)$user_data['id'],
            "username" => $user_data['username'],
            "nama" => $user_data['nama'],
            "role" => $user_data['role']
        ],
        "waktu_checkin" => $waktu,
        "tipe" => $tipe,
        "sudah_checkin_hari_ini" => $sudah_ada,
        "pinjaman_aktif" => (int)$active_loans,
        "message" => $message
    ]);
} else {
    sendError("Barcode/NIM tidak ditemukan dalam sistem", 404);
}
?>
