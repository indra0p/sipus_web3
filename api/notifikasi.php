<?php
/**
 * API Notifikasi
 * GET  /api/notifikasi.php         → Daftar notifikasi user (?limit=20)
 * PUT  /api/notifikasi.php         → Tandai dibaca (mark_all / mark_one)
 * POST /api/notifikasi.php         → Simpan FCM device token / kirim push
 *   { "action": "register_device", "fcm_token": "...", "device_name": "..." }
 *   { "action": "unregister_device", "fcm_token": "..." }
 *   { "action": "send_push", "id_user_target": N, "judul": "...", "pesan": "..." } (admin)
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = (int)($_GET['limit'] ?? 50);
    $q = mysqli_query($koneksi, "SELECT * FROM notifications WHERE id_user = '$id_user' ORDER BY created_at DESC LIMIT $limit");
    $notifications = []; $unread = 0;
    while ($r = mysqli_fetch_assoc($q)) {
        if (!$r['is_read']) $unread++;
        $notifications[] = ["id"=>(int)$r['id'],"judul"=>$r['judul'],"pesan"=>$r['pesan'],"tipe"=>$r['tipe'],"is_read"=>(bool)$r['is_read'],"link"=>$r['link'],"created_at"=>$r['created_at']];
    }
    sendOk($notifications, 200, ["unread"=>$unread,"total"=>count($notifications)]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $body = getJsonBody();
    $action = $body['action'] ?? 'mark_all';
    if ($action === 'mark_all') {
        mysqli_query($koneksi, "UPDATE notifications SET is_read = 1 WHERE id_user = '$id_user'");
        sendOk(["message"=>"Semua notifikasi ditandai dibaca"]);
    } elseif ($action === 'mark_one') {
        $nid = mysqli_real_escape_string($koneksi, $body['id'] ?? '');
        if (empty($nid)) sendError("ID notifikasi diperlukan", 422);
        mysqli_query($koneksi, "UPDATE notifications SET is_read = 1 WHERE id = '$nid' AND id_user = '$id_user'");
        sendOk(["message"=>"Notifikasi ditandai dibaca"]);
    } else {
        sendError("Action tidak valid", 422);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? '';

    if ($action === 'register_device') {
        // Simpan FCM device token
        $fcm_token = mysqli_real_escape_string($koneksi, $body['fcm_token'] ?? '');
        $device_name = mysqli_real_escape_string($koneksi, $body['device_name'] ?? 'Unknown');
        if (empty($fcm_token)) sendError("fcm_token wajib diisi", 422);

        // Ensure table exists
        mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS fcm_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NOT NULL,
            fcm_token TEXT NOT NULL,
            device_name VARCHAR(100) DEFAULT 'Unknown',
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Upsert: deactivate old tokens for this device, insert new
        mysqli_query($koneksi, "DELETE FROM fcm_tokens WHERE fcm_token = '$fcm_token'");
        mysqli_query($koneksi, "INSERT INTO fcm_tokens (id_user, fcm_token, device_name) VALUES ('$id_user', '$fcm_token', '$device_name')");
        sendOk(["message"=>"Device token berhasil disimpan","device_name"=>$device_name]);

    } elseif ($action === 'unregister_device') {
        $fcm_token = mysqli_real_escape_string($koneksi, $body['fcm_token'] ?? '');
        if (empty($fcm_token)) sendError("fcm_token wajib diisi", 422);
        mysqli_query($koneksi, "DELETE FROM fcm_tokens WHERE fcm_token = '$fcm_token' AND id_user = '$id_user'");
        sendOk(["message"=>"Device token berhasil dihapus"]);

    } elseif ($action === 'send_push') {
        // Admin only: kirim push notification
        $role = requireStaff($koneksi, $id_user);
        $target = mysqli_real_escape_string($koneksi, $body['id_user_target'] ?? '');
        $judul = mysqli_real_escape_string($koneksi, $body['judul'] ?? '');
        $pesan = mysqli_real_escape_string($koneksi, $body['pesan'] ?? '');
        if (empty($target) || empty($judul) || empty($pesan)) sendError("id_user_target, judul, dan pesan wajib diisi", 422);

        // Simpan ke tabel notifications
        mysqli_query($koneksi, "INSERT INTO notifications (id_user, judul, pesan, tipe) VALUES ('$target', '$judul', '$pesan', 'system')");

        // Ambil FCM tokens user target
        $q_tokens = mysqli_query($koneksi, "SELECT fcm_token FROM fcm_tokens WHERE id_user = '$target' AND is_active = 1");
        $tokens = [];
        while ($r = mysqli_fetch_assoc($q_tokens)) {
            $tokens[] = $r['fcm_token'];
        }

        // Kirim via FCM (jika FCM server key dikonfigurasi)
        $fcm_sent = 0;
        $fcm_key = getenv('FCM_SERVER_KEY');
        if (!empty($fcm_key) && !empty($tokens)) {
            foreach ($tokens as $tk) {
                $payload = json_encode(["to"=>$tk,"notification"=>["title"=>$judul,"body"=>$pesan],"data"=>["tipe"=>"system"]]);
                $ch = curl_init('https://fcm.googleapis.com/fcm/send');
                curl_setopt_array($ch, [CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>["Authorization: key=$fcm_key","Content-Type: application/json"],CURLOPT_POSTFIELDS=>$payload,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>5]);
                $res = curl_exec($ch); curl_close($ch);
                if ($res) $fcm_sent++;
            }
        }

        sendOk(["message"=>"Notifikasi terkirim","notification_saved"=>true,"fcm_sent"=>$fcm_sent,"fcm_targets"=>count($tokens)]);

    } else {
        sendError("Action tidak valid. Gunakan 'register_device', 'unregister_device', atau 'send_push'", 422);
    }

} else {
    sendError("Method not allowed", 405);
}
?>
