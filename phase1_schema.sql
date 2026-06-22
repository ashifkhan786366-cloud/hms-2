-- file: c:/xampp/htdocs/hms/phase1_schema.sql

CREATE TABLE IF NOT EXISTS `print_template_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hospital_id` INT DEFAULT 1,
    `logo_path` VARCHAR(255) DEFAULT NULL,
    `header_text` TEXT,
    `footer_text` TEXT,
    `font_family` VARCHAR(100) DEFAULT 'Arial, sans-serif',
    `primary_color` VARCHAR(20) DEFAULT '#0056b3',
    `secondary_color` VARCHAR(20) DEFAULT '#6c757d',
    `show_watermark` TINYINT(1) DEFAULT 0,
    `watermark_path` VARCHAR(255) DEFAULT NULL,
    `page_size` ENUM('A4', 'A5', 'thermal') DEFAULT 'A4',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bill_partitions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `partition_key` ENUM('LAB','OPD','PHARMACY','ROOM','PROCEDURE','OTHER') NOT NULL,
    `label` VARCHAR(100) NOT NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `sub_total_visible` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

TRUNCATE TABLE `bill_partitions`;
INSERT INTO `bill_partitions` (`partition_key`, `label`, `sort_order`, `is_active`, `sub_total_visible`) VALUES
('LAB', 'LABORATORY', 5, 1, 1),
('OPD', 'OPD CONSULTATION CHARGES', 2, 1, 1),
('PHARMACY', 'PHARMACY', 6, 1, 1),
('ROOM', 'ROOM CHARGES', 1, 1, 1),
('PROCEDURE', 'PROCEDURE CHARGES', 4, 1, 1),
('OTHER', 'OTHER CHARGES', 3, 1, 1);
