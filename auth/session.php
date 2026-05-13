<?php
// =============================================================
// auth/session.php — Session guard
// Include at the top of every protected page.
// =============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set true on HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/**
 * Require the user to be logged in.
 * Optionally restrict to one or more roles.
 *
 * @param string|string[] $roles  Allowed role(s); empty = any logged-in user.
 */
function require_login($roles = []): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    if (!empty($roles)) {
        $roles = (array)$roles;
        if (!in_array($_SESSION['role'], $roles, true)) {
            http_response_code(403);
            die('Access denied.');
        }
    }
}

/**
 * Convenience: current logged-in user's role.
 */
function current_role(): string {
    return $_SESSION['role'] ?? '';
}

/**
 * Convenience: current logged-in user's ID.
 */
function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}
