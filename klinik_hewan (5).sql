-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 20 Jun 2026 pada 10.18
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `klinik_hewan`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `aktivitas_log`
--

CREATE TABLE `aktivitas_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat_history`
--

CREATE TABLE `chat_history` (
  `id` int(11) NOT NULL,
  `pelanggan_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `reply` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `hewan_peliharaan`
--

CREATE TABLE `hewan_peliharaan` (
  `id` int(11) NOT NULL,
  `pelanggan_id` int(11) DEFAULT NULL,
  `nama_hewan` varchar(50) NOT NULL,
  `jenis_hewan` varchar(50) DEFAULT NULL,
  `ras` varchar(50) DEFAULT NULL,
  `umur` int(11) DEFAULT NULL,
  `berat` decimal(5,2) DEFAULT NULL,
  `warna` varchar(30) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `hewan_peliharaan`
--

INSERT INTO `hewan_peliharaan` (`id`, `pelanggan_id`, `nama_hewan`, `jenis_hewan`, `ras`, `umur`, `berat`, `warna`, `foto`, `created_at`, `updated_at`) VALUES
(1, 1, 'Milo', 'Kucing', 'Persia', 3, 4.50, 'pink', NULL, '2026-06-18 15:10:30', '2026-06-18 16:57:01'),
(2, 1, 'rival', 'Anjing', 'pitbull', 4, 26.00, 'hitam', NULL, '2026-06-19 05:06:16', '2026-06-19 05:06:16'),
(3, 1, 'coki', 'Ikan', 'Koi', 2, 1.50, 'putih', NULL, '2026-06-19 08:51:47', '2026-06-19 08:51:47'),
(4, 1, 'Wowo', 'Anjing', 'pittbul kampung', 2, 20.00, 'hitam', NULL, '2026-06-20 08:07:56', '2026-06-20 08:07:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `janji_temu`
--

CREATE TABLE `janji_temu` (
  `id` int(11) NOT NULL,
  `pelanggan_id` int(11) DEFAULT NULL,
  `perawat_id` int(11) DEFAULT NULL,
  `hewan_id` int(11) DEFAULT NULL,
  `layanan_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `keluhan` text DEFAULT NULL,
  `catatan_dokter` text DEFAULT NULL,
  `total_harga` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','selesai','batal') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `janji_temu`
--

INSERT INTO `janji_temu` (`id`, `pelanggan_id`, `perawat_id`, `hewan_id`, `layanan_id`, `tanggal`, `waktu`, `keluhan`, `catatan_dokter`, `total_harga`, `status`, `created_at`, `updated_at`) VALUES
(2, 1, 2, 2, 4, '2026-06-21', '16:10:00', 'bau mulut', NULL, 250000.00, 'pending', '2026-06-19 05:07:09', '2026-06-19 05:07:09'),
(3, 1, 6, 3, 1, '2026-06-19', '15:00:00', 'ikan saya mabuk air garam', NULL, 150000.00, 'selesai', '2026-06-19 08:52:42', '2026-06-20 08:14:32'),
(4, 1, 6, 4, 1, '2026-06-22', '18:08:00', 'anjing nya tidak mau makan', NULL, 150000.00, 'pending', '2026-06-20 08:08:45', '2026-06-20 08:08:45');

--
-- Trigger `janji_temu`
--
DELIMITER $$
CREATE TRIGGER `update_total_harga` BEFORE INSERT ON `janji_temu` FOR EACH ROW BEGIN 
    DECLARE harga_layanan DECIMAL(10,2);
    SELECT harga INTO harga_layanan FROM layanan WHERE id = NEW.layanan_id;
    SET NEW.total_harga = harga_layanan;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `layanan`
--

CREATE TABLE `layanan` (
  `id` int(11) NOT NULL,
  `nama_layanan` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `durasi` int(11) DEFAULT 30,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `layanan`
--

INSERT INTO `layanan` (`id`, `nama_layanan`, `deskripsi`, `harga`, `durasi`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Konsultasi Umum', 'Konsultasi kesehatan hewan secara umum', 150000.00, 30, 'aktif', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(2, 'Vaksinasi', 'Program vaksinasi lengkap', 200000.00, 45, 'aktif', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(3, 'Laboratorium', 'Pemeriksaan laboratorium lengkap', 350000.00, 60, 'aktif', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(4, 'Perawatan Gigi', 'Perawatan gigi dan mulut hewan', 250000.00, 45, 'aktif', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(5, 'Operasi', 'Tindakan operasi (steril, tumor, dll)', 1500000.00, 120, 'aktif', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(6, 'Perawatan Intensif', 'Perawatan intensif untuk hewan sakit', 500000.00, 60, 'aktif', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(7, 'Grooming', 'Perawatan bulu dan penampilan', 100000.00, 30, 'aktif', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(8, 'Fisioterapi', 'Terapi fisik untuk pemulihan', 300000.00, 45, 'aktif', '2026-06-18 16:33:50', '2026-06-18 16:33:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int(11) NOT NULL,
  `pelanggan_id` int(11) NOT NULL,
  `judul` varchar(100) NOT NULL,
  `pesan` text NOT NULL,
  `jenis` enum('info','success','warning','danger') DEFAULT 'info',
  `dibaca` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifikasi`
--

INSERT INTO `notifikasi` (`id`, `pelanggan_id`, `judul`, `pesan`, `jenis`, `dibaca`, `link`, `created_at`) VALUES
(1, 1, 'Selamat Datang!', 'Selamat datang di Klinik Hewan. Kami siap membantu kesehatan hewan peliharaan Anda.', 'info', 1, NULL, '2026-06-18 16:55:13'),
(2, 1, 'Tips Perawatan', 'Jangan lupa vaksinasi rutin untuk hewan peliharaan Anda setiap 6 bulan sekali.', 'info', 1, NULL, '2026-06-18 16:55:13'),
(3, 1, 'Hewan Diperbarui', 'Data hewan Milo telah diperbarui.', 'info', 1, NULL, '2026-06-18 16:56:57'),
(4, 1, 'Hewan Diperbarui', 'Data hewan Milo telah diperbarui.', 'info', 1, NULL, '2026-06-18 16:57:01'),
(5, 1, 'Janji Temu Dibuat', 'Janji temu untuk Milo dengan layanan Perawatan Intensif telah dibuat dan menunggu konfirmasi.', 'info', 0, NULL, '2026-06-18 16:57:53'),
(6, 1, 'Hewan Baru Ditambahkan', 'Hewan peliharaan rival telah ditambahkan ke daftar.', 'success', 0, NULL, '2026-06-19 05:06:16'),
(7, 1, 'Janji Temu Dibuat', 'Janji temu untuk rival dengan layanan Perawatan Gigi telah dibuat dan menunggu konfirmasi.', 'info', 0, NULL, '2026-06-19 05:07:10'),
(8, 1, 'Hewan Baru Ditambahkan', 'Hewan peliharaan coki telah ditambahkan ke daftar.', 'success', 0, NULL, '2026-06-19 08:51:47'),
(9, 1, 'Janji Temu Dibuat', 'Janji temu untuk coki dengan layanan Konsultasi Umum telah dibuat dan menunggu konfirmasi.', 'info', 0, NULL, '2026-06-19 08:52:42'),
(10, 1, 'Hewan Baru Ditambahkan', 'Hewan peliharaan Wowo telah ditambahkan ke daftar.', 'success', 0, NULL, '2026-06-20 08:07:56'),
(11, 1, 'Janji Temu Dibuat', 'Janji temu untuk Wowo dengan layanan Konsultasi Umum telah dibuat dan menunggu konfirmasi.', 'info', 0, NULL, '2026-06-20 08:08:45'),
(12, 1, 'Status Janji Diperbarui', 'Janji untuk hewan coki Anda sekarang berstatus: Dikonfirmasi', 'info', 0, NULL, '2026-06-20 08:10:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi_perawat`
--

CREATE TABLE `notifikasi_perawat` (
  `id` int(11) NOT NULL,
  `perawat_id` int(11) NOT NULL,
  `judul` varchar(100) NOT NULL,
  `pesan` text NOT NULL,
  `jenis` enum('info','success','warning','danger') DEFAULT 'info',
  `dibaca` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto_profile` varchar(255) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pelanggan`
--

INSERT INTO `pelanggan` (`id`, `user_id`, `nama_lengkap`, `no_telepon`, `alamat`, `foto_profile`, `tanggal_lahir`, `jenis_kelamin`, `created_at`, `updated_at`) VALUES
(1, 4, 'Budi Santosoa', '081311122233', 'Jl. Mawar No. 12, Jakarta', 'uploads/pelanggan/pelanggan_1_1781801783.png', '0000-00-00', '', '2026-06-18 15:10:30', '2026-06-18 16:56:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `key_setting` varchar(50) NOT NULL,
  `value_setting` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `key_setting`, `value_setting`, `created_at`, `updated_at`) VALUES
(1, 'nama_klinik', 'Klinik Hewan', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(2, 'alamat', 'Jl. Kesehatan No. 1, Jakarta', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(3, 'telepon', '(021) 1234-5678', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(4, 'email', 'info@klinikhewan.com', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(5, 'jam_buka_senin_jumat', '08:00 - 20:00', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(6, 'jam_buka_sabtu', '08:00 - 17:00', '2026-06-18 16:33:50', '2026-06-18 16:33:50'),
(7, 'jam_buka_minggu', 'Tutup', '2026-06-18 16:33:50', '2026-06-18 16:33:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `perawat`
--

CREATE TABLE `perawat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `keahlian` varchar(100) DEFAULT NULL,
  `pengalaman` int(11) DEFAULT NULL,
  `foto_profile` varchar(255) DEFAULT NULL,
  `status` enum('aktif','tidak_aktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `perawat`
--

INSERT INTO `perawat` (`id`, `user_id`, `nama_lengkap`, `no_telepon`, `keahlian`, `pengalaman`, `foto_profile`, `status`, `created_at`, `updated_at`) VALUES
(2, 3, 'dr. Dhera Widodo', '081298765432', 'Bedah Telinga', 8, '../uploads/perawat/perawat_2_1781805172.jpeg', 'aktif', '2026-06-18 15:10:30', '2026-06-19 01:55:07'),
(6, 8, 'dr. Azril Baswedan', '', 'Bedah Gigi', 2, '../uploads/perawat/perawat_6_1781806947.jpeg', 'aktif', '2026-06-18 18:18:40', '2026-06-19 01:55:52'),
(7, 9, 'dr. Thoriq MustDur', '', 'Bedah Saraf', 2, '../uploads/perawat/perawat_7_1781807160.jpeg', 'aktif', '2026-06-18 18:19:30', '2026-06-19 01:56:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('pelanggan','perawat','admin') NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `status`, `created_at`, `updated_at`, `last_login`, `last_ip`) VALUES
(1, 'admin', '$2y$10$hxDVUXeLgalSOfNHGHYC/ejFo9nnJxGYH43Gkw.RRPx0KhTILDiPy', 'admin@klinik.com', 'admin', 'aktif', '2026-06-18 15:10:29', '2026-06-18 18:24:51', NULL, NULL),
(3, 'perawat2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'perawat2@klinik.com', 'perawat', 'aktif', '2026-06-18 15:10:29', '2026-06-18 18:14:34', NULL, NULL),
(4, 'pelanggan1', '$2y$10$aSfpq89vE/kaUL3O7AT7gepkBoYhev6taFmIK6iYEZx1csbAqA43u', 'pelanggan1@email.com', 'pelanggan', 'aktif', '2026-06-18 15:10:29', '2026-06-19 05:05:20', NULL, NULL),
(8, 'perawat1', '$2y$10$8ZYOzCtaoZaSqroUBGmOl.ypAy6Q.CmY2tcjcRRud7T.2gTWFlIqS', 'perawat1@klinik.com', 'perawat', 'aktif', '2026-06-18 18:18:40', '2026-06-18 18:18:40', NULL, NULL),
(9, 'perawat3', '$2y$10$hurLTWvRT39tsTZhkT4CRuRAgFlYENhrh5rRfPESDg17hFv5PtJ1S', 'perawat3@gmail.com', 'perawat', 'aktif', '2026-06-18 18:19:30', '2026-06-18 18:25:32', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `aktivitas_log`
--
ALTER TABLE `aktivitas_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `chat_history`
--
ALTER TABLE `chat_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pelanggan_id` (`pelanggan_id`);

--
-- Indeks untuk tabel `hewan_peliharaan`
--
ALTER TABLE `hewan_peliharaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pelanggan` (`pelanggan_id`);

--
-- Indeks untuk tabel `janji_temu`
--
ALTER TABLE `janji_temu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hewan_id` (`hewan_id`),
  ADD KEY `janji_temu_ibfk_4` (`layanan_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_perawat_tanggal` (`perawat_id`,`tanggal`),
  ADD KEY `idx_pelanggan_tanggal` (`pelanggan_id`,`tanggal`);

--
-- Indeks untuk tabel `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pelanggan_dibaca` (`pelanggan_id`,`dibaca`);

--
-- Indeks untuk tabel `notifikasi_perawat`
--
ALTER TABLE `notifikasi_perawat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `perawat_id` (`perawat_id`);

--
-- Indeks untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_setting` (`key_setting`);

--
-- Indeks untuk tabel `perawat`
--
ALTER TABLE `perawat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `aktivitas_log`
--
ALTER TABLE `aktivitas_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `chat_history`
--
ALTER TABLE `chat_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `hewan_peliharaan`
--
ALTER TABLE `hewan_peliharaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `janji_temu`
--
ALTER TABLE `janji_temu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `notifikasi_perawat`
--
ALTER TABLE `notifikasi_perawat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `perawat`
--
ALTER TABLE `perawat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `aktivitas_log`
--
ALTER TABLE `aktivitas_log`
  ADD CONSTRAINT `aktivitas_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `chat_history`
--
ALTER TABLE `chat_history`
  ADD CONSTRAINT `chat_history_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `hewan_peliharaan`
--
ALTER TABLE `hewan_peliharaan`
  ADD CONSTRAINT `hewan_peliharaan_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `janji_temu`
--
ALTER TABLE `janji_temu`
  ADD CONSTRAINT `janji_temu_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`),
  ADD CONSTRAINT `janji_temu_ibfk_2` FOREIGN KEY (`perawat_id`) REFERENCES `perawat` (`id`),
  ADD CONSTRAINT `janji_temu_ibfk_3` FOREIGN KEY (`hewan_id`) REFERENCES `hewan_peliharaan` (`id`),
  ADD CONSTRAINT `janji_temu_ibfk_4` FOREIGN KEY (`layanan_id`) REFERENCES `layanan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifikasi_perawat`
--
ALTER TABLE `notifikasi_perawat`
  ADD CONSTRAINT `notifikasi_perawat_ibfk_1` FOREIGN KEY (`perawat_id`) REFERENCES `perawat` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD CONSTRAINT `pelanggan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `perawat`
--
ALTER TABLE `perawat`
  ADD CONSTRAINT `perawat_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
