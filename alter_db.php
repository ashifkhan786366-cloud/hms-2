<?php
require 'config/db.php';
try {
    $pdo->exec("ALTER TABLE bills ADD COLUMN bill_type VARCHAR(50) NULL DEFAULT 'OPD'");
    echo "Added bill_type to bills.\n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE bill_items ADD COLUMN item_type VARCHAR(100) NULL");
    echo "Added item_type to bill_items.\n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE bill_items ADD COLUMN discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00");
    echo "Added discount_percent to bill_items.\n";
} catch(Exception $e) {}

echo "DB Update Complete.";
