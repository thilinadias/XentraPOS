CREATE DATABASE IF NOT EXISTS pos_db;
USE pos_db;

-- 1. USERS & AUTH
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin', 'agent', 'auditor', 'viewer') NOT NULL DEFAULT 'viewer',
  `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. ACTIVITY AUDITING
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 3. CATEGORIES & PRODUCTS
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NULL,
  `name` VARCHAR(150) NOT NULL,
  `barcode` VARCHAR(100) UNIQUE NULL,
  `cost_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `price` DECIMAL(10,2) NOT NULL,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `image_path` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
);

-- 4. SALES & TRANSACTIONS
CREATE TABLE IF NOT EXISTS `sales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(10,2) NOT NULL,
  `payment_type` ENUM('Cash', 'Card', 'Credit') NOT NULL,
  `amount_tendered` DECIMAL(10,2) NOT NULL,
  `change_due` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sale_id` INT NOT NULL,
  `product_id` INT NULL,
  `custom_name` VARCHAR(255) NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `item_discount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
);

-- 5. SYSTEM SETTINGS
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NULL
);

-- INITIAL DATA
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES 
('company_name', 'XentraPOS Enterprise'),
('company_address', '123 Business Avenue, City, State 12345'),
('company_email', 'contact@company.com'),
('company_phone', '(123) 456-7890'),
('company_logo', 'assets/img/logo.png');

-- Default Super Admin (password: admin123)
-- Delete or edit this after first login
INSERT IGNORE INTO `users` (`username`, `password`, `role`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
