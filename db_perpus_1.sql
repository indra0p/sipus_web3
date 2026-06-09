-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 24, 2026 at 10:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_perpus_1`
--

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id_buku` int(11) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `judul` varchar(255) NOT NULL,
  `pengarang` varchar(100) DEFAULT NULL,
  `penerbit` varchar(255) DEFAULT NULL,
  `jenis_buku` varchar(50) DEFAULT NULL,
  `sinopsis` text DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `sampul` varchar(255) DEFAULT NULL,
  `file_ebook` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id_buku`, `barcode`, `judul`, `pengarang`, `penerbit`, `jenis_buku`, `sinopsis`, `stok`, `sampul`) VALUES
(25, 'BK000025', 'kitab rongawi ANTI SUKI', 'ambaboy', 'NGAWI BOOK', 'Informatika', 'Nama Ambaruwo terdengar asing sekaligus menarik karena mengandung nuansa lokal yang kuat dan menimbulkan rasa penasaran bagi para penikmat kisah supranatural. \r\n\r\nIde cerita yang diangkat dalam film itu menjadi titik awal pembahasan tentang bagaimana sebuah legenda atau tokoh rekaan dapat membangun atmosfer ketegangan di dunia sinema independen.', 27, '997231469_Screenshot 2026-04-17 165535.png'),
(29, 'BK000029', 'RAHASIA SUKSES KHOLID', 'KHOLID', 'NGAWI BOOK', 'Informatika', 'menekankan bahwa keberhasilan bukanlah hal instan, melainkan hasil proses, perjuangan, dan ketekunan.', 9, '1492044031_SUKSESS.png'),
(30, 'BK000030', 'TIPS AND TRIK MENJADI HACKER DALAM SEMENIT', 'reja auditore', 'NGAWI BOOK', 'Informatika', 'Buku ini bertujuan mulia untuk membantu pembaca memahami ilmu hacking agar tidak menjadi korban peretasan oleh pihak yang tidak bertanggung jawab. Buku ini sering kali membedah teknik dasar hingga lanjut yang digunakan peretas untuk menembus pertahanan sisteM', 106, '2113786467_menjadi hacker.png'),
(31, 'BK000031', 'BIOGRAFI SANG PRESIDEN', 'jkw', 'NGAWI BOOK', 'Umum', 'Buku ini mengisahkan perjalanan sang presiden kholid mashuri', 0, '600671835_1.png');

-- --------------------------------------------------------

--
-- Table structure for table `checkin_log`
--

CREATE TABLE `checkin_log` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `waktu_checkin` datetime DEFAULT current_timestamp(),
  `metode` varchar(20) DEFAULT 'barcode',
  `tipe` enum('checkin','checkout') DEFAULT 'checkin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checkin_log`
--

