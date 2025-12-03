<?php
/**
 * /ajax/random-products.php - Fetch Random Products
 * 
 * AJAX endpoint that returns 2 random products from the database for displaying
 * as featured/recommended items on the home page. Performs no authentication
 * checks (public endpoint). Queries products table using ORDER BY RAND() for
 * random selection. Returns JSON array of product objects with full details.
 * 
 * REQUEST: No parameters required (GET/POST)
 * 
 * RESPONSE (JSON Array):
 *   - product_id: int (primary key)
 *   - name: string (product name)
 *   - description: string (product description)
 *   - image1: string (primary product image filename)
 *   - image2: string (secondary product image filename)
 *   - price: decimal (product price)
 * 
 * EXAMPLE RESPONSE:
 *   [
 *     {
 *       "product_id": 3,
 *       "name": "Pancakes",
 *       "description": "Fluffy cosmic pancakes",
 *       "image1": "pancakes1.png",
 *       "image2": "pancakes2.png",
 *       "price": "8.99"
 *     },
 *     {...}
 *   ]
 */

// 1. Include database connection with PDO configuration
require_once '../includes/db.php';

// 2. Set JSON response header
header('Content-Type: application/json');

// 3. QUERY - Select 2 random products with full details using ORDER BY RAND()
$stmt = $pdo->query("
    SELECT product_id, name, description, image1, image2, price
    FROM products
    ORDER BY RAND()
    LIMIT 2
");

// 4. RETURN - Encode results as JSON array and output
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
