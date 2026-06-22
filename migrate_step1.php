<?php
/**
 * migrate_step1.php — HMS DB Migration (MySQL 5.x compatible)
 * Uses INFORMATION_SCHEMA to check columns before adding
 */
require_once __DIR__ . '/config/db.php';

// Get current database name
$dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();

function column_exists(PDO $pdo, string $dbName, string $table, string $column): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$dbName, $table, $column]);
    return (bool)$stmt->fetchColumn();
}

function table_exists(PDO $pdo, string $dbName, string $table): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
    ");
    $stmt->execute([$dbName, $table]);
    return (bool)$stmt->fetchColumn();
}

function addColumn(PDO $pdo, string $dbName, string $table, string $column, string $definition, string &$log): void {
    if (!table_exists($pdo, $dbName, $table)) {
        $log .= "<p class='skip'>⏭️ SKIP — table <strong>{$table}</strong> does not exist yet</p>";
        return;
    }
    if (column_exists($pdo, $dbName, $table, $column)) {
        $log .= "<p class='skip'>⏭️ SKIP (already exists): <strong>{$table}.{$column}</strong></p>";
        return;
    }
    try {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        $log .= "<p class='ok'>✅ ADDED: <strong>{$table}.{$column}</strong> {$definition}</p>";
    } catch (PDOException $e) {
        $log .= "<p class='err'>❌ FAILED: {$table}.{$column} — " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

$log = '';

// ──────────────────────────────────────────
// BILLS TABLE
// ──────────────────────────────────────────
addColumn($pdo, $dbName, 'bills', 'balance_due',        "DECIMAL(10,2) DEFAULT 0", $log);
addColumn($pdo, $dbName, 'bills', 'payment_status',     "VARCHAR(20) DEFAULT 'Pending'", $log);
addColumn($pdo, $dbName, 'bills', 'bill_date',          "DATETIME", $log);
addColumn($pdo, $dbName, 'bills', 'modified_at',        "DATETIME", $log);
addColumn($pdo, $dbName, 'bills', 'modified_by',        "INT DEFAULT NULL", $log);
addColumn($pdo, $dbName, 'bills', 'payment_mode_cash',  "DECIMAL(10,2) DEFAULT 0", $log);
addColumn($pdo, $dbName, 'bills', 'payment_mode_upi',   "DECIMAL(10,2) DEFAULT 0", $log);
addColumn($pdo, $dbName, 'bills', 'bill_type',          "VARCHAR(20) DEFAULT 'OPD'", $log);
addColumn($pdo, $dbName, 'bills', 'discount_type',      "VARCHAR(20) DEFAULT 'amount'", $log);
addColumn($pdo, $dbName, 'bills', 'discount_percent',   "DECIMAL(5,2) DEFAULT 0", $log);
addColumn($pdo, $dbName, 'bills', 'last_edited_at',     "DATETIME", $log);
addColumn($pdo, $dbName, 'bills', 'paid_amount',        "DECIMAL(10,2) DEFAULT 0", $log);

// ──────────────────────────────────────────
// PAYMENTS TABLE
// ──────────────────────────────────────────
if (table_exists($pdo, $dbName, 'payments')) {
    addColumn($pdo, $dbName, 'payments', 'cash_amount', "DECIMAL(10,2) DEFAULT 0", $log);
    addColumn($pdo, $dbName, 'payments', 'upi_amount',  "DECIMAL(10,2) DEFAULT 0", $log);
    addColumn($pdo, $dbName, 'payments', 'card_amount', "DECIMAL(10,2) DEFAULT 0", $log);
    addColumn($pdo, $dbName, 'payments', 'remarks',     "TEXT", $log);
} else {
    // Create payments table from scratch
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                bill_id      INT NOT NULL,
                amount       DECIMAL(10,2) NOT NULL,
                payment_mode VARCHAR(30) DEFAULT 'Cash',
                cash_amount  DECIMAL(10,2) DEFAULT 0,
                upi_amount   DECIMAL(10,2) DEFAULT 0,
                card_amount  DECIMAL(10,2) DEFAULT 0,
                remarks      TEXT,
                created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX(bill_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $log .= "<p class='ok'>✅ CREATED: <strong>payments</strong> table (new)</p>";
    } catch (PDOException $e) {
        $log .= "<p class='err'>❌ CREATE payments: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// ──────────────────────────────────────────
// BILL_ITEMS TABLE
// ──────────────────────────────────────────
addColumn($pdo, $dbName, 'bill_items', 'item_type',        "VARCHAR(50) DEFAULT 'General'", $log);
addColumn($pdo, $dbName, 'bill_items', 'discount_percent', "DECIMAL(5,2) DEFAULT 0", $log);
addColumn($pdo, $dbName, 'bill_items', 'report_status',    "VARCHAR(20) DEFAULT NULL", $log);
addColumn($pdo, $dbName, 'bill_items', 'lab_result',       "TEXT", $log);

// ──────────────────────────────────────────
// BILL_PARTITIONS TABLE (for bill_print.php grouping)
// ──────────────────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bill_partitions (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            partition_key     VARCHAR(50) NOT NULL,
            label             VARCHAR(100) NOT NULL,
            sort_order        INT DEFAULT 0,
            sub_total_visible TINYINT(1) DEFAULT 1,
            is_active         TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $log .= "<p class='ok'>✅ CREATE IF NOT EXISTS: <strong>bill_partitions</strong></p>";
} catch (PDOException $e) {
    $log .= "<p class='err'>❌ bill_partitions: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Seed default partitions
try {
    $cnt = $pdo->query("SELECT COUNT(*) FROM bill_partitions")->fetchColumn();
    if ($cnt == 0) {
        $pdo->exec("INSERT INTO bill_partitions (partition_key, label, sort_order, sub_total_visible) VALUES
            ('CONSULTATION', 'Consultation Charges', 1, 1),
            ('LAB',          'Laboratory / Investigations', 2, 1),
            ('PROCEDURE',    'Procedures', 3, 1),
            ('MEDICINE',     'Medicines / Pharmacy', 4, 1),
            ('ROOM',         'Room / Bed Charges', 5, 1),
            ('OTHER',        'Other Charges', 6, 1)
        ");
        $log .= "<p class='ok'>✅ SEEDED: 6 default partitions</p>";
    } else {
        $log .= "<p class='skip'>⏭️ bill_partitions already has {$cnt} rows — skip seed</p>";
    }
} catch (PDOException $e) {
    $log .= "<p class='err'>❌ Seed partitions: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ──────────────────────────────────────────
// POST-FIX: backfill bill_date from created_at or NOW()
// ──────────────────────────────────────────
if (column_exists($pdo, $dbName, 'bills', 'bill_date')) {
    try {
        // Try to copy from created_at if it exists
        if (column_exists($pdo, $dbName, 'bills', 'created_at')) {
            $n1 = $pdo->exec("UPDATE bills SET bill_date = created_at WHERE bill_date IS NULL AND created_at IS NOT NULL");
            $log .= "<p class='ok'>✅ POST-FIX: Copied created_at → bill_date for {$n1} rows</p>";
        }
        $n2 = $pdo->exec("UPDATE bills SET bill_date = NOW() WHERE bill_date IS NULL");
        $log .= "<p class='ok'>✅ POST-FIX: bill_date = NOW() for {$n2} remaining null rows</p>";
    } catch (PDOException $e) {
        $log .= "<p class='err'>❌ POST-FIX bill_date: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// ──────────────────────────────────────────
// POST-FIX: sync balance_due = net_amount - paid_amount
// ──────────────────────────────────────────
if (column_exists($pdo, $dbName, 'bills', 'balance_due') && column_exists($pdo, $dbName, 'bills', 'paid_amount')) {
    try {
        $n3 = $pdo->exec("UPDATE bills SET balance_due = GREATEST(0, net_amount - paid_amount) WHERE balance_due = 0 OR balance_due IS NULL");
        $log .= "<p class='ok'>✅ POST-FIX: Synced balance_due for {$n3} bills</p>";
    } catch (PDOException $e) {
        $log .= "<p class='err'>❌ POST-FIX balance_due: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// ──────────────────────────────────────────
// SHOW CURRENT BILLS TABLE STRUCTURE
// ──────────────────────────────────────────
$cols = [];
try {
    $stmt = $pdo->query("DESCRIBE bills");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$hasErrors = strpos($log, 'FAILED') !== false;
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>HMS Migration Step 1</title>
<style>
body{font-family:Arial,sans-serif;padding:30px;background:#f5f5f5;max-width:900px;margin:0 auto;}
.ok{color:#2e7d32;} .err{color:#c62828;font-weight:bold;} .skip{color:#e65100;}
h2{color:#1a237e;} .card{background:#fff;padding:20px;border-radius:8px;margin:15px 0;box-shadow:0 2px 6px rgba(0,0,0,.1);}
.done{background:#e8f5e9;border:2px solid #4caf50;padding:16px;border-radius:8px;margin-top:20px;}
.fail-box{background:#ffebee;border:2px solid #f44336;padding:16px;border-radius:8px;margin-top:20px;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{background:#37474f;color:#fff;padding:8px;}
td{padding:6px 8px;border-bottom:1px solid #eee;}
a.btn{display:inline-block;padding:8px 20px;background:#1565c0;color:#fff;border-radius:5px;text-decoration:none;margin:4px;}
a.btn-green{background:#2e7d32;}
</style></head><body>
<h2>🏥 HMS Database Migration — Step 1</h2>
<p>Database: <strong><?= htmlspecialchars($dbName) ?></strong></p>
<div class="card"><?= $log ?></div>

<?php if (!$hasErrors): ?>
<div class="done">
    <h3>✅ Migration Successful!</h3>
    <p>All required columns are in place. You can now test the system.</p>
    <div style="margin-top:12px">
        <a href="bill_print.php?id=1" class="btn" target="_blank">🖨 Test bill_print.php</a>
        <a href="patient_view.php?id=1" class="btn" target="_blank">👤 Test patient_view.php</a>
        <a href="daily_report.php" class="btn btn-green" target="_blank">📊 Daily Report</a>
    </div>
</div>
<?php else: ?>
<div class="fail-box">
    <h3>⚠️ Some migrations had errors (see above)</h3>
</div>
<?php endif; ?>

<?php if (!empty($cols)): ?>
<div class="card">
    <h4>Current Bills Table Columns</h4>
    <table>
        <tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>
        <?php foreach ($cols as $c): ?>
        <tr>
            <td><strong><?= htmlspecialchars($c['Field']) ?></strong></td>
            <td><?= htmlspecialchars($c['Type']) ?></td>
            <td><?= htmlspecialchars($c['Null']) ?></td>
            <td><?= htmlspecialchars($c['Default'] ?? 'NULL') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
</body></html>
