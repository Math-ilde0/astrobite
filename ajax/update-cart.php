<?php
/**
 * /ajax/update-cart.php
 * 
 * Updates the quantity of an item in the cart
 * 
 * Expected POST:
 *   - product_id: int
 *   - quantity: int
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
  $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

  if ($product_id <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
  }

  if (!isset($_SESSION['cart'][$product_id])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Item not in cart']);
    exit;
  }

  $_SESSION['cart'][$product_id]['quantity'] = $quantity;

  echo json_encode([
    'success' => true,
    'message' => 'Cart updated'
  ]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  exit;
}
?>
