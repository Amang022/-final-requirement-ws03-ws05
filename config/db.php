<?php
// =============================================================
// config/db.php — Database connection (mysqli)
// =============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agri_inventory');
define('SECRET_KEY', 'change_this_to_a_random_secret_32chars'); // used for ID hashing

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die(json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]));
}

mysqli_set_charset($conn, 'utf8mb4');
