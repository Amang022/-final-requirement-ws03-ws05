<?php
// =============================================================
// auth/signup.php — Sign up POST handler (secured)
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

// 2. Sanitize inputs
$name      = sanitize_input($_POST['name'] ?? '');
$email     = sanitize_input($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';

// 3. Validate required fields
if (empty($name) || empty($email) || empty($password)) {
    flash('error', 'All fields are required.');
    redirect(BASE_URL . '/index.php');
}

// 4. Validate name length (prevent excessively long names)
if (strlen($name) > 100) {
    flash('error', 'Name must not exceed 100 characters.');
    redirect(BASE_URL . '/index.php');
}

// 5. Validate email format
if (!is_valid_email($email)) {
    flash('error', 'Please enter a valid email address.');
    redirect(BASE_URL . '/index.php');
}

// 6. Validate password strength
$pw_error = validate_password($password);
if ($pw_error !== null) {
    flash('error', $pw_error);
    redirect(BASE_URL . '/index.php');
}

// 7. Check duplicate email (prepared statement)
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

// 8. Hash password with bcrypt (cost 12)
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// 9. Insert user as regular role
$stmt = mysqli_prepare($conn,
    "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'regular')"
);
mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hashed);
mysqli_stmt_execute($stmt);
$user_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// 10. Log the signup activity
log_activity(null, 'signup', $email);

flash('success', 'Account created successfully! Please log in.');
redirect(BASE_URL . '/index.php');
?>