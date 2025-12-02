<?php
/**
 * order-confirmation.php - Order Confirmation Page
 */

session_start();
require_once 'includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Get order ID from query parameter
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
  header('Location: products.php');
  exit;
}

// Fetch order details
$stmt = $pdo->prepare("
  SELECT 
    o.order_id, o.user_id, o.store_id, o.total_price, o.status, o.created_at,
    s.name AS store_name, s.location_code, s.address
  FROM orders o
  LEFT JOIN stores s ON s.store_id = o.store_id
  WHERE o.order_id = ? AND o.user_id = ?
  LIMIT 1
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
  header('Location: products.php');
  exit;
}

// Fetch order items
$stmt = $pdo->prepare("
  SELECT oi.product_id, oi.quantity, oi.price_at_purchase, p.name, p.image1
  FROM order_items oi
  JOIN products p ON p.product_id = oi.product_id
  WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title for header
$pageTitle = 'Order Confirmation — AstroBite';
$pageDescription = 'Your order has been placed successfully.';

require_once 'includes/header.php';
?>

<main class="container confirmation-page">
  <section class="confirmation-success">
    <div class="success-icon">✓</div>
    <h1>Order Placed Successfully!</h1>
    <p class="order-number">Order #<?= str_pad((int)$order['order_id'], 6, '0', STR_PAD_LEFT) ?></p>
  </section>

  <div class="confirmation-wrapper">
    <!-- Order Details -->
    <section class="order-details">
      <h2>Order Details</h2>

      <div class="detail-group">
        <label>Order Date:</label>
        <p><?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></p>
      </div>

      <div class="detail-group">
        <label>Status:</label>
        <p class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
          <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))) ?>
        </p>
      </div>

      <?php if ($order['store_name']): ?>
        <div class="detail-group">
          <label>Collection Point:</label>
          <div class="store-details">
            <strong><?= htmlspecialchars($order['store_name']) ?></strong><br />
            <span class="code"><?= htmlspecialchars($order['location_code']) ?></span><br />
            <span class="address"><?= htmlspecialchars($order['address']) ?></span>
          </div>
        </div>
      <?php else: ?>
        <div class="detail-group">
          <label>Delivery:</label>
          <p>To your registered address</p>
        </div>
      <?php endif; ?>
    </section>

    <!-- Order Items -->
    <section class="order-items">
      <h2>Items Ordered</h2>

      <div class="items-list">
        <?php foreach ($order_items as $item): 
          $subtotal = $item['price_at_purchase'] * $item['quantity'];
        ?>
          <div class="item">
            <?php if ($item['image1']): ?>
              <div class="item-image">
                <img src="<?= htmlspecialchars($item['image1']) ?>" 
                     alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy" />
              </div>
            <?php endif; ?>

            <div class="item-info">
              <h3><?= htmlspecialchars($item['name']) ?></h3>
              <div class="item-row">
                <span>Quantity:</span>
                <span class="qty">×<?= (int)$item['quantity'] ?></span>
              </div>
              <div class="item-row">
                <span>Unit Price:</span>
                <span class="price">$<?= number_format($item['price_at_purchase'], 2) ?></span>
              </div>
              <div class="item-row subtotal">
                <span>Subtotal:</span>
                <span>$<?= number_format($subtotal, 2) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Order Summary -->
      <div class="order-summary">
        <div class="summary-row">
          <span>Subtotal:</span>
          <span>$<?= number_format($order['total_price'], 2) ?></span>
        </div>
        <div class="summary-row">
          <span>Shipping:</span>
          <span>Free</span>
        </div>
        <div class="summary-row total">
          <span>Total:</span>
          <span>$<?= number_format($order['total_price'], 2) ?></span>
        </div>
      </div>
    </section>
  </div>

  <!-- Next Steps -->
  <section class="next-steps">
    <h2>What's Next?</h2>
    <div class="steps-container">
      <div class="step">
        <div class="step-number">1</div>
        <h3>Confirmation Email</h3>
        <p>Check your email for an order confirmation with details.</p>
      </div>

      <div class="step">
        <div class="step-number">2</div>
        <h3>Order Processing</h3>
        <p>We'll prepare your order and notify you when it's ready.</p>
      </div>

      <div class="step">
        <div class="step-number">3</div>
        <h3><?= $order['store_name'] ? 'Pickup' : 'Delivery' ?></h3>
        <p><?= $order['store_name'] 
          ? 'Visit the collection point to pick up your order.' 
          : 'Your order will be delivered to your address.' ?></p>
      </div>
    </div>
  </section>

  <!-- Actions -->
  <div class="confirmation-actions">
    <a href="<?= $basePath ?>/profile.php" class="button secondary">
      View All Orders
    </a>
    <a href="<?= $basePath ?>/products.php" class="button primary">
      Continue Shopping
    </a>
  </div>
