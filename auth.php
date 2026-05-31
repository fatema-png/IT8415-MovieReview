<?php
// Session helper - depends on Member 2's session structure
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

// A creator OR an admin is allowed to manage content
function canCreate() {
    return isCreator() || isAdmin();
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? 'Guest';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

// Redirect if the user is not a creator or an admin
function requireCreator() {
    if (!canCreate()) {
        header("Location: login.php");
        exit();
    }
}
