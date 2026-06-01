--  Movie Review - full database
--  Import this in phpMyAdmin to set up.
--
--
--  Demo accounts (THE PASSWORD FOR AL OF THEM IS: password)
--    admin@movies.com   -> admin
--    john@movies.com    -> creator
--    sara@movies.com    -> creator
--    mike@movies.com    -> viewer
--    anna@movies.com    -> viewer
-- =============================================================

-- Drop old tables so the file can be re imported cleanly
DROP TABLE IF EXISTS `dbproj_comments`;
DROP TABLE IF EXISTS `dbproj_ratings`;
DROP TABLE IF EXISTS `dbproj_media`;
DROP TABLE IF EXISTS `dbproj_movies`;
DROP TABLE IF EXISTS `dbproj_genres`;
DROP TABLE IF EXISTS `dbproj_users`;
DROP TABLE IF EXISTS `dbproj_roles`;
DROP PROCEDURE IF EXISTS `GetPopularMoviesByDateRange`;

SET FOREIGN_KEY_CHECKS = 1;



-- 1. ROLES  (the 3 user types)

CREATE TABLE `dbproj_roles` (
    `role_id`   INT(11) NOT NULL AUTO_INCREMENT,
    `role_name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`role_id`),
    UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dbproj_roles` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'Creator'),
(3, 'Viewer');



