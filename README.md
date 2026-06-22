# Offline Hospital Management System (HMS)

## Overview
A complete, lightweight, and offline-ready Hospital Management System built with Plain PHP and MySQL. Designed for easy installation on XAMPP/WAMP.

## Features
- **Dashboard**: Real-time hospital stats.
- **Patient Registration**: EMR usage, Photo upload.
- **OPD & Doctor Consultation**: Digital prescriptions.
- **Billing**: GST-ready invoices with print support.
- **IPD**: Admissions, Discharges.
- **Pharmacy & Lab**: Stock and Test result management.
- **Role-Based Access**: Admin, Doctor, Receptionist, etc.

---

## 🚀 Installation Steps

### 1. Install XAMPP
Download and install [XAMPP](https://www.apachefriends.org/) if you haven't already.

### 2. Setup Project Files
1. Go to your XAMPP installation directory (usually `C:\xampp\htdocs`).
2. Create a folder named `hms`.
3. Copy all the project files into `C:\xampp\htdocs\hms\`.
   - Ensure the structure is:
     ```
     hms/
     ├── assets/ (css, js, images)
     ├── config/
     ├── includes/
     ├── uploads/
     ├── index.php
     ├── login.php
     ... and other php files
     ```

### 3. Setup Database
1. Start **Apache** and **MySQL** from XAMPP Control Panel.
2. Open your browser and go to `http://localhost/phpmyadmin`.
3. Create a new database named `hms_db`.
4. Click **Import** tab.
5. Choose the file `hms_schema.sql` (provided in the project root) and click **Go**.

### 4. Configure Offline Assets (Crucial)
Since this system must run without internet, you need to download the following libraries and place them in the `assets` folder:

1. **Bootstrap 5.3 CSS**:
   - Download `bootstrap.min.css`
   - Place in `assets/css/bootstrap.min.css`
2. **FontAwesome (WebFree)**:
   - Download the folder, rename `css/all.min.css` to `assets/css/all.min.css`.
3. **jQuery**:
   - Download `jquery.min.js`
   - Place in `assets/js/jquery.min.js`
4. **Bootstrap Bundle JS**:
   - Download `bootstrap.bundle.min.js`
   - Place in `assets/js/bootstrap.bundle.min.js`
5. **Hospital Logo**:
   - Place your logo image at `assets/logo.png`.

*If you skip this, the design will look broken offline.*

### 5. Run the Application
1. Open browser: `http://localhost/hms`
2. **Login Credentials**:
   - **Admin**: `admin` / `password`
   - **Doctor**: `doctor` / `password`
   - **Reception**: `reception` / `password`

---

## 🎨 Customization
To change Hospital Name, Address, or Colors:
1. Open `config/db.php`.
2. Edit the constants at the bottom:
   ```php
   define('APP_NAME', 'YOUR HOSPITAL NAME');
   define('APP_ADDRESS', 'Your Address...');
   define('PRIMARY_COLOR', '#0066CC'); // Change Theme Color
   ```

## 📂 Folder Structure
- `config/` - Database connection.
- `includes/` - Header, Footer, Sidebar, Auth checks.
- `uploads/` - Patient photos.
- `assets/` - CSS/JS files.

---

**Developed for Offline Use | Plain PHP**
