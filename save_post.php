<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Login required");
}

$userId = $_SESSION['user_id'];

$title = trim($_POST['title']);
$description = trim($_POST['description']);
$fullReview = trim($_POST['full_review']);
$releaseYear = $_POST['release_year'];

$imagePath = "";

if (isset($_FILES['image']) &&
    $_FILES['image']['error'] == 0) {

    $fileName = time() . "_" .
                basename($_FILES['image']['name']);

    $target = "uploads/" . $fileName;

    move_uploaded_file(
        $_FILES['image']['tmp_name'],
        $target
    );

    $imagePath = $target;
}

$query = "
INSERT INTO dbproj_movies
(user_id, title, description, full_review,
 release_year, status)

VALUES
(?, ?, ?, ?, ?, 'published')
";

$stmt = $conn->prepare($query);

$stmt->bind_param(
    "isssi",
    $userId,
    $title,
    $description,
    $fullReview,
    $releaseYear
);

$stmt->execute();

header("Location: creator_dashboard.php");
exit();
?>