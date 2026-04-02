-- C:\xampp\htdocs\pos\database_module_4_settings.sql
USE pos_db;

CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NULL
);

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES 
('company_name', 'COMPANY NAME'),
('company_address', '123 Business Avenue, City, State 12345'),
('company_email', 'contact@company.com'),
('company_phone', '(123) 456-7890'),
('company_logo', '');

-- Modify sale_items to support custom names (null product_id)
-- First drop the foreign key constraint safely 
SET @fk_name = (
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'pos_db' 
      AND TABLE_NAME = 'sale_items' 
      AND COLUMN_NAME = 'product_id' 
      AND REFERENCED_TABLE_NAME = 'products' 
    LIMIT 1
);

SET @s = CONCAT('ALTER TABLE sale_items DROP FOREIGN KEY ', @fk_name);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Now modify column
ALTER TABLE `sale_items` MODIFY `product_id` INT NULL;
ALTER TABLE `sale_items` ADD COLUMN `custom_name` VARCHAR(255) NULL AFTER `product_id`;

-- Add it back with ON DELETE SET NULL
ALTER TABLE `sale_items` ADD CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL;
