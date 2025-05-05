-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 03:13 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mindara`
--

-- --------------------------------------------------------

--
-- Table structure for table `hasil_stres`
--

CREATE TABLE `hasil_stres` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_skor` int(11) DEFAULT NULL,
  `tingkat_stres` varchar(20) DEFAULT NULL,
  `jawaban` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `tanggal` datetime DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `hasil_stres`
--

INSERT INTO `hasil_stres` (`id`, `user_id`, `total_skor`, `tingkat_stres`, `jawaban`, `tanggal`) VALUES
(1, 4, 76, NULL, '[\"3\",\"2\",\"3\",\"2\",\"3\",\"2\",\"3\",\"2\",\"2\",\"3\",\"3\",\"2\",\"2\",\"2\",\"3\",\"2\",\"3\",\"2\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"2\",\"2\",\"2\",\"2\"]', '2025-05-05 15:03:39'),
(2, 4, 76, NULL, '[\"3\",\"2\",\"3\",\"2\",\"3\",\"2\",\"3\",\"2\",\"2\",\"3\",\"3\",\"2\",\"2\",\"2\",\"3\",\"2\",\"3\",\"2\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"2\",\"2\",\"2\",\"2\"]', '2025-05-05 15:06:58'),
(3, 4, 74, NULL, '[\"3\",\"2\",\"3\",\"2\",\"3\",\"2\",\"3\",\"2\",\"2\",\"3\",\"3\",\"2\",\"2\",\"2\",\"3\",\"2\",\"3\",\"2\",\"3\",\"3\",\"0\",\"2\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"1\"]', '2025-05-05 15:07:21');

-- --------------------------------------------------------

--
-- Table structure for table `hasil_tes`
--

CREATE TABLE `hasil_tes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `q1` int(11) DEFAULT NULL,
  `q2` int(11) DEFAULT NULL,
  `q3` int(11) DEFAULT NULL,
  `q4` int(11) DEFAULT NULL,
  `q5` int(11) DEFAULT NULL,
  `q6` int(11) DEFAULT NULL,
  `q7` int(11) DEFAULT NULL,
  `q8` int(11) DEFAULT NULL,
  `q9` int(11) DEFAULT NULL,
  `q10` int(11) DEFAULT NULL,
  `q11` int(11) DEFAULT NULL,
  `q12` int(11) DEFAULT NULL,
  `q13` int(11) DEFAULT NULL,
  `q14` int(11) DEFAULT NULL,
  `q15` int(11) DEFAULT NULL,
  `q16` int(11) DEFAULT NULL,
  `q17` int(11) DEFAULT NULL,
  `q18` int(11) DEFAULT NULL,
  `q19` int(11) DEFAULT NULL,
  `q20` int(11) DEFAULT NULL,
  `q21` int(11) DEFAULT NULL,
  `q22` int(11) DEFAULT NULL,
  `q23` int(11) DEFAULT NULL,
  `q24` int(11) DEFAULT NULL,
  `q25` int(11) DEFAULT NULL,
  `q26` int(11) DEFAULT NULL,
  `q27` int(11) DEFAULT NULL,
  `q28` int(11) DEFAULT NULL,
  `q29` int(11) DEFAULT NULL,
  `q30` int(11) DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hasil_tes`
--

INSERT INTO `hasil_tes` (`id`, `user_id`, `q1`, `q2`, `q3`, `q4`, `q5`, `q6`, `q7`, `q8`, `q9`, `q10`, `q11`, `q12`, `q13`, `q14`, `q15`, `q16`, `q17`, `q18`, `q19`, `q20`, `q21`, `q22`, `q23`, `q24`, `q25`, `q26`, `q27`, `q28`, `q29`, `q30`, `total`, `created_at`) VALUES
(1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-05-05 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `notification_preferences` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `phone`, `birthdate`, `gender`, `bio`, `notification_preferences`, `profile_pic`) VALUES
(4, 'qori', 'qori11@gmail.com', '$2y$10$2aeGVaEw4D8U9lYiikt4Uu79fYs6fBAoS0ORYsSzix1PTElt62dqi', '0895338720368', '2006-06-04', 'female', 'y gt', 'none', 'uploads/profile_pics/user_4_1746445817.jpeg'),
(5, 'Mi', 'test@gmail.com', '$2y$10$sj3RQYGY17ubX8WPhvh3feVYjOywNY1Nf6EqL5QZnoJDCDdcyJp4W', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Ismatul Ilmi', 'isma.tul0702@gmail.com', '$2y$10$efeMgToaD/VOsIy6Niwe4.ImHLvl2FohLbWXoZPymvCmGq2LZG7Mu', '082165089655', '2004-02-07', 'female', 'Saya alhamdulillah', 'important', 'uploads/profile_pics/user_6_1745744410.png');

-- --------------------------------------------------------

--
-- Table structure for table `user_profile`
--

CREATE TABLE `user_profile` (
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `bio` text DEFAULT NULL,
  `preferences` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hasil_stres`
--
ALTER TABLE `hasil_stres`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hasil_tes`
--
ALTER TABLE `hasil_tes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_profile`
--
ALTER TABLE `user_profile`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hasil_stres`
--
ALTER TABLE `hasil_stres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hasil_tes`
--
ALTER TABLE `hasil_tes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_profile`
--
ALTER TABLE `user_profile`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
