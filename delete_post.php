<?php
// Deletes a movie review (creator deletes own, admin deletes any).
require_once 'db.php';
require_once 'auth.php';

requireCreator();

// Deleting changes data, so only accept POST (prevents CSRF via a plain link).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: creator_dashboard.php");
    exit();
}

$movieId = intval($_POST['id'] ?? 0);
if ($movieId <= 0) {
    die("Movie ID missing.");
}

// Ownership check before deleting
$check = $conn->prepare("SELECT user_id FROM dbproj_movies WHERE movie_id = ?");
$check->bind_param("i", $movieId);
$check->execute();
$owner = $check->get_result()->fetch_assoc();
$check->close();

if (!$owner) {
    die("Movie not found.");
}
if ($owner['user_id'] != getCurrentUserId() && !isAdmin()) {
    die("You are not allowed to delete this review.");
}

// Delete it. Ratings, comments and media are removed automatically
// because of the ON DELETE CASCADE rules in the database.
$stmt = $conn->prepare("DELETE FROM dbproj_movies WHERE movie_id = ?");
$stmt->bind_param("i", $movieId);
$stmt->execute();
$stmt->close();

header("Location: creator_dashboard.php?msg=deleted");
exit();
