-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 11, 2026 at 11:45 AM
-- Server version: 8.0.40
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `armatech`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `account_id` int NOT NULL,
  `profile_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teller','customer') DEFAULT 'customer',
  `status` enum('active','inactive') DEFAULT 'active',
  `force_change` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`account_id`, `profile_id`, `username`, `password`, `role`, `status`, `force_change`) VALUES
(1, 1, 'teller1', 'teller1', 'teller', 'active', 0),
(2, 2, 'ARM-2026-7849', '$2y$10$pFR9MQCJGlOPh.5/2dpsp.eWL2FoK52n2ZVMecP0n3afPyG1Wi5lW', 'customer', 'active', 0),
(3, 3, 'ARM-2026-8682', '$2y$10$NNvGHtQJcq6K.fHAj3CxheNoipndWinYN2GFBRz0ags2m2xHglz9a', 'customer', 'active', 0),
(5, 4, 'admin', 'admin123', 'admin', 'active', 1),
(7, 7, 'ARM-TELLER-ASIERTO5922', '$2y$10$BmrwwKOslscbSyjHTtzQuuRaGHLY4BMQGE3/x69VJ28qJINeDE.56', 'teller', 'active', 0),
(8, 8, 'ARM-2026-1913', '$2y$10$oFQMPXK.O1Gvx9R5mODaq.qQnNHBO2BeBXiRQK/vy0tO8w/w05yZ2', 'customer', 'active', 0),
(9, 9, 'ARM-2026-1536', '$2y$10$.Tlvswse9JlGFkYB/EXb1eKtIHEpTY/rPcSPu8iros8JENnk4PBOO', 'customer', 'active', 0),
(10, 10, 'ARM-2026-7624', '$2y$10$I39Xrs8fD9TL7MDcDD.GNOx8x27CjgBNxdkLB4p5cMI/FlGLsTmcy', 'customer', 'active', 1),
(11, 11, 'ARM-2026-5767', '$2y$10$rJiZ5q.aFNKJO1t6oTYNDes1n5dFXAaLyKMF5kiM6Acc0e5NH47FC', 'customer', 'active', 0),
(12, 12, 'ARM-TELLER-LY8313', '$2y$10$k/kY08x39PeyiEwgnt0gke.J.gOfy1dA7/JXEOZAmkKBtA2yxIQhG', 'teller', 'active', 0),
(13, 13, 'ARM-2026-2179', '$2y$10$p/RKOmXcHth15c4lMjxjFOzvA9y2ByEfiWNLkC9h2DUVvu9kgyCrW', 'customer', 'active', 0);

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address_id` int NOT NULL,
  `profile_id` int NOT NULL,
  `house_no_street` varchar(150) DEFAULT NULL,
  `barangay` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) DEFAULT 'Metro Manila',
  `zip_code` varchar(10) DEFAULT NULL,
  `address_type` enum('permanent','present') DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`address_id`, `profile_id`, `house_no_street`, `barangay`, `city`, `province`, `zip_code`, `address_type`) VALUES
(1, 2, 'phase 9 package 3c block 1 lot 24 maharlika bagong silang', '176', 'Caloocan City', 'Metro Manila', '1428', 'present'),
(2, 3, '4547 Ebony Pass', '176', 'Caloocan City', 'Metro Manila', '1428', 'present'),
(4, 7, 'Ph1 Package 1 Block 1 Lot 3 ', '176', 'Caloocan City', 'Metro Manila', '1428', 'present'),
(5, 8, 'Lot 2 Block 14 Maginoo Brixton', '175', 'Caloocan City', 'Metro Manila', '1422', 'present'),
(6, 9, 'Ph1 Package 1 Block 1 Lot 3 ', '176', 'Caloocan City', 'Metro Manila', '1482', 'present'),
(7, 10, 'phase 8 package 3c block 1 lot 24 maharlika Bagong Silang', '175', 'Caloocan City', 'Metro Manila', '1428', 'present'),
(8, 11, 'phase 9 package 3c block 1 lot 24 maharlika Bagong Silang', '176', 'Caloocan City', 'Metro Manila', '1428', 'present'),
(9, 12, 'phase 9 package 3c block 1 lot 24 maharlika Bagong Silang', '176', 'Caloocan City', 'Metro Manila', '1428', 'present'),
(10, 13, 'phase 9 package 3c block 1 lot 24 maharlika Bagong Silang', '176', 'Caloocan City', 'Metro Manila', '1428', 'permanent');

