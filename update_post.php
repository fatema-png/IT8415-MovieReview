<?php
session_start();

$_SESSION['user_id'] = 1;

require_once 'db.php';

$movieId = $_POST['movie_id'];

$title = trim($_POST['title']);
$description = trim($_POST['description']);
$fullReview = trim($_POST['full_review']);
$releaseYear = $_POST['release_year'];

$query = "
UPDATE dbproj_movies

SET
title = ?,
description = ?,
full_review = ?,
release_year = ?

WHERE movie_id = ?
";

$stmt = $conn->prepare($query);

$stmt->bind_param(
    "sssii",
    $title,
    $description,
    $fullReview,
    $releaseYear,
    $movieId
);

$stmt->execute();

header("Location: creator_dashboard.php");
exit();
?>