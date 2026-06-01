<?php
require_once 'db.php';
require_once 'auth.php';

// already logged in then go home
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// show any message set before we were redirected here (like ccess denied)
$error = getFlash() ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password, role_id FROM dbproj_users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id']  = $user['role_id'];
            header("Location: index.php");
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Movie Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #0d0d1a; }
        .login-card {
            background: #111827;
            border-radius: 14px;
            padding: 40px;
            max-width: 420px;
            margin: 80px auto;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }
        .login-card h2 { color: #fff; font-weight: 700; margin-bottom: 8px; }
        .login-card p  { color: #9ca3af; margin-bottom: 28px; }
        .form-label    { color: #d1d5db; font-size: 0.88rem; font-weight: 600; }
        .form-control  {
            background: #1f2937; border: 1px solid #374151;
            color: #fff; border-radius: 8px; padding: 11px 14px;
        }
        .form-control:focus {
            background: #1f2937; color: #fff;
            border-color: #ef4444; box-shadow: 0 0 0 2px rgba(239,68,68,0.2);
        }
        .btn-login {
            background: #ef4444; color: #fff; border: none;
            width: 100%; padding: 12px; border-radius: 8px;
            font-weight: 600; font-size: 1rem; margin-top: 8px;
        }
        .btn-login:hover { background: #dc2626; color: #fff; }
        .register-link  { text-align: center; margin-top: 20px; color: #9ca3af; font-size: 0.9rem; }
        .register-link a { color: #ef4444; text-decoration: none; font-weight: 600; }
        .register-link a:hover { text-decoration: underline; }
        .alert-error {
            background: #2a1a1a; border: 1px solid #ef4444;
            color: #fca5a5; border-radius: 8px; padding: 12px 16px;
            margin-bottom: 20px; font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="login-card">
        <h2>🎬 Welcome Back</h2>
        <p>Sign in to your account</p>

        <?php if ($error): ?>
            <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm">
            <div class="mb-3">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Create one</a>
        </div>
    </div>

    <script>
    // javascript validation before sending the login form
    document.getElementById('loginForm').addEventListener('submit', function (e) {
        const email = this.email.value.trim();
        const pass  = this.password.value;
        // simple email pattern check
        const emailOk = /^\S+@\S+\.\S+$/.test(email);
        if (!emailOk) {
            e.preventDefault();
            alert('Please enter a valid email address.');
        } else if (pass.length === 0) {
            e.preventDefault();
            alert('Please enter your password.');
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
