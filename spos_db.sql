-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 18, 2026 at 08:41 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spos_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `user_id`, `name`) VALUES
(4, 1, 'Sud-an'),
(5, 1, 'Sinabaw'),
(7, 1, 'Rice'),
(8, 1, 'Soft Drinks'),
(9, 1, 'Coffee');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `service_type_id` int DEFAULT '1',
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_id_type` varchar(50) DEFAULT NULL,
  `customer_id_number` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT '0.00',
  `vat_amount` decimal(10,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `discount_type` varchar(50) DEFAULT NULL,
  `payment_method` varchar(20) NOT NULL,
  `amount_tendered` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `service_type_id` (`service_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `transaction_number`, `reference_number`, `service_type_id`, `customer_name`, `customer_id_type`, `customer_id_number`, `total_amount`, `subtotal`, `vat_amount`, `discount_amount`, `discount_type`, `payment_method`, `amount_tendered`, `change_amount`, `created_at`) VALUES
(1, 1, 'TRX-20260218-0001', '', 1, 'Keanu G.', 'PWD ID', 'Nilakwan', 483.04, 597.00, 0.00, 50.00, 'PWD', 'cash', 500.00, 16.96, '2026-02-18 07:11:34'),
(2, 1, 'TRX-20260218-0002', '', 1, 'Teny Gulang', 'Senior ID', '12121243434242543', 56.43, 79.00, 0.00, 14.11, 'Senior', 'cash', 100.00, 43.57, '2026-02-18 07:17:56'),
(3, 1, 'TRX-20260218-0003', '121212121435465778', 1, 'Boe Tha', 'PWD ID', '12-000398-022', 42.14, 59.00, 0.00, 10.54, 'PWD', 'GCash', 42.14, 0.00, '2026-02-18 07:22:05'),
(4, 1, 'TRX-20260218-0004', '', 1, NULL, NULL, NULL, 144.00, 144.00, 15.43, 0.00, NULL, 'cash', 150.00, 6.00, '2026-02-18 08:05:34'),
(5, 1, 'TRX-20260218-0005', '', 1, NULL, NULL, NULL, 264.00, 264.00, 28.29, 0.00, NULL, 'cash', 270.00, 6.00, '2026-02-18 08:13:20'),
(6, 1, 'TRX-20260218-0006', '', 1, NULL, NULL, NULL, 324.00, 324.00, 34.71, 0.00, NULL, 'cash', 500.00, 176.00, '2026-02-18 08:17:56');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `variant_id` int DEFAULT NULL,
  `variant_name` varchar(50) DEFAULT NULL,
  `quantity` int NOT NULL,
  `price_at_sale` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `variant_id`, `variant_name`, `quantity`, `price_at_sale`) VALUES
