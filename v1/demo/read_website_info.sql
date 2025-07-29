-- phpMyAdmin SQL Dump
-- version 4.6.6deb5ubuntu0.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 11, 2022 at 12:20 PM
-- Server version: 5.7.38-0ubuntu0.18.04.1
-- PHP Version: 7.1.33-34+ubuntu18.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mckodevc_demoFawaz`
--

-- --------------------------------------------------------

--
-- Table structure for table `read_website_info`
--

CREATE TABLE `read_website_info` (
  `id` int(10) UNSIGNED NOT NULL,
  `hash_id` varchar(225) NOT NULL,
  `input_name` varchar(225) NOT NULL,
  `input_email` varchar(100) NOT NULL,
  `input_phone_number` char(50) NOT NULL,
  `input_address` varchar(225) NOT NULL,
  `input_linkedin` varchar(225) NOT NULL,
  `input_facebook` varchar(225) NOT NULL,
  `input_instagram` varchar(225) NOT NULL,
  `input_behance` varchar(225) DEFAULT NULL,
  `input_dribbble` varchar(225) DEFAULT NULL,
  `input_twitter` varchar(225) NOT NULL,
  `image_1` varchar(225) NOT NULL,
  `text_description` text NOT NULL,
  `input_country` varchar(225) DEFAULT NULL,
  `visibility` varchar(20) NOT NULL,
  `date_created` date NOT NULL,
  `time_created` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `read_website_info`
--

INSERT INTO `read_website_info` (`id`, `hash_id`, `input_name`, `input_email`, `input_phone_number`, `input_address`, `input_linkedin`, `input_facebook`, `input_instagram`, `input_behance`, `input_dribbble`, `input_twitter`, `image_1`, `text_description`, `input_country`, `visibility`, `date_created`, `time_created`) VALUES
(1, '345yjhgfse3456yhbgvfc', 'Fawaz Adelaja', 'adelajaarc148304@futa.edu.ng', '+2348029276682', '20 Demurin Street, Ketu, Lagos.', 'http://linkedin.com/in/fawaz-adelaja-b7444a95/', 'http://facebook.com/lilwhiezz', 'http://instagram.com/lilwhiezz/', 'https://behance.net/adelaja-fawaz', 'https://dibbble.com', 'http://twitter.com/lilwhiezz', 'https://fawazadelaja.com/uploads/163913811515369filename.jpg', '<p>I\'m a brilliant, smart and very detailed architectural designer with an eye for simple yet classic and expressive forms. I strongly believe in functionality, aesthetics, simplicity, Afrocentricity, Cubism and Expressionism.<br><br>I studied at the Federal University of technology Akure (FUTA) where I majored in architecture, developed different approach to architectural design, learnt construction management &amp; building information modelling (BIM), developed architectural visualization &amp; blogging skills amongst several other important industry skills and graduated top of my class with a second class (4.47/5) upper honors in 2019.<br><br>In a future that depends on innovation to strive and achieve better success, I see myself as a creative designer with a distinct mind of gorgeous chaos and a passion for modernism, African cultural heritage and a fuse of both in my architecture.</p>', 'Nigeria', 'show', '2021-06-20', '11:33:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `read_website_info`
--
ALTER TABLE `read_website_info`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `read_website_info`
--
ALTER TABLE `read_website_info`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
