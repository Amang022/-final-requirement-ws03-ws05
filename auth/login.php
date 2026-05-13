<?php
// =============================================================
// auth/login.php — Login POST handler
// =============================================================

define('BASE_URL', '..');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/index.php');
}

// 1. Validate CSRF
csrf_validate();

// 2. Sanitize inputs
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    flash('error', 'Email and password are required.');
    redirect(BASE_URL . '/index.php');
}

// 3. Fetch user by email (prepared statement prevents SQL injection)
$stmt = mysqli_prepare($conn,
    "SELECT id, name, email, password, role, is_archived
     FROM users WHERE email = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// 4. Verify password & account status
if (!$user || !password_verify($password, $user['password'])) {
    flash('error', 'Invalid email or password.');
    redirect(BASE_URL . '/index.php');
}

if ($user['is_archived']) {
    flash('error', 'Your account has been deactivated. Contact an administrator.');
    redirect(BASE_URL . '/index.php');
}

// 5. Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// 6. Store user data in session
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['role']      = $user['role'];

log_activity($user['id'], 'login', $user['email']);

redirect(BASE_URL . '/dashboard.php');
