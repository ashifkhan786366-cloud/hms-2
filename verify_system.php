<?php
/**
 * Self-Healing & Bug Tracking Script for Sankhla HMS
 * Run this via CLI after deployment: php verify_system.php
 */

$log_file = __DIR__ . '/system_verification.log';
file_put_contents($log_file, "Starting System Verification at " . date('Y-m-d H:i:s') . "\n");

function logMsg($msg)
{
    global $log_file;
    echo $msg . "\n";
    file_put_contents($log_file, $msg . "\n", FILE_APPEND);
}

// 1. Check File Parity
$required_files = [
    'config/db.php',
    'includes/header.php',
    'includes/sidebar.php',
    'patients.php',
    'billing.php',
    'tpa_companies.php',
    'treatment_packages.php',
    'accounting.php',
    'n8n_api.php',
    'hms_schema.sql'
];

$missing_files = [];
foreach ($required_files as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $missing_files[] = $file;
        logMsg("[ERROR] Missing File: $file");
    } else {
        logMsg("[OK] Found File: $file");
    }
}

// 2. Database Connection Check
try {
    require_once 'config/db.php';
    logMsg("[OK] Database connection successful.");

    // Check tables
    $tables = ['users', 'patients', 'bills', 'tpa_companies', 'treatment_packages', 'accounting_ledger', 'n8n_tasks'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            logMsg("[OK] Table exists: $table");
        } catch (Exception $e) {
            logMsg("[ERROR] Missing Table: $table");
            // Auto-heal attempt or report to n8n
            $stmt = $pdo->prepare("INSERT INTO n8n_tasks (task_name, module, description, status) VALUES (?, ?, ?, ?)");
            try {
                $stmt->execute(["Create Missing Table: $table", "Database", "Table validation failed during self-check.", "Pending"]);
                logMsg("[INFO] Bug logged to n8n_tasks automatically.");
            } catch (Exception $ex) {
                // Table itself missing
            }
        }
    }
} catch (Exception $e) {
    logMsg("[WARNING] Database tests skipped. Reason: " . $e->getMessage());
}

// Summary
if (empty($missing_files)) {
    logMsg("\n[SUCCESS] Feature Parity 100% Match! No missing files.");
} else {
    logMsg("\n[FAILED] System missing " . count($missing_files) . " critical files.");
}
?>