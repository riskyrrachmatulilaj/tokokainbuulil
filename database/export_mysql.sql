-- ============================================================
-- Export Data: Toko Kain Bu Ulil (SQLite → MySQL)
-- Tanggal: 2026-08-14 12:24:32
-- ============================================================
-- PETUNJUK:
-- 1. Pastikan sudah menjalankan `php artisan migrate` di server
--    untuk membuat struktur tabel MySQL terlebih dahulu.
-- 2. Import file ini via phpMyAdmin di cPanel.
-- 3. File ini HANYA berisi data (INSERT), BUKAN struktur tabel.
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- ----------------------------------------------------------
-- Tabel `users`: 2 baris
-- ----------------------------------------------------------
DELETE FROM `users`;
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'risky', 'admin@hutang.test', NULL, '$2y$12$rv9pnC7stVYDQCicnUuMn.PjhYG7vu/gtz/Jeh4bBE7lCjk6cx/k6', 'admin', NULL, '2026-08-07 17:30:05', '2026-08-09 09:39:34'),
(2, 'Dika', 'dika@gmail.com', NULL, '$2y$12$8l4nDYRfinn9yF6/4uQ84.VcjF/lBlSiHil0qkMSS7d2n9uTEEvGe', 'kasir', 'jTkDVV3jELpAITUxunCAE6LDe41Pkipw7wW48IqwLg7Wtq1hzGwvAl5eV0N9', '2026-08-07 17:30:05', '2026-08-09 09:39:22');

