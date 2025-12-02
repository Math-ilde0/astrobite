<?php
/**
 * admin/orders.php - Admin Orders Dashboard
 * Manage all orders and update their status
 */

session_start();
require_once __DIR__ . '/../includes/db.php';

// Check admin access
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
  header('Location: ../login.php');
  exit;
}

// Handle status update
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
  $order_id = (int)$_POST['order_id'];
  $status = $_POST['status'];
  
  $allowed_statuses = ['pending', 'ready_for_pickup', 'completed', 'cancelled'];
  
  if (in_array($status, $allowed_statuses)) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$status, $order_id]);
    $message = 'Order status updated successfully!';
  }
}

// Get filter
$filter = $_GET['status'] ?? 'all';
$allowed_filters = ['all', 'pending', 'ready_for_pickup', 'completed', 'cancelled'];
if (!in_array($filter, $allowed_filters)) {
  $filter = 'all';
}

// Fetch orders
$where = $filter !== 'all' ? "WHERE o.status = ?" : "";
$query = "
  SELECT 
    o.order_id, o.user_id, o.status, o.total_price, o.created_at,
    u.name, u.email,
    s.name AS store_name, s.location_code
  FROM orders o
  JOIN users u ON u.user_id = o.user_id
  LEFT JOIN stores s ON s.store_id = o.store_id
  $where
  ORDER BY o.created_at DESC
  LIMIT 100
";

$stmt = $pdo->prepare($query);
if ($filter !== 'all') {
  $stmt->execute([$filter]);
} else {
  $stmt->execute();
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders Dashboard — AstroBite Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
</head>
<body>

<main class="container admin-orders-page">
  <div class="admin-header">
    <h1>Orders Dashboard</h1>
    <a href="../profile.php" class="back-link">← Back to Profile</a>
  </div>

  <?php if ($message): ?>
    <div class="success-message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Filter Tabs -->
  <div class="filter-tabs">
    <a href="?status=all" class="tab <?= $filter === 'all' ? 'active' : '' ?>">All Orders</a>
    <a href="?status=pending" class="tab <?= $filter === 'pending' ? 'active' : '' ?>">⏳ Pending</a>
    <a href="?status=ready_for_pickup" class="tab <?= $filter === 'ready_for_pickup' ? 'active' : '' ?>">✅ Ready</a>
    <a href="?status=completed" class="tab <?= $filter === 'completed' ? 'active' : '' ?>">✔️ Completed</a>
    <a href="?status=cancelled" class="tab <?= $filter === 'cancelled' ? 'active' : '' ?>">❌ Cancelled</a>
  </div>

  <!-- Orders Table -->
  <div class="orders-wrapper">
    <?php if (empty($orders)): ?>
      <p class="no-orders">No orders found.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="orders-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Collection</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr class="order-row">
                <td class="order-id">#<?= str_pad((int)$order['order_id'], 6, '0', STR_PAD_LEFT) ?></td>
                <td class="customer">
                  <div class="name"><?= htmlspecialchars($order['name']) ?></div>
                  <div class="email"><?= htmlspecialchars($order['email']) ?></div>
                </td>
                <td class="amount">$<?= number_format((float)$order['total_price'], 2) ?></td>
                <td class="status">
                  <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))) ?>
                  </span>
                </td>
                <td class="collection">
                  <?php if ($order['store_name']): ?>
                    <div class="store"><?= htmlspecialchars($order['store_name']) ?></div>
                    <div class="code"><?= htmlspecialchars($order['location_code']) ?></div>
                  <?php else: ?>
                    <span class="delivery">Delivery</span>
                  <?php endif; ?>
                </td>
                <td class="date"><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                <td class="actions">
                  <button class="btn-status" data-order-id="<?= (int)$order['order_id'] ?>" data-status="<?= htmlspecialchars($order['status']) ?>">
                    Update
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Status Update Modal -->
  <div id="statusModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Update Order Status</h2>
      <form method="POST" id="statusForm">
        <input type="hidden" name="order_id" id="modalOrderId">
        
        <div class="form-group">
          <label for="statusSelect">New Status:</label>
          <select name="status" id="statusSelect" required>
            <option value="pending">Pending</option>
            <option value="ready_for_pickup">Ready for Pickup</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <div class="modal-actions">
          <button type="submit" class="button primary">Update</button>
          <button type="button" class="button secondary cancel">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
(function() {
  const modal = document.getElementById('statusModal');
  const statusForm = document.getElementById('statusForm');
  const modalOrderId = document.getElementById('modalOrderId');
  const modalStatusSelect = document.getElementById('statusSelect');
  const closeBtn = document.querySelector('.close');
  const cancelBtn = document.querySelector('.cancel');

  // Open modal
  document.querySelectorAll('.btn-status').forEach(btn => {
    btn.addEventListener('click', function() {
      const orderId = this.dataset.orderId;
      const currentStatus = this.dataset.status;
      
      modalOrderId.value = orderId;
      modalStatusSelect.value = currentStatus;
      modal.classList.add('active');
    });
  });

  // Close modal
  closeBtn.addEventListener('click', () => modal.classList.remove('active'));
  cancelBtn.addEventListener('click', () => modal.classList.remove('active'));

  // Close on outside click
  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.remove('active');
    }
  });
})();
</script>

</body>
</html>
