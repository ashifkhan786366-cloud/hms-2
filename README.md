# 🏥 Hospital Management System (HMS)
### Sankhla Hospital - Jhotwara, Jaipur

A complete, lightweight **Hospital Management System** built with **Plain PHP + MySQL**. Designed for XAMPP/WAMP local installations.

---

## ✨ Features

| Module | Description |
|--------|-------------|
| 🏠 **Dashboard** | Real-time stats - OPD, Revenue, IPD, New Patients |
| 👤 **Patients** | Registration, MR Number, Photo Upload, EMR |
| 🩺 **OPD** | Token system, Doctor consultation, Prescriptions |
| 🛏️ **IPD** | Admissions, Ward/Bed management, Discharge |
| 💊 **Pharmacy** | Medicine stock, Expiry tracking |
| 🧪 **Laboratory** | Test orders, Result management |
| 💰 **Billing** | GST-ready invoices, Print support, Payment tracking |
| 📊 **Reports** | Daily, Monthly, Revenue reports |
| 👥 **Users & Roles** | Admin, Doctor, Receptionist, Nurse, Lab Tech, Pharmacist |
| ⚙️ **Settings** | Hospital info, Logo, Colors customization |

---

## 🚀 Quick Start (Local / XAMPP)

### Step 1: Download & Setup
```bash
git clone https://github.com/YOUR_USERNAME/hms.git
```
1. Copy the `hms` folder to `C:\xampp\htdocs\hms\`

### Step 2: Database Setup
1. Start **Apache** & **MySQL** in XAMPP Control Panel
2. Open: `http://localhost/phpmyadmin`
3. Create database: **`hms_db`**
4. Click **Import** → Select `hms_schema.sql` → Click **Go**

### Step 3: Configure App
```bash
# Copy the example config
cp config/db.example.php config/db.php
```
Edit `config/db.php` with your details:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Your MySQL password
define('DB_NAME', 'hms_db');
define('APP_NAME', 'YOUR HOSPITAL NAME');
```

### Step 4: Run
Open browser: **`http://localhost/hms`**

---

## 🔐 Default Login Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `password` |
| Doctor | `doctor` | `password` |
| Reception | `reception` | `password` |

> ⚠️ **IMPORTANT**: Change these passwords immediately after first login!

---

## 📂 Project Structure

```
hms/
├── config/
│   ├── db.example.php    # Copy this to db.php
│   └── db.php            # Your local config (gitignored)
├── includes/
│   ├── header.php        # Common header + sidebar
│   ├── footer.php        # Scripts footer
│   ├── sidebar.php       # Navigation sidebar
│   └── auth_check.php    # Login protection
├── assets/
│   ├── css/              # Bootstrap + FontAwesome (offline)
│   ├── js/               # jQuery + Bootstrap JS (offline)
│   └── logo.png          # Hospital logo
├── uploads/              # Patient photos (gitignored)
├── hms_schema.sql        # Full database schema + demo data
├── index.php             # Dashboard
├── patients.php          # Patient management
├── opd.php               # OPD registration
├── ipd.php               # IPD admissions
├── billing.php           # Billing module
├── laboratory.php        # Lab management
├── pharmacy.php          # Pharmacy stock
├── reports.php           # Reports
├── settings.php          # Hospital settings
└── users.php             # User management
```

---

## 🎨 Customization

Edit `config/db.php` to change:
```php
define('APP_NAME', 'YOUR HOSPITAL NAME');
define('APP_ADDRESS', 'Your Full Address');
define('APP_PHONE', '9999999999');
define('APP_EMAIL', 'email@hospital.com');
define('PRIMARY_COLOR', '#0066CC');  // Theme color
```

---

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+ (PDO)
- **Database**: MySQL 5.7+ / MariaDB
- **Frontend**: Bootstrap 5.3, jQuery, FontAwesome 6
- **Offline Ready**: All assets bundled locally

---

## 📋 Requirements

- XAMPP / WAMP / LAMP
- PHP 7.4 or higher
- MySQL 5.7+ / MariaDB 10+
- Web Browser (Chrome/Firefox recommended)

---

## 📄 License

This project is for private hospital use. All rights reserved.

---

**Developed for Sankhla Hospital | Jhotwara, Jaipur** 🏥
