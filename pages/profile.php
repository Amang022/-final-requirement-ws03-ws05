<?php
// =============================================================
// pages/profile.php — User Profile Management
// =============================================================

define('BASE_URL', '..');
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

send_security_headers();
require_login();

$user_id = current_user_id();
$role    = current_role();
$error   = flash('error');
$success = flash('success');

// 1. Handle Update Profile Information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    csrf_validate();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || empty($email)) {
        flash('error', 'Name and email are required.');
        redirect(BASE_URL . '/pages/profile.php');
    }

    // Check if email exists for another user
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        flash('error', 'Email is already in use by another account.');
        redirect(BASE_URL . '/pages/profile.php');
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'ssi', $name, $email, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['user_name'] = $name;
    log_activity($user_id, 'update_profile', $email);
    flash('success', 'Profile updated successfully.');
    redirect(BASE_URL . '/pages/profile.php');
}

// 2. Handle Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    csrf_validate();
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        flash('error', 'All password fields are required.');
        redirect(BASE_URL . '/pages/profile.php');
    }

    if ($new_password !== $confirm_password) {
        flash('error', 'New passwords do not match.');
        redirect(BASE_URL . '/pages/profile.php');
    }
    
    // Validate password strength
    if (strlen($new_password) < 8 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        flash('error', 'Password must be at least 8 characters and include an uppercase letter and a number.');
        redirect(BASE_URL . '/pages/profile.php');
    }

    // Verify current password
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!password_verify($current_password, $user['password'])) {
        flash('error', 'Current password is incorrect.');
        redirect(BASE_URL . '/pages/profile.php');
    }

    // Update to new password
    $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $hash, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    log_activity($user_id, 'update_password', '');
    flash('success', 'Password updated successfully.');
    redirect(BASE_URL . '/pages/profile.php');
}

// Fetch current user details
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$current_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriStock — Profile</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <h2>My Profile</h2>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <div class="action-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px;">
        
        <!-- Profile Details Form -->
        <div class="stat-card" style="flex-direction: column; align-items: stretch; gap: 20px;">
            <h3 style="font-family: var(--font-heading); color: var(--secondary); margin-bottom: 8px;">Account Information</h3>
            <form method="POST" action="">
                <?php csrf_field(); ?>
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= e($current_user['name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= e($current_user['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <div><span class="badge badge-<?= $role ?>"><?= e(str_replace('_', ' ', $role)) ?></span></div>
                </div>

                <div class="form-group">
                    <label>Member Since</label>
                    <div class="text-muted"><?= date('F j, Y', strtotime($current_user['created_at'])) ?></div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Changes</button>
            </form>
        </div>

        <!-- Security / Password Form -->
        <div class="stat-card" style="flex-direction: column; align-items: stretch; gap: 20px;">
            <h3 style="font-family: var(--font-heading); color: var(--secondary); margin-bottom: 8px;">Security</h3>
            <form method="POST" action="">
                <?php csrf_field(); ?>
                <input type="hidden" name="update_password" value="1">
                
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required placeholder="Min. 8 chars, uppercase, number">
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-outline" style="margin-top: 10px;">Update Password</button>
            </form>
        </div>

    </div>
</main>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
