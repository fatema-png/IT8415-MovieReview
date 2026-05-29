<?php
session_start();
$_SESSION['user_id'] = 1;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Post</title>
</head>
<body>

<h1>Create Movie Post</h1>

<form action="save_post.php"
      method="POST"
      enctype="multipart/form-data">

    <label>Title</label>
    <br>
    <input type="text"
           name="title"
           required>
    <br><br>

    <label>Description</label>
    <br>
    <textarea name="description"
              required></textarea>
    <br><br>

    <label>Full Review</label>
    <br>
    <textarea name="full_review"></textarea>
    <br><br>

    <label>Release Year</label>
    <br>
    <input type="number"
           name="release_year">
    <br><br>

    <label>Upload Image</label>
    <br>
    <input type="file"
           name="image">
    <br><br>

    <button type="submit">
        Save Post
    </button>

</form>

</body>
</html>