<?php
// =============================================================
// includes/hash.php — ID hashing helpers
// Prevents raw integer IDs from being exposed in URLs/forms.
// =============================================================

require_once __DIR__ . '/../config/db.php';

/**
 * Encode a raw integer ID into a URL-safe hash.
 *
 * @param  int    $id   The real database ID.
 * @return string       HMAC-SHA256 hex string (64 chars).
 */
function hash_id(int $id): string {
    return hash_hmac('sha256', (string)$id, SECRET_KEY);
}

/**
 * Given a submitted hash, look it up against a table to find
 * the real integer ID. Returns null if no match found.
 *
 * @param  string $hash       The hash received from the request.
 * @param  string $table      Table name ('items' or 'users').
 * @return int|null           Real ID or null on failure.
 */
function decode_id(string $hash, string $table): ?int {
    global $conn;

    // Whitelist allowed tables to prevent SQL injection via $table param
    $allowed = ['items', 'users'];
    if (!in_array($table, $allowed, true)) {
        return null;
    }

    $stmt = mysqli_prepare($conn, "SELECT id FROM `{$table}` WHERE is_archived = 0 OR is_archived = 1");
    if (!$stmt) return null;

    mysqli_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        if (hash_equals(hash_id((int)$row['id']), $hash)) {
            mysqli_stmt_close($stmt);
            return (int)$row['id'];
        }
    }

    mysqli_stmt_close($stmt);
    return null;
}