INSERT INTO `checkin_log` (`id`, `id_user`, `waktu_checkin`, `metode`, `tipe`) VALUES
(1, 12, '2026-05-23 12:23:01', 'barcode', 'checkin'),
(2, 12, '2026-05-23 12:24:07', 'barcode', 'checkout');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `tipe` enum('info','approval','overdue','fine','checkin','system') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `id_user`, `judul`, `pesan`, `tipe`, `is_read`, `link`, `created_at`) VALUES
(1, 12, 'Peminjaman Disetujui', 'Pengajuan peminjaman buku Anda telah disetujui. Silakan ambil buku di perpustakaan.', 'approval', 0, 'peminjaman_saya.php', '2026-05-23 10:24:02'),
(2, 12, 'Pengembalian Disetujui', 'Buku berhasil dikembalikan. Denda: Rp 0', 'approval', 0, 'peminjaman_saya.php', '2026-05-23 10:39:37'),
(3, 12, 'Peminjaman Disetujui', 'Pengajuan peminjaman buku Anda telah disetujui. Silakan ambil buku di perpustakaan.', 'approval', 0, 'peminjaman_saya.php', '2026-05-23 10:43:39'),
(4, 12, 'Perpanjangan Disetujui', 'Masa pinjam diperpanjang hingga 06 Jun 2026', 'approval', 0, 'peminjaman_saya.php', '2026-05-23 11:31:01'),
(5, 12, 'Peminjaman Disetujui', 'Pengajuan peminjaman buku Anda telah disetujui. Silakan ambil buku di perpustakaan.', 'approval', 0, 'peminjaman_saya.php', '2026-05-23 11:31:58'),
(6, 12, 'Perpanjangan Disetujui', 'Masa pinjam diperpanjang hingga 06 Jun 2026', 'approval', 0, 'peminjaman_saya.php', '2026-05-23 11:32:14'),
(7, 12, 'Pengembalian Disetujui', 'Buku berhasil dikembalikan. Denda: Rp 0', 'approval', 0, 'peminjaman_saya.php', '2026-05-23 11:32:21'),
(8, 12, 'Pengembalian Disetujui', 'Buku berhasil dikembalikan. Denda: Rp 0', 'approval', 0, 'peminjaman_saya.php', '2026-05-23 16:59:27'),
(9, 12, 'Peminjaman Disetujui', 'Pengajuan peminjaman buku Anda telah disetujui. Silakan ambil buku di perpustakaan.', 'approval', 0, 'peminjaman_saya.php', '2026-05-23 16:59:37');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `id_penalty` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  `metode_bayar` enum('cash','transfer','online') DEFAULT 'cash',
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id_peminjaman` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_buku` int(11) DEFAULT NULL,
  `tgl_pinjam` date NOT NULL,
  `tgl_kembali_seharusnya` date NOT NULL,
  `tgl_kembali_asli` date DEFAULT NULL,
  `status` enum('Menunggu','Dipinjam','Ditolak','Pengajuan_Kembali','Kembali') DEFAULT 'Menunggu',
  `denda` int(11) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `catatan_admin` varchar(255) DEFAULT NULL,
  `kondisi_kembali` enum('baik','rusak_ringan','rusak_berat','hilang') DEFAULT NULL,
  `perpanjangan_status` enum('none','requested','approved','rejected') DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id_peminjaman`, `id_user`, `id_buku`, `tgl_pinjam`, `tgl_kembali_seharusnya`, `tgl_kembali_asli`, `status`, `denda`, `approved_by`, `approved_at`, `catatan_admin`, `kondisi_kembali`, `perpanjangan_status`) VALUES
(108, 8, 25, '2026-05-12', '2026-05-19', '2026-05-12', 'Kembali', 0, NULL, NULL, NULL, NULL, 'none'),
(109, 8, 30, '2026-05-12', '2026-05-19', '2026-05-12', 'Kembali', 0, NULL, NULL, NULL, NULL, 'none'),
(110, 8, 29, '2026-05-04', '2026-05-11', '2026-05-17', 'Kembali', 12000, NULL, NULL, NULL, NULL, 'none'),
(111, 9, 25, '2026-05-17', '2026-05-24', '2026-05-17', 'Kembali', 0, NULL, NULL, NULL, NULL, 'none'),
(112, 9, 25, '2026-05-17', '2026-05-24', '2026-05-17', 'Kembali', 0, NULL, NULL, NULL, NULL, 'none'),
(113, 9, 31, '2026-05-17', '2026-05-24', '2026-05-20', 'Kembali', 0, NULL, NULL, NULL, NULL, 'none'),
(114, 12, 25, '2026-05-20', '2026-05-27', '2026-05-20', 'Kembali', 0, 12, '2026-05-20 02:53:45', NULL, NULL, 'none'),
(115, 12, 30, '2026-05-20', '2026-05-27', '2026-05-20', 'Kembali', 0, 12, '2026-05-20 02:53:43', NULL, NULL, 'none'),
(116, 12, 25, '2026-05-20', '2026-05-27', '2026-05-20', 'Kembali', 0, 12, '2026-05-20 03:06:07', NULL, NULL, 'none'),
(117, 12, 25, '2026-05-20', '2026-05-27', '2026-05-20', 'Kembali', 0, 12, '2026-05-20 03:06:04', NULL, NULL, 'none'),
(118, 12, 25, '2026-05-20', '2026-05-27', '2026-05-20', 'Kembali', 0, 12, '2026-05-20 04:14:14', NULL, NULL, 'none'),
(119, 12, 29, '2026-05-20', '2026-05-27', '2026-05-20', 'Kembali', 0, 12, '2026-05-20 04:14:12', NULL, NULL, 'none'),
(120, 12, 30, '2026-05-20', '2026-05-27', '2026-05-20', 'Kembali', 0, 12, '2026-05-20 04:14:10', NULL, NULL, 'none'),
(121, 12, 25, '2026-05-23', '2026-05-30', '2026-05-23', 'Kembali', 0, 12, '2026-05-23 05:39:37', NULL, NULL, 'none'),
(122, 12, 30, '2026-05-23', '2026-06-06', '2026-05-23', 'Kembali', 0, 12, '2026-05-23 06:32:21', NULL, NULL, 'approved'),
(123, 12, 29, '2026-05-23', '2026-06-06', '2026-05-23', 'Kembali', 0, 12, '2026-05-23 11:59:27', NULL, 'baik', 'approved'),
(124, 12, 30, '2026-05-23', '2026-05-30', NULL, 'Dipinjam', 0, 13, '2026-05-23 12:03:24', '', NULL, 'none'),
(125, 12, 25, '2026-05-23', '2026-05-30', NULL, 'Dipinjam', 0, 12, '2026-05-23 11:59:37', NULL, NULL, 'none');

--
-- Triggers `peminjaman`
--
DELIMITER $$
CREATE TRIGGER `trg_pinjam_buku` AFTER INSERT ON `peminjaman` FOR EACH ROW BEGIN
    UPDATE buku SET stok = stok - 1 WHERE id_buku = NEW.id_buku;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `penalties`
--

CREATE TABLE `penalties` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_peminjaman` int(11) DEFAULT NULL,
  `id_buku` int(11) DEFAULT NULL,
  `tipe_denda` enum('overdue','damage','lost') NOT NULL,
  `jumlah` decimal(12,2) NOT NULL DEFAULT 0.00,
  `jumlah_dibayar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('unpaid','partial','paid','waived','disputed') DEFAULT 'unpaid',
  `catatan` text DEFAULT NULL,
  `catatan_dispute` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty_rules`
