-- AstroBite Database - Full schema and seed data
-- 1. SCHEMA

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

-- Helper index for faster lookups on category trees
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
-- 4) products
-- ---------------------------------
CREATE TABLE IF NOT EXISTS products (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL COMMENT 'Used for search',
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL COMMENT 'Unit price',
  `category_id` INT NOT NULL,
  `image1` VARCHAR(255) NULL COMMENT 'Main product image',
  `image2` VARCHAR(255) NULL COMMENT 'Secondary image for carousel/hover',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`),
  CONSTRAINT `chk_products_price_nonneg` CHECK (`price` >= 0)
) ENGINE=InnoDB COMMENT='Product catalogue';
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
);
-- helper index 
CREATE INDEX idx_oi_product ON order_items(product_id);


-- 2. SEED DATA
START TRANSACTION;

-- 1) Categories
INSERT INTO categories (category_id, name)
VALUES
(1, 'Main Dish'),
(2, 'Snacks'),
(3, 'Breakfast'),
(4, 'Dessert')
ON DUPLICATE KEY UPDATE
  name = VALUES(name);

-- 2) Stores
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

-- 3) Products
INSERT INTO products (product_id, name, description, price, category_id, image1, image2)
VALUES
(1, 'Freeze-Dried Spaghetti Bolognese', 'Classic Italian pasta made for outer space explorers.', 12.99, 1, 'assets/images/products/spaghetti1.png', 'assets/images/products/spaghetti2.png'),
(2, 'Space Strawberries', 'Sweet freeze-dried strawberries, perfect for snacking in zero gravity.', 5.50, 2, 'assets/images/products/strawberries1.png', 'assets/images/products/strawberries2.png'),
(3, 'Galactic Granola', 'A nutritious mix of oats and freeze-dried fruits.', 8.20, 3, 'assets/images/products/granola1.png', 'assets/images/products/granola2.png'),
(4, 'Mars Mac & Cheese', 'Creamy mac and cheese, astronaut-approved.', 10.75, 1, 'assets/images/products/macncheese1.png', 'assets/images/products/macncheese2.png'),
(5, 'Chocolate Meteorite Cake', 'Freeze-dried chocolate dessert with cosmic crunch.', 6.90, 4, 'assets/images/products/cake1.png', 'assets/images/products/cake2.png'),
(6, 'Lunar Lentil Curry', 'A spicy and hearty plant-based dish crafted for cosmic adventures.', 11.45, 1, 'assets/images/products/lentilcurry1.png', 'assets/images/products/lentilcurry2.png'),
(7, 'Astro Apple Chips', 'Crispy freeze-dried apple slices for on-the-go orbit snacking.', 4.95, 2, 'assets/images/products/applechips1.png', 'assets/images/products/applechips2.png'),
(8, 'Cosmic Cinnamon Pancakes', 'Fluffy pancakes with a touch of cinnamon, ready in light-speed.', 7.80, 3, 'assets/images/products/pancakes1.png', 'assets/images/products/pancakes2.png')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  price = VALUES(price),
  category_id = VALUES(category_id),
  image1 = VALUES(image1),
  image2 = VALUES(image2);

-- 4) Inventory
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

-- 5) Users
INSERT INTO users (user_id, name, email, password, role, provider)
VALUES
(1, 'Admin User', 'admin@astrobite.com',
 '$2y$12$aOgeFkFKRobD38NHkWHxG.pA5MpYF1xFtJ2SbaUVzY5Rq2b/qY33i', 'admin', 'password'),
(2, 'Test User', 'user@astrobite.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'password')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  role = VALUES(role),
  provider = VALUES(provider);

-- 6) Orders
INSERT INTO orders (order_id, user_id, store_id, total_price, status)
VALUES
(1001, 2, 1, 18.49, 'completed'),
(1002, 2, 2, 18.95, 'pending')
ON DUPLICATE KEY UPDATE
  user_id = VALUES(user_id),
  store_id = VALUES(store_id),
  total_price = VALUES(total_price),
  status = VALUES(status);

-- 7) Order Items
INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
VALUES
(1001, 1, 1, 12.99),
(1001, 2, 1, 5.50),
(1002, 3, 1, 8.20),
(1002, 4, 1, 10.75)
ON DUPLICATE KEY UPDATE
  quantity = VALUES(quantity),
  price_at_purchase = VALUES(price_at_purchase);

ALTER TABLE orders AUTO_INCREMENT = 1003;

COMMIT;
