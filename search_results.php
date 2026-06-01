<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// input sanitization
$search_title   = trim($_GET['title']   ?? '');
$search_creator = trim($_GET['creator'] ?? '');
$date_from      = $_GET['date_from']    ?? '';
$date_to        = $_GET['date_to']      ?? '';
$sort_by        = in_array($_GET['sort'] ?? '', ['newest', 'oldest', 'popular', 'rating'])
                    ? $_GET['sort'] : 'newest';
$genre_id       = intval($_GET['genre'] ?? 0);

// pagination
$per_page    = 10;
$page        = max(1, intval($_GET['page'] ?? 1));
$offset      = ($page - 1) * $per_page;

// build query
$conditions = ["m.status = 'published'"];
$params     = [];
$types      = '';

if ($search_title !== '') {
    // full text search using the fulltext index
    $conditions[] = "MATCH(m.title, m.description) AGAINST(? IN BOOLEAN MODE)";
    $params[]     = $search_title . '*';
    $types       .= 's';
}

if ($search_creator !== '') {
    $conditions[] = "u.username LIKE ?";
    $params[]     = '%' . $search_creator . '%';
    $types       .= 's';
}

if ($date_from !== '') {
    $conditions[] = "DATE(m.created_at) >= ?";
    $params[]     = $date_from;
    $types       .= 's';
}

if ($date_to !== '') {
    $conditions[] = "DATE(m.created_at) <= ?";
    $params[]     = $date_to;
    $types       .= 's';
}

if ($genre_id > 0) {
    $conditions[] = "m.genre_id = ?";
    $params[]     = $genre_id;
    $types       .= 'i';
}

$where = 'WHERE ' . implode(' AND ', $conditions);

$order = match ($sort_by) {
    'oldest'  => 'ORDER BY m.created_at ASC',
    'popular' => 'ORDER BY m.view_count DESC',
    'rating'  => 'ORDER BY avg_rating DESC',
    default   => 'ORDER BY m.created_at DESC',
};

// count total results for pagination
$count_sql = "
    SELECT COUNT(DISTINCT m.movie_id) AS total
    FROM dbproj_movies m
    JOIN dbproj_users u ON m.user_id = u.user_id
    LEFT JOIN dbproj_genres g ON m.genre_id = g.genre_id
    $where
";

$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages   = ceil($total_results / $per_page);
$count_stmt->close();

// main search query
$sql = "
    SELECT
        m.movie_id,
        m.title,
        m.description,
        m.release_year,
        m.view_count,
        m.created_at,
        u.username AS creator,
        g.genre_name,
        ROUND(AVG(r.rating_value), 1) AS avg_rating,
        COUNT(DISTINCT r.rating_id) AS total_ratings,
        COUNT(DISTINCT c.comment_id) AS total_comments,
        (SELECT med.file_path FROM dbproj_media med
         WHERE med.movie_id = m.movie_id AND med.file_type = 'image'
         LIMIT 1) AS thumbnail
    FROM dbproj_movies m
    JOIN dbproj_users u ON m.user_id = u.user_id
    LEFT JOIN dbproj_genres g ON m.genre_id = g.genre_id
    LEFT JOIN dbproj_ratings r ON m.movie_id = r.movie_id
    LEFT JOIN dbproj_comments c ON m.movie_id = c.movie_id
    $where
    GROUP BY m.movie_id
    $order
    LIMIT ? OFFSET ?
";

$params[]  = $per_page;
$params[]  = $offset;
$types    .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$any_search = $search_title || $search_creator || $date_from || $date_to || $genre_id;
?>
<?php if ($any_search): ?>
    <div class="results-header">
        <span><?= $total_results ?> result<?= $total_results != 1 ? 's' : '' ?> found</span>
        <?php if ($total_results > 0): ?>
            <span>Page <?= $page ?> of <?= $total_pages ?></span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($any_search && empty($results)): ?>
    <div class="no-results">
        <div class="icon">🎬</div>
        <p>No reviews found matching your search.</p>
        <a href="search.php" style="color:#e50914;">Clear Search</a>
    </div>
<?php endif; ?>

<?php foreach ($results as $movie): ?>
    <div class="movie-card">
        <?php if ($movie['thumbnail']): ?>
            <img src="<?= htmlspecialchars($movie['thumbnail']) ?>"
                 alt="<?= htmlspecialchars($movie['title']) ?>"
                 class="movie-thumb">
        <?php else: ?>
            <div class="movie-thumb-placeholder">🎬</div>
        <?php endif; ?>

        <div class="movie-info">
            <a href="movie.php?id=<?= $movie['movie_id'] ?>" class="movie-title">
                <?= htmlspecialchars($movie['title']) ?>
                <span style="color:#888; font-weight:400; font-size:0.9rem;">
                    (<?= $movie['release_year'] ?>)
                </span>
            </a>

            <div class="movie-meta">
                <?php if ($movie['genre_name']): ?>
                    <span class="badge"><?= htmlspecialchars($movie['genre_name']) ?></span>
                <?php endif; ?>
                <span>👤 <?= htmlspecialchars($movie['creator']) ?></span>
                <span>👁 <?= number_format($movie['view_count']) ?> views</span>
                <span>💬 <?= $movie['total_comments'] ?> comments</span>
                <?php if ($movie['avg_rating']): ?>
                    <span class="stars">
                        <?= str_repeat('★', round($movie['avg_rating'])) ?><?= str_repeat('☆', 5 - round($movie['avg_rating'])) ?>
                    </span>
                    <span style="color:#f5c518;"><?= $movie['avg_rating'] ?>/5</span>
                <?php else: ?>
                    <span style="color:#666;">Not yet rated</span>
                <?php endif; ?>
                <span style="margin-left:8px; color:#666; font-size:0.8rem;">
                    <?= date('M d, Y', strtotime($movie['created_at'])) ?>
                </span>
            </div>

            <p class="movie-desc">
                <?= htmlspecialchars(mb_strimwidth($movie['description'], 0, 180, '...')) ?>
            </p>

            <a href="movie.php?id=<?= $movie['movie_id'] ?>" class="btn-view">View Review →</a>
        </div>
    </div>
<?php endforeach; ?>

<!-- pagination -->
<?php if ($total_pages > 1): ?>
    <?php
    // build pagination url preserving all search params
    $base_params = array_filter([
        'title'     => $search_title,
        'creator'   => $search_creator,
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'sort'      => $sort_by,
        'genre'     => $genre_id ?: null,
    ]);
    ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($base_params, ['page' => $page - 1])) ?>">← Prev</a>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <?php if ($i == $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="?<?= http_build_query(array_merge($base_params, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?<?= http_build_query(array_merge($base_params, ['page' => $page + 1])) ?>">Next →</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
