<?php
// =============================================================
// pages/items.php — Item management (Add, View, Update, Archive, Restore)
// =============================================================

define('BASE_URL', '..');
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/hash.php';
require_once __DIR__ . '/../includes/security.php';

send_security_headers();
require_login();

$role    = current_role();
$user_id = current_user_id();
$action  = $_GET['action'] ?? 'list';
$error   = flash('error');
$success = flash('success');

// ---------------------------------------------------------------
// POST: Add item
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    csrf_validate();

    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = (float)($_POST['quantity'] ?? 0);
    $unit     = trim($_POST['unit'] ?? '');
    $desc     = trim($_POST['description'] ?? '');

    if (empty($name) || empty($category) || empty($unit)) {
        flash('error', 'Name, category, and unit are required.');
        redirect(BASE_URL . '/pages/items.php');
    }

    $allowed_categories = ['Grains', 'Vegetables', 'Fruits', 'Root Crops', 'Fertilizers', 'Chemicals', 'Supplies', 'Equipment'];
    $allowed_units = ['kg', 'pcs', 'bags', 'liters', 'sacks'];

    if (!in_array($category, $allowed_categories) || !in_array($unit, $allowed_units)) {
        flash('error', 'Invalid category or unit selected.');
        redirect(BASE_URL . '/pages/items.php');
    }

    // All users can add items directly (unlimited/approved)
    $status = 'approved';

    $stmt = mysqli_prepare($conn,
        "INSERT INTO items (name, category, quantity, unit, description, status, added_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'ssdsssi', $name, $category, $quantity, $unit, $desc, $status, $user_id);
    mysqli_stmt_execute($stmt);
    $new_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // If pending, create approval request
    if ($status === 'pending') {
        $stmt2 = mysqli_prepare($conn,
            "INSERT INTO item_approvals (item_id, requested_by) VALUES (?, ?)"
        );
        mysqli_stmt_bind_param($stmt2, 'ii', $new_id, $user_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
        flash('success', 'Item submitted for admin approval.');
    } else {
        flash('success', 'Item added successfully.');
    }

    log_activity($user_id, 'add_item', $name);
    redirect(BASE_URL . '/pages/items.php');
}

// ---------------------------------------------------------------
// POST: Update item (admin+)
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    csrf_validate();
    if (!in_array($role, ['admin','super_admin'])) {
        http_response_code(403); die('Access denied.');
    }

    $hash     = $_POST['item_hash'] ?? '';
    $item_id  = decode_id($hash, 'items');
    if (!$item_id) { flash('error', 'Invalid item.'); redirect(BASE_URL . '/pages/items.php'); }

    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = (float)($_POST['quantity'] ?? 0);
    $unit     = trim($_POST['unit'] ?? '');
    $desc     = trim($_POST['description'] ?? '');

    $allowed_categories = ['Grains', 'Vegetables', 'Fruits', 'Root Crops', 'Fertilizers', 'Chemicals', 'Supplies', 'Equipment'];
    $allowed_units = ['kg', 'pcs', 'bags', 'liters', 'sacks'];

    if (!in_array($category, $allowed_categories) || !in_array($unit, $allowed_units)) {
        flash('error', 'Invalid category or unit selected.');
        redirect(BASE_URL . '/pages/items.php');
    }

    $stmt = mysqli_prepare($conn,
        "UPDATE items SET name=?, category=?, quantity=?, unit=?, description=? WHERE id=?"
    );
    mysqli_stmt_bind_param($stmt, 'ssdssi', $name, $category, $quantity, $unit, $desc, $item_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    log_activity($user_id, 'update_item', $name);
    flash('success', 'Item updated successfully.');
    redirect(BASE_URL . '/pages/items.php');
}

// ---------------------------------------------------------------
// GET: Archive / Restore item (admin+)
// ---------------------------------------------------------------
if ($action === 'archive' || $action === 'restore') {
    if (!in_array($role, ['admin','super_admin'])) {
        http_response_code(403); die('Access denied.');
    }
    $hash    = $_GET['h'] ?? '';
    $item_id = decode_id($hash, 'items');
    if (!$item_id) { flash('error', 'Invalid item.'); redirect(BASE_URL . '/pages/items.php'); }

    $archived = ($action === 'archive') ? 1 : 0;
    $stmt = mysqli_prepare($conn, "UPDATE items SET is_archived=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ii', $archived, $item_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    log_activity($user_id, $action . '_item', (string)$item_id);
    flash('success', 'Item ' . $action . 'd successfully.');
    redirect(BASE_URL . '/pages/items.php' . ($action === 'restore' ? '?tab=archived' : ''));
}

// ---------------------------------------------------------------
// Fetch items for listing
// ---------------------------------------------------------------
$tab = $_GET['tab'] ?? 'active';

if ($tab === 'archived' && in_array($role, ['admin','super_admin'])) {
    $stmt = mysqli_prepare($conn,
        "SELECT i.*, u.name AS added_by_name FROM items i
         JOIN users u ON u.id = i.added_by
         WHERE i.is_archived = 1 ORDER BY i.updated_at DESC"
    );
} else {
    if ($role === 'regular') {
        $stmt = mysqli_prepare($conn,
            "SELECT i.*, u.name AS added_by_name FROM items i
             JOIN users u ON u.id = i.added_by
             WHERE i.is_archived = 0 AND i.status = 'approved'
             ORDER BY i.created_at DESC"
        );
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT i.*, u.name AS added_by_name FROM items i
             JOIN users u ON u.id = i.added_by
             WHERE i.is_archived = 0 ORDER BY i.created_at DESC"
        );
    }
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$items  = [];
while ($row = mysqli_fetch_assoc($result)) $items[] = $row;
mysqli_stmt_close($stmt);

// Item for edit modal
$edit_item = null;
if ($action === 'edit' && in_array($role, ['admin','super_admin'])) {
    $hash = $_GET['h'] ?? '';
    $eid  = decode_id($hash, 'items');
    if ($eid) {
        $s = mysqli_prepare($conn, "SELECT * FROM items WHERE id=? AND is_archived=0 LIMIT 1");
        mysqli_stmt_bind_param($s, 'i', $eid);
        mysqli_stmt_execute($s);
        $edit_item = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HarvestHub — Items</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <h2><?= $tab === 'archived' ? 'Archived Items' : 'Item Inventory' ?></h2>
        <div class="header-actions">
            <?php if (in_array($role, ['admin','super_admin'])): ?>
            <a href="?tab=<?= $tab === 'archived' ? 'active' : 'archived' ?>" class="btn btn-outline btn-sm">
                <?= $tab === 'archived' ? '📋 Active Items' : '🗄 View Archived' ?>
            </a>
            <?php endif; ?>
            <button class="btn btn-primary" onclick="openModal('addItemModal')">+ Add Item</button>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <!-- Search bar -->
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="🔍  Search items by name or category…" oninput="filterItems()">
    </div>

    <div class="table-wrapper">
        <table class="data-table" id="itemsTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th>Added By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e($item['category']) ?></td>
                    <td class="<?= $item['quantity'] <= 10 ? 'text-warning' : '' ?>">
                        <?= e($item['quantity']) ?>
                    </td>
                    <td><?= e($item['unit']) ?></td>
                    <td>
                        <span class="badge badge-<?= $item['status'] ?>">
                            <?= e($item['status']) ?>
                        </span>
                    </td>
                    <td><?= e($item['added_by_name']) ?></td>
                    <td class="actions">
                        <?php if (in_array($role, ['admin','super_admin']) && !$item['is_archived']): ?>
                        <a href="?action=edit&h=<?= hash_id($item['id']) ?>" class="btn btn-sm btn-outline">Edit</a>
                        <a href="?action=archive&h=<?= hash_id($item['id']) ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Archive this item?')">Archive</a>
                        <?php elseif ($item['is_archived'] && in_array($role, ['admin','super_admin'])): ?>
                        <a href="?action=restore&h=<?= hash_id($item['id']) ?>"
                           class="btn btn-sm btn-success"
                           onclick="return confirm('Restore this item?')">Restore</a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
                <tr><td colspan="7" class="text-center text-muted">No items found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Add Item Modal -->
<div class="modal-overlay" id="addItemModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New Item</h3>
            <button onclick="closeModal('addItemModal')" class="modal-close">×</button>
        </div>
        <form method="POST" action="">
            <?php csrf_field(); ?>
            <input type="hidden" name="add_item" value="1">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="name" required placeholder="e.g. Rice Sack">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="" disabled selected>Select Category</option>
                        <option value="Grains">Grains</option>
                        <option value="Vegetables">Vegetables</option>
                        <option value="Fruits">Fruits</option>
                        <option value="Root Crops">Root Crops</option>
                        <option value="Fertilizers">Fertilizers</option>
                        <option value="Chemicals">Chemicals</option>
                        <option value="Supplies">Supplies</option>
                        <option value="Equipment">Equipment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <select name="unit" required>
                        <option value="" disabled selected>Select Unit</option>
                        <option value="kg">kg</option>
                        <option value="pcs">pcs</option>
                        <option value="bags">bags</option>
                        <option value="liters">liters</option>
                        <option value="sacks">sacks</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" step="0.01" min="0" value="0" required>
            </div>
            <div class="form-group">
                <label>Description (optional)</label>
                <textarea name="description" rows="3" placeholder="Additional details…"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal('addItemModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Item Modal -->
<?php if ($edit_item): ?>
<div class="modal-overlay active" id="editItemModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Item</h3>
            <a href="items.php" class="modal-close">×</a>
        </div>
        <form method="POST" action="">
            <?php csrf_field(); ?>
            <input type="hidden" name="update_item" value="1">
            <input type="hidden" name="item_hash" value="<?= hash_id($edit_item['id']) ?>">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="name" required value="<?= e($edit_item['name']) ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="Grains" <?= $edit_item['category'] === 'Grains' ? 'selected' : '' ?>>Grains</option>
                        <option value="Vegetables" <?= $edit_item['category'] === 'Vegetables' ? 'selected' : '' ?>>Vegetables</option>
                        <option value="Fruits" <?= $edit_item['category'] === 'Fruits' ? 'selected' : '' ?>>Fruits</option>
                        <option value="Root Crops" <?= $edit_item['category'] === 'Root Crops' ? 'selected' : '' ?>>Root Crops</option>
                        <option value="Fertilizers" <?= $edit_item['category'] === 'Fertilizers' ? 'selected' : '' ?>>Fertilizers</option>
                        <option value="Chemicals" <?= $edit_item['category'] === 'Chemicals' ? 'selected' : '' ?>>Chemicals</option>
                        <option value="Supplies" <?= $edit_item['category'] === 'Supplies' ? 'selected' : '' ?>>Supplies</option>
                        <option value="Equipment" <?= $edit_item['category'] === 'Equipment' ? 'selected' : '' ?>>Equipment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <select name="unit" required>
                        <option value="kg" <?= $edit_item['unit'] === 'kg' ? 'selected' : '' ?>>kg</option>
                        <option value="pcs" <?= $edit_item['unit'] === 'pcs' ? 'selected' : '' ?>>pcs</option>
                        <option value="bags" <?= $edit_item['unit'] === 'bags' ? 'selected' : '' ?>>bags</option>
                        <option value="liters" <?= $edit_item['unit'] === 'liters' ? 'selected' : '' ?>>liters</option>
                        <option value="sacks" <?= $edit_item['unit'] === 'sacks' ? 'selected' : '' ?>>sacks</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" step="0.01" min="0" value="<?= e($edit_item['quantity']) ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?= e($edit_item['description'] ?? '') ?></textarea>
            </div>
            <div class="modal-footer">
                <a href="items.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
