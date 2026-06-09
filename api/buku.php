<?php
/**
 * GET /api/buku.php - Daftar/cari buku
 *   ?keyword=xxx          → Cari buku
 *   ?id=5                 → Detail buku per ID
 *   ?id=5&availability=1  → Cek ketersediaan buku
 * Requires: Bearer token
 */
require_once 'config.php';
$id_user = requireAuth();
$baseUrl = getBaseUrl();

// Single book detail
if (isset($_GET['id'])) {
    $bid = mysqli_real_escape_string($koneksi, $_GET['id']);
    $q = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku = '$bid'");
    $book = mysqli_fetch_assoc($q);
    if (!$book) sendError("Buku tidak ditemukan", 404);

    $sampul_url = !empty($book['sampul']) ? $baseUrl . "/assets/img/sampul/" . $book['sampul'] : null;

    // Count active loans for this book
    $q_loans = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE id_buku = '$bid' AND status = 'Dipinjam'");
    $dipinjam = (int)(mysqli_fetch_assoc($q_loans)['t'] ?? 0);

    // Check if current user is borrowing
    $q_my = mysqli_query($koneksi, "SELECT id_peminjaman, status FROM peminjaman WHERE id_user='$id_user' AND id_buku='$bid' AND status IN ('Dipinjam','Menunggu')");
    $my_loan = mysqli_fetch_assoc($q_my);

    $data = [
        "id_buku" => (int)$book['id_buku'],
        "barcode" => $book['barcode'] ?? null,
        "judul" => $book['judul'],
        "pengarang" => $book['pengarang'] ?? '',
        "penerbit" => $book['penerbit'] ?? '',
        "jenis_buku" => $book['jenis_buku'] ?? '',
        "sinopsis" => $book['sinopsis'] ?? '',
        "stok" => (int)$book['stok'],
        "sampul" => $sampul_url,
        "availability" => [
            "tersedia" => (int)$book['stok'] > 0,
            "stok_tersedia" => (int)$book['stok'],
            "sedang_dipinjam" => $dipinjam
        ],
        "my_loan" => $my_loan ? [
            "id_peminjaman" => (int)$my_loan['id_peminjaman'],
            "status" => $my_loan['status']
        ] : null
    ];

    sendOk($data);
}

// Search/list books
$keyword = mysqli_real_escape_string($koneksi, $_GET['keyword'] ?? '');

if (!empty($keyword)) {
    $sql = "SELECT * FROM buku WHERE 
            judul LIKE '%$keyword%' OR 
            pengarang LIKE '%$keyword%' OR 
            jenis_buku LIKE '%$keyword%' 
            ORDER BY judul ASC";
} else {
    $sql = "SELECT * FROM buku ORDER BY judul ASC";
}

$query = mysqli_query($koneksi, $sql);
$books = [];

while ($row = mysqli_fetch_assoc($query)) {
    $sampul_url = null;
    if (!empty($row['sampul'])) {
        $sampul_url = $baseUrl . "/assets/img/sampul/" . $row['sampul'];
    }
    
    // Cek apakah user sedang meminjam buku ini
    $cek = mysqli_query($koneksi, "SELECT id_peminjaman FROM peminjaman WHERE id_user='$id_user' AND id_buku='{$row['id_buku']}' AND status='Dipinjam'");
    $sedang_pinjam = mysqli_num_rows($cek) > 0;
    
    $books[] = [
        "id_buku" => (int)$row['id_buku'],
        "barcode" => $row['barcode'] ?? null,
        "judul" => $row['judul'],
        "pengarang" => $row['pengarang'] ?? '',
        "penerbit" => $row['penerbit'] ?? '',
        "jenis_buku" => $row['jenis_buku'] ?? '',
        "sinopsis" => $row['sinopsis'] ?? '',
        "stok" => (int)$row['stok'],
        "sampul" => $sampul_url,
        "sedang_dipinjam" => $sedang_pinjam
    ];
}

sendOk($books, 200, ["total" => count($books)]);
?>
