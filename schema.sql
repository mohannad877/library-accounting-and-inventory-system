-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 17 نوفمبر 2025 الساعة 12:32
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `schema`
--

-- --------------------------------------------------------

--
-- بنية الجدول `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `grade_from` varchar(50) DEFAULT NULL,
  `grade_to` varchar(50) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `series` varchar(100) DEFAULT NULL,
  `term` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `books`
--

INSERT INTO `books` (`id`, `title`, `category`, `grade_from`, `grade_to`, `price`, `quantity`, `series`, `term`) VALUES
(1, 'Rise Up', 'انجليزي', 'الرابع', 'السادس', 1200, 0, 'Rise Up', NULL),
(2, 'Learn English - Core', 'انجليزي', 'الرابع', 'السادس', 700, 0, 'Learn English', NULL),
(3, 'Learn English - Work', 'انجليزي', 'الرابع', 'السادس', 300, 0, 'Learn English', NULL),
(4, 'Better - ترم أول', 'انجليزي', 'الرابع', 'السادس', 800, 0, 'Better', 'ترم أول'),
(5, 'Better - ترم ثاني', 'انجليزي', 'الرابع', 'السادس', 800, 0, 'Better', 'ترم ثاني'),
(6, 'ويندوز 7', 'حاسوب', 'الأول', 'الإعدادي', 500, 0, 'ويندوز 7', NULL),
(7, 'المستقبل الرقمي', 'حاسوب', 'الأول', 'الإعدادي', 500, 0, 'المستقبل الرقمي', NULL),
(8, 'الطفل المبدع - عربي', 'تمهيدي', NULL, NULL, 600, 0, 'الطفل المبدع', NULL),
(9, 'الطفل المبدع - انجليزي', 'تمهيدي', NULL, NULL, 600, 0, 'الطفل المبدع', NULL),
(10, 'الطفل المبدع - حساب', 'تمهيدي', NULL, NULL, 600, 0, 'الطفل المبدع', NULL),
(11, 'الطفل المبدع - قرآن', 'تمهيدي', NULL, NULL, 600, 0, 'الطفل المبدع', NULL),
(18, 'تربية فنية', 'تربية فنية', 'الأول', 'الثاني الإعدادي', 500, 0, NULL, NULL),
(19, 'كنز الطفل - قرآن', 'تمهيدي', NULL, NULL, 700, 0, NULL, NULL),
(20, 'كنز الطفل - عربي', 'تمهيدي', NULL, NULL, 700, 0, NULL, NULL),
(21, 'كنز الطفل - انجليزي', 'تمهيدي', NULL, NULL, 700, 0, NULL, NULL),
(22, 'كنز الطفل - حساب', 'تمهيدي', NULL, NULL, 700, 0, NULL, NULL),
(23, 'كنز الطفل - علوم و فنون', 'تمهيدي', NULL, NULL, 700, 0, NULL, NULL),
(24, 'المبدع الصغير - عربي - الترم الأول', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(25, 'المبدع الصغير - عربي - الترم الثاني', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(26, 'المبدع الصغير - حساب - الترم الأول', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(27, 'المبدع الصغير - حساب - الترم الثاني', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(28, 'المبدع الصغير - إسلامية - الترم الأول', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(29, 'المبدع الصغير - إسلامية - الترم الثاني', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(30, 'المبدع الصغير - انجليزي - الترم الأول', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(31, 'المبدع الصغير - انجليزي - الترم الثاني', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(32, 'المبدع الصغير - علوم - الترم الأول', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(33, 'المبدع الصغير - علوم - الترم الثاني', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(34, 'المبدع الصغير - كمبيوتر - الترم الأول', 'أساسي', NULL, NULL, 700, 0, NULL, NULL),
(35, 'المبدع الصغير - كمبيوتر - الترم الثاني', 'أساسي', NULL, NULL, 700, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `book_stages`
--

CREATE TABLE `book_stages` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `stage` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `book_stages`
--

INSERT INTO `book_stages` (`id`, `book_id`, `stage`, `quantity`) VALUES
(1, 4, 'تمهيدي', 3),
(2, 4, 'ثاني أساسي', 55),
(3, 4, 'ثالث إعدادي', 77);

-- --------------------------------------------------------

--
-- بنية الجدول `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `sale_date` datetime DEFAULT current_timestamp(),
  `type` varchar(50) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `sales_items`
--

CREATE TABLE `sales_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `stage` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `book_id` int(11) DEFAULT NULL,
  `stage` varchar(50) DEFAULT NULL,
  `type` enum('صرف','وارد') DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  `invoice_number` varchar(255) DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(10) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`, `role`) VALUES
(1, 'admin', '$2y$10$YgBo2WJ7DK/LRwRe0n9pB.xIL1Zw3rQsWih56ZF1kR3R2lHQTx.CG', '2025-06-09 11:36:37', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `book_stages`
--
ALTER TABLE `book_stages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`);

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
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `book_stages`
--
ALTER TABLE `book_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `book_stages`
--
ALTER TABLE `book_stages`
  ADD CONSTRAINT `book_stages_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `sales_items`
--
ALTER TABLE `sales_items`
  ADD CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sales_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- قيود الجداول `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
