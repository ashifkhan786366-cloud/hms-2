<?php
// ============================================================
// DATABASE CONFIGURATION - EXAMPLE FILE
// ============================================================
// Instructions:
// 1. Is file ko copy karo: cp config/db.example.php config/db.php
// 2. config/db.php mein apni values fill karo
// 3. config/db.php KABHI bhi Git commit mat karo (already .gitignore mein hai)
// ============================================================

define('DB_HOST', 'localhost');       // Your MySQL host
define('DB_USER', 'root');            // Your MySQL username
define('DB_PASS', '');                // Your MySQL password
define('DB_NAME', 'hms_db');          // Database name (pehle create karo)

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Database se connect nahi ho pa raha. " . $e->getMessage());
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// App Configuration - Apni hospital ki details yahan update karo
define('APP_NAME', 'YOUR HOSPITAL NAME HERE');
define('APP_SHORT_NAME', 'YOUR HOSPITAL SHORT NAME');
define('APP_ADDRESS', 'Your Full Address Here');
define('APP_PHONE', '9999999999');
define('APP_EMAIL', 'youremail@hospital.com');
define('APP_LOGO', 'assets/logo.png');
define('CURRENCY', '₹');
define('PRIMARY_COLOR', '#0066CC');
define('SECONDARY_COLOR', '#2C2C2C');
define('HEADER_FONT', "'Roboto', Arial, sans-serif");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
