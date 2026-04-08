-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 08, 2026 at 10:40 PM
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
-- Database: `dbproj_movies`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetPopularMoviesByDateRange` (IN `start_date` DATE, IN `end_date` DATE)   BEGIN
    SELECT 
        m.movie_id,
        m.title,
        g.genre_name,
        u.username AS reviewer,
        m.view_count,
        ROUND(AVG(r.rating_value), 1) AS avg_rating,
        COUNT(DISTINCT r.rating_id) AS total_ratings,
        COUNT(DISTINCT c.comment_id) AS total_comments,
        m.created_at
    FROM dbProj_movies m
    JOIN dbProj_users u ON m.user_id = u.user_id
    LEFT JOIN dbProj_genres g ON m.genre_id = g.genre_id
    LEFT JOIN dbProj_ratings r ON m.movie_id = r.movie_id
    LEFT JOIN dbProj_comments c ON m.movie_id = c.movie_id
    WHERE m.status = 'published'
      AND DATE(m.created_at) BETWEEN start_date AND end_date
    GROUP BY m.movie_id
    ORDER BY m.view_count DESC, avg_rating DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `dbproj_comments`
--

CREATE TABLE `dbproj_comments` (
  `comment_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dbproj_comments`
--

INSERT INTO `dbproj_comments` (`comment_id`, `movie_id`, `user_id`, `comment_text`, `created_at`) VALUES
(1, 1, 4, 'This movie completely blew my mind!', '2026-04-08 23:37:29'),
(2, 1, 5, 'Watched it three times already.', '2026-04-08 23:37:29'),
(3, 2, 4, 'An absolute classic. Nothing compares.', '2026-04-08 23:37:29'),
(4, 5, 5, 'The science in this film is fascinating.', '2026-04-08 23:37:29'),
(5, 9, 4, 'Parasite deserved every Oscar it won.', '2026-04-08 23:37:29');

--
-- Triggers `dbproj_comments`
--
DELIMITER $$
CREATE TRIGGER `after_comment_insert` AFTER INSERT ON `dbproj_comments` FOR EACH ROW BEGIN
    UPDATE dbProj_movies
    SET view_count = view_count + 1
    WHERE movie_id = NEW.movie_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `dbproj_genres`
--

CREATE TABLE `dbproj_genres` (
  `genre_id` int(11) NOT NULL,
  `genre_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dbproj_genres`
--

INSERT INTO `dbproj_genres` (`genre_id`, `genre_name`) VALUES
(1, 'Action'),
(7, 'Animation'),
(3, 'Comedy'),
(2, 'Drama'),
(4, 'Horror'),
(5, 'Sci-Fi'),
(6, 'Thriller');

-- --------------------------------------------------------

--
-- Table structure for table `dbproj_media`
--

CREATE TABLE `dbproj_media` (
  `media_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` enum('image','video','audio','document') DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dbproj_movies`
--

