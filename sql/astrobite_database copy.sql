-- =================================================================
-- astrobite_database.sql - AstroBite e-commerce Database Schema
-- =================================================================
--
-- Purpose: Complete database schema for AstroBite freeze-dried food e-commerce platform
--
-- Contains:
-- - User authentication with OAuth support (Google, Facebook, password)
-- - Product catalogue with categories and SEO metadata
-- - Store/location management for click & collect points
-- - Inventory tracking per store
-- - Order and order-item records with pricing snapshots
-- - Static page SEO configuration
--
-- Features:
-- - Composite primary keys (inventory, order_items)
-- - Hierarchical categories (self-referencing parent_id)
-- - Price snapshots to protect against post-purchase price changes
-- - FULLTEXT search on product names and descriptions
-- - Automatic timestamps (created_at, updated_at)
-- - Referential integrity with CASCADE delete rules
-- - CHECK constraints for non-negative quantities and prices
-- - ON DUPLICATE KEY UPDATE for idempotent inserts (safe re-imports)
--
-- Compatibility: MySQL 5.7+ (InnoDB engine)
-- Default collation: utf8mb4_unicode_ci (UTF-8 support)
--
-- Seed Data:
-- - 4 categories (Main Dish, Snacks, Breakfast, Dessert)
-- - 2 stores (D1, D3 with addresses and Google Maps links)
-- - 8 products with images, SEO titles, descriptions, alt texts
-- - 16 inventory records (2 per product, one per store)
-- - 2 test users (admin@astrobite.com, user@astrobite.com)
-- - 2 sample orders with 4 order items total
-- - 3 static page SEO defaults
--
-- =================================================================
-- 1) SCHEMA
-- =================================================================

-- ---------------------------------
-- 1) users
-- ---------------------------------
CREATE TABLE IF NOT EXISTS users (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL COMMENT 'Full name of the user',
  `email` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Primary login email',
  `password` VARCHAR(255) NOT NULL COMMENT 'Hashed password',
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user' COMMENT 'Permission role',
  -- OAuth
  `provider` VARCHAR(20) NULL COMMENT 'e.g., google, facebook, password',
  `provider_id` VARCHAR(255) NULL COMMENT 'ID from OAuth provider',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_provider_providerid` (`provider`, `provider_id`)
) ENGINE=InnoDB COMMENT='User and authentication table';

-- ---------------------------------
-- 2) categories (self-referencing tree)
-- ---------------------------------
CREATE TABLE IF NOT EXISTS categories (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'e.g., Main Dish',
  `parent_id` INT NULL COMMENT 'Parent category',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_categories_parent`
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`category_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Product categories (hierarchy)';

CREATE INDEX idx_categories_parent ON categories(parent_id);

-- ---------------------------------
-- 3) stores (click & collect points)
-- ---------------------------------
CREATE TABLE IF NOT EXISTS stores (
  `store_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT 'e.g., AstroBite D1',
  `location_code` VARCHAR(10) NOT NULL UNIQUE COMMENT 'e.g., D1, D4',
  `address` VARCHAR(255) NOT NULL,
  `maps_link` VARCHAR(1024) NULL COMMENT 'Google Maps link',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Collection points';

-- ---------------------------------
-- 4) products + SEO fields
-- ---------------------------------
CREATE TABLE IF NOT EXISTS products (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL COMMENT 'Used for search',
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL COMMENT 'Unit price',
  `category_id` INT NOT NULL,
  `image1` VARCHAR(255) NULL COMMENT 'Main product image',
  `image2` VARCHAR(255) NULL COMMENT 'Secondary image for carousel/hover',

  -- SEO additions
  `meta_title` VARCHAR(70) NULL,
  `meta_description` VARCHAR(160) NULL,
  `image1_alt` VARCHAR(255) NULL,
  `image2_alt` VARCHAR(255) NULL,

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`),
  CONSTRAINT `chk_products_price_nonneg` CHECK (`price` >= 0)
) ENGINE=InnoDB COMMENT='Product catalogue';

CREATE INDEX idx_products_category ON products(category_id);

-- Version-safe FULLTEXT index (works whether it exists or not)
SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'products'
    AND INDEX_NAME   = 'ft_products_name_desc'
);

SET @sql := IF(@idx_exists > 0,
  'DO 0',
  'CREATE FULLTEXT INDEX ft_products_name_desc ON products (name, description)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------
-- 5) inventory (composite PK per ERD)
-- ---------------------------------
CREATE TABLE IF NOT EXISTS inventory (
  `product_id` INT NOT NULL,
  `store_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 0 COMMENT 'Stock at store',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`, `store_id`),
  CONSTRAINT `fk_inventory_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventory_store`
    FOREIGN KEY (`store_id`)  REFERENCES `stores`(`store_id`)  ON DELETE CASCADE,
  CONSTRAINT `chk_inventory_qty_nonneg` CHECK (`quantity` >= 0)
) ENGINE=InnoDB COMMENT='Stock by store';

CREATE INDEX idx_inventory_store ON inventory(store_id);

-- ---------------------------------
-- 6) orders
-- ---------------------------------
CREATE TABLE IF NOT EXISTS orders (
  `order_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL COMMENT 'Who placed the order',
  `store_id` INT NULL COMMENT 'NULL if delivery (not click&collect)',
  `total_price` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending','ready_for_pickup','completed','cancelled')
           NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  CONSTRAINT `fk_orders_store`
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`store_id`),
  CONSTRAINT `chk_orders_total_nonneg` CHECK (`total_price` >= 0)
) ENGINE=InnoDB COMMENT='Customer orders';

