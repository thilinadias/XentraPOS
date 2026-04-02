-- ===========================================
-- MIGRATION: 2.0.0 (Enterprise CRM & Profit Tracking)
-- ===========================================

-- 1. Ensure Migrations table exists
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL UNIQUE,
    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 1b. Ensure Activity Log table exists
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 2. Add missing columns to Products
ALTER TABLE products 
  ADD COLUMN IF NOT EXISTS cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER barcode, 
  ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER image_path;

-- 3. Create the Customers table
CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(100) NULL,
  address TEXT NULL,
  balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Create the Customer Payments table
CREATE TABLE IF NOT EXISTS `customer_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('Cash', 'Card', 'Credit') NOT NULL DEFAULT 'Cash',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

-- 5. Upgrade Sales Table
ALTER TABLE sales 
  ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(20) UNIQUE NULL AFTER id,
  ADD COLUMN IF NOT EXISTS customer_id INT NULL AFTER user_id,
  ADD COLUMN IF NOT EXISTS customer_name VARCHAR(100) NULL AFTER customer_id,
  ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(20) NULL AFTER customer_name,
  ADD COLUMN IF NOT EXISTS status ENUM('Completed', 'Refunded', 'Cancelled') NOT NULL DEFAULT 'Completed',
  ADD COLUMN IF NOT EXISTS refund_reason TEXT NULL,
  ADD COLUMN IF NOT EXISTS refund_notes TEXT NULL,
  ADD CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;

-- 6. Upgrade Sale Items (for Profit Tracking)
ALTER TABLE sale_items 
  ADD COLUMN IF NOT EXISTS buy_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER unit_price;

-- 8. Data Correction: Mark all legacy sales as 'Completed'
UPDATE sales SET status = 'Completed' WHERE status IS NULL OR status = 'Paid' OR status = '';

-- 9. Data Correction: Historical Profit Backfill
-- If pre-enterprise items had 0 cost, use current cost price for profit reporting
UPDATE sale_items si 
JOIN products p ON si.product_id = p.id 
SET si.buy_price = p.cost_price 
WHERE si.buy_price = 0.00;

-- 10. Register this migration manually
INSERT IGNORE INTO `migrations` (`filename`) VALUES ('v2.0.0_enterprise.sql');