-- --------------------------------------------------------

--
-- Table structure for table `device_cache`
--

CREATE TABLE `device_cache` (
  `id` int NOT NULL,
  `category` varchar(100) NOT NULL,
  `search_query` varchar(255) NOT NULL,
  `specs_json` json NOT NULL,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int NOT NULL,
  `transaction_id` int NOT NULL,
  `device_type` varchar(50) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(100) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `storage_capacity` varchar(20) DEFAULT NULL,
  `ram` varchar(20) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `inclusions` varchar(255) DEFAULT NULL,
  `condition_notes` text,
  `appraised_value` decimal(10,2) NOT NULL,
  `img_front` varchar(255) DEFAULT NULL,
  `img_back` varchar(255) DEFAULT NULL,
  `img_serial` varchar(255) DEFAULT NULL,
  `extra_specs` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `transaction_id`, `device_type`, `brand`, `model`, `serial_number`, `storage_capacity`, `ram`, `color`, `inclusions`, `condition_notes`, `appraised_value`, `img_front`, `img_back`, `img_serial`, `extra_specs`) VALUES
(1, 3, 'Smartphone', 'Xiaomi', 'Redmi Note 11', '37793/61ZT32237', '128GB', '8GB', 'Ocean Blue', 'Unit Only', 'Swollen Battery', 2500.00, NULL, NULL, NULL, NULL),
(2, 4, 'Laptop', 'Acer', 'Aspire 5', 'D856BAE6-382C-40B0-852B-A157F92E85A4', '256GB', '8GB', 'Black', 'Original Charger', '', 7000.00, NULL, NULL, NULL, NULL),
(3, 5, 'Smartphone', 'Apple', 'Iphone 15 Pro max', '356303481115127', '512GB', '12GB', 'Natural Titanium', 'Original Charger', '', 15000.00, NULL, NULL, NULL, NULL),
(4, 6, 'Smartphone', 'Apple', 'Iphone XR', '353136108849170', '128GB', '8GB', 'White', 'Unit Only', 'scratches and volume button damage ', 4000.00, NULL, NULL, NULL, NULL),
(5, 7, 'Tablet', 'Apple', 'Ipad Air', '657393/61ZT32137', '128GB', '8GB', 'Black', 'Unit Only', '', 21000.00, NULL, NULL, NULL, NULL),
(6, 8, 'Smartphone', 'Samsung', 'Galaxy S25', '101905067913663', '512GB', '12GB', 'Navy', 'Original Box, Original Charger', '', 28000.00, NULL, NULL, NULL, NULL),
(7, 9, 'Smartphone', 'Apple', 'Iphone 15', '12003841829184671', '128GB', '8GB', 'Pink', 'Unit Only', '', 25000.00, NULL, NULL, NULL, NULL),
(8, 10, 'Smartphone', 'Xiaomi', 'Redmi Note 13 pro plus', '37793/61ZT32237', '512GB', '12GB', 'White', 'Unit Only', 'nanakaw', 9000.00, NULL, NULL, NULL, NULL),
(9, 11, 'Smartphone', 'Apple', 'Iphone 12 mini', '356303481321512', '64GB', '4GB', 'Blue', 'Unit Only', '71 battery percentage ', 12000.00, NULL, NULL, NULL, NULL),
(10, 12, 'Smartphone', 'IPHONE ', 'IPHONE 15', '356303453kvj235', '128GB', '4GB', 'Pink', 'Unit Only', '', 25000.00, NULL, NULL, NULL, NULL),
(11, 13, 'Smartphone', 'Samsung', 'Galaxy S25', '356k3jk2315127', '256GB', '16GB', 'Black', 'Unit Only', '', 23500.00, NULL, NULL, NULL, NULL),
(12, 14, 'Smartphone', 'Xiaomi', 'Redmi Note 14 pro +', '215901291205123123', '512GB', '12GB', 'White', 'Unit Only', '', 28000.00, NULL, NULL, NULL, NULL),
(13, 15, 'Smartphone', 'Apple', 'Iphone 14', '353136lkj108849170', '256GB', '8GB', 'Black', 'Unit Only', 'The Battery Health is 80%', 15000.00, NULL, NULL, NULL, NULL),
(14, 16, 'Smartphone', 'Apple', 'Iphone 12', '5237793/63ZT32237', '128GB', '4GB', 'Ocean Blue', 'Unit Only', '', 12000.00, NULL, NULL, NULL, NULL),
(15, 17, 'Smartphone', 'Apple', 'Iphone 13', '353136108jdja9170', '128GB', '8GB', 'Black', 'Unit Only', 'Battery health is 80%', 15000.00, NULL, NULL, NULL, NULL),
(16, 18, 'Smartphone', 'Apple', 'Redmi Note 11', '37793/61ZT32237', '128GB', '8GB', 'Black', 'Unit Only, Receipt', 'none', 20000.00, 'PT-2026-15806_front_1775499422.png', 'PT-2026-15806_back_1775499422.jpg', 'PT-2026-15806_serial_1775499422.jpg', NULL),
(17, 19, 'Smartphone', 'Apple', 'Iphone XR', 'D856BAE6-382C-40B0-852B-A157F92E85A4', '128GB', '6GB', 'Navy', 'Unit Only, Original Box, Original Charger, Receipt', 'the battery health is 75%', 12000.00, 'PT-2026-67386_front_1775506377.jpg', 'PT-2026-67386_back_1775506377.jpg', 'PT-2026-67386_serial_1775506377.png', '{\"os\": \"iOS\", \"ram\": \"6GB\", \"storage\": \"128GB\"}'),
(18, 20, 'Smartphone', 'Samsung', 'Galaxy S25+', '37793/61ZT3241231', 'N/A', 'N/A', 'Blue', 'Unit Only, Original Box, Original Charger, Receipt', 'none', 23500.00, 'PT-2026-70559_front_1775512337.webp', 'PT-2026-70559_back_1775512337.webp', 'PT-2026-70559_serial_1775512337.JPG', '{\"os\": \"Android\", \"ram\": \"12GB\", \"storage\": \"128GB\"}'),
(19, 21, 'Smartphone', 'Apple', 'iPhone 16', '35313610342449170', 'N/A', 'N/A', 'Black', 'Unit Only, Original Box, Original Charger, Receipt', 'None', 23400.00, 'PT-2026-30589_front_1775523582.webp', 'PT-2026-30589_back_1775523582.webp', 'PT-2026-30589_serial_1775523582.jpg', '{\"os\": \"iOS\", \"ram\": \"8GB\", \"storage\": \"128GB\"}'),
(20, 22, 'Smartphone', 'Apple', 'iPhone 16', '353136108849170', 'N/A', 'N/A', 'White', 'Unit Only, Original Box, Original Charger, Receipt', 'N/A', 25999.99, 'PT-2026-26412_front_1775893232.webp', 'PT-2026-26412_back_1775893232.webp', 'PT-2026-26412_serial_1775893232.JPG', '{\"os\": \"iOS\", \"ram\": \"6GB\", \"storage\": \"256GB\"}');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int NOT NULL,
  `transaction_id` int NOT NULL,
  `teller_id` int NOT NULL,
  `payment_type` enum('interest_only','partial_payment','full_redemption') NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `old_principal` decimal(10,2) DEFAULT NULL,
  `new_principal` decimal(10,2) DEFAULT NULL,
  `date_paid` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `transaction_id`, `teller_id`, `payment_type`, `amount_paid`, `old_principal`, `new_principal`, `date_paid`) VALUES
