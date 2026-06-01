<?php

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

// ownership check: make sure this movie belongs to the current user (or admin)
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

// work out the new status based on which button was clicked
$statusSql = "";
if ($action === 'publish') {
    $statusSql = ", status = 'published'";
} elseif ($action === 'unpublish') {
    $statusSql = ", status = 'draft'";
}

$genreIdParam     = $genreId > 0 ? $genreId : null;
$releaseYearParam = $releaseYear > 0 ? $releaseYear : null;
$trailerUrlParam  = $trailerUrl !== '' ? $trailerUrl : null;

// update the movie
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

//1hour of my life wasted here i love php 
$imgError = null;
$imageUrl = trim($_POST['image_url'] ?? '');

if ($imageUrl !== '') {
    if (preg_match('#^https?://#i', $imageUrl)
        && filter_var($imageUrl, FILTER_VALIDATE_URL)
        && strlen($imageUrl) <= 255) {
        // remove old image row for this movie then store the new url
        $del = $conn->prepare("DELETE FROM dbproj_media WHERE movie_id = ? AND file_type = 'image'");
        $del->bind_param("i", $movieId);
        $del->execute();
        $del->close();

        $ins = $conn->prepare("INSERT INTO dbproj_media (movie_id, file_path, file_type) VALUES (?, ?, 'image')");
        $ins->bind_param("is", $movieId, $imageUrl);
        $ins->execute();
        $ins->close();
    } else {
        $imgError = 'The image URL is not valid. Use a link starting with '
                  . 'http:// or https:// (max 255 characters).';
    }
}

$redirect = "creator_dashboard.php?msg=updated";
if ($imgError !== null) {
    $redirect .= "&imgerror=" . urlencode($imgError);
}
header("Location: " . $redirect);
exit();
