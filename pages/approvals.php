<?php
// =============================================================
// pages/approvals.php — Item approval queue (Admin & Super Admin)
// =============================================================

define('BASE_URL', '..');
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

send_security_headers();
require_login(['admin','super_admin']);

$role    = current_role();
$user_id = current_user_id();
$error   = flash('error');
$success = flash('success');

// ---------------------------------------------------------------
// POST: Approve or Reject an item
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_item'])) {
    csrf_validate();

    $approval_id = (int)($_POST['approval_id'] ?? 0);
    $decision    = $_POST['decision'] ?? '';
    $note        = trim($_POST['note'] ?? '');

    if (!in_array($decision, ['approved','rejected']) || $approval_id <= 0) {
        flash('error', 'Invalid request.');
        redirect(BASE_URL . '/pages/approvals.php');
    }

    // Get item_id from approval
    $stmt = mysqli_prepare($conn, "SELECT item_id FROM item_approvals WHERE id=? AND status='pending' LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $approval_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row) {
        flash('error', 'Approval request not found or already reviewed.');
        redirect(BASE_URL . '/pages/approvals.php');
    }

    $item_id = (int)$row['item_id'];
    $now     = date('Y-m-d H:i:s');

    // Update approval record
    $stmt = mysqli_prepare($conn,
        "UPDATE item_approvals SET status=?, reviewed_by=?, note=?, reviewed_at=? WHERE id=?"
    );
    mysqli_stmt_bind_param($stmt, 'sissi', $decision, $user_id, $note, $now, $approval_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Update item status
    $stmt = mysqli_prepare($conn,
        "UPDATE items SET status=?, approved_by=? WHERE id=?"
    );
    mysqli_stmt_bind_param($stmt, 'sii', $decision, $user_id, $item_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    log_activity($user_id, 'review_item_' . $decision, (string)$item_id);
    flash('success', 'Item ' . $decision . ' successfully.');
    redirect(BASE_URL . '/pages/approvals.php');
}

// ---------------------------------------------------------------
// Fetch pending approvals
// ---------------------------------------------------------------
$stmt = mysqli_prepare($conn,
    "SELECT ia.id AS approval_id, ia.created_at AS requested_at, ia.note,
            i.id AS item_id, i.name AS item_name, i.category, i.quantity, i.unit, i.description,
            u.name AS requested_by_name, u.email AS requested_by_email
     FROM item_approvals ia
     JOIN items i ON i.id = ia.item_id
     JOIN users u ON u.id = ia.requested_by
     WHERE ia.status = 'pending'
     ORDER BY ia.created_at ASC"
);
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$approvals = [];
while ($row = mysqli_fetch_assoc($result)) $approvals[] = $row;
mysqli_stmt_close($stmt);

// ---------------------------------------------------------------
// Fetch recently reviewed (last 20)
// ---------------------------------------------------------------
$stmt = mysqli_prepare($conn,
    "SELECT ia.status, ia.reviewed_at, ia.note,
            i.name AS item_name, i.category,
            u.name AS requested_by_name,
            rv.name AS reviewed_by_name
     FROM item_approvals ia
     JOIN items i ON i.id = ia.item_id
     JOIN users u ON u.id = ia.requested_by
     LEFT JOIN users rv ON rv.id = ia.reviewed_by
     WHERE ia.status != 'pending'
     ORDER BY ia.reviewed_at DESC
     LIMIT 20"
);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$history = [];
while ($row = mysqli_fetch_assoc($result)) $history[] = $row;
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriStock — Approvals</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <h2>Item Approval Queue</h2>
        <span class="badge badge-info"><?= count($approvals) ?> pending</span>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <?php if (empty($approvals)): ?>
        <div class="empty-state">
            <p>✅ No pending approvals. All items have been reviewed.</p>
        </div>
    <?php else: ?>
    <div class="approval-grid">
        <?php foreach ($approvals as $ap): ?>
        <div class="approval-card">
            <div class="approval-card-header">
                <strong><?= e($ap['item_name']) ?></strong>
                <span class="badge badge-pending">Pending</span>
            </div>
            <div class="approval-meta">
                <span>📁 <?= e($ap['category']) ?></span>
                <span>📦 <?= e($ap['quantity']) ?> <?= e($ap['unit']) ?></span>
            </div>
            <?php if ($ap['description']): ?>
            <p class="approval-desc"><?= e($ap['description']) ?></p>
            <?php endif; ?>
            <p class="text-muted text-sm">
                Requested by <strong><?= e($ap['requested_by_name']) ?></strong>
                on <?= e(date('M d, Y H:i', strtotime($ap['requested_at']))) ?>
            </p>
            <form method="POST" action="" class="approval-actions">
                <?php csrf_field(); ?>
                <input type="hidden" name="review_item" value="1">
                <input type="hidden" name="approval_id" value="<?= (int)$ap['approval_id'] ?>">
                <input type="text" name="note" placeholder="Optional note…" class="note-input">
                <div class="btn-group">
                    <button type="submit" name="decision" value="approved" class="btn btn-success btn-sm">
                        ✓ Approve
                    </button>
                    <button type="submit" name="decision" value="rejected" class="btn btn-danger btn-sm"
                            onclick="return confirm('Reject this item?')">
                        ✗ Reject
                    </button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($history)): ?>
    <div class="section-divider"></div>
    <h3>Recent Review History</h3>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Requested By</th>
                    <th>Reviewed By</th>
                    <th>Decision</th>
                    <th>Reviewed At</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= e($h['item_name']) ?></td>
                    <td><?= e($h['category']) ?></td>
                    <td><?= e($h['requested_by_name']) ?></td>
                    <td><?= e($h['reviewed_by_name'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= $h['status'] ?>"><?= e($h['status']) ?></span></td>
                    <td><?= e(date('M d, Y H:i', strtotime($h['reviewed_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
