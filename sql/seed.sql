INSERT INTO products (name, description, price, category, image, image2)
VALUES
('Freeze-Dried Spaghetti Bolognese', 'Classic Italian pasta made for outer space explorers.', 12.99, 'Main Dish', 'assets/images/products/spaghetti1.png', 'assets/images/products/spaghetti2.png'),
('Space Strawberries', 'Sweet freeze-dried strawberries, perfect for snacking in zero gravity.', 5.50, 'Snacks', 'assets/images/products/strawberries1.png', 'assets/images/products/strawberries2.png'),
('Galactic Granola', 'A nutritious mix of oats and freeze-dried fruits.', 8.20, 'Breakfast', 'assets/images/products/granola1.png', 'assets/images/products/granola2.png'),
('Mars Mac & Cheese', 'Creamy mac and cheese, astronaut-approved.', 10.75, 'Main Dish', 'assets/images/products/macncheese1.png', 'assets/images/products/macncheese2.png'),
('Chocolate Meteorite Cake', 'Freeze-dried chocolate dessert with cosmic crunch.', 6.90, 'Dessert', 'assets/images/products/cake1.png', 'assets/images/products/cake2.png');

INSERT INTO stock (product_id, location, quantity) VALUES
-- Spaghetti
(1, 'D1', 10),
(1, 'D4', 5),

-- Strawberries
(2, 'D1', 8),
(2, 'D4', 12),

-- Granola
(3, 'D1', 6),
(3, 'D4', 7),

-- Mac & Cheese
(4, 'D1', 9),
(4, 'D4', 4),

-- Cake
(5, 'D1', 5),
(5, 'D4', 5);
