-- ============================================================
-- Hospital Management System - PostgreSQL Schema
-- Converted from MySQL for Aiven PostgreSQL deployment
-- ============================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id          SERIAL PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        VARCHAR(50)  NOT NULL CHECK (role IN ('Admin','Receptionist','Doctor','Nurse','Lab Technician','Pharmacist','Accountant')),
    email       VARCHAR(100) DEFAULT NULL,
    phone       VARCHAR(20)  DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (full_name, username, password, role, email, phone) VALUES
('Super Admin',     'admin',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin',        'admin@hospital.com',     '1234567890'),
('Dr. B.K. Sankhla','doctor',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor',       'doctor@hospital.com',    '9876543210'),
('Reception Desk',  'reception', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Receptionist', 'reception@hospital.com', '1122334455')
ON CONFLICT (username) DO NOTHING;
-- Password for all users: 'password'

-- Patients table
CREATE TABLE IF NOT EXISTS patients (
    id          SERIAL PRIMARY KEY,
    mr_number   VARCHAR(20)  NOT NULL UNIQUE,
    full_name   VARCHAR(100) NOT NULL,
    gender      VARCHAR(10)  NOT NULL CHECK (gender IN ('Male','Female','Other')),
    age         INT          NOT NULL,
    dob         DATE         DEFAULT NULL,
    phone       VARCHAR(20)  DEFAULT NULL,
    address     TEXT,
    photo_path  VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id           SERIAL PRIMARY KEY,
    patient_id   INT          NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    doctor_id    INT          NOT NULL REFERENCES users(id),
    visit_date   DATE         NOT NULL,
    token_number INT          NOT NULL,
    status       VARCHAR(20)  DEFAULT 'Pending' CHECK (status IN ('Pending','Checked','Cancelled')),
    symptoms     TEXT,
    bp           VARCHAR(20)  DEFAULT NULL,
    pulse        VARCHAR(20)  DEFAULT NULL,
    temperature  VARCHAR(20)  DEFAULT NULL,
    weight       VARCHAR(20)  DEFAULT NULL,
    notes        TEXT,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- Prescriptions table
CREATE TABLE IF NOT EXISTS prescriptions (
    id             SERIAL PRIMARY KEY,
    appointment_id INT NOT NULL REFERENCES appointments(id) ON DELETE CASCADE,
    patient_id     INT NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    doctor_id      INT NOT NULL REFERENCES users(id),
    diagnosis      TEXT,
    advice         TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Prescription medicines table
CREATE TABLE IF NOT EXISTS prescription_medicines (
    id              SERIAL PRIMARY KEY,
    prescription_id INT          NOT NULL REFERENCES prescriptions(id) ON DELETE CASCADE,
    medicine_name   VARCHAR(100) NOT NULL,
    dosage          VARCHAR(50)  NOT NULL,
    duration        VARCHAR(50)  NOT NULL,
    instruction     VARCHAR(100) DEFAULT NULL
);

-- Prescription tests table
CREATE TABLE IF NOT EXISTS prescription_tests (
    id              SERIAL PRIMARY KEY,
    prescription_id INT          NOT NULL REFERENCES prescriptions(id) ON DELETE CASCADE,
    test_name       VARCHAR(100) NOT NULL,
    notes           VARCHAR(255) DEFAULT NULL
);

-- IPD Admissions table
CREATE TABLE IF NOT EXISTS ipd_admissions (
    id             SERIAL PRIMARY KEY,
    patient_id     INT         NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    doctor_id      INT         NOT NULL REFERENCES users(id),
    admission_date TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    discharge_date TIMESTAMP   DEFAULT NULL,
    bed_number     VARCHAR(50) DEFAULT NULL,
    ward_type      VARCHAR(50) DEFAULT 'General',
    diagnosis      TEXT,
    status         VARCHAR(20) DEFAULT 'Admitted' CHECK (status IN ('Admitted','Discharged'))
);

-- Services table
CREATE TABLE IF NOT EXISTS services (
    id           SERIAL PRIMARY KEY,
    service_name VARCHAR(100)   NOT NULL,
    cost         DECIMAL(10,2)  NOT NULL,
    type         VARCHAR(20)    NOT NULL CHECK (type IN ('OPD','Lab','IPD','Other'))
);

INSERT INTO services (service_name, cost, type) VALUES
('OPD Consultation',        500.00,  'OPD'),
('ECG',                     300.00,  'Lab'),
('X-Ray',                   400.00,  'Lab'),
('Blood Sugar',             100.00,  'Lab'),
('General Ward Bed Charge', 1000.00, 'IPD'),
('ICU Bed Charge',          3000.00, 'IPD')
ON CONFLICT DO NOTHING;

-- Bills table
CREATE TABLE IF NOT EXISTS bills (
    id                SERIAL PRIMARY KEY,
    bill_number       VARCHAR(50)   NOT NULL UNIQUE,
    patient_id        INT           NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    appointment_id    INT           DEFAULT NULL REFERENCES appointments(id),
    ipd_admission_id  INT           DEFAULT NULL REFERENCES ipd_admissions(id),
    bill_date         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount          DECIMAL(10,2) DEFAULT 0.00,
    tax               DECIMAL(10,2) DEFAULT 0.00,
    net_amount        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paid_amount       DECIMAL(10,2) DEFAULT 0.00,
    payment_status    VARCHAR(20)   DEFAULT 'Pending' CHECK (payment_status IN ('Pending','Partial','Paid')),
    payment_method    VARCHAR(20)   DEFAULT 'Cash'    CHECK (payment_method IN ('Cash','Card','UPI','Other')),
    generated_by      INT           NOT NULL REFERENCES users(id)
);

-- Bill items table
CREATE TABLE IF NOT EXISTS bill_items (
    id           SERIAL PRIMARY KEY,
    bill_id      INT           NOT NULL REFERENCES bills(id) ON DELETE CASCADE,
    service_name VARCHAR(100)  NOT NULL,
    cost         DECIMAL(10,2) NOT NULL,
    quantity     INT           DEFAULT 1,
    amount       DECIMAL(10,2) NOT NULL
);

-- Medicines table
CREATE TABLE IF NOT EXISTS medicines (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL,
    batch_no        VARCHAR(50)   DEFAULT NULL,
    expiry_date     DATE          DEFAULT NULL,
    stock_qty       INT           DEFAULT 0,
    price_per_unit  DECIMAL(10,2) NOT NULL,
    manufacturer    VARCHAR(100)  DEFAULT NULL
);

INSERT INTO medicines (name, stock_qty, price_per_unit) VALUES
('Paracetamol 500mg',  1000, 2.00),
('Amoxicillin 500mg',  500,  10.00),
('Pantoprazole 40mg',  800,  8.00)
ON CONFLICT DO NOTHING;
