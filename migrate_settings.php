<?php
// migrate_settings.php
require_once __DIR__ . '/config/db.php';

try {
    // Create Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `hospital_settings` (
          `setting_key` varchar(100) NOT NULL,
          `setting_value` text NOT NULL,
          PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $default_settings = [
        'APP_NAME' => defined('APP_NAME') ? APP_NAME : 'SANKHLA HOSPITAL HEART & TRUMA CENTER',
        'APP_SHORT_NAME' => defined('APP_SHORT_NAME') ? APP_SHORT_NAME : 'SANKHLA HOSPITAL',
        'APP_ADDRESS' => defined('APP_ADDRESS') ? APP_ADDRESS : 'GOVT. DISS.NEAR KANJI PETROL PUMP,NEWARU ROAD,JHOTWARA,JAIPUR',
        'APP_PHONE' => defined('APP_PHONE') ? APP_PHONE : '9829208462',
        'APP_EMAIL' => defined('APP_EMAIL') ? APP_EMAIL : 'bksankhlahospital@gmail.com',
        'APP_LOGO' => defined('APP_LOGO') ? APP_LOGO : 'assets/logo.png',
        'CURRENCY' => '₹',
        'PRIMARY_COLOR' => '#0066CC',
        'SECONDARY_COLOR' => '#2C2C2C',
        'HEADER_FONT' => "'Roboto', Arial, sans-serif",
        'BILL_TERMS_CONDITIONS' => "1. All payments in favour of \"Sankhla Hospital Heart & Trauma Center\" only.\n2. This is a computer generated bill, no signature required.\n3. Medicines / consumables once issued will not be returned or exchanged.\n4. Subject to Jaipur jurisdiction only.",
        'BILL_FOOTER_MESSAGE' => "Get Well Soon!",
        'OPD_FOOTER_MESSAGE' => "* Not valid for medico-legal purpose\n* In case of emergency, contact immediately\n* Please bring this prescription on next visit",
        'PRINT_THEME_COLOR' => '#9dc3e6'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO hospital_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($default_settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    echo "Settings Table Migrated and Seeded Successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>