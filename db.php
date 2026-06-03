<?php
// detect environment: is this running on the deployment server or local XAMPP?
$host = $_SERVER['SERVER_ADDR'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = in_array($host, ['127.0.0.1', '::1', 'localhost'])
        || strpos($host, 'localhost') !== false;

if ($isLocal) {
    // local development (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'dbproj_movies');
} else {
    // production (tutor server)
    // Credentials live in config.prod.php, which is git-ignored and uploaded
    // to the server separately, so they are never committed to the repo.
    require __DIR__ . '/config.prod.php';
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
