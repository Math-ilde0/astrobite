<?php
/**
 * /ajax/remove-cart-item.php - Remove Item from Cart
 * 
 * AJAX endpoint for removing a product from the user's shopping cart by
 * unsetting the item from $_SESSION['cart']. Requires authentication and
 * validates product_id as a positive integer. Silently succeeds if item
 * is not in cart (idempotent behavior). Returns JSON response with status.
 * 
 * REQUEST (POST):
 *   - product_id: int (must be > 0)
 * 
 * RESPONSE (JSON):
 *   - success: bool (true/false)
 *   - message: string (status description)
 * 
 * HTTP STATUS CODES:
 *   - 200 OK: Item removed from cart (or was not present)
 *   - 400 Bad Request: Invalid product_id (<=0)
 *   - 401 Unauthorized: User not logged in
 *   - 500 Internal Server Error: Exception caught
 */

// 1. Initialize session and set JSON response header
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
  // 2. AUTHENTICATION - Check if user is logged in
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
  }

  // 3. INPUT VALIDATION - Parse and sanitize product_id
  $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

  // 4. VERIFY INPUT - Ensure product_id is a positive integer
  if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
  }

  // 5. REMOVE ITEM - Unset product from cart if it exists (idempotent)
  if (isset($_SESSION['cart'][$product_id])) {
    unset($_SESSION['cart'][$product_id]);
  }

  // 6. RETURN SUCCESS - JSON response confirming removal
  echo json_encode([
    'success' => true,
    'message' => 'Item removed from cart'
  ]);
  exit;

} catch (Throwable $e) {
  // 7. ERROR HANDLING - Catch all exceptions and return 500 error
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  exit;
}
?>
