CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(50) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NULL,
    bill_type VARCHAR(50) NOT NULL COMMENT 'OPD, IPD, Pharmacy, Lab',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_mode VARCHAR(50) NOT NULL COMMENT 'Cash, Card, UPI, Insurance',
    status VARCHAR(50) NOT NULL DEFAULT 'Paid' COMMENT 'Paid, Partial, Unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(100) NULL,
    qty DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bill_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NULL
);

INSERT IGNORE INTO bill_settings (setting_key, setting_value) VALUES
('hospital_name', 'Sankhla Hospital'),
('hospital_address', '123 Main Street, City'),
('hospital_phone', '9876543210'),
('hospital_logo', ''),
('bill_prefix', 'BILL'),
('gst_number', ''),
('enable_gst', '1'),
('default_payment_mode', 'Cash'),
('print_size', 'A4'),
('primary_color', '#007bff'),
('secondary_color', '#6c757d'),
('header_text', 'Thank you for choosing Sankhla Hospital'),
('footer_text', 'Terms & Conditions apply'),
('show_discount_col', '1'),
('show_tax_col', '1');
