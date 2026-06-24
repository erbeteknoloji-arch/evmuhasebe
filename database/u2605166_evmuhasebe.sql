-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 23 Haz 2026, 09:21:00
-- Sunucu sürümü: 10.6.27-MariaDB
-- PHP Sürümü: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `u2605166_evmuhasebe`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `accounts`
--

CREATE TABLE `accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` enum('cash','bank','credit_card') NOT NULL DEFAULT 'bank',
  `bank_name` varchar(120) DEFAULT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `accounts`
--

INSERT INTO `accounts` (`id`, `household_id`, `name`, `type`, `bank_name`, `opening_balance`, `is_active`, `created_at`) VALUES
(3, 3, 'Garanti  Maaş', 'bank', 'Garanti  Maaş', 70000.00, 1, '2026-06-07 13:45:26'),
(4, 4, 'Maaş Hesabım', 'bank', 'Garanti Bankası', -4373.86, 1, '2026-06-07 20:52:10'),
(5, 7, 'Maaş Hesap (5000 Ek Hesap)', 'bank', 'Garanti  Maaş', -4924.00, 1, '2026-06-23 09:07:12');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `detail` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `activity_log`
--

INSERT INTO `activity_log` (`id`, `household_id`, `user_id`, `action`, `detail`, `created_at`) VALUES
(3, 2, 2, 'household_create', 'Ev oluşturuldu: Evim', '2026-06-05 09:49:06'),
(6, 3, 3, 'household_create', 'Ev oluşturuldu: Evim', '2026-06-05 10:06:05'),
(8, 4, 4, 'household_create', 'Ev oluşturuldu: Evim', '2026-06-05 15:31:25'),
(17, 3, 3, 'import_commit', '44 işlem içe aktarıldı (Yapıştırılan metin)', '2026-06-07 13:40:43'),
(18, 3, 3, 'goal_create', 'EV · 5.000.000,00 ₺', '2026-06-07 13:44:06'),
(19, 3, 3, 'goal_contribute', 'EV · 1.500.000,00 ₺', '2026-06-07 13:44:35'),
(23, 4, 4, 'import_commit', '42 işlem içe aktarıldı (Yapıştırılan metin)', '2026-06-07 20:46:30'),
(24, 4, 4, 'transaction_create', 'Maaş · 43.200,00 ₺', '2026-06-07 20:49:09'),
(25, 4, 4, 'transaction_delete', 'İşlem silindi #132', '2026-06-07 20:49:56'),
(26, 4, 4, 'transaction_delete', 'İşlem silindi #131', '2026-06-07 20:50:07'),
(27, 4, 4, 'transaction_delete', 'İşlem silindi #100', '2026-06-07 20:52:45'),
(28, 4, 4, 'transaction_delete', 'İşlem silindi #121', '2026-06-07 20:52:57'),
(29, 4, 4, 'transaction_delete', 'İşlem silindi #126', '2026-06-07 20:53:00'),
(30, 4, 4, 'transaction_delete', 'İşlem silindi #120', '2026-06-07 20:53:04'),
(31, 4, 4, 'transaction_delete', 'İşlem silindi #119', '2026-06-07 20:53:11'),
(32, 4, 4, 'transaction_delete', 'İşlem silindi #106', '2026-06-07 20:53:20'),
(33, 4, 4, 'transaction_delete', 'İşlem silindi #99', '2026-06-07 20:53:24'),
(34, 4, 4, 'transaction_delete', 'İşlem silindi #94', '2026-06-07 20:53:28'),
(35, 4, 4, 'transaction_delete', 'İşlem silindi #108', '2026-06-07 20:53:32'),
(36, 4, 4, 'transaction_delete', 'İşlem silindi #105', '2026-06-07 20:53:34'),
(37, 4, 4, 'transaction_delete', 'İşlem silindi #104', '2026-06-07 20:53:37'),
(38, 4, 4, 'transaction_delete', 'İşlem silindi #93', '2026-06-07 20:53:39'),
(39, 4, 4, 'transaction_delete', 'İşlem silindi #92', '2026-06-07 20:53:45'),
(40, 4, 4, 'transaction_delete', 'İşlem silindi #118', '2026-06-07 20:53:49'),
(41, 4, 4, 'transaction_delete', 'İşlem silindi #107', '2026-06-07 20:53:54'),
(42, 4, 4, 'transaction_delete', 'İşlem silindi #117', '2026-06-07 20:53:57'),
(43, 4, 4, 'transaction_delete', 'İşlem silindi #116', '2026-06-07 20:54:00'),
(44, 4, 4, 'transaction_delete', 'İşlem silindi #90', '2026-06-07 20:54:03'),
(45, 4, 4, 'transaction_delete', 'İşlem silindi #125', '2026-06-07 20:54:06'),
(46, 4, 4, 'transaction_delete', 'İşlem silindi #102', '2026-06-07 20:54:10'),
(47, 4, 4, 'transaction_delete', 'İşlem silindi #115', '2026-06-07 20:54:13'),
(48, 4, 4, 'transaction_delete', 'İşlem silindi #123', '2026-06-07 20:54:16'),
(49, 4, 4, 'transaction_delete', 'İşlem silindi #111', '2026-06-07 20:54:23'),
(50, 4, 4, 'transaction_delete', 'İşlem silindi #98', '2026-06-07 20:54:29'),
(51, 4, 4, 'transaction_delete', 'İşlem silindi #114', '2026-06-07 20:54:32'),
(52, 4, 4, 'transaction_delete', 'İşlem silindi #97', '2026-06-07 20:54:35'),
(53, 4, 4, 'transaction_delete', 'İşlem silindi #113', '2026-06-07 20:54:38'),
(54, 4, 4, 'transaction_delete', 'İşlem silindi #110', '2026-06-07 20:54:41'),
(55, 4, 4, 'transaction_delete', 'İşlem silindi #129', '2026-06-07 20:54:44'),
(56, 4, 4, 'transaction_delete', 'İşlem silindi #112', '2026-06-07 20:54:48'),
(57, 4, 4, 'transaction_delete', 'İşlem silindi #128', '2026-06-07 20:54:52'),
(58, 4, 4, 'transaction_delete', 'İşlem silindi #103', '2026-06-07 20:54:55'),
(59, 4, 4, 'transaction_delete', 'İşlem silindi #91', '2026-06-07 20:54:57'),
(60, 4, 4, 'transaction_delete', 'İşlem silindi #96', '2026-06-07 20:55:00'),
(62, 4, 4, 'transaction_delete', 'İşlem silindi #130', '2026-06-07 20:55:03'),
(63, 4, 4, 'transaction_delete', 'İşlem silindi #127', '2026-06-07 20:55:06'),
(64, 4, 4, 'transaction_delete', 'İşlem silindi #109', '2026-06-07 20:55:09'),
(65, 4, 4, 'transaction_delete', 'İşlem silindi #124', '2026-06-07 20:55:12'),
(66, 4, 4, 'transaction_delete', 'İşlem silindi #122', '2026-06-07 20:55:14'),
(67, 4, 4, 'transaction_delete', 'İşlem silindi #101', '2026-06-07 20:55:17'),
(68, 4, 4, 'transaction_delete', 'İşlem silindi #95', '2026-06-07 20:55:19'),
(69, 4, 4, 'scheduled_create', 'Maaş ödemesi · 45.000,00 ₺', '2026-06-07 20:57:02'),
(70, 4, 4, 'member_invite', 'Davet: nur.kaderg28@gmail.com', '2026-06-07 20:58:00'),
(71, 4, 4, 'goal_create', 'Sksksk · 1.550,00 ₺', '2026-06-07 21:05:48'),
(72, 4, 4, 'shopping_list_create', 'Pazar alış veriş i', '2026-06-07 21:07:03'),
(73, 4, 4, 'scheduled_pay', 'Maaş ödemesi · 45.000,00 ₺', '2026-06-07 21:09:13'),
(74, 4, 4, 'scheduled_pay', 'Maaş ödemesi · 45.000,00 ₺', '2026-06-07 21:09:14'),
(75, 4, 4, 'transaction_delete', 'İşlem silindi #134', '2026-06-07 21:10:09'),
(76, 4, 4, 'transaction_delete', 'İşlem silindi #133', '2026-06-07 21:10:12'),
(77, 6, 5, 'household_create', 'Ev oluşturuldu: Evim', '2026-06-07 22:48:33'),
(78, 6, 4, 'member_join', 'Mert Pektaş eve katıldı', '2026-06-07 22:52:14'),
(81, 2, 2, 'shopping_list_create', 'Market', '2026-06-08 18:03:01'),
(83, 4, 4, 'import_commit', '42 işlem içe aktarıldı (Yapıştırılan metin)', '2026-06-10 13:24:35'),
(84, 7, 1, 'household_create', 'Ev oluşturuldu: Home', '2026-06-23 08:56:27'),
(85, 7, 1, 'member_invite', 'Davet: mervesencer12@hotmail.com', '2026-06-23 08:59:13'),
(86, 7, 1, 'scheduled_create', 'Ev kirası · 19.000,00 ₺', '2026-06-23 09:01:37'),
(87, 7, 1, 'scheduled_create', 'Yeşilköy Maaşı · 30.000,00 ₺', '2026-06-23 09:02:10'),
(88, 7, 1, 'scheduled_create', 'Otel Maaş · 48.000,00 ₺', '2026-06-23 09:02:53'),
(89, 7, 1, 'scheduled_create', 'Avans · 20.000,00 ₺', '2026-06-23 09:03:15'),
(90, 7, 3, 'member_join', 'Merve  Pektaş eve katıldı', '2026-06-23 09:04:00'),
(91, 7, 3, 'scheduled_create', 'Merve maaş · 70.000,00 ₺', '2026-06-23 09:07:00'),
(92, 7, 3, 'scheduled_pay', 'Merve maaş · 70.000,00 ₺', '2026-06-23 09:07:36'),
(93, 7, 1, 'transaction_create', 'Yanlış işlem · 70.000,00 ₺', '2026-06-23 09:11:17'),
(94, 7, 1, 'transaction_delete', 'İşlem silindi #180', '2026-06-23 09:13:42'),
(95, 7, 1, 'transaction_delete', 'İşlem silindi #179', '2026-06-23 09:13:47');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `asset_holdings`
--

CREATE TABLE `asset_holdings` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `asset_code` varchar(16) NOT NULL,
  `label` varchar(80) DEFAULT NULL,
  `quantity` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `cost_basis_try` decimal(14,2) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `asset_holdings`
--

INSERT INTO `asset_holdings` (`id`, `household_id`, `asset_code`, `label`, `quantity`, `cost_basis_try`, `created_by`, `created_at`) VALUES
(1, 4, 'TAM', 'Tam Altın', 1.0000, NULL, 4, '2026-06-06 15:23:20'),
(2, 3, 'XAU_GRAM', 'Gram Altın', 10.0000, 6200.00, 3, '2026-06-07 13:43:00'),
(3, 4, 'TAM', 'Tam Altın', 10.0000, NULL, 4, '2026-06-07 21:04:14'),
(4, 4, 'EUR', 'Euro', 10.0000, 28.00, 4, '2026-06-07 21:04:37'),
(6, 4, 'XAU_GRAM', 'Gram Altın', 10.0000, 70000.00, 4, '2026-06-07 21:27:03');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `budgets`
--

