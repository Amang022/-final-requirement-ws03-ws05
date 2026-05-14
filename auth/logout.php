<?php
// =============================================================
// auth/logout.php — Secure logout handler
// =============================================================

define('BASE_URL', '..');
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['user_id'])) {
    log_activity($_SESSION['user_id'], 'logout', $_SESSION['user_name'] ?? '');
}

// Clear all session data
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destroy the session
session_destroy();

redirect(BASE_URL . '/index.php');
