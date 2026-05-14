<?php
// =============================================================
// config/db.php — Database connection (mysqli) & app constants
// =============================================================

// --- Environment: suppress detailed errors in production ---
error_reporting(0);
ini_set('display_errors', '0');

// --- Database credentials ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agri_inventory');

// --- Secret key for HMAC ID hashing (change this to your own random key!) ---
// Generate a new one with: php -r "echo bin2hex(random_bytes(32));"
define('SECRET_KEY', 'a7f3c9e1b4d2f8a0e5c7d3b6f9a1e4c8b2d5f7a0c3e6b9d1f4a7c0e2b5d8f1');

// --- Establish database connection ---
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    // Don't expose connection error details to users
    http_response_code(503);
    die('Service temporarily unavailable. Please try again later.');
}

// Set charset to prevent encoding-based attacks
mysqli_set_charset($conn, 'utf8mb4');

// Enable strict SQL mode for data integrity
mysqli_query($conn, "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