(1, 1, 6, 'Adobo', 14, 'Chicken (Solo)', 1, 79.00),
(2, 1, 9, 'Rice', NULL, NULL, 4, 30.00),
(3, 1, 17, 'Tinolang Manok', 37, 'Solo', 1, 100.00),
(4, 1, 12, 'Coke', 24, 'Regular', 4, 35.00),
(5, 1, 2, 'Calamares', 10, 'Solo', 1, 79.00),
(6, 1, 4, 'Sweet & Sour', 7, 'Chicken (Solo)', 1, 79.00),
(7, 2, 7, 'Chicken Curry', NULL, NULL, 1, 79.00),
(8, 3, 14, 'Americano', NULL, NULL, 1, 59.00),
(9, 4, 6, 'Adobo', 14, 'Chicken (Solo)', 1, 79.00),
(10, 4, 9, 'Rice', NULL, NULL, 1, 30.00),
(11, 4, 12, 'Coke', 24, 'Regular', 1, 35.00),
(12, 5, 2, 'Calamares', 11, 'For Sharing', 1, 169.00),
(13, 5, 9, 'Rice', NULL, NULL, 2, 30.00),
(14, 5, 12, 'Coke', 24, 'Regular', 1, 35.00),
(15, 6, 3, 'Bulalo', 12, 'Solo', 1, 150.00),
(16, 6, 9, 'Rice', NULL, NULL, 2, 30.00),
(17, 6, 12, 'Coke', 24, 'Regular', 1, 35.00),
(18, 6, 2, 'Calamares', 10, 'Solo', 1, 79.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `has_variants` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `product_code` (`product_code`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `user_id`, `category_id`, `name`, `product_code`, `image`, `price`, `has_variants`, `created_at`) VALUES
(2, 1, 4, 'Calamares', '0000001', 'uploads/69955d9de106b.jpg', 0.00, 1, '2026-02-18 06:35:09'),
(3, 1, 5, 'Bulalo', '0000002', 'uploads/69955e0e2d355.jpg', 0.00, 1, '2026-02-18 06:37:02'),
(4, 1, 4, 'Sweet & Sour', '0000003', 'uploads/69955e9361b0c.jpg', 0.00, 1, '2026-02-18 06:39:15'),
(6, 1, 4, 'Adobo', '0000004', 'uploads/6995616fb80e8.jpg', 0.00, 1, '2026-02-18 06:51:27'),
(7, 1, 4, 'Chicken Curry', '0000005', 'uploads/69956194a7a81.jpg', 79.00, 0, '2026-02-18 06:52:04'),
(8, 1, 4, 'Lumpia', '0000006', 'uploads/699561dbc978d.jpg', 0.00, 1, '2026-02-18 06:53:15'),
(9, 1, 7, 'Rice', '0000007', 'uploads/699562bcdb586.webp', 30.00, 0, '2026-02-18 06:57:00'),
(10, 1, 8, 'Mountain Dew', '0000008', 'uploads/6995693dd6ab4.jpg', 0.00, 1, '2026-02-18 06:58:10'),
(11, 1, 8, 'Sprite', '0000009', 'uploads/69956328e2874.png', 0.00, 1, '2026-02-18 06:58:48'),
(12, 1, 8, 'Coke', '0000010', 'uploads/69956351379ac.webp', 0.00, 1, '2026-02-18 06:59:29'),
(13, 1, 9, 'Coffee Latte', '0000011', 'uploads/69956370085c9.png', 79.00, 0, '2026-02-18 07:00:00'),
(14, 1, 9, 'Americano', '0000012', 'uploads/6995638fd1eb9.jpg', 59.00, 0, '2026-02-18 07:00:31'),
(15, 1, 5, 'Pork Ribs', '0000013', 'uploads/6995645735b21.jpg', 0.00, 1, '2026-02-18 07:03:51'),
(16, 1, 5, 'Tinolang Bangus', '0000015', 'uploads/6995648307cc6.jpg', 0.00, 1, '2026-02-18 07:04:35'),
(17, 1, 5, 'Tinolang Manok', '000016', 'uploads/6995657ed4fe9.jpg', 0.00, 1, '2026-02-18 07:08:46');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE IF NOT EXISTS `product_variants` (
  `variant_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `variant_name` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`variant_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`variant_id`, `product_id`, `variant_name`, `price`) VALUES
(7, 4, 'Chicken (Solo)', 79.00),
(8, 4, 'Pork (Solo)', 99.00),
(9, 4, 'Fish (Solo)', 79.00),
(10, 2, 'Solo', 79.00),
(11, 2, 'For Sharing', 169.00),
(12, 3, 'Solo', 150.00),
(13, 3, 'For Sharing', 450.00),
(14, 6, 'Chicken (Solo)', 79.00),
(15, 6, 'Pork (Solo)', 99.00),
(16, 8, 'Chicken (6 Pcs)', 65.00),
(17, 8, 'Pork (6 Pcs)', 75.00),
(21, 11, 'Regular', 35.00),
(22, 11, 'Large', 45.00),
(23, 11, '1 Liter', 75.00),
(24, 12, 'Regular', 35.00),
(25, 12, 'Large', 45.00),
(26, 12, '1 Liter', 75.00),
(29, 16, 'Solo', 90.00),
(30, 16, 'For Sharing', 200.00),
(35, 15, 'Solo', 120.00),
(36, 15, 'For Sharing', 280.00),
(37, 17, 'Solo', 100.00),
(38, 17, 'For Sharing', 260.00),
(39, 10, 'Regular', 35.00),
(40, 10, 'Large', 45.00),
(41, 10, '1 Liter', 75.00);

-- --------------------------------------------------------

--
-- Table structure for table `service_types`
--

DROP TABLE IF EXISTS `service_types`;
CREATE TABLE IF NOT EXISTS `service_types` (
  `service_id` int NOT NULL AUTO_INCREMENT,
  `service_name` varchar(50) NOT NULL,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `service_types`
--

INSERT INTO `service_types` (`service_id`, `service_name`) VALUES
(1, 'Walk-in'),
(2, 'Take-out'),
(3, 'Delivery');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Hans', 'Matthews', 'Hansifies', '$2y$10$OueeN8U5vEC.5QO90YTzSeLZT1lHK9mNTtaXFjUVDqON2zX/03VAG', 'staff', '2026-02-18 05:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE IF NOT EXISTS `user_settings` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `vat_rate` decimal(5,2) DEFAULT '12.00',
  `store_name` varchar(100) DEFAULT 'SimplePOS',
  `store_address` varchar(255) DEFAULT 'Philippines',
  PRIMARY KEY (`setting_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`setting_id`, `user_id`, `vat_rate`, `store_name`, `store_address`) VALUES
(1, 1, 12.00, 'J3 Cafe Solutions', 'Aguayo Bldg Ext, Quezon Blvd, Sudapin, Kidapawan City');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_cat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_service` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`service_id`),
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_prod_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prod_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
