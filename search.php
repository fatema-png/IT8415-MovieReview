<?php
require_once 'db.php';
require_once 'auth.php';

// values used only to pre fill the search form. The actual querying and the
// rendering of the results live in search results php, which is included below
// for the first page load and got by ajax for live type as you go search
$search_title   = trim($_GET['title']   ?? '');
$search_creator = trim($_GET['creator'] ?? '');
$date_from      = $_GET['date_from']    ?? '';
$date_to        = $_GET['date_to']      ?? '';
$sort_by        = in_array($_GET['sort'] ?? '', ['newest', 'oldest', 'popular', 'rating'])
                    ? $_GET['sort'] : 'newest';
$genre_id       = intval($_GET['genre'] ?? 0);

// genres for the filter dropdown
$genres = $conn->query("SELECT genre_id, genre_name FROM dbproj_genres ORDER BY genre_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Movie Reviews</title>
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
    <?php include 'includes/navbar.php';?>

    <div class="search-container">
        <h1 style="color:#fff; margin-bottom:24px;">🔍 Search Movie Reviews</h1>

        <!-- search form -->
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
                    <button type="button" id="clearBtn" class="btn-search">Clear</button>
                </div>
            </div>
        </form>

        <!-- server rendered first then refreshed live by ajax -->
        <div id="resultsContainer">
            <?php include __DIR__ . '/search_results.php'; ?>
        </div>
    </div>

    <script>
    // live search: refresh results as the user types/changes filters
    const searchForm       = document.getElementById('searchForm');
    const resultsContainer = document.getElementById('resultsContainer');
    const fromInput        = document.getElementById('date_from');
    const toInput          = document.getElementById('date_to');

    // build the query string from the current form values (drops empty fields)
    function buildSearchQuery() {
        const params = new URLSearchParams(new FormData(searchForm));
        for (const [key, value] of [...params]) {
            if (value === '') params.delete(key);
        }
        return params.toString();
    }

    // get the results and swap it in. keeps the address bar in sync
    // so the search stays shareable/bookmarkable and survives a refresh
    async function loadResults(query, updateUrl = true) {
        try {
            const res  = await fetch('search_results.php' + (query ? '?' + query : ''));
            resultsContainer.innerHTML = await res.text();
        } catch (err) {
            // leave the current results in place
            return;
        }
        if (updateUrl) {
            history.replaceState(null, '', 'search.php' + (query ? '?' + query : ''));
        }
    }

    // a new search always starts from page 1
    function runSearch() {
        // dont search on an invalid date range, wait
        if (fromInput.value && toInput.value && fromInput.value > toInput.value) return;
        loadResults(buildSearchQuery());
    }

    // make sure we dont fire a request on every keystroke.
    let searchTimer = null;
    searchForm.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(runSearch, 300);
    });

    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        if (fromInput.value && toInput.value && fromInput.value > toInput.value) {
            alert('Error: "From Date" cannot be after "To Date".');
            return;
        }
        clearTimeout(searchTimer);
        runSearch();
    });

    document.getElementById('clearBtn').addEventListener('click', () => {
        searchForm.reset();
        document.getElementById('suggestions').innerHTML = '';
        clearTimeout(searchTimer);
        runSearch();
    });

    // pagination links also load
    resultsContainer.addEventListener('click', (e) => {
        const link = e.target.closest('.pagination a');
        if (!link) return;
        e.preventDefault();
        loadResults(link.getAttribute('href').replace(/^\?/, ''));
        resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    </script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	
	<script>
const titleInput = document.getElementById('title');
const suggestionsBox = document.getElementById('suggestions');

// escape user/creator controlled text before putting it in the dom = prevent xss
function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
}

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
            const safeTitle = escapeHtml(movie.title);
            html += `
                <div class="suggestion-item" data-id="${parseInt(movie.movie_id, 10)}" data-title="${safeTitle}">
                    ${safeTitle}
                </div>
            `;
        });
        html += '</div>';

        suggestionsBox.innerHTML = html;

        document.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', function () {
                titleInput.value = this.dataset.title;
                suggestionsBox.innerHTML = '';
                // refresh the results for the chosen title (fires live search).
                titleInput.dispatchEvent(new Event('input', { bubbles: true }));
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