CREATE TABLE `dbproj_movies` (
  `movie_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `genre_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `full_review` longtext DEFAULT NULL,
  `release_year` year(4) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `view_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dbproj_movies`
--

INSERT INTO `dbproj_movies` (`movie_id`, `user_id`, `genre_id`, `title`, `description`, `full_review`, `release_year`, `status`, `view_count`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'Inception', 'A mind-bending heist thriller', 'Full review...', '2010', 'published', 522, '2026-04-08 23:37:29', '2026-04-08 23:37:29'),
(2, 2, 2, 'The Godfather', 'A classic crime drama masterpiece', 'Full review...', '1972', 'published', 891, '2026-04-08 23:37:29', '2026-04-08 23:37:29'),
(3, 2, 3, 'The Grand Budapest Hotel', 'Quirky Wes Anderson comedy', 'Full review...', '2014', 'published', 310, '2026-04-08 23:37:29', NULL),
(4, 3, 4, 'Get Out', 'Groundbreaking social horror film', 'Full review...', '2017', 'published', 640, '2026-04-08 23:37:29', NULL),
(5, 3, 5, 'Interstellar', 'Epic space exploration drama', 'Full review...', '2014', 'published', 751, '2026-04-08 23:37:29', '2026-04-08 23:37:29'),
(6, 2, 6, 'Gone Girl', 'Gripping psychological thriller', 'Full review...', '2014', 'published', 480, '2026-04-08 23:37:29', NULL),
(7, 3, 7, 'Spirited Away', 'Magical animated adventure', 'Full review...', '2001', 'published', 420, '2026-04-08 23:37:29', NULL),
(8, 2, 1, 'Mad Max: Fury Road', 'High-octane post-apocalyptic action', 'Full review...', '2015', 'published', 390, '2026-04-08 23:37:29', NULL),
(9, 3, 2, 'Parasite', 'Korean class struggle masterpiece', 'Full review...', '2019', 'published', 811, '2026-04-08 23:37:29', '2026-04-08 23:37:29'),
(10, 2, 5, 'The Matrix', 'Revolutionary sci-fi action film', 'Full review...', '1999', 'published', 960, '2026-04-08 23:37:29', NULL),
(11, 3, 3, 'Everything Everywhere All at Once', 'Surreal multiverse comedy drama', 'Full review...', '2022', 'published', 570, '2026-04-08 23:37:29', NULL),
(12, 2, 4, 'Hereditary', 'Disturbing family horror film', 'Full review...', '2018', 'published', 330, '2026-04-08 23:37:29', NULL),
(13, 3, 6, 'Knives Out', 'Witty modern murder mystery', 'Full review...', '2019', 'published', 440, '2026-04-08 23:37:29', NULL),
(14, 2, 1, 'Top Gun: Maverick', 'Thrilling aviation action sequel', 'Full review...', '2022', 'published', 700, '2026-04-08 23:37:29', NULL),
(15, 3, 2, 'Oppenheimer', 'Epic biography of atomic bomb creator', 'Full review...', '2023', 'published', 830, '2026-04-08 23:37:29', NULL);

--
-- Triggers `dbproj_movies`
--
DELIMITER $$
CREATE TRIGGER `before_movie_update` BEFORE UPDATE ON `dbproj_movies` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `dbproj_ratings`
--

CREATE TABLE `dbproj_ratings` (
  `rating_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating_value` tinyint(4) DEFAULT NULL CHECK (`rating_value` between 1 and 5),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dbproj_ratings`
--

INSERT INTO `dbproj_ratings` (`rating_id`, `movie_id`, `user_id`, `rating_value`, `created_at`) VALUES
(1, 1, 4, 5, '2026-04-08 23:37:29'),
(2, 1, 5, 4, '2026-04-08 23:37:29'),
(3, 2, 4, 5, '2026-04-08 23:37:29'),
(4, 2, 5, 5, '2026-04-08 23:37:29'),
(5, 3, 4, 4, '2026-04-08 23:37:29'),
(6, 3, 5, 3, '2026-04-08 23:37:29'),
(7, 4, 4, 5, '2026-04-08 23:37:29'),
(8, 5, 5, 5, '2026-04-08 23:37:29'),
(9, 9, 4, 5, '2026-04-08 23:37:29'),
(10, 10, 5, 5, '2026-04-08 23:37:29');

-- --------------------------------------------------------

--
-- Table structure for table `dbproj_roles`
--

CREATE TABLE `dbproj_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dbproj_roles`
--

INSERT INTO `dbproj_roles` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'Creator'),
(3, 'Viewer');

-- --------------------------------------------------------

--
-- Table structure for table `dbproj_users`
--

CREATE TABLE `dbproj_users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dbproj_users`
--

INSERT INTO `dbproj_users` (`user_id`, `username`, `email`, `password`, `role_id`, `created_at`) VALUES
(1, 'admin_user', 'admin@movies.com', 'hashed_admin', 1, '2026-04-08 23:37:29'),
(2, 'critic_john', 'john@movies.com', 'hashed_john', 2, '2026-04-08 23:37:29'),
(3, 'critic_sara', 'sara@movies.com', 'hashed_sara', 2, '2026-04-08 23:37:29'),
(4, 'viewer_mike', 'mike@movies.com', 'hashed_mike', 3, '2026-04-08 23:37:29'),
(5, 'viewer_anna', 'anna@movies.com', 'hashed_anna', 3, '2026-04-08 23:37:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dbproj_comments`
--
ALTER TABLE `dbproj_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `dbproj_genres`
--
ALTER TABLE `dbproj_genres`
  ADD PRIMARY KEY (`genre_id`),
  ADD UNIQUE KEY `genre_name` (`genre_name`);

--
-- Indexes for table `dbproj_media`
--
ALTER TABLE `dbproj_media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `dbproj_movies`
--
ALTER TABLE `dbproj_movies`
  ADD PRIMARY KEY (`movie_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_view_count` (`view_count`),
  ADD KEY `idx_genre_id` (`genre_id`);
ALTER TABLE `dbproj_movies` ADD FULLTEXT KEY `ft_movie_search` (`title`,`description`);

--
-- Indexes for table `dbproj_ratings`
--
ALTER TABLE `dbproj_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `unique_rating` (`movie_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `dbproj_roles`
--
ALTER TABLE `dbproj_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `dbproj_users`
--
ALTER TABLE `dbproj_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dbproj_comments`
--
ALTER TABLE `dbproj_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dbproj_genres`
--
ALTER TABLE `dbproj_genres`
  MODIFY `genre_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `dbproj_media`
--
ALTER TABLE `dbproj_media`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dbproj_movies`
--
ALTER TABLE `dbproj_movies`
  MODIFY `movie_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `dbproj_ratings`
--
ALTER TABLE `dbproj_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dbproj_roles`
--
ALTER TABLE `dbproj_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dbproj_users`
--
ALTER TABLE `dbproj_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dbproj_comments`
--
ALTER TABLE `dbproj_comments`
  ADD CONSTRAINT `dbproj_comments_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `dbproj_movies` (`movie_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dbproj_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `dbproj_users` (`user_id`);

--
-- Constraints for table `dbproj_media`
--
ALTER TABLE `dbproj_media`
  ADD CONSTRAINT `dbproj_media_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `dbproj_movies` (`movie_id`) ON DELETE CASCADE;

--
-- Constraints for table `dbproj_movies`
--
ALTER TABLE `dbproj_movies`
  ADD CONSTRAINT `dbproj_movies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `dbproj_users` (`user_id`),
  ADD CONSTRAINT `dbproj_movies_ibfk_2` FOREIGN KEY (`genre_id`) REFERENCES `dbproj_genres` (`genre_id`);

--
-- Constraints for table `dbproj_ratings`
--
ALTER TABLE `dbproj_ratings`
  ADD CONSTRAINT `dbproj_ratings_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `dbproj_movies` (`movie_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dbproj_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `dbproj_users` (`user_id`);

--
-- Constraints for table `dbproj_users`
--
ALTER TABLE `dbproj_users`
  ADD CONSTRAINT `dbproj_users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `dbproj_roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
