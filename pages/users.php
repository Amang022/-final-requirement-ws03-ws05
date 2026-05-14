<?php
// =============================================================
// pages/users.php — User management (Admin & Super Admin)
// =============================================================

define('BASE_URL', '..');
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/hash.php';
require_once __DIR__ . '/../includes/security.php';

send_security_headers();
require_login(['admin','super_admin']);

$role    = current_role();
$user_id = current_user_id();
$action  = $_GET['action'] ?? 'list';
$error   = flash('error');
$success = flash('success');

// ---------------------------------------------------------------
// POST: Add user
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    csrf_validate();

    $name      = sanitize_input($_POST['name'] ?? '');
    $email     = sanitize_input($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $new_role  = $_POST['role'] ?? 'regular';

    // Super admin can create admins; admin can only create regulars
    if ($role !== 'super_admin' && $new_role !== 'regular') {
        flash('error', 'You can only add regular users.');
        redirect(BASE_URL . '/pages/users.php');
    }
    if ($role === 'super_admin' && !in_array($new_role, ['admin','regular'])) {
        flash('error', 'Invalid role selected.');
        redirect(BASE_URL . '/pages/users.php');
    }

    if (empty($name) || empty($email) || empty($password)) {
        flash('error', 'All fields are required.');
        redirect(BASE_URL . '/pages/users.php');
    }

    if (!is_valid_email($email)) {
        flash('error', 'Please enter a valid email address.');
        redirect(BASE_URL . '/pages/users.php');
    }

    $pw_error = validate_password($password);
    if ($pw_error !== null) {
        flash('error', $pw_error);
        redirect(BASE_URL . '/pages/users.php');
    }

    // Check duplicate email
    $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($chk, 's', $email);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) > 0) {
        mysqli_stmt_close($chk);
        flash('error', 'Email already exists.');
        redirect(BASE_URL . '/pages/users.php');
    }
    mysqli_stmt_close($chk);

    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $hashed, $new_role);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    log_activity($user_id, 'add_user', $email);
    flash('success', 'User added successfully.');
    redirect(BASE_URL . '/pages/users.php');
}

// ---------------------------------------------------------------
// POST: Reset password
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    csrf_validate();

    $hash       = $_POST['user_hash'] ?? '';
    $target_id  = decode_id($hash, 'users');
    $new_pass   = $_POST['new_password'] ?? '';

    if (!$target_id) {
        flash('error', 'Invalid request.');
        redirect(BASE_URL . '/pages/users.php');
    }

    $pw_error = validate_password($new_pass);
    if ($pw_error !== null) {
        flash('error', $pw_error);
        redirect(BASE_URL . '/pages/users.php');
    }

    // Admin can only reset regular users; super admin can reset admins
    $chk = mysqli_prepare($conn, "SELECT role FROM users WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($chk, 'i', $target_id);
    mysqli_stmt_execute($chk);
    $res  = mysqli_stmt_get_result($chk);
    $trow = mysqli_fetch_assoc($res);
    mysqli_stmt_close($chk);

    if ($role === 'admin' && $trow['role'] !== 'regular') {
        flash('error', 'You can only reset regular user passwords.');
        redirect(BASE_URL . '/pages/users.php');
    }

    $hashed = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt   = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'si', $hashed, $target_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    log_activity($user_id, 'reset_password', (string)$target_id);
    flash('success', 'Password reset successfully.');
    redirect(BASE_URL . '/pages/users.php');
}

