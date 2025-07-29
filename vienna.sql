-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 29, 2025 at 10:42 AM
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
-- Database: `vienna`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `hash_id` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `twitter_link` varchar(255) DEFAULT NULL,
  `linkedin_link` varchar(255) DEFAULT NULL,
  `image_2` varchar(225) DEFAULT NULL,
  `image_1` varchar(255) DEFAULT NULL,
  `time_created` time NOT NULL,
  `date_created` date NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_logout` datetime DEFAULT NULL,
  `login_status` varchar(255) DEFAULT NULL,
  `level` varchar(255) DEFAULT NULL,
  `verification` varchar(255) DEFAULT NULL,
  `profile_status` varchar(255) DEFAULT NULL,
  `user_status` varchar(255) DEFAULT NULL,
  `defaulted` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `firstname`, `lastname`, `email`, `hash`, `hash_id`, `position`, `phone_number`, `facebook_link`, `twitter_link`, `linkedin_link`, `image_2`, `image_1`, `time_created`, `date_created`, `last_login`, `last_logout`, `login_status`, `level`, `verification`, `profile_status`, `user_status`, `defaulted`) VALUES
(7, 'Banji', 'Akole', 'banjimayowa@gmail.com', '$2y$10$nfIX.S/vu469XEOOr4nrjupfWxF2tHfUwpX7S0sH1eyaIY8tZivs.', 'j90819542aBn72i', '555666777888999000', NULL, NULL, NULL, NULL, 'b12b9681-2bdc-4237-bfa1-51db8b8c2d81', '1545335942mailIMG-20181022-WA0003.jpg', '14:25:12', '2018-02-28', '2020-03-15 17:31:46', '2019-01-01 19:53:53', 'Logged In', 'MASTER', '1', NULL, '1', NULL),
(35, 'Abayomi', 'Sarumi', 'aatsarumi@gmail.com', '$2y$10$R0xO.ooeAZX2AHtFC7NZkeiR9Yc3rWGUqtlKWPhwTQex/XMEw1yC2', '96b78075a0oim85ay', '555666777888999000', '8037455296', NULL, NULL, NULL, NULL, NULL, '20:37:18', '2019-08-04', '2019-08-04 21:01:35', NULL, 'Logged In', 'MASTER', '1', NULL, '1', NULL),
(36, 'Mckodev', 'Project Manager', 'pm@mckodev', '$2y$10$5qbZZ4cTe3KpAp7dAnkjwOpnGvFLHEGi0kHUi9xehYx1AzNJUJWEW', '15847344529ve54312633d9mkco', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20:00:52', '2020-03-20', NULL, NULL, NULL, '3', '1', NULL, '1', NULL),
(41, 'Tolu', 'Akintayo', 'tolubama@gmail.com', '$2y$10$zbI3i.Z07nOK/8NPk63gaeV6t1i3n0s8nqealpGQbnfV4GjzSV3iu', '159647435833784tl257u83o', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '17:05:58', '2020-08-03', NULL, NULL, NULL, 'MASTER', '1', NULL, '1', NULL),
(47, 'Ayoola', 'Hamed', 'hamedayoola@yahoo.com', '$2y$10$1lbxsffMzGQbRvfpk4DuNeqaTbgkxrrHxkFxs4Q77XV0AQpfWwNMe', '16583380979o39430045a7yola', '555666777888999000', NULL, NULL, NULL, NULL, NULL, NULL, '17:28:17', '2022-07-20', NULL, NULL, NULL, 'MASTER', '1', NULL, '1', NULL),
(49, 'Abiola', 'Palmer', 'abiolaoluwatosinpalmer@yahoo.com', '$2y$10$eQg576Wor3xcgRP0Hq6SielV9J7w0.BaWAsNOwj1.EgnII/nAobmK', '170180673747979o2baa58li89', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20:05:37', '2023-12-05', NULL, NULL, NULL, '3', '1', NULL, '1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_auth`
--

