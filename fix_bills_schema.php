<?php
/**
 * fix_bills_schema.php
 * One-time migration for Bug Fixes 1-5
 * Visit: http://localhost/hms/fix_bills_schema.php
 * Delete this file after running.
 */
require_once __DIR__ . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');
echo '<style>body{font-family:monospace;padding:20px;background:#111;color:#eee;}
      .ok{color:#4caf50;} .warn{color:#ff9800;} .err{color:#f44336;}</style>';
echo '<h2>🔧 HMS Bills Schema Fix</h2><hr>';

$fixes = [
    // bills table
    "ALTER TABLE `bills` ADD COLUMN `balance_due` DECIMAL(12,2) DEFAULT 0.00"             => "bills.balance_due",
    "ALTER TABLE `bills` ADD COLUMN `payment_status` VARCHAR(20) DEFAULT 'Paid'"           => "bills.payment_status",
    "ALTER TABLE `bills` ADD COLUMN `bill_date` DATETIME DEFAULT NULL"                     => "bills.bill_date",
    "ALTER TABLE `bills` ADD COLUMN `payment_mode_cash` DECIMAL(12,2) DEFAULT 0.00"        => "bills.payment_mode_cash",
    "ALTER TABLE `bills` ADD COLUMN `payment_mode_upi`  DECIMAL(12,2) DEFAULT 0.00"        => "bills.payment_mode_upi",
    "ALTER TABLE `bills` ADD COLUMN `bill_type` VARCHAR(20) DEFAULT 'OPD'"                 => "bills.bill_type",
    "ALTER TABLE `bills` ADD COLUMN `modified_at` DATETIME DEFAULT NULL"                   => "bills.modified_at",
    "ALTER TABLE `bills` ADD COLUMN `modified_by` INT DEFAULT NULL"                        => "bills.modified_by",
    // bill_items table
    "ALTER TABLE `bill_items` ADD COLUMN `item_type` VARCHAR(50) DEFAULT 'General'"        => "bill_items.item_type",
];

// Backfill bill_date from created_at where null
$backfills = [
    "UPDATE `bills` SET `bill_date` = `created_at` WHERE `bill_date` IS NULL AND `created_at` IS NOT NULL" 
        => "Backfill bill_date from created_at",
    "UPDATE `bills` SET `payment_status` = CASE WHEN `paid_amount` >= `net_amount` THEN 'Paid'
        WHEN `paid_amount` > 0 THEN 'Partial' ELSE 'Pending' END WHERE `payment_status` = 'Paid' OR `payment_status` IS NULL"
        => "Sync payment_status from paid vs net amounts",
    "UPDATE `bills` SET `balance_due` = `net_amount` - `paid_amount` WHERE `balance_due` = 0"
        => "Backfill balance_due"
];

foreach ($fixes as $sql => $label) {
    try {
        $pdo->exec($sql);
        echo "<div class='ok'>✅ Added: <strong>$label</strong></div>";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Duplicate column') !== false || strpos($msg, 'already exists') !== false) {
            echo "<div class='warn'>⚠️ Already exists: <strong>$label</strong></div>";
        } else {
            echo "<div class='err'>❌ Error on $label: $msg</div>";
        }
    }
}

echo '<br><hr><h3>🔄 Backfilling data...</h3>';

foreach ($backfills as $sql => $label) {
    try {
        $rows = $pdo->exec($sql);
        echo "<div class='ok'>✅ $label — {$rows} rows updated</div>";
    } catch (PDOException $e) {
        echo "<div class='warn'>⚠️ $label — " . $e->getMessage() . "</div>";
    }
}

echo '<br><hr>';
echo '<div class="ok" style="font-size:18px;"><strong>✅ Schema fix complete!</strong></div>';
echo '<br><a href="daily_report.php" style="color:#4fc3f7">→ Go to Daily Report</a>';
echo ' &nbsp; <a href="billing_app.php" style="color:#4fc3f7">→ Billing</a>';
echo '<br><br><em style="color:#888">You can delete this file after running it.</em>';
?>
