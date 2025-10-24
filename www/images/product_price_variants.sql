-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 18, 2025 at 01:09 PM
-- Server version: 11.4.8-MariaDB
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `viennaby_viennabytnq`
--

-- --------------------------------------------------------

--
-- Table structure for table `product_price_variants`
--

CREATE TABLE `product_price_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_name` text NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_price_variants`
--

INSERT INTO `product_price_variants` (`id`, `product_id`, `variant_name`, `price`) VALUES
(7, 1, 'Long length', 150750.00),
(8, 1, 'Short length', 70500.00),
(13, 2, 'Size 4 to 12', 120500.00),
(14, 2, '12 and above', 150750.00),
(17, 3, 'Size 4 to 12', 148500.00),
(18, 3, '12 and above', 160000.00),
(21, 42, 'Size 4 to 12', 130500.00),
(22, 42, 'Size 14 and above', 150000.00),
(23, 43, 'Size 4 to 12', 200000.00),
(24, 43, 'Size 14 and above', 235000.00),
(25, 44, 'Size 4 to 12', 130000.00),
(26, 44, 'Size 14 and above', 150250.00),
(27, 45, 'Size 4 to 12', 150000.00),
(28, 45, 'Size 14 and above', 185750.00),
(29, 46, 'SIZE 8 to 12', 195250.00),
(30, 46, 'SIZE 14 and above', 225750.00),
(31, 47, 'SIZE 8 to 12', 65500.00),
(32, 47, 'SIZE 14 and above', 80750.00),
(33, 48, 'SIZE 8 to 12', 120450.00),
(34, 48, 'SIZE 14 and Above', 150750.00),
(35, 49, 'SIZE 8 to 12', 100450.00),
(36, 49, 'SIZE 14 and Above', 125500.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `product_price_variants`
--
ALTER TABLE `product_price_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `product_price_variants`
--
ALTER TABLE `product_price_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
