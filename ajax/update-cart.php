<?php
/**
 * /ajax/update-cart.php - Update Cart Item Quantity
 * 
 * AJAX endpoint for updating the quantity of an existing item in the user's
 * shopping cart. Modifies $_SESSION['cart'][product_id]['quantity'] with the
 * new quantity value. Requires authentication and validates both product_id
 * and quantity as positive integers. Returns JSON response with success status.
 * 
 * REQUEST (POST):
 *   - product_id: int (must be > 0)
 *   - quantity: int (must be > 0)
 * 
 * RESPONSE (JSON):
 *   - success: bool (true/false)
 *   - message: string (status description)
 * 
 * HTTP STATUS CODES:
 *   - 200 OK: Quantity successfully updated
 *   - 400 Bad Request: Invalid product_id or quantity
 *   - 401 Unauthorized: User not logged in
 *   - 404 Not Found: Product not in cart
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

  // 3. INPUT VALIDATION - Parse and sanitize product_id and quantity
  $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
  $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

  // 4. VERIFY INPUT - Ensure both values are positive integers
  if ($product_id <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
  }

  // 5. CHECK CART - Verify item exists in cart before updating
  if (!isset($_SESSION['cart'][$product_id])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Item not in cart']);
    exit;
  }

  // 6. UPDATE QUANTITY - Modify session cart with new quantity
  $_SESSION['cart'][$product_id]['quantity'] = $quantity;

  // 7. RETURN SUCCESS - JSON response confirming update
  echo json_encode([
    'success' => true,
    'message' => 'Cart updated'
  ]);
  exit;

} catch (Throwable $e) {
  // 8. ERROR HANDLING - Catch all exceptions and return 500 error
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  exit;
}
?>
