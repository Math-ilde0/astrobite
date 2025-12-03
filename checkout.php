<?php
/**
 * checkout.php - Checkout and Order Placement Page
 * 
 * Purpose: Display order review, store selection, and process order placement
 * 
 * Features:
 * - Order review with cart items and pricing summary
 * - Click & collect store selection (optional)
 * - Database transaction for atomic order creation
 * - Session-based authentication (login required)
 * 
 * Security: Prepared statements, transactions, htmlspecialchars() escaping, session validation
 * 
 * Session: user_id, cart (array of product_id, name, price, quantity)
 * POST: store_id (optional, for click & collect)
 * Redirects: login.php (not auth), products.php (empty cart), order-confirmation.php (success)
 * 
 * Dependencies: db.php, header.php, footer.php
 */

session_start();
require_once 'includes/db.php';

// -------------------------------------------------------
// 1) Verify User Authentication
// -------------------------------------------------------
// Redirect to login if user_id not in session
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// -------------------------------------------------------
// 2) Verify Cart is Not Empty
// -------------------------------------------------------
// Get cart from session (empty array if not set)
$cart = $_SESSION['cart'] ?? [];

// Redirect if cart is empty
if (empty($cart)) {
  header('Location: products.php');
  exit;
}

// -------------------------------------------------------
// 3) Calculate Order Totals
// -------------------------------------------------------
// Sum price × quantity for each cart item
$total = 0;
$cart_count = 0;
foreach ($cart as $item) {
  $total += $item['price'] * $item['quantity'];
  $cart_count += $item['quantity'];
}

// -------------------------------------------------------
// 4) Handle Order Placement Form Submission
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Get authenticated user ID (safe - from session, cast to int)
    $user_id = (int)$_SESSION['user_id'];
    
    // Get store_id from POST if provided, otherwise null
    $store_id = isset($_POST['store_id']) && $_POST['store_id'] !== '' ? (int)$_POST['store_id'] : null;

    // -------------------------------------------------------
    // 5) Begin Database Transaction
    // -------------------------------------------------------
    // Ensures all-or-nothing: if any insert fails, everything rolls back
    $pdo->beginTransaction();

    // -------------------------------------------------------
    // 6) Create Order Record
    // -------------------------------------------------------
    // Insert main order with user_id, optional store_id, and current total
    $stmt = $pdo->prepare("
      INSERT INTO orders (user_id, store_id, total_price, status)
      VALUES (?, ?, ?, 'pending')
    ");
    $stmt->execute([$user_id, $store_id, $total]);
    
    // Get auto-incremented order ID for related items
    $order_id = (int)$pdo->lastInsertId();

    // -------------------------------------------------------
    // 7) Add Order Line Items
    // -------------------------------------------------------
    // Record each cart item with price snapshot (protects against price changes)
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

    // -------------------------------------------------------
    // 8) Commit Transaction
    // -------------------------------------------------------
    // All inserts succeeded - permanently store the order
    $pdo->commit();

    // -------------------------------------------------------
    // 9) Clear Cart & Redirect to Confirmation
    // -------------------------------------------------------
    // Remove cart from session after successful order
    unset($_SESSION['cart']);

    // Redirect to order confirmation page with order ID
    header('Location: order-confirmation.php?order_id=' . $order_id);
    exit;

  } catch (Throwable $e) {
    // -------------------------------------------------------
    // 10) Handle Transaction Error
    // -------------------------------------------------------
    // Rollback all changes if any database operation fails
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $error = 'Failed to place order: ' . $e->getMessage();
  }
}

// -------------------------------------------------------
// 11) Fetch Available Collection Points
// -------------------------------------------------------
// Get all stores for click & collect dropdown
$stmt = $pdo->query("SELECT store_id, name, location_code, address FROM stores ORDER BY name ASC");
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------------
// 12) Set SEO Metadata
// -------------------------------------------------------
$pageTitle = 'Checkout — AstroBite';
$pageDescription = 'Complete your order.';

require_once 'includes/header.php';
?>