-- ---------------------------------
-- 7) order_items
-- ---------------------------------
CREATE TABLE IF NOT EXISTS order_items (
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL COMMENT 'Qty ordered',
  `price_at_purchase` DECIMAL(10,2) NOT NULL COMMENT 'Snapshot price',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_oi_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`),
  CONSTRAINT `chk_oi_qty_nonneg` CHECK (`quantity` > 0),
  CONSTRAINT `chk_oi_price_nonneg` CHECK (`price_at_purchase` >= 0),
  PRIMARY KEY (`order_id`, `product_id`)
) ENGINE=InnoDB COMMENT='Order lines';

CREATE INDEX idx_oi_product ON order_items(product_id);

-- ---------------------------------
-- 8) pages_seo (static pages SEO defaults)
-- ---------------------------------
CREATE TABLE IF NOT EXISTS pages_seo (
  page_slug VARCHAR(64) PRIMARY KEY,           -- e.g. 'home', 'products', 'contact'
  meta_title VARCHAR(70) NOT NULL,
  meta_description VARCHAR(160) NOT NULL,
  canonical_path VARCHAR(255) NULL,            -- e.g. '/mywebsite/astrobite/'
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Static pages SEO defaults';

-- =================================================================
-- 2) SEED DATA
-- =================================================================
START TRANSACTION;

-- Categories
INSERT INTO categories (category_id, name)
VALUES
(1, 'Main Dish'),
(2, 'Snacks'),
(3, 'Breakfast'),
(4, 'Dessert')
ON DUPLICATE KEY UPDATE
  name = VALUES(name);

-- Stores
INSERT INTO stores (store_id, name, location_code, address, maps_link)
VALUES
(1, 'AstroBite D1 - Saigon Center', 'D1',
 '2nd Floor, Saigon Center, District 1, Ho Chi Minh City',
 'https://www.google.com/maps/embed?pb=!1m18!...'),
(2, 'AstroBite D3 - Rail Station', 'D3',
 'Basement 1, Saigon Rail Station, District 3, Ho Chi Minh City',
 'https://www.google.com/maps/embed?pb=!1m18!...')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  location_code = VALUES(location_code),
  address = VALUES(address),
  maps_link = VALUES(maps_link);

-- Products (8 items) with SEO meta + alt texts
INSERT INTO products
(product_id, name, description, price, category_id, image1, image2,
 meta_title, meta_description, image1_alt, image2_alt)
VALUES
(1, 'Freeze-Dried Spaghetti Bolognese',
 'Classic Italian pasta made for outer space explorers.',
 12.99, 1,
 'assets/images/products/spaghetti1.png', 'assets/images/products/spaghetti2.png',
 'Freeze-Dried Spaghetti Bolognese | AstroBite',
 'Classic Italian freeze-dried pasta. Delicious, lightweight, and long shelf life — perfect for hiking, camping, and space-themed adventures.',
 'Freeze-Dried Spaghetti Bolognese pouch front',
 'Freeze-Dried Spaghetti Bolognese pouch back'),

(2, 'Space Strawberries',
 'Sweet freeze-dried strawberries, perfect for snacking in zero gravity.',
 5.50, 2,
 'assets/images/products/strawberries1.png', 'assets/images/products/strawberries2.png',
 'Space Strawberries | Freeze-Dried Snack | AstroBite',
 'Sweet freeze-dried strawberries for on-the-go energy. Light, tasty, and shelf-stable for any mission.',
 'Space Strawberries front pouch',
 'Space Strawberries alternate view'),

(3, 'Galactic Granola',
 'A nutritious mix of oats and freeze-dried fruits.',
 8.20, 3,
 'assets/images/products/granola1.png', 'assets/images/products/granola2.png',
 'Galactic Granola | Breakfast Fuel | AstroBite',
 'Nutritious granola with freeze-dried fruits. Lightweight, energizing breakfast for hikes, travel, and adventures.',
 'Galactic Granola front pouch',
 'Galactic Granola alternate view'),

(4, 'Mars Mac & Cheese',
 'Creamy mac and cheese, astronaut-approved.',
 10.75, 1,
 'assets/images/products/macncheese1.png', 'assets/images/products/macncheese2.png',
 'Mars Mac & Cheese | Freeze-Dried Meal | AstroBite',
 'Astronaut-approved creamy mac and cheese. Freeze-dried, tasty, and easy to prepare anywhere.',
 'Mars Mac & Cheese pouch front',
 'Mars Mac & Cheese pouch back'),

(5, 'Chocolate Meteorite Cake',
 'Freeze-dried chocolate dessert with cosmic crunch.',
 6.90, 4,
 'assets/images/products/cake1.png', 'assets/images/products/cake2.png',
 'Chocolate Meteorite Cake | Dessert | AstroBite',
 'Freeze-dried chocolate dessert with cosmic crunch. Light, delicious, and perfect for a sweet break.',
 'Chocolate Meteorite Cake front pouch',
 'Chocolate Meteorite Cake alternate view'),

(6, 'Lunar Lentil Curry',
 'A spicy and hearty plant-based dish crafted for cosmic adventures.',
 11.45, 1,
 'assets/images/products/lentilcurry1.png', 'assets/images/products/lentilcurry2.png',
 'Lunar Lentil Curry | Plant-Based Meal | AstroBite',
 'Spicy, hearty plant-based freeze-dried curry. Packed with flavor and nutrients for any expedition.',
 'Lunar Lentil Curry pouch front',
 'Lunar Lentil Curry pouch back'),

(7, 'Astro Apple Chips',
 'Crispy freeze-dried apple slices for on-the-go orbit snacking.',
 4.95, 2,
 'assets/images/products/applechips1.png', 'assets/images/products/applechips2.png',
 'Astro Apple Chips | Freeze-Dried Apple Snack',
 'Crispy freeze-dried apple slices. Light, tasty, and long shelf life — perfect for hikes and travel.',
 'Astro Apple Chips front pouch',
 'Astro Apple Chips back pouch'),

(8, 'Cosmic Cinnamon Pancakes',
 'Fluffy pancakes with a touch of cinnamon, ready in light-speed.',
 7.80, 3,
 'assets/images/products/pancakes1.png', 'assets/images/products/pancakes2.png',
 'Cosmic Cinnamon Pancakes | Breakfast | AstroBite',
 'Fluffy freeze-dried pancakes with cinnamon. Fast prep, delicious taste — ideal for mornings on the move.',
 'Cosmic Cinnamon Pancakes front pouch',
 'Cosmic Cinnamon Pancakes alternate view')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  price = VALUES(price),
  category_id = VALUES(category_id),
  image1 = VALUES(image1),
  image2 = VALUES(image2),
  meta_title = VALUES(meta_title),
  meta_description = VALUES(meta_description),
  image1_alt = VALUES(image1_alt),
  image2_alt = VALUES(image2_alt);

-- Inventory
INSERT INTO inventory (product_id, store_id, quantity) VALUES
(1,1,10),(1,2,5),
(2,1,8),(2,2,12),
(3,1,6),(3,2,7),
(4,1,9),(4,2,4),
(5,1,5),(5,2,5),
(6,1,7),(6,2,6),
(7,1,10),(7,2,8),
(8,1,5),(8,2,7)
ON DUPLICATE KEY UPDATE
  quantity = VALUES(quantity);

-- Users
INSERT INTO users (user_id, name, email, password, role, provider)
VALUES
(1, 'Admin User', 'admin@astrobite.com',
 '$2y$12$Yo44LbSI0tyL0d/Q20ULheJGSUOvPo8SeE6K5U.duHIISxW3mKIP2', 'admin', 'password'),
(2, 'Test User', 'user@astrobite.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'password')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  role = VALUES(role),
  provider = VALUES(provider);

-- Orders
INSERT INTO orders (order_id, user_id, store_id, total_price, status)
VALUES
(1001, 2, 1, 18.49, 'completed'),
(1002, 2, 2, 18.95, 'pending')
ON DUPLICATE KEY UPDATE
  user_id = VALUES(user_id),
  store_id = VALUES(store_id),
  total_price = VALUES(total_price),
  status = VALUES(status);

-- Order Items
INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
VALUES
(1001, 1, 1, 12.99),
(1001, 2, 1, 5.50),
(1002, 3, 1, 8.20),
(1002, 4, 1, 10.75)
ON DUPLICATE KEY UPDATE
  quantity = VALUES(quantity),
  price_at_purchase = VALUES(price_at_purchase);

-- Static pages SEO defaults
INSERT INTO pages_seo (page_slug, meta_title, meta_description, canonical_path)
VALUES
('home',
 'AstroBite | Freeze-Dried Space Meals & Snacks',
 'Astronaut-inspired freeze-dried meals and snacks. Lightweight, long shelf life, and delicious — ideal for hiking, camping, and adventures.',
 '/mywebsite/astrobite/'),
('products',
 'Shop Freeze-Dried Meals & Snacks | AstroBite',
 'Explore astronaut-inspired freeze-dried meals and snacks. Tasty, lightweight, and built for any mission.',
 '/mywebsite/astrobite/products.php'),
('contact',
 'Contact AstroBite | Customer Support',
 'Questions about our freeze-dried products? Get in touch with AstroBite support.',
 '/mywebsite/astrobite/contact.php')
ON DUPLICATE KEY UPDATE
  meta_title = VALUES(meta_title),
  meta_description = VALUES(meta_description),
  canonical_path = VALUES(canonical_path);

ALTER TABLE orders AUTO_INCREMENT = 1003;

COMMIT;
