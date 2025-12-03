<?php
/**
 * /ajax/add-to-cart.php - Add Product to Shopping Cart
 * 
 * AJAX endpoint for adding items to the user's session-based shopping cart.
 * Requires authentication (user_id in session). Validates product_id and
 * quantity, verifies product exists in database, then adds/updates cart item
 * in $_SESSION['cart']. Increments quantity if product already in cart.
 * Returns JSON with success status, message, and updated cart item count.
 * 
 * REQUEST (POST):
 *   - product_id: int (must be > 0, must exist in database)
 *   - quantity: int (optional, defaults to 1, must be > 0)
 * 
 * RESPONSE (JSON):
 *   - success: bool (true/false)
 *   - message: string (status description or product name confirmation)
 *   - cart_count: int (total items across all products in cart)
 *   - redirect_url: string (only if not logged in, points to login.php)
 * 
 * HTTP STATUS CODES:
 *   - 200 OK: Product successfully added to cart
 *   - 400 Bad Request: Invalid product_id or quantity
 *   - 401 Unauthorized: User not logged in
 *   - 404 Not Found: Product doesn't exist in database
 *   - 500 Internal Server Error: Exception caught
 */

// 1. Initialize session and include database connection
session_start();
require_once __DIR__ . '/../includes/db.php';

// 2. Set JSON response header with UTF-8 encoding
header('Content-Type: application/json; charset=utf-8');

try {
  // 3. AUTHENTICATION - Verify user is logged in via session
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
      'success' => false,
      'message' => 'Please log in to add items to your cart.',
      'redirect_url' => 'login.php'
    ]);
    exit;
  }

  // 4. INPUT VALIDATION - Parse and sanitize product_id and quantity
  $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
  $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

  // 5. VERIFY INPUT - Ensure both are positive integers
  if ($product_id <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode([
      'success' => false,
      'message' => 'Invalid product ID or quantity.'
    ]);
    exit;
  }

  // 6. DATABASE LOOKUP - Query product by ID to get name and price
  $stmt = $pdo->prepare("SELECT product_id, name, price FROM products WHERE product_id = ? LIMIT 1");
  $stmt->execute([$product_id]);
  $product = $stmt->fetch(PDO::FETCH_ASSOC);

  // 7. VERIFY PRODUCT EXISTS - Return 404 if product not found in database
  if (!$product) {
    http_response_code(404);
    echo json_encode([
      'success' => false,
      'message' => 'Product not found.'
    ]);
    exit;
  }

  // 8. INITIALIZE CART - Create empty cart array in session if not present
  if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
  }

  // 9. ADD/UPDATE ITEM - If product already in cart, increment quantity; otherwise add new
  if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
  } else {
    $_SESSION['cart'][$product_id] = [
      'product_id' => $product_id,
      'name' => $product['name'],
      'price' => (float)$product['price'],
      'quantity' => $quantity
    ];
  }

  // 10. CALCULATE TOTALS - Sum total item count across all cart products
  $cart_count = 0;
  foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
  }

  // 11. RETURN SUCCESS - JSON response with confirmation and updated cart count
  echo json_encode([
    'success' => true,
    'message' => htmlspecialchars($product['name']) . ' added to cart!',
    'cart_count' => $cart_count
  ]);
  exit;

} catch (Throwable $e) {
  // 12. ERROR HANDLING - Catch all exceptions and return 500 error
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'An error occurred: ' . $e->getMessage()
  ]);
  exit;
}
?>
