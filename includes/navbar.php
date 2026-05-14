<?php
// =============================================================
// includes/navbar.php — Shared navigation bar
// =============================================================
$role = current_role();
?>
<nav class="navbar">
    <div class="navbar-brand">
        <span>🌾</span> HarvestHub
    </div>
    <ul class="navbar-nav">
        <li><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>/pages/items.php">Items</a></li>
        <?php if (in_array($role, ['admin','super_admin'])): ?>
        <li><a href="<?= BASE_URL ?>/pages/approvals.php">Approvals</a></li>
        <li><a href="<?= BASE_URL ?>/pages/users.php">Users</a></li>
        <?php endif; ?>
    </ul>
    <div class="navbar-user">
        <a href="<?= BASE_URL ?>/pages/profile.php"><?= e($_SESSION['user_name'] ?? '') ?></a>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-sm btn-outline">Logout</a>
    </div>
</nav>
