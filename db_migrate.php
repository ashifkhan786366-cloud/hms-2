<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'c:/Users/ok/.gemini/antigravity/playground/ultraviolet-singularity/hms/config/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `ac_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ac_journal_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `description` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ac_journal_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(10,2) DEFAULT 0.00,
  `credit` decimal(10,2) DEFAULT 0.00,
  `patient_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ac_journal` FOREIGN KEY (`journal_id`) REFERENCES `ac_journal_entries`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ac_accid` FOREIGN KEY (`account_id`) REFERENCES `ac_accounts`(`id`),
  CONSTRAINT `fk_ac_patid` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Accounts
INSERT IGNORE INTO `ac_accounts` (`id`, `code`, `name`, `type`) VALUES
(1, 'CASH', 'Cash in Hand', 'Asset'),
(2, 'BANK', 'Bank Account', 'Asset'),
(3, 'AR', 'Accounts Receivable (Patients)', 'Asset'),
(4, 'REV_OPD', 'OPD Revenue', 'Revenue'),
(5, 'REV_IPD', 'IPD Revenue', 'Revenue'),
(6, 'REV_LAB', 'Laboratory Revenue', 'Revenue'),
(7, 'EXP_SALARY', 'Salary Expense', 'Expense'),
(8, 'EXP_ELEC', 'Electricity Bill', 'Expense'),
(9, 'EXP_MED', 'Medicine Purchases', 'Expense'),
(10, 'EQUITY', 'Owner Equity', 'Equity');
";

try {
  $pdo->exec($sql);
  echo "Accounting tables created successfully!";
} catch (PDOException $e) {
  echo "Error creating tables: " . $e->getMessage();
}
