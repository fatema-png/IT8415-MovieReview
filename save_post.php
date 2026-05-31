<?php
// Handles the "Add New Review" form (from create_post.php).
require_once 'db.php';
require_once 'auth.php';

// Only creators and admins can save content
requireCreator();

// This page should only be reached by submitting the form
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

// The button the user clicked decides the status (draft or published)
$status = ($_POST['action'] ?? 'draft') === 'publish' ? 'published' : 'draft';

// ---- Server-side validation ----
if ($title === '' || $description === '' || $genreId <= 0) {
    header("Location: create_post.php?error=" . urlencode('Please fill in the title, genre and description.'));
    exit();
}

// Turn empty values into NULL so they are stored cleanly
$genreIdParam     = $genreId > 0 ? $genreId : null;
$releaseYearParam = $releaseYear > 0 ? $releaseYear : null;
$trailerUrlParam  = $trailerUrl !== '' ? $trailerUrl : null;

// ---- Insert the movie ----
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

// ---- Handle the uploaded image (if any) ----
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    // Make a unique file name so two uploads never clash
    $fileName = time() . "_" . basename($_FILES['image']['name']);
    $target   = "uploads/" . $fileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        // Save the image path in the media table so the site can show it
        $mediaStmt = $conn->prepare("
            INSERT INTO dbproj_media (movie_id, file_path, file_type)
            VALUES (?, ?, 'image')
        ");
        $mediaStmt->bind_param("is", $newMovieId, $target);
        $mediaStmt->execute();
        $mediaStmt->close();
    }
}

header("Location: creator_dashboard.php?msg=created");
exit();
