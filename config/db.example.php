<?php
/**
 * Database Configuration — EXAMPLE FILE
 * =======================================
 * Is file ko copy karke db.php banao:
 *   cp config/db.example.php config/db.php
 * 
 * Phir db.php mein apni values update karo.
 * db.php gitignored hai — sensitive data safe rahega.
 * 
 * ====================================================
 * RAILWAY.APP DEPLOYMENT (Cloud):
 * ====================================================
 * Railway pe MySQL plugin add karne ke baad ye variables
 * automatically set ho jaate hain — kuch karna nahi padta!
 * 
 *   MYSQL_HOST      → Railway auto-set karta hai
 *   MYSQL_USER      → Railway auto-set karta hai
 *   MYSQL_PASSWORD  → Railway auto-set karta hai
 *   MYSQL_DATABASE  → Railway auto-set karta hai
 *   MYSQL_PORT      → Railway auto-set karta hai
 * 
 * Optional Railway Variables (Dashboard > Variables mein add karo):
 *   APP_ENV=production
 *   DEBUG=false
 *   INIT_TOKEN=your-secret-token-here   (for init_db.php security)
 * 
 * ====================================================
 * LOCAL XAMPP SETUP:
 * ====================================================
 */

// ── Local Development Values ──
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: empty password
define('DB_NAME', 'hms_db');
define('DB_PORT', '3306');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:20px;color:#c0392b;'>
        <h2>⚠️ Database Connection Error</h2>
        <p>MySQL se connect nahi ho pa raha. Check karo:</p>
        <ul>
            <li>XAMPP mein MySQL start hai?</li>
            <li>Database <strong>" . DB_NAME . "</strong> exist karta hai?</li>
            <li>Username/Password sahi hai?</li>
        </ul>
        <small>Error: " . htmlspecialchars($e->getMessage()) . "</small>
    </div>");
}

// App Config (ye change mat karo — db.php mein karo)
define('APP_NAME',       'SANKHLA HOSPITAL HEART & TRUMA CENTER');
define('APP_SHORT_NAME', 'SANKHLA HOSPITAL');
define('APP_ADDRESS',    'GOVT. DISS.NEAR KANJI PETROL PUMP,NEWARU ROAD,JHOTWARA,JAIPUR');
define('APP_PHONE',      '9829208462');
define('APP_EMAIL',      'bksankhlahospital@gmail.com');
define('APP_LOGO',       'assets/logo.png');
define('CURRENCY',       '₹');
define('PRIMARY_COLOR',  '#0066CC');
define('SECONDARY_COLOR','#2C2C2C');
define('HEADER_FONT',    "'Roboto', Arial, sans-serif");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
