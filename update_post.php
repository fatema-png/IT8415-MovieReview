<?php
// Handles the "Edit Review" form (from edit_post.php).
require_once 'db.php';
require_once 'auth.php';

requireCreator();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: creator_dashboard.php");
    exit();
}

$movieId     = intval($_POST['movie_id'] ?? 0);
$title       = trim($_POST['title'] ?? '');
$genreId     = intval($_POST['genre_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$fullReview  = trim($_POST['full_review'] ?? '');
$releaseYear = intval($_POST['release_year'] ?? 0);
$trailerUrl  = trim($_POST['trailer_url'] ?? '');
$action      = $_POST['action'] ?? 'save';

if ($movieId <= 0 || $title === '' || $description === '' || $genreId <= 0) {
    header("Location: edit_post.php?id=" . $movieId);
    exit();
}

// Ownership check: make sure this movie belongs to the current user (or admin)
$check = $conn->prepare("SELECT user_id FROM dbproj_movies WHERE movie_id = ?");
$check->bind_param("i", $movieId);
$check->execute();
$owner = $check->get_result()->fetch_assoc();
$check->close();

if (!$owner) {
    die("Movie not found.");
}
if ($owner['user_id'] != getCurrentUserId() && !isAdmin()) {
    die("You are not allowed to edit this review.");
}

// Work out the new status based on which button was clicked
$statusSql = "";
if ($action === 'publish') {
    $statusSql = ", status = 'published'";
} elseif ($action === 'unpublish') {
    $statusSql = ", status = 'draft'";
}

$genreIdParam     = $genreId > 0 ? $genreId : null;
$releaseYearParam = $releaseYear > 0 ? $releaseYear : null;
$trailerUrlParam  = $trailerUrl !== '' ? $trailerUrl : null;

// Update the movie
$stmt = $conn->prepare("
    UPDATE dbproj_movies
    SET title = ?, genre_id = ?, description = ?, full_review = ?,
        release_year = ?, trailer_url = ? $statusSql
    WHERE movie_id = ?
");
$stmt->bind_param(
    "sissssi",
    $title, $genreIdParam, $description, $fullReview,
    $releaseYearParam, $trailerUrlParam, $movieId
);
$stmt->execute();
$stmt->close();

// If a new image was uploaded, replace the old one
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $fileName = time() . "_" . basename($_FILES['image']['name']);
    $target   = "uploads/" . $fileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        // Remove the old image rows for this movie, then add the new one
        $del = $conn->prepare("DELETE FROM dbproj_media WHERE movie_id = ? AND file_type = 'image'");
        $del->bind_param("i", $movieId);
        $del->execute();
        $del->close();

        $ins = $conn->prepare("INSERT INTO dbproj_media (movie_id, file_path, file_type) VALUES (?, ?, 'image')");
        $ins->bind_param("is", $movieId, $target);
        $ins->execute();
        $ins->close();
    }
}

header("Location: creator_dashboard.php?msg=updated");
exit();
