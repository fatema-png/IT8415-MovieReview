<?php
session_start();

$_SESSION['user_id'] = 1;

require_once 'db.php';

if (!isset($_GET['id'])) {
    die("Movie ID missing");
}

$movieId = $_GET['id'];

$query = "
SELECT *
FROM dbproj_movies
WHERE movie_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $movieId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Movie not found");
}

$movie = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Post</title>
</head>
<body>

<h1>Edit Movie</h1>

<form action="update_post.php"
      method="POST">

    <input type="hidden"
           name="movie_id"
           value="<?= $movie['movie_id'] ?>">

    <label>Title</label>
    <br>

    <input type="text"
           name="title"
           value="<?= htmlspecialchars($movie['title']) ?>"
           required>

    <br><br>

    <label>Description</label>
    <br>

    <textarea name="description"
              required><?= htmlspecialchars($movie['description']) ?></textarea>

    <br><br>

    <label>Full Review</label>
    <br>

    <textarea name="full_review"><?= htmlspecialchars($movie['full_review']) ?></textarea>

    <br><br>

    <label>Release Year</label>
    <br>

    <input type="number"
           name="release_year"
           value="<?= $movie['release_year'] ?>">

    <br><br>

    <button type="submit">
        Update Post
    </button>

</form>

</body>
</html>