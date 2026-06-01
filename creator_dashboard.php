<?php
// Creator dashboard: shows the logged-in creator their own reviews.
require_once 'db.php';
require_once 'auth.php';

// Must be a creator or admin to see this page
requireCreator();

$userId = getCurrentUserId();

// ---- Pagination (10 per page) ----
$perPage = 10;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Count how many movies this user has, to work out the number of pages
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM dbproj_movies WHERE user_id = ?");
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$totalMovies = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($totalMovies / $perPage);

// Get this page of the user's movies (newest first)
$stmt = $conn->prepare("
    SELECT m.movie_id, m.title, m.description, m.status, m.view_count, m.created_at,
           g.genre_name
    FROM dbproj_movies m
    LEFT JOIN dbproj_genres g ON m.genre_id = g.genre_id
    WHERE m.user_id = ?
    ORDER BY m.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $userId, $perPage, $offset);
$stmt->execute();
$movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// A small system message after creating / updating / deleting
$messages = [
    'created' => 'Your review was created.',
    'updated' => 'Your review was updated.',
    'deleted' => 'The review was deleted.',
];
$msg = $messages[$_GET['msg'] ?? ''] ?? '';

// An optional image-upload warning passed from save_post.php / update_post.php.
// The review itself was saved; only the poster image had a problem.
$imgError = trim($_GET['imgerror'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Dashboard — Movie Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="m-0">My Reviews</h1>
        <a href="create_post.php" class="btn btn-main">+ Add New Review</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if ($imgError): ?>
        <div class="alert alert-warning">
            ⚠ <?= htmlspecialchars($imgError) ?>
            You can add the poster by editing the review.
        </div>
    <?php endif; ?>

    <?php if (empty($movies)): ?>
        <div class="card-dark text-center text-secondary">
            You have not written any reviews yet. Click "Add New Review" to start.
        </div>
    <?php else: ?>
        <div class="table-responsive card-dark">
            <table class="table table-dark table-hover align-middle m-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Genre</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movies as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['title']) ?></td>
                            <td><?= htmlspecialchars($m['genre_name'] ?? '—') ?></td>
                            <td>
                                <?php if ($m['status'] === 'published'): ?>
                                    <span class="badge bg-success">Published</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($m['view_count']) ?></td>
                            <td><?= date('M d, Y', strtotime($m['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="movie.php?id=<?= $m['movie_id'] ?>" class="btn btn-sm btn-outline-light">View</a>
                                <a href="edit_post.php?id=<?= $m['movie_id'] ?>" class="btn btn-sm btn-outline-light">Edit</a>
                                <form action="delete_post.php" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this review? This cannot be undone.')">
                                    <input type="hidden" name="id" value="<?= $m['movie_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (only shows when there is more than one page) -->
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
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
