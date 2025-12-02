<?php
/**
 * /ajax/get-cart.php
 * 
 * Returns the user's current cart contents as JSON
 * 
 * Returns JSON:
 *   - items: array of cart items with product_id, name, price, quantity
 *   - total: sum of all items (quantity * price)
 *   - cart_count: total number of items
 */

session_start();

header('Content-Type: application/json; charset=utf-8');

try {
  $cart = $_SESSION['cart'] ?? [];
  $total = 0;
  $cart_count = 0;

  $items = [];
  foreach ($cart as $product_id => $item) {
    $items[] = [
      'product_id' => $item['product_id'],
      'name' => $item['name'],
      'price' => $item['price'],
      'quantity' => $item['quantity'],
      'subtotal' => $item['price'] * $item['quantity']
    ];
    $total += $item['price'] * $item['quantity'];
    $cart_count += $item['quantity'];
  }

  echo json_encode([
    'items' => $items,
    'total' => round($total, 2),
    'cart_count' => $cart_count
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'error' => 'Failed to fetch cart: ' . $e->getMessage()
  ]);
  exit;
}
?>
