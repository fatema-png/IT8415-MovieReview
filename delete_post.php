<?php
session_start();

$_SESSION['user_id'] = 1;

require_once 'db.php';

if (!isset($_GET['id'])) {
    die("Movie ID missing");
}

$movieId = $_GET['id'];

$query = "
DELETE FROM dbproj_movies
WHERE movie_id = ?
";

$stmt = $conn->prepare($query);

$stmt->bind_param("i", $movieId);

$stmt->execute();

header("Location: creator_dashboard.php");
exit();
?>