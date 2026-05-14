<?php
// =============================================================
// index.php — Login page
// =============================================================

define('BASE_URL', '.');
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

send_security_headers();

// Already logged in? Go to dashboard
if (!empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/dashboard.php');
}

$error   = flash('error');
$success = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HarvestHub — Login</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">

<div class="auth-container">
    <div class="auth-brand">
        <span class="brand-icon">🌾</span>
        <h1>HarvestHub</h1>
        <p>Agricultural Inventory System</p>
    </div>

    <div class="auth-tabs">
        <button class="tab-btn active" onclick="showTab('login')">Login</button>
        <button class="tab-btn" onclick="showTab('signup')">Sign Up</button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <div id="login-tab" class="auth-form active">
        <form method="POST" action="<?= BASE_URL ?>/auth/login.php">
            <?php csrf_field(); ?>

            <div class="form-group">
                <label for="login-email">Email Address</label>
                <input type="email" id="login-email" name="email"
                       placeholder="you@example.com"
                       required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="password"
                       placeholder="••••••••"
                       required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary btn-full">Sign In</button>
        </form>
    </div>

    <div id="signup-tab" class="auth-form">
        <form method="POST" action="<?= BASE_URL ?>/auth/signup.php">
            <?php csrf_field(); ?>

            <div class="form-group">
                <label for="signup-name">Full Name</label>
                <input type="text" id="signup-name" name="name"
                       placeholder="Juan dela Cruz"
                       required autocomplete="name">
            </div>

            <div class="form-group">
                <label for="signup-email">Email Address</label>
                <input type="email" id="signup-email" name="email"
                       placeholder="you@example.com"
                       required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="signup-password">Password (min 8 chars, upper + lower + number)</label>
                <input type="password" id="signup-password" name="password"
                       placeholder="••••••••"
                       required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary btn-full">Sign Up</button>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
function showTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
    document.querySelector(`[onclick="showTab('${tab}')"]`).classList.add('active');
    document.getElementById(`${tab}-tab`).classList.add('active');
}
</script>
</body>
</html>
