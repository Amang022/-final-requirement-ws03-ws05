<?php
// =============================================================
// auth/session.php — Session guard with security hardening
// Include at the top of every protected page.
// =============================================================

// --- Session timeout (30 minutes of inactivity) ---
define('SESSION_TIMEOUT', 1800);

if (session_status() === PHP_SESSION_NONE) {
    // Use a custom session name (don't expose default "PHPSESSID")
    session_name('AGRI_SESSID');

    session_set_cookie_params([
        'lifetime' => 0,           // Session cookie (expires when browser closes)
        'path'     => '/',
        'secure'   => false,       // Set true when using HTTPS
        'httponly' => true,        // Prevent JavaScript access to session cookie
        'samesite' => 'Strict',   // Prevent CSRF via cross-site requests
    ]);

    session_start();
}

// --- Session timeout check ---
if (!empty($_SESSION['user_id'])) {
    $last_activity = $_SESSION['last_activity'] ?? 0;

    if ($last_activity > 0 && (time() - $last_activity) > SESSION_TIMEOUT) {
        // Session expired — destroy and redirect
        $_SESSION = [];
        session_destroy();

        // Restart session to set flash message
        session_name('AGRI_SESSID');
        session_start();
        $_SESSION['flash']['error'] = 'Your session has expired. Please log in again.';

        header('Location: ' . (defined('BASE_URL') ? BASE_URL : '.') . '/index.php');
        exit;
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = time();

    // --- Session fingerprint check (prevent session hijacking) ---
    $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? ''));

    if (empty($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = $fingerprint;
    } elseif (!hash_equals($_SESSION['fingerprint'], $fingerprint)) {
        // Fingerprint mismatch — possible session hijacking
        $_SESSION = [];
        session_destroy();

        session_name('AGRI_SESSID');
        session_start();
        $_SESSION['flash']['error'] = 'Session security violation. Please log in again.';

        header('Location: ' . (defined('BASE_URL') ? BASE_URL : '.') . '/index.php');
        exit;
    }
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
