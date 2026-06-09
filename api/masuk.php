<?php
/**
 * POST /api/masuk.php
 * Body: { "username": "...", "password": "..." }
 * Response: token + user data
 * 
 * Endpoint login — TIDAK memerlukan Bearer token
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Method not allowed", 405);
}

$body = getJsonBody();
$username = mysqli_real_escape_string($koneksi, $body['username'] ?? '');
$password = mysqli_real_escape_string($koneksi, $body['password'] ?? '');

if (empty($username) || empty($password)) {
    sendError("Username dan password harus diisi", 422);
}

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND password='$password'");

if (mysqli_num_rows($query) > 0) {
    $data = mysqli_fetch_assoc($query);
    
    // Check if user is blocked
    if (isset($data['status']) && $data['status'] === 'blocked') {
        sendError("Akun Anda diblokir. Hubungi admin.", 403);
    }
    
    $token = generateToken($data['id']);
    $baseUrl = getBaseUrl();
    
    $foto_url = null;
    if (!empty($data['foto'])) {
        $foto_url = $baseUrl . "/assets/img/profil/" . $data['foto'];
    }
    
    sendOk([
        "token" => $token,
        "user" => [
            "id" => (int)$data['id'],
            "username" => $data['username'],
            "nama" => $data['nama'] ?? '',
            "email" => $data['email'] ?? '',
            "jenkel" => $data['jenkel'] ?? '',
            "role" => $data['role'],
            "status" => $data['status'] ?? 'active',
            "foto" => $foto_url
        ]
    ]);
} else {
    sendError("Username atau password salah", 401);
}
?>
