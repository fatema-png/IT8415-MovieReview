<?php
// Admin page: manage ALL movie reviews on the site.
require_once 'db.php';
require_once 'auth.php';

// Only admins are allowed here
requireAdmin();

$msg = '';

// ---- Handle publish / unpublish / delete actions (POST only) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = intval($_POST['id']);

    if ($_POST['action'] === 'publish' || $_POST['action'] === 'unpublish') {
        $newStatus = $_POST['action'] === 'publish' ? 'published' : 'draft';
        $stmt = $conn->prepare("UPDATE dbproj_movies SET status = ? WHERE movie_id = ?");
        $stmt->bind_param("si", $newStatus, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_content.php?msg=status");
        exit();
    }

    if ($_POST['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM dbproj_movies WHERE movie_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_content.php?msg=deleted");
        exit();
    }
}

// System message shown after an action
$messages = [
    'status'  => 'The movie status was updated.',
    'deleted' => 'The movie was removed from the site.',
];
$msg = $messages[$_GET['msg'] ?? ''] ?? '';

// ---- Pagination (10 per page) ----
$perPage = 10;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$totalMovies = $conn->query("SELECT COUNT(*) AS total FROM dbproj_movies")
                    ->fetch_assoc()['total'];
$totalPages  = ceil($totalMovies / $perPage);

// Get this page of movies (all creators)
$stmt = $conn->prepare("
    SELECT m.movie_id, m.title, m.status, m.view_count, m.created_at,
           u.username AS creator, g.genre_name
    FROM dbproj_movies m
    JOIN dbproj_users u  ON m.user_id  = u.user_id
    LEFT JOIN dbproj_genres g ON m.genre_id = g.genre_id
    ORDER BY m.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Moderation — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
    <h1 class="mb-4">🛠 Content Moderation</h1>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="table-responsive card-dark">
        <table class="table table-dark table-hover align-middle m-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Creator</th>
                    <th>Genre</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movies as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['title']) ?></td>
                        <td><?= htmlspecialchars($m['creator']) ?></td>
                        <td><?= htmlspecialchars($m['genre_name'] ?? '—') ?></td>
                        <td>
                            <?php if ($m['status'] === 'published'): ?>
                                <span class="badge bg-success">Published</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($m['view_count']) ?></td>
                        <td class="text-end">
                            <a href="movie.php?id=<?= $m['movie_id'] ?>" class="btn btn-sm btn-outline-light">View</a>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="id" value="<?= $m['movie_id'] ?>">
                                <?php if ($m['status'] === 'published'): ?>
                                    <button type="submit" name="action" value="unpublish" class="btn btn-sm btn-outline-warning">Unpublish</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="publish" class="btn btn-sm btn-outline-success">Publish</button>
                                <?php endif; ?>
                            </form>
                            <form method="POST" class="d-inline"
                                  onsubmit="return confirm('Remove this movie from the site? This cannot be undone.')">
                                <input type="hidden" name="id" value="<?= $m['movie_id'] ?>">
                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
