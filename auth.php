<?php
// session helper
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

function isCreator() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
}

// creator OR an admin allowed to manage content
function canCreate() {
    return isCreator() || isAdmin();
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? 'Guest';
}

// store a one time message to show on the page we redirect to
function setFlash($message) {
    $_SESSION['flash'] = $message;
}

// read and clear the one time message (returns null if none)
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $message = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $message;
    }
    return null;
}

// redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('Please sign in to continue.');
        header("Location: login.php");
        exit();
    }
}

// redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        setFlash('Access denied: that page is for administrators only.');
        header("Location: index.php");
        exit();
    }
}

// redirect if the user is not a creator or an admin
function requireCreator() {
    if (!canCreate()) {
        setFlash('Access denied: that page is for content creators only.');
        header("Location: " . (isLoggedIn() ? "index.php" : "login.php"));
        exit();
    }
}
