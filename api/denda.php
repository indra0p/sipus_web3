<?php
/**
 * API Denda untuk Patron
 * GET  ?action=my_fines|history|simulate&id_penalty=N
 * POST action: pay, simulate_pay, dispute
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'my_fines';
    $tgl_now = date('Y-m-d');

    if ($action === 'my_fines') {
        $q = mysqli_query($koneksi, "SELECT pen.*, b.judul FROM penalties pen LEFT JOIN buku b ON pen.id_buku = b.id_buku WHERE pen.id_user = '$id_user' ORDER BY pen.created_at DESC");
        $fines = [];
        while ($r = mysqli_fetch_assoc($q)) {
            $fines[] = ["id"=>(int)$r['id'],"tipe_denda"=>$r['tipe_denda'],"jumlah"=>(float)$r['jumlah'],"jumlah_dibayar"=>(float)$r['jumlah_dibayar'],"sisa"=>(float)($r['jumlah']-$r['jumlah_dibayar']),"status"=>$r['status'],"buku"=>$r['judul'],"catatan"=>$r['catatan'],"catatan_dispute"=>$r['catatan_dispute'],"created_at"=>$r['created_at'],"resolved_at"=>$r['resolved_at']];
        }
        $q_ov = mysqli_query($koneksi, "SELECT p.id_peminjaman, p.tgl_kembali_seharusnya, b.judul FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku WHERE p.id_user = '$id_user' AND p.status = 'Dipinjam' AND p.tgl_kembali_seharusnya < '$tgl_now'");
        $overdue = []; $total_overdue = 0;
        while ($r = mysqli_fetch_assoc($q_ov)) {
            $days = (new DateTime($tgl_now))->diff(new DateTime($r['tgl_kembali_seharusnya']))->days;
            $denda = $days * 2000; $total_overdue += $denda;
            $overdue[] = ["id_peminjaman"=>(int)$r['id_peminjaman'],"judul"=>$r['judul'],"hari_terlambat"=>$days,"denda"=>$denda];
        }
        $q_sum = mysqli_query($koneksi, "SELECT SUM(jumlah - jumlah_dibayar) as sisa FROM penalties WHERE id_user = '$id_user' AND status IN ('unpaid','partial')");
        $sisa_recorded = (float)(mysqli_fetch_assoc($q_sum)['sisa'] ?? 0);
        sendOk(["total_denda"=>$total_overdue+$sisa_recorded,"overdue_berjalan"=>$total_overdue,"denda_tercatat"=>$sisa_recorded,"overdue_detail"=>$overdue,"penalties"=>$fines]);

    } elseif ($action === 'history') {
        $q = mysqli_query($koneksi, "SELECT pay.*, pen.tipe_denda FROM payments pay JOIN penalties pen ON pay.id_penalty = pen.id WHERE pay.id_user = '$id_user' ORDER BY pay.created_at DESC");
        $payments = [];
        while ($r = mysqli_fetch_assoc($q)) {
            $payments[] = ["id"=>(int)$r['id'],"id_penalty"=>(int)$r['id_penalty'],"tipe_denda"=>$r['tipe_denda'],"jumlah"=>(float)$r['jumlah'],"metode_bayar"=>$r['metode_bayar'],"catatan"=>$r['catatan'],"created_at"=>$r['created_at']];
        }
        sendOk($payments, 200, ["total"=>count($payments)]);

    } elseif ($action === 'simulate') {
        $id_penalty = mysqli_real_escape_string($koneksi, $_GET['id_penalty'] ?? '');
        if (empty($id_penalty)) sendError("ID penalty diperlukan", 422);
        $q = mysqli_query($koneksi, "SELECT pen.*, b.judul FROM penalties pen LEFT JOIN buku b ON pen.id_buku = b.id_buku WHERE pen.id = '$id_penalty' AND pen.id_user = '$id_user'");
        $pen = mysqli_fetch_assoc($q);
        if (!$pen) sendError("Denda tidak ditemukan", 404);
        $sisa = (float)($pen['jumlah'] - $pen['jumlah_dibayar']);
        sendOk(["id_penalty"=>(int)$pen['id'],"tipe_denda"=>$pen['tipe_denda'],"buku"=>$pen['judul'],"jumlah_total"=>(float)$pen['jumlah'],"jumlah_dibayar"=>(float)$pen['jumlah_dibayar'],"sisa_bayar"=>$sisa,"status"=>$pen['status'],"bisa_dispute"=>in_array($pen['status'],['unpaid','partial']),"bisa_bayar"=>in_array($pen['status'],['unpaid','partial'])]);
    } else {
        sendError("Action tidak valid", 422);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? '';

    if ($action === 'dispute') {
        $id_penalty = mysqli_real_escape_string($koneksi, $body['id_penalty'] ?? '');
        $alasan = mysqli_real_escape_string($koneksi, $body['alasan'] ?? '');
        if (empty($id_penalty) || empty($alasan)) sendError("ID penalty dan alasan wajib diisi", 422);
        $q = mysqli_query($koneksi, "SELECT * FROM penalties WHERE id = '$id_penalty' AND id_user = '$id_user' AND status IN ('unpaid','partial')");
        if (mysqli_num_rows($q) == 0) sendError("Denda tidak ditemukan atau tidak dapat di-dispute", 404);
        mysqli_query($koneksi, "UPDATE penalties SET status = 'disputed', catatan_dispute = '$alasan' WHERE id = '$id_penalty'");
        sendOk(["id_penalty"=>(int)$id_penalty,"status"=>"disputed","message"=>"Keberatan denda berhasil diajukan"]);

    } elseif ($action === 'simulate_pay') {
        $id_penalty = mysqli_real_escape_string($koneksi, $body['id_penalty'] ?? '');
        $jumlah = (float)($body['jumlah'] ?? 0);
        if (empty($id_penalty) || $jumlah <= 0) sendError("ID penalty dan jumlah wajib diisi", 422);
        $q = mysqli_query($koneksi, "SELECT * FROM penalties WHERE id = '$id_penalty' AND id_user = '$id_user' AND status IN ('unpaid','partial')");
        $pen = mysqli_fetch_assoc($q);
        if (!$pen) sendError("Denda tidak ditemukan", 404);
        $sisa = (float)($pen['jumlah'] - $pen['jumlah_dibayar']);
        $bayar_efektif = min($jumlah, $sisa);
        $sisa_setelah = $sisa - $bayar_efektif;
        sendOk(["simulasi"=>true,"id_penalty"=>(int)$id_penalty,"jumlah_diminta"=>$jumlah,"jumlah_efektif"=>$bayar_efektif,"sisa_sebelum"=>$sisa,"sisa_setelah"=>$sisa_setelah,"status_setelah"=>($sisa_setelah<=0)?'paid':'partial']);

    } elseif ($action === 'pay') {
        $id_penalty = mysqli_real_escape_string($koneksi, $body['id_penalty'] ?? '');
        $jumlah = (float)($body['jumlah'] ?? 0);
        if (empty($id_penalty) || $jumlah <= 0) sendError("ID penalty dan jumlah wajib diisi", 422);
        $q = mysqli_query($koneksi, "SELECT * FROM penalties WHERE id = '$id_penalty' AND id_user = '$id_user' AND status IN ('unpaid','partial')");
        $pen = mysqli_fetch_assoc($q);
        if (!$pen) sendError("Denda tidak ditemukan", 404);
        $sisa = $pen['jumlah'] - $pen['jumlah_dibayar'];
        if ($jumlah > $sisa) $jumlah = $sisa;
        $new_paid = $pen['jumlah_dibayar'] + $jumlah;
        $new_status = ($new_paid >= $pen['jumlah']) ? 'paid' : 'partial';
        $resolved = ($new_status == 'paid') ? "'" . date('Y-m-d H:i:s') . "'" : "NULL";
        mysqli_query($koneksi, "UPDATE penalties SET jumlah_dibayar = '$new_paid', status = '$new_status', resolved_at = $resolved WHERE id = '$id_penalty'");
        mysqli_query($koneksi, "INSERT INTO payments (id_penalty, id_user, jumlah, metode_bayar) VALUES ('$id_penalty', '$id_user', '$jumlah', 'online')");
        sendOk(["id_penalty"=>(int)$id_penalty,"jumlah_dibayar"=>(float)$jumlah,"status"=>$new_status,"sisa"=>(float)($pen['jumlah']-$new_paid),"message"=>"Pembayaran berhasil dicatat"]);
    } else {
        sendError("Action tidak valid. Gunakan 'dispute', 'pay', atau 'simulate_pay'", 422);
    }
} else {
    sendError("Method not allowed", 405);
}
?>
