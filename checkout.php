<?php
/**
 * checkout.php - Checkout and Order Placement Page
 */

session_start();
require_once 'includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Get cart from session
$cart = $_SESSION['cart'] ?? [];

// Redirect if cart is empty
if (empty($cart)) {
  header('Location: products.php');
  exit;
}

// Calculate total
$total = 0;
$cart_count = 0;
foreach ($cart as $item) {
  $total += $item['price'] * $item['quantity'];
  $cart_count += $item['quantity'];
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $user_id = (int)$_SESSION['user_id'];
    $store_id = isset($_POST['store_id']) && $_POST['store_id'] !== '' ? (int)$_POST['store_id'] : null;

    // Begin transaction
    $pdo->beginTransaction();

    // Create order
    $stmt = $pdo->prepare("
      INSERT INTO orders (user_id, store_id, total_price, status)
      VALUES (?, ?, ?, 'pending')
    ");
    $stmt->execute([$user_id, $store_id, $total]);
    $order_id = (int)$pdo->lastInsertId();

    // Add order items
    $insertStmt = $pdo->prepare("
      INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
      VALUES (?, ?, ?, ?)
    ");

    foreach ($cart as $product_id => $item) {
      $insertStmt->execute([
        $order_id,
        $item['product_id'],
        $item['quantity'],
        $item['price']
      ]);
    }

    // Commit transaction
    $pdo->commit();

    // Clear cart
    unset($_SESSION['cart']);

    // Redirect to order confirmation
    header('Location: order-confirmation.php?order_id=' . $order_id);
    exit;

  } catch (Throwable $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $error = 'Failed to place order: ' . $e->getMessage();
  }
}

// Fetch stores for click & collect option
$stmt = $pdo->query("SELECT store_id, name, location_code, address FROM stores ORDER BY name ASC");
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title for header
$pageTitle = 'Checkout — AstroBite';
$pageDescription = 'Complete your order.';

require_once 'includes/header.php';
?>

<main class="container checkout-page">
  <h1>Checkout</h1>

  <?php if (isset($error)): ?>
    <p class="error-message"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <div class="checkout-wrapper">
    <!-- Order Review -->
    <section class="checkout-order-review">
      <h2>Order Review</h2>

      <div class="review-items">
        <?php foreach ($cart as $product_id => $item): 
          $subtotal = $item['price'] * $item['quantity'];
        ?>
          <div class="review-item">
            <div class="item-name">
              <a href="<?= $basePath ?>/product.php?id=<?= (int)$product_id ?>">
                <?= htmlspecialchars($item['name']) ?>
              </a>
            </div>
            <div class="item-details">
              <span class="qty">Qty: <?= (int)$item['quantity'] ?></span>
              <span class="price">$<?= number_format($item['price'], 2) ?></span>
              <span class="subtotal">$<?= number_format($subtotal, 2) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="review-summary">
        <div class="summary-row">
          <span>Subtotal:</span>
          <span>$<?= number_format($total, 2) ?></span>
        </div>
        <div class="summary-row">
          <span>Shipping:</span>
          <span>Free</span>
        </div>
        <div class="summary-row total">
          <span>Total:</span>
          <span>$<?= number_format($total, 2) ?></span>
        </div>
      </div>
    </section>

    <!-- Checkout Form -->
    <section class="checkout-form-section">
      <h2>Place Your Order</h2>

      <form method="POST" action="checkout.php" class="checkout-form">

        <!-- Collection Point Selection -->
        <fieldset>
          <legend>Select Collection Point (Optional)</legend>
          <p class="form-hint">Choose a store for click & collect, or leave empty for delivery.</p>

          <div class="radio-group">


            <?php foreach ($stores as $store): ?>
              <label class="radio-label">
                <input type="radio" name="store_id" value="<?= (int)$store['store_id'] ?>" />
                <span class="store-info">
                  <strong><?= htmlspecialchars($store['name']) ?></strong>
                  <br />
                  <small><?= htmlspecialchars($store['location_code']) ?> • <?= htmlspecialchars($store['address']) ?></small>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <!-- Actions -->
        <div class="checkout-actions">
          <button type="submit" class="button primary large">
            Place Order
          </button>
          <a href="<?= $basePath ?>/cart.php" class="button secondary">
            Back to Cart
          </a>
        </div>

        <p class="agreement">
          By placing this order, you agree to our terms and conditions.
        </p>
      </form>
    </section>
  </div>

  <!-- Screen reader announcements -->
  <div class="sr-live" aria-live="polite" aria-atomic="true"></div>
</main>

<style>
.checkout-page { padding: 2rem 0; }
.checkout-page h1 { margin-bottom: 2rem; }

.checkout-wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.checkout-order-review,
.checkout-form-section {
  background: rgba(10, 40, 60, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  padding: 1.5rem;
  backdrop-filter: blur(4px);
}

.checkout-order-review h2,
.checkout-form-section h2 {
  margin-bottom: 1.5rem;
  font-size: 1.2rem;
}

.review-items {
  display: grid;
  gap: 1rem;
  margin-bottom: 1.5rem;
  max-height: 400px;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.review-item {
  padding: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  background: rgba(0, 0, 0, 0.2);
}

.review-item .item-name {
  margin-bottom: 0.5rem;
}

.review-item .item-name a {
  color: #5dd9ff;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s;
}

.review-item .item-name a:hover {
  color: #fff;
  text-decoration: underline;
}

.review-item .item-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.8);
  gap: 1rem;
}

.item-details .price {
  font-weight: 600;
}

.item-details .subtotal {
  margin-left: auto;
  color: #a3ff70;
  font-weight: 600;
}

.review-summary {
  border-top: 2px solid rgba(255, 255, 255, 0.12);
  padding-top: 1rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.8);
}

