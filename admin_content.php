<?php
require_once 'db.php';
require_once 'auth.php';

// DEBUGGING: Add this right here
echo "<pre>";
echo "Is Admin? " . (isAdmin() ? "YES" : "NO") . "<br>";
var_dump($_SESSION); // This will show exactly what your session holds
echo "</pre>";
die("STOPPED HERE"); 



// Handle the Publish/Unpublish toggle
if (isset($_GET['action']) && isset($_GET['id'])) {
    $new_status = ($_GET['action'] == 'publish') ? 'published' : 'pending';
    $stmt = $conn->prepare("UPDATE dbproj_movies SET status = ? WHERE movie_id = ?");
    $stmt->bind_param("si", $new_status, $_GET['id']);
    $stmt->execute();
    header("Location: admin_content.php?success=1");
    exit();
}

// Fetch all movies
$result = $conn->query("SELECT * FROM dbproj_movies ORDER BY movie_id DESC");
?>

<!DOCTYPE html>
<html>
<head><title>Admin Content Moderation</title></head>
<body>
    <h1>Admin Moderation</h1>
    <a href="index.php">Back to Home</a>
    
    <table border="1">
        <tr>
            <th>Title</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo ucfirst($row['status']); ?></td>
            <td>
                <?php if ($row['status'] == 'pending'): ?>
                    <a href="admin_content.php?action=publish&id=<?php echo $row['movie_id']; ?>">Publish</a>
                <?php else: ?>
                    <a href="admin_content.php?action=unpublish&id=<?php echo $row['movie_id']; ?>">Unpublish</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>