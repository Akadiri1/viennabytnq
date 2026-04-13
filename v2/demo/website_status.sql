-- phpMyAdmin SQL Dump
-- version 4.6.6deb5ubuntu0.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 20, 2022 at 09:06 AM
-- Server version: 5.7.35-0ubuntu0.18.04.2
-- PHP Version: 7.2.34-24+ubuntu18.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mckodevc_demo`
--

-- --------------------------------------------------------

--
-- Table structure for table `website_status`
--

CREATE TABLE `website_status` (
  `id` int(10) UNSIGNED NOT NULL,
  `hash_id` char(20) NOT NULL,
  `color` char(11) NOT NULL,
  `status` char(4) NOT NULL,
  `coming_status` int(11) NOT NULL,
  `coming_date` date NOT NULL DEFAULT '2020-01-02',
  `maintenance_status` int(11) NOT NULL,
  `visibility` char(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `website_status`
--

INSERT INTO `website_status` (`id`, `hash_id`, `color`, `status`, `coming_status`, `coming_date`, `maintenance_status`, `visibility`) VALUES
(3, 'y7633gusdsd', '21618C', 'demo', 0, '2021-07-31', 0, 'show');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `website_status`
--
ALTER TABLE `website_status`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `website_status`
--
ALTER TABLE `website_status`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
