<?php
// Admin page: manage the site's users (change role or delete).
require_once 'db.php';
require_once 'auth.php';

requireAdmin();

$currentAdminId = getCurrentUserId();

// ---- Change a user's role ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $uid     = intval($_POST['user_id']);
    $roleId  = intval($_POST['role_id']);

    // Only allow the three real role ids, and never let an admin change their own role
    if (in_array($roleId, [1, 2, 3]) && $uid !== $currentAdminId) {
        $stmt = $conn->prepare("UPDATE dbproj_users SET role_id = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $roleId, $uid);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_users.php?msg=role");
    exit();
}

// ---- Delete a user ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = intval($_POST['delete_user']);
    // An admin cannot delete their own account
    if ($uid !== $currentAdminId) {
        $stmt = $conn->prepare("DELETE FROM dbproj_users WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_users.php?msg=deleted");
    exit();
}

// System message
$messages = [
    'role'    => 'The user role was updated.',
    'deleted' => 'The user was deleted.',
];
$msg = $messages[$_GET['msg'] ?? ''] ?? '';

// Load all users with their role name
$users = $conn->query("
    SELECT u.user_id, u.username, u.email, u.role_id, r.role_name, u.created_at
    FROM dbproj_users u
    JOIN dbproj_roles r ON u.role_id = r.role_id
    ORDER BY u.user_id
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
    <h1 class="mb-4">👥 Manage Users</h1>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="table-responsive card-dark">
        <table class="table table-dark table-hover align-middle m-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['user_id'] ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <!-- The role dropdown submits straight away when changed -->
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                <select name="role_id" class="form-select form-select-sm" style="width:auto;"
                                        <?= $u['user_id'] === $currentAdminId ? 'disabled' : '' ?>
                                        onchange="this.form.submit()">
                                    <option value="1" <?= $u['role_id'] == 1 ? 'selected' : '' ?>>Admin</option>
                                    <option value="2" <?= $u['role_id'] == 2 ? 'selected' : '' ?>>Creator</option>
                                    <option value="3" <?= $u['role_id'] == 3 ? 'selected' : '' ?>>Viewer</option>
                                </select>
                                <input type="hidden" name="change_role" value="1">
                            </form>
                        </td>
                        <td class="text-end">
                            <?php if ($u['user_id'] === $currentAdminId): ?>
                                <span class="text-secondary">You</span>
                            <?php else: ?>
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this user?')">
                                    <input type="hidden" name="delete_user" value="<?= $u['user_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-secondary mt-3" style="font-size:0.9rem;">
        Note: deleting a user who created movies may be blocked by the database
        if those movies still exist. Remove their movies first if needed.
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
