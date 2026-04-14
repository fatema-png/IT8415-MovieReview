<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($movie_id <= 0) {
    die("Movie not found.");
}

$sql = "
    SELECT
        m.movie_id,
        m.title,
        m.description,
        m.release_year,
        m.view_count,
        m.created_at,
        m.status,
        u.username AS creator,
        g.genre_name,
        ROUND(AVG(r.rating_value), 1) AS avg_rating,
        COUNT(DISTINCT r.rating_id) AS total_ratings,
        COUNT(DISTINCT c.comment_id) AS total_comments,
        (
            SELECT med.file_path
            FROM dbproj_media med
            WHERE med.movie_id = m.movie_id AND med.file_type = 'image'
            LIMIT 1
        ) AS thumbnail
    FROM dbproj_movies m
    JOIN dbproj_users u ON m.user_id = u.user_id
    LEFT JOIN dbproj_genres g ON m.genre_id = g.genre_id
    LEFT JOIN dbproj_ratings r ON m.movie_id = r.movie_id
    LEFT JOIN dbproj_comments c ON m.movie_id = c.movie_id
    WHERE m.movie_id = ?
    GROUP BY m.movie_id
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$movie) {
    die("Movie not found.");
}

// optional: increase view count
$update_views = $conn->prepare("UPDATE dbproj_movies SET view_count = view_count + 1 WHERE movie_id = ?");
$update_views->bind_param("i", $movie_id);
$update_views->execute();
$update_views->close();

$movie['view_count']++;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movie['title']) ?> - Movie Review</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .movie-details-container{
            max-width:1100px;
            margin:40px auto;
            padding:0 20px;
        }

        .movie-hero-card{
            background:#111827;
            border-radius:16px;
            padding:28px;
            box-shadow:0 8px 24px rgba(0,0,0,0.35);
            margin-bottom:30px;
        }

        .movie-layout{
            display:grid;
            grid-template-columns:260px 1fr;
            gap:28px;
            align-items:start;
        }

        .movie-cover,
        .movie-cover-placeholder{
            width:100%;
            height:360px;
            border-radius:14px;
            object-fit:cover;
            background:#1f2937;
        }

        .movie-cover-placeholder{
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:70px;
            color:#9ca3af;
        }

        .detail-title{
            font-size:2.2rem;
            font-weight:700;
            margin-bottom:10px;
        }

        .detail-meta{
            color:#9ca3af;
            font-size:0.95rem;
            display:flex;
            gap:14px;
            flex-wrap:wrap;
            margin-bottom:14px;
        }

        .detail-badge{
            display:inline-block;
            background:#1f2937;
            color:#d1d5db;
            border:1px solid #374151;
            border-radius:999px;
            padding:4px 10px;
            font-size:0.8rem;
            margin-right:8px;
        }

        .detail-rating{
            color:#f5c518;
            font-weight:600;
            margin:12px 0;
        }

        .detail-description{
            color:#d1d5db;
            line-height:1.7;
            margin-top:16px;
        }

        .back-link{
            text-decoration:none;
            color:#9ca3af;
            display:inline-block;
            margin-bottom:16px;
        }

        .back-link:hover{
            color:#fff;
        }

        @media (max-width: 768px){
            .movie-layout{
                grid-template-columns:1fr;
            }

            .movie-cover,
            .movie-cover-placeholder{
                height:300px;
            }

            .detail-title{
                font-size:1.8rem;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="movie-details-container">
    <a href="search.php" class="back-link">← Back to Search</a>

    <div class="movie-hero-card">
        <div class="movie-layout">
            <div>
                <?php if (!empty($movie['thumbnail'])): ?>
                    <img
                        src="<?= htmlspecialchars($movie['thumbnail']) ?>"
                        alt="<?= htmlspecialchars($movie['title']) ?>"
                        class="movie-cover"
                    >
                <?php else: ?>
                    <div class="movie-cover-placeholder">🎬</div>
                <?php endif; ?>
            </div>

            <div>
                <div class="detail-title">
                    <?= htmlspecialchars($movie['title']) ?>
                </div>

                <div class="detail-meta">
                    <span><?= htmlspecialchars($movie['release_year'] ?: 'N/A') ?></span>
                    <span>👤 <?= htmlspecialchars($movie['creator']) ?></span>
                    <span>👁 <?= number_format($movie['view_count']) ?> views</span>
                    <span>💬 <?= (int)$movie['total_comments'] ?> comments</span>
                    <span><?= date('M d, Y', strtotime($movie['created_at'])) ?></span>
                </div>

                <div>
                    <?php if (!empty($movie['genre_name'])): ?>
                        <span class="detail-badge"><?= htmlspecialchars($movie['genre_name']) ?></span>
                    <?php endif; ?>

                    <span class="detail-badge"><?= ucfirst(htmlspecialchars($movie['status'])) ?></span>
                </div>

                <div class="detail-rating">
                    <?php if (!empty($movie['avg_rating'])): ?>
                        <?= str_repeat('★', (int) round($movie['avg_rating'])) ?><?= str_repeat('☆', 5 - (int) round($movie['avg_rating'])) ?>
                        <?= $movie['avg_rating'] ?>/5
                        <span style="color:#9ca3af; font-weight:400;">
                            (<?= (int)$movie['total_ratings'] ?> ratings)
                        </span>
                    <?php else: ?>
                        <span style="color:#9ca3af;">Not yet rated</span>
                    <?php endif; ?>
                </div>

                <div class="detail-description">
                    <?= nl2br(htmlspecialchars($movie['description'])) ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/comments_section.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>