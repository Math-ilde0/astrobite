<?php
/**
 * /ajax/get-cart.php - Retrieve Shopping Cart Contents
 * 
 * AJAX endpoint that returns the user's current shopping cart as JSON.
 * Builds cart data from $_SESSION['cart'], calculates totals, and item count.
 * No authentication required (session-based cart access). Iterates through
 * cart items, calculates subtotals (price * quantity), sums total price and
 * total item count. Returns complete cart state with line items and totals.
 * 
 * REQUEST: No parameters required (GET/POST)
 * 
 * RESPONSE (JSON Object):
 *   - items: array of cart items, each containing:
 *     - product_id: int (product identifier)
 *     - name: string (product name)
 *     - price: decimal (unit price)
 *     - quantity: int (quantity in cart)
 *     - subtotal: decimal (price * quantity)
 *   - total: decimal (sum of all subtotals, rounded to 2 decimals)
 *   - cart_count: int (total number of items across all products)
 * 
 * EXAMPLE RESPONSE:
 *   {
 *     "items": [
 *       {
 *         "product_id": 1,
 *         "name": "Pancakes",
 *         "price": "8.99",
 *         "quantity": 2,
 *         "subtotal": 17.98
 *       }
 *     ],
 *     "total": 17.98,
 *     "cart_count": 2
 *   }
 */

// 1. Initialize session for cart access
session_start();

// 2. Set JSON response header with UTF-8 encoding
header('Content-Type: application/json; charset=utf-8');

try {
  // 3. INITIALIZE - Get cart from session or use empty array if not set
  $cart = $_SESSION['cart'] ?? [];
  $total = 0;
  $cart_count = 0;

  // 4. BUILD ITEMS - Iterate through cart and calculate subtotals
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

  // 5. RETURN - JSON response with items, total, and item count
  echo json_encode([
    'items' => $items,
    'total' => round($total, 2),
    'cart_count' => $cart_count
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  // 6. ERROR HANDLING - Catch exceptions and return 500 with error message
  http_response_code(500);
  echo json_encode([
    'error' => 'Failed to fetch cart: ' . $e->getMessage()
  ]);
  exit;
}
?>
