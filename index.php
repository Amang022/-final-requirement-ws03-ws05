<?php
// =============================================================
// index.php — Login page
// =============================================================

define('BASE_URL', '.');
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

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
<title>AgriStock — Login</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">

<div class="login-wrapper">
    <div class="login-brand">
        <span class="brand-icon">🌾</span>
        <h1>AgriStock</h1>
        <p>Agricultural Inventory System</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <form class="login-form" method="POST" action="<?= BASE_URL ?>/auth/login.php">
        <?php csrf_field(); ?>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="you@example.com"
                   required autocomplete="email">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••"
                   required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