(1, 3, 1, 'full_redemption', 2575.00, 2500.00, 0.00, '2026-02-17 05:49:13'),
(2, 4, 1, 'partial_payment', 4210.00, 7000.00, 3000.00, '2026-02-17 06:20:56'),
(3, 4, 1, 'full_redemption', 3090.00, 3000.00, 0.00, '2026-02-17 06:21:15'),
(4, 5, 1, 'interest_only', 450.00, 15000.00, 15000.00, '2026-02-17 18:46:10'),
(5, 5, 1, 'partial_payment', 4000.00, 15000.00, 11450.00, '2026-02-17 18:53:25'),
(6, 6, 7, 'partial_payment', 1120.00, 4000.00, 3000.00, '2026-02-18 18:38:27'),
(7, 8, 7, 'full_redemption', 28840.00, 28000.00, 0.00, '2026-02-19 15:54:38'),
(8, 9, 7, 'partial_payment', 10750.00, 25000.00, 15000.00, '2026-02-19 16:14:27'),
(9, 9, 7, 'full_redemption', 16200.00, NULL, NULL, '2026-02-20 04:58:29'),
(10, 5, 7, 'partial_payment', 5343.50, 11450.00, 6450.00, '2026-02-21 16:00:26'),
(11, 5, 7, 'full_redemption', 6643.50, 6450.00, 0.00, '2026-02-26 05:09:50'),
(12, 10, 7, 'full_redemption', 9000.00, NULL, NULL, '2026-02-26 05:15:00'),
(13, 11, 7, 'full_redemption', 12360.00, 12000.00, 0.00, '2026-03-13 21:35:37'),
(14, 12, 7, 'full_redemption', 27000.00, NULL, NULL, '2026-03-13 21:53:42'),
(15, 13, 7, 'partial_payment', 23525.00, 23500.00, 3500.00, '2026-03-14 03:13:45'),
(16, 13, 7, 'partial_payment', 2105.00, 3500.00, 1500.00, '2026-03-14 03:35:00'),
(17, 17, 7, 'partial_payment', 5450.00, 15000.00, 10000.00, '2026-03-14 13:10:17'),
(18, 17, 7, 'full_redemption', 10300.00, 10000.00, 0.00, '2026-03-14 13:11:21'),
(19, 13, 7, 'full_redemption', 22500.00, NULL, NULL, '2026-03-14 13:28:21'),
(20, 16, 7, 'full_redemption', 12360.00, 12000.00, 0.00, '2026-04-06 22:51:11'),
(21, 20, 12, 'full_redemption', 24205.00, 23500.00, 0.00, '2026-04-07 06:16:01'),
(22, 21, 7, 'full_redemption', 24102.00, 23400.00, 0.00, '2026-04-07 09:06:03');

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `profile_id` int NOT NULL,
  `public_id` varchar(20) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`profile_id`, `public_id`, `first_name`, `middle_name`, `last_name`, `date_of_birth`, `gender`, `civil_status`, `contact_number`, `email`, `emergency_contact_name`, `emergency_contact_phone`, `date_hired`, `created_at`) VALUES
