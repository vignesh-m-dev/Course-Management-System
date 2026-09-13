-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 25, 2025 at 03:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `23ucs105`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `course_id`) VALUES
(9, 2, 10),
(10, 2, 2),
(11, 2, 3),
(13, 3, 1),
(14, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `deal` varchar(255) DEFAULT NULL,
  `offer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `price`, `deal`, `offer`) VALUES
(1, 'Full Stack Web Development', 'Learn HTML, CSS, JavaScript, React, Node.js and MySQL from scratch.', 4999.00, '20% OFF', 'Free projects'),
(2, 'Python for Beginners', 'Start your Python journey with basics to intermediate concepts.', 2999.00, '10% OFF', 'Certificate included'),
(3, 'React JS Mastery', 'Complete guide to React JS with hooks, context, and projects.', 3999.00, '15% OFF', 'Free GitHub portfolio setup'),
(4, 'Java Programming Bootcamp', 'OOP, Collections, JDBC, and full Java fundamentals.', 3500.00, '25% OFF', 'Bonus coding questions'),
(5, 'Android App Development', 'Build Android apps using Java and Android Studio.', 4500.00, '30% OFF', '2 free app templates'),
(6, 'Data Structures & Algorithms', 'Master DSA for interviews with real practice problems.', 5000.00, '20% OFF', 'Mock interview included'),
(7, 'Machine Learning Basics', 'Intro to ML concepts, algorithms, and model building.', 6500.00, '18% OFF', 'Free datasets'),
(8, 'UI/UX Design Fundamentals', 'Learn design tools, wireframing, and UX workflow.', 2800.00, '12% OFF', 'Design templates included'),
(9, 'Node.js & Express API Development', 'Build REST APIs with Node.js, Express, and MongoDB.', 4200.00, '22% OFF', 'Free deployment guide'),
(10, 'Cybersecurity Essentials', 'Basics of cybersecurity, threats, protection mechanisms.', 3800.00, '15% OFF', 'Free security checklist');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) DEFAULT 'PAID',
  `order_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `course_id`, `total_amount`, `payment_status`, `order_date`) VALUES
(1, 1, 0, 4999.00, 'PAID', '2025-12-25 19:40:20'),
(2, 1, 1, 4999.00, 'PAID', '2025-12-25 19:41:28'),
(3, 1, 1, 4999.00, 'PAID', '2025-12-25 19:41:53'),
(4, 1, 2, 2999.00, 'PAID', '2025-12-25 19:42:03'),
(5, 1, 1, 4999.00, 'PAID', '2025-12-25 19:47:54'),
(6, 1, 1, 4999.00, 'PAID', '2025-12-25 19:48:15'),
(7, 1, 3, 3999.00, 'PAID', '2025-12-25 19:48:38'),
(8, 1, 7, 6500.00, 'PENDING', '2025-12-25 19:51:51'),
(9, 1, 1, 4999.00, 'PENDING', '2025-12-25 19:56:04');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) DEFAULT 'PENDING',
  `payment_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `user_id`, `order_id`, `amount`, `payment_status`, `payment_date`) VALUES
(1, 1, 8, 6500.00, 'PENDING', '2025-12-25 19:51:51'),
(2, 1, 9, 4999.00, 'PENDING', '2025-12-25 19:56:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password`) VALUES
(1, 'Venkatesh M', 'venkatesh21arikai@gmail.com', '07397057670', '$2y$10$Jd7nm9XzO9p3c4QPDcVnuu1aSqQyeQbqZZLFuMlEIDTz6CzC1pyzu'),
(2, 'testing', 'test@gmail.com', '237469234', '$2y$10$ZtbSrtljVDTU7RwOywVgcOwAOzNvjTHJMoiL59Wyf/g5O2cKDmmxe'),
(3, 'vignesh', 'vignesh@gmail.com', '9894517008', '$2y$10$4syxW8vyjaw5e.qt.sWm1OmsmMVeIQYjdwQTKpKs5ndwHPAr8I5om');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--
]6 TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `user_id`, `course_id`) VALUES
(4, 2, 10),
(13, 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
