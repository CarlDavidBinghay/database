<?php
session_start();
require_once(__DIR__ . '/users_store.php');

function regenerateSession(): void { session_regenerate_id(true); }

// ── Login handler ──────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $password = $_POST['password'] ?? '';
    $user     = verifyUser($email, $password);

    if ($user) {
        regenerateSession();
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['user_email']   = $email;
        $_SESSION['user_name']    = $user['name'];
        $_SESSION['user_role']    = $user['role'];
        $_SESSION['logged_in_at'] = time();
        if ($user['role'] === 'service_admin') { $_SESSION['assigned_service'] = $user['service'] ?? null; }

        switch ($user['role']) {
            case 'developer': case 'admin': header('Location: /queue-system/admin/dashboard.php'); break;
            case 'service_admin': header('Location: /queue-system/admin/manage-queue.php'); break;
            default: header('Location: /queue-system/public/index.php');
        }
        exit();
    } else {
        $_SESSION['error'] = 'Invalid email or password.';
        header('Location: /queue-system/public/login.php');
        exit();
    }
}

// ── Registration handler ───────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $lastName  = filter_input(INPUT_POST, 'last_name',  FILTER_SANITIZE_SPECIAL_CHARS);
    $email     = filter_input(INPUT_POST, 'email',      FILTER_SANITIZE_EMAIL);
    $phone     = filter_input(INPUT_POST, 'phone',      FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $password  = $_POST['password'] ?? '';
    try {
        $newId = registerUser($firstName, $lastName, $email, $phone, $password);
        regenerateSession();
        $_SESSION['user_id']      = $newId;
        $_SESSION['user_email']   = $email;
        $_SESSION['user_name']    = trim($firstName . ' ' . $lastName);
        $_SESSION['user_role']    = 'user';
        $_SESSION['logged_in_at'] = time();
    } catch (Exception $e) { error_log($e->getMessage()); }
    header('Location: /queue-system/public/index.php');
    exit();
}

// ── Logout handler ─────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: /queue-system/public/index.php');
    exit();
}

// ── Helpers ────────────────────────────────────────────────────
function isLoggedIn(): bool   { return isset($_SESSION['user_id']); }
function isAdmin(): bool      { return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin','developer']); }
function isDeveloper(): bool  { return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'developer'; }
function isServiceAdmin(): bool { return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'service_admin'; }
function getAssignedService(): ?string { return $_SESSION['assigned_service'] ?? null; }
function getUserName(): string { return $_SESSION['user_name'] ?? 'Guest'; }
function getUserRole(): string { return $_SESSION['user_role'] ?? 'guest'; }

function requireLogin(string $to='/queue-system/public/login.php'): void {
    if (!isLoggedIn()) { $_SESSION['error']='Please log in to continue.'; header('Location:'.$to); exit(); }
}
function requireAdmin(string $to='/queue-system/public/login.php'): void {
    requireLogin($to);
    if (!isAdmin() && !isServiceAdmin()) { header('Location:/queue-system/public/index.php'); exit(); }
}
function requireDeveloperOrAdmin(string $to='/queue-system/public/login.php'): void {
    requireLogin($to);
    if (!isAdmin()) { header('Location:/queue-system/public/index.php'); exit(); }
}