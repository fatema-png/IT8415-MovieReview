<?php
/**
 * search_suggestions.php
 * Small AJAX endpoint for the live search box on search.php.
 * It returns a JSON list of published movie titles that match what
 * the user is typing.
 */
require_once 'db.php';

header('Content-Type: application/json');

// What the user typed in the search box
$q = trim($_GET['q'] ?? '');

// If nothing was typed, return an empty list
if ($q === '') {
    echo json_encode([]);
    exit();
}

// Look for titles that contain the typed text (published movies only)
$like = '%' . $q . '%';

$stmt = $conn->prepare("
    SELECT movie_id, title
    FROM dbproj_movies
    WHERE status = 'published' AND title LIKE ?
    ORDER BY view_count DESC
    LIMIT 6
");
$stmt->bind_param('s', $like);
$stmt->execute();
$movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($movies);
