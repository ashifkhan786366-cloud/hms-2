<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🏥 Database Connection Diagnostics</h1>";

// Load DB configurations directly from env
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'hms_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

echo "<h3>Environment Variables Loaded:</h3>";
echo "<ul>";
echo "<li>DB_HOST: " . htmlspecialchars($host) . "</li>";
echo "<li>DB_PORT: " . htmlspecialchars($port) . "</li>";
echo "<li>DB_NAME: " . htmlspecialchars($name) . "</li>";
echo "<li>DB_USER: " . htmlspecialchars($user) . "</li>";
echo "<li>DB_PASS length: " . strlen($pass) . "</li>";
echo "</ul>";

$is_pgsql = (strpos($host, 'aivencloud.com') !== false || $port !== '3306');
echo "Detected DB Driver: " . ($is_pgsql ? "<strong>PostgreSQL (pgsql)</strong>" : "<strong>MySQL (mysql)</strong>") . "<br><br>";

try {
    if ($is_pgsql) {
        $dsn = "pgsql:host=$host;port=$port;dbname=$name;sslmode=require";
        echo "Attempting PDO connection with DSN: <code>" . htmlspecialchars($dsn) . "</code>...<br>";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5 // 5 seconds timeout
        ]);
    } else {
        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
        echo "Attempting PDO connection with DSN: <code>" . htmlspecialchars($dsn) . "</code>...<br>";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5 // 5 seconds timeout
        ]);
    }
    echo "<h2 style='color:green'>✅ Database Connection SUCCESSFUL!</h2>";
    
    // Check tables
    if ($is_pgsql) {
        $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    echo "<h3>Tables found:</h3><ul>";
    foreach ($tables as $t) {
        echo "<li>" . htmlspecialchars($t) . "</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Database Connection FAILED!</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<h4>Trace:</h4><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
