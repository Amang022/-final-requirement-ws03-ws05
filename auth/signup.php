<?php
// =============================================================
// auth/signup.php — Sign up POST handler
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
$name      = trim($_POST['name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';

if (empty($name) || empty($email) || strlen($password) < 8) {
    flash('error', 'All fields required and password must be at least 8 characters.');
    redirect(BASE_URL . '/index.php');
}

// 3. Check duplicate email
$stmt = mysqli_prepare($conn,
    "SELECT id FROM users WHERE email = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    flash('error', 'Email already exists.');
    redirect(BASE_URL . '/index.php');
}
mysqli_stmt_close($stmt);

// 4. Hash password
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// 5. Insert user (regular, pending approval)
$stmt = mysqli_prepare($conn,
    "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'regular')"
);
mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hashed);
mysqli_stmt_execute($stmt);
$user_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// 6. Log activity (no user logged in, so no user_id)
log_activity(null, 'signup', $email);

flash('success', 'Account created successfully! Please wait for admin approval.');
redirect(BASE_URL . '/index.php');
?>