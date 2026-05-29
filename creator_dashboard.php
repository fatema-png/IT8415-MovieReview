<?php
session_start();
$_SESSION['user_id'] = 1;
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$query = "
SELECT *
FROM dbproj_movies
WHERE user_id = ?
ORDER BY created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Creator Dashboard</title>

    <style>
        body{
            font-family: Arial;
            background:#f4f4f4;
            padding:20px;
        }

        .card{
            background:white;
            padding:15px;
            margin-bottom:15px;
            border-radius:8px;
        }

        .btn{
            padding:8px 12px;
            background:#333;
            color:white;
            text-decoration:none;
            border-radius:5px;
        }

        .btn-danger{
            background:red;
        }
    </style>
</head>
<body>

<h1>Creator Dashboard</h1>

<br>

<a class="btn" href="create_post.php">
    Add New Post
</a>

<br><br>

<?php while($movie = $result->fetch_assoc()): ?>

<div class="card">

    <h2>
        <?= htmlspecialchars($movie['title']) ?>
    </h2>

    <p>
        <?= htmlspecialchars($movie['description']) ?>
    </p>

    <p>
        Status:
        <strong><?= $movie['status'] ?></strong>
    </p>

    <a class="btn"
       href="edit_post.php?id=<?= $movie['movie_id'] ?>">
        Edit
    </a>

    <a class="btn btn-danger"
       href="delete_post.php?id=<?= $movie['movie_id'] ?>"
       onclick="return confirm('Delete this post?')">
        Delete
    </a>

</div>

<?php endwhile; ?>

</body>
</html>