CREATE TABLE `budgets` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `monthly_limit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `budgets`
--

INSERT INTO `budgets` (`id`, `household_id`, `category_id`, `monthly_limit`, `created_at`) VALUES
(9, 3, 53, 10000.00, '2026-06-07 13:48:03'),
(11, 4, 77, 120.00, '2026-06-07 21:13:01');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6B7280',
  `icon` varchar(8) NOT NULL DEFAULT '?',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `household_id`, `name`, `type`, `color`, `icon`, `is_archived`, `created_at`) VALUES
(22, 2, 'Market & Gıda', 'expense', '#E0533D', '🛒', 0, '2026-06-05 09:49:05'),
(23, 2, 'Kira', 'expense', '#B45309', '🏠', 0, '2026-06-05 09:49:05'),
(24, 2, 'Faturalar', 'expense', '#0E7490', '🧾', 0, '2026-06-05 09:49:05'),
(25, 2, 'Elektrik', 'expense', '#CA8A04', '⚡', 0, '2026-06-05 09:49:05'),
(26, 2, 'Su', 'expense', '#0891B2', '💧', 0, '2026-06-05 09:49:05'),
(27, 2, 'Doğalgaz', 'expense', '#EA580C', '🔥', 0, '2026-06-05 09:49:05'),
(28, 2, 'İnternet & Telefon', 'expense', '#7C3AED', '📶', 0, '2026-06-05 09:49:05'),
(29, 2, 'Ulaşım & Yakıt', 'expense', '#1D4ED8', '⛽', 0, '2026-06-05 09:49:05'),
(30, 2, 'Sağlık', 'expense', '#DC2626', '💊', 0, '2026-06-05 09:49:05'),
(31, 2, 'Eğitim', 'expense', '#2563EB', '📚', 0, '2026-06-05 09:49:05'),
(32, 2, 'Giyim', 'expense', '#DB2777', '👕', 0, '2026-06-05 09:49:05'),
(33, 2, 'Eğlence', 'expense', '#9333EA', '🎬', 0, '2026-06-05 09:49:05'),
(34, 2, 'Abonelikler', 'expense', '#0D9488', '🔁', 0, '2026-06-05 09:49:05'),
(35, 2, 'Restoran & Kafe', 'expense', '#D97706', '☕', 0, '2026-06-05 09:49:05'),
(36, 2, 'Ev Bakımı', 'expense', '#65A30D', '🛠️', 0, '2026-06-05 09:49:05'),
(37, 2, 'Diğer Gider', 'expense', '#6B7280', '📦', 0, '2026-06-05 09:49:05'),
(38, 2, 'Maaş', 'income', '#16A34A', '💼', 0, '2026-06-05 09:49:05'),
(39, 2, 'Ek Gelir', 'income', '#059669', '➕', 0, '2026-06-05 09:49:05'),
(40, 2, 'Kira Geliri', 'income', '#0D9488', '🏘️', 0, '2026-06-05 09:49:05'),
(41, 2, 'Faiz & Yatırım', 'income', '#15803D', '📈', 0, '2026-06-05 09:49:05'),
(42, 2, 'Diğer Gelir', 'income', '#22C55E', '💰', 0, '2026-06-05 09:49:05'),
(43, 3, 'Market & Gıda', 'expense', '#E0533D', '🛒', 0, '2026-06-05 10:06:05'),
(44, 3, 'Kira', 'expense', '#B45309', '🏠', 0, '2026-06-05 10:06:05'),
(45, 3, 'Faturalar', 'expense', '#0E7490', '🧾', 0, '2026-06-05 10:06:05'),
(46, 3, 'Elektrik', 'expense', '#CA8A04', '⚡', 0, '2026-06-05 10:06:05'),
(47, 3, 'Su', 'expense', '#0891B2', '💧', 0, '2026-06-05 10:06:05'),
(48, 3, 'Doğalgaz', 'expense', '#EA580C', '🔥', 0, '2026-06-05 10:06:05'),
(49, 3, 'İnternet & Telefon', 'expense', '#7C3AED', '📶', 0, '2026-06-05 10:06:05'),
(50, 3, 'Ulaşım & Yakıt', 'expense', '#1D4ED8', '⛽', 0, '2026-06-05 10:06:05'),
(51, 3, 'Sağlık', 'expense', '#DC2626', '💊', 0, '2026-06-05 10:06:05'),
(52, 3, 'Eğitim', 'expense', '#2563EB', '📚', 0, '2026-06-05 10:06:05'),
(53, 3, 'Giyim', 'expense', '#DB2777', '👕', 0, '2026-06-05 10:06:05'),
(54, 3, 'Eğlence', 'expense', '#9333EA', '🎬', 0, '2026-06-05 10:06:05'),
(55, 3, 'Abonelikler', 'expense', '#0D9488', '🔁', 0, '2026-06-05 10:06:05'),
(56, 3, 'Restoran & Kafe', 'expense', '#D97706', '☕', 0, '2026-06-05 10:06:05'),
(57, 3, 'Ev Bakımı', 'expense', '#65A30D', '🛠️', 0, '2026-06-05 10:06:05'),
(58, 3, 'Diğer Gider', 'expense', '#6B7280', '📦', 0, '2026-06-05 10:06:05'),
(59, 3, 'Maaş', 'income', '#16A34A', '💼', 0, '2026-06-05 10:06:05'),
(60, 3, 'Ek Gelir', 'income', '#059669', '➕', 0, '2026-06-05 10:06:05'),
(61, 3, 'Kira Geliri', 'income', '#0D9488', '🏘️', 0, '2026-06-05 10:06:05'),
(62, 3, 'Faiz & Yatırım', 'income', '#15803D', '📈', 0, '2026-06-05 10:06:05'),
(63, 3, 'Diğer Gelir', 'income', '#22C55E', '💰', 0, '2026-06-05 10:06:05'),
(64, 4, 'Market & Gıda', 'expense', '#E0533D', '🛒', 0, '2026-06-05 15:31:25'),
(66, 4, 'Faturalar', 'expense', '#0E7490', '🧾', 0, '2026-06-05 15:31:25'),
(67, 4, 'Elektrik', 'expense', '#CA8A04', '⚡', 0, '2026-06-05 15:31:25'),
(68, 4, 'Su', 'expense', '#0891B2', '💧', 0, '2026-06-05 15:31:25'),
(69, 4, 'Doğalgaz', 'expense', '#EA580C', '🔥', 0, '2026-06-05 15:31:25'),
(70, 4, 'İnternet & Telefon', 'expense', '#7C3AED', '📶', 0, '2026-06-05 15:31:25'),
(71, 4, 'Ulaşım & Yakıt', 'expense', '#1D4ED8', '⛽', 0, '2026-06-05 15:31:25'),
(72, 4, 'Sağlık', 'expense', '#DC2626', '💊', 0, '2026-06-05 15:31:25'),
(73, 4, 'Eğitim', 'expense', '#2563EB', '📚', 0, '2026-06-05 15:31:25'),
(74, 4, 'Giyim', 'expense', '#DB2777', '👕', 0, '2026-06-05 15:31:25'),
(77, 4, 'Restoran & Kafe', 'expense', '#D97706', '☕', 0, '2026-06-05 15:31:25'),
(78, 4, 'Ev Bakımı', 'expense', '#65A30D', '🛠️', 0, '2026-06-05 15:31:25'),
(79, 4, 'Diğer Gider', 'expense', '#6B7280', '📦', 0, '2026-06-05 15:31:25'),
(80, 4, 'Maaş', 'income', '#16A34A', '💼', 0, '2026-06-05 15:31:25'),
(81, 4, 'Ek Gelir', 'income', '#059669', '➕', 0, '2026-06-05 15:31:25'),
(82, 4, 'Kira Geliri', 'income', '#0D9488', '🏘️', 0, '2026-06-05 15:31:25'),
(83, 4, 'Faiz & Yatırım', 'income', '#15803D', '📈', 0, '2026-06-05 15:31:25'),
(84, 4, 'Diğer Gelir', 'income', '#22C55E', '💰', 0, '2026-06-05 15:31:25'),
(106, 3, 'Sigorta', 'expense', '#c2410c', '🏠', 0, '2026-06-07 13:47:42'),
(107, 4, 'Para transfer', 'expense', '#00ff00', '💰', 0, '2026-06-07 21:20:12'),
(108, 6, 'Market & Gıda', 'expense', '#E0533D', '🛒', 0, '2026-06-07 22:48:33'),
(109, 6, 'Kira', 'expense', '#B45309', '🏠', 0, '2026-06-07 22:48:33'),
(110, 6, 'Faturalar', 'expense', '#0E7490', '🧾', 0, '2026-06-07 22:48:33'),
(111, 6, 'Elektrik', 'expense', '#CA8A04', '⚡', 0, '2026-06-07 22:48:33'),
(112, 6, 'Su', 'expense', '#0891B2', '💧', 0, '2026-06-07 22:48:33'),
(113, 6, 'Doğalgaz', 'expense', '#EA580C', '🔥', 0, '2026-06-07 22:48:33'),
(114, 6, 'İnternet & Telefon', 'expense', '#7C3AED', '📶', 0, '2026-06-07 22:48:33'),
(115, 6, 'Ulaşım & Yakıt', 'expense', '#1D4ED8', '⛽', 0, '2026-06-07 22:48:33'),
(116, 6, 'Sağlık', 'expense', '#DC2626', '💊', 0, '2026-06-07 22:48:33'),
(117, 6, 'Eğitim', 'expense', '#2563EB', '📚', 0, '2026-06-07 22:48:33'),
(118, 6, 'Giyim', 'expense', '#DB2777', '👕', 0, '2026-06-07 22:48:33'),
(119, 6, 'Eğlence', 'expense', '#9333EA', '🎬', 0, '2026-06-07 22:48:33'),
(120, 6, 'Abonelikler', 'expense', '#0D9488', '🔁', 0, '2026-06-07 22:48:33'),
(121, 6, 'Restoran & Kafe', 'expense', '#D97706', '☕', 0, '2026-06-07 22:48:33'),
(122, 6, 'Ev Bakımı', 'expense', '#65A30D', '🛠️', 0, '2026-06-07 22:48:33'),
(123, 6, 'Diğer Gider', 'expense', '#6B7280', '📦', 0, '2026-06-07 22:48:33'),
(124, 6, 'Maaş', 'income', '#16A34A', '💼', 0, '2026-06-07 22:48:33'),
(125, 6, 'Ek Gelir', 'income', '#059669', '➕', 0, '2026-06-07 22:48:33'),
(126, 6, 'Kira Geliri', 'income', '#0D9488', '🏘️', 0, '2026-06-07 22:48:33'),
(127, 6, 'Faiz & Yatırım', 'income', '#15803D', '📈', 0, '2026-06-07 22:48:33'),
(128, 6, 'Diğer Gelir', 'income', '#22C55E', '💰', 0, '2026-06-07 22:48:33'),
(129, 7, 'Market & Gıda', 'expense', '#E0533D', '🛒', 0, '2026-06-23 08:56:27'),
(130, 7, 'Kira', 'expense', '#B45309', '🏠', 0, '2026-06-23 08:56:27'),
(131, 7, 'Faturalar', 'expense', '#0E7490', '🧾', 0, '2026-06-23 08:56:27'),
(132, 7, 'Elektrik', 'expense', '#CA8A04', '⚡', 0, '2026-06-23 08:56:27'),
(133, 7, 'Su', 'expense', '#0891B2', '💧', 0, '2026-06-23 08:56:27'),
(134, 7, 'Doğalgaz', 'expense', '#EA580C', '🔥', 0, '2026-06-23 08:56:27'),
(135, 7, 'İnternet & Telefon', 'expense', '#7C3AED', '📶', 0, '2026-06-23 08:56:27'),
(136, 7, 'Ulaşım & Yakıt', 'expense', '#1D4ED8', '⛽', 0, '2026-06-23 08:56:27'),
(137, 7, 'Sağlık', 'expense', '#DC2626', '💊', 0, '2026-06-23 08:56:27'),
(138, 7, 'Eğitim', 'expense', '#2563EB', '📚', 0, '2026-06-23 08:56:27'),
(139, 7, 'Giyim', 'expense', '#DB2777', '👕', 0, '2026-06-23 08:56:27'),
(140, 7, 'Eğlence', 'expense', '#9333EA', '🎬', 0, '2026-06-23 08:56:27'),
(141, 7, 'Abonelikler', 'expense', '#0D9488', '🔁', 0, '2026-06-23 08:56:27'),
(142, 7, 'Restoran & Kafe', 'expense', '#D97706', '☕', 0, '2026-06-23 08:56:27'),
(143, 7, 'Ev Bakımı', 'expense', '#65A30D', '🛠️', 0, '2026-06-23 08:56:27'),
(144, 7, 'Diğer Gider', 'expense', '#6B7280', '📦', 0, '2026-06-23 08:56:27'),
(145, 7, 'Maaş', 'income', '#16A34A', '💼', 0, '2026-06-23 08:56:27'),
(146, 7, 'Ek Gelir', 'income', '#059669', '➕', 0, '2026-06-23 08:56:27'),
(147, 7, 'Kira Geliri', 'income', '#0D9488', '🏘️', 0, '2026-06-23 08:56:27'),
(148, 7, 'Faiz & Yatırım', 'income', '#15803D', '📈', 0, '2026-06-23 08:56:27'),
(149, 7, 'Diğer Gelir', 'income', '#22C55E', '💰', 0, '2026-06-23 08:56:27');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `kind` enum('chat','price') NOT NULL DEFAULT 'chat',
  `message` text DEFAULT NULL,
  `product` varchar(160) DEFAULT NULL,
  `price` decimal(14,2) DEFAULT NULL,
  `store` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `kind`, `message`, `product`, `price`, `store`, `created_at`) VALUES
(1, 1, 'chat', 'Selamlar', NULL, NULL, NULL, '2026-06-05 23:58:00'),
(2, 2, 'price', '', 'Deneme Ürün', 999.00, 'A101', '2026-06-06 00:01:20'),
(3, 2, 'chat', 'İndirimmmm varrrr', NULL, NULL, NULL, '2026-06-06 00:01:34'),
(4, 1, 'chat', 'Denemeee', NULL, NULL, NULL, '2026-06-06 00:39:48'),
(5, 2, 'chat', 'deneme', NULL, NULL, NULL, '2026-06-06 00:40:07'),
(6, 1, 'chat', 'Merttttttt', NULL, NULL, NULL, '2026-06-06 00:56:42'),
(7, 1, 'price', 'Sıfır km', 'F - 35', 12000000.00, 'ABD HAVA ÜSSÜ', '2026-06-06 00:58:00'),
(8, 3, 'chat', 'Sea', NULL, NULL, NULL, '2026-06-07 13:41:42'),
(9, 3, 'price', 'Opsiyonel', 'Satılık Koca', 1.00, 'Elden', '2026-06-07 13:59:13'),
(10, 4, 'chat', 'Sa', NULL, NULL, NULL, '2026-06-07 20:37:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `direct_messages`
--

CREATE TABLE `direct_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `from_user` int(10) UNSIGNED NOT NULL,
  `to_user` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `exchange_rates`