--

CREATE TABLE `penalty_rules` (
  `id` int(11) NOT NULL,
  `tipe_denda` enum('overdue','damage','lost') NOT NULL,
  `nama_aturan` varchar(100) NOT NULL,
  `tarif` decimal(12,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(20) DEFAULT 'per_hari',
  `deskripsi` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penalty_rules`
--

INSERT INTO `penalty_rules` (`id`, `tipe_denda`, `nama_aturan`, `tarif`, `satuan`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'overdue', 'Denda Keterlambatan', 2000.00, 'per_hari', 'Denda Rp 2.000 per hari keterlambatan pengembalian buku', 1, '2026-05-23 10:22:27', '2026-05-23 10:22:27'),
(2, 'damage', 'Denda Kerusakan Ringan', 25000.00, 'flat', 'Denda untuk kerusakan ringan (halaman sobek, kotor, dll)', 1, '2026-05-23 10:22:27', '2026-05-23 10:22:27'),
(3, 'damage', 'Denda Kerusakan Berat', 75000.00, 'flat', 'Denda untuk kerusakan berat (sampul rusak, halaman hilang, dll)', 1, '2026-05-23 10:22:27', '2026-05-23 10:22:27'),
(4, 'lost', 'Denda Buku Hilang', 0.00, 'replacement', 'Biaya penggantian buku + biaya administrasi Rp 25.000', 1, '2026-05-23 10:22:27', '2026-05-23 10:22:27');

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id_pengembalian` int(11) NOT NULL,
  `id_peminjaman` int(11) DEFAULT NULL,
  `tgl_dikembalikan` date NOT NULL,
  `denda` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `pengembalian`
--
DELIMITER $$
CREATE TRIGGER `trg_kembali_buku` AFTER INSERT ON `pengembalian` FOR EACH ROW BEGIN
    UPDATE buku SET stok = stok + 1 
    WHERE id_buku = (SELECT id_buku FROM peminjaman WHERE id_peminjaman = NEW.id_peminjaman);
    
    UPDATE peminjaman SET status = 'Kembali' 
    WHERE id_peminjaman = NEW.id_peminjaman;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `jenkel` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','mahasiswa','dosen','karyawan') NOT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `borrowing_limit` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `foto`, `nama`, `jenkel`, `password`, `role`, `status`, `borrowing_limit`) VALUES
(1, '32241012', NULL, NULL, NULL, NULL, 'k1', 'admin', 'active', 3),
(8, '32231011', '', 'user_8_1778589878.jpeg', 'kholid mashuri', 'Laki-laki', 'k1', 'mahasiswa', 'active', 3),
(9, '32231006', NULL, NULL, 'ambatukim', 'Laki-laki', 'k1', 'mahasiswa', 'active', 3),
(10, '32241011', NULL, NULL, 'surti', 'Laki-laki', 'kq', 'dosen', 'active', 3),
(11, '32241001', NULL, NULL, 'amba', 'Laki-laki', 'k1', 'karyawan', 'active', 3),
(12, 'user', NULL, NULL, 'budi', 'Laki-laki', 'user', 'mahasiswa', 'active', 3),
(13, 'admin', NULL, NULL, 'bubud', 'Laki-laki', 'admin', 'karyawan', 'active', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id_buku`);

--
-- Indexes for table `checkin_log`
--
ALTER TABLE `checkin_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_penalty` (`id_penalty`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_buku` (`id_buku`);

--
-- Indexes for table `penalties`
--
ALTER TABLE `penalties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `penalty_rules`
--
ALTER TABLE `penalty_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id_pengembalian`),
  ADD KEY `id_peminjaman` (`id_peminjaman`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id_buku` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `checkin_log`
--
ALTER TABLE `checkin_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_peminjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `penalties`
--
ALTER TABLE `penalties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penalty_rules`
--
ALTER TABLE `penalty_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id_pengembalian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `checkin_log`
--
ALTER TABLE `checkin_log`
  ADD CONSTRAINT `checkin_log_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`id_penalty`) REFERENCES `penalties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON DELETE CASCADE;

--
-- Constraints for table `penalties`
--
ALTER TABLE `penalties`
  ADD CONSTRAINT `penalties_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE;
  
  
  -- mig.sql
  -- ============================================================
-- SIPUS POLSA - Database Migration (Priority 1 & 2)
-- Jalankan SQL ini untuk menambah tabel & kolom baru
-- ============================================================

-- 1. Tambah kolom status di tabel users (active/blocked)
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active','blocked') DEFAULT 'active' AFTER role;

-- 2. Tambah kolom borrowing_limit di tabel users
ALTER TABLE users ADD COLUMN IF NOT EXISTS borrowing_limit INT DEFAULT 3 AFTER status;

-- 3. Tambah kolom email di tabel users (jika belum ada)
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(100) DEFAULT NULL AFTER nama;

-- 4. Tambah kolom barcode di tabel buku
ALTER TABLE buku ADD COLUMN IF NOT EXISTS barcode VARCHAR(50) DEFAULT NULL AFTER id_buku;

-- 5. Tambah kolom kondisi_kembali di tabel peminjaman
ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS kondisi_kembali ENUM('baik','rusak_ringan','rusak_berat','hilang') DEFAULT NULL;

-- 6. Tambah kolom perpanjangan_status di tabel peminjaman
ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS perpanjangan_status ENUM('none','requested','approved','rejected') DEFAULT 'none';

-- 7. Buat tabel checkin_log (jika belum ada dari API)
CREATE TABLE IF NOT EXISTS checkin_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    waktu_checkin DATETIME DEFAULT CURRENT_TIMESTAMP,
    metode VARCHAR(20) DEFAULT 'barcode',
    tipe ENUM('checkin','checkout') DEFAULT 'checkin',
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
);

-- 8. Buat tabel penalty_rules (konfigurasi tarif denda)
CREATE TABLE IF NOT EXISTS penalty_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipe_denda ENUM('overdue','damage','lost') NOT NULL,
    nama_aturan VARCHAR(100) NOT NULL,
    tarif DECIMAL(12,2) NOT NULL DEFAULT 0,
    satuan VARCHAR(20) DEFAULT 'per_hari',
    deskripsi TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 9. Insert default penalty rules
INSERT IGNORE INTO penalty_rules (id, tipe_denda, nama_aturan, tarif, satuan, deskripsi) VALUES
(1, 'overdue', 'Denda Keterlambatan', 2000.00, 'per_hari', 'Denda Rp 2.000 per hari keterlambatan pengembalian buku'),
(2, 'damage', 'Denda Kerusakan Ringan', 25000.00, 'flat', 'Denda untuk kerusakan ringan (halaman sobek, kotor, dll)'),
(3, 'damage', 'Denda Kerusakan Berat', 75000.00, 'flat', 'Denda untuk kerusakan berat (sampul rusak, halaman hilang, dll)'),
(4, 'lost', 'Denda Buku Hilang', 0.00, 'replacement', 'Biaya penggantian buku + biaya administrasi Rp 25.000');

-- 10. Buat tabel penalties (denda per user/transaksi)
CREATE TABLE IF NOT EXISTS penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_peminjaman INT DEFAULT NULL,
    id_buku INT DEFAULT NULL,
    tipe_denda ENUM('overdue','damage','lost') NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL DEFAULT 0,
    jumlah_dibayar DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('unpaid','partial','paid','waived','disputed') DEFAULT 'unpaid',
    catatan TEXT,
    catatan_dispute TEXT,
    created_by INT DEFAULT NULL,
    resolved_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME DEFAULT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
);

-- 11. Buat tabel payments (riwayat pembayaran)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_penalty INT NOT NULL,
    id_user INT NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL,
    metode_bayar ENUM('cash','transfer','online') DEFAULT 'cash',
    bukti_bayar VARCHAR(255) DEFAULT NULL,
    catatan VARCHAR(255) DEFAULT NULL,
    verified_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_penalty) REFERENCES penalties(id) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
);

-- 12. Buat tabel notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    pesan TEXT NOT NULL,
    tipe ENUM('info','approval','overdue','fine','checkin','system') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
);

-- 13. Buat tabel fcm_tokens (untuk push notification)
CREATE TABLE IF NOT EXISTS fcm_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    fcm_token TEXT NOT NULL,
    device_name VARCHAR(100) DEFAULT 'Unknown',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
);

-- 14. Generate barcode otomatis untuk buku yang belum punya
UPDATE buku SET barcode = CONCAT('BK', LPAD(id_buku, 6, '0')) WHERE barcode IS NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
