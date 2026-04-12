<?php
/**
 * admin_reports.php
 * Admin-only. Generates:
 *   Report 1: Most popular movies in a date range (uses stored procedure)
 *   Report 2: Content created by a specific user
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAdmin(); // Redirect non-admins

// ---- Report 1: Popular movies by date range ----
$r1_from    = $_GET['r1_from']    ?? date('Y-01-01'); // Default: Jan 1 current year
$r1_to      = $_GET['r1_to']      ?? date('Y-m-d');   // Default: today
$r1_results = [];
$r1_error   = '';

if (!empty($r1_from) && !empty($r1_to)) {
    if ($r1_from > $r1_to) {
        $r1_error = 'Start date cannot be after end date.';
    } else {
        // Call the stored procedure created by Member 1
        $stmt = $conn->prepare("CALL GetPopularMoviesByDateRange(?, ?)");
        $stmt->bind_param('ss', $r1_from, $r1_to);
        $stmt->execute();
        $r1_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// ---- Report 2: Content by a specific user ----
$r2_user_id  = intval($_GET['r2_user'] ?? 0);
$r2_results  = [];
$r2_user_info = null;

// Fetch all creators for the dropdown
$creators = $conn->query("
    SELECT u.user_id, u.username, u.email
    FROM dbproj_users u
    WHERE u.role_id IN (1, 2)
    ORDER BY u.username
")->fetch_all(MYSQLI_ASSOC);

if ($r2_user_id > 0) {
    // Get user info
    $us = $conn->prepare("SELECT username, email FROM dbproj_users WHERE user_id = ?");
    $us->bind_param('i', $r2_user_id);
    $us->execute();
    $r2_user_info = $us->get_result()->fetch_assoc();
    $us->close();

    // Get all their movies
    $stmt2 = $conn->prepare("
        SELECT
            m.movie_id, m.title, m.description, m.release_year,
            m.status, m.view_count, m.created_at,
            g.genre_name,
            ROUND(AVG(r.rating_value), 1) AS avg_rating,
            COUNT(DISTINCT r.rating_id) AS total_ratings,
            COUNT(DISTINCT c.comment_id) AS total_comments
        FROM dbproj_movies m
        LEFT JOIN dbproj_genres g ON m.genre_id = g.genre_id
        LEFT JOIN dbproj_ratings r ON m.movie_id = r.movie_id
        LEFT JOIN dbproj_comments c ON m.movie_id = c.movie_id
        WHERE m.user_id = ?
        GROUP BY m.movie_id
        ORDER BY m.created_at DESC
    ");
    $stmt2->bind_param('i', $r2_user_id);
    $stmt2->execute();
    $r2_results = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports — Movie Reviews</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #0d0d1a; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; }
        .reports-container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        h1 { color: #fff; margin-bottom: 6px; }
        .subtitle { color: #888; margin-bottom: 36px; }

        /* Report cards */
        .report-card { background: #1e1e2e; border-radius: 12px; padding: 28px; margin-bottom: 36px; }
        .report-card h2 { color: #e50914; font-size: 1.15rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 18px; }

        /* Filter rows */
        .filter-row { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; margin-bottom: 22px; }
        .filter-row label { color: #aaa; font-size: 0.82rem; font-weight: 600; text-transform: uppercase; display: block; margin-bottom: 5px; }
        .filter-row input, .filter-row select {
            padding: 9px 14px; border-radius: 6px; border: 1px solid #444;
            background: #2a2a3e; color: #fff; font-size: 0.93rem;
        }
        .btn-run { background: #e50914; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-run:hover { background: #b0060f; }

        /* Tables */
        .report-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .report-table th { background: #2a2a3e; color: #ccc; text-align: left; padding: 10px 14px; font-size: 0.83rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .report-table td { padding: 11px 14px; border-bottom: 1px solid #2a2a3e; color: #ddd; font-size: 0.92rem; }
        .report-table tr:hover td { background: #242438; }
        .rank { font-weight: 700; color: #f5c518; }
        .stars-small { color: #f5c518; }
        .status-badge { padding: 3px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 600; }
        .status-published { background: #1a4a1a; color: #4caf50; }
        .status-draft     { background: #4a3a1a; color: #ff9800; }
        .empty-msg { color: #666; text-align: center; padding: 30px 0; }

        /* Summary bar */
        .summary-bar { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px; }
        .summary-stat { background: #2a2a3e; border-radius: 8px; padding: 14px 20px; }
        .summary-stat .val { font-size: 1.6rem; font-weight: 700; color: #e50914; }
        .summary-stat .lbl { font-size: 0.8rem; color: #888; margin-top: 2px; }

        .error-msg { color: #e50914; background: #2a1a1a; border: 1px solid #e50914; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="reports-container">
        <h1>📊 Admin Reports</h1>
        <p class="subtitle">Generate reports for content analysis and moderation.</p>

        <!-- ========== REPORT 1: Popular Movies by Date Range ========== -->
        <div class="report-card">
            <h2>Report 1 — Most Popular Movies in Date Range</h2>
            <form method="GET" action="admin_reports.php">
                <input type="hidden" name="r2_user" value="<?= $r2_user_id ?>">
                <div class="filter-row">
                    <div>
                        <label for="r1_from">From Date</label>
                        <input type="date" id="r1_from" name="r1_from" value="<?= htmlspecialchars($r1_from) ?>">
                    </div>
                    <div>
                        <label for="r1_to">To Date</label>
                        <input type="date" id="r1_to" name="r1_to" value="<?= htmlspecialchars($r1_to) ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn-run">Run Report</button>
                    </div>
                </div>
            </form>

            <?php if ($r1_error): ?>
                <p class="error-msg">⚠ <?= htmlspecialchars($r1_error) ?></p>
            <?php elseif (!empty($r1_results)): ?>
                <div class="summary-bar">
                    <div class="summary-stat">
                        <div class="val"><?= count($r1_results) ?></div>
                        <div class="lbl">Movies Found</div>
                    </div>
                    <div class="summary-stat">
                        <div class="val"><?= number_format(array_sum(array_column($r1_results, 'view_count'))) ?></div>
                        <div class="lbl">Total Views</div>
                    </div>
                    <div class="summary-stat">
                        <div class="val"><?= number_format(array_sum(array_column($r1_results, 'total_ratings'))) ?></div>
                        <div class="lbl">Total Ratings</div>
                    </div>
                    <div class="summary-stat">
                        <div class="val"><?= number_format(array_sum(array_column($r1_results, 'total_comments'))) ?></div>
                        <div class="lbl">Total Comments</div>
                    </div>
                </div>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>Reviewer</th>
                            <th>Views</th>
                            <th>Avg Rating</th>
                            <th>Ratings</th>
                            <th>Comments</th>
                            <th>Published</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($r1_results as $i => $row): ?>
                            <tr>
                                <td class="rank">#<?= $i + 1 ?></td>
                                <td>
                                    <a href="movie.php?id=<?= $row['movie_id'] ?>"
                                       style="color:#e50914; text-decoration:none;">
                                        <?= htmlspecialchars($row['title']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['genre_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['reviewer']) ?></td>
                                <td>👁 <?= number_format($row['view_count']) ?></td>
                                <td>
                                    <?php if ($row['avg_rating']): ?>
                                        <span class="stars-small"><?= str_repeat('★', round($row['avg_rating'])) ?></span>
                                        <?= $row['avg_rating'] ?>
                                    <?php else: ?>
                                        <span style="color:#555;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $row['total_ratings'] ?></td>
                                <td><?= $row['total_comments'] ?></td>
                                <td style="color:#888; font-size:0.85rem;">
                                    <?= date('M d, Y', strtotime($row['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <p class="empty-msg">No published movies found in that date range.</p>
            <?php endif; ?>
        </div>

        <!-- ========== REPORT 2: Content by User ========== -->
        <div class="report-card">
            <h2>Report 2 — Content by Creator</h2>
            <form method="GET" action="admin_reports.php">
                <input type="hidden" name="r1_from" value="<?= htmlspecialchars($r1_from) ?>">
                <input type="hidden" name="r1_to"   value="<?= htmlspecialchars($r1_to) ?>">
                <div class="filter-row">
                    <div>
                        <label for="r2_user">Select Creator</label>
                        <select id="r2_user" name="r2_user">
                            <option value="0">— Choose a creator —</option>
                            <?php foreach ($creators as $creator): ?>
                                <option value="<?= $creator['user_id'] ?>"
                                    <?= $r2_user_id == $creator['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($creator['username']) ?>
                                    (<?= htmlspecialchars($creator['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn-run">Run Report</button>
                    </div>
                </div>
            </form>

            <?php if ($r2_user_id > 0 && $r2_user_info): ?>
                <div class="summary-bar">
                    <div class="summary-stat">
                        <div class="val"><?= count($r2_results) ?></div>
                        <div class="lbl">Total Reviews</div>
                    </div>
                    <div class="summary-stat">
                        <div class="val"><?= count(array_filter($r2_results, fn($r) => $r['status'] === 'published')) ?></div>
                        <div class="lbl">Published</div>
                    </div>
                    <div class="summary-stat">
                        <div class="val"><?= number_format(array_sum(array_column($r2_results, 'view_count'))) ?></div>
                        <div class="lbl">Total Views</div>
                    </div>
                    <div class="summary-stat">
                        <div class="val"><?= number_format(array_sum(array_column($r2_results, 'total_comments'))) ?></div>
                        <div class="lbl">Total Comments</div>
                    </div>
                </div>

                <?php if (empty($r2_results)): ?>
                    <p class="empty-msg">This user has not created any reviews yet.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Genre</th>
                                <th>Year</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Avg Rating</th>
                                <th>Comments</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($r2_results as $row): ?>
                                <tr>
                                    <td>
                                        <a href="movie.php?id=<?= $row['movie_id'] ?>"
                                           style="color:#e50914; text-decoration:none;">
                                            <?= htmlspecialchars($row['title']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($row['genre_name'] ?? '—') ?></td>
                                    <td><?= $row['release_year'] ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status'] ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>👁 <?= number_format($row['view_count']) ?></td>
                                    <td>
                                        <?php if ($row['avg_rating']): ?>
                                            <span class="stars-small"><?= str_repeat('★', round($row['avg_rating'])) ?></span>
                                            <?= $row['avg_rating'] ?>
                                        <?php else: ?>
                                            <span style="color:#555;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['total_comments'] ?></td>
                                    <td style="color:#888; font-size:0.85rem;">
                                        <?= date('M d, Y', strtotime($row['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <?php elseif ($r2_user_id > 0): ?>
                <p class="empty-msg">User not found.</p>
            <?php else: ?>
                <p class="empty-msg">Select a creator from the dropdown to generate the report.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