--

CREATE TABLE `exchange_rates` (
  `code` varchar(16) NOT NULL,
  `rate_try` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `label` varchar(60) DEFAULT NULL,
  `source` varchar(40) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `exchange_rates`
--

INSERT INTO `exchange_rates` (`code`, `rate_try`, `label`, `source`, `updated_at`) VALUES
('CEYREK', 10988.942055, 'Çeyrek Altın', 'auto', '2026-06-14 21:56:43'),
('EUR', 53.530697, 'Euro', 'auto', '2026-06-14 21:56:43'),
('GBP', 62.027225, 'İngiliz Sterlini', 'auto', '2026-06-14 21:56:43'),
('TAM', 43955.768218, 'Tam Altın', 'auto', '2026-06-14 21:56:43'),
('USD', 46.278947, 'Amerikan Doları', 'auto', '2026-06-14 21:56:43'),
('XAG_GRAM', 101.410981, 'Gram Gümüş', 'auto', '2026-06-14 21:56:43'),
('XAU_GRAM', 6279.395460, 'Gram Altın', 'auto', '2026-06-14 21:56:43'),
('YARIM', 21977.884109, 'Yarım Altın', 'auto', '2026-06-14 21:56:43');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `goal_contributions`
--

CREATE TABLE `goal_contributions` (
  `id` int(10) UNSIGNED NOT NULL,
  `goal_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `note` varchar(200) DEFAULT NULL,
  `contributed_on` date NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `goal_contributions`
--

INSERT INTO `goal_contributions` (`id`, `goal_id`, `amount`, `note`, `contributed_on`, `user_id`, `created_at`) VALUES
(1, 1, 1500000.00, 'Birikim', '2026-06-07', 3, '2026-06-07 13:44:35');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `households`
--

CREATE TABLE `households` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `currency` varchar(4) NOT NULL DEFAULT 'TRY',
  `join_code` varchar(12) NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `households`
--

INSERT INTO `households` (`id`, `name`, `currency`, `join_code`, `created_by`, `created_at`) VALUES
(2, 'Evim', 'TRY', 'UMU3NK37', 2, '2026-06-05 09:49:05'),
(3, 'Evim', 'TRY', '946XQRR6', 3, '2026-06-05 10:06:05'),
(4, 'Evim', 'TRY', 'QEZ6UJGM', 4, '2026-06-05 15:31:25'),
(6, 'Evim', 'TRY', 'C4S44FP4', 5, '2026-06-07 22:48:33'),
(7, 'Home Sweet Home', 'TRY', 'berkan', 1, '2026-06-23 08:56:27');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `household_members`
--

CREATE TABLE `household_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` enum('owner','member') NOT NULL DEFAULT 'member',
  `joined_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `household_members`
--

INSERT INTO `household_members` (`id`, `household_id`, `user_id`, `role`, `joined_at`) VALUES
(2, 2, 2, 'owner', '2026-06-05 09:49:05'),
(4, 3, 3, 'owner', '2026-06-05 10:06:05'),
(6, 4, 4, 'owner', '2026-06-05 15:31:25'),
(10, 6, 5, 'owner', '2026-06-07 22:48:33'),
(11, 6, 4, 'member', '2026-06-07 22:52:14'),
(12, 7, 1, 'owner', '2026-06-23 08:56:27'),
(13, 7, 3, 'member', '2026-06-23 09:04:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `import_batches`
--

CREATE TABLE `import_batches` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `bank_name` varchar(120) DEFAULT NULL,
  `row_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `imported_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `import_batches`
--

INSERT INTO `import_batches` (`id`, `household_id`, `user_id`, `filename`, `bank_name`, `row_count`, `imported_count`, `created_at`) VALUES
(1, 3, 3, 'Yapıştırılan metin', 'Garanti', 44, 44, '2026-06-07 13:40:43'),
(3, 4, 4, 'Yapıştırılan metin', 'Garanti', 42, 42, '2026-06-07 20:46:30'),
(4, 4, 4, 'Yapıştırılan metin', 'Garanti', 42, 42, '2026-06-10 13:24:35');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `import_rules`
--

CREATE TABLE `import_rules` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `match_text` varchar(120) NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `import_rules`
--

INSERT INTO `import_rules` (`id`, `household_id`, `match_text`, `category_id`, `created_at`) VALUES
(70, 2, 'MIGROS', 22, '2026-06-05 09:49:05'),
(71, 2, 'CARREFOUR', 22, '2026-06-05 09:49:05'),
(72, 2, 'A101', 22, '2026-06-05 09:49:05'),
(73, 2, 'BIM', 22, '2026-06-05 09:49:05'),
(74, 2, 'SOK MARKET', 22, '2026-06-05 09:49:05'),
(75, 2, 'MACROCENTER', 22, '2026-06-05 09:49:05'),
(76, 2, 'GETIR', 22, '2026-06-05 09:49:05'),
(77, 2, 'MIGROS SANAL', 22, '2026-06-05 09:49:05'),
(78, 2, 'SHELL', 29, '2026-06-05 09:49:05'),
(79, 2, 'OPET', 29, '2026-06-05 09:49:05'),
(80, 2, 'BP ', 29, '2026-06-05 09:49:05'),
(81, 2, 'PETROL OFISI', 29, '2026-06-05 09:49:05'),
(82, 2, 'TOTAL', 29, '2026-06-05 09:49:05'),
(83, 2, 'BENZIN', 29, '2026-06-05 09:49:05'),
(84, 2, 'IBB', 29, '2026-06-05 09:49:05'),
(85, 2, 'ISTANBULKART', 29, '2026-06-05 09:49:05'),
(86, 2, 'UBER', 29, '2026-06-05 09:49:05'),
(87, 2, 'BITAKSI', 29, '2026-06-05 09:49:05'),
(88, 2, 'OTOYOL', 29, '2026-06-05 09:49:05'),
(89, 2, 'HGS', 29, '2026-06-05 09:49:05'),
(90, 2, 'NETFLIX', 34, '2026-06-05 09:49:05'),
(91, 2, 'SPOTIFY', 34, '2026-06-05 09:49:05'),
(92, 2, 'YOUTUBE', 34, '2026-06-05 09:49:05'),
(93, 2, 'DISNEY', 34, '2026-06-05 09:49:05'),
(94, 2, 'APPLE.COM', 34, '2026-06-05 09:49:05'),
(95, 2, 'GOOGLE', 34, '2026-06-05 09:49:05'),
(96, 2, 'AMAZON PRIME', 34, '2026-06-05 09:49:05'),
(97, 2, 'EXXEN', 34, '2026-06-05 09:49:05'),
(98, 2, 'BLUTV', 34, '2026-06-05 09:49:05'),
(99, 2, 'TURKCELL', 28, '2026-06-05 09:49:05'),
(100, 2, 'VODAFONE', 28, '2026-06-05 09:49:05'),
(101, 2, 'TURK TELEKOM', 28, '2026-06-05 09:49:05'),
(102, 2, 'TTNET', 28, '2026-06-05 09:49:05'),
(103, 2, 'SUPERONLINE', 28, '2026-06-05 09:49:05'),
(104, 2, 'MILLENICOM', 28, '2026-06-05 09:49:05'),
(105, 2, 'ELEKTRIK', 25, '2026-06-05 09:49:05'),
(106, 2, 'CK ENERJI', 25, '2026-06-05 09:49:05'),
(107, 2, 'BEDAS', 25, '2026-06-05 09:49:05'),
(108, 2, 'AYEDAS', 25, '2026-06-05 09:49:05'),
(109, 2, 'ENERJISA', 25, '2026-06-05 09:49:05'),
(110, 2, 'ISKI', 26, '2026-06-05 09:49:05'),
(111, 2, 'ASKI', 26, '2026-06-05 09:49:05'),
(112, 2, 'SU FATURA', 26, '2026-06-05 09:49:05'),
(113, 2, 'IGDAS', 27, '2026-06-05 09:49:05'),
(114, 2, 'DOGALGAZ', 27, '2026-06-05 09:49:05'),
(115, 2, 'BASKENTGAZ', 27, '2026-06-05 09:49:05'),
(116, 2, 'ECZANE', 30, '2026-06-05 09:49:05'),
(117, 2, 'HASTANE', 30, '2026-06-05 09:49:05'),
(118, 2, 'MEDICAL', 30, '2026-06-05 09:49:05'),
(119, 2, 'DENT', 30, '2026-06-05 09:49:05'),
(120, 2, 'STARBUCKS', 35, '2026-06-05 09:49:05'),
(121, 2, 'KAHVE', 35, '2026-06-05 09:49:05'),
(122, 2, 'MCDONALD', 35, '2026-06-05 09:49:05'),
(123, 2, 'BURGER', 35, '2026-06-05 09:49:05'),
(124, 2, 'DOMINO', 35, '2026-06-05 09:49:05'),
(125, 2, 'YEMEKSEPETI', 35, '2026-06-05 09:49:05'),
(126, 2, 'TRENDYOL', 32, '2026-06-05 09:49:05'),
(127, 2, 'LC WAIKIKI', 32, '2026-06-05 09:49:05'),
(128, 2, 'DEFACTO', 32, '2026-06-05 09:49:05'),
(129, 2, 'ZARA', 32, '2026-06-05 09:49:05'),
(130, 2, 'H&M', 32, '2026-06-05 09:49:05'),
(131, 2, 'MAVI', 32, '2026-06-05 09:49:05'),
(132, 2, 'MAAS', 38, '2026-06-05 09:49:05'),
(133, 2, 'ODEME-MAAS', 38, '2026-06-05 09:49:05'),
(134, 2, 'UCRET', 38, '2026-06-05 09:49:05'),
(135, 2, 'FAIZ', 41, '2026-06-05 09:49:06'),
(136, 2, 'TEMETTU', 41, '2026-06-05 09:49:06'),
(137, 2, 'VADELI', 41, '2026-06-05 09:49:06'),
(138, 3, 'MIGROS', 43, '2026-06-05 10:06:05'),
(139, 3, 'CARREFOUR', 43, '2026-06-05 10:06:05'),
(140, 3, 'A101', 43, '2026-06-05 10:06:05'),
(141, 3, 'BIM', 43, '2026-06-05 10:06:05'),
(142, 3, 'SOK MARKET', 43, '2026-06-05 10:06:05'),
(143, 3, 'MACROCENTER', 43, '2026-06-05 10:06:05'),
(144, 3, 'GETIR', 43, '2026-06-05 10:06:05'),
(145, 3, 'MIGROS SANAL', 43, '2026-06-05 10:06:05'),
(146, 3, 'SHELL', 50, '2026-06-05 10:06:05'),
(147, 3, 'OPET', 50, '2026-06-05 10:06:05'),
(148, 3, 'BP ', 50, '2026-06-05 10:06:05'),
(149, 3, 'PETROL OFISI', 50, '2026-06-05 10:06:05'),
(150, 3, 'TOTAL', 50, '2026-06-05 10:06:05'),
(151, 3, 'BENZIN', 50, '2026-06-05 10:06:05'),
(152, 3, 'IBB', 50, '2026-06-05 10:06:05'),
(153, 3, 'ISTANBULKART', 50, '2026-06-05 10:06:05'),
(154, 3, 'UBER', 50, '2026-06-05 10:06:05'),
(155, 3, 'BITAKSI', 50, '2026-06-05 10:06:05'),
(156, 3, 'OTOYOL', 50, '2026-06-05 10:06:05'),
(157, 3, 'HGS', 50, '2026-06-05 10:06:05'),
(158, 3, 'NETFLIX', 55, '2026-06-05 10:06:05'),
(159, 3, 'SPOTIFY', 55, '2026-06-05 10:06:05'),
(160, 3, 'YOUTUBE', 55, '2026-06-05 10:06:05'),
(161, 3, 'DISNEY', 55, '2026-06-05 10:06:05'),
(162, 3, 'APPLE.COM', 55, '2026-06-05 10:06:05'),
(163, 3, 'GOOGLE', 55, '2026-06-05 10:06:05'),
(164, 3, 'AMAZON PRIME', 55, '2026-06-05 10:06:05'),
(165, 3, 'EXXEN', 55, '2026-06-05 10:06:05'),
(166, 3, 'BLUTV', 55, '2026-06-05 10:06:05'),
(167, 3, 'TURKCELL', 49, '2026-06-05 10:06:05'),
(168, 3, 'VODAFONE', 49, '2026-06-05 10:06:05'),
(169, 3, 'TURK TELEKOM', 49, '2026-06-05 10:06:05'),
(170, 3, 'TTNET', 49, '2026-06-05 10:06:05'),
(171, 3, 'SUPERONLINE', 49, '2026-06-05 10:06:05'),
(172, 3, 'MILLENICOM', 49, '2026-06-05 10:06:05'),
(173, 3, 'ELEKTRIK', 46, '2026-06-05 10:06:05'),
(174, 3, 'CK ENERJI', 46, '2026-06-05 10:06:05'),
(175, 3, 'BEDAS', 46, '2026-06-05 10:06:05'),
(176, 3, 'AYEDAS', 46, '2026-06-05 10:06:05'),
(177, 3, 'ENERJISA', 46, '2026-06-05 10:06:05'),
(178, 3, 'ISKI', 47, '2026-06-05 10:06:05'),
(179, 3, 'ASKI', 47, '2026-06-05 10:06:05'),
(180, 3, 'SU FATURA', 47, '2026-06-05 10:06:05'),
(181, 3, 'IGDAS', 48, '2026-06-05 10:06:05'),
(182, 3, 'DOGALGAZ', 48, '2026-06-05 10:06:05'),
(183, 3, 'BASKENTGAZ', 48, '2026-06-05 10:06:05'),
(184, 3, 'ECZANE', 51, '2026-06-05 10:06:05'),
(185, 3, 'HASTANE', 51, '2026-06-05 10:06:05'),
(186, 3, 'MEDICAL', 51, '2026-06-05 10:06:05'),
(187, 3, 'DENT', 51, '2026-06-05 10:06:05'),
(188, 3, 'STARBUCKS', 56, '2026-06-05 10:06:05'),
(189, 3, 'KAHVE', 56, '2026-06-05 10:06:05'),
(190, 3, 'MCDONALD', 56, '2026-06-05 10:06:05'),
(191, 3, 'BURGER', 56, '2026-06-05 10:06:05'),
(192, 3, 'DOMINO', 56, '2026-06-05 10:06:05'),
(193, 3, 'YEMEKSEPETI', 56, '2026-06-05 10:06:05'),
(194, 3, 'TRENDYOL', 53, '2026-06-05 10:06:05'),
(195, 3, 'LC WAIKIKI', 53, '2026-06-05 10:06:05'),
(196, 3, 'DEFACTO', 53, '2026-06-05 10:06:05'),
(197, 3, 'ZARA', 53, '2026-06-05 10:06:05'),
(198, 3, 'H&M', 53, '2026-06-05 10:06:05'),
(199, 3, 'MAVI', 53, '2026-06-05 10:06:05'),
(200, 3, 'MAAS', 59, '2026-06-05 10:06:05'),
(201, 3, 'ODEME-MAAS', 59, '2026-06-05 10:06:05'),
(202, 3, 'UCRET', 59, '2026-06-05 10:06:05'),
(203, 3, 'FAIZ', 62, '2026-06-05 10:06:05'),
(204, 3, 'TEMETTU', 62, '2026-06-05 10:06:05'),
(205, 3, 'VADELI', 62, '2026-06-05 10:06:05'),
(206, 4, 'MIGROS', 64, '2026-06-05 15:31:25'),
(207, 4, 'CARREFOUR', 64, '2026-06-05 15:31:25'),
(208, 4, 'A101', 64, '2026-06-05 15:31:25'),
(209, 4, 'BIM', 64, '2026-06-05 15:31:25'),
(210, 4, 'SOK MARKET', 64, '2026-06-05 15:31:25'),
(211, 4, 'MACROCENTER', 64, '2026-06-05 15:31:25'),
(212, 4, 'GETIR', 64, '2026-06-05 15:31:25'),
(213, 4, 'MIGROS SANAL', 64, '2026-06-05 15:31:25'),
(214, 4, 'SHELL', 71, '2026-06-05 15:31:25'),
(215, 4, 'OPET', 71, '2026-06-05 15:31:25'),
(216, 4, 'BP ', 71, '2026-06-05 15:31:25'),
(217, 4, 'PETROL OFISI', 71, '2026-06-05 15:31:25'),
(218, 4, 'TOTAL', 71, '2026-06-05 15:31:25'),
(219, 4, 'BENZIN', 71, '2026-06-05 15:31:25'),
(220, 4, 'IBB', 71, '2026-06-05 15:31:25'),
(221, 4, 'ISTANBULKART', 71, '2026-06-05 15:31:25'),
(222, 4, 'UBER', 71, '2026-06-05 15:31:25'),
(223, 4, 'BITAKSI', 71, '2026-06-05 15:31:25'),
(224, 4, 'OTOYOL', 71, '2026-06-05 15:31:25'),
(225, 4, 'HGS', 71, '2026-06-05 15:31:25'),
(235, 4, 'TURKCELL', 70, '2026-06-05 15:31:25'),
(236, 4, 'VODAFONE', 70, '2026-06-05 15:31:25'),
(237, 4, 'TURK TELEKOM', 70, '2026-06-05 15:31:25'),
(238, 4, 'TTNET', 70, '2026-06-05 15:31:25'),
(239, 4, 'SUPERONLINE', 70, '2026-06-05 15:31:25'),
(240, 4, 'MILLENICOM', 70, '2026-06-05 15:31:25'),
(241, 4, 'ELEKTRIK', 67, '2026-06-05 15:31:25'),
(242, 4, 'CK ENERJI', 67, '2026-06-05 15:31:25'),
(243, 4, 'BEDAS', 67, '2026-06-05 15:31:25'),
(244, 4, 'AYEDAS', 67, '2026-06-05 15:31:25'),
(245, 4, 'ENERJISA', 67, '2026-06-05 15:31:25'),
(246, 4, 'ISKI', 68, '2026-06-05 15:31:25'),
(247, 4, 'ASKI', 68, '2026-06-05 15:31:25'),
(248, 4, 'SU FATURA', 68, '2026-06-05 15:31:25'),
(249, 4, 'IGDAS', 69, '2026-06-05 15:31:25'),
(250, 4, 'DOGALGAZ', 69, '2026-06-05 15:31:25'),
(251, 4, 'BASKENTGAZ', 69, '2026-06-05 15:31:25'),
(252, 4, 'ECZANE', 72, '2026-06-05 15:31:25'),
(253, 4, 'HASTANE', 72, '2026-06-05 15:31:25'),
(254, 4, 'MEDICAL', 72, '2026-06-05 15:31:25'),
(255, 4, 'DENT', 72, '2026-06-05 15:31:25'),
(256, 4, 'STARBUCKS', 77, '2026-06-05 15:31:25'),
(257, 4, 'KAHVE', 77, '2026-06-05 15:31:25'),
(258, 4, 'MCDONALD', 77, '2026-06-05 15:31:25'),
(259, 4, 'BURGER', 77, '2026-06-05 15:31:25'),
(260, 4, 'DOMINO', 77, '2026-06-05 15:31:25'),
(261, 4, 'YEMEKSEPETI', 77, '2026-06-05 15:31:25'),
(262, 4, 'TRENDYOL', 74, '2026-06-05 15:31:25'),
(263, 4, 'LC WAIKIKI', 74, '2026-06-05 15:31:25'),
(264, 4, 'DEFACTO', 74, '2026-06-05 15:31:25'),
(265, 4, 'ZARA', 74, '2026-06-05 15:31:25'),
(266, 4, 'H&M', 74, '2026-06-05 15:31:25'),
(267, 4, 'MAVI', 74, '2026-06-05 15:31:25'),
(268, 4, 'MAAS', 80, '2026-06-05 15:31:25'),
(269, 4, 'ODEME-MAAS', 80, '2026-06-05 15:31:25'),
(270, 4, 'UCRET', 80, '2026-06-05 15:31:25'),
(271, 4, 'FAIZ', 83, '2026-06-05 15:31:25'),
(272, 4, 'TEMETTU', 83, '2026-06-05 15:31:25'),
(273, 4, 'VADELI', 83, '2026-06-05 15:31:25'),
(342, 3, 'MIGROS-AIRPORT', 43, '2026-06-07 13:40:43'),
(343, 3, 'TRENDYOL.COM', 53, '2026-06-07 13:40:43'),
(344, 3, 'AYLIN', 51, '2026-06-07 13:40:43'),
(345, 3, 'APPLE.COM/BILL', 55, '2026-06-07 13:40:43'),
(354, 4, '9930', 64, '2026-06-07 20:46:30'),
(355, 4, 'ISIKLAR', 71, '2026-06-07 20:46:30'),
(356, 4, 'TRENDYOL.COM', 74, '2026-06-07 20:46:30'),
(357, 4, 'MOKA', 77, '2026-06-07 20:46:30'),
(358, 4, '9942', 64, '2026-06-07 20:46:30'),
(359, 4, 'PAYCELL/TURKCELL', 70, '2026-06-07 20:46:30'),
(360, 4, 'AVRUPA', 71, '2026-06-07 20:46:30'),
(361, 4, 'NAKIT', 80, '2026-06-07 20:46:30'),
(362, 4, 'PARA TRANSFER', 79, '2026-06-07 21:19:16'),
(363, 4, 'PARA TRANSFER', 107, '2026-06-07 21:20:45'),
(364, 6, 'MIGROS', 108, '2026-06-07 22:48:33'),
(365, 6, 'CARREFOUR', 108, '2026-06-07 22:48:33'),
(366, 6, 'A101', 108, '2026-06-07 22:48:33'),
(367, 6, 'BIM', 108, '2026-06-07 22:48:33'),
(368, 6, 'SOK MARKET', 108, '2026-06-07 22:48:33'),
(369, 6, 'MACROCENTER', 108, '2026-06-07 22:48:33'),
(370, 6, 'GETIR', 108, '2026-06-07 22:48:33'),
(371, 6, 'MIGROS SANAL', 108, '2026-06-07 22:48:33'),
(372, 6, 'SHELL', 115, '2026-06-07 22:48:33'),
(373, 6, 'OPET', 115, '2026-06-07 22:48:33'),
(374, 6, 'BP ', 115, '2026-06-07 22:48:33'),
(375, 6, 'PETROL OFISI', 115, '2026-06-07 22:48:33'),
(376, 6, 'TOTAL', 115, '2026-06-07 22:48:33'),
(377, 6, 'BENZIN', 115, '2026-06-07 22:48:33'),
(378, 6, 'IBB', 115, '2026-06-07 22:48:33'),
(379, 6, 'ISTANBULKART', 115, '2026-06-07 22:48:33'),
(380, 6, 'UBER', 115, '2026-06-07 22:48:33'),
(381, 6, 'BITAKSI', 115, '2026-06-07 22:48:33'),
(382, 6, 'OTOYOL', 115, '2026-06-07 22:48:33'),
(383, 6, 'HGS', 115, '2026-06-07 22:48:33'),
(384, 6, 'NETFLIX', 120, '2026-06-07 22:48:33'),
(385, 6, 'SPOTIFY', 120, '2026-06-07 22:48:33'),
(386, 6, 'YOUTUBE', 120, '2026-06-07 22:48:33'),
(387, 6, 'DISNEY', 120, '2026-06-07 22:48:33'),
(388, 6, 'APPLE.COM', 120, '2026-06-07 22:48:33'),
(389, 6, 'GOOGLE', 120, '2026-06-07 22:48:33'),
(390, 6, 'AMAZON PRIME', 120, '2026-06-07 22:48:33'),
(391, 6, 'EXXEN', 120, '2026-06-07 22:48:33'),
(392, 6, 'BLUTV', 120, '2026-06-07 22:48:33'),
(393, 6, 'TURKCELL', 114, '2026-06-07 22:48:33'),
(394, 6, 'VODAFONE', 114, '2026-06-07 22:48:33'),
(395, 6, 'TURK TELEKOM', 114, '2026-06-07 22:48:33'),
(396, 6, 'TTNET', 114, '2026-06-07 22:48:33'),
(397, 6, 'SUPERONLINE', 114, '2026-06-07 22:48:33'),
(398, 6, 'MILLENICOM', 114, '2026-06-07 22:48:33'),
(399, 6, 'ELEKTRIK', 111, '2026-06-07 22:48:33'),
(400, 6, 'CK ENERJI', 111, '2026-06-07 22:48:33'),
(401, 6, 'BEDAS', 111, '2026-06-07 22:48:33'),
(402, 6, 'AYEDAS', 111, '2026-06-07 22:48:33'),
(403, 6, 'ENERJISA', 111, '2026-06-07 22:48:33'),
(404, 6, 'ISKI', 112, '2026-06-07 22:48:33'),
(405, 6, 'ASKI', 112, '2026-06-07 22:48:33'),
(406, 6, 'SU FATURA', 112, '2026-06-07 22:48:33'),
(407, 6, 'IGDAS', 113, '2026-06-07 22:48:33'),
(408, 6, 'DOGALGAZ', 113, '2026-06-07 22:48:33'),
(409, 6, 'BASKENTGAZ', 113, '2026-06-07 22:48:33'),
(410, 6, 'ECZANE', 116, '2026-06-07 22:48:33'),
(411, 6, 'HASTANE', 116, '2026-06-07 22:48:33'),
(412, 6, 'MEDICAL', 116, '2026-06-07 22:48:33'),
(413, 6, 'DENT', 116, '2026-06-07 22:48:33'),
(414, 6, 'STARBUCKS', 121, '2026-06-07 22:48:33'),
(415, 6, 'KAHVE', 121, '2026-06-07 22:48:33'),
(416, 6, 'MCDONALD', 121, '2026-06-07 22:48:33'),
(417, 6, 'BURGER', 121, '2026-06-07 22:48:33'),
(418, 6, 'DOMINO', 121, '2026-06-07 22:48:33'),
(419, 6, 'YEMEKSEPETI', 121, '2026-06-07 22:48:33'),
(420, 6, 'TRENDYOL', 118, '2026-06-07 22:48:33'),
(421, 6, 'LC WAIKIKI', 118, '2026-06-07 22:48:33'),
(422, 6, 'DEFACTO', 118, '2026-06-07 22:48:33'),
(423, 6, 'ZARA', 118, '2026-06-07 22:48:33'),
(424, 6, 'H&M', 118, '2026-06-07 22:48:33'),
(425, 6, 'MAVI', 118, '2026-06-07 22:48:33'),
(426, 6, 'MAAS', 124, '2026-06-07 22:48:33'),
(427, 6, 'ODEME-MAAS', 124, '2026-06-07 22:48:33'),
(428, 6, 'UCRET', 124, '2026-06-07 22:48:33'),
(429, 6, 'FAIZ', 127, '2026-06-07 22:48:33'),
(430, 6, 'TEMETTU', 127, '2026-06-07 22:48:33'),
(431, 6, 'VADELI', 127, '2026-06-07 22:48:33'),
(432, 4, 'INTERNET', 80, '2026-06-10 13:24:35'),
(433, 7, 'MIGROS', 129, '2026-06-23 08:56:27'),
(434, 7, 'CARREFOUR', 129, '2026-06-23 08:56:27'),
(435, 7, 'A101', 129, '2026-06-23 08:56:27'),
(436, 7, 'BIM', 129, '2026-06-23 08:56:27'),
(437, 7, 'SOK MARKET', 129, '2026-06-23 08:56:27'),
(438, 7, 'MACROCENTER', 129, '2026-06-23 08:56:27'),
(439, 7, 'GETIR', 129, '2026-06-23 08:56:27'),
(440, 7, 'MIGROS SANAL', 129, '2026-06-23 08:56:27'),
(441, 7, 'SHELL', 136, '2026-06-23 08:56:27'),
(442, 7, 'OPET', 136, '2026-06-23 08:56:27'),
(443, 7, 'BP ', 136, '2026-06-23 08:56:27'),
(444, 7, 'PETROL OFISI', 136, '2026-06-23 08:56:27'),
(445, 7, 'TOTAL', 136, '2026-06-23 08:56:27'),
(446, 7, 'BENZIN', 136, '2026-06-23 08:56:27'),
(447, 7, 'IBB', 136, '2026-06-23 08:56:27'),
(448, 7, 'ISTANBULKART', 136, '2026-06-23 08:56:27'),
(449, 7, 'UBER', 136, '2026-06-23 08:56:27'),
(450, 7, 'BITAKSI', 136, '2026-06-23 08:56:27'),
(451, 7, 'OTOYOL', 136, '2026-06-23 08:56:27'),
(452, 7, 'HGS', 136, '2026-06-23 08:56:27'),
(453, 7, 'NETFLIX', 141, '2026-06-23 08:56:27'),
(454, 7, 'SPOTIFY', 141, '2026-06-23 08:56:27'),
(455, 7, 'YOUTUBE', 141, '2026-06-23 08:56:27'),
(456, 7, 'DISNEY', 141, '2026-06-23 08:56:27'),
(457, 7, 'APPLE.COM', 141, '2026-06-23 08:56:27'),
(458, 7, 'GOOGLE', 141, '2026-06-23 08:56:27'),
(459, 7, 'AMAZON PRIME', 141, '2026-06-23 08:56:27'),
(460, 7, 'EXXEN', 141, '2026-06-23 08:56:27'),
(461, 7, 'BLUTV', 141, '2026-06-23 08:56:27'),
(462, 7, 'TURKCELL', 135, '2026-06-23 08:56:27'),
(463, 7, 'VODAFONE', 135, '2026-06-23 08:56:27'),
(464, 7, 'TURK TELEKOM', 135, '2026-06-23 08:56:27'),
(465, 7, 'TTNET', 135, '2026-06-23 08:56:27'),
(466, 7, 'SUPERONLINE', 135, '2026-06-23 08:56:27'),
(467, 7, 'MILLENICOM', 135, '2026-06-23 08:56:27'),
(468, 7, 'ELEKTRIK', 132, '2026-06-23 08:56:27'),
(469, 7, 'CK ENERJI', 132, '2026-06-23 08:56:27'),
(470, 7, 'BEDAS', 132, '2026-06-23 08:56:27'),
(471, 7, 'AYEDAS', 132, '2026-06-23 08:56:27'),
(472, 7, 'ENERJISA', 132, '2026-06-23 08:56:27'),
(473, 7, 'ISKI', 133, '2026-06-23 08:56:27'),
(474, 7, 'ASKI', 133, '2026-06-23 08:56:27'),
(475, 7, 'SU FATURA', 133, '2026-06-23 08:56:27'),
(476, 7, 'IGDAS', 134, '2026-06-23 08:56:27'),
(477, 7, 'DOGALGAZ', 134, '2026-06-23 08:56:27'),
(478, 7, 'BASKENTGAZ', 134, '2026-06-23 08:56:27'),
(479, 7, 'ECZANE', 137, '2026-06-23 08:56:27'),
(480, 7, 'HASTANE', 137, '2026-06-23 08:56:27'),
(481, 7, 'MEDICAL', 137, '2026-06-23 08:56:27'),
(482, 7, 'DENT', 137, '2026-06-23 08:56:27'),
(483, 7, 'STARBUCKS', 142, '2026-06-23 08:56:27'),
(484, 7, 'KAHVE', 142, '2026-06-23 08:56:27'),
(485, 7, 'MCDONALD', 142, '2026-06-23 08:56:27'),
(486, 7, 'BURGER', 142, '2026-06-23 08:56:27'),
(487, 7, 'DOMINO', 142, '2026-06-23 08:56:27'),
(488, 7, 'YEMEKSEPETI', 142, '2026-06-23 08:56:27'),
(489, 7, 'TRENDYOL', 139, '2026-06-23 08:56:27'),
(490, 7, 'LC WAIKIKI', 139, '2026-06-23 08:56:27'),
(491, 7, 'DEFACTO', 139, '2026-06-23 08:56:27'),
(492, 7, 'ZARA', 139, '2026-06-23 08:56:27'),
(493, 7, 'H&M', 139, '2026-06-23 08:56:27'),
(494, 7, 'MAVI', 139, '2026-06-23 08:56:27'),
(495, 7, 'MAAS', 145, '2026-06-23 08:56:27'),
(496, 7, 'ODEME-MAAS', 145, '2026-06-23 08:56:27'),
(497, 7, 'UCRET', 145, '2026-06-23 08:56:27'),
(498, 7, 'FAIZ', 148, '2026-06-23 08:56:27'),
(499, 7, 'TEMETTU', 148, '2026-06-23 08:56:27'),
(500, 7, 'VADELI', 148, '2026-06-23 08:56:27');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `invitations`
--

CREATE TABLE `invitations` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `token` varchar(64) NOT NULL,
  `invited_by` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','accepted','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `invitations`
--

INSERT INTO `invitations` (`id`, `household_id`, `email`, `token`, `invited_by`, `status`, `created_at`) VALUES
(3, 4, 'nur.kaderg28@gmail.com', '67b72065e31da5a6dd60d4d65c91a02f', 4, 'pending', '2026-06-07 20:58:00'),
(4, 7, 'mervesencer12@hotmail.com', 'f133fb2d346bdf8476b7df815f098cfb', 1, 'accepted', '2026-06-23 08:59:13');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(9, 1, 'f5fdf708c391ae353b379cd774c017d1c8c320ff89088c5da279c3011c932053', '2026-06-06 01:37:40', 0, '2026-06-06 00:37:40'),
(11, 4, '8752e92dce26d5c100237839315f7a9359dd394b74ec3de7978359415457b965', '2026-06-06 02:00:24', 0, '2026-06-06 01:00:24');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `savings_goals`
--

CREATE TABLE `savings_goals` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `target_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `target_date` date DEFAULT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#14452F',
  `icon` varchar(8) NOT NULL DEFAULT '?',
  `status` enum('active','reached','archived') NOT NULL DEFAULT 'active',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `savings_goals`
--

INSERT INTO `savings_goals` (`id`, `household_id`, `name`, `target_amount`, `target_date`, `color`, `icon`, `status`, `created_by`, `created_at`) VALUES
(1, 3, 'EV', 5000000.00, '2026-12-31', '#55d89d', '🎯', 'active', 3, '2026-06-07 13:44:06'),
(2, 4, 'Sksksk', 1550.00, '2026-06-07', '#14452f', '🎯', 'active', 4, '2026-06-07 21:05:48');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `scheduled_items`
--

CREATE TABLE `scheduled_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `account_id` int(10) UNSIGNED DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('income','expense') NOT NULL DEFAULT 'expense',
  `title` varchar(160) NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `recurrence` enum('none','weekly','monthly','yearly') NOT NULL DEFAULT 'none',
  `status` enum('pending','paid','skipped') NOT NULL DEFAULT 'pending',
  `notes` varchar(255) DEFAULT NULL,
  `last_paid_on` date DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `scheduled_items`
--

INSERT INTO `scheduled_items` (`id`, `household_id`, `account_id`, `category_id`, `type`, `title`, `amount`, `due_date`, `recurrence`, `status`, `notes`, `last_paid_on`, `created_by`, `created_at`) VALUES
(2, 4, 4, 80, 'income', 'Maaş ödemesi', 45000.00, '2026-08-10', 'monthly', 'pending', '', '2026-06-07', 4, '2026-06-07 20:57:02'),
(3, 7, NULL, 130, 'expense', 'Ev kirası', 19000.00, '2026-06-15', 'monthly', 'pending', 'Zam yapılmadı', NULL, 1, '2026-06-23 09:01:37'),
(4, 7, NULL, 146, 'income', 'Yeşilköy Maaşı', 30000.00, '2026-06-10', 'monthly', 'pending', '', NULL, 1, '2026-06-23 09:02:10'),
(5, 7, NULL, 145, 'income', 'Otel Maaş', 48000.00, '2026-06-05', 'monthly', 'pending', 'Avans Eksik', NULL, 1, '2026-06-23 09:02:53'),
(6, 7, NULL, 145, 'income', 'Avans', 20000.00, '2026-06-20', 'monthly', 'pending', 'Avans', NULL, 1, '2026-06-23 09:03:15'),
(7, 7, NULL, 145, 'income', 'Merve maaş', 70000.00, '2026-07-30', 'monthly', 'pending', '', '2026-06-23', 3, '2026-06-23 09:07:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `shopping_items`
--

CREATE TABLE `shopping_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `list_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `icon` varchar(8) NOT NULL DEFAULT '?',
  `color` varchar(20) NOT NULL DEFAULT '#EFEBE0',
  `qty` varchar(40) DEFAULT NULL,
  `note` varchar(200) DEFAULT NULL,
  `est_price` decimal(12,2) DEFAULT NULL,
  `is_done` tinyint(1) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `done_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `shopping_items`
--

INSERT INTO `shopping_items` (`id`, `list_id`, `name`, `icon`, `color`, `qty`, `note`, `est_price`, `is_done`, `position`, `created_by`, `created_at`, `done_at`) VALUES
(42, 3, 'Kivi', '🥝', '#E7F3EB', NULL, NULL, NULL, 1, 1, 2, '2026-06-08 18:03:06', '2026-06-08 18:03:24'),
(43, 3, 'Mantar', '🍄', '#E7F3EB', NULL, NULL, NULL, 1, 2, 2, '2026-06-08 18:03:09', '2026-06-08 18:03:28'),
(44, 3, 'Mantar', '🍄', '#E7F3EB', NULL, NULL, NULL, 1, 3, 2, '2026-06-08 18:03:10', '2026-06-08 18:03:29'),
(45, 3, 'Peçete', '🧻', '#F3EEF9', NULL, NULL, NULL, 0, 4, 2, '2026-06-08 18:03:11', NULL),
(46, 3, 'Duş Jeli', '🧼', '#F3EEF9', NULL, NULL, NULL, 0, 5, 2, '2026-06-08 18:03:11', NULL),
(47, 3, 'Şeker', '🍬', '#F0EDE4', NULL, NULL, NULL, 0, 6, 2, '2026-06-08 18:03:12', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `shopping_lists`
--

CREATE TABLE `shopping_lists` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `icon` varchar(8) NOT NULL DEFAULT '?',
  `color` varchar(20) NOT NULL DEFAULT '#14452F',
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `shopping_lists`
--

INSERT INTO `shopping_lists` (`id`, `household_id`, `name`, `icon`, `color`, `status`, `created_by`, `created_at`) VALUES
(2, 4, 'Pazar alış veriş i', '🛒', '#14452f', 'active', 4, '2026-06-07 21:07:03'),
(3, 2, 'Market', '🛒', '#00ff00', 'active', 2, '2026-06-08 18:03:01');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_settings`
--

CREATE TABLE `site_settings` (
  `skey` varchar(64) NOT NULL,
  `svalue` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `site_settings`
--

INSERT INTO `site_settings` (`skey`, `svalue`, `updated_at`) VALUES
('captcha_enabled', '1', '2026-06-06 00:43:37'),
('feat_assets', '1', '2026-06-06 00:43:37'),
('feat_calculator', '1', '2026-06-06 00:43:37'),
('feat_calendar', '1', '2026-06-06 00:43:37'),
('feat_chat', '1', '2026-06-06 00:43:37'),
('feat_goals', '1', '2026-06-06 00:43:37'),
('feat_import', '1', '2026-06-06 00:43:37'),
('feat_messages', '0', '2026-06-06 00:43:37'),
('feat_reports', '1', '2026-06-06 00:43:37'),
('feat_shopping', '1', '2026-06-07 19:31:41'),
('feat_tickets', '1', '2026-06-06 00:43:37'),
('kvkk_text', 'KİŞİSEL VERİLERİN KORUNMASI HAKKINDA AYDINLATMA VE AÇIK RIZA METNİ\r\n\r\nİşbu metin, 6698 sayılı Kişisel Verilerin Korunması Kanunu (\"KVKK\") kapsamında, veri sorumlusu sıfatıyla tarafımızca hazırlanmıştır.\r\n\r\n1) İŞLENEN KİŞİSEL VERİLER\r\nÜyelik ve hizmetin sunulması amacıyla; ad-soyad, kullanıcı adı, e-posta adresiniz ve tarafınızca sisteme girilen gelir-gider/bütçe verileri işlenmektedir.\r\n\r\n2) İŞLEME AMAÇLARI\r\nKişisel verileriniz; üyelik kaydının oluşturulması, hizmetin sunulması, hesabınızın güvenliğinin sağlanması, talep ve şikayetlerinizin yönetilmesi ve yasal yükümlülüklerin yerine getirilmesi amaçlarıyla işlenir.\r\n\r\n3) HUKUKİ SEBEP\r\nVerileriniz, KVKK madde 5 kapsamında \"bir sözleşmenin kurulması veya ifasıyla doğrudan doğruya ilgili olması\", \"hukuki yükümlülük\" ve gerektiğinde \"açık rızanız\" hukuki sebeplerine dayanılarak işlenir.\r\n\r\n4) AKTARIM\r\nKişisel verileriniz, yasal zorunluluklar dışında üçüncü kişilerle paylaşılmaz. Veriler, hizmetin barındırıldığı sunucu/altyapı sağlayıcısı nezdinde saklanır.\r\n\r\n5) HAKLARINIZ (KVKK m.11)\r\nKişisel verilerinizin işlenip işlenmediğini öğrenme, düzeltilmesini veya silinmesini isteme, işlemenin sınırlandırılmasını talep etme ve kanunda sayılan diğer haklarınızı kullanma hakkına sahipsiniz. Taleplerinizi destek/iletişim kanallarımız üzerinden iletebilirsiniz.\r\n\r\n6) AÇIK RIZA\r\nİşbu metni okuduğunuzu, kişisel verilerinizin yukarıda belirtilen kapsamda işlenmesine açık rıza gösterdiğinizi kabul edersiniz.\r\n\r\nNot: Bu metin genel bir bilgilendirme şablonudur. Yürürlükteki mevzuata ve kendi işleme faaliyetlerinize tam uyum için bir hukuk danışmanından görüş almanız önerilir.', '2026-06-06 00:43:37'),
('maintenance_mode', '0', '2026-06-06 00:43:37'),
('registration_enabled', '1', '2026-06-06 00:43:37'),
('seo_description', 'Ev halkı için gelir-gider takibi, bütçe, raporlar ve banka ekstresi içe aktarma.', '2026-06-06 00:43:37'),
('seo_keywords', 'ev bütçesi, gider takibi, bütçe, muhasebe, aile bütçesi', '2026-06-06 00:43:37'),
('seo_title', 'Ev Muhasebe · Hane Bütçe ve Gider Takibi', '2026-06-06 00:43:37'),
('site_name', 'Ev Muhasebe', '2026-06-06 00:43:37');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tickets`
--

CREATE TABLE `tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(180) NOT NULL,
  `status` enum('open','answered','closed') NOT NULL DEFAULT 'open',
  `priority` enum('low','normal','high') NOT NULL DEFAULT 'normal',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `subject`, `status`, `priority`, `created_at`, `updated_at`) VALUES
(1, 2, 'Deneme talep', 'answered', 'normal', '2026-06-06 00:42:17', '2026-06-06 00:43:01'),
(2, 3, 'Selamın aleyküm', 'open', 'high', '2026-06-07 13:42:03', '2026-06-07 13:42:03'),
(3, 4, 'Zzz', 'closed', 'normal', '2026-06-07 21:11:13', '2026-06-07 21:11:33'),
(4, 4, 'Jrjddj', 'closed', 'normal', '2026-06-07 21:22:21', '2026-06-07 21:22:36'),
(5, 4, 'Sjjs', 'closed', 'normal', '2026-06-07 21:23:01', '2026-06-07 21:23:21');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ticket_messages`
--

INSERT INTO `ticket_messages` (`id`, `ticket_id`, `user_id`, `is_admin`, `message`, `created_at`) VALUES
(1, 1, 2, 0, 'Deneme taleptir dikkate almayınız', '2026-06-06 00:42:17'),
(2, 1, 1, 1, 'Tamam', '2026-06-06 00:43:01'),
(3, 2, 3, 0, 'Sea', '2026-06-07 13:42:03'),
(4, 3, 4, 0, 'Xxxx', '2026-06-07 21:11:13'),
(5, 4, 4, 0, 'Odmddk', '2026-06-07 21:22:21'),
(6, 5, 4, 0, 'Ndjd', '2026-06-07 21:23:01'),
(7, 5, 4, 1, 'Znzj', '2026-06-07 21:23:04');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `transactions`
--

CREATE TABLE `transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `household_id` int(10) UNSIGNED NOT NULL,
  `account_id` int(10) UNSIGNED DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `transaction_date` date NOT NULL,
  `source` enum('manual','import') NOT NULL DEFAULT 'manual',
  `import_batch_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `transactions`
--

INSERT INTO `transactions` (`id`, `household_id`, `account_id`, `category_id`, `user_id`, `type`, `amount`, `description`, `transaction_date`, `source`, `import_batch_id`, `created_at`) VALUES
(4, 3, NULL, NULL, 3, 'expense', 1000.00, 'ÖDEMENİZ İÇİN TEŞEKKÜR EDERİZ', '2026-05-05', 'import', 1, '2026-06-07 13:40:43'),
(5, 3, NULL, NULL, 3, 'expense', 30000.00, 'ÖDEMENİZ İÇİN TEŞEKKÜR EDERİZ', '2026-05-05', 'import', 1, '2026-06-07 13:40:43'),
(6, 3, NULL, NULL, 3, 'expense', 750.00, 'OFÖ BONUS GERİ ALIM', '2026-05-06', 'import', 1, '2026-06-07 13:40:43'),
(7, 3, NULL, NULL, 3, 'expense', 2012.07, 'ÖDEMENİZ İÇİN TEŞEKKÜR EDERİZ', '2026-05-13', 'import', 1, '2026-06-07 13:40:43'),
(8, 3, NULL, NULL, 3, 'expense', 4497.93, 'ÖDEMENİZ İÇİN TEŞEKKÜR EDERİZ', '2026-05-21', 'import', 1, '2026-06-07 13:40:43'),
(9, 3, NULL, NULL, 3, 'expense', 745.69, 'EKSİYE DÖNEN ÖDÜLDÜZELTME', '2026-06-01', 'import', 1, '2026-06-07 13:40:43'),
(10, 3, NULL, NULL, 3, 'expense', 745.69, 'Pazartesi hesap kesimli hesap özetinizde ödül toplamınızı sıfırlamak amacı ile', '2026-06-01', 'import', 1, '2026-06-07 13:40:43'),
(11, 3, NULL, NULL, 3, 'expense', 0.58, 'BONUS BEDAVA ALIŞVERİS İADESİ', '2026-05-09', 'import', 1, '2026-06-07 13:40:43'),
(12, 3, NULL, 43, 3, 'expense', 0.57, 'MİGROS-AİRPORT', '2026-05-16', 'import', 1, '2026-06-07 13:40:43'),
(13, 3, NULL, NULL, 3, 'expense', 0.30, '3512 ESENYURT MEŞE', '2026-05-25', 'import', 1, '2026-06-07 13:40:43'),
(14, 3, NULL, NULL, 3, 'expense', 0.06, '3512 ESENYURT MEŞE', '2026-05-25', 'import', 1, '2026-06-07 13:40:43'),
(15, 3, NULL, NULL, 3, 'expense', 0.10, '3512 ESENYURT MEŞE', '2026-05-25', 'import', 1, '2026-06-07 13:40:43'),
(16, 3, NULL, 53, 3, 'expense', 449.42, 'TRENDYOL.COM 0,09', '2026-05-09', 'import', 1, '2026-06-07 13:40:43'),
(17, 3, NULL, 53, 3, 'expense', 398.42, 'TRENDYOL.COM 0,08', '2026-05-10', 'import', 1, '2026-06-07 13:40:43'),
(18, 3, NULL, 53, 3, 'expense', 18000.00, 'TRENDYOL.COM 2.000,00x9=', '2026-05-11', 'import', 1, '2026-06-07 13:40:43'),
(19, 3, NULL, 53, 3, 'expense', 1166.89, 'TRENDYOL.COM 388,96x3=', '2026-05-11', 'import', 1, '2026-06-07 13:40:43'),
(20, 3, NULL, 53, 3, 'expense', 2835.00, 'TRENDYOL.COM 945,00x3=', '2026-05-16', 'import', 1, '2026-06-07 13:40:43'),
(21, 3, NULL, 53, 3, 'expense', 2800.00, 'TRENDYOL.COM 933,34x3=', '2026-05-24', 'import', 1, '2026-06-07 13:40:43'),
(22, 3, NULL, 53, 3, 'expense', 493.84, 'TRENDYOL.COM 164,61x3=', '2026-05-29', 'import', 1, '2026-06-07 13:40:43'),
(23, 3, NULL, 53, 3, 'expense', 1943.84, 'TRENDYOL.COM 647,94x3=', '2026-05-29', 'import', 1, '2026-06-07 13:40:43'),
(24, 3, NULL, NULL, 3, 'expense', 1.30, 'BTM MAGAZALARI', '2026-05-25', 'import', 1, '2026-06-07 13:40:43'),
(25, 3, NULL, NULL, 3, 'expense', 3575.62, 'SENCARD', '2026-05-28', 'import', 1, '2026-06-07 13:40:43'),
(26, 3, NULL, 43, 3, 'expense', 0.27, 'MİGROS ONE', '2026-05-08', 'import', 1, '2026-06-07 13:40:43'),
(27, 3, NULL, 43, 3, 'expense', 0.64, 'MİGROS HEMEN', '2026-05-19', 'import', 1, '2026-06-07 13:40:43'),
(28, 3, NULL, 43, 3, 'expense', 1272.70, 'MİGROS HEMEN 0,64', '2026-05-19', 'import', 1, '2026-06-07 13:40:43'),
(29, 3, NULL, 43, 3, 'expense', 237.00, 'BİM V927 MUHTARLIK - BEYL', '2026-05-12', 'import', 1, '2026-06-07 13:40:43'),
(30, 3, NULL, NULL, 3, 'expense', 280.00, 'SAF INCI', '2026-05-09', 'import', 1, '2026-06-07 13:40:43'),
(31, 3, NULL, NULL, 3, 'expense', 60.00, 'PINAR MEDİKAL SAĞLIK', '2026-05-19', 'import', 1, '2026-06-07 13:40:43'),
(32, 3, NULL, NULL, 3, 'expense', 25.00, 'PINAR MEDİKAL BEYLİKDÜZÜ', '2026-05-19', 'import', 1, '2026-06-07 13:40:43'),
(33, 3, NULL, NULL, 3, 'expense', 5000.00, 'TOYTAŞ TURİSTİK OTELLER', '2026-05-09', 'import', 1, '2026-06-07 13:40:43'),
(34, 3, NULL, NULL, 3, 'income', 1980.00, 'DOGADAN GELEN YUSUF', '2026-05-10', 'import', 1, '2026-06-07 13:40:43'),
(35, 3, NULL, NULL, 3, 'expense', 275.00, 'GLORİA JEANS', '2026-05-16', 'import', 1, '2026-06-07 13:40:43'),
(36, 3, NULL, NULL, 3, 'expense', 1475.00, 'AYNALI GRUP İÇ VE DIŞ TİC', '2026-05-14', 'import', 1, '2026-06-07 13:40:43'),
(37, 3, NULL, 43, 3, 'expense', 503.00, 'BIM T414 NILUFER', '2026-05-20', 'import', 1, '2026-06-07 13:40:43'),
(38, 3, NULL, NULL, 3, 'expense', 230.00, 'BARIŞ TEKEL BAHRİ YA', '2026-05-24', 'import', 1, '2026-06-07 13:40:43'),
(39, 3, NULL, NULL, 3, 'expense', 808.00, 'ANTEPLİOĞLU', '2026-05-30', 'import', 1, '2026-06-07 13:40:43'),
(40, 3, NULL, NULL, 3, 'expense', 335.00, 'OZ LEZZET BOREK', '2026-05-19', 'import', 1, '2026-06-07 13:40:43'),
(41, 3, NULL, NULL, 3, 'expense', 60.00, 'NİNOVA', '2026-05-25', 'import', 1, '2026-06-07 13:40:43'),
(42, 3, NULL, 51, 3, 'expense', 152.62, 'AYLIN ECZANESI', '2026-05-16', 'import', 1, '2026-06-07 13:40:43'),
(43, 3, NULL, NULL, 3, 'expense', 1366.00, 'GRATİS İST AİRPORT', '2026-05-16', 'import', 1, '2026-06-07 13:40:43'),
(44, 3, NULL, NULL, 3, 'expense', 4801.20, 'HEPSİPAY-HEP*HEPSİBURADA 1.600,40x3=', '2026-05-28', 'import', 1, '2026-06-07 13:40:43'),
(45, 3, NULL, 55, 3, 'expense', 154.98, 'APPLE.COM/BILL', '2026-05-12', 'import', 1, '2026-06-07 13:40:43'),
(46, 3, NULL, 55, 3, 'expense', 129.99, 'APPLE.COM/BILL', '2026-05-18', 'import', 1, '2026-06-07 13:40:43'),
(47, 3, NULL, 55, 3, 'expense', 154.98, 'APPLE.COM/BILL', '2026-05-28', 'import', 1, '2026-06-07 13:40:43'),
(137, 4, NULL, NULL, 4, 'expense', 6067.00, 'ÖDEMENİZ İÇİN TEŞEKKÜR EDERİZ', '2026-05-12', 'import', 4, '2026-06-10 13:24:35'),
(138, 4, NULL, NULL, 4, 'expense', 0.82, 'BONUS BEDAVA ALIŞVERİS İADESİ', '2026-05-06', 'import', 4, '2026-06-10 13:24:35'),
(139, 4, NULL, NULL, 4, 'expense', 0.53, 'BONUS BEDAVA ALIŞVERİŞ', '2026-05-17', 'import', 4, '2026-06-10 13:24:35'),
(140, 4, NULL, NULL, 4, 'expense', 7.57, 'BONUS BEDAVA ALIŞVERİŞ', '2026-05-17', 'import', 4, '2026-06-10 13:24:35'),
(141, 4, NULL, NULL, 4, 'expense', 7.57, 'BONUS BEDAVA ALIŞVERİS İADESİ', '2026-05-18', 'import', 4, '2026-06-10 13:24:35'),
(142, 4, NULL, NULL, 4, 'expense', 0.06, 'ECE PETROL OTOM INS SAN', '2026-05-01', 'import', 4, '2026-06-10 13:24:35'),
(143, 4, NULL, NULL, 4, 'expense', 0.04, 'ECE PETROL OTOM INS SAN', '2026-05-04', 'import', 4, '2026-06-10 13:24:35'),
(144, 4, NULL, NULL, 4, 'expense', 0.03, 'ECE PETROL OTOM INS SAN', '2026-05-09', 'import', 4, '2026-06-10 13:24:35'),
(145, 4, NULL, 64, 4, 'expense', 0.03, '9930 0654 A101 ALI RIZA/E', '2026-05-10', 'import', 4, '2026-06-10 13:24:35'),
(146, 4, NULL, NULL, 4, 'expense', 5.52, 'IYZICO/MOTOPIT.COM.TR', '2026-05-18', 'import', 4, '2026-06-10 13:24:35'),
(147, 4, NULL, NULL, 4, 'expense', 413.04, 'Kart Ödeme Güvencesi', '2026-05-30', 'import', 4, '2026-06-10 13:24:35'),
(148, 4, NULL, 71, 4, 'expense', 0.04, 'IŞIKLAR OHT-1 BATI SHELL', '2026-05-01', 'import', 4, '2026-06-10 13:24:35'),
(149, 4, NULL, NULL, 4, 'expense', 310.53, 'DEMPET PETROL ÜRÜNLERİ', '2026-05-11', 'import', 4, '2026-06-10 13:24:35'),
(150, 4, NULL, 74, 4, 'expense', 288.17, 'TRENDYOL.COM 0,06', '2026-05-06', 'import', 4, '2026-06-10 13:24:35'),
(151, 4, NULL, 74, 4, 'expense', 0.05, 'TRENDYOL.COM', '2026-05-17', 'import', 4, '2026-06-10 13:24:35'),
(152, 4, NULL, NULL, 4, 'expense', 0.53, 'N11 ONLİNE ALIŞVERİŞ', '2026-05-17', 'import', 4, '2026-06-10 13:24:35'),
(153, 4, NULL, NULL, 4, 'expense', 1061.33, 'N11 ONLİNE ALIŞVERİŞ 0,53', '2026-05-18', 'import', 4, '2026-06-10 13:24:35'),
(154, 4, NULL, 74, 4, 'expense', 0.07, 'TRENDYOL YEMEK', '2026-05-13', 'import', 4, '2026-06-10 13:24:35'),
(155, 4, NULL, 74, 4, 'expense', 0.05, 'TRENDYOL YEMEK', '2026-05-17', 'import', 4, '2026-06-10 13:24:35'),
(156, 4, NULL, 64, 4, 'expense', 390.00, 'GETİR - OPERASYON', '2026-05-02', 'import', 4, '2026-06-10 13:24:35'),
(157, 4, NULL, NULL, 4, 'expense', 1.05, 'OBİLET.COM', '2026-05-08', 'import', 4, '2026-06-10 13:24:35'),
(158, 4, NULL, NULL, 4, 'expense', 415.40, 'ÜNSOY TURİZM İNŞAAT', '2026-05-10', 'import', 4, '2026-06-10 13:24:35'),
(159, 4, NULL, 77, 4, 'expense', 355.74, 'MOKA UNITED/YEMEKSEPETI', '2026-04-30', 'import', 4, '2026-06-10 13:24:35'),
(160, 4, NULL, 77, 4, 'expense', 590.00, 'MOKA UNITED/YEMEKSEPETI', '2026-05-08', 'import', 4, '2026-06-10 13:24:35'),
(161, 4, NULL, NULL, 4, 'expense', 480.00, 'PİLAVCI SEYFO', '2026-05-09', 'import', 4, '2026-06-10 13:24:35'),
(162, 4, NULL, NULL, 4, 'expense', 585.00, 'BULUT KEBAP DÖNER', '2026-05-11', 'import', 4, '2026-06-10 13:24:35'),
(163, 4, NULL, 77, 4, 'expense', 10.00, 'MOKA UNITED/YEMEKSEPETI', '2026-05-12', 'import', 4, '2026-06-10 13:24:35'),
(164, 4, NULL, 77, 4, 'expense', 217.99, 'MOKA UNITED/YEMEKSEPETI', '2026-05-12', 'import', 4, '2026-06-10 13:24:35'),
(165, 4, NULL, 77, 4, 'expense', 420.00, 'MOKA UNITED/YEMEKSEPETI', '2026-05-13', 'import', 4, '2026-06-10 13:24:35'),
(166, 4, NULL, NULL, 4, 'expense', 1760.00, 'HALİS DÜRÜM BEYKENT', '2026-05-18', 'import', 4, '2026-06-10 13:24:35'),
(167, 4, NULL, NULL, 4, 'expense', 410.00, 'BULUT KEBAP DONER', '2026-05-19', 'import', 4, '2026-06-10 13:24:35'),
(168, 4, NULL, 77, 4, 'expense', 504.00, 'MOKA UNITED/YEMEKSEPETI', '2026-05-20', 'import', 4, '2026-06-10 13:24:35'),
(169, 4, NULL, 64, 4, 'expense', 9.50, '9942 F762 A101 ISIKLAR', '2026-05-01', 'import', 4, '2026-06-10 13:24:35'),
(170, 4, NULL, NULL, 4, 'expense', 909.80, 'BIRLIK GROSS TOPTAN PERAK', '2026-05-10', 'import', 4, '2026-06-10 13:24:35'),
(171, 4, NULL, 70, 4, 'expense', 158.00, 'PAYCELL/TURKCELL FAT', '2026-05-01', 'import', 4, '2026-06-10 13:24:35'),
(172, 4, NULL, 70, 4, 'expense', 1338.90, 'PAYCELL/TURKCELL FAT', '2026-05-11', 'import', 4, '2026-06-10 13:24:35'),
(173, 4, NULL, 70, 4, 'expense', 383.75, 'VODAFONE UMUT ILETISIM', '2026-05-19', 'import', 4, '2026-06-10 13:24:35'),
(174, 4, NULL, NULL, 4, 'expense', 15.00, 'S/KARAYOLLARI GM', '2026-05-02', 'import', 4, '2026-06-10 13:24:35'),
(175, 4, NULL, NULL, 4, 'expense', 262.00, 'S/ICA YSS 3 KOPRU', '2026-05-06', 'import', 4, '2026-06-10 13:24:35'),
(176, 4, NULL, 71, 4, 'expense', 27.00, 'AVRUPA OTOYOLU YATIRIM VE', '2026-05-06', 'import', 4, '2026-06-10 13:24:35'),
(177, 4, NULL, 80, 4, 'income', 400.00, 'Internet Nakit Avans', '2026-05-03', 'import', 4, '2026-06-10 13:24:35'),
(178, 4, NULL, 80, 4, 'income', 4.60, 'NAKİT AVANS ÜCRETİ', '2026-05-03', 'import', 4, '2026-06-10 13:24:35');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `avatar_color` varchar(7) NOT NULL DEFAULT '#13452F',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `theme` varchar(20) NOT NULL DEFAULT 'standart',
  `notify_email` tinyint(1) NOT NULL DEFAULT 1,
  `notify_transactions` tinyint(1) NOT NULL DEFAULT 1,
  `notify_imports` tinyint(1) NOT NULL DEFAULT 1,
  `notify_upcoming` tinyint(1) NOT NULL DEFAULT 1,
  `notify_goals` tinyint(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `kvkk_accepted_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password_hash`, `avatar_color`, `created_at`, `theme`, `notify_email`, `notify_transactions`, `notify_imports`, `notify_upcoming`, `notify_goals`, `is_admin`, `is_active`, `kvkk_accepted_at`, `last_login_at`) VALUES
(1, 'Berkan Pektaş', 'berkan', 'berkanpekta44@gmail.com', '$2y$10$hjm3iGke8EuiN0qvDBrAx.3ZKiJ7svKO7aH2gIxxCaT0sfQyEO/26', '#DB2777', '2026-06-05 09:36:43', 'standart', 1, 1, 1, 1, 1, 1, 1, NULL, '2026-06-23 09:10:47'),
(2, 'Görkem Gelici', 'gorkem', 'gorkemgelici@gmail.com', '$2y$10$E7jQhEloevGlPpaxNzMprOuFM630Osyc5Owi3lwu9wW0Z4.Z6PFZ6', '#9333EA', '2026-06-05 09:49:04', 'kahve', 1, 1, 1, 1, 1, 1, 1, NULL, '2026-06-08 18:01:16'),
(3, 'Merve  Pektaş', 'Mervepektas', 'mervesencer12@hotmail.com', '$2y$10$4fbIRJCVtIl/9YxPPK490utfI7BxuZNpU7DxoEycMb8hyOBkyhWfi', '#1D4ED8', '2026-06-05 10:06:05', 'standart', 1, 1, 1, 1, 1, 1, 1, NULL, '2026-06-23 09:11:50'),
(4, 'Mert Pektaş', 'Mertpektas', 'mertpektas4434@gmail.com', '$2y$10$AYtyyntbXSmExu14cBTnZuoBr0OeRqPoBSYpXrAWp5cEnPAk4i8aW', '#C2410C', '2026-06-05 15:31:25', 'standart', 1, 1, 1, 1, 1, 1, 1, NULL, '2026-06-10 13:20:58'),
(5, 'Nurkader Güneş', 'Nurkader', 'nur.kaderg28@gmail.com', '$2y$10$xy6orPUBAcpyw5n2WZ2hC.5w85XBDtBr43gaZuU8vpWEpo.JVwrCq', '#B98A2E', '2026-06-07 22:48:33', 'pembe', 1, 1, 1, 1, 1, 0, 1, '2026-06-07 22:48:33', '2026-06-07 22:52:07');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_account_household` (`household_id`);

--
-- Tablo için indeksler `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_household` (`household_id`,`created_at`);

--
-- Tablo için indeksler `asset_holdings`
--
ALTER TABLE `asset_holdings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asset_household` (`household_id`);

--
-- Tablo için indeksler `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_budget` (`household_id`,`category_id`),
  ADD KEY `fk_budget_category` (`category_id`);

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_household` (`household_id`),
  ADD KEY `idx_category_type` (`household_id`,`type`);

--
-- Tablo için indeksler `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_created` (`created_at`),
  ADD KEY `fk_chat_user` (`user_id`);

--
-- Tablo için indeksler `direct_messages`
--
ALTER TABLE `direct_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dm_pair` (`from_user`,`to_user`),
  ADD KEY `idx_dm_to` (`to_user`,`read_at`);

--
-- Tablo için indeksler `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD PRIMARY KEY (`code`);

--
-- Tablo için indeksler `goal_contributions`
--
ALTER TABLE `goal_contributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gc_goal` (`goal_id`);

--
-- Tablo için indeksler `households`
--
ALTER TABLE `households`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_household_code` (`join_code`),
  ADD KEY `idx_household_creator` (`created_by`);

--
-- Tablo için indeksler `household_members`
--
ALTER TABLE `household_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_member` (`household_id`,`user_id`),
  ADD KEY `idx_member_user` (`user_id`);

--
-- Tablo için indeksler `import_batches`
--
ALTER TABLE `import_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_batch_household` (`household_id`),
  ADD KEY `fk_batch_user` (`user_id`);

--
-- Tablo için indeksler `import_rules`
--
ALTER TABLE `import_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rule_household` (`household_id`),
  ADD KEY `fk_rule_category` (`category_id`);

--
-- Tablo için indeksler `invitations`
--
ALTER TABLE `invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_invite_token` (`token`),
  ADD KEY `idx_invite_household` (`household_id`),
  ADD KEY `fk_invite_user` (`invited_by`);

--
-- Tablo için indeksler `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reset_token` (`token`),
  ADD KEY `idx_reset_user` (`user_id`);

--
-- Tablo için indeksler `savings_goals`
--
ALTER TABLE `savings_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_goal_household` (`household_id`);

--
-- Tablo için indeksler `scheduled_items`
--
ALTER TABLE `scheduled_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sched_household` (`household_id`,`due_date`),
  ADD KEY `fk_sched_account` (`account_id`),
  ADD KEY `fk_sched_category` (`category_id`);

--
-- Tablo için indeksler `shopping_items`
--
ALTER TABLE `shopping_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shopitem_list` (`list_id`);

--
-- Tablo için indeksler `shopping_lists`
--
ALTER TABLE `shopping_lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shoplist_household` (`household_id`);

--
-- Tablo için indeksler `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`skey`);

--
-- Tablo için indeksler `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_user` (`user_id`);

--
-- Tablo için indeksler `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tm_ticket` (`ticket_id`);

--
-- Tablo için indeksler `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tx_household` (`household_id`),
  ADD KEY `idx_tx_date` (`household_id`,`transaction_date`),
  ADD KEY `idx_tx_type` (`household_id`,`type`),
  ADD KEY `idx_tx_category` (`category_id`),
  ADD KEY `idx_tx_account` (`account_id`),
  ADD KEY `fk_tx_user` (`user_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- Tablo için AUTO_INCREMENT değeri `asset_holdings`
--
ALTER TABLE `asset_holdings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- Tablo için AUTO_INCREMENT değeri `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `direct_messages`
--
ALTER TABLE `direct_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `goal_contributions`
--
ALTER TABLE `goal_contributions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `households`
--
ALTER TABLE `households`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `household_members`
--
ALTER TABLE `household_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tablo için AUTO_INCREMENT değeri `import_batches`
--
ALTER TABLE `import_batches`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `import_rules`
--
ALTER TABLE `import_rules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=501;

--
-- Tablo için AUTO_INCREMENT değeri `invitations`
--
ALTER TABLE `invitations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `savings_goals`
--
ALTER TABLE `savings_goals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `scheduled_items`
--
ALTER TABLE `scheduled_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `shopping_items`
--
ALTER TABLE `shopping_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- Tablo için AUTO_INCREMENT değeri `shopping_lists`
--
ALTER TABLE `shopping_lists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_account_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_log_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `asset_holdings`
--
ALTER TABLE `asset_holdings`
  ADD CONSTRAINT `fk_asset_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `fk_budget_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_budget_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_category_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `direct_messages`
--
ALTER TABLE `direct_messages`
  ADD CONSTRAINT `fk_dm_from` FOREIGN KEY (`from_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dm_to` FOREIGN KEY (`to_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `goal_contributions`
--
ALTER TABLE `goal_contributions`
  ADD CONSTRAINT `fk_gc_goal` FOREIGN KEY (`goal_id`) REFERENCES `savings_goals` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `households`
--
ALTER TABLE `households`
  ADD CONSTRAINT `fk_household_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `household_members`
--
ALTER TABLE `household_members`
  ADD CONSTRAINT `fk_member_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `import_batches`
--
ALTER TABLE `import_batches`
  ADD CONSTRAINT `fk_batch_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_batch_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `import_rules`
--
ALTER TABLE `import_rules`
  ADD CONSTRAINT `fk_rule_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rule_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `invitations`
--
ALTER TABLE `invitations`
  ADD CONSTRAINT `fk_invite_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invite_user` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `savings_goals`
--
ALTER TABLE `savings_goals`
  ADD CONSTRAINT `fk_goal_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `scheduled_items`
--
ALTER TABLE `scheduled_items`
  ADD CONSTRAINT `fk_sched_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sched_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sched_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `shopping_items`
--
ALTER TABLE `shopping_items`
  ADD CONSTRAINT `fk_shopitem_list` FOREIGN KEY (`list_id`) REFERENCES `shopping_lists` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `shopping_lists`
--
ALTER TABLE `shopping_lists`
  ADD CONSTRAINT `fk_shoplist_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_ticket_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `fk_tm_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_tx_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
