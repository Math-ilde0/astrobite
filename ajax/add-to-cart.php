<?php
/**
 * /ajax/add-to-cart.php
 * 
 * Handles adding a product to the user's cart (session-based)
 * 
 * Expected POST:
 *   - product_id: int
 *   - quantity: int
 * 
 * Returns JSON:
 *   - success: bool
 *   - message: string
 *   - redirect_url: string (if user not logged in)
 *   - cart_count: int (total items in cart)
 */

session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
  // Check if user is logged in
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
      'success' => false,
      'message' => 'Please log in to add items to your cart.',
      'redirect_url' => 'login.php'
    ]);
    exit;
  }

  // Validate input
  $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
  $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

  if ($product_id <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode([
      'success' => false,
      'message' => 'Invalid product ID or quantity.'
    ]);
    exit;
  }

  // Verify product exists
  $stmt = $pdo->prepare("SELECT product_id, name, price FROM products WHERE product_id = ? LIMIT 1");
  $stmt->execute([$product_id]);
  $product = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$product) {
    http_response_code(404);
    echo json_encode([
      'success' => false,
      'message' => 'Product not found.'
    ]);
    exit;
  }

  // Initialize cart in session if it doesn't exist
  if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
  }

  // Add or update item in cart
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

  // Calculate total cart count
  $cart_count = 0;
  foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
  }

  echo json_encode([
    'success' => true,
    'message' => htmlspecialchars($product['name']) . ' added to cart!',
    'cart_count' => $cart_count
  ]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'An error occurred: ' . $e->getMessage()
  ]);
  exit;
}
?>
