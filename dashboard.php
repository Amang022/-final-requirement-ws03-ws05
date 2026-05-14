<?php
// =============================================================
// dashboard.php — Main dashboard (all roles)
// =============================================================

define('BASE_URL', '.');
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

send_security_headers();
require_login(); // any logged-in user

$role      = current_role();
$user_name = $_SESSION['user_name'];

// --- Stats ---
$total_items = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM items WHERE is_archived = 0 AND status = 'approved'"))['c'];

$low_stock = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM items WHERE is_archived = 0 AND status = 'approved' AND quantity <= 10"))['c'];

$pending_approvals = 0;
if (in_array($role, ['admin','super_admin'])) {
    $pending_approvals = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS c FROM item_approvals WHERE status = 'pending'"))['c'];
}

$total_users = 0;
if (in_array($role, ['admin','super_admin'])) {
    $total_users = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS c FROM users WHERE is_archived = 0"))['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HarvestHub — Dashboard</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h2>Dashboard</h2>
            <p class="text-muted">Welcome back, <?= e($user_name) ?>! 
                <span class="badge badge-<?= $role ?>"><?= e(str_replace('_',' ', $role)) ?></span>
            </p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <span class="stat-value"><?= $total_items ?></span>
                <span class="stat-label">Total Items</span>
            </div>
        </div>

        <div class="stat-card <?= $low_stock > 0 ? 'stat-warning' : '' ?>">
            <div class="stat-icon">⚠️</div>
            <div class="stat-info">
                <span class="stat-value"><?= $low_stock ?></span>
                <span class="stat-label">Low Stock (≤10)</span>
            </div>
        </div>

        <?php if (in_array($role, ['admin','super_admin'])): ?>
        <div class="stat-card <?= $pending_approvals > 0 ? 'stat-info' : '' ?>">
            <div class="stat-icon">🕐</div>
            <div class="stat-info">
                <span class="stat-value"><?= $pending_approvals ?></span>
                <span class="stat-label">Pending Approvals</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <span class="stat-value"><?= $total_users ?></span>
                <span class="stat-label">Active Users</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="quick-links">
        <h3>Quick Actions</h3>
        <div class="action-grid">
            <a href="pages/items.php" class="action-card">
                <span>📋</span> Manage Items
            </a>
            <?php if (in_array($role, ['admin','super_admin'])): ?>
            <a href="pages/approvals.php" class="action-card">
                <span>✅</span> Review Approvals
                <?php if ($pending_approvals > 0): ?>
                    <span class="badge-count"><?= $pending_approvals ?></span>
                <?php endif; ?>
            </a>
            <a href="pages/users.php" class="action-card">
                <span>👤</span> Manage Users
            </a>
            <?php endif; ?>
            <a href="auth/logout.php" class="action-card action-danger">
                <span>🚪</span> Sign Out
            </a>
        </div>
    </div>
</main>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
