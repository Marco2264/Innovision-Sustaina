-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql300.ezyro.com
-- Generation Time: May 20, 2026 at 06:57 PM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ezyro_41972952_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `claims`
--

CREATE TABLE `claims` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `buyer_name` varchar(100) NOT NULL,
  `status` varchar(50) DEFAULT 'Claimed',
  `claimed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `claims`
--

INSERT INTO `claims` (`id`, `inventory_id`, `buyer_name`, `status`, `claimed_at`) VALUES
(1, 8, 'Juan Dela Cruz', 'Pending Pickup', '2026-05-20 18:23:49'),
(2, 8, 'Juan Dela Cruz', 'Pending Pickup', '2026-05-20 18:23:56'),
(3, 11, 'Marco', 'Pending Pickup', '2026-05-20 19:24:10'),
(4, 3, 'Etherious', 'Pending Pickup', '2026-05-20 19:57:12');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `qty` varchar(50) NOT NULL,
  `bought_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `listed_on_market` tinyint(1) DEFAULT 0,
  `market_price` decimal(10,2) DEFAULT 0.00,
  `market_desc` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `seller` varchar(100) DEFAULT 'Bella Grillhouse',
  `seller_type` varchar(100) DEFAULT 'Restaurant',
  `location` varchar(100) DEFAULT 'Downtown',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `name`, `category`, `qty`, `bought_date`, `expiry_date`, `listed_on_market`, `market_price`, `market_desc`, `image_url`, `seller`, `seller_type`, `location`, `created_at`) VALUES
(8, 'Roma Salad Tomatoes', 'Vegetables', '5.0 kg', NULL, '2026-05-24', 0, '5.00', 'High quality Roma tomatoes, excess from bulk order. Great for sauces or salads.', 'https://images.unsplash.com/photo-1546470427-0d4db154ceb8?w=600&h=400&fit=crop', 'Cafe Fresh & Co', 'Cafe', 'West End (2.1 mi)', '2026-05-20 18:01:21'),
(9, 'Steak', 'Meat', '2', '2026-05-17', '2026-05-30', 1, '0.00', 'Urgent listing: listed to prevent food waste. Store refrigerated.', 'uploads/img_6a0e078b6770e.png', 'Bella Grillhouse', 'Restaurant', 'Downtown', '2026-05-20 19:12:11'),
(10, 'Fish', 'Meat', '2', '2026-05-17', '2026-05-30', 0, '0.00', NULL, 'uploads/img_6a0e091ad22a4.png', 'Bella Grillhouse', 'Restaurant', 'Downtown', '2026-05-20 19:18:50'),
(11, 'tomatoes', 'Vegetables', '2', NULL, '2026-05-30', 0, '0.10', 'asda', NULL, 'Raven Santos', 'Household', 'Downtown', '2026-05-20 19:19:30'),
(12, 'steak', 'Meat', '2', '2026-05-17', '2026-05-30', 0, '0.00', NULL, 'uploads/img_6a0e125a6124e.png', 'Bella Grillhouse', 'Restaurant', 'Downtown', '2026-05-20 19:58:18'),
(14, 'banana', 'Fruits', '6 pcs', NULL, '2026-05-23', 0, '0.00', 'Urgent listing: listed to prevent food waste. Store refrigerated.', NULL, 'Bella Grillhouse', 'Restaurant', 'Downtown', '2026-05-20 20:32:03');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `sender` varchar(255) NOT NULL,
  `receiver` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `item_id`, `sender`, `receiver`, `message`, `created_at`) VALUES
(1, 11, 'Marco', 'Raven Santos', 'hillu', '2026-05-20 19:24:16'),
(2, 11, 'Raven Santos', 'Marco', 'hahaha', '2026-05-20 19:24:25'),
(3, 11, 'Marco', 'Raven Santos', 'palddoooooooooooooooo', '2026-05-20 19:25:00'),
(4, 3, 'Etherious', 'Bella Grillhouse', 'is this still available?', '2026-05-20 19:57:24'),
(5, 11, 'Raven Santos', 'Marco', 'asdasd', '2026-05-20 19:57:36'),
(6, 11, 'Marco', 'Raven Santos', 'okay', '2026-05-20 19:58:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` varchar(100) DEFAULT 'Member',
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `full_name`, `role`, `phone`, `created_at`) VALUES
(1, 'ravensantos60@gmail.com', '$2y$10$xgKtCRW0nqoyimYI8BtU4e9RGmoZ3NDevzl2il26p3I0obQzD4.He', 'Raven Santos', 'Household', '09912345678', '2026-05-20 18:02:57'),
(2, 'marcosalarda.7@gmail.com', '$2y$10$7U73Ij3ztYvlXbfS/KRcwOLFAWmpvFxbM.W4Mx934sidxqIEqYs46', 'Etherious', 'Household', '09937535688', '2026-05-20 18:06:38'),
(3, 'ElonMusk@x.com', '$2y$10$VkhUcVEvAaXoJKbcA.7gh.8W3xeh87F4x32UhZpY37.QLVU0mX/wm', 'Marco', 'Cafe', '', '2026-05-20 19:04:55'),
(4, 'pablo@gmail.com', '$2y$10$w92ZwOOzSJ4lTaZosyRdK.BpI.EaaSyoLBL4RqXKfayRrvEbH0IzO', 'Pablo', 'Household', '', '2026-05-20 22:23:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `claims`
--
ALTER TABLE `claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
