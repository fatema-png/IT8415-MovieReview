<?php
/**
 * ajax/submit_comment.php
 * AJAX endpoint: POST only. Returns JSON.
 * Called by movie.php via fetch()
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Must be logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to comment.']);
    exit();
}

$movie_id     = intval($_POST['movie_id'] ?? 0);
$comment_text = trim($_POST['comment_text'] ?? '');
$user_id      = getCurrentUserId();

// Server-side validation
if ($movie_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid movie.']);
    exit();
}

if (strlen($comment_text) < 3) {
    echo json_encode(['success' => false, 'message' => 'Comment is too short (min 3 characters).']);
    exit();
}

if (strlen($comment_text) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Comment is too long (max 1000 characters).']);
    exit();
}

// Verify movie exists and is published
$check = $conn->prepare("SELECT movie_id FROM dbproj_movies WHERE movie_id = ? AND status = 'published'");
$check->bind_param('i', $movie_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Movie not found.']);
    exit();
}
$check->close();

// Insert comment using prepared statement
$stmt = $conn->prepare("INSERT INTO dbproj_comments (movie_id, user_id, comment_text) VALUES (?, ?, ?)");
$stmt->bind_param('iis', $movie_id, $user_id, $comment_text);

if ($stmt->execute()) {
    $comment_id = $conn->insert_id;
    echo json_encode([
        'success'    => true,
        'message'    => 'Comment posted!',
        'comment_id' => $comment_id,
        'username'   => htmlspecialchars(getCurrentUsername()),
        'comment'    => htmlspecialchars($comment_text),
        'created_at' => date('M d, Y H:i'),
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to post comment. Please try again.']);
}

$stmt->close();
