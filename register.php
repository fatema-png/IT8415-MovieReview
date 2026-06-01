<?php
require_once 'db.php';
require_once 'auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $role_id  = intval($_POST['role_id'] ?? 3); // default: viewer

    // validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role_id, [2, 3])) {
        $error = 'Invalid role selected.';
    } else {
        // check if email already exists
        $check = $conn->prepare("SELECT user_id FROM dbproj_users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'This email is already registered.';
        }
        $check->close();

        // check if username already exists
        if (!$error) {
            $check2 = $conn->prepare("SELECT user_id FROM dbproj_users WHERE username = ?");
            $check2->bind_param('s', $username);
            $check2->execute();
            if ($check2->get_result()->num_rows > 0) {
                $error = 'This username is already taken.';
            }
            $check2->close();
        }

        if (!$error) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO dbproj_users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $username, $email, $hashed, $role_id);

            if ($stmt->execute()) {
                $success = 'Account created successfully! You can now <a href="login.php">log in</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Movie Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #0d0d1a; }
        .register-card {
            background: #111827;
            border-radius: 14px;
            padding: 40px;
            max-width: 460px;
            margin: 60px auto;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }
        .register-card h2 { color: #fff; font-weight: 700; margin-bottom: 8px; }
        .register-card p  { color: #9ca3af; margin-bottom: 28px; }
        .form-label { color: #d1d5db; font-size: 0.88rem; font-weight: 600; }
        .form-control, .form-select {
            background: #1f2937; border: 1px solid #374151;
            color: #fff; border-radius: 8px; padding: 11px 14px;
        }
        .form-control:focus, .form-select:focus {
            background: #1f2937; color: #fff;
            border-color: #ef4444; box-shadow: 0 0 0 2px rgba(239,68,68,0.2);
        }
        .form-select option { background: #1f2937; }
        .btn-register {
            background: #ef4444; color: #fff; border: none;
            width: 100%; padding: 12px; border-radius: 8px;
            font-weight: 600; font-size: 1rem; margin-top: 8px;
        }
        .btn-register:hover { background: #dc2626; color: #fff; }
        .login-link { text-align: center; margin-top: 20px; color: #9ca3af; font-size: 0.9rem; }
        .login-link a { color: #ef4444; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
        .alert-error {
            background: #2a1a1a; border: 1px solid #ef4444;
            color: #fca5a5; border-radius: 8px; padding: 12px 16px;
            margin-bottom: 20px; font-size: 0.9rem;
        }
        .alert-success {
            background: #1a2a1a; border: 1px solid #4caf50;
            color: #a5f3a5; border-radius: 8px; padding: 12px 16px;
            margin-bottom: 20px; font-size: 0.9rem;
        }
        .alert-success a { color: #4caf50; font-weight: 600; }
        .role-hint { color: #6b7280; font-size: 0.8rem; margin-top: 4px; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="register-card">
        <h2>🎬 Create Account</h2>
        <p>Join the Movie Review community</p>

        <?php if ($error): ?>
            <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success">✓ <?= $success ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="register.php" id="registerForm">
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="e.g. john_doe"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Min. 6 characters" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" class="form-control"
                       placeholder="Repeat your password" required>
            </div>
            <div class="mb-4">
                <label class="form-label" for="role_id">Account Type</label>
                <select id="role_id" name="role_id" class="form-select">
                    <option value="3" <?= ($_POST['role_id'] ?? 3) == 3 ? 'selected' : '' ?>>Viewer — Browse and comment on reviews</option>
                    <option value="2" <?= ($_POST['role_id'] ?? 3) == 2 ? 'selected' : '' ?>>Creator — Write and publish movie reviews</option>
                </select>
                <p class="role-hint">Admins are assigned manually by the site administrator.</p>
            </div>
            <button type="submit" class="btn-register">Create Account</button>
        </form>
        <?php endif; ?>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>

    <script>
    // javascript validation before sending the register form
    const form = document.getElementById('registerForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const username = this.username.value.trim();
            const email    = this.email.value.trim();
            const password = this.password.value;
            const confirm  = this.confirm.value;
            const emailOk  = /^\S+@\S+\.\S+$/.test(email);

            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters.');
            } else if (!emailOk) {
                e.preventDefault();
                alert('Please enter a valid email address.');
            } else if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters.');
            } else if (password !== confirm) {
                e.preventDefault();
                alert('The two passwords do not match.');
            }
        });
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
