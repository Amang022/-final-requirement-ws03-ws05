<?php
// =============================================================
// auth/logout.php — Logout handler
// =============================================================

define('BASE_URL', '..');
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['user_id'])) {
    log_activity($_SESSION['user_id'], 'logout', $_SESSION['user_name'] ?? '');
}

$_SESSION = [];
session_destroy();

redirect(BASE_URL . '/index.php');
