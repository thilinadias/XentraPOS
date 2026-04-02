# XentraPOS: Professional Point of Sale & Business Intelligence Suite

XentraPOS is a high-performance, professional business management system designed for small to medium businesses. It features an integrated reporting engine with refund-aware profit calculation and a built-in automated summary dispatch.

---

## 🚀 Key Features v1.6.0 Stable

### 🏆 Professional Reporting & Auditing
- **Net Profit Engine**: Automatically calculates revenue by deducting refunds and reversals.
- **Star Cashier Tracker**: Identifies top-performing category and cashier of the month.
- **Refund Context**: Captures reasons and notes for every reversal with full activity logging.

### 🤖 XentraUpdate Core (Self-Updater)
- **One-Click Synchronization**: Update your local system directly from the GitHub repository via the GUI.
- **Sequential SQL Migrations**: Automatically detects and applies database schema changes from the `/updates/` folder.
- **Safe-Merge Technology**: Protects your local `config/database.php` and Company logos during any update.

### 📧 Automated Business Summaries
- **Passive Morning Heartbeat**: XentraPOS automatically sends Missing Daily Summaries on the first login of the day.
- **Executive Monthly Digest**: Dispatches a full monthly performance report on the 1st of every month.

---

## 🛠️ Technical Stack
- **Architecture**: PHP 8.1+ / PDO / RESTful API
- **Frontend**: Bootstrap 5 / Vanilla JS (ES6)
- **Email Core**: PHPMailer (SMTP over TLS/SSL)
- **Automation**: Dual-mode (Passive PHP Trigger + Optional Windows Task Scheduler)

---

## 💻 Installation Guide

### 🪟 Windows Setup (XAMPP / WAMP)
1. **Prerequisites**: Install [XAMPP](https://www.apachefriends.org/) with PHP 8.1 or higher.
2. **File Placement**: Download this repository and extract it into `C:\xampp\htdocs\pos`.
3. **PHP Extensions**: Open `php.ini` in XAMPP and ensure the following are enabled:
   - `extension=zip` (Required for XentraUpdate)
   - `extension=openssl` (Required for Email Alerts)
   - `extension=pdo_mysql`
4. **Database Setup**:
   - Open `phpMyAdmin` (http://localhost/phpmyadmin).
   - Create a new database named `pos_db`.
   - Import the `database.sql` file provided in this repository.
5. **Configuration**: Rename `config/database.example.php` to `config/database.php` and enter your credentials.
6. **Access**: Navigate to `http://localhost/pos/login.php`.

### 🐧 Linux Setup (Ubuntu / LAMP)
1. **Install Dependencies**:
   ```bash
   sudo apt update
   sudo apt install apache2 mariadb-server php php-mysql php-zip php-curl libapache2-mod-php
   ```
2. **File Placement**:
   ```bash
   cd /var/www/html
   sudo git clone https://github.com/thilinadias/XentraPOS.git pos
   ```
3. **Permissions (Critical)**: For XentraUpdate to work, Apache must own the files:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/pos
   sudo chmod -R 755 /var/www/html/pos
   ```
4. **Database Setup**:
   ```bash
   sudo mysql -u root -p
   CREATE DATABASE pos_db;
   USE pos_db;
   SOURCE /var/www/html/pos/database.sql;
   ```
5. **Virtual Host**: Ensure your Apache config allows `.htaccess` overrides if you use pretty URLs (optional).

---

## 🔒 Security & Privacy
- **Credentials Guard**: Local configuration and database passwords are never stored in the repository.
- **Personal Branding**: Your private business logos and settings remain local to your computer.

---

## ⚖️ License
Personal / Commercial use with local licensing. All rights reserved.
