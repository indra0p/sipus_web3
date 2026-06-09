<?php
/**
 * POST /api/perbarui_token.php
 * Header: Authorization: Bearer <old_token>
 * Response: new token with extended expiry (24h)
 * 
 * Silent refresh — return token baru tanpa redirect
 * Token expired → HTTP 401, BUKAN redirect ke halaman login
 * Mendukung grace period 7 hari untuk expired token
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Method not allowed", 405);
}

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (empty($authHeader) || strpos($authHeader, 'Bearer ') !== 0) {
    sendError("Token tidak ditemukan", 401);
}

$token = substr($authHeader, 7);
$payload = json_decode(base64_decode($token), true);

if (!$payload || !isset($payload['id'])) {
    sendError("Token tidak valid", 401);
}

// Allow refresh even if expired (within 7 days grace period)
if (isset($payload['exp']) && $payload['exp'] < (time() - 7 * 24 * 60 * 60)) {
    sendError("Token terlalu lama kadaluarsa. Silakan login ulang.", 401);
}

$userId = $payload['id'];
$q = mysqli_query($koneksi, "SELECT id, username, nama, role, status FROM users WHERE id = '$userId'");
$user = mysqli_fetch_assoc($q);

if (!$user) {
    sendError("User tidak ditemukan", 404);
}

// Cek apakah user diblokir
if (isset($user['status']) && $user['status'] === 'blocked') {
    sendError("Akun Anda diblokir. Hubungi admin.", 403);
}

$newToken = generateToken($user['id']);

sendOk([
    "token" => $newToken,
    "expires_in" => 86400,
    "user" => [
        "id" => (int)$user['id'],
        "username" => $user['username'],
        "nama" => $user['nama'],
        "role" => $user['role']
    ]
]);
?>
