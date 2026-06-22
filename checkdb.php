<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config/db.php';

try {
    // PostgreSQL: list all tables in public schema
    $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre style='font-family:monospace;padding:20px'>";
    echo "✅ Connected to database!\n\nTables:\n";
    foreach ($tables as $table) {
        echo "  - " . $table . "\n";
    }
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color:red;padding:20px'>❌ Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
