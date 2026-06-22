<?php
// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'hms_db');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// Connection retry logic: 3 attempts
$max_retries = 3;
$retry_delay = 2;
$pdo = null;

// Determine driver: If DB_HOST contains aivencloud.com or DB_PORT is not 3306, we use pgsql
$is_pgsql = (strpos(DB_HOST, 'aivencloud.com') !== false || DB_PORT !== '3306');

for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
      try {
            if ($is_pgsql) {
                // PostgreSQL: Aiven connection (requires SSL)
                $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } else {
                // MySQL: Localhost or Railway connection
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            }
            break;
      } catch (PDOException $e) {
            if ($attempt === $max_retries) {
                  die("<div style='font-family:sans-serif;padding:20px;color:#c0392b;'>
                        <h2>Database Connection Error</h2>
                        <p>Connection failed. Please check:</p>
                        <ul>
                              <li>Database server is running?</li>
                              <li>Credentials are correct?</li>
                        </ul>
                        <small>Error: " . htmlspecialchars($e->getMessage()) . "</small>
                        </div>");
            }
            sleep($retry_delay);
      }
}

// App Config
define('APP_NAME',       'SANKHLA HOSPITAL HEART & TRUMA CENTER');
define('APP_SHORT_NAME', 'SANKHLA HOSPITAL');
define('APP_ADDRESS',    'GOVT. DISS.NEAR KANJI PETROL PUMP,NEWARU ROAD,JHOTWARA,JAIPUR');
define('APP_PHONE',      '9829208462');
define('APP_EMAIL',      'bksankhlahospital@gmail.com');
define('APP_LOGO',       'assets/logo.png');
define('CURRENCY',       "\u{20B9}");
define('PRIMARY_COLOR',  '#0066CC');
define('SECONDARY_COLOR','#2C2C2C');
define('HEADER_FONT',    "'Roboto', Arial, sans-serif");

if (session_status() === PHP_SESSION_NONE) {
      session_start();
}
?>
