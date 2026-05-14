<?php
// =============================================================
// includes/csrf.php — CSRF token generation & validation
// =============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_name('AGRI_SESSID');
    session_start();
}

/**
 * Generate a CSRF token and store it in the session.
 * Returns the token string.
 */
function csrf_generate(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field (call inside every <form>).
 */
function csrf_field(): void {
    $token = htmlspecialchars(csrf_generate(), ENT_QUOTES, 'UTF-8');
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate the token sent via POST.
 * Regenerates a fresh token after each validation (one-time use).
 * Terminates with 403 on failure.
 */
function csrf_validate(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';

    if (empty($stored) || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        die('Invalid or missing CSRF token.');
    }

    // Rotate token after use
    unset($_SESSION['csrf_token']);
}