<main class="container checkout-page">
  <!-- ========== PAGE TITLE ========== -->
  <!-- Main checkout heading -->
  <h1>Checkout</h1>

  <!-- ========== ERROR MESSAGE ========== -->
  <!-- Display transaction error if order placement failed -->
  <?php if (isset($error)): ?>
    <p class="error-message"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <!-- ========== CHECKOUT LAYOUT (2-COLUMN GRID) ========== -->
  <!-- Left: Order review | Right: Checkout form -->
  <div class="checkout-wrapper">
    
    <!-- ========== ORDER REVIEW SECTION ========== -->
    <!-- Displays cart items with individual subtotals and order summary -->
    <section class="checkout-order-review">
      <h2>Order Review</h2>

      <!-- ========== REVIEW ITEMS LIST ========== -->
      <!-- Scrollable list of cart items with qty, unit price, and subtotal -->
      <div class="review-items">
        <?php foreach ($cart as $product_id => $item): 
          $subtotal = $item['price'] * $item['quantity'];
        ?>
          <!-- Individual cart item with product link and pricing -->
          <div class="review-item">
            <div class="item-name">
              <a href="<?= $basePath ?>/product.php?id=<?= (int)$product_id ?>">
                <?= htmlspecialchars($item['name']) ?>
              </a>
            </div>
            <!-- Item details: quantity, unit price, subtotal -->
            <div class="item-details">
              <span class="qty">Qty: <?= (int)$item['quantity'] ?></span>
              <span class="price">$<?= number_format($item['price'], 2) ?></span>
              <span class="subtotal">$<?= number_format($subtotal, 2) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ========== ORDER SUMMARY ========== -->
      <!-- Subtotal, shipping, and total price display -->
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

    <!-- ========== CHECKOUT FORM SECTION ========== -->
    <!-- Store selection and order placement -->
    <section class="checkout-form-section">
      <h2>Place Your Order</h2>

      <!-- ========== CHECKOUT FORM ========== -->
      <!-- POST to checkout.php to trigger order creation -->
      <form method="POST" action="checkout.php" class="checkout-form">

        <!-- ========== COLLECTION POINT SELECTION ========== -->
        <!-- Optional click & collect store selection -->
        <fieldset>
          <legend>Select Collection Point (Optional)</legend>
          <p class="form-hint">Choose a store for click & collect, or leave empty for delivery.</p>

          <!-- ========== STORE RADIO OPTIONS ========== -->
          <!-- List of available stores with location info -->
          <div class="radio-group">
            <?php foreach ($stores as $store): ?>
              <!-- Individual store radio button with name and address -->
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

        <!-- ========== ACTION BUTTONS ========== -->
        <!-- Place order (primary) and return to cart (secondary) buttons -->
        <div class="checkout-actions">
          <button type="submit" class="button primary large">
            Place Order
          </button>
          <a href="<?= $basePath ?>/cart.php" class="button secondary">
            Back to Cart
          </a>
        </div>

        <!-- ========== TERMS AGREEMENT ========== -->
        <!-- Legal notice before order submission -->
        <p class="agreement">
          By placing this order, you agree to our terms and conditions.
        </p>
      </form>
    </section>
  </div>

  <!-- ========== SCREEN READER ANNOUNCEMENTS ========== -->
  <!-- Live region for dynamic status updates (accessibility) -->
  <div class="sr-live" aria-live="polite" aria-atomic="true"></div>
</main>

<!-- ========== CHECKOUT PAGE STYLES ========== -->
<!-- Responsive grid layout, card styling, form elements, and error states -->
<style>
.checkout-page { padding: 2rem 0; }
.checkout-page h1 { margin-bottom: 2rem; }

/* Two-column grid layout: order review on left, form on right */
.checkout-wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

/* Shared section styling: dark background with blur effect */
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

/* Scrollable item list with max height */
.review-items {
  display: grid;
  gap: 1rem;
  margin-bottom: 1.5rem;
  max-height: 400px;
  overflow-y: auto;
  padding-right: 0.5rem;
}

/* Individual item card styling */
.review-item {
  padding: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  background: rgba(0, 0, 0, 0.2);
}

.review-item .item-name {
  margin-bottom: 0.5rem;
}

/* Product link styling with hover state */
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

/* Item details (qty, price, subtotal) layout */
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

/* Subtotal highlighted in green */
.item-details .subtotal {
  margin-left: auto;
  color: #a3ff70;
  font-weight: 600;
}

/* Summary section with divider */
.review-summary {
  border-top: 2px solid rgba(255, 255, 255, 0.12);
  padding-top: 1rem;
}

/* Summary row (subtotal, shipping, etc.) */
.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.8);
}

/* Final total row with emphasis */
.summary-row.total {
  font-size: 1.1rem;
  font-weight: 700;
  color: #5dd9ff;
  padding: 0.75rem 0;
  margin-top: 0.5rem;
}

/* Form fieldset styling (no border, custom legend) */
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

/* Hint text for optional fields */
.form-hint {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 1rem;
}

/* Radio button group layout */
.radio-group {
  display: grid;
  gap: 0.75rem;
}

/* Radio label styling with hover effect */
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

/* Custom radio button accent color */
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

/* Store name in cyan, address in gray */
.radio-label .store-info strong {
  display: block;
  color: #5dd9ff;
  margin-bottom: 0.25rem;
}

.radio-label .store-info small {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.8rem;
}

/* Action buttons layout (vertical stack) */
.checkout-actions {
  margin-top: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* Button styling (place order / back to cart) */
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

/* Primary button (green gradient) */
.checkout-actions button.primary {
  background: linear-gradient(135deg, #00d84e, #00ff6a);
  color: #000;
}

.checkout-actions button.primary:hover {
  opacity: 0.9;
  transform: translateY(-2px);
}

/* Secondary button (cyan outline) */
.checkout-actions a.secondary {
  background: rgba(255, 255, 255, 0.1);
  color: #5dd9ff;
  border: 1px solid rgba(93, 217, 255, 0.3);
}

.checkout-actions a.secondary:hover {
  background: rgba(93, 217, 255, 0.2);
}

/* Terms agreement disclaimer */
.agreement {
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.5);
  text-align: center;
  margin-top: 1rem;
}

/* Error message styling */
.error-message {
  background: rgba(255, 100, 100, 0.1);
  border: 1px solid rgba(255, 100, 100, 0.3);
  color: #ff7a7a;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

/* Mobile responsive: single column on small screens */
@media (max-width: 900px) {
  .checkout-wrapper {
    grid-template-columns: 1fr;
  }

  .review-items {
    max-height: 300px;
  }
}
</style>

<!-- ========== CHECKOUT FORM VALIDATION SCRIPT ========== -->
<!-- Handles form submission state (disables button, shows processing status) -->
<script>
(function() {
  // Form validation before submission
  const form = document.querySelector('.checkout-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      // Could add more validation here if needed
      // Disable submit button and show processing status during submission
      const btn = form.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.textContent = 'Processing...';
    });
  }
})();
</script>

<?php require_once 'includes/footer.php'; ?>