-- 2. USERS
-- The password hash below is bcrypt for the word "password".
CREATE TABLE `dbproj_users` (
    `user_id`    INT(11) NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(100) NOT NULL,
    `email`      VARCHAR(150) NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `role_id`    INT(11) NOT NULL,
    `created_at` DATETIME DEFAULT current_timestamp(),
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `role_id` (`role_id`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`)
        REFERENCES `dbproj_roles` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dbproj_users` (`user_id`, `username`, `email`, `password`, `role_id`, `created_at`) VALUES
(1, 'admin_user',  'admin@movies.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2026-04-08 23:37:29'),
(2, 'critic_john', 'john@movies.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '2026-04-08 23:37:29'),
(3, 'critic_sara', 'sara@movies.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '2026-04-08 23:37:29'),
(4, 'viewer_mike', 'mike@movies.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, '2026-04-08 23:37:29'),
(5, 'viewer_anna', 'anna@movies.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, '2026-04-08 23:37:29');



-- 3. GENRES  (categories)
CREATE TABLE `dbproj_genres` (
    `genre_id`   INT(11) NOT NULL AUTO_INCREMENT,
    `genre_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`genre_id`),
    UNIQUE KEY `genre_name` (`genre_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dbproj_genres` (`genre_id`, `genre_name`) VALUES
(1, 'Action'),
(2, 'Drama'),
(3, 'Comedy'),
(4, 'Horror'),
(5, 'Sci-Fi'),
(6, 'Thriller'),
(7, 'Animation');


-- 4. MOVIES  (the main content)
CREATE TABLE `dbproj_movies` (
    `movie_id`     INT(11) NOT NULL AUTO_INCREMENT,
    `user_id`      INT(11) NOT NULL,
    `genre_id`     INT(11) DEFAULT NULL,
    `title`        VARCHAR(255) NOT NULL,
    `description`  TEXT DEFAULT NULL,
    `full_review`  LONGTEXT DEFAULT NULL,
    `release_year` YEAR(4) DEFAULT NULL,
    `trailer_url`  VARCHAR(255) DEFAULT NULL,
    `status`       ENUM('draft','published') DEFAULT 'draft',
    `view_count`   INT(11) DEFAULT 0,
    `created_at`   DATETIME DEFAULT current_timestamp(),
    `updated_at`   DATETIME DEFAULT NULL ON UPDATE current_timestamp(),
    PRIMARY KEY (`movie_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_view_count` (`view_count`),
    KEY `idx_genre_id` (`genre_id`),
    FULLTEXT KEY `ft_movie_search` (`title`,`description`),
    CONSTRAINT `fk_movies_user`  FOREIGN KEY (`user_id`)  REFERENCES `dbproj_users` (`user_id`),
    CONSTRAINT `fk_movies_genre` FOREIGN KEY (`genre_id`) REFERENCES `dbproj_genres` (`genre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- created_at values are spread across several weeks so the newest are first
INSERT INTO `dbproj_movies`
(`movie_id`, `user_id`, `genre_id`, `title`, `description`, `full_review`, `release_year`, `trailer_url`, `status`, `view_count`, `created_at`, `updated_at`) VALUES
(1,  2, 5, 'Inception',                          'A mind-bending heist thriller set inside dreams.',        'Christopher Nolan builds a layered world where thieves steal ideas from the subconscious. Stunning visuals and a clever script make this a modern classic.', '2010', 'https://www.youtube.com/embed/YoHD9XEInc0', 'published', 522, '2026-02-03 10:15:00', NULL),
(2,  2, 2, 'The Godfather',                       'A classic crime drama masterpiece.',                      'The story of the Corleone family is widely considered one of the greatest films ever made. Brilliant acting and direction throughout.', '1972', 'https://www.youtube.com/embed/sY1S34973zA', 'published', 891, '2026-02-09 14:40:00', NULL),
(3,  2, 3, 'The Grand Budapest Hotel',            'A quirky and colourful Wes Anderson comedy.',             'Wes Anderson delivers his signature symmetrical style with a fast, funny and charming caper. A visual treat from start to finish.', '2014', 'https://www.youtube.com/embed/1Fg5iWmQjwk', 'published', 310, '2026-02-16 09:05:00', NULL),
(4,  3, 4, 'Get Out',                             'A groundbreaking social horror film.',                    'Jordan Peele mixes horror with sharp social commentary. Tense, smart and original from beginning to end.', '2017', 'https://www.youtube.com/embed/DzfpyUB60YY', 'published', 640, '2026-02-24 18:30:00', NULL),
(5,  3, 2, 'Interstellar',                        'An epic space exploration drama.',                        'A visually stunning journey through space and time that also tells a moving story about family and sacrifice.', '2014', 'https://www.youtube.com/embed/zSWdZVtXT7E', 'published', 751, '2026-03-02 12:00:00', NULL),
(6,  2, 6, 'Gone Girl',                           'A gripping psychological thriller.',                      'A twisting mystery about a marriage gone wrong. Keeps you guessing until the very end.', '2014', 'https://www.youtube.com/embed/2-_-1nJf8Vg', 'published', 480, '2026-03-08 20:20:00', NULL),
(7,  3, 7, 'Spirited Away',                       'A magical animated adventure.',                           'Studio Ghibli at its best. A beautiful, imaginative story that works for both children and adults.', '2001', 'https://www.youtube.com/embed/ByXuk9QqQkk', 'published', 420, '2026-03-15 11:45:00', NULL),
(8,  2, 1, 'Mad Max: Fury Road',                  'High-octane post-apocalyptic action.',                    'Almost non-stop action with incredible practical stunts. One of the best action films of the decade.', '2015', 'https://www.youtube.com/embed/hEJnMQG9ev8', 'published', 390, '2026-03-21 16:10:00', NULL),
(9,  3, 2, 'Parasite',                            'A Korean class-struggle masterpiece.',                    'Bong Joon-ho weaves comedy, drama and thriller into one unforgettable film. A deserving Best Picture winner.', '2019', 'https://www.youtube.com/embed/5xH0HfJHsaY', 'published', 811, '2026-03-28 13:25:00', NULL),
(10, 2, 5, 'The Matrix',                          'A revolutionary sci-fi action film.',                     'Groundbreaking visual effects and a thought-provoking story changed action cinema forever.', '1999', 'https://www.youtube.com/embed/vKQi3bBA1y8', 'published', 960, '2026-04-04 08:50:00', NULL),
(11, 3, 2, 'Everything Everywhere All at Once',   'A surreal multiverse comedy-drama.',                      'A wildly creative film that is funny, emotional and completely unique. A true original.', '2022', 'https://www.youtube.com/embed/wxN1T1uxQ2g', 'published', 570, '2026-04-11 19:35:00', NULL),
(12, 2, 4, 'Hereditary',                          'A disturbing family horror film.',                        'A slow-burning, deeply unsettling horror film with a powerful lead performance.', '2018', 'https://www.youtube.com/embed/V6wWKNij_1M', 'published', 330, '2026-04-18 15:00:00', NULL),
(13, 3, 6, 'Knives Out',                          'A witty modern murder mystery.',                          'A clever, funny whodunit with a great cast and a satisfying twist.', '2019', 'https://www.youtube.com/embed/qGqiHJTsRkQ', 'published', 440, '2026-04-25 10:40:00', NULL),
(14, 2, 1, 'Top Gun: Maverick',                   'A thrilling aviation action sequel.',                     'Spectacular flight sequences and real emotion make this a rare sequel that surpasses the original.', '2022', 'https://www.youtube.com/embed/qSqVVswa420', 'published', 700, '2026-05-02 17:55:00', NULL),
(15, 3, 2, 'Oppenheimer',                         'An epic biography of the atomic bomb creator.',           'Nolan tells the story of J. Robert Oppenheimer with intensity and scale. A gripping historical drama.', '2023', 'https://www.youtube.com/embed/uYPbbksJxIg', 'published', 830, '2026-05-10 21:15:00', NULL),
-- one draft so the "edit before publishing" workflow can be demonstrated
(16, 2, 1, 'John Wick',                           'A stylish revenge action thriller.',                      'This review is still a work in progress and has not been published yet.', '2014', NULL, 'draft', 0, '2026-05-16 09:30:00', NULL);


-- 5. MEDIA  (images + video trailers for each movie)
--    Each published movie has at least one image.
CREATE TABLE `dbproj_media` (
    `media_id`    INT(11) NOT NULL AUTO_INCREMENT,
    `movie_id`    INT(11) NOT NULL,
    `file_path`   VARCHAR(255) DEFAULT NULL,
    `file_type`   ENUM('image','video','audio','document') DEFAULT NULL,
    `uploaded_at` DATETIME DEFAULT current_timestamp(),
    PRIMARY KEY (`media_id`),
    KEY `movie_id` (`movie_id`),
    CONSTRAINT `fk_media_movie` FOREIGN KEY (`movie_id`)
        REFERENCES `dbproj_movies` (`movie_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posters are stored as image URLs (because doing file uploads was a mess for us).
INSERT INTO `dbproj_media` (`movie_id`, `file_path`, `file_type`) VALUES
(1,  'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_.jpg',                                            'image'),
(2,  'https://m.media-amazon.com/images/M/MV5BNGEwYjgwOGQtYjg5ZS00Njc1LTk2ZGEtM2QwZWQ2NjdhZTE5XkEyXkFqcGc@._V1_.jpg',                            'image'),
(3,  'https://m.media-amazon.com/images/M/MV5BMzM5NjUxOTEyMl5BMl5BanBnXkFtZTgwNjEyMDM0MDE@._V1_FMjpg_UX1000_.jpg',                              'image'),
(4,  'https://m.media-amazon.com/images/M/MV5BMjUxMDQwNjcyNl5BMl5BanBnXkFtZTgwNzcwMzc0MTI@._V1_.jpg',                                            'image'),
(5,  'https://m.media-amazon.com/images/M/MV5BYzdjMDAxZGItMjI2My00ODA1LTlkNzItOWFjMDU5ZDJlYWY3XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg',              'image'),
(6,  'https://lumiere-a.akamaihd.net/v1/images/image_059c8da8.jpeg?region=0%2C0%2C800%2C1200',                                                  'image'),
(7,  'https://upload.wikimedia.org/wikipedia/en/thumb/d/db/Spirited_Away_Japanese_poster.png/250px-Spirited_Away_Japanese_poster.png',          'image'),
(8,  'https://m.media-amazon.com/images/M/MV5BZDRkODJhOTgtOTc1OC00NTgzLTk4NjItNDgxZDY4YjlmNDY2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg',              'image'),
(9,  'https://image.tmdb.org/t/p/original/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg',                                                                     'image'),
(10, 'https://media.themoviedb.org/t/p/w220_and_h330_face/8bqQCNer6aNtO3sWcCZ6SV6E6fS.jpg',                                                     'image'),
(11, 'https://m.media-amazon.com/images/M/MV5BOWNmMzAzZmQtNDQ1NC00Nzk5LTkyMmUtNGI2N2NkOWM4MzEyXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg',              'image'),
(12, 'https://m.media-amazon.com/images/I/91U6sekg9yL.jpg',                                                                                     'image'),
(13, 'https://m.media-amazon.com/images/M/MV5BZDU5ZTRkYmItZjg0Mi00ZTQwLThjMWItNWM3MTMxMzVjZmVjXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg',              'image'),
(14, 'https://m.media-amazon.com/images/I/71BokibfVUL.jpg',                                                                                     'image'),
(15, 'https://upload.wikimedia.org/wikipedia/en/4/4a/Oppenheimer_%28film%29.jpg',                                                               'image');


-- 6. RATINGS  (1 to 5 stars, one per user per movie)
CREATE TABLE `dbproj_ratings` (
    `rating_id`    INT(11) NOT NULL AUTO_INCREMENT,
    `movie_id`     INT(11) NOT NULL,
    `user_id`      INT(11) NOT NULL,
    `rating_value` TINYINT(4) DEFAULT NULL CHECK (`rating_value` BETWEEN 1 AND 5),
    `created_at`   DATETIME DEFAULT current_timestamp(),
    PRIMARY KEY (`rating_id`),
    UNIQUE KEY `unique_rating` (`movie_id`,`user_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_ratings_movie` FOREIGN KEY (`movie_id`) REFERENCES `dbproj_movies` (`movie_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ratings_user`  FOREIGN KEY (`user_id`)  REFERENCES `dbproj_users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dbproj_ratings` (`movie_id`, `user_id`, `rating_value`) VALUES
(1, 4, 5), (1, 5, 4),
(2, 4, 5), (2, 5, 5),
(3, 4, 4), (3, 5, 3),
(4, 4, 5),
(5, 5, 5),
(9, 4, 5),
(10, 5, 5);


-- 7. COMMENTS
CREATE TABLE `dbproj_comments` (
    `comment_id`   INT(11) NOT NULL AUTO_INCREMENT,
    `movie_id`     INT(11) NOT NULL,
    `user_id`      INT(11) NOT NULL,
    `comment_text` TEXT NOT NULL,
    `created_at`   DATETIME DEFAULT current_timestamp(),
    PRIMARY KEY (`comment_id`),
    KEY `movie_id` (`movie_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_comments_movie` FOREIGN KEY (`movie_id`) REFERENCES `dbproj_movies` (`movie_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comments_user`  FOREIGN KEY (`user_id`)  REFERENCES `dbproj_users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dbproj_comments` (`movie_id`, `user_id`, `comment_text`) VALUES
(1, 4, 'This movie completely blew my mind!'),
(1, 5, 'Watched it three times already.'),
(2, 4, 'An absolute classic. Nothing compares.'),
(5, 5, 'The science in this film is fascinating.'),
(9, 4, 'Parasite deserved every Oscar it won.');


-- 8. STORED PROCEDURE
--    Used by the admin "Most Popular Movies" report.
--    Returns published movies created between two dates,
--    ordered by views and average rating.
DELIMITER $$
CREATE PROCEDURE `GetPopularMoviesByDateRange`(IN `start_date` DATE, IN `end_date` DATE)
BEGIN
    SELECT
        m.movie_id,
        m.title,
        g.genre_name,
        u.username AS reviewer,
        m.view_count,
        ROUND(AVG(r.rating_value), 1) AS avg_rating,
        COUNT(DISTINCT r.rating_id)  AS total_ratings,
        COUNT(DISTINCT c.comment_id) AS total_comments,
        m.created_at
    FROM dbproj_movies m
    JOIN dbproj_users u  ON m.user_id  = u.user_id
    LEFT JOIN dbproj_genres g   ON m.genre_id = g.genre_id
    LEFT JOIN dbproj_ratings r  ON m.movie_id = r.movie_id
    LEFT JOIN dbproj_comments c ON m.movie_id = c.movie_id
    WHERE m.status = 'published'
      AND DATE(m.created_at) BETWEEN start_date AND end_date
    GROUP BY m.movie_id
    ORDER BY m.view_count DESC, avg_rating DESC;
END$$
DELIMITER ;


-- 9. TRIGGER
--    Automatically refresh updated_at whenever a movie changes.
DELIMITER $$
CREATE TRIGGER `before_movie_update`
BEFORE UPDATE ON `dbproj_movies`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END$$
DELIMITER ;
