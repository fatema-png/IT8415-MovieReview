<?php
require_once 'db.php';
require_once 'auth.php';

// ---------- Input sanitization ----------
$search_title   = trim($_GET['title']   ?? '');
$search_creator = trim($_GET['creator'] ?? '');
$date_from      = $_GET['date_from']    ?? '';
$date_to        = $_GET['date_to']      ?? '';
$sort_by        = in_array($_GET['sort'] ?? '', ['newest', 'oldest', 'popular', 'rating'])
                    ? $_GET['sort'] : 'newest';
$genre_id       = intval($_GET['genre'] ?? 0);

// ---------- Pagination ----------
$per_page    = 10;
$page        = max(1, intval($_GET['page'] ?? 1));
$offset      = ($page - 1) * $per_page;

// ---------- Build query ----------
$conditions = ["m.status = 'published'"];
$params     = [];
$types      = '';

if ($search_title !== '') {
    // Full-text search using the ft_movie_search FULLTEXT index
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

// Count total results for pagination
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

// Main search query
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

// Fetch genres for filter dropdown
$genres = $conn->query("SELECT genre_id, genre_name FROM dbproj_genres ORDER BY genre_name")->fetch_all(MYSQLI_ASSOC);

$any_search = $search_title || $search_creator || $date_from || $date_to || $genre_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Movie Reviews</title>
    <!-- Link to Member 4's stylesheet -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    .search-container{
        max-width:1100px;
        margin:40px auto;
        padding:0 20px;
    }

    .search-title{
        color:#fff;
        font-size:2.2rem;
        font-weight:700;
        margin-bottom:24px;
    }

    .search-form{
        background:#111827;
        border-radius:14px;
        padding:24px;
        margin-bottom:30px;
        box-shadow:0 8px 24px rgba(0,0,0,0.35);
    }

    .form-row{
        display:flex;
        flex-wrap:wrap;
        gap:16px;
        margin-bottom:16px;
    }

    .form-group{
        flex:1;
        min-width:180px;
        display:flex;
        flex-direction:column;
        gap:6px;
    }

    .form-group label{
        color:#d1d5db;
        font-size:0.82rem;
        font-weight:600;
        text-transform:uppercase;
    }

    .form-group input,
    .form-group select{
        padding:10px 14px;
        border-radius:8px;
        border:1px solid #374151;
        background:#1f2937;
        color:#fff;
        font-size:0.95rem;
        outline:none;
    }

    .form-group input:focus,
    .form-group select:focus{
        border-color:#ef4444;
        box-shadow:0 0 0 2px rgba(239,68,68,0.15);
    }

    .btn-search{
        background:#ef4444;
        color:#fff;
        border:none;
        padding:11px 28px;
        border-radius:8px;
        cursor:pointer;
        font-size:1rem;
        font-weight:600;
        transition:0.2s ease;
    }

    .btn-search:hover{
        background:#dc2626;
    }

    .results-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:18px;
        color:#9ca3af;
        font-size:0.95rem;
    }

    .movie-card{
        display:flex;
        gap:20px;
        background:#111827;
        border-radius:14px;
        padding:16px;
        margin-bottom:18px;
        box-shadow:0 8px 24px rgba(0,0,0,0.35);
        transition:0.2s ease;
    }

    .movie-card:hover{
        transform:translateY(-3px) scale(1.01);
    }

    .movie-thumb{
        width:100px;
        height:140px;
        object-fit:cover;
        border-radius:10px;
        flex-shrink:0;
        background:#1f2937;
    }

    .movie-thumb-placeholder{
        width:100px;
        height:140px;
        border-radius:10px;
        background:#1f2937;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#6b7280;
        font-size:2rem;
        flex-shrink:0;
    }

    .movie-info{
        flex:1;
    }

    .movie-title{
        font-size:1.25rem;
        font-weight:700;
        color:#fff;
        text-decoration:none;
    }

    .movie-title:hover{
        color:#ef4444;
    }

    .movie-meta{
        font-size:0.85rem;
        color:#9ca3af;
        margin:8px 0;
    }

    .movie-meta span{
        margin-right:14px;
    }

    .movie-desc{
        color:#d1d5db;
        font-size:0.94rem;
        margin:8px 0;
    }

    .stars{
        color:#f5c518;
    }

    .badge{
        display:inline-block;
        background:#1f2937;
        border:1px solid #374151;
        color:#d1d5db;
        font-size:0.75rem;
        padding:2px 8px;
        border-radius:12px;
        margin-right:6px;
    }

    .btn-view{
        display:inline-block;
        margin-top:10px;
        background:#ef4444;
        color:#fff;
        padding:8px 18px;
        border-radius:8px;
        text-decoration:none;
        font-size:0.88rem;
        font-weight:600;
        transition:0.2s ease;
    }

    .btn-view:hover{
        background:#dc2626;
        color:#fff;
    }

    .pagination{
        display:flex;
        gap:8px;
        justify-content:center;
        margin-top:30px;
        flex-wrap:wrap;
    }

    .pagination a,
    .pagination span{
        padding:8px 14px;
        border-radius:8px;
        border:1px solid #374151;
        color:#d1d5db;
        text-decoration:none;
        font-size:0.9rem;
        background:#111827;
    }

    .pagination a:hover{
        background:#ef4444;
        border-color:#ef4444;
        color:#fff;
    }

    .pagination .active{
        background:#ef4444;
        border-color:#ef4444;
        color:#fff;
    }

    .no-results{
        text-align:center;
        color:#9ca3af;
        padding:60px 0;
        background:#111827;
        border-radius:14px;
        box-shadow:0 8px 24px rgba(0,0,0,0.35);
    }

    .no-results .icon{
        font-size:3rem;
        margin-bottom:10px;
    }

    @media (max-width: 768px){
        .search-title{
            font-size:1.8rem;
        }

        .movie-card{
            flex-direction:column;
        }

        .movie-thumb,
        .movie-thumb-placeholder{
            width:100%;
            height:220px;
        }

        .results-header{
            flex-direction:column;
            align-items:flex-start;
            gap:8px;
        }
    }