(1, 'EMP-2026-0001', 'Juan', NULL, 'Dela Cruz', '1990-01-01', NULL, NULL, '09171234567', 'juan.teller@armatech.com', NULL, NULL, NULL, '2026-02-16 13:13:53'),
(2, 'CUS-2026-3578', 'Ralp Anjelo', 'Mendoza', 'Armario', '2004-10-27', NULL, NULL, '09971376355', NULL, NULL, NULL, NULL, '2026-02-16 16:57:39'),
(3, 'CUS-2026-3872', 'Vaughn Angelo', '', 'Maco', '2003-08-16', NULL, NULL, '09235728395', 'ralparmario202@gmail.com', NULL, NULL, NULL, '2026-02-17 10:22:09'),
(4, NULL, 'System', NULL, 'Administrator', '2000-01-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-17 18:02:26'),
(7, 'EMP-2026-0002', 'Andrea Faith', 'Rivera', 'Asierto', '2004-09-04', 'Female', 'Single', '0975823512', 'aseirtoandrea@gmail.com', 'Nina Manzanero', '9971376355', '2026-02-18', '2026-02-17 20:19:15'),
(8, 'CUS-2026-8204', 'Niña', NULL, 'Manzanero', '2005-03-20', 'Female', 'Single', '09934586544', 'nina@gmail.com', NULL, NULL, NULL, '2026-02-18 10:30:33'),
(9, 'CUS-2026-1497', 'Noel', '', 'Orano', '2001-10-10', 'Male', 'Single', '09789471283', 'ralparmario101@gmail.com', NULL, NULL, NULL, '2026-03-13 12:58:57'),
(10, 'CUS-2026-4467', 'Jexabelle', '', 'Jandusay', '2005-10-05', 'Male', 'Single', '09385701234', 'jandusay@gmail.com', NULL, NULL, NULL, '2026-03-14 04:18:32'),
(11, 'CUS-2026-3410', 'Ian', '', 'Payawal', '2004-10-20', 'Male', 'Single', '09235728395', 'xruncelgatchi@gmail.com', NULL, NULL, NULL, '2026-03-14 05:00:08'),
(12, 'EMP-2026-0003', 'Sumi', '', 'Ly', '2003-04-28', 'Female', 'Single', '9971376355', 'sumily@gmail.com', 'ralp anjelo ARMARIO', '9971376355', '2026-04-06', '2026-04-06 14:57:10'),
(13, 'CUS-2026-3256', 'Jerson', '', 'Sagun', '2004-02-02', 'Male', 'Single', '09971376355', 'jerson@gmail.com', NULL, NULL, NULL, '2026-04-07 00:55:41');

