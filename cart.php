<?php
/**
 * cart.php - Shopping Cart Page
 */

session_start();
require_once 'includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Page title for header
$pageTitle = 'Shopping Cart — AstroBite';
$pageDescription = 'Review your shopping cart and proceed to checkout.';

require_once 'includes/header.php';

// Get cart from session
$cart = $_SESSION['cart'] ?? [];
$total = 0;
$cart_count = 0;

foreach ($cart as $item) {
  $total += $item['price'] * $item['quantity'];
  $cart_count += $item['quantity'];
}
?>

<main class="container cart-page">
  <h1>Shopping Cart</h1>

  <?php if (empty($cart)): ?>
    <p class="empty-cart-message">Your cart is empty.</p>
    <a href="<?= $basePath ?>/products.php" class="button primary">Continue Shopping</a>

  <?php else: ?>
    <div class="cart-wrapper">
      <!-- Cart Items -->
      <section class="cart-items">
        <h2>Cart Items (<?= $cart_count ?>)</h2>
        
        <div class="cart-table-responsive">
          <table class="cart-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cart as $product_id => $item): 
                $subtotal = $item['price'] * $item['quantity'];
              ?>
                <tr class="cart-item" data-product-id="<?= (int)$product_id ?>">
                  <td class="item-name">
                    <a href="<?= $basePath ?>/product.php?id=<?= (int)$product_id ?>">
                      <?= htmlspecialchars($item['name']) ?>
                    </a>
                  </td>
                  <td class="item-price">$<?= number_format($item['price'], 2) ?></td>
                  <td class="item-qty">
                    <input type="number" 
                           min="1" 
                           value="<?= (int)$item['quantity'] ?>" 
                           class="qty-input update-qty"
                           data-product-id="<?= (int)$product_id ?>"
                           aria-label="Quantity for <?= htmlspecialchars($item['name']) ?>" />
                  </td>
                  <td class="item-subtotal">
                    <span class="subtotal-value">$<?= number_format($subtotal, 2) ?></span>
                  </td>
                  <td class="item-action">
                    <button class="btn-remove" 
                            data-product-id="<?= (int)$product_id ?>"
                            aria-label="Remove <?= htmlspecialchars($item['name']) ?> from cart">
                      Remove
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Cart Summary -->
      <section class="cart-summary">
        <h2>Order Summary</h2>
        <div class="summary-content">
          <div class="summary-row">
            <span>Subtotal</span>
            <span class="subtotal">$<?= number_format($total, 2) ?></span>
          </div>
          <div class="summary-row">
            <span>Shipping</span>
            <span class="shipping">Free</span>
          </div>
          <div class="summary-row total">
            <span>Total</span>
            <span class="total-amount">$<?= number_format($total, 2) ?></span>
          </div>

          <a href="<?= $basePath ?>/checkout.php" class="button primary large">
            Proceed to Checkout
          </a>
          <a href="<?= $basePath ?>/products.php" class="button secondary">
            Continue Shopping
          </a>
        </div>
      </section>
    </div>
  <?php endif; ?>

  <!-- Screen reader announcements -->
  <div class="sr-live" aria-live="polite" aria-atomic="true"></div>
</main>

<style>
.cart-page { padding: 2rem 0; }
.cart-page h1 { margin-bottom: 2rem; }

.empty-cart-message {
  text-align: center;
  padding: 2rem;
  color: rgba(255, 255, 255, 0.7);
  font-size: 1.1rem;
  margin-bottom: 2rem;
}

