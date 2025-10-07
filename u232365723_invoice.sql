-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 07, 2025 at 06:42 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u232365723_invoice`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `company`, `email`, `phone`, `country`, `address`, `created_at`) VALUES
(9, 'Ramesh', 'CCandC Solutions', 'ramesh@ccandcsolutions.com', '1236567891', 'Australia', '3rd Floor, ABC Towers, Chennai - 600 000\\r\\nTamil Nadu, India', '2025-10-07 15:19:21'),
(10, 'PinkSari', 'PinkSari', '', '', '', '', '2025-10-07 17:14:06'),
(11, 'INTEGRATED SYSTEMS', 'INTEGRATED SYSTEMS', '', '', '', '', '2025-10-07 17:16:58'),
(12, 'LIVING WAY A G CHURCH', 'LIVING WAY A G CHURCH', '', '', '', '', '2025-10-07 17:17:36'),
(13, 'Vaagiip', 'Vaagiip', '', '', '', '', '2025-10-07 17:18:28'),
(14, 'Rankraze', 'Rankraze', '', '', '', '', '2025-10-07 17:22:13'),
(15, 'Eissa Golden Seed', 'Eissa Golden Seed', '', '', '', '', '2025-10-07 17:22:53'),
(16, 'Klymate', 'Klymate', '', '', '', '', '2025-10-07 17:23:42'),
(17, 'ACA Perambur', 'ACA Perambur', '', '', '', '', '2025-10-07 17:24:30'),
(18, 'Greens Australia', 'Greens Australia', '', '', '', '', '2025-10-07 17:24:57'),
(19, 'Samrtan', 'Samrtan', '', '', '', '', '2025-10-07 17:27:01'),
(20, 'Palmera', 'Palmera', '', '', '', '', '2025-10-07 17:27:53');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `client_id` int(11) NOT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `total_gst` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `approx_inr_value` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `invoice_date`, `client_id`, `currency`, `subtotal`, `total_gst`, `total_amount`, `notes`, `terms`, `created_at`, `approx_inr_value`) VALUES
(16, 'ITS/25-26/0015', '2025-10-07', 9, 'INR', 1000.00, 180.00, 1180.00, '', '', '2025-10-07 15:19:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `hsn_code` varchar(50) DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `hsn_code`, `quantity`, `rate`, `gst_percent`, `amount`, `gst_amount`, `line_total`) VALUES
(11, 16, 'Web Design', '12122', 1.00, 1000.00, 18.00, 1000.00, 180.00, 1180.00);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_numbers`
--

CREATE TABLE `invoice_numbers` (
  `id` int(11) NOT NULL,
  `fy_label` varchar(10) NOT NULL,
  `last_seq` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `invoice_numbers`
--

INSERT INTO `invoice_numbers` (`id`, `fy_label`, `last_seq`) VALUES
(1, '25-26', 15),
(2, '23-24', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `invoice_numbers`
--
ALTER TABLE `invoice_numbers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fy_label` (`fy_label`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `invoice_numbers`
--
ALTER TABLE `invoice_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
