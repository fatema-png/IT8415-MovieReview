<?php
// Creator page: shows the form to edit an existing review.
require_once 'db.php';
require_once 'auth.php';

requireCreator();

$movieId = intval($_GET['id'] ?? 0);
if ($movieId <= 0) {
    die("Movie ID missing.");
}

// Load the movie
$stmt = $conn->prepare("SELECT * FROM dbproj_movies WHERE movie_id = ?");
$stmt->bind_param("i", $movieId);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$movie) {
    die("Movie not found.");
}

// Ownership check: you can only edit your OWN movie, unless you are an admin.
if ($movie['user_id'] != getCurrentUserId() && !isAdmin()) {
    die("You are not allowed to edit this review.");
}

// Load genres for the dropdown
$genres = $conn->query("SELECT genre_id, genre_name FROM dbproj_genres ORDER BY genre_name")
              ->fetch_all(MYSQLI_ASSOC);

// Load the current poster image (if there is one)
$imgStmt = $conn->prepare("
    SELECT file_path FROM dbproj_media
    WHERE movie_id = ? AND file_type = 'image' LIMIT 1
");
$imgStmt->bind_param("i", $movieId);
$imgStmt->execute();
$currentImage = $imgStmt->get_result()->fetch_assoc()['file_path'] ?? '';
$imgStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review — Movie Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5" style="max-width:720px;">
    <div class="card-dark">
        <h2 class="mb-4">✏️ Edit Movie Review</h2>

        <form action="update_post.php" method="POST" id="postForm">
            <input type="hidden" name="movie_id" value="<?= $movie['movie_id'] ?>">

            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control"
                       value="<?= htmlspecialchars($movie['title']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Genre</label>
                <select name="genre_id" class="form-select" required>
                    <option value="">— Choose a genre —</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= $g['genre_id'] ?>"
                            <?= $movie['genre_id'] == $g['genre_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['genre_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Short Description</label>
                <textarea name="description" class="form-control" rows="2" required><?= htmlspecialchars($movie['description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Full Review</label>
                <textarea name="full_review" class="form-control" rows="6"><?= htmlspecialchars($movie['full_review']) ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Release Year</label>
                    <input type="number" name="release_year" class="form-control"
                           min="1900" max="2099" value="<?= htmlspecialchars($movie['release_year']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Trailer URL (optional)</label>
                    <input type="url" name="trailer_url" class="form-control"
                           value="<?= htmlspecialchars($movie['trailer_url']) ?>"
                           placeholder="https://www.youtube.com/embed/...">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Poster Image URL</label>
                <?php if ($currentImage): ?>
                    <div class="mb-2">
                        <img src="<?= htmlspecialchars($currentImage) ?>" alt="current poster"
                             style="height:120px; border-radius:8px;">
                    </div>
                <?php endif; ?>
                <?php
                    // If the current poster is an external URL, pre-fill the URL box.
                    $currentIsUrl = preg_match('#^https?://#i', $currentImage);
                ?>
                <input type="url" name="image_url" class="form-control"
                       value="<?= $currentIsUrl ? htmlspecialchars($currentImage) : '' ?>"
                       placeholder="Paste an image URL, e.g. https://image.tmdb.org/.../poster.jpg">
                <small class="text-secondary">Leave empty to keep the current image.</small>
            </div>

            <!-- Update keeps the current status. Publish/Unpublish change it. -->
            <button type="submit" name="action" value="save" class="btn btn-main">Update</button>
            <?php if ($movie['status'] === 'draft'): ?>
                <button type="submit" name="action" value="publish" class="btn btn-outline-light">Update &amp; Publish</button>
            <?php else: ?>
                <button type="submit" name="action" value="unpublish" class="btn btn-outline-light">Update &amp; Unpublish</button>
            <?php endif; ?>
            <a href="creator_dashboard.php" class="btn btn-link text-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
// Simple JavaScript validation
document.getElementById('postForm').addEventListener('submit', function (e) {
    if (this.title.value.trim().length < 2) {
        e.preventDefault();
        alert('Please enter a longer title.');
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
