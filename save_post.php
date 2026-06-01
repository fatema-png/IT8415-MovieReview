<?php
// handles the "Add New Review" form (from create post php)
require_once 'db.php';
require_once 'auth.php';

// only creators and admins can save content
requireCreator();

// this page should only be reached by submitting the form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: create_post.php");
    exit();
}

$userId      = getCurrentUserId();
$title       = trim($_POST['title'] ?? '');
$genreId     = intval($_POST['genre_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$fullReview  = trim($_POST['full_review'] ?? '');
$releaseYear = intval($_POST['release_year'] ?? 0);
$trailerUrl  = trim($_POST['trailer_url'] ?? '');

// the button the user clicked decides the status (draft or published)
$status = ($_POST['action'] ?? 'draft') === 'publish' ? 'published' : 'draft';

// server side validation
if ($title === '' || $description === '' || $genreId <= 0) {
    header("Location: create_post.php?error=" . urlencode('Please fill in the title, genre and description.'));
    exit();
}

// turn empty values into NULL so they are stored cleanly
$genreIdParam     = $genreId > 0 ? $genreId : null;
$releaseYearParam = $releaseYear > 0 ? $releaseYear : null;
$trailerUrlParam  = $trailerUrl !== '' ? $trailerUrl : null;

// insert the movie
$stmt = $conn->prepare("
    INSERT INTO dbproj_movies
        (user_id, genre_id, title, description, full_review, release_year, trailer_url, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "iissssss",
    $userId, $genreIdParam, $title, $description,
    $fullReview, $releaseYearParam, $trailerUrlParam, $status
);
$stmt->execute();
$newMovieId = $conn->insert_id;
$stmt->close();

// 2 hours of my life wasted here
$imgError  = null;
$imageUrl  = trim($_POST['image_url'] ?? '');

if ($imageUrl !== '') {
    if (preg_match('#^https?://#i', $imageUrl)
        && filter_var($imageUrl, FILTER_VALIDATE_URL)
        && strlen($imageUrl) <= 255) {
        $mediaStmt = $conn->prepare("
            INSERT INTO dbproj_media (movie_id, file_path, file_type)
            VALUES (?, ?, 'image')
        ");
        $mediaStmt->bind_param("is", $newMovieId, $imageUrl);
        $mediaStmt->execute();
        $mediaStmt->close();
    } else {
        $imgError = 'The image URL is not valid. Use a link starting with '
                  . 'http:// or https:// (max 255 characters).';
    }
}

// the review was created either way, tell the dashboard and pass on any image problem so it can be shown as a warning
$redirect = "creator_dashboard.php?msg=created";
if ($imgError !== null) {
    $redirect .= "&imgerror=" . urlencode($imgError);
}
header("Location: " . $redirect);
exit();
