# XentraPOS: Enterprise-Grade Point of Sale & Strategic Business Intelligence Suite

XentraPOS is a high-performance, professional-grade business management architecture designed to bridge the gap between traditional retail operations and modern data-driven intelligence. It provides business owners with a mission-critical dashboard featuring automated dispatch, refund-aware profit analytics, and a secure, synchronized core for seamless feature updates.

---

## 💎 Strategic Core Features v1.6.0 Stable

### 📊 Professional Performance Analytics
- **Refund-Aware Profit Engine**: A high-integrity financial module that calculates true net revenue by intelligently deducting refunds, reversals, and operational adjustments.
- **Executive Performance Digest**: Automated monthly and daily performance snapshots featuring **Star Cashier** tracking and **Category Excellence** metrics.
- **Full Transparency Logs**: Comprehensive activity logging and refund context capture for professional auditing and loss prevention.

### 🔄 XentraUpdate Hub (Self-Healing Core)
- **GitHub Synchronized Deployment**: A manual, one-click update infrastructure that keeps your local system aligned with the latest stable repository features via a secure GUI.
- **Safe-Merge Asset Protection**: Robust file management that ensures your private `database.php` credentials and local branding assets are **never** compromised during synchronization.
- **Sequential SQL Migration Engine**: An intelligent database versioning system that detects and applies schema improvements sequentially, ensuring long-term database stability.
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

## 🔑 First-Time Login (Default)
After importing the `database.sql` file, you can access the system using the following temporary credentials:

- **Username**: `admin`
- **Password**: `admin123`

> [!WARNING]
> **Security Requirement**: For your protection, you MUST change this default password in the **User Management** section immediately after your first successful login.

---

## 🔒 Security & Privacy
- **Credentials Guard**: Local configuration and database passwords are never stored in the repository.
- **Personal Branding**: Your private business logos and settings remain local to your computer.

---

## ⚖️ License
Personal / Commercial use with local licensing. All rights reserved.
