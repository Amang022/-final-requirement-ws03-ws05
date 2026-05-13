<?php
// =============================================================
// includes/functions.php — Shared helper functions
// =============================================================

require_once __DIR__ . '/../config/db.php';

/**
 * Sanitize output to prevent XSS.
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Log an action to the activity_log table.
 */
function log_activity(int $user_id = null, string $action = '', string $target = ''): void {
    global $conn;
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = mysqli_prepare($conn,
        "INSERT INTO activity_log (user_id, action, target, ip_address) VALUES (?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'isss', $user_id, $action, $target, $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Redirect helper.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Flash message: set once, read once.
 */
function flash(string $key, string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

/**
 * Get all non-archived items (filtered by status for regular users).
 */
function get_items(string $role): array {
    global $conn;
    if ($role === 'regular') {
        $stmt = mysqli_prepare($conn,
            "SELECT i.*, u.name AS added_by_name
             FROM items i
             JOIN users u ON u.id = i.added_by
             WHERE i.is_archived = 0 AND i.status = 'approved'
             ORDER BY i.created_at DESC"
        );
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT i.*, u.name AS added_by_name
             FROM items i
             JOIN users u ON u.id = i.added_by
             WHERE i.is_archived = 0
             ORDER BY i.created_at DESC"
        );
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

/**
 * Get all non-archived users.
 */
function get_users(string $role): array {
    global $conn;
    if ($role === 'super_admin') {
        $stmt = mysqli_prepare($conn,
            "SELECT id, name, email, role, is_archived, created_at FROM users ORDER BY role, name"
        );
    } else {
        // Admin can only manage regular users
        $stmt = mysqli_prepare($conn,
            "SELECT id, name, email, role, is_archived, created_at
             FROM users WHERE role = 'regular' ORDER BY name"
        );
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

/**
 * Get pending item approval requests.
 */
function get_pending_approvals(): array {
    global $conn;
    $stmt = mysqli_prepare($conn,
        "SELECT ia.*, i.name AS item_name, i.category, i.quantity, i.unit,
                u.name AS requested_by_name
         FROM item_approvals ia
         JOIN items i ON i.id = ia.item_id
         JOIN users u ON u.id = ia.requested_by
         WHERE ia.status = 'pending'
         ORDER BY ia.created_at ASC"
    );
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}
