<?php
// =============================================================
// auth/login.php — Login POST handler (secured)
// =============================================================

define('BASE_URL', '..');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../auth/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/index.php');
}

// 1. Validate CSRF
csrf_validate();

// 2. Check rate limiting (max 5 attempts, 5 minute lockout)
if (is_rate_limited(5, 300)) {
    flash('error', 'Too many failed login attempts. Please wait 5 minutes before trying again.');
    redirect(BASE_URL . '/index.php');
}

// 3. Sanitize inputs
$email    = sanitize_input($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    flash('error', 'Email and password are required.');
    redirect(BASE_URL . '/index.php');
}

// 4. Validate email format
if (!is_valid_email($email)) {
    record_failed_login();
    flash('error', 'Invalid email or password.');
    redirect(BASE_URL . '/index.php');
}

// 5. Fetch user by email (prepared statement prevents SQL injection)
$stmt = mysqli_prepare($conn,
    "SELECT id, name, email, password, role, is_archived
     FROM users WHERE email = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// 6. Verify password & account status
if (!$user || !password_verify($password, $user['password'])) {
    record_failed_login();
    // Generic error message — don't reveal whether email exists
    flash('error', 'Invalid email or password.');
    redirect(BASE_URL . '/index.php');
}

if ($user['is_archived']) {
    record_failed_login();
    flash('error', 'Your account has been deactivated. Contact an administrator.');
    redirect(BASE_URL . '/index.php');
}

// 7. Successful login — clear rate limiter
clear_login_attempts();

// 8. Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// 9. Store user data in session
$_SESSION['user_id']       = $user['id'];
$_SESSION['user_name']     = $user['name'];
$_SESSION['role']          = $user['role'];
$_SESSION['last_activity'] = time();
$_SESSION['fingerprint']   = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? ''));

log_activity($user['id'], 'login', $user['email']);

redirect(BASE_URL . '/dashboard.php');
