-- ===========================================
-- MIGRATION: 2.1.4 (Persistent Configuration Restoration)
-- ===========================================

-- 1. Ensure the Settings table exists (Non-destructive)
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Seed Default Application Values (Only if missing)
-- Company Identity
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('company_name', 'XentraPOS');
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('company_address', 'Princes Gate, Colombo, Sri Lanka');
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('company_email', 'admin@xentra.pos');
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('company_phone', '+94 77 123 4567');
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('currency_symbol', 'Rs.');
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('low_stock_threshold', '10');

-- Email Automation Defaults
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('email_alerts_enabled', '0');
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('email_daily_summary_enabled', '0');
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('smtp_host', 'smtp.gmail.com');
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('smtp_port', '587');
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('smtp_encryption', 'tls');

-- 3. Mark as executed
INSERT IGNORE INTO `migrations` (`filename`) VALUES ('v2.1.4_persistent_config.sql');