-- ----------------------------------------------------------
-- Tabel `customers`: 4 baris
-- ----------------------------------------------------------
DELETE FROM `customers`;
INSERT INTO `customers` (`id`, `name`, `phone`, `address`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Budi Santoso', '081234567890', 'Jl. Melati No. 12, Jakarta', '2026-08-07 17:30:50', '2026-08-08 12:27:13', '2026-08-08 12:27:13'),
(2, 'Siti Aminah', '081298765432', 'Jl. Kenanga No. 5, Bandung', '2026-08-07 17:30:50', '2026-08-08 12:27:12', '2026-08-08 12:27:12'),
(3, 'Ahmad Fauzi', NULL, 'Jl. Merdeka No. 1, Surabaya', '2026-08-07 17:30:50', '2026-08-08 12:27:10', '2026-08-08 12:27:10'),
(4, 'Bu Engelina', NULL, 'PT Jati Mulya [Jakarta]', '2026-08-10 13:14:51', '2026-08-10 13:14:51', NULL);

-- ----------------------------------------------------------
-- Tabel `debts`: 4 baris
-- ----------------------------------------------------------
DELETE FROM `debts`;
INSERT INTO `debts` (`id`, `invoice_number`, `customer_id`, `amount`, `paid_amount`, `remaining_amount`, `debt_date`, `due_date`, `status`, `description`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'INV-20260807-0001', 3, 2500000, 2500000, 0, '2026-07-07 00:00:00', NULL, 'paid', NULL, 1, '2026-08-07 17:31:09', '2026-08-08 01:10:07', '2026-08-08 01:10:07'),
(2, 'INV-20260807-0002', 3, 2500000, 0, 2500000, '2026-07-10 00:00:00', NULL, 'unpaid', NULL, 1, '2026-08-07 17:31:49', '2026-08-08 01:10:03', '2026-08-08 01:10:03'),
(3, 'INV-20260807-0003', 3, 2500000, 2200000, 300000, '2026-07-09 00:00:00', NULL, 'unpaid', NULL, 1, '2026-08-07 17:32:10', '2026-08-08 01:10:04', '2026-08-08 01:10:04'),
(4, 'INV-20260810-0001', 4, 50000000, 30000000, 20000000, '2026-08-10 00:00:00', NULL, 'unpaid', NULL, 1, '2026-08-10 13:21:40', '2026-08-10 13:49:00', '2026-08-10 13:49:00');

-- ----------------------------------------------------------
-- Tabel `installments`: 1 baris
-- ----------------------------------------------------------
DELETE FROM `installments`;
INSERT INTO `installments` (`id`, `debt_id`, `installment_date`, `amount`, `description`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 4, '2026-08-10 00:00:00', 30000000, NULL, 1, '2026-08-10 13:47:56', '2026-08-10 14:04:48', '2026-08-10 14:04:48');

-- ----------------------------------------------------------
-- Tabel `collective_payments`: 1 baris
-- ----------------------------------------------------------
DELETE FROM `collective_payments`;
INSERT INTO `collective_payments` (`id`, `transaction_number`, `customer_id`, `amount`, `payment_date`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'TRX-20260807-0001', 3, 4700000, '2026-08-07 00:00:00', NULL, 1, '2026-08-07 17:35:21', '2026-08-07 17:35:21');

-- ----------------------------------------------------------
-- Tabel `payment_histories`: 3 baris
-- ----------------------------------------------------------
DELETE FROM `payment_histories`;
INSERT INTO `payment_histories` (`id`, `transaction_number`, `customer_id`, `debt_id`, `installment_id`, `collective_payment_id`, `payment_type`, `amount`, `payment_date`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'TRX-20260807-0001', 3, 1, NULL, 1, 'collective', 2500000, '2026-08-07 00:00:00', NULL, 1, '2026-08-07 17:35:21', '2026-08-07 17:35:21'),
(2, 'TRX-20260807-0001', 3, 3, NULL, 1, 'collective', 2200000, '2026-08-07 00:00:00', NULL, 1, '2026-08-07 17:35:21', '2026-08-07 17:35:21'),
(3, 'TRX-20260810-0001', 4, 4, 1, NULL, 'installment', 30000000, '2026-08-10 00:00:00', NULL, 1, '2026-08-10 13:47:56', '2026-08-10 13:47:56');

-- ----------------------------------------------------------
-- Tabel `receivable_parties`: 74 baris
-- ----------------------------------------------------------
DELETE FROM `receivable_parties`;
INSERT INTO `receivable_parties` (`id`, `name`, `phone`, `address`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'CV Maju Jaya', '081234567890', 'Jl. Industri No. 5, Bandung', '2026-08-07 17:44:37', '2026-08-08 12:27:18', '2026-08-08 12:27:18'),
(2, 'Toko Berkah', '081298765432', 'Jl. Pasar Baru No. 8, Cimahi', '2026-08-07 17:44:37', '2026-08-08 12:27:17', '2026-08-08 12:27:17'),
(3, 'Bapak Andi Wijaya', NULL, 'Jl. Melati No. 3, Garut', '2026-08-07 17:44:37', '2026-08-08 12:27:16', '2026-08-08 12:27:16'),
(4, 'Pak Khodim', NULL, 'Pandaan', '2026-08-08 13:02:20', '2026-08-08 13:02:20', NULL),
(5, 'Pak Agus Bandrek', NULL, 'Bandrek', '2026-08-08 13:02:35', '2026-08-08 13:02:35', NULL),
(6, 'Pak Agus Lawatan', NULL, 'Lawatan', '2026-08-08 13:02:51', '2026-08-08 13:02:51', NULL),
(7, 'Andika <3 Koder', '085852010913', 'Dolli', '2026-08-10 11:26:40', '2026-08-10 11:26:40', NULL),
(8, 'Mas Fahrudin', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(9, 'Mas Farhan', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(10, 'Mas Basori', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(11, 'Hj bibah', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(12, 'Mas Robby', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(13, 'Pak Fendi', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(14, 'Mas Ramdan', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(15, 'Mas Faisal', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(16, 'Pak Ridho', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(17, 'Pak Saiful Watu Agung', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(18, 'Pak Romhan Rombo', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(19, 'Mas Rohim', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(20, 'Bu Arifin', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(21, 'Mbak Nita', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(22, 'Pak Khoiron Lawang', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(23, 'Bu Ike', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(24, 'Mas Yono', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(25, 'HJ Mulyono', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(26, 'Pak Hamid', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(27, 'Pak Huda', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(28, 'Pak Hari', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(29, 'Pak Mahmud Jati Tengah', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(30, 'Pak Mahmud', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(31, 'Pak Imron', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(32, 'Hj Samuji', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(33, 'Pak Imam', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(34, 'P Jaini Calukan', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(35, 'Bu Nadziroh', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(36, 'Mas Haidar', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(37, 'HJ Tholib', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(38, 'Mbak Yayuk', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(39, 'P Sudiono', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(40, 'Pak Nastain', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(41, 'Hj Maksum', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(42, 'Pak Suyitno', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(43, 'Pak Wawan', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(44, 'Mbak Ruroh', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(45, 'Mas Saiful Gutner', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(46, 'Pak Rahman SBY', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(47, 'Bu Khusnul', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(48, 'Bu Habib', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(49, 'Pak Majid', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(50, 'Toko Avidah', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL);

INSERT INTO `receivable_parties` (`id`, `name`, `phone`, `address`, `created_at`, `updated_at`, `deleted_at`) VALUES
(51, 'Pak Supri', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(52, 'Mas Basir', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(53, 'Bu Diva', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(54, 'Hj dull sby', NULL, NULL, '2026-08-10 13:00:27', '2026-08-10 13:00:27', NULL),
(55, 'Aba Tohir', NULL, NULL, '2026-08-11 14:48:08', '2026-08-11 14:48:08', NULL),
(56, 'Pak Sodiq', NULL, NULL, '2026-08-11 15:10:13', '2026-08-11 15:10:13', NULL),
(57, 'Bu Suci', NULL, NULL, '2026-08-12 07:13:07', '2026-08-12 07:13:07', NULL),
(58, 'Bu lifa', NULL, NULL, '2026-08-12 08:23:19', '2026-08-12 08:23:19', NULL),
(59, 'Hj Nik', NULL, NULL, '2026-08-12 08:42:28', '2026-08-12 08:42:28', NULL),
(60, 'Mas Rahmat', NULL, NULL, '2026-08-12 09:11:11', '2026-08-12 09:11:11', NULL),
(61, 'Pak Arifin', NULL, NULL, '2026-08-12 10:15:25', '2026-08-12 10:15:25', NULL),
(62, 'Bu Aisyah', NULL, NULL, '2026-08-12 13:07:34', '2026-08-12 13:07:34', NULL),
(63, 'Pak Dayat', NULL, NULL, '2026-08-12 13:22:34', '2026-08-12 13:22:34', NULL),
(64, 'Bu Cindy', NULL, NULL, '2026-08-12 13:47:41', '2026-08-12 13:47:41', NULL),
(65, 'Pak Kasmoro', NULL, NULL, '2026-08-12 15:53:59', '2026-08-12 15:53:59', NULL),
(66, 'Pak Edi', NULL, NULL, '2026-08-13 07:46:00', '2026-08-13 07:46:00', NULL),
(67, 'Pak Faroid', NULL, NULL, '2026-08-13 08:21:03', '2026-08-13 08:21:03', NULL),
(68, 'Bu Eva', NULL, NULL, '2026-08-13 08:27:44', '2026-08-13 08:27:44', NULL),
(69, 'Pak Yani', NULL, NULL, '2026-08-13 08:38:53', '2026-08-13 08:38:53', NULL),
(70, 'Pak Junaidi', NULL, NULL, '2026-08-13 08:47:11', '2026-08-13 08:47:11', NULL),
(71, 'Pak Amir', NULL, NULL, '2026-08-14 08:29:09', '2026-08-14 08:29:09', NULL),
(72, 'Pak Basri', NULL, NULL, '2026-08-14 09:10:41', '2026-08-14 09:10:41', NULL),
(73, 'Pak Husain', NULL, NULL, '2026-08-14 10:42:26', '2026-08-14 10:42:26', NULL),
(74, 'Bu Alfiyah', NULL, NULL, '2026-08-14 11:25:51', '2026-08-14 11:25:51', NULL);

-- ----------------------------------------------------------
-- Tabel `receivables`: 16 baris
-- ----------------------------------------------------------
DELETE FROM `receivables`;
INSERT INTO `receivables` (`id`, `invoice_number`, `receivable_party_id`, `amount`, `paid_amount`, `remaining_amount`, `receivable_date`, `due_date`, `status`, `description`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PINV-20260808-0001', 3, 150000, 0, 150000, '2026-08-08 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260808-0002', 1, '2026-08-08 11:53:07', '2026-08-08 12:14:15', '2026-08-08 12:14:15'),
(2, 'PINV-20260809-0001', 5, 1020000, 0, 1020000, '2026-08-09 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260809-0002', 1, '2026-08-09 10:17:34', '2026-08-09 10:25:45', '2026-08-09 10:25:45'),
(3, 'PINV-20260809-0002', 5, 330000, 0, 330000, '2026-08-09 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260809-0002', 2, '2026-08-09 11:30:39', '2026-08-09 11:36:18', '2026-08-09 11:36:18'),
(4, 'PINV-20260810-0001', 7, 4920000, 4000000, 920000, '2026-08-10 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260810-0001', 1, '2026-08-10 13:04:48', '2026-08-10 13:48:53', '2026-08-10 13:48:53'),
(5, 'PINV-20260810-0002', 37, 50000000, 0, 50000000, '2026-07-23 00:00:00', NULL, 'unpaid', NULL, 1, '2026-08-10 13:49:37', '2026-08-10 13:58:06', '2026-08-10 13:58:06'),
(6, 'PINV-20260810-0003', 7, 200000000, 0, 200000000, '2026-08-10 00:00:00', NULL, 'unpaid', NULL, 1, '2026-08-10 13:58:26', '2026-08-10 14:03:29', '2026-08-10 14:03:29'),
(7, 'PINV-20260810-0004', 7, 2500000, 2500000, 0, '2026-08-10 00:00:00', NULL, 'paid', NULL, 1, '2026-08-10 14:03:43', '2026-08-10 14:04:12', NULL),
(8, 'PINV-20260811-0001', 7, 440000, 0, 440000, '2026-08-11 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260811-0003', 1, '2026-08-11 14:12:08', '2026-08-11 14:34:05', '2026-08-11 14:34:05'),
(9, 'PINV-20260812-0001', 60, 960000, 0, 960000, '2026-08-12 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260812-0011', 1, '2026-08-12 09:11:38', '2026-08-12 09:11:38', NULL),
(10, 'PINV-20260812-0002', 47, 10820000, 0, 10820000, '2026-08-12 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260812-0014', 2, '2026-08-12 11:21:15', '2026-08-12 11:21:15', NULL),
(11, 'PINV-20260812-0003', 12, 5204920, 0, 5204920, '2026-08-12 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260812-0018', 2, '2026-08-12 13:36:57', '2026-08-12 13:36:57', NULL),
(12, 'PINV-20260813-0001', 69, 5400000, 0, 5400000, '2026-08-13 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260813-0004', 2, '2026-08-13 08:39:29', '2026-08-13 08:39:29', NULL),
(13, 'PINV-20260813-0002', 13, 2025000, 0, 2025000, '2026-08-13 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260813-0007', 2, '2026-08-13 09:22:34', '2026-08-13 09:22:34', NULL),
(14, 'PINV-20260813-0003', 42, 1350000, 0, 1350000, '2026-08-13 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260813-0012', 2, '2026-08-13 15:38:12', '2026-08-13 15:38:12', NULL),
(15, 'PINV-20260814-0001', 13, 390570, 0, 390570, '2026-08-14 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260814-0004', 2, '2026-08-14 08:24:37', '2026-08-14 08:24:37', NULL),
(16, 'PINV-20260814-0002', 71, 1350000, 0, 1350000, '2026-08-14 00:00:00', NULL, 'unpaid', 'Penjualan kredit SLS-20260814-0005', 2, '2026-08-14 08:29:36', '2026-08-14 08:29:36', NULL);

-- ----------------------------------------------------------
-- Tabel `receivable_installments`: 1 baris
-- ----------------------------------------------------------
DELETE FROM `receivable_installments`;
INSERT INTO `receivable_installments` (`id`, `receivable_id`, `installment_date`, `amount`, `description`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 7, '2026-08-10 00:00:00', 2500000, NULL, 1, '2026-08-10 14:04:12', '2026-08-10 14:04:20', '2026-08-10 14:04:20');

-- ----------------------------------------------------------
-- Tabel `receivable_collective_payments`: 1 baris
-- ----------------------------------------------------------
DELETE FROM `receivable_collective_payments`;
INSERT INTO `receivable_collective_payments` (`id`, `transaction_number`, `receivable_party_id`, `amount`, `payment_date`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PTRX-20260810-0001', 7, 4000000, '2026-08-10 00:00:00', NULL, 1, '2026-08-10 13:07:27', '2026-08-10 13:07:27');

-- ----------------------------------------------------------
-- Tabel `receivable_payment_histories`: 2 baris
-- ----------------------------------------------------------
DELETE FROM `receivable_payment_histories`;
INSERT INTO `receivable_payment_histories` (`id`, `transaction_number`, `receivable_party_id`, `receivable_id`, `installment_id`, `collective_payment_id`, `payment_type`, `amount`, `payment_date`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PTRX-20260810-0001', 7, 4, NULL, 1, 'collective', 4000000, '2026-08-10 00:00:00', NULL, 1, '2026-08-10 13:07:27', '2026-08-10 13:07:27'),
(2, 'PTRX-20260810-0002', 7, 7, 1, NULL, 'installment', 2500000, '2026-08-10 00:00:00', NULL, 1, '2026-08-10 14:04:12', '2026-08-10 14:04:12');

-- ----------------------------------------------------------
-- Tabel `products`: 215 baris
-- ----------------------------------------------------------
DELETE FROM `products`;
INSERT INTO `products` (`id`, `name`, `price`, `description`, `is_active`, `created_at`, `updated_at`, `track_stock`, `stock`) VALUES
(5, 'Rasfur Mtex 17 - Maroon', 19500, 'Warna maroon', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(6, 'Rasfur Mtex 17 - Merah', 19500, 'Warna merah', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(7, 'Rasfur Mtex 17 - Hijau', 19500, 'Warna hijau', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(8, 'Rasfur Mtex 17 - Coklat', 19500, 'Warna coklat', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(9, 'Rasfur Mtex 17 - Coklat Tua', 19500, 'Warna coklat tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(10, 'Rasfur Mtex 17 - Hitam', 19500, 'Warna hitam', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(11, 'Rasfur Mtex 17 - Putih', 19500, 'Warna putih', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(12, 'Rasfur Mtex 17 - Pink', 19500, 'Warna pink', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(13, 'Rasfur Mtex 17 - Fanta', 19500, 'Warna fanta', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(14, 'Rasfur Mtex 17 - Biru Muda', 19500, 'Warna biru muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(15, 'Rasfur Mtex 17 - Biru BCA', 19500, 'Warna biru BCA', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(16, 'Rasfur Mtex 17 - Kuning Pooh', 19500, 'Warna kuning pooh', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(17, 'Rasfur Mtex 17 - Kuning Tweety', 19500, 'Warna kuning tweety', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(18, 'Rasfur Mtex 17 - Ungu', 19500, 'Warna ungu', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(19, 'Rasfur Mtex 17 - Cream', 19500, 'Warna cream', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(20, 'Rasfur Mtex 17 - Tan', 19500, 'Warna tan', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(21, 'Rasfur Mtex 17 - Abu Muda', 19500, 'Warna abu muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(22, 'Rasfur Mtex 17 - Abu Tua', 19500, 'Warna abu tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(23, 'Rasfur Mtex 17 Potongan - Maroon', 23000, 'Warna maroon', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(24, 'Rasfur Mtex 17 Potongan - Merah', 23000, 'Warna merah', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(25, 'Rasfur Mtex 17 Potongan - Hijau', 23000, 'Warna hijau', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(26, 'Rasfur Mtex 17 Potongan - Coklat', 23000, 'Warna coklat', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(27, 'Rasfur Mtex 17 Potongan - Coklat Tua', 23000, 'Warna coklat tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(28, 'Rasfur Mtex 17 Potongan - Hitam', 23000, 'Warna hitam', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(29, 'Rasfur Mtex 17 Potongan - Putih', 23000, 'Warna putih', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(30, 'Rasfur Mtex 17 Potongan - Pink', 23000, 'Warna pink', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(31, 'Rasfur Mtex 17 Potongan - Fanta', 23000, 'Warna fanta', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(32, 'Rasfur Mtex 17 Potongan - Biru Muda', 23000, 'Warna biru muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(33, 'Rasfur Mtex 17 Potongan - Biru BCA', 23000, 'Warna biru BCA', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(34, 'Rasfur Mtex 17 Potongan - Kuning Pooh', 23000, 'Warna kuning pooh', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(35, 'Rasfur Mtex 17 Potongan - Kuning Tweety', 23000, 'Warna kuning tweety', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(36, 'Rasfur Mtex 17 Potongan - Ungu', 23000, 'Warna ungu', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(37, 'Rasfur Mtex 17 Potongan - Cream', 23000, 'Warna cream', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(38, 'Rasfur Mtex 17 Potongan - Tan', 23000, 'Warna tan', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(39, 'Rasfur Mtex 17 Potongan - Abu Muda', 23000, 'Warna abu muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(40, 'Rasfur Mtex 17 Potongan - Abu Tua', 23000, 'Warna abu tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(41, 'Rasfur KSS 17 - Maroon', 16000, 'Warna maroon', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(42, 'Rasfur KSS 17 - Merah', 16000, 'Warna merah', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(43, 'Rasfur KSS 17 - Hijau', 16000, 'Warna hijau', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(44, 'Rasfur KSS 17 - Coklat', 16000, 'Warna coklat', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(45, 'Rasfur KSS 17 - Coklat Tua', 16000, 'Warna coklat tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(46, 'Rasfur KSS 17 - Hitam', 16000, 'Warna hitam', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(47, 'Rasfur KSS 17 - Putih', 16000, 'Warna putih', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(48, 'Rasfur KSS 17 - Pink', 16000, 'Warna pink', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(49, 'Rasfur KSS 17 - Fanta', 16000, 'Warna fanta', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(50, 'Rasfur KSS 17 - Biru Muda', 16000, 'Warna biru muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(51, 'Rasfur KSS 17 - Biru BCA', 16000, 'Warna biru BCA', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(52, 'Rasfur KSS 17 - Kuning Pooh', 16000, 'Warna kuning pooh', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(53, 'Rasfur KSS 17 - Kuning Tweety', 16000, 'Warna kuning tweety', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(54, 'Rasfur KSS 17 - Ungu', 16000, 'Warna ungu', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL);

INSERT INTO `products` (`id`, `name`, `price`, `description`, `is_active`, `created_at`, `updated_at`, `track_stock`, `stock`) VALUES
(55, 'Rasfur KSS 17 - Cream', 16000, 'Warna cream', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(56, 'Rasfur KSS 17 - Tan', 16000, 'Warna tan', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(57, 'Rasfur KSS 17 - Abu Muda', 16000, 'Warna abu muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(58, 'Rasfur KSS 17 - Abu Tua', 16000, 'Warna abu tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(59, 'Rasfur KSS 17 Potongan - Maroon', 20000, 'Warna maroon', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(60, 'Rasfur KSS 17 Potongan - Merah', 20000, 'Warna merah', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(61, 'Rasfur KSS 17 Potongan - Hijau', 20000, 'Warna hijau', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(62, 'Rasfur KSS 17 Potongan - Coklat', 20000, 'Warna coklat', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(63, 'Rasfur KSS 17 Potongan - Coklat Tua', 20000, 'Warna coklat tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(64, 'Rasfur KSS 17 Potongan - Hitam', 20000, 'Warna hitam', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(65, 'Rasfur KSS 17 Potongan - Putih', 20000, 'Warna putih', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(66, 'Rasfur KSS 17 Potongan - Pink', 20000, 'Warna pink', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(67, 'Rasfur KSS 17 Potongan - Fanta', 20000, 'Warna fanta', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(68, 'Rasfur KSS 17 Potongan - Biru Muda', 20000, 'Warna biru muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(69, 'Rasfur KSS 17 Potongan - Biru BCA', 20000, 'Warna biru BCA', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(70, 'Rasfur KSS 17 Potongan - Kuning Pooh', 20000, 'Warna kuning pooh', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(71, 'Rasfur KSS 17 Potongan - Kuning Tweety', 20000, 'Warna kuning tweety', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(72, 'Rasfur KSS 17 Potongan - Ungu', 20000, 'Warna ungu', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(73, 'Rasfur KSS 17 Potongan - Cream', 20000, 'Warna cream', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(74, 'Rasfur KSS 17 Potongan - Tan', 20000, 'Warna tan', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(75, 'Rasfur KSS 17 Potongan - Abu Muda', 20000, 'Warna abu muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(76, 'Rasfur KSS 17 Potongan - Abu Tua', 20000, 'Warna abu tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(77, 'Nylex - Maroon', 12000, 'Warna maroon', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:55', 0, NULL),
(78, 'Nylex - Merah', 12000, 'Warna merah', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:55', 0, NULL),
(79, 'Nylex - Hijau', 12000, 'Warna hijau', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:55', 0, NULL),
(80, 'Nylex - Coklat', 12000, 'Warna coklat', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:54', 0, NULL),
(81, 'Nylex - Coklat Tua', 12000, 'Warna coklat tua', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:54', 0, NULL),
(82, 'Nylex - Hitam', 12000, 'Warna hitam', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:55', 0, NULL),
(83, 'Nylex - Putih', 12000, 'Warna putih', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:55', 0, NULL),
(84, 'Nylex - Pink', 12000, 'Warna pink', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:55', 0, NULL),
(85, 'Nylex - Fanta', 12000, 'Warna fanta', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:54', 0, NULL),
(86, 'Nylex - Biru Muda', 12000, 'Warna biru muda', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:54', 0, NULL),
(87, 'Nylex - Biru BCA', 12000, 'Warna biru BCA', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:54', 0, NULL),
(88, 'Nylex - Kuning Pooh', 12000, 'Warna kuning pooh', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:55', 0, NULL),
(89, 'Nylex - Kuning Tweety', 12000, 'Warna kuning tweety', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:55', 0, NULL),
(90, 'Nylex - Ungu', 12000, 'Warna ungu', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:55', 0, NULL),
(91, 'Nylex - Cream', 12000, 'Warna cream', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:54', 0, NULL),
(92, 'Nylex - Tan', 12000, 'Warna tan', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:55', 0, NULL),
(93, 'Nylex - Abu Muda', 12000, 'Warna abu muda', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:54', 0, NULL),
(94, 'Nylex - Abu Tua', 12000, 'Warna abu tua', 1, '2026-08-09 09:14:38', '2026-08-12 12:03:54', 0, NULL),
(95, 'Nylex Potongan - Maroon', 14000, 'Warna maroon', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(96, 'Nylex Potongan - Merah', 14000, 'Warna merah', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(97, 'Nylex Potongan - Hijau', 14000, 'Warna hijau', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(98, 'Nylex Potongan - Coklat', 14000, 'Warna coklat', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(99, 'Nylex Potongan - Coklat Tua', 14000, 'Warna coklat tua', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(100, 'Nylex Potongan - Hitam', 14000, 'Warna hitam', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(101, 'Nylex Potongan - Putih', 14000, 'Warna putih', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:44', 0, NULL),
(102, 'Nylex Potongan - Pink', 14000, 'Warna pink', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(103, 'Nylex Potongan - Fanta', 14000, 'Warna fanta', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(104, 'Nylex Potongan - Biru Muda', 14000, 'Warna biru muda', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL);

INSERT INTO `products` (`id`, `name`, `price`, `description`, `is_active`, `created_at`, `updated_at`, `track_stock`, `stock`) VALUES
(105, 'Nylex Potongan - Biru BCA', 14000, 'Warna biru BCA', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(106, 'Nylex Potongan - Kuning Pooh', 14000, 'Warna kuning pooh', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(107, 'Nylex Potongan - Kuning Tweety', 14000, 'Warna kuning tweety', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(108, 'Nylex Potongan - Ungu', 14000, 'Warna ungu', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:44', 0, NULL),
(109, 'Nylex Potongan - Cream', 14000, 'Warna cream', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(110, 'Nylex Potongan - Tan', 14000, 'Warna tan', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:44', 0, NULL),
(111, 'Nylex Potongan - Abu Muda', 14000, 'Warna abu muda', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(112, 'Nylex Potongan - Abu Tua', 14000, 'Warna abu tua', 1, '2026-08-09 09:14:38', '2026-08-12 12:04:43', 0, NULL),
(113, 'Vellboa - Maroon', 24500, 'Warna maroon', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(114, 'Vellboa - Merah', 24500, 'Warna merah', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(115, 'Vellboa - Hijau', 24500, 'Warna hijau', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(116, 'Vellboa - Coklat', 24500, 'Warna coklat', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(117, 'Vellboa - Coklat Tua', 24500, 'Warna coklat tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(118, 'Vellboa - Hitam', 24500, 'Warna hitam', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(119, 'Vellboa - Putih', 24500, 'Warna putih', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(120, 'Vellboa - Pink', 24500, 'Warna pink', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(121, 'Vellboa - Fanta', 24500, 'Warna fanta', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(122, 'Vellboa - Biru Muda', 24500, 'Warna biru muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(123, 'Vellboa - Biru BCA', 24500, 'Warna biru BCA', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(124, 'Vellboa - Kuning Pooh', 24500, 'Warna kuning pooh', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(125, 'Vellboa - Kuning Tweety', 24500, 'Warna kuning tweety', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(126, 'Vellboa - Ungu', 24500, 'Warna ungu', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(127, 'Vellboa - Cream', 24500, 'Warna cream', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(128, 'Vellboa - Tan', 24500, 'Warna tan', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(129, 'Vellboa - Abu Muda', 24500, 'Warna abu muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(130, 'Vellboa - Abu Tua', 24500, 'Warna abu tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(131, 'Vellboa Eceran - Maroon', 29000, 'Warna maroon', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(132, 'Vellboa Eceran - Merah', 29000, 'Warna merah', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(133, 'Vellboa Eceran - Hijau', 29000, 'Warna hijau', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(134, 'Vellboa Eceran - Coklat', 29000, 'Warna coklat', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(135, 'Vellboa Eceran - Coklat Tua', 29000, 'Warna coklat tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(136, 'Vellboa Eceran - Hitam', 29000, 'Warna hitam', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(137, 'Vellboa Eceran - Putih', 29000, 'Warna putih', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(138, 'Vellboa Eceran - Pink', 29000, 'Warna pink', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(139, 'Vellboa Eceran - Fanta', 29000, 'Warna fanta', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(140, 'Vellboa Eceran - Biru Muda', 29000, 'Warna biru muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(141, 'Vellboa Eceran - Biru BCA', 29000, 'Warna biru BCA', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(142, 'Vellboa Eceran - Kuning Pooh', 29000, 'Warna kuning pooh', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(143, 'Vellboa Eceran - Kuning Tweety', 29000, 'Warna kuning tweety', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(144, 'Vellboa Eceran - Ungu', 29000, 'Warna ungu', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(145, 'Vellboa Eceran - Cream', 29000, 'Warna cream', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(146, 'Vellboa Eceran - Tan', 29000, 'Warna tan', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(147, 'Vellboa Eceran - Abu Muda', 29000, 'Warna abu muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(148, 'Vellboa Eceran - Abu Tua', 29000, 'Warna abu tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(149, 'Pigment', 10250, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(150, 'Pigment Potongan', 11250, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(151, 'Mika RMP', 160000, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(152, 'Mika Panjang', 300000, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(153, 'Spunbound Amari 35 Gramasi', 135000, 'Gramasi 35', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(154, 'Spunbound Amari 40 Gramasi', 165000, 'Gramasi 40', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL);

INSERT INTO `products` (`id`, `name`, `price`, `description`, `is_active`, `created_at`, `updated_at`, `track_stock`, `stock`) VALUES
(155, 'Spunbound Amari 45 Gramasi', 200000, 'Gramasi 45', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(156, 'Spunbound Amari 50 Gramasi', 225000, 'Gramasi 50', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(157, 'Spunbound Jombang', 135000, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(158, 'Rasfur Lokal - Maroon', 25500, 'Warna maroon', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(159, 'Rasfur Lokal - Merah', 25500, 'Warna merah', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(160, 'Rasfur Lokal - Hijau', 25500, 'Warna hijau', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(161, 'Rasfur Lokal - Coklat', 25500, 'Warna coklat', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(162, 'Rasfur Lokal - Coklat Tua', 25500, 'Warna coklat tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(163, 'Rasfur Lokal - Hitam', 25500, 'Warna hitam', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(164, 'Rasfur Lokal - Putih', 25500, 'Warna putih', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(165, 'Rasfur Lokal - Pink', 25500, 'Warna pink', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(166, 'Rasfur Lokal - Fanta', 25500, 'Warna fanta', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(167, 'Rasfur Lokal - Biru Muda', 25500, 'Warna biru muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(168, 'Rasfur Lokal - Biru BCA', 25500, 'Warna biru BCA', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(169, 'Rasfur Lokal - Kuning Pooh', 25500, 'Warna kuning pooh', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(170, 'Rasfur Lokal - Kuning Tweety', 25500, 'Warna kuning tweety', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(171, 'Rasfur Lokal - Ungu', 25500, 'Warna ungu', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(172, 'Rasfur Lokal - Cream', 25500, 'Warna cream', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(173, 'Rasfur Lokal - Tan', 25500, 'Warna tan', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(174, 'Rasfur Lokal - Abu Muda', 25500, 'Warna abu muda', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(175, 'Rasfur Lokal - Abu Tua', 25500, 'Warna abu tua', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(176, 'Silikon', 430000, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(177, 'Resleting 05', 37000, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(178, 'Resleting 03', 41000, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(179, 'Kepala Resleting Kecil', 55000, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(180, 'Kepala Resleting Besar', 75000, NULL, 1, '2026-08-09 09:14:38', '2026-08-12 09:23:34', 0, NULL),
(181, 'Solasi', 40000, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(182, 'Polymicro', 14000, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(183, 'Polymicro Potongan', 16000, NULL, 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(184, 'Peles - Hitam', 425000, 'Warna hitam', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(185, 'Peles - Putih', 425000, 'Warna putih', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(186, 'Peles - Merah', 425000, 'Warna merah', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(187, 'Peles - Hijau', 425000, 'Warna hijau', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(188, 'Peles - Biru', 425000, 'Warna biru', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(189, 'Peles - Ungu', 425000, 'Warna ungu', 1, '2026-08-09 09:14:38', '2026-08-09 09:14:38', 0, NULL),
(190, 'Rasfur Printing', 29000, NULL, 1, '2026-08-10 11:15:56', '2026-08-10 11:15:56', 0, NULL),
(191, 'Resleting Putih 03', 41000, NULL, 1, '2026-08-10 11:18:33', '2026-08-10 11:18:33', 0, NULL),
(192, 'Resleting Putih 05', 37000, NULL, 1, '2026-08-10 11:18:51', '2026-08-10 11:18:51', 0, NULL),
(193, 'HDP GSM 600 - Ukuran 80', 28500, 'GSM 600, ukuran 80', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(194, 'HDP GSM 600 - Ukuran 90', 31500, 'GSM 600, ukuran 90', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(195, 'HDP GSM 600 - Ukuran 100', 34500, 'GSM 600, ukuran 100', 1, '2026-08-10 11:39:08', '2026-08-14 12:21:02', 1, 100),
(196, 'HDP GSM 600 - Ukuran 120', 41500, 'GSM 600, ukuran 120', 1, '2026-08-10 11:39:08', '2026-08-14 10:03:33', 0, NULL),
(197, 'HDP GSM 600 - Ukuran 140', 48500, 'GSM 600, ukuran 140', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(198, 'HDP GSM 600 - Ukuran 160', 55000, 'GSM 600, ukuran 160', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(199, 'HDP GSM 700 - Ukuran 90', 37000, 'GSM 700, ukuran 90', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(200, 'HDP GSM 700 - Ukuran 100', 41000, 'GSM 700, ukuran 100', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(201, 'HDP GSM 700 - Ukuran 120', 48500, 'GSM 700, ukuran 120', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(202, 'HDP GSM 700 - Ukuran 140', 56500, 'GSM 700, ukuran 140', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(203, 'HDP GSM 700 - Ukuran 160', 64000, 'GSM 700, ukuran 160', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(204, 'HDP GSM 800 - Ukuran 90', 42000, 'GSM 800, ukuran 90', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL);

INSERT INTO `products` (`id`, `name`, `price`, `description`, `is_active`, `created_at`, `updated_at`, `track_stock`, `stock`) VALUES
(205, 'HDP GSM 800 - Ukuran 100', 47000, 'GSM 800, ukuran 100', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(206, 'HDP GSM 800 - Ukuran 120', 55000, 'GSM 800, ukuran 120', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(207, 'HDP GSM 800 - Ukuran 140', 64500, 'GSM 800, ukuran 140', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(208, 'HDP GSM 800 - Ukuran 160', 73500, 'GSM 800, ukuran 160', 1, '2026-08-10 11:39:08', '2026-08-10 11:39:08', 0, NULL),
(209, 'Polkadot Roll', 570000, NULL, 1, '2026-08-12 08:27:56', '2026-08-12 08:27:56', 0, NULL),
(210, 'Polkadot Lipat', 325000, NULL, 1, '2026-08-12 08:28:10', '2026-08-12 08:28:10', 0, NULL),
(211, 'Plastik Kiloan', 47000, NULL, 1, '2026-08-12 08:48:50', '2026-08-12 08:48:50', 0, NULL),
(212, 'Rasfur Printing', 29000, NULL, 1, '2026-08-12 09:21:04', '2026-08-12 09:21:04', 0, NULL),
(213, 'Yelvo Printing', 33000, NULL, 1, '2026-08-12 09:22:37', '2026-08-12 09:22:37', 0, NULL),
(214, 'Yelvo Motif Roll', 29000, NULL, 1, '2026-08-12 09:22:58', '2026-08-12 09:22:58', 0, NULL),
(215, 'Resleting 05 Grosir', 35500, NULL, 1, '2026-08-12 09:24:33', '2026-08-12 09:24:33', 0, NULL),
(216, 'Jagat Roll', 17000, NULL, 1, '2026-08-12 13:06:23', '2026-08-12 13:06:23', 0, NULL),
(217, 'Jagat Ecet', 19000, NULL, 1, '2026-08-12 13:06:42', '2026-08-12 13:06:42', 0, NULL),
(218, 'Polywhite Gsm 70', 11750, NULL, 1, '2026-08-12 13:12:13', '2026-08-12 13:13:43', 0, NULL),
(219, 'Polywhite GSm 80', 12500, NULL, 1, '2026-08-12 13:12:41', '2026-08-12 13:13:50', 0, NULL);

-- ----------------------------------------------------------
-- Tabel `sales`: 51 baris
-- ----------------------------------------------------------
DELETE FROM `sales`;
INSERT INTO `sales` (`id`, `transaction_number`, `sale_date`, `payment_method`, `receivable_party_id`, `receivable_id`, `total_amount`, `received_amount`, `change_amount`, `description`, `created_by`, `created_at`, `updated_at`, `cash_amount`, `transfer_amount`) VALUES
(12, 'SLS-20260811-0002', '2026-08-11 00:00:00', 'transfer', 25, NULL, 640000, 640000, 0, NULL, 1, '2026-08-11 14:06:47', '2026-08-11 14:06:47', 0, 640000),
(14, 'SLS-20260811-0004', '2026-08-11 00:00:00', 'transfer', 28, NULL, 995000, 995000, 0, NULL, 1, '2026-08-11 14:22:32', '2026-08-11 14:22:32', 0, 995000),
(15, 'SLS-20260811-0005', '2026-08-11 00:00:00', 'cash', 55, NULL, 573750, 599000, 25250, NULL, 1, '2026-08-11 14:53:15', '2026-08-11 14:53:15', 599000, NULL),
(16, 'SLS-20260811-0006', '2026-08-11 00:00:00', 'transfer', 56, NULL, 2540000, 2540000, 0, NULL, 1, '2026-08-11 15:10:28', '2026-08-11 15:10:28', 0, 2540000),
(17, 'SLS-20260811-0007', '2026-08-11 00:00:00', 'cash', 32, NULL, 2490000, 2500000, 10000, NULL, 1, '2026-08-11 15:18:54', '2026-08-11 15:18:54', 2500000, NULL),
(18, 'SLS-20260812-0001', '2026-08-12 00:00:00', 'cash', 32, NULL, 320000, 500000, 180000, NULL, 2, '2026-08-12 07:10:18', '2026-08-12 07:10:18', 500000, NULL),
(19, 'SLS-20260812-0002', '2026-08-12 00:00:00', 'cash', 57, NULL, 90000, 100000, 10000, NULL, 2, '2026-08-12 07:13:42', '2026-08-12 07:13:42', 100000, NULL),
(20, 'SLS-20260812-0003', '2026-08-12 00:00:00', 'cash', 32, NULL, 480000, 500000, 20000, NULL, 2, '2026-08-12 07:16:38', '2026-08-12 07:16:38', 500000, NULL),
(21, 'SLS-20260812-0004', '2026-08-12 00:00:00', 'transfer', 26, NULL, 370000, 370000, 0, NULL, 2, '2026-08-12 07:33:08', '2026-08-12 07:33:08', 0, 370000),
(22, 'SLS-20260812-0005', '2026-08-12 00:00:00', 'transfer', 38, NULL, 6010000, 6010000, 0, NULL, 2, '2026-08-12 07:48:13', '2026-08-12 07:48:13', 0, 6010000),
(23, 'SLS-20260812-0006', '2026-08-12 00:00:00', 'transfer', 8, NULL, 2110000, 2110000, 0, NULL, 2, '2026-08-12 07:53:47', '2026-08-12 07:53:47', 0, 2110000),
(24, 'SLS-20260812-0007', '2026-08-12 00:00:00', 'transfer', 17, NULL, 3448500, 3448500, 0, NULL, 2, '2026-08-12 08:07:01', '2026-08-12 08:07:01', 0, 3448500),
(25, 'SLS-20260812-0008', '2026-08-12 00:00:00', 'cash', 58, NULL, 40000, 50000, 10000, NULL, 2, '2026-08-12 08:23:46', '2026-08-12 08:23:46', 50000, NULL),
(26, 'SLS-20260812-0009', '2026-08-12 00:00:00', 'split', 59, NULL, 1506000, 1506000, 0, NULL, 1, '2026-08-12 08:43:16', '2026-08-12 08:43:16', 6000, 1500000),
(27, 'SLS-20260812-0010', '2026-08-12 00:00:00', 'cash', 32, NULL, 1865000, 1906000, 41000, NULL, 1, '2026-08-12 09:05:15', '2026-08-12 09:05:15', 1906000, NULL),
(28, 'SLS-20260812-0011', '2026-08-12 00:00:00', 'receivable', 60, 9, 960000, NULL, NULL, NULL, 1, '2026-08-12 09:11:38', '2026-08-12 09:11:38', NULL, NULL),
(29, 'SLS-20260812-0012', '2026-08-12 00:00:00', 'transfer', 61, NULL, 6543500, 6543500, 0, NULL, 2, '2026-08-12 10:16:59', '2026-08-12 10:16:59', 0, 6543500),
(30, 'SLS-20260812-0013', '2026-08-12 00:00:00', 'transfer', 5, NULL, 4300000, 4300000, 0, NULL, 2, '2026-08-12 10:22:04', '2026-08-12 10:22:04', 0, 4300000),
(31, 'SLS-20260812-0014', '2026-08-12 00:00:00', 'receivable', 47, 10, 10820000, NULL, NULL, NULL, 2, '2026-08-12 11:21:15', '2026-08-12 11:21:15', NULL, NULL),
(32, 'SLS-20260812-0015', '2026-08-12 00:00:00', 'cash', 32, NULL, 461250, 470000, 8750, NULL, 2, '2026-08-12 11:27:54', '2026-08-12 11:27:54', 470000, NULL),
(33, 'SLS-20260812-0016', '2026-08-12 00:00:00', 'transfer', 62, NULL, 300000, 300000, 0, NULL, 1, '2026-08-12 13:09:55', '2026-08-12 13:09:55', 0, 300000),
(34, 'SLS-20260812-0017', '2026-08-12 00:00:00', 'transfer', 63, NULL, 1350000, 1350000, 0, NULL, 2, '2026-08-12 13:23:02', '2026-08-12 13:23:02', 0, 1350000),
(35, 'SLS-20260812-0018', '2026-08-12 00:00:00', 'receivable', 12, 11, 5204920, NULL, NULL, NULL, 2, '2026-08-12 13:36:57', '2026-08-12 13:36:57', NULL, NULL),
(36, 'SLS-20260812-0019', '2026-08-12 00:00:00', 'transfer', 11, NULL, 988500, 988500, 0, NULL, 2, '2026-08-12 13:41:40', '2026-08-12 13:41:40', 0, 988500),
(37, 'SLS-20260812-0020', '2026-08-12 00:00:00', 'cash', 30, NULL, 80000, 80000, 0, NULL, 2, '2026-08-12 14:09:52', '2026-08-12 14:09:52', 80000, NULL),
(38, 'SLS-20260812-0021', '2026-08-12 00:00:00', 'cash', 28, NULL, 550000, 550000, 0, NULL, 2, '2026-08-12 15:21:26', '2026-08-12 15:21:26', 550000, NULL),
(39, 'SLS-20260812-0022', '2026-08-12 00:00:00', 'cash', 65, NULL, 292500, 300000, 7500, NULL, 2, '2026-08-12 15:57:05', '2026-08-12 15:57:05', 300000, NULL),
(40, 'SLS-20260813-0001', '2026-08-13 00:00:00', 'cash', 66, NULL, 658750, 700000, 41250, NULL, 2, '2026-08-13 07:47:43', '2026-08-13 07:47:43', 700000, NULL),
(41, 'SLS-20260813-0002', '2026-08-13 00:00:00', 'transfer', 67, NULL, 120000, 120000, 0, NULL, 2, '2026-08-13 08:21:31', '2026-08-13 08:21:31', 0, 120000),
(42, 'SLS-20260813-0003', '2026-08-13 00:00:00', 'transfer', 68, NULL, 2379000, 2379000, 0, NULL, 2, '2026-08-13 08:33:11', '2026-08-13 08:33:11', 0, 2379000),
(43, 'SLS-20260813-0004', '2026-08-13 00:00:00', 'receivable', 69, 12, 5400000, NULL, NULL, NULL, 2, '2026-08-13 08:39:29', '2026-08-13 08:39:29', NULL, NULL),
(44, 'SLS-20260813-0005', '2026-08-13 00:00:00', 'cash', 70, NULL, 1350000, 1350000, 0, NULL, 2, '2026-08-13 08:48:23', '2026-08-13 08:48:23', 1350000, NULL),
(45, 'SLS-20260813-0006', '2026-08-13 00:00:00', 'transfer', 34, NULL, 3895500, 3895500, 0, NULL, 2, '2026-08-13 09:13:37', '2026-08-13 09:13:37', 0, 3895500),
(46, 'SLS-20260813-0007', '2026-08-13 00:00:00', 'receivable', 13, 13, 2025000, NULL, NULL, NULL, 2, '2026-08-13 09:22:34', '2026-08-13 09:22:34', NULL, NULL),
(47, 'SLS-20260813-0008', '2026-08-13 00:00:00', 'transfer', 11, NULL, 3150000, 3150000, 0, NULL, 2, '2026-08-13 09:35:06', '2026-08-13 09:35:06', 0, 3150000),
(48, 'SLS-20260813-0009', '2026-08-13 00:00:00', 'transfer', 15, NULL, 1230000, 1230000, 0, NULL, 2, '2026-08-13 09:37:37', '2026-08-13 09:37:37', 0, 1230000),
(49, 'SLS-20260813-0010', '2026-08-13 00:00:00', 'transfer', 12, NULL, 2735000, 2735000, 0, NULL, 2, '2026-08-13 10:22:24', '2026-08-13 10:22:24', 0, 2735000),
(50, 'SLS-20260813-0011', '2026-08-13 00:00:00', 'transfer', 63, NULL, 1200000, 1200000, 0, NULL, 2, '2026-08-13 12:57:49', '2026-08-13 12:57:49', 0, 1200000),
(51, 'SLS-20260813-0012', '2026-08-13 00:00:00', 'receivable', 42, 14, 1350000, NULL, NULL, NULL, 2, '2026-08-13 15:38:12', '2026-08-13 15:38:12', NULL, NULL),
(52, 'SLS-20260813-0013', '2026-08-13 00:00:00', 'transfer', 23, NULL, 9640400, 9640400, 0, NULL, 2, '2026-08-13 15:49:07', '2026-08-13 15:49:07', 0, 9640400),
(53, 'SLS-20260813-0014', '2026-08-13 00:00:00', 'transfer', 53, NULL, 1110000, 1110000, 0, NULL, 2, '2026-08-13 15:55:38', '2026-08-13 15:55:38', 0, 1110000),
(54, 'SLS-20260814-0001', '2026-08-14 00:00:00', 'cash', 55, NULL, 562500, 600000, 37500, NULL, 2, '2026-08-14 08:18:21', '2026-08-14 08:18:21', 600000, NULL),
(55, 'SLS-20260814-0002', '2026-08-14 00:00:00', 'cash', 55, NULL, 562500, 600000, 37500, NULL, 2, '2026-08-14 08:19:57', '2026-08-14 08:19:57', 600000, NULL),
(56, 'SLS-20260814-0003', '2026-08-14 00:00:00', 'transfer', 25, NULL, 4089000, 4089000, 0, NULL, 2, '2026-08-14 08:23:06', '2026-08-14 08:23:06', 0, 4089000),
(57, 'SLS-20260814-0004', '2026-08-14 00:00:00', 'receivable', 13, 15, 390570, NULL, NULL, NULL, 2, '2026-08-14 08:24:37', '2026-08-14 08:24:37', NULL, NULL),
(58, 'SLS-20260814-0005', '2026-08-14 00:00:00', 'receivable', 71, 16, 1350000, NULL, NULL, NULL, 2, '2026-08-14 08:29:36', '2026-08-14 08:29:36', NULL, NULL),
(59, 'SLS-20260814-0006', '2026-08-14 00:00:00', 'transfer', 68, NULL, 144000, 144000, 0, NULL, 2, '2026-08-14 09:00:12', '2026-08-14 09:00:12', 0, 144000),
(60, 'SLS-20260814-0007', '2026-08-14 00:00:00', 'transfer', 72, NULL, 5095000, 5095000, 0, NULL, 2, '2026-08-14 09:12:06', '2026-08-14 09:12:06', 0, 5095000),
(61, 'SLS-20260814-0008', '2026-08-14 00:00:00', 'transfer', 12, NULL, 3471000, 3471000, 0, NULL, 1, '2026-08-14 10:04:32', '2026-08-14 10:04:32', 0, 3471000),
(62, 'SLS-20260814-0009', '2026-08-14 00:00:00', 'cash', 73, NULL, 188000, 200000, 12000, NULL, 1, '2026-08-14 10:43:00', '2026-08-14 10:43:00', 200000, NULL);

INSERT INTO `sales` (`id`, `transaction_number`, `sale_date`, `payment_method`, `receivable_party_id`, `receivable_id`, `total_amount`, `received_amount`, `change_amount`, `description`, `created_by`, `created_at`, `updated_at`, `cash_amount`, `transfer_amount`) VALUES
(63, 'SLS-20260814-0010', '2026-08-14 00:00:00', 'transfer', 74, NULL, 5382000, 5382000, 0, NULL, 1, '2026-08-14 11:26:59', '2026-08-14 11:26:59', 0, 5382000);

-- ----------------------------------------------------------
-- Tabel `sale_items`: 117 baris
-- ----------------------------------------------------------
DELETE FROM `sale_items`;
INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`, `created_at`, `updated_at`) VALUES
(27, 12, 151, 'Mika RMP', 160000, 4, 640000, '2026-08-11 14:06:47', '2026-08-11 14:06:47'),
(29, 14, 196, 'HDP GSM 600 - Ukuran 120', 41500, 20, 830000, '2026-08-11 14:22:32', '2026-08-11 14:22:32'),
(30, 14, 154, 'Spunbound Amari 40 Gramasi', 165000, 1, 165000, '2026-08-11 14:22:32', '2026-08-11 14:22:32'),
(31, 15, 150, 'Pigment Potongan', 11250, 51, 573750, '2026-08-11 14:53:15', '2026-08-11 14:53:15'),
(32, 16, 88, 'Nylex - Kuning Pooh', 12000, 200, 2400000, '2026-08-11 15:10:28', '2026-08-11 15:10:28'),
(33, 16, 101, 'Nylex Potongan - Putih', 14000, 10, 140000, '2026-08-11 15:10:28', '2026-08-11 15:10:28'),
(34, 17, 195, 'HDP GSM 600 - Ukuran 100', 34500, 20, 690000, '2026-08-11 15:18:54', '2026-08-11 15:18:54'),
(35, 17, 193, 'HDP GSM 600 - Ukuran 80', 28500, 30, 855000, '2026-08-11 15:18:54', '2026-08-11 15:18:54'),
(36, 17, 194, 'HDP GSM 600 - Ukuran 90', 31500, 30, 945000, '2026-08-11 15:18:54', '2026-08-11 15:18:54'),
(37, 18, 151, 'Mika RMP', 160000, 2, 320000, '2026-08-12 07:10:18', '2026-08-12 07:10:18'),
(38, 19, 150, 'Pigment Potongan', 11250, 8, 90000, '2026-08-12 07:13:42', '2026-08-12 07:13:42'),
(39, 20, 151, 'Mika RMP', 160000, 3, 480000, '2026-08-12 07:16:38', '2026-08-12 07:16:38'),
(40, 21, 177, 'Resleting 05', 37000, 10, 370000, '2026-08-12 07:33:08', '2026-08-12 07:33:08'),
(41, 22, 206, 'HDP GSM 800 - Ukuran 120', 55000, 40, 2200000, '2026-08-12 07:48:13', '2026-08-12 07:48:13'),
(42, 22, 207, 'HDP GSM 800 - Ukuran 140', 64500, 50, 3225000, '2026-08-12 07:48:13', '2026-08-12 07:48:13'),
(43, 22, 150, 'Pigment Potongan', 11250, 52, 585000, '2026-08-12 07:48:13', '2026-08-12 07:48:13'),
(44, 23, 154, 'Spunbound Amari 40 Gramasi', 165000, 3, 495000, '2026-08-12 07:53:47', '2026-08-12 07:53:47'),
(45, 23, 82, 'Nylex - Hitam', 12000, 97, 1164000, '2026-08-12 07:53:47', '2026-08-12 07:53:47'),
(46, 23, 178, 'Resleting 03', 41000, 11, 451000, '2026-08-12 07:53:47', '2026-08-12 07:53:47'),
(47, 24, 176, 'Silikon', 430000, 5, 2150000, '2026-08-12 08:07:01', '2026-08-12 08:07:01'),
(48, 24, 47, 'Rasfur KSS 17 - Putih', 24500, 53, 1298500, '2026-08-12 08:07:01', '2026-08-12 08:07:01'),
(49, 25, 181, 'Solasi', 40000, 1, 40000, '2026-08-12 08:23:46', '2026-08-12 08:23:46'),
(50, 26, 149, 'Pigment', 10250, 120, 1230000, '2026-08-12 08:43:16', '2026-08-12 08:43:16'),
(51, 26, 154, 'Spunbound Amari 40 Gramasi', 165000, 1, 165000, '2026-08-12 08:43:16', '2026-08-12 08:43:16'),
(52, 26, 177, 'Resleting 05', 37000, 3, 111000, '2026-08-12 08:43:16', '2026-08-12 08:43:16'),
(53, 27, 196, 'HDP GSM 600 - Ukuran 120', 41500, 20, 830000, '2026-08-12 09:05:15', '2026-08-12 09:05:15'),
(54, 27, 197, 'HDP GSM 600 - Ukuran 140', 48500, 10, 485000, '2026-08-12 09:05:15', '2026-08-12 09:05:15'),
(55, 27, 198, 'HDP GSM 600 - Ukuran 160', 55000, 10, 550000, '2026-08-12 09:05:15', '2026-08-12 09:05:15'),
(56, 28, 51, 'Rasfur KSS 17 - Biru BCA', 16000, 60, 960000, '2026-08-12 09:11:38', '2026-08-12 09:11:38'),
(57, 29, 167, 'Rasfur Lokal - Biru Muda', 25500, 150, 3825000, '2026-08-12 10:16:59', '2026-08-12 10:16:59'),
(58, 29, 119, 'Vellboa - Putih', 24500, 61, 1494500, '2026-08-12 10:16:59', '2026-08-12 10:16:59'),
(59, 29, 169, 'Rasfur Lokal - Kuning Pooh', 25500, 48, 1224000, '2026-08-12 10:16:59', '2026-08-12 10:16:59'),
(60, 30, 176, 'Silikon', 430000, 10, 4300000, '2026-08-12 10:22:04', '2026-08-12 10:22:04'),
(61, 31, 176, 'Silikon', 430000, 17, 7310000, '2026-08-12 11:21:15', '2026-08-12 11:21:15'),
(62, 31, 87, 'Nylex - Biru BCA', 12000, 87, 1044000, '2026-08-12 11:21:15', '2026-08-12 11:21:15'),
(63, 31, 78, 'Nylex - Merah', 12000, 100, 1200000, '2026-08-12 11:21:15', '2026-08-12 11:21:15'),
(64, 31, 82, 'Nylex - Hitam', 12000, 100, 1200000, '2026-08-12 11:21:15', '2026-08-12 11:21:15'),
(65, 31, 213, 'Yelvo Printing', 33000, 2, 66000, '2026-08-12 11:21:15', '2026-08-12 11:21:15'),
(66, 32, 150, 'Pigment Potongan', 11250, 41, 461250, '2026-08-12 11:27:54', '2026-08-12 11:27:54'),
(67, 33, 152, 'Mika Panjang', 300000, 1, 300000, '2026-08-12 13:09:55', '2026-08-12 13:09:55'),
(68, 34, 218, 'Polywhite Gsm 70', 11250, 120, 1350000, '2026-08-12 13:23:02', '2026-08-12 13:23:02'),
(69, 35, 182, 'Polymicro', 14250, 100, 1425000, '2026-08-12 13:36:57', '2026-08-12 13:36:57'),
(70, 35, 154, 'Spunbound Amari 40 Gramasi', 165000, 2, 330000, '2026-08-12 13:36:57', '2026-08-12 13:36:57'),
(71, 35, 196, 'HDP GSM 600 - Ukuran 120', 41496, 20, 829920, '2026-08-12 13:36:57', '2026-08-12 13:36:57'),
(72, 35, 197, 'HDP GSM 600 - Ukuran 140', 48500, 20, 970000, '2026-08-12 13:36:57', '2026-08-12 13:36:57'),
(73, 35, 198, 'HDP GSM 600 - Ukuran 160', 55000, 30, 1650000, '2026-08-12 13:36:57', '2026-08-12 13:36:57'),
(74, 36, 150, 'Pigment Potongan', 11250, 42, 472500, '2026-08-12 13:41:40', '2026-08-12 13:41:40'),
(75, 36, 177, 'Resleting 05', 37000, 3, 111000, '2026-08-12 13:41:40', '2026-08-12 13:41:40'),
(76, 36, 157, 'Spunbound Jombang', 135000, 3, 405000, '2026-08-12 13:41:40', '2026-08-12 13:41:40'),
(77, 37, 183, 'Polymicro Potongan', 16000, 5, 80000, '2026-08-12 14:09:52', '2026-08-12 14:09:52');

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`, `created_at`, `updated_at`) VALUES
(78, 38, 198, 'HDP GSM 600 - Ukuran 160', 55000, 10, 550000, '2026-08-12 15:21:26', '2026-08-12 15:21:26'),
(79, 39, 150, 'Pigment Potongan', 11250, 26, 292500, '2026-08-12 15:57:05', '2026-08-12 15:57:05'),
(80, 40, 150, 'Pigment Potongan', 11250, 55, 618750, '2026-08-13 07:47:43', '2026-08-13 07:47:43'),
(81, 40, 181, 'Solasi', 40000, 1, 40000, '2026-08-13 07:47:43', '2026-08-13 07:47:43'),
(82, 41, 181, 'Solasi', 40000, 3, 120000, '2026-08-13 08:21:31', '2026-08-13 08:21:31'),
(83, 42, 176, 'Silikon', 430000, 1, 430000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(84, 42, 154, 'Spunbound Amari 40 Gramasi', 165000, 1, 165000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(85, 42, 183, 'Polymicro Potongan', 16000, 36, 576000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(86, 42, 181, 'Solasi', 40000, 2, 80000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(87, 42, 152, 'Mika Panjang', 300000, 1, 300000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(88, 42, 36, 'Rasfur Mtex 17 Potongan - Ungu', 23000, 7, 161000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(89, 42, 23, 'Rasfur Mtex 17 Potongan - Maroon', 23000, 6, 138000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(90, 42, 25, 'Rasfur Mtex 17 Potongan - Hijau', 23000, 6, 138000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(91, 42, 33, 'Rasfur Mtex 17 Potongan - Biru BCA', 23000, 5, 115000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(92, 42, 40, 'Rasfur Mtex 17 Potongan - Abu Tua', 23000, 6, 138000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(93, 42, 27, 'Rasfur Mtex 17 Potongan - Coklat Tua', 23000, 6, 138000, '2026-08-13 08:33:11', '2026-08-13 08:33:11'),
(94, 43, 218, 'Polywhite Gsm 70', 11250, 480, 5400000, '2026-08-13 08:39:29', '2026-08-13 08:39:29'),
(95, 44, 150, 'Pigment Potongan', 11250, 120, 1350000, '2026-08-13 08:48:23', '2026-08-13 08:48:23'),
(96, 45, 48, 'Rasfur KSS 17 - Pink', 24500, 107, 2621500, '2026-08-13 09:13:37', '2026-08-13 09:13:37'),
(97, 45, 54, 'Rasfur KSS 17 - Ungu', 24500, 52, 1274000, '2026-08-13 09:13:37', '2026-08-13 09:13:37'),
(98, 46, 219, 'Polywhite GSm 80', 12750, 120, 1530000, '2026-08-13 09:22:34', '2026-08-13 09:22:34'),
(99, 46, 154, 'Spunbound Amari 40 Gramasi', 165000, 3, 495000, '2026-08-13 09:22:34', '2026-08-13 09:22:34'),
(100, 47, 194, 'HDP GSM 600 - Ukuran 90', 31500, 100, 3150000, '2026-08-13 09:35:06', '2026-08-13 09:35:06'),
(101, 48, 149, 'Pigment', 10250, 120, 1230000, '2026-08-13 09:37:37', '2026-08-13 09:37:37'),
(102, 49, 195, 'HDP GSM 600 - Ukuran 100', 34500, 10, 345000, '2026-08-13 10:22:24', '2026-08-13 10:22:24'),
(103, 49, 196, 'HDP GSM 600 - Ukuran 120', 40000, 20, 800000, '2026-08-13 10:22:24', '2026-08-13 10:22:24'),
(104, 49, 182, 'Polymicro', 14250, 100, 1425000, '2026-08-13 10:22:24', '2026-08-13 10:22:24'),
(105, 49, 154, 'Spunbound Amari 40 Gramasi', 165000, 1, 165000, '2026-08-13 10:22:24', '2026-08-13 10:22:24'),
(106, 50, 83, 'Nylex - Putih', 12000, 100, 1200000, '2026-08-13 12:57:49', '2026-08-13 12:57:49'),
(107, 51, 213, 'Yelvo Printing', 27000, 50, 1350000, '2026-08-13 15:38:12', '2026-08-13 15:38:12'),
(108, 52, 149, 'Pigment', 10250, 360, 3690000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(109, 52, 177, 'Resleting 05', 37000, 3, 111000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(110, 52, 180, 'Kepala Resleting Besar', 75000, 1, 75000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(111, 52, 151, 'Mika RMP', 160000, 5, 800000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(112, 52, 154, 'Spunbound Amari 40 Gramasi', 165000, 3, 495000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(113, 52, 42, 'Rasfur KSS 17 - Merah', 16000, 60, 960000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(114, 52, 43, 'Rasfur KSS 17 - Hijau', 16000, 61, 976000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(115, 52, 51, 'Rasfur KSS 17 - Biru BCA', 16000, 58, 928000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(116, 52, 45, 'Rasfur KSS 17 - Coklat Tua', 16000, 56, 896000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(117, 52, 150, 'Pigment Potongan', 11250, 20, 225000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(118, 52, 211, 'Plastik Kiloan', 47000, 5.2, 244400, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(119, 52, 183, 'Polymicro Potongan', 16000, 15, 240000, '2026-08-13 15:49:07', '2026-08-13 15:49:07'),
(120, 53, 199, 'HDP GSM 700 - Ukuran 90', 37000, 30, 1110000, '2026-08-13 15:55:38', '2026-08-13 15:55:38'),
(121, 54, 150, 'Pigment Potongan', 11250, 50, 562500, '2026-08-14 08:18:22', '2026-08-14 08:18:22'),
(122, 55, 150, 'Pigment Potongan', 11250, 50, 562500, '2026-08-14 08:19:57', '2026-08-14 08:19:57'),
(123, 56, 204, 'HDP GSM 800 - Ukuran 90', 42000, 50, 2100000, '2026-08-14 08:23:06', '2026-08-14 08:23:06'),
(124, 56, 206, 'HDP GSM 800 - Ukuran 120', 55000, 20, 1100000, '2026-08-14 08:23:06', '2026-08-14 08:23:06'),
(125, 56, 151, 'Mika RMP', 160000, 2, 320000, '2026-08-14 08:23:06', '2026-08-14 08:23:06'),
(126, 56, 154, 'Spunbound Amari 40 Gramasi', 165000, 3, 495000, '2026-08-14 08:23:06', '2026-08-14 08:23:06'),
(127, 56, 177, 'Resleting 05', 37000, 2, 74000, '2026-08-14 08:23:06', '2026-08-14 08:23:06');

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`, `created_at`, `updated_at`) VALUES
(128, 57, 211, 'Plastik Kiloan', 47000, 8.31, 390570, '2026-08-14 08:24:37', '2026-08-14 08:24:37'),
(129, 58, 214, 'Yelvo Motif Roll', 27000, 50, 1350000, '2026-08-14 08:29:36', '2026-08-14 08:29:36'),
(130, 59, 183, 'Polymicro Potongan', 16000, 9, 144000, '2026-08-14 09:00:12', '2026-08-14 09:00:12'),
(131, 60, 154, 'Spunbound Amari 40 Gramasi', 165000, 20, 3300000, '2026-08-14 09:12:06', '2026-08-14 09:12:06'),
(132, 60, 180, 'Kepala Resleting Besar', 75000, 5, 375000, '2026-08-14 09:12:06', '2026-08-14 09:12:06'),
(133, 60, 215, 'Resleting 05 Grosir', 35500, 40, 1420000, '2026-08-14 09:12:06', '2026-08-14 09:12:06'),
(134, 61, 194, 'HDP GSM 600 - Ukuran 90', 31500, 30, 945000, '2026-08-14 10:04:32', '2026-08-14 10:04:32'),
(135, 61, 195, 'HDP GSM 600 - Ukuran 100', 34500, 20, 690000, '2026-08-14 10:04:32', '2026-08-14 10:04:32'),
(136, 61, 196, 'HDP GSM 600 - Ukuran 120', 41500, 30, 1245000, '2026-08-14 10:04:32', '2026-08-14 10:04:32'),
(137, 61, 198, 'HDP GSM 600 - Ukuran 160', 55000, 10, 550000, '2026-08-14 10:04:32', '2026-08-14 10:04:32'),
(138, 61, 191, 'Resleting Putih 03', 41000, 1, 41000, '2026-08-14 10:04:32', '2026-08-14 10:04:32'),
(139, 62, 211, 'Plastik Kiloan', 47000, 4, 188000, '2026-08-14 10:43:00', '2026-08-14 10:43:00'),
(140, 63, 41, 'Rasfur KSS 17 - Maroon', 16000, 62, 992000, '2026-08-14 11:26:59', '2026-08-14 11:26:59'),
(141, 63, 51, 'Rasfur KSS 17 - Biru BCA', 16000, 60, 960000, '2026-08-14 11:26:59', '2026-08-14 11:26:59'),
(142, 63, 45, 'Rasfur KSS 17 - Coklat Tua', 16000, 61, 976000, '2026-08-14 11:26:59', '2026-08-14 11:26:59'),
(143, 63, 58, 'Rasfur KSS 17 - Abu Tua', 16000, 69, 1104000, '2026-08-14 11:26:59', '2026-08-14 11:26:59'),
(144, 63, 157, 'Spunbound Jombang', 135000, 10, 1350000, '2026-08-14 11:26:59', '2026-08-14 11:26:59');

-- ----------------------------------------------------------
-- Tabel `activity_logs`: 3 baris
-- ----------------------------------------------------------
DELETE FROM `activity_logs`;
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `module`, `action`, `description`, `subject_type`, `subject_id`, `properties`, `ip_address`, `created_at`) VALUES
(1, 1, 'risky', 'Pelanggan', 'create', 'Menambah data pelanggan piutang baru \'Bu Alfiyah\'', 'App\\Models\\ReceivableParty', 74, '{\"name\":\"Bu Alfiyah\",\"phone\":null}', '127.0.0.1', '2026-08-14 11:25:51'),
(2, 1, 'risky', 'Penjualan', 'create', 'Memproses transaksi penjualan SLS-20260814-0010 (Transfer) senilai Rp 5.382.000 untuk pelanggan Bu Alfiyah', 'App\\Models\\Sale', 63, '{\"total\":5382000,\"payment_method\":\"transfer\",\"party\":\"Bu Alfiyah\"}', '127.0.0.1', '2026-08-14 11:26:59'),
(3, 1, 'risky', 'Produk', 'update', 'Mengubah data produk \'HDP GSM 600 - Ukuran 100\'', 'App\\Models\\Product', 195, NULL, '127.0.0.1', '2026-08-14 12:21:02');


SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================
-- Export selesai. Semoga sukses deploy di cPanel! 🎉
-- ============================================================