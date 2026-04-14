<?php require_once __DIR__ . '/db.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Review</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="container hero">
    <h1>Discover Movies, Ratings & Reviews</h1>
    <p>
        Browse movie reviews, explore genres, check ratings, and read user comments in one place.
    </p>

    <div class="hero-buttons">
        <a href="#featured" class="btn btn-main">Browse Movies</a>
        <a href="search.php" class="btn btn-outline-main">Search Reviews</a>
    </div>
</div>

<div class="container mb-5">
    <div class="stats-grid">
        <?php
        $movies_count    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM dbproj_movies WHERE status = 'published'"))['total'] ?? 0;
        $genres_count    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM dbproj_genres"))['total'] ?? 0;
        $ratings_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM dbproj_ratings"))['total'] ?? 0;
        $comments_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM dbproj_comments"))['total'] ?? 0;
        ?>

        <div class="stat-card">
            <div class="stat-number"><?= $movies_count ?></div>
            <div class="stat-label">Published Movies</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?= $genres_count ?></div>
            <div class="stat-label">Genres</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?= $ratings_count ?></div>
            <div class="stat-label">Ratings Submitted</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?= $comments_count ?></div>
            <div class="stat-label">Comments Posted</div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <h2 id="featured" class="section-title">Featured Movies</h2>
    <p class="section-subtitle">
        Explore some of the latest published movie reviews from the platform.
    </p>

    <div class="row">
        <?php
        $sql = "
            SELECT
                m.movie_id,
                m.title,
                m.description,
                m.release_year,
                g.genre_name,
                ROUND(AVG(r.rating_value), 1) AS avg_rating
            FROM dbproj_movies m
            LEFT JOIN dbproj_genres g ON m.genre_id = g.genre_id
            LEFT JOIN dbproj_ratings r ON m.movie_id = r.movie_id
            WHERE m.status = 'published'
            GROUP BY m.movie_id
            ORDER BY m.created_at DESC
            LIMIT 4
        ";

        $result = mysqli_query($conn, $sql);

        while ($row = mysqli_fetch_assoc($result)) {
        ?>
            <div class="col-md-6 mb-4">
                <div class="movie-card h-100">
                    <div class="movie-title">
                        <?= htmlspecialchars($row['title']) ?>
                    </div>

                    <div class="movie-meta">
                        <?= htmlspecialchars($row['genre_name'] ?? 'Unknown Genre') ?>
                        •
                        <?= htmlspecialchars($row['release_year'] ?? 'N/A') ?>
                    </div>

                    <?php if (!empty($row['avg_rating'])): ?>
                        <div class="rating-text">⭐ <?= $row['avg_rating'] ?>/5</div>
                    <?php else: ?>
                        <div class="movie-meta">Not yet rated</div>
                    <?php endif; ?>

                    <p class="text-secondary mt-3">
                        <?= htmlspecialchars(mb_strimwidth($row['description'] ?? '', 0, 120, '...')) ?>
                    </p>

                    <div class="mt-3">
                        <a href="movie.php?id=<?= $row['movie_id'] ?>" class="btn btn-main">
                            View Review
                        </a>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<div class="container mb-5">
    <h2 class="section-title">Project Features</h2>
    <p class="section-subtitle">
        Everything users need to browse, evaluate, and interact with movie reviews.
    </p>

    <div class="features-grid">
        <div class="feature-card">
            <h4>Smart Search</h4>
            <p class="text-secondary mb-0">
                Search by movie title, creator, genre, and date range.
            </p>
        </div>

        <div class="feature-card">
            <h4>Ratings</h4>
            <p class="text-secondary mb-0">
                View average ratings and evaluate movie reviews easily.
            </p>
        </div>

        <div class="feature-card">
            <h4>Comments</h4>
            <p class="text-secondary mb-0">
                Read audience opinions and interact through comments.
            </p>
        </div>

        <div class="feature-card">
            <h4>Reports</h4>
            <p class="text-secondary mb-0">
                Generate admin reports for content analysis and moderation.
            </p>
        </div>
    </div>
</div>

<div class="footer">
    Movie Review System
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>