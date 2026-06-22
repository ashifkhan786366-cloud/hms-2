<?php
/**
 * daily_report_migrate.php
 * One-time migration runner for Daily Collection Report module.
 * Access: http://localhost/hms/daily_report_migrate.php
 */
require_once __DIR__ . '/config/db.php';

$results = [];
$errors  = [];

$sqls = [

    // 1. daily_vouchers table
    "CREATE TABLE IF NOT EXISTS `daily_vouchers` (
      `id`             INT AUTO_INCREMENT PRIMARY KEY,
      `voucher_number` VARCHAR(40) NOT NULL UNIQUE,
      `voucher_date`   DATE NOT NULL,
      `voucher_time`   TIME NOT NULL,
      `voucher_type`   ENUM('Receipt','Payment') NOT NULL DEFAULT 'Payment',
      `staff_name`     VARCHAR(100) DEFAULT NULL,
      `category`       VARCHAR(80) DEFAULT 'Other',
      `purpose`        TEXT NOT NULL,
      `amount`         DECIMAL(10,2) NOT NULL,
      `payment_mode`   ENUM('Cash','UPI','Bank Transfer','Other') DEFAULT 'Cash',
      `reference_no`   VARCHAR(60) DEFAULT NULL,
      `note`           TEXT DEFAULT NULL,
      `created_by`     INT DEFAULT NULL,
      `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      KEY `idx_voucher_date` (`voucher_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 2. daily_report_locks table
    "CREATE TABLE IF NOT EXISTS `daily_report_locks` (
      `id`          INT AUTO_INCREMENT PRIMARY KEY,
      `report_date` DATE NOT NULL UNIQUE,
      `locked_by`   INT DEFAULT NULL,
      `locked_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 3. Add item_type column to bill_items if not exists
    "ALTER TABLE `bill_items` ADD COLUMN `item_type` VARCHAR(50) DEFAULT 'General'",

    // 4. Add payment_mode_cash column to bills if not exists
    "ALTER TABLE `bills` ADD COLUMN `payment_mode_cash` DECIMAL(10,2) DEFAULT 0",

    // 5. Add payment_mode_upi column to bills if not exists
    "ALTER TABLE `bills` ADD COLUMN `payment_mode_upi` DECIMAL(10,2) DEFAULT 0",

    // 6. Add balance_due column to bills if not exists
    "ALTER TABLE `bills` ADD COLUMN `balance_due` DECIMAL(10,2) DEFAULT 0",

    // 7. Add bill_type column to bills if not exists
    "ALTER TABLE `bills` ADD COLUMN `bill_type` VARCHAR(20) DEFAULT 'OPD'",
];
// Note: ALTER TABLE ADD COLUMN errors (duplicate column) are caught below — safe to ignore.

foreach ($sqls as $i => $sql) {
    try {
        $pdo->exec($sql);
        $results[] = "✅ Statement " . ($i + 1) . " — OK";
    } catch (PDOException $e) {
        $errors[] = "⚠️ Statement " . ($i + 1) . " — " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Report Migration</title>
    <style>
        body { font-family: monospace; background: #111; color: #0f0; padding: 30px; }
        .ok  { color: #4caf50; margin: 5px 0; }
        .err { color: #f44336; margin: 5px 0; }
        .done{ color: #fff; font-size: 20px; margin-top: 20px; }
        a { color: #90caf9; }
    </style>
</head>
<body>
<h2 style="color:#fff">🏥 Daily Report Migration</h2>
<?php foreach ($results as $r): ?>
    <p class="ok"><?= htmlspecialchars($r) ?></p>
<?php endforeach; ?>
<?php foreach ($errors as $e): ?>
    <p class="err"><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>
<p class="done">
    ✅ Migration complete. 
    <?php if (empty($errors)): ?>All succeeded!<?php else: ?><?= count($errors) ?> warning(s) — may already exist.<?php endif; ?>
</p>
<p><a href="daily_report.php">→ Go to Daily Collection Report</a></p>
</body>
</html>