-- --------------------------------------------------------

--
-- Table structure for table `shop_items`
--

CREATE TABLE `shop_items` (
  `shop_id` int NOT NULL,
  `transaction_id` int NOT NULL,
  `item_id` int NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `shop_status` enum('available','reserved','sold') DEFAULT 'available',
  `date_published` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `shop_items`
--

INSERT INTO `shop_items` (`shop_id`, `transaction_id`, `item_id`, `selling_price`, `shop_status`, `date_published`) VALUES
(1, 9, 7, 18000.00, 'sold', '2026-02-20 02:46:44'),
(6, 10, 8, 10000.00, 'sold', '2026-02-26 05:12:42'),
(8, 12, 10, 30000.00, 'sold', '2026-03-13 21:46:09'),
(9, 7, 5, 25000.00, 'reserved', '2026-03-14 05:24:58'),
(10, 13, 11, 25000.00, 'sold', '2026-03-14 13:19:21'),
(11, 6, 4, 5000.00, 'available', '2026-04-07 09:11:47'),
(12, 14, 12, 30000.00, 'available', '2026-04-11 17:15:16'),
(13, 22, 20, 27000.00, 'available', '2026-04-11 17:15:25');

-- --------------------------------------------------------

--
-- Table structure for table `shop_reservations`
--

CREATE TABLE `shop_reservations` (
  `reservation_id` int NOT NULL,
  `shop_id` int NOT NULL,
  `customer_profile_id` int NOT NULL,
  `reservation_amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `receipt_image` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','claimed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `shop_reservations`
--

INSERT INTO `shop_reservations` (`reservation_id`, `shop_id`, `customer_profile_id`, `reservation_amount`, `reference_number`, `receipt_image`, `status`, `created_at`) VALUES
(1, 1, 3, 1800.00, '2319581908231', 'receipt_1771532294_3.jpg', 'claimed', '2026-02-20 04:18:14'),
(2, 2, 3, 2500.00, '4123561312', 'receipt_1771533202_3.jpg', 'rejected', '2026-02-20 04:33:22'),
(3, 6, 3, 1000.00, '321123464534231', 'receipt_1772054005_3.png', 'claimed', '2026-02-26 05:13:25'),
(4, 8, 3, 3000.00, '03821908129035123', 'receipt_1773409732_3.png', 'claimed', '2026-03-13 21:48:52'),
(5, 9, 9, 2500.00, '03821908129035123', 'receipt_1773437110_9.jpg', 'approved', '2026-03-14 05:25:10'),
(6, 10, 9, 2500.00, '38921085908129384', 'receipt_1773465747_9.jpg', 'claimed', '2026-03-14 13:22:27'),
(7, 11, 13, 500.00, '03821908129023', 'receipt_1775524434_13.jpg', 'rejected', '2026-04-07 09:13:54');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int NOT NULL,
  `pt_number` varchar(20) NOT NULL,
  `customer_id` int NOT NULL,
  `teller_id` int NOT NULL,
  `date_pawned` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_renewed_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `maturity_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `principal_amount` decimal(10,2) NOT NULL,
  `interest_rate` decimal(5,2) DEFAULT '3.00',
  `status` enum('active','redeemed','expired','auctioned') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `pt_number`, `customer_id`, `teller_id`, `date_pawned`, `last_renewed_date`, `maturity_date`, `expiry_date`, `principal_amount`, `interest_rate`, `status`) VALUES
(3, 'PT-2026-48838', 2, 1, '2026-02-17 03:28:19', '2026-03-14 03:31:00', '2026-04-17', '2026-05-17', 2500.00, 3.00, 'redeemed'),
(4, 'PT-2026-99645', 2, 1, '2026-02-17 06:20:16', '2026-03-14 03:31:00', '2026-07-17', '2026-08-17', 3000.00, 3.00, 'redeemed'),
(5, 'PT-2026-66447', 3, 1, '2026-02-17 18:25:49', '2026-03-14 03:31:00', '2026-08-17', '2026-09-16', 6450.00, 3.00, 'redeemed'),
(6, 'PT-2026-58935', 8, 7, '2026-02-18 18:33:42', '2026-03-14 03:31:00', '2026-07-18', '2026-01-18', 3000.00, 3.00, 'expired'),
(7, 'PT-2026-35351', 8, 7, '2026-02-19 05:27:26', '2026-03-14 03:31:00', '2026-08-19', '2026-01-18', 21000.00, 3.00, 'expired'),
(8, 'PT-2026-44310', 3, 7, '2025-11-19 05:29:18', '2026-03-14 03:31:00', '2026-11-10', '2026-04-18', 28000.00, 3.00, 'redeemed'),
(9, 'PT-2026-97319', 8, 7, '2026-02-19 16:13:10', '2026-03-14 03:31:00', '2026-07-19', '2026-01-19', 15000.00, 3.00, 'auctioned'),
(10, 'PT-2026-28518', 3, 7, '2026-02-26 05:07:16', '2026-03-14 03:31:00', '2026-07-26', '2026-01-25', 9000.00, 3.00, 'auctioned'),
(11, 'PT-2026-57190', 9, 7, '2026-03-13 21:07:52', '2026-03-14 03:31:00', '2026-06-13', '2026-07-13', 12000.00, 3.00, 'redeemed'),
(12, 'PT-2026-49577', 9, 7, '2026-03-13 21:37:52', '2026-03-14 03:31:00', '2026-02-20', '2026-05-12', 25000.00, 3.00, 'auctioned'),
(13, 'PT-2026-41606', 9, 7, '2025-10-17 22:11:39', '2026-02-14 03:35:00', '2026-03-01', '2026-02-17', 1500.00, 3.00, 'auctioned'),
(14, 'PT-2026-25088', 9, 7, '2026-03-13 04:16:39', '2026-03-13 04:16:39', '2026-03-17', '2026-04-10', 28000.00, 3.00, 'expired'),
(15, 'PT-2026-77415', 9, 7, '2026-03-14 07:28:32', '2026-03-14 07:28:32', '2026-04-15', '2026-07-14', 15000.00, 3.00, 'active'),
(16, 'PT-2026-15931', 10, 7, '2026-03-14 12:37:38', '2026-03-14 12:37:38', '2026-05-14', '2026-06-13', 12000.00, 3.00, 'redeemed'),
(17, 'PT-2026-84212', 11, 7, '2026-03-14 13:03:15', '2026-03-14 13:10:17', '2026-04-13', '2026-07-12', 10000.00, 3.00, 'redeemed'),
(18, 'PT-2026-15806', 11, 12, '2026-04-07 02:17:01', '2026-04-07 02:17:02', '2026-04-16', '2026-08-06', 20000.00, 3.00, 'active'),
(19, 'PT-2026-67386', 11, 12, '2026-04-07 04:12:57', '2026-04-07 04:12:57', '2026-05-07', '2026-06-06', 12000.00, 3.00, 'active'),
(20, 'PT-2026-70559', 10, 12, '2026-04-07 05:52:17', '2026-04-07 05:52:17', '2026-10-07', '2026-11-06', 23500.00, 3.00, 'redeemed'),
(21, 'PT-2026-30589', 13, 7, '2026-04-07 08:59:42', '2026-04-07 08:59:42', '2026-07-07', '2026-08-06', 23400.00, 3.00, 'redeemed'),
(22, 'PT-2026-26412', 3, 12, '2026-04-11 15:40:32', '2026-04-11 15:40:32', '2026-04-13', '2026-04-10', 25999.99, 3.00, 'expired');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `profile_id` (`profile_id`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `profile_id` (`profile_id`);

--
-- Indexes for table `device_cache`
--
ALTER TABLE `device_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_search` (`category`,`search_query`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `teller_id` (`teller_id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `public_id` (`public_id`);

--
-- Indexes for table `shop_items`
--
ALTER TABLE `shop_items`
  ADD PRIMARY KEY (`shop_id`);

--
-- Indexes for table `shop_reservations`
--
ALTER TABLE `shop_reservations`
  ADD PRIMARY KEY (`reservation_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD UNIQUE KEY `pt_number` (`pt_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `teller_id` (`teller_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `account_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `address_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `device_cache`
--
ALTER TABLE `device_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `profile_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shop_items`
--
ALTER TABLE `shop_items`
  MODIFY `shop_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shop_reservations`
--
ALTER TABLE `shop_reservations`
  MODIFY `reservation_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`) ON DELETE CASCADE;

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`) ON DELETE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`teller_id`) REFERENCES `accounts` (`account_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `profiles` (`profile_id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`teller_id`) REFERENCES `accounts` (`account_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
