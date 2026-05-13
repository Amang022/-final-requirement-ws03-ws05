<?php
// =============================================================
// scripts/update_superadmin_password.php
// Update the seeded Super Admin password in the database.
// Usage: php scripts/update_superadmin_password.php [new_password] [email]
// =============================================================

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

require_once __DIR__ . '/../config/db.php';

$password = $argv[1] ?? 'Admin@1234';
$email    = $argv[2] ?? 'superadmin@agri.local';

if (strlen($password) < 8) {
    echo "Error: Password must be at least 8 characters.\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
if (!$stmt) {
    echo "Prepare failed: " . mysqli_error($conn) . "\n";
    exit(1);
}

mysqli_stmt_bind_param($stmt, 'ss', $hash, $email);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected > 0) {
    echo "Super Admin password updated successfully for {$email}.\n";
    echo "New password: {$password}\n";
    exit(0);
}

// If no rows updated, maybe the email does not exist.

$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    echo "No user found with email {$email}.\n";
    exit(1);
}

echo "Password was already set to this value for {$email}, or no change occurred.\n";
exit(0);
