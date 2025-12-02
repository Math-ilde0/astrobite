<?php
/**
 * /ajax/remove-cart-item.php
 * 
 * Removes an item from the cart
 * 
 * Expected POST:
 *   - product_id: int
 * 
 * Returns JSON:
 *   - success: bool
 *   - message: string
 */

session_start();

header('Content-Type: application/json; charset=utf-8');

try {
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
  }

  $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

  if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
  }

  if (isset($_SESSION['cart'][$product_id])) {
    unset($_SESSION['cart'][$product_id]);
  }

  echo json_encode([
    'success' => true,
    'message' => 'Item removed from cart'
  ]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  exit;
}
?>
