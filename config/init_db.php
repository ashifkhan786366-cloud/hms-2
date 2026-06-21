<?php
/**
 * Database Initialization Script
 * ================================
 * Ye script Railway pe pehli baar run karo — sab tables + demo data create ho jaayenge.
 * 
 * URL: https://your-app.up.railway.app/config/init_db.php
 * 
 * IMPORTANT: Ek baar setup ke baad, is file ka naam badal do ya delete kar do for security.
 */

// Security: Simple token check (change this token!)
$secret = $_GET['token'] ?? '';
$expected_token = getenv('INIT_TOKEN') ?: 'hms-setup-2024';

if ($secret !== $expected_token) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:30px;color:#c0392b;background:#fdf2f2;border-radius:8px;">
        <h2>🔒 Access Denied</h2>
        <p>Valid token required. Use: <code>?token=hms-setup-2024</code></p>
    </div>');
}

// Load DB config
require_once __DIR__ . '/db.php';

echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HMS Database Setup</title>
<style>
    body { font-family: "Segoe UI", sans-serif; background: #f0f4f8; margin: 0; padding: 30px; }
    .card { background: white; border-radius: 12px; padding: 30px; max-width: 700px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    h1 { color: #0066CC; border-bottom: 3px solid #0066CC; padding-bottom: 10px; }
    .step { padding: 12px 16px; margin: 8px 0; border-radius: 8px; }
    .ok   { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
    .skip { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
    .err  { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
    .done { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; font-size: 1.1em; font-weight: bold; }
    code  { background: #f1f1f1; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
</style>
</head>
<body>
<div class="card">
    <h1>🏥 HMS Database Setup</h1>
    <p><strong>Sankhla Hospital — Railway Deployment</strong></p>';

$errors = 0;
$tables_created = 0;
$tables_skipped = 0;

// SQL Statements — Each table separately for proper error handling
$sql_statements = [

    'users' => "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `full_name` varchar(100) NOT NULL,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `role` enum('Admin','Receptionist','Doctor','Nurse','Lab Technician','Pharmacist','Accountant') NOT NULL,
        `email` varchar(100) DEFAULT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'patients' => "CREATE TABLE IF NOT EXISTS `patients` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `mr_number` varchar(20) NOT NULL,
        `full_name` varchar(100) NOT NULL,
        `gender` enum('Male','Female','Other') NOT NULL,
        `age` int(11) NOT NULL,
        `dob` date DEFAULT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `address` text,
        `photo_path` varchar(255) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `mr_number` (`mr_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'appointments' => "CREATE TABLE IF NOT EXISTS `appointments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `patient_id` int(11) NOT NULL,
        `doctor_id` int(11) NOT NULL,
        `visit_date` date NOT NULL,
        `token_number` int(11) NOT NULL,
        `status` enum('Pending','Checked','Cancelled') DEFAULT 'Pending',
        `symptoms` text,
        `bp` varchar(20) DEFAULT NULL,
        `pulse` varchar(20) DEFAULT NULL,
        `temperature` varchar(20) DEFAULT NULL,
        `weight` varchar(20) DEFAULT NULL,
        `notes` text,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `patient_id` (`patient_id`),
        KEY `doctor_id` (`doctor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'prescriptions' => "CREATE TABLE IF NOT EXISTS `prescriptions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `appointment_id` int(11) NOT NULL,
        `patient_id` int(11) NOT NULL,
        `doctor_id` int(11) NOT NULL,
        `diagnosis` text,
        `advice` text,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `appointment_id` (`appointment_id`),
        KEY `patient_id` (`patient_id`),
        KEY `doctor_id` (`doctor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'prescription_medicines' => "CREATE TABLE IF NOT EXISTS `prescription_medicines` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `prescription_id` int(11) NOT NULL,
        `medicine_name` varchar(100) NOT NULL,
        `dosage` varchar(50) NOT NULL,
        `duration` varchar(50) NOT NULL,
        `instruction` varchar(100) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `prescription_id` (`prescription_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'prescription_tests' => "CREATE TABLE IF NOT EXISTS `prescription_tests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `prescription_id` int(11) NOT NULL,
        `test_name` varchar(100) NOT NULL,
        `notes` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `prescription_id` (`prescription_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'ipd_admissions' => "CREATE TABLE IF NOT EXISTS `ipd_admissions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `patient_id` int(11) NOT NULL,
        `doctor_id` int(11) NOT NULL,
        `admission_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `discharge_date` datetime DEFAULT NULL,
        `bed_number` varchar(50) DEFAULT NULL,
        `ward_type` varchar(50) DEFAULT 'General',
        `diagnosis` text,
        `status` enum('Admitted','Discharged') DEFAULT 'Admitted',
        PRIMARY KEY (`id`),
        KEY `patient_id` (`patient_id`),
        KEY `doctor_id` (`doctor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'services' => "CREATE TABLE IF NOT EXISTS `services` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `service_name` varchar(100) NOT NULL,
        `cost` decimal(10,2) NOT NULL,
        `type` enum('OPD','Lab','IPD','Other') NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'bills' => "CREATE TABLE IF NOT EXISTS `bills` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bill_number` varchar(50) NOT NULL,
        `patient_id` int(11) NOT NULL,
        `appointment_id` int(11) DEFAULT NULL,
        `ipd_admission_id` int(11) DEFAULT NULL,
        `bill_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
        `discount` decimal(10,2) DEFAULT 0.00,
        `tax` decimal(10,2) DEFAULT 0.00,
        `net_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
        `paid_amount` decimal(10,2) DEFAULT 0.00,
        `payment_status` enum('Pending','Partial','Paid') DEFAULT 'Pending',
        `payment_method` enum('Cash','Card','UPI','Other') DEFAULT 'Cash',
        `generated_by` int(11) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `bill_number` (`bill_number`),
        KEY `patient_id` (`patient_id`),
        KEY `appointment_id` (`appointment_id`),
        KEY `ipd_admission_id` (`ipd_admission_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'bill_items' => "CREATE TABLE IF NOT EXISTS `bill_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bill_id` int(11) NOT NULL,
        `service_name` varchar(100) NOT NULL,
        `cost` decimal(10,2) NOT NULL,
        `quantity` int(11) DEFAULT 1,
        `amount` decimal(10,2) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `bill_id` (`bill_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'medicines' => "CREATE TABLE IF NOT EXISTS `medicines` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `batch_no` varchar(50) DEFAULT NULL,
        `expiry_date` date DEFAULT NULL,
        `stock_qty` int(11) DEFAULT 0,
        `price_per_unit` decimal(10,2) NOT NULL,
        `manufacturer` varchar(100) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'lab_tests' => "CREATE TABLE IF NOT EXISTS `lab_tests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `patient_id` int(11) NOT NULL,
        `appointment_id` int(11) DEFAULT NULL,
        `test_name` varchar(100) NOT NULL,
        `result` text DEFAULT NULL,
        `status` enum('Pending','Completed') DEFAULT 'Pending',
        `requested_by` int(11) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'journal_entries' => "CREATE TABLE IF NOT EXISTS `journal_entries` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `entry_date` date NOT NULL,
        `description` varchar(255) NOT NULL,
        `debit_account` varchar(100) NOT NULL,
        `credit_account` varchar(100) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `reference` varchar(100) DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

// Create tables
foreach ($sql_statements as $table => $sql) {
    try {
        $pdo->exec($sql);
        $tables_created++;
        echo "<div class='step ok'>✅ Table <code>$table</code> — Ready</div>";
    } catch (PDOException $e) {
        $errors++;
        echo "<div class='step err'>❌ Table <code>$table</code> — Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Foreign Key Constraints (run after all tables exist)
$constraints = [
    "ALTER TABLE `appointments` ADD CONSTRAINT IF NOT EXISTS `appt_patient_fk` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE",
    "ALTER TABLE `appointments` ADD CONSTRAINT IF NOT EXISTS `appt_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`)",
    "ALTER TABLE `prescriptions` ADD CONSTRAINT IF NOT EXISTS `presc_appt_fk` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE",
    "ALTER TABLE `prescriptions` ADD CONSTRAINT IF NOT EXISTS `presc_patient_fk` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE",
    "ALTER TABLE `ipd_admissions` ADD CONSTRAINT IF NOT EXISTS `ipd_patient_fk` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE",
    "ALTER TABLE `bills` ADD CONSTRAINT IF NOT EXISTS `bills_patient_fk` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE",
    "ALTER TABLE `bill_items` ADD CONSTRAINT IF NOT EXISTS `bitems_bill_fk` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE",
];

foreach ($constraints as $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // Ignore if already exists
    }
}

// Insert Default Data
try {
    // Check if admin already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();

    if ($user_count == 0) {
        // Insert default users
        $pdo->exec("INSERT INTO `users` (`full_name`, `username`, `password`, `role`, `email`, `phone`) VALUES
            ('Super Admin', 'admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'admin@hospital.com', '1234567890'),
            ('Dr. B.K. Sankhla', 'doctor', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', 'bksankhlahospital@gmail.com', '9829208462'),
            ('Reception Desk', 'reception', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Receptionist', 'reception@hospital.com', '1122334455')");
        echo "<div class='step ok'>✅ Default users inserted (admin, doctor, reception) — Password: <code>password</code></div>";
    } else {
        echo "<div class='step skip'>⏭️ Users already exist ($user_count found) — Skipped</div>";
    }

    // Services
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO `services` (`service_name`, `cost`, `type`) VALUES
            ('OPD Consultation', 500.00, 'OPD'),
            ('ECG', 300.00, 'Lab'),
            ('X-Ray', 400.00, 'Lab'),
            ('Blood Sugar', 100.00, 'Lab'),
            ('CBC (Complete Blood Count)', 200.00, 'Lab'),
            ('Urine Routine', 150.00, 'Lab'),
            ('General Ward Bed Charge', 1000.00, 'IPD'),
            ('Semi-Private Ward', 2000.00, 'IPD'),
            ('ICU Bed Charge', 3000.00, 'IPD'),
            ('Emergency Consultation', 800.00, 'Other')");
        echo "<div class='step ok'>✅ Default services inserted (10 services)</div>";
    } else {
        echo "<div class='step skip'>⏭️ Services already exist — Skipped</div>";
    }

    // Medicines
    $stmt = $pdo->query("SELECT COUNT(*) FROM medicines");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO `medicines` (`name`, `batch_no`, `stock_qty`, `price_per_unit`, `manufacturer`) VALUES
            ('Paracetamol 500mg', 'B001', 1000, 2.00, 'Generic'),
            ('Amoxicillin 500mg', 'B002', 500, 10.00, 'Generic'),
            ('Pantoprazole 40mg', 'B003', 800, 8.00, 'Generic'),
            ('Metformin 500mg', 'B004', 600, 5.00, 'Generic'),
            ('Amlodipine 5mg', 'B005', 400, 7.00, 'Generic'),
            ('Atorvastatin 10mg', 'B006', 300, 12.00, 'Generic'),
            ('Cefixime 200mg', 'B007', 200, 25.00, 'Generic'),
            ('ORS Sachet', 'B008', 2000, 3.00, 'Generic')");
        echo "<div class='step ok'>✅ Default medicines inserted (8 medicines)</div>";
    } else {
        echo "<div class='step skip'>⏭️ Medicines already exist — Skipped</div>";
    }

} catch (PDOException $e) {
    $errors++;
    echo "<div class='step err'>❌ Demo data error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Summary
if ($errors === 0) {
    echo "
    <div class='step done' style='margin-top:20px;font-size:1.2em;'>
        🎉 Setup Complete! All $tables_created tables created successfully.<br>
        <a href='../index.php' style='color:#0066CC;text-decoration:none;font-size:0.9em;'>➡️ Go to HMS Dashboard →</a>
    </div>
    <div class='step' style='background:#fff8e1;margin-top:10px;border-left:4px solid #ff9800;'>
        ⚠️ <strong>Security:</strong> Please rename or delete this file after setup!<br>
        <code>config/init_db.php</code> → rename to something like <code>config/init_db_DONE.php</code>
    </div>";
} else {
    echo "<div class='step err' style='margin-top:20px;'>⚠️ Setup completed with $errors error(s). Check above for details.</div>";
}

echo "
    <hr style='margin-top:20px;'>
    <p style='color:#888;font-size:0.85em;'>HMS — Sankhla Hospital, Jhotwara, Jaipur | Deployed on Railway.app</p>
</div></body></html>";
?>
