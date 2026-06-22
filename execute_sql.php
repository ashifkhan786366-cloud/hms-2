<?php
require_once 'config/db.php'; // gets $pdo

$sql_file = __DIR__ . '/sql/billing_tables.sql';

if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    try {
        $pdo->exec($sql);
        echo "Successfully executed billing tables SQL!\n";
    } catch (PDOException $e) {
        echo "Error executing SQL: " . $e->getMessage() . "\n";
    }
} else {
    echo "SQL file not found at $sql_file\n";
}
