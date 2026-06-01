<?php
// Creator page: shows the form to add a brand new movie review.
require_once 'db.php';
require_once 'auth.php';

// Only creators and admins are allowed here
requireCreator();

// Load the genres so the user can pick one from a dropdown
$genres = $conn->query("SELECT genre_id, genre_name FROM dbproj_genres ORDER BY genre_name")
              ->fetch_all(MYSQLI_ASSOC);

// If we came back from save_post.php with an error, show it
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Review — Movie Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5" style="max-width:720px;">
    <div class="card-dark">
        <h2 class="mb-4">🎬 Add New Movie Review</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="save_post.php" method="POST" id="postForm">

            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Genre</label>
                <select name="genre_id" class="form-select" required>
                    <option value="">— Choose a genre —</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= $g['genre_id'] ?>"><?= htmlspecialchars($g['genre_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Short Description</label>
                <textarea name="description" class="form-control" rows="2" required
                          placeholder="One or two lines shown on the movie cards."></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Full Review</label>
                <textarea name="full_review" class="form-control" rows="6"
                          placeholder="The full review text."></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Release Year</label>
                    <input type="number" name="release_year" class="form-control"
                           min="1900" max="2099" placeholder="e.g. 2024">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Trailer URL (optional)</label>
                    <input type="url" name="trailer_url" class="form-control"
                           placeholder="https://www.youtube.com/embed/...">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Poster Image URL</label>
                <input type="url" name="image_url" class="form-control"
                       placeholder="Paste an image URL, e.g. https://image.tmdb.org/.../poster.jpg">
                <small class="text-secondary">Optional. Paste a direct link to an image (ending in .jpg, .png, etc.).</small>
            </div>

            <!-- Two buttons: save as a draft, or publish straight away.
                 The "action" value tells save_post.php which one was clicked. -->
            <button type="submit" name="action" value="draft" class="btn btn-outline-light">
                Save as Draft
            </button>
            <button type="submit" name="action" value="publish" class="btn btn-main">
                Publish Now
            </button>
            <a href="creator_dashboard.php" class="btn btn-link text-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
// Simple JavaScript validation before the form is sent
document.getElementById('postForm').addEventListener('submit', function (e) {
    const title = this.title.value.trim();
    const desc  = this.description.value.trim();
    if (title.length < 2) {
        e.preventDefault();
        alert('Please enter a longer title.');
    } else if (desc.length < 5) {
        e.preventDefault();
        alert('Please enter a short description.');
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
