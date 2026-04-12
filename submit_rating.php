<?php
/**
 * ajax/submit_rating.php
 * AJAX endpoint: POST only. Returns JSON.
 * Inserts or updates a rating (1–5 stars).
 * Uses UNIQUE KEY (movie_id, user_id) to prevent duplicate ratings.
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to rate.']);
    exit();
}

$movie_id     = intval($_POST['movie_id'] ?? 0);
$rating_value = intval($_POST['rating']   ?? 0);
$user_id      = getCurrentUserId();

if ($movie_id <= 0 || $rating_value < 1 || $rating_value > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating data.']);
    exit();
}

// INSERT or UPDATE (user can change their rating)
$stmt = $conn->prepare("
    INSERT INTO dbproj_ratings (movie_id, user_id, rating_value)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE rating_value = VALUES(rating_value)
");
$stmt->bind_param('iii', $movie_id, $user_id, $rating_value);

if ($stmt->execute()) {
    // Fetch updated average and count
    $avg_stmt = $conn->prepare("
        SELECT ROUND(AVG(rating_value), 1) AS avg_rating,
               COUNT(*) AS total_ratings
        FROM dbproj_ratings
        WHERE movie_id = ?
    ");
    $avg_stmt->bind_param('i', $movie_id);
    $avg_stmt->execute();
    $stats = $avg_stmt->get_result()->fetch_assoc();
    $avg_stmt->close();

    echo json_encode([
        'success'      => true,
        'message'      => 'Rating saved!',
        'avg_rating'   => $stats['avg_rating'],
        'total_ratings'=> $stats['total_ratings'],
        'your_rating'  => $rating_value,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not save rating.']);
}

$stmt->close();
