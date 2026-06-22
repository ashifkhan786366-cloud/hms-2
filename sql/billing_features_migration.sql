-- =========================================================
-- HMS Billing Features Migration Script
-- Features: Split Payment, Discount, Bill Edit, Lab Auto-link
-- =========================================================

-- bill_items table mein missing columns add karo (safe: IF NOT EXISTS style)
SET @dbname = DATABASE();

-- 1. bill_items mein item_type add karo (Lab auto-link ke liye ZARURI)
ALTER TABLE `bill_items` 
    ADD COLUMN IF NOT EXISTS `item_type` VARCHAR(50) DEFAULT 'General',
    ADD COLUMN IF NOT EXISTS `discount_percent` DECIMAL(5,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `report_status` VARCHAR(20) DEFAULT NULL;

-- 2. bills table mein split payment columns add karo
ALTER TABLE `bills`
    ADD COLUMN IF NOT EXISTS `payment_mode_cash` DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `payment_mode_upi` DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `discount_type` VARCHAR(10) DEFAULT 'amount',
    ADD COLUMN IF NOT EXISTS `discount_percent` DECIMAL(5,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `last_edited_at` DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `bill_type` VARCHAR(20) DEFAULT 'OPD',
    ADD COLUMN IF NOT EXISTS `doctor_id` INT(11) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `balance_due` DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP;

-- 3. bills table mein payment_method ENUM update (Split ke liye 'Split' add)
-- Note: Agar ENUM update fail ho to IGNORE karo — 'Other' use hoga as fallback
ALTER TABLE `bills` MODIFY COLUMN `payment_method` 
    ENUM('Cash','Card','UPI','Insurance','Split','Other') DEFAULT 'Cash';

-- 4. Existing bill_items mein jo bhi report_status NULL hai unhe NULL rehne do
--    (sirf naye Lab items billing save ke time 'Pending' set honge)

-- Final check
SELECT 'Migration completed successfully' AS status;
