<?php
// =============================================================
// auth/forgot.php — Forgot password handler
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

// 2. Sanitize input
$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    flash('error', 'Email is required.');
    redirect(BASE_URL . '/index.php');
}

// 3. Check if email exists
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    // Don't reveal if email exists or not for security
    flash('success', 'If the email exists, a password reset link has been sent.');
    redirect(BASE_URL . '/index.php');
}

// For simplicity, since no email system, just show a message
// In a real app, send email with reset token
flash('success', 'Password reset link sent to your email (simulated).');
redirect(BASE_URL . '/index.php');
?>