DROP TABLE IF EXISTS `admin_auth`;
CREATE TABLE IF NOT EXISTS `admin_auth` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `auth` varchar(225) DEFAULT NULL,
  `created_by` varchar(225) DEFAULT NULL,
  `used_by` varchar(225) DEFAULT NULL,
  `date_created` date DEFAULT NULL,
  `time_created` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin_auth`
--

INSERT INTO `admin_auth` (`id`, `auth`, `created_by`, `used_by`, `date_created`, `time_created`) VALUES
(39, '191138', '1565905740k783la6e402o033', NULL, '2020-01-08', '10:33:09'),
(40, '446358', '1565905740k783la6e402o033', NULL, '2020-01-08', '17:32:19'),
(41, '986659', 'j90819542aBn72i', '15820373186h5mie15l1a6c3274', '2020-02-07', '15:17:28'),
(42, '729912', 'j90819542aBn72i', '1582040299142aoai8478bym978', '2020-02-18', '13:59:17'),
(43, '798371', 'j90819542aBn72i', '15847344529ve54312633d9mkco', '2020-03-20', '19:59:51'),
(44, '217393', 'j90819542aBn72i', '15878996009ey86a3d9on55t948u', '2020-04-26', '11:09:57'),
(45, '286197', 'j90819542aBn72i', '1587900415968505p5m273', '2020-04-26', '11:11:22'),
(46, '619362', 'j90819542aBn72i', NULL, '2020-05-24', '16:48:43'),
(47, '883224', 'j90819542aBn72i', NULL, '2020-05-24', '17:25:37'),
(48, '452916', 'j90819542aBn72i', '1590421922e4o782692adg4n09ur', '2020-05-25', '15:38:29'),
(49, '127052', 'j90819542aBn72i', '1590440727e51o1aay3if1u6d75lnw-e0i0mi', '2020-05-25', '16:53:50'),
(50, '847282', 'j90819542aBn72i', NULL, '2020-05-25', '22:42:13'),
(51, '632590', 'j90819542aBn72i', '1590537355141l896bl585e1o', '2020-05-26', '23:48:14'),
(52, '134052', 'j90819542aBn72i', NULL, '2020-05-26', '23:48:48'),
(53, '618225', 'j90819542aBn72i', NULL, '2020-08-03', '17:05:19'),
(54, '458506', 'j90819542aBn72i', '159647435833784tl257u83o', '2020-08-03', '17:05:20'),
(55, '871811', 'j90819542aBn72i', '1602674515ew9j215a2o3l338e3', '2020-10-14', '10:59:15'),
(56, '475618', '159647435833784tl257u83o', NULL, '2022-06-30', '14:48:21'),
(57, '209805', '159647435833784tl257u83o', '16571158232a9u408651m3di2we', '2022-07-06', '13:54:54'),
(58, '418794', 'j90819542aBn72i', '1657700209541al14o6019o7ya', '2022-07-13', '06:40:34'),
(59, '564085', 'j90819542aBn72i', '16577052421o402o2aa8l6472y', '2022-07-13', '09:20:09'),
(60, '679779', '16577052421o402o2aa8l6472y', NULL, '2022-07-14', '20:05:44'),
(61, '368980', '16577052421o402o2aa8l6472y', NULL, '2022-07-15', '14:50:43'),
(62, '529983', 'j90819542aBn72i', '16583380979o39430045a7yola', '2022-07-20', '17:22:26'),
(63, '725171', '16577052421o402o2aa8l6472y', '16583378814515oo6639yl4aa', '2022-07-20', '17:23:21'),
(64, '884435', '16583380979o39430045a7yola', '16597132191g41lan9u9259eie21q', '2022-08-05', '15:17:41'),
(65, '532673', '16583380979o39430045a7yola', NULL, '2022-08-05', '15:27:18'),
(66, '219643', '16583380979o39430045a7yola', '1675553064f4i5247t12a2a09', '2023-02-04', '23:18:58'),
(67, '867909', '16583380979o39430045a7yola', '170180673747979o2baa58li89', '2023-12-05', '20:03:56');

-- --------------------------------------------------------

--
-- Table structure for table `home_video`
--

DROP TABLE IF EXISTS `home_video`;
CREATE TABLE IF NOT EXISTS `home_video` (
  `id` int NOT NULL AUTO_INCREMENT,
  `video_url` varchar(255) NOT NULL,
  `video_text` varchar(255) NOT NULL,
  `visibility` varchar(20) NOT NULL DEFAULT 'show',
  `date_created` date NOT NULL,
  `time_created` time NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `read_website_info`
--

DROP TABLE IF EXISTS `read_website_info`;
CREATE TABLE IF NOT EXISTS `read_website_info` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `hash_id` varchar(225) NOT NULL,
  `input_name` varchar(225) NOT NULL,
  `input_email` varchar(100) NOT NULL,
  `input_email_2` varchar(100) DEFAULT NULL,
  `input_email_from` varchar(225) DEFAULT NULL,
  `input_email_smtp_host` varchar(225) DEFAULT NULL,
  `input_email_smtp_secure_type` varchar(225) DEFAULT NULL,
  `input_email_smtp_port` varchar(225) DEFAULT NULL,
  `input_email_password` varchar(225) DEFAULT NULL,
  `input_phone_number` char(50) NOT NULL,
  `input_phone_number_1` char(50) DEFAULT NULL,
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
  `time_created` time NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `read_website_info`
--

INSERT INTO `read_website_info` (`id`, `hash_id`, `input_name`, `input_email`, `input_email_2`, `input_email_from`, `input_email_smtp_host`, `input_email_smtp_secure_type`, `input_email_smtp_port`, `input_email_password`, `input_phone_number`, `input_phone_number_1`, `input_address`, `input_linkedin`, `input_facebook`, `input_instagram`, `input_behance`, `input_dribbble`, `input_twitter`, `image_1`, `text_description`, `input_country`, `visibility`, `date_created`, `time_created`) VALUES
(1, '345yjhgfse3456yhbgvfc', 'Tutto Mondo Care', 'info@tuttomondocare.com', 'info@tuttomondocare.com', 'info@tuttomondocare.com', 'eight.qservers.net', 'ssl', '465', 'Abiola@2021', '02030894674', '+44(730) 756-2773', '55 John walsh tower,Montague Road, London,E11, 3ES', 'http://linkedin.com/in/', 'http://facebook.com/', 'http://instagram.com/', 'https://behance.net/', 'https://dibbble.com', 'http://twitter.com/', 'https://vipresa.mckodev.com.ng/uploads/166226262321711filename.jpg', '<p>Tutto Mondo &nbsp;is a leading care provider with over 10 years of experience. We provide tailored support to all clients and offering support to individual with long term conditions to manage their own health within the community. Our carers and nurses Deliver outstanding performance to all clients for a great result.</p>', 'United Kingdom', 'show', '2021-06-20', '11:33:49');

-- --------------------------------------------------------

--
-- Table structure for table `settings_config`
--

DROP TABLE IF EXISTS `settings_config`;
CREATE TABLE IF NOT EXISTS `settings_config` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `hash_id` varchar(225) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `input_name` varchar(225) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `text_body` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `time_created` time NOT NULL,
  `date_created` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_created` (`date_created`),
  UNIQUE KEY `hash_id_unique` (`hash_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings_config`
--

INSERT INTO `settings_config` (`id`, `hash_id`, `input_name`, `text_body`, `time_created`, `date_created`) VALUES
(1, 'unique_hash_id_37', 'Tutto Mondo', '<p>Tutto Mondo Care is a leading care provider with over 10 years experience. We provide highly trained carers to clients. Our pride lies in the close collaboration we maintain with our clients, ensuring the provision of personalised assistance within the familiarity of their homes. Our fully qualified and DBS-checked staff are committed to your peace of mind, we treat our staff right, carers who enjoy working for us provide the best care.</p><p>Our mission is to offer a professional, amicable service that fosters independence and enhances the best quality of life for those in need of care and support within their homes. We highly value our professional approach to establishing strong partnerships with general practitioners, social services, hospitals, and voluntary organisations and working closely with our client’s wider network: family, friends, doctors, therapists.</p><p>Providing high-level safe care with Dignity and Respect, making sure we maintain high-quality care through staff training, user feedback and quality control.</p><p>Our &nbsp;recruitment process involves formal interviews, thorough professional and personal reference checks, DBS and Protection of Vulnerable Adults screenings. We also verify existing training and certifications. Whatever your health needs are Tutto Mondo Care will assist you to improve your quality of life.</p>', '06:11:29', '2025-03-06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hash_id` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `phone_number` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `hash` varchar(225) NOT NULL,
  `visibility` char(4) NOT NULL,
  `user_status` int DEFAULT NULL,
  `time_created` time NOT NULL,
  `date_created` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `hash_id`, `firstname`, `lastname`, `phone_number`, `email`, `hash`, `visibility`, `user_status`, `time_created`, `date_created`) VALUES
(6, '16572939882111umtw91e0n62liilio1ha', 'Oluwatimilehin', 'Oladipupo', '08107777777', 'timiochukwu0@gmail.com', '$2y$10$.ZStAjY9qdRrvxxWoNGBj.8tq8tYlfLqC6e1dC5xV7bUfZQnjAPmi', 'show', 2, '15:26:28', '2022-07-08'),
(11, '16574736207454e67tt394s2', 'Test', 'Account', '08108139993', 'earltbam@gmail.com', '$2y$10$SmvseGbFkBxrJE4IoV0VXOj95mxPYEvZFBGTSp3z7IDhcSO2WpT5m', 'show', 1, '17:20:20', '2022-07-10'),
(12, '1657493805o3l67767e95a6k4', 'Akole', 'Banji', '08168745591', 'banjimayowa1@gmail.com', '$2y$10$jpzqCejOR2aNSIfxJ9H3Lex5Hlrzr5JFALuvHoGWyXaSsHNaUPAmy', 'show', 2, '22:56:45', '2022-07-10'),
(13, '1660671339b59na671705i2j5', 'Banji', 'Akole', '08168785591', 'banjimayowa@gmail.com', '$2y$10$q.M/pLB7.RVNA1s8sfJsNuev4CuS0aorvveECgNLYcYhnJjViduL.', 'show', 2, '17:35:39', '2022-08-16'),
(14, '16619770486o732070715yaaol', 'Ayoola', 'Hamed', '07307562773', 'vicky200904@yahoo.com', '$2y$10$aVM6GLlLH/.ldSoRVlH.6.DXdiI5d0vYcOu7lsHgIWZf.ZoldKTcO', 'show', 1, '20:17:28', '2022-08-31'),
(15, '1744872272c136l595i8ea208', 'Alice', 'John', 'John', 'juvpiapr@formtest.guru', '$2y$10$bMvxeAOPEU1cH.VVR2F1Pe1EaTwl67axeEL3kuRwl1y7CK5RIOv3C', 'show', NULL, '06:44:32', '2025-04-17');

-- --------------------------------------------------------

--
-- Table structure for table `verify`
--

DROP TABLE IF EXISTS `verify`;
CREATE TABLE IF NOT EXISTS `verify` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hash_id` varchar(225) NOT NULL,
  `token` varchar(225) NOT NULL,
  `token_s` varchar(225) NOT NULL,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `verify`
--

INSERT INTO `verify` (`id`, `hash_id`, `token`, `token_s`, `email`) VALUES
(1, '1657291851ks171693a4a206', '1657291851_c1skab4nahten6tr8v27151Gid4e606viee14o9ri351Mokmr89oVn421af7Dos916d0aac', '1', 'test1@services.com'),
(2, '16572932632l204ii6luhtwie8495oman4', '1657293263_c47ehnl829orD53a50tionmaoo6nd446i3ks9vrtfmiv27Vebh20192i0iiMon3euec7Gltd5w2a9ae6r', '1', 'test@services.com'),
(3, '1657293421i7o3aheimn4lu2w6ilt19188', '1657293421_ratVf4odoe3e6ei22iDaeii8t1nr3d41ublMw92k718tnmmv1hoo4v6h72c79n3a5arlin9es0c6o4iG', '1', 'timiochukwu0@gmail.com'),
(4, '1744872272c136l595i8ea208', '1744872272_h8ovt4do4e597to32fid6ve4895ra7re87cGa1ec04irlkaneDi7m5i2sM5c3n221V8oab6n', '1', 'juvpiapr@formtest.guru');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
