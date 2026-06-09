<?php
/**
 * SIPUS API Configuration
 * Konfigurasi CORS, koneksi database, dan helper untuk REST API
 * 
 * Response format standar:
 *   Sukses: {"status":"ok","data":{...}}
 *   Error:  {"status":"error","message":"..."}
 */

// === CORS Headers ===
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request — HARUS sebelum logic apapun
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// === Database Connection ===
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_perpus_1";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Koneksi database gagal"]);
    exit();
}

// === Response Helpers ===

/**
 * Kirim response sukses
 * Format: {"status":"ok","data":{...}} atau {"status":"ok","data":{...},"total":N}
 */
function sendOk($data, $statusCode = 200, $extra = []) {
    http_response_code($statusCode);
    $response = ["status" => "ok", "data" => $data];
    if (!empty($extra)) {
        $response = array_merge($response, $extra);
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Kirim response error
 * Format: {"status":"error","message":"..."}
 */
function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(["status" => "error", "message" => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Backward-compatible sendResponse — maps old format to new standard
 * Old: {"success":true,"data":{...}} → New: {"status":"ok","data":{...}}
 * Old: {"success":false,"message":"..."} → New: {"status":"error","message":"..."}
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    
    // Map to new format
    $response = [];
    if (isset($data['success']) && $data['success'] === true) {
        $response['status'] = 'ok';
        // Copy all keys except 'success' and 'message' (message goes into data or as top-level)
        if (isset($data['data'])) {
            $response['data'] = $data['data'];
        }
        // Preserve extra fields like 'total', 'unread', etc
        foreach ($data as $key => $value) {
            if (!in_array($key, ['success', 'data', 'message'])) {
                $response[$key] = $value;
            }
        }
        // If there's a message but no data, put message in data
        if (isset($data['message']) && !isset($data['data'])) {
            $response['data'] = ["message" => $data['message']];
        } elseif (isset($data['message']) && isset($data['data'])) {
            $response['message'] = $data['message'];
        }
    } elseif (isset($data['success']) && $data['success'] === false) {
        $response['status'] = 'error';
        $response['message'] = $data['message'] ?? 'Unknown error';
    } else {
        // Raw data — wrap as ok
        $response['status'] = 'ok';
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

// === Utility Functions ===

function getJsonBody() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?? [];
}

function generateToken($userId) {
    $payload = json_encode(['id' => $userId, 'exp' => time() + (24 * 60 * 60)]);
    return base64_encode($payload);
}

function validateToken($token) {
    $payload = json_decode(base64_decode($token), true);
    if (!$payload || !isset($payload['id']) || !isset($payload['exp'])) return false;
    if ($payload['exp'] < time()) return false;
    return $payload['id'];
}

/**
 * Validasi Bearer token — return user ID atau kirim 401
 * Semua endpoint kecuali masuk.php dan daftar.php wajib panggil ini
 */
function requireAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (empty($authHeader) || strpos($authHeader, 'Bearer ') !== 0) {
        sendError("Token tidak ditemukan", 401);
    }
    $token = substr($authHeader, 7);
    $userId = validateToken($token);
    if (!$userId) {
        sendError("Token tidak valid atau kadaluarsa", 401);
    }
    return $userId;
}

/**
 * Validasi role admin/karyawan
 */
function requireStaff($koneksi, $id_user) {
    $q_role = mysqli_query($koneksi, "SELECT role FROM users WHERE id = '$id_user'");
    $u = mysqli_fetch_assoc($q_role);
    if (!$u || !in_array($u['role'], ['admin', 'karyawan'])) {
        sendError("Akses ditolak. Hanya admin/petugas.", 403);
    }
    return $u['role'];
}

function getBaseUrl() {
    $protocol = "http://";
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        $protocol = "https://";
    }
    $host = $_SERVER['HTTP_HOST'];

    // Get the directory of the current script (e.g., /api)
    $currentDir = dirname($_SERVER['SCRIPT_NAME']);
    // Get the parent directory (the root of the project)
    $parentDir = dirname($currentDir);

    // Clean up slashes
    $parentDir = ($parentDir === DIRECTORY_SEPARATOR || $parentDir === '\\') ? '' : $parentDir;

    return $protocol . $host . $parentDir;
}
?>
