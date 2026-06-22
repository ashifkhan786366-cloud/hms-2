<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config/db.php';

// Token check for security
$token = $_GET['token'] ?? '';
$expected = getenv('INIT_TOKEN') ?: 'hms-sankhla-init-2024';
if ($token !== $expected) {
    http_response_code(403);
    die("❌ Unauthorized. Use ?token=YOUR_INIT_TOKEN");
}

try {
    // 1. Run main schema from file
    $schemaFile = __DIR__ . '/hms_schema_pg.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file hms_schema_pg.sql not found at $schemaFile");
    }
    
    $schemaSql = file_get_contents($schemaFile);
    $pdo->exec($schemaSql);
    echo "<p style='color:green;font-family:sans-serif;padding:10px 20px;margin:5px 0;'>✅ Main database tables created successfully!</p>";

    // 2. Run accounting module tables
    $accountingSql = "
    CREATE TABLE IF NOT EXISTS ac_accounts (
        id       SERIAL PRIMARY KEY,
        code     VARCHAR(50) DEFAULT NULL,
        name     VARCHAR(100) NOT NULL,
        type     VARCHAR(20)  NOT NULL CHECK (type IN ('Asset','Liability','Equity','Revenue','Expense')),
        is_active SMALLINT    DEFAULT 1
    );

    CREATE TABLE IF NOT EXISTS ac_journal_entries (
        id           SERIAL PRIMARY KEY,
        entry_date   DATE      NOT NULL,
        reference_no VARCHAR(50) DEFAULT NULL,
        description  TEXT,
        created_by   INT       DEFAULT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS ac_journal_items (
        id         SERIAL PRIMARY KEY,
        journal_id INT           NOT NULL REFERENCES ac_journal_entries(id) ON DELETE CASCADE,
        account_id INT           NOT NULL REFERENCES ac_accounts(id),
        debit      DECIMAL(10,2) DEFAULT 0.00,
        credit     DECIMAL(10,2) DEFAULT 0.00,
        patient_id INT           DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL
    );

    -- Seed Accounts
    INSERT INTO ac_accounts (id, code, name, type) VALUES
    (1,  'CASH',       'Cash in Hand',                   'Asset'),
    (2,  'BANK',       'Bank Account',                   'Asset'),
    (3,  'AR',         'Accounts Receivable (Patients)',  'Asset'),
    (4,  'REV_OPD',   'OPD Revenue',                    'Revenue'),
    (5,  'REV_IPD',   'IPD Revenue',                    'Revenue'),
    (6,  'REV_LAB',   'Laboratory Revenue',              'Revenue'),
    (7,  'EXP_SALARY','Salary Expense',                  'Expense'),
    (8,  'EXP_ELEC',  'Electricity Bill',                'Expense'),
    (9,  'EXP_MED',   'Medicine Purchases',              'Expense'),
    (10, 'EQUITY',    'Owner Equity',                    'Equity')
    ON CONFLICT (id) DO NOTHING;
    ";
    
    $pdo->exec($accountingSql);
    echo "<p style='color:green;font-family:sans-serif;padding:10px 20px;margin:5px 0;'>✅ Accounting tables created and seeded successfully!</p>";

} catch (Exception $e) {
    echo "<p style='color:red;font-family:sans-serif;padding:20px'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
