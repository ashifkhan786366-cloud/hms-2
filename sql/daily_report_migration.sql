-- Daily Collection Report System — Migration
-- Run this once to add tables needed for voucher module and report locking.

CREATE TABLE IF NOT EXISTS `daily_vouchers` (
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
  KEY `idx_voucher_date` (`voucher_date`),
  KEY `idx_voucher_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `daily_report_locks` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `report_date` DATE NOT NULL UNIQUE,
  `locked_by`   INT DEFAULT NULL,
  `locked_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional: Index on bill_items.item_type for fast lab collection query
-- (Safe — only adds index, doesn't change existing schema)
CREATE INDEX IF NOT EXISTS `idx_bill_items_item_type` ON `bill_items` (`item_type`(20));
