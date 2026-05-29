<?php
/**
 * ajax/delete_comment.php
 * AJAX endpoint: POST only. Admin-only. Returns JSON.
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

$comment_id = intval($_POST['comment_id'] ?? 0);

if ($comment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid comment ID.']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM dbproj_comments WHERE comment_id = ?");
$stmt->bind_param('i', $comment_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Comment removed.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Comment not found or already deleted.']);
}

$stmt->close();