.summary-row.total {
  font-size: 1.1rem;
  font-weight: 700;
  color: #5dd9ff;
  padding: 0.75rem 0;
  margin-top: 0.5rem;
}

.checkout-form fieldset {
  margin-bottom: 1.5rem;
  border: none;
  padding: 0;
}

.checkout-form legend {
  font-weight: 600;
  font-size: 0.95rem;
  margin-bottom: 0.75rem;
  display: block;
  color: #fff;
}

.form-hint {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 1rem;
}

.radio-group {
  display: grid;
  gap: 0.75rem;
}

.radio-label {
  display: flex;
  align-items: flex-start;
  cursor: pointer;
  padding: 0.75rem;
  border-radius: 8px;
  transition: background 0.2s;
  background: rgba(0, 0, 0, 0.2);
}

.radio-label:hover {
  background: rgba(93, 217, 255, 0.1);
}

.radio-label input[type="radio"] {
  margin-right: 0.75rem;
  margin-top: 0.25rem;
  cursor: pointer;
  accent-color: #5dd9ff;
}

.radio-label span {
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.9rem;
}

.radio-label .store-info {
  display: block;
}

.radio-label .store-info strong {
  display: block;
  color: #5dd9ff;
  margin-bottom: 0.25rem;
}

.radio-label .store-info small {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.8rem;
}

.checkout-actions {
  margin-top: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.checkout-actions button.primary,
.checkout-actions a.secondary {
  width: 100%;
  padding: 1rem;
  font-size: 1rem;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
  transition: all 0.2s;
}

.checkout-actions button.primary {
  background: linear-gradient(135deg, #00d84e, #00ff6a);
  color: #000;
}

.checkout-actions button.primary:hover {
  opacity: 0.9;
  transform: translateY(-2px);
}

.checkout-actions a.secondary {
  background: rgba(255, 255, 255, 0.1);
  color: #5dd9ff;
  border: 1px solid rgba(93, 217, 255, 0.3);
}

.checkout-actions a.secondary:hover {
  background: rgba(93, 217, 255, 0.2);
}

.agreement {
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.5);
  text-align: center;
  margin-top: 1rem;
}

.error-message {
  background: rgba(255, 100, 100, 0.1);
  border: 1px solid rgba(255, 100, 100, 0.3);
  color: #ff7a7a;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

@media (max-width: 900px) {
  .checkout-wrapper {
    grid-template-columns: 1fr;
  }

  .review-items {
    max-height: 300px;
  }
}
</style>

<script>
(function() {
  // Form validation before submission
  const form = document.querySelector('.checkout-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      // Could add more validation here if needed
      const btn = form.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.textContent = 'Processing...';
    });
  }
})();
</script>

<?php require_once 'includes/footer.php'; ?>
