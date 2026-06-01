<?php

require_once 'db.php';

header('Content-Type: application/json');

// what the user typed in the search box
$q = trim($_GET['q'] ?? '');

// if nothing was typed return an empty list
if ($q === '') {
    echo json_encode([]);
    exit();
}

// Look for titles that has the typed text published movies only
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