</main>

<style>
.confirmation-page { padding: 2rem 0; }

.confirmation-success {
  text-align: center;
  margin-bottom: 3rem;
  padding: 2rem;
  background: rgba(10, 60, 40, 0.3);
  border: 2px solid rgba(163, 255, 112, 0.3);
  border-radius: 12px;
}

.success-icon {
  font-size: 4rem;
  color: #a3ff70;
  margin-bottom: 1rem;
}

.confirmation-success h1 {
  color: #a3ff70;
  margin-bottom: 0.5rem;
}

.order-number {
  font-size: 1.2rem;
  color: #5dd9ff;
  font-weight: 600;
}

.confirmation-wrapper {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.order-details,
.order-items {
  background: rgba(10, 40, 60, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  padding: 1.5rem;
  backdrop-filter: blur(4px);
}

.order-details h2,
.order-items h2 {
  margin-bottom: 1.5rem;
  font-size: 1.2rem;
}

.detail-group {
  margin-bottom: 1.5rem;
}

.detail-group label {
  font-weight: 600;
  color: rgba(255, 255, 255, 0.8);
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.detail-group p {
  color: rgba(255, 255, 255, 0.9);
  margin: 0;
}

.status-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
}

.status-pending {
  background: rgba(255, 200, 0, 0.2);
  color: #ffc800;
}

.status-ready_for_pickup {
  background: rgba(100, 200, 255, 0.2);
  color: #64c8ff;
}

.status-completed {
  background: rgba(163, 255, 112, 0.2);
  color: #a3ff70;
}

.store-details {
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.6;
}

.store-details strong {
  color: #5dd9ff;
  display: block;
  margin-bottom: 0.25rem;
}

.store-details .code {
  display: inline-block;
  background: rgba(93, 217, 255, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}

.store-details .address {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.7);
}

.items-list {
  display: grid;
  gap: 1rem;
  margin-bottom: 1.5rem;
  max-height: 400px;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  background: rgba(0, 0, 0, 0.2);
}

.item-image {
  flex-shrink: 0;
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  background: rgba(0, 0, 0, 0.3);
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-info {
  flex: 1;
}

.item-info h3 {
  margin: 0 0 0.75rem 0;
  color: #5dd9ff;
  font-size: 1rem;
}

.item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 0.5rem;
}

.item-row .qty,
.item-row .price {
  font-weight: 600;
}

.item-row.subtotal {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 0.5rem;
  margin-top: 0.5rem;
  color: #a3ff70;
  font-weight: 600;
}

.order-summary {
  border-top: 2px solid rgba(255, 255, 255, 0.12);
  padding-top: 1rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.next-steps {
  background: rgba(10, 40, 60, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  padding: 2rem;
  backdrop-filter: blur(4px);
  margin-bottom: 2rem;
}

.next-steps h2 {
  margin-bottom: 2rem;
  font-size: 1.3rem;
}

.steps-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.step {
  text-align: center;
}

.step-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #00d84e, #00ff6a);
  color: #000;
  font-weight: 700;
  font-size: 1.5rem;
  margin-bottom: 1rem;
}

.step h3 {
  margin: 0.5rem 0;
  color: #5dd9ff;
  font-size: 1rem;
}

.step p {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.9rem;
  line-height: 1.5;
}

.confirmation-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.confirmation-actions a {
  padding: 1rem 2rem;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
}

.confirmation-actions a.primary {
  background: linear-gradient(135deg, #00d84e, #00ff6a);
  color: #000;
}

.confirmation-actions a.primary:hover {
  opacity: 0.9;
  transform: translateY(-2px);
}

.confirmation-actions a.secondary {
  background: rgba(255, 255, 255, 0.1);
  color: #5dd9ff;
  border: 1px solid rgba(93, 217, 255, 0.3);
}

.confirmation-actions a.secondary:hover {
  background: rgba(93, 217, 255, 0.2);
}

@media (max-width: 900px) {
  .confirmation-wrapper {
    grid-template-columns: 1fr;
  }

  .steps-container {
    grid-template-columns: 1fr;
  }

  .item {
    flex-direction: column;
  }

  .item-image {
    width: 100%;
    height: 150px;
  }
}
</style>

<?php require_once 'includes/footer.php'; ?>