</style>
</head>
<body>
    <?php include 'includes/navbar.php'; // Member 4's nav ?>

    <div class="search-container">
        <h1 style="color:#fff; margin-bottom:24px;">🔍 Search Movie Reviews</h1>

        <!-- Search Form -->
        <form class="search-form" method="GET" action="search.php" id="searchForm">
            <div class="form-row">
                <div class="form-group suggestion-wrapper" style="flex:2; min-width:240px;">
					<label for="title">Movie Title</label>
					<input type="text" id="title" name="title"
					placeholder="Search by title or keyword..."
					autocomplete="off"
					value="<?= htmlspecialchars($search_title) ?>">
				<div id="suggestions"></div>
				</div>
                <div class="form-group">
                    <label for="creator">Critic / Creator</label>
                    <input type="text" id="creator" name="creator"
                           placeholder="e.g. critic_john"
                           value="<?= htmlspecialchars($search_creator) ?>">
                </div>
                <div class="form-group">
                    <label for="genre">Genre</label>
                    <select id="genre" name="genre">
                        <option value="0">All Genres</option>
                        <?php foreach ($genres as $g): ?>
                            <option value="<?= $g['genre_id'] ?>"
                                <?= $genre_id == $g['genre_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['genre_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="date_from">From Date</label>
                    <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="form-group">
                    <label for="date_to">To Date</label>
                    <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="form-group">
                    <label for="sort">Sort By</label>
                    <select id="sort" name="sort">
                        <option value="newest"  <?= $sort_by == 'newest'  ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest"  <?= $sort_by == 'oldest'  ? 'selected' : '' ?>>Oldest First</option>
                        <option value="popular" <?= $sort_by == 'popular' ? 'selected' : '' ?>>Most Viewed</option>
                        <option value="rating"  <?= $sort_by == 'rating'  ? 'selected' : '' ?>>Highest Rated</option>
                    </select>
                </div>
                <div class="form-group" style="justify-content:flex-end; padding-top:22px;">
                    <button type="submit" class="btn-search">Search</button>
                </div>
            </div>
        </form>

        <!-- Results -->
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

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <?php
            // Build pagination URL preserving all search params
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
    </div>

    <script>
        // JavaScript validation for date range
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            const from = document.getElementById('date_from').value;
            const to   = document.getElementById('date_to').value;
            if (from && to && from > to) {
                e.preventDefault();
                alert('Error: "From Date" cannot be after "To Date".');
            }
        });
    </script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	
	<script>
const titleInput = document.getElementById('title');
const suggestionsBox = document.getElementById('suggestions');

titleInput.addEventListener('input', async function () {
    const query = this.value.trim();

    if (query.length === 0) {
        suggestionsBox.innerHTML = '';
        return;
    }

    try {
        const response = await fetch(`search_suggestions.php?q=${encodeURIComponent(query)}`);
        const movies = await response.json();

        if (!movies.length) {
            suggestionsBox.innerHTML = '';
            return;
        }

        let html = '<div class="suggestion-list">';
        movies.forEach(movie => {
            html += `
                <div class="suggestion-item" data-id="${movie.movie_id}" data-title="${movie.title}">
                    ${movie.title}
                </div>
            `;
        });
        html += '</div>';

        suggestionsBox.innerHTML = html;

        document.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', function () {
                titleInput.value = this.dataset.title;
                suggestionsBox.innerHTML = '';
            });
        });
    } catch (error) {
        suggestionsBox.innerHTML = '';
    }
});

document.addEventListener('click', function(e) {
    if (!suggestionsBox.contains(e.target) && e.target !== titleInput) {
        suggestionsBox.innerHTML = '';
    }
});
</script>
</body>
</html>
