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
- **Architecture**: PHP 8.1 / PDO / RESTful API
- **Frontend**: Bootstrap 5 / Vanilla JS (ES6)
- **Email Core**: PHPMailer (SMTP over TLS/SSL)
- **Automation**: Dual-mode (Passive PHP Trigger + Optional Windows Task Scheduler)

---

## 🔒 Security & Privacy
- **Credentials Guard**: Local configuration and database passwords are never stored in the repository.
- **Personal Branding**: Your private business logos and settings remain local to your computer.

---

## ⚖️ License
Personal / Commercial use with local licensing. All rights reserved.
