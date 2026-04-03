-- ===========================================
-- MIGRATION: 2.1.9 (Enterprise Supplier & Purchase Schema)
-- ===========================================

-- 1. Create the Suppliers table if it doesn't exist
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create the Purchase History table if it doesn't exist 
-- Note: Named 'purchase_history' in the API code
CREATE TABLE IF NOT EXISTS `purchase_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `supplier_id` INT(11) DEFAULT NULL,
  `quantity` INT(11) NOT NULL,
  `unit_cost` DECIMAL(10,2) NOT NULL,
  `total_cost` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_purchase_product` (`product_id`),
  KEY `fk_purchase_supplier` (`supplier_id`),
  CONSTRAINT `fk_purchase_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Mark this migration as applied
INSERT IGNORE INTO `migrations` (`filename`) VALUES ('v2.1.9_supplier_schema.sql');