// ---------------------------------------------------------------
// GET: Archive / Restore user
// ---------------------------------------------------------------
if ($action === 'archive' || $action === 'restore') {
    $hash      = $_GET['h'] ?? '';
    $target_id = decode_id($hash, 'users');
    if (!$target_id || $target_id === $user_id) {
        flash('error', 'Invalid request or cannot archive yourself.');
        redirect(BASE_URL . '/pages/users.php');
    }

    // Check target role
    $chk = mysqli_prepare($conn, "SELECT role FROM users WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($chk, 'i', $target_id);
    mysqli_stmt_execute($chk);
    $res  = mysqli_stmt_get_result($chk);
    $trow = mysqli_fetch_assoc($res);
    mysqli_stmt_close($chk);

    if ($role === 'admin' && $trow['role'] !== 'regular') {
        flash('error', 'Admin can only archive regular users.');
        redirect(BASE_URL . '/pages/users.php');
    }

    $archived = ($action === 'archive') ? 1 : 0;
    $stmt = mysqli_prepare($conn, "UPDATE users SET is_archived=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ii', $archived, $target_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    log_activity($user_id, $action . '_user', (string)$target_id);
    flash('success', 'User ' . $action . 'd successfully.');
    redirect(BASE_URL . '/pages/users.php');
}

// ---------------------------------------------------------------
// Fetch users
// ---------------------------------------------------------------
$tab = $_GET['tab'] ?? 'active';
$archived_flag = ($tab === 'archived') ? 1 : 0;

if ($role === 'super_admin') {
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM users WHERE is_archived=? AND role != 'super_admin' ORDER BY role, name"
    );
} else {
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM users WHERE is_archived=? AND role='regular' ORDER BY name"
    );
}
mysqli_stmt_bind_param($stmt, 'i', $archived_flag);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users  = [];
while ($row = mysqli_fetch_assoc($result)) $users[] = $row;
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriStock — Users</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <h2><?= $tab === 'archived' ? 'Archived Users' : 'User Management' ?></h2>
        <div class="header-actions">
            <a href="?tab=<?= $tab === 'archived' ? 'active' : 'archived' ?>" class="btn btn-outline btn-sm">
                <?= $tab === 'archived' ? '👥 Active Users' : '🗄 Archived Users' ?>
            </a>
            <button class="btn btn-primary" onclick="openModal('addUserModal')">+ Add User</button>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= e(str_replace('_',' ',$u['role'])) ?></span></td>
                    <td><?= e(date('M d, Y', strtotime($u['created_at']))) ?></td>
                    <td>
                        <span class="badge <?= $u['is_archived'] ? 'badge-danger' : 'badge-success' ?>">
                            <?= $u['is_archived'] ? 'Archived' : 'Active' ?>
                        </span>
                    </td>
                    <td class="actions">
                        <?php if (!$u['is_archived']): ?>
                        <button class="btn btn-sm btn-outline"
                            onclick="openResetModal('<?= hash_id($u['id']) ?>', '<?= e($u['name']) ?>')">
                            Reset PW
                        </button>
                        <a href="?action=archive&h=<?= hash_id($u['id']) ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Archive <?= e($u['name']) ?>?')">Archive</a>
                        <?php else: ?>
                        <a href="?action=restore&h=<?= hash_id($u['id']) ?>"
                           class="btn btn-sm btn-success"
                           onclick="return confirm('Restore this user?')">Restore</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New User</h3>
            <button onclick="closeModal('addUserModal')" class="modal-close">×</button>
        </div>
        <form method="POST" action="">
            <?php csrf_field(); ?>
            <input type="hidden" name="add_user" value="1">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required placeholder="Juan dela Cruz">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="juan@example.com">
            </div>
            <div class="form-group">
                <label>Password (min 8 chars, upper + lower + number)</label>
                <input type="password" name="password" required minlength="8">
            </div>
            <?php if ($role === 'super_admin'): ?>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="regular">Regular User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <?php else: ?>
                <input type="hidden" name="role" value="regular">
            <?php endif; ?>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addUserModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="resetPasswordModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Reset Password — <span id="resetUserName"></span></h3>
            <button onclick="closeModal('resetPasswordModal')" class="modal-close">×</button>
        </div>
        <form method="POST" action="">
            <?php csrf_field(); ?>
            <input type="hidden" name="reset_password" value="1">
            <input type="hidden" name="user_hash" id="resetUserHash">
            <div class="form-group">
                <label>New Password (min 8 chars, upper + lower + number)</label>
                <input type="password" name="new_password" required minlength="8">
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('resetPasswordModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
function openResetModal(hash, name) {
    document.getElementById('resetUserHash').value = hash;
    document.getElementById('resetUserName').textContent = name;
    openModal('resetPasswordModal');
}
</script>
</body>
</html>
