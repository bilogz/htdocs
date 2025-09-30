-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: May 01, 2025 at 06:51 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 1,
  `available_quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `available_stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `description`, `cover_image`, `category`, `stock_quantity`, `available_quantity`, `created_at`, `available_stock`) VALUES
(1, 'The Power of Interactive Magazine', 'John Doe', 'This is a great book about the future of digital magazines and interactive content. It explores the impact of interactive design in modern publishing.', 'popular-01.jpg', 'Technology', 10, 8, '2025-04-24 16:33:57', 11),
(2, 'Understanding AI and Machine Learning', 'Jane Smith', 'An in-depth exploration of artificial intelligence and its applications. This book covers the fundamentals and the cutting-edge advancements in AI.', 'popular-02.jpg', 'Technology', 15, 10, '2025-04-24 16:33:57', 7),
(3, 'Healthy Living Guide', 'Dr. Emily Taylor', 'A comprehensive guide to living a healthy lifestyle, from balanced diets to exercise routines. Perfect for anyone looking to improve their wellness.', 'popular-03.jpg', 'Health', 20, 15, '2025-04-24 16:33:57', 13),
(4, 'Mastering Web Development', 'Robert Green', 'This book provides a step-by-step approach to becoming a full-stack developer. Learn front-end and back-end technologies to build modern websites and applications.', 'popular-04.jpg', 'Programming', 25, 18, '2025-04-24 16:33:57', 17),
(5, 'The Future of Space Exploration', 'Neil Armstrong', 'Explore the possibilities of future space missions and the technologies that will drive humanity beyond Earth. A must-read for space enthusiasts and professionals alike.', 'popular-05.jpg', 'Science', 5, 2, '2025-04-24 16:33:57', 1);

-- --------------------------------------------------------

--
-- Table structure for table `borrowed_books`
--

CREATE TABLE `borrowed_books` (
  `record_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `admin_confirmed_return` tinyint(1) DEFAULT 0,
  `user_confirmed_return` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowed_books`
--

INSERT INTO `borrowed_books` (`record_id`, `student_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `admin_confirmed_return`, `user_confirmed_return`) VALUES
(1, 2222, 1, '2025-05-01', '2025-05-08', '2025-05-01', 'returned', 0, 0),
(2, 2222, 2, '2025-05-01', '2025-05-08', '2025-05-01', 'returned', 0, 0),
(3, 2222, 3, '2025-05-01', '2025-05-08', '2025-05-01', 'returned', 0, 0),
(4, 2222, 3, '2025-05-01', '2025-05-08', '2025-05-01', 'returned', 0, 0),
(5, 2222, 2, '2025-05-01', '2025-05-08', '2025-05-01', 'returned', 0, 0),
(6, 2222, 2, '2025-05-01', '2025-05-08', NULL, 'borrowed', 0, 0),
(7, 2222, 1, '2025-05-01', '2025-05-08', NULL, 'borrowed', 0, 0),
(8, 2222, 4, '2025-05-01', '2025-05-08', NULL, 'borrowed', 0, 0),
(9, 2222, 5, '2025-05-01', '2025-05-08', NULL, 'borrowed', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `ebooks`
--

CREATE TABLE `ebooks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ebooks`
--

INSERT INTO `ebooks` (`id`, `title`, `author`, `description`, `file_path`, `stock`) VALUES
(1, 'The Power of Interactive Magazine', 'Jane Smith', 'The magazine market has changed dramatically in recent years with the development of digital platforms and the growing demand for interactive content. Digital magazines are a staple of today’s market because they offer an immersive and engaging experience. People are increasingly less likely to buy magazines at the newsstand and store, but instead prefer to read a digital magazine on their mobile devices!', 'uploads/sample.pdf', 10);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `student_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `profile_pic` varchar(255) NOT NULL,
  `user_type` enum('student','librarian','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`student_id`, `password`, `email`, `full_name`, `profile_pic`, `user_type`, `created_at`) VALUES
(2, '$2y$10$r2xgZtqWnonMFRyDrnoSQOVaiLjNzLhXH/KFpcMQ6DoLe/QCRBJde', 'joecel@gmail.com', 'Joecel', 'uploads/id_1745510456_490987580_1926634821411218_606849430782418085_n.jpg', 'student', '2025-04-24 16:00:56'),
(1111, '$2y$10$QT.64MaFBVnIg2nA40N5v.oBKCI3FwwqyaHk8UPWspSsO0xIuarO6', 'roseily@gmail.com', 'Rose my laloves', 'E:/htdocs/uploads/id_1745511239_490987580_1926634821411218_606849430782418085_n.jpg', 'student', '2025-04-24 16:13:59'),
(2222, '$2y$10$xj3Bu5RXSJeEgfo5p60zV.oUVtTKBOE/iHmIq9B8gEhXfFgLsCjNS', 'joecel11@gmail.com', 'Joecel', 'assets/images/profile_pictures/profile_2222.png', 'student', '2025-05-01 14:21:08'),
(22123123, '$2y$10$1mrKJ6JViHfEFA/C3ZIsD.ym7S6Bt8V2UHAU1/fMip0OwfVHpaGBi', 'joecel1@gmail.com', '123123', 'E:/htdocs/uploads/id_1745510942_490987580_1926634821411218_606849430782418085_n.jpg', 'student', '2025-04-24 16:09:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `user_id` (`student_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `ebooks`
--
ALTER TABLE `ebooks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ebooks`
--
ALTER TABLE `ebooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22123124;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  ADD CONSTRAINT `borrowed_books_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`student_id`),
  ADD CONSTRAINT `borrowed_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
