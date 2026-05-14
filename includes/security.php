<?php
// =============================================================
// includes/security.php — Security headers & hardening
// Include at the top of every page before any output.
// =============================================================

/**
 * Send security headers to protect against common web attacks.
 * Call this before any HTML output.
 */
function send_security_headers(): void {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');

    // Prevent MIME-type sniffing
    header('X-Content-Type-Options: nosniff');

    // Enable XSS filtering in older browsers
    header('X-XSS-Protection: 1; mode=block');

    // Referrer policy — don't leak full URLs to external sites
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions policy — restrict browser features
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    // Prevent caching of sensitive pages (authenticated pages)
    if (!empty($_SESSION['user_id'])) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }
}

/**
 * Simple session-based login rate limiter.
 * Returns true if the user is currently rate-limited.
 *
 * @param  int $maxAttempts  Max failed attempts allowed.
 * @param  int $lockoutSecs  Lockout duration in seconds.
 * @return bool              True if rate-limited (block the attempt).
 */
function is_rate_limited(int $maxAttempts = 5, int $lockoutSecs = 300): bool {
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $lockTime = $_SESSION['login_lockout'] ?? 0;

    // If currently locked out, check if lockout has expired
    if ($lockTime > 0 && time() < $lockTime) {
        return true;
    }

    // Reset if lockout expired
    if ($lockTime > 0 && time() >= $lockTime) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_lockout']  = 0;
        return false;
    }

    return $attempts >= $maxAttempts;
}

/**
 * Record a failed login attempt.
 *
 * @param  int $maxAttempts  Max attempts before lockout.
 * @param  int $lockoutSecs  Duration of lockout in seconds.
 */
function record_failed_login(int $maxAttempts = 5, int $lockoutSecs = 300): void {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;

    if ($_SESSION['login_attempts'] >= $maxAttempts) {
        $_SESSION['login_lockout'] = time() + $lockoutSecs;
    }
}

/**
 * Clear failed login attempts after a successful login.
 */
function clear_login_attempts(): void {
    unset($_SESSION['login_attempts'], $_SESSION['login_lockout']);
}

/**
 * Validate an email address strictly.
 *
 * @param  string $email
 * @return bool
 */
function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && strlen($email) <= 150;
}

/**
 * Validate password strength.
 * Requirements: min 8 chars, at least 1 uppercase, 1 lowercase, 1 digit.
 *
 * @param  string $password
 * @return string|null  Error message if invalid, null if valid.
 */
function validate_password(string $password): ?string {
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }
    return null;
}

/**
 * Sanitize a string for safe output — trim and remove null bytes.
 *
 * @param  string $input
 * @return string
 */
function sanitize_input(string $input): string {
    return trim(str_replace(chr(0), '', $input));
}
