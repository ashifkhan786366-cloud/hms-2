<?php
// Include config if not already included
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/db.php';
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