.cart-wrapper {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.cart-items {
  background: rgba(10, 40, 60, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  padding: 1.5rem;
  backdrop-filter: blur(4px);
}

.cart-items h2 {
  margin-bottom: 1.5rem;
  font-size: 1.3rem;
}

.cart-table-responsive {
  overflow-x: auto;
}

.cart-table {
  width: 100%;
  border-collapse: collapse;
  color: rgba(255, 255, 255, 0.9);
}

.cart-table thead {
  background: rgba(0, 0, 0, 0.3);
  border-bottom: 2px solid rgba(255, 255, 255, 0.12);
}

.cart-table th {
  text-align: left;
  padding: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
}

.cart-table tbody tr {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.cart-table td {
  padding: 1rem 0.75rem;
}

.item-name a {
  color: #5dd9ff;
  text-decoration: none;
  transition: color 0.2s;
}

.item-name a:hover {
  color: #fff;
  text-decoration: underline;
}

.item-price, .item-subtotal {
  font-weight: 600;
}

.qty-input {
  width: 70px;
  padding: 0.5rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 6px;
  color: #fff;
  text-align: center;
}

.qty-input:focus {
  outline: none;
  border-color: #5dd9ff;
  box-shadow: 0 0 8px rgba(93, 217, 255, 0.3);
}

.btn-remove {
  padding: 0.5rem 1rem;
  background: rgba(255, 100, 100, 0.1);
  border: 1px solid rgba(255, 100, 100, 0.3);
  color: #ff7a7a;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9rem;
}

.btn-remove:hover {
  background: rgba(255, 100, 100, 0.3);
  border-color: rgba(255, 100, 100, 0.6);
}

.cart-summary {
  background: rgba(10, 40, 60, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  padding: 1.5rem;
  backdrop-filter: blur(4px);
  height: fit-content;
  position: sticky;
  top: 80px;
}

.cart-summary h2 {
  margin-bottom: 1.5rem;
  font-size: 1.2rem;
}

.summary-content { display: flex; flex-direction: column; gap: 1rem; }

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 0.95rem;
}

.summary-row.total {
  border: none;
  font-size: 1.2rem;
  font-weight: 700;
  color: #5dd9ff;
  padding: 1rem 0;
  margin: 0.5rem 0;
}

.summary-row .shipping {
  color: #a3ff70;
  font-weight: 600;
}

button.primary.large,
a.button.primary.large {
  width: 100%;
  padding: 1rem;
  margin-top: 1rem;
  font-size: 1rem;
  font-weight: 600;
}

a.button.secondary {
  width: 100%;
  text-align: center;
  padding: 0.75rem;
  margin-top: 0.5rem;
}

@media (max-width: 768px) {
  .cart-wrapper {
    grid-template-columns: 1fr;
  }

  .cart-summary {
    position: static;
  }

  .cart-table {
    font-size: 0.9rem;
  }

  .cart-table th, .cart-table td {
    padding: 0.5rem;
  }

  .qty-input {
    width: 60px;
    font-size: 0.9rem;
  }
}
</style>

<script>
(function() {
  // Update quantity in session
  document.querySelectorAll('.update-qty').forEach(input => {
    input.addEventListener('change', async function() {
      const product_id = parseInt(this.dataset.productId);
      const quantity = parseInt(this.value);

      if (quantity <= 0) {
        this.value = 1;
        return;
      }

      try {
        const formData = new FormData();
        formData.append('product_id', product_id);
        formData.append('quantity', quantity);

        const response = await fetch('<?= $basePath ?>/ajax/update-cart.php', {
          method: 'POST',
          body: formData
        });

        if (response.ok) {
          // Reload page to reflect changes
          location.reload();
        }
      } catch (error) {
        console.error('Error updating cart:', error);
        alert('Failed to update cart.');
      }
    });
  });

  // Remove item from cart
  document.querySelectorAll('.btn-remove').forEach(btn => {
    btn.addEventListener('click', async function() {
      const product_id = parseInt(this.dataset.productId);

      if (!confirm('Remove this item from cart?')) return;

      try {
        const formData = new FormData();
        formData.append('product_id', product_id);

        const response = await fetch('<?= $basePath ?>/ajax/remove-cart-item.php', {
          method: 'POST',
          body: formData
        });

        if (response.ok) {
          location.reload();
        }
      } catch (error) {
        console.error('Error removing item:', error);
        alert('Failed to remove item.');
      }
    });
  });
})();
</script>

<?php require_once 'includes/footer.php'; ?>
