<?php
/**
 * admin/orders.php - Admin Orders Management Dashboard
 * 
 * OVERVIEW:
 * Protected admin page for viewing and managing all customer orders. Displays
 * paginated order list with customer details, totals, status, and collection
 * info. Admins can filter by status (pending, ready_for_pickup, completed,
 * cancelled) and update order status via modal dialog. Updates persist to
 * database. Returns 403 redirect for non-admin users.
 * 
 * FEATURES:
 * - Admin-only access with role verification
 * - Status filter tabs (All, Pending, Ready, Completed, Cancelled)
 * - Order table with 100 orders max (DESC by created_at)
 * - Customer info (name, email), amount formatted, collection (store or delivery)
 * - Status badges with color coding (pending, ready_for_pickup, etc.)
 * - Modal for status updates with validation (POST method)
 * - Success message confirmation after update
 * - Responsive table layout with table-responsive class
 * 
 * DATABASE QUERIES:
 * 1. Admin verification: SELECT role FROM users WHERE user_id = ?
 * 2. Order list: SELECT o.*, u.name, u.email, s.name, s.location_code
 *    FROM orders JOIN users JOIN stores (conditional WHERE by status)
 * 3. Status update: UPDATE orders SET status = ? WHERE order_id = ?
 */

// 1. SESSION & DATABASE - Initialize session and load PDO connection
session_start();
require_once __DIR__ . '/../includes/db.php';

// 2. ADMIN VERIFICATION - Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit;
}

// 3. ROLE CHECK - Verify user has admin role, redirect to login if not
$stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
  header('Location: ../login.php');
  exit;
}

// 4. HANDLE STATUS UPDATE - Process POST requests to update order status
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
  $order_id = (int)$_POST['order_id'];
  $status = $_POST['status'];
  
  // 4a. VALIDATE STATUS - Only allow predefined statuses
  $allowed_statuses = ['pending', 'ready_for_pickup', 'completed', 'cancelled'];
  
  if (in_array($status, $allowed_statuses)) {
    // 4b. UPDATE DATABASE - Execute prepared statement with bound parameters
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$status, $order_id]);
    $message = 'Order status updated successfully!';
  }
}

// 5. GET FILTER - Parse status filter from URL query parameter
$filter = $_GET['status'] ?? 'all';
$allowed_filters = ['all', 'pending', 'ready_for_pickup', 'completed', 'cancelled'];
if (!in_array($filter, $allowed_filters)) {
  $filter = 'all';
}

// 6. FETCH ORDERS - Query orders with joins to users and stores
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

// 6a. EXECUTE QUERY - Run with or without filter parameter binding
$stmt = $pdo->prepare($query);
if ($filter !== 'all') {
  $stmt->execute([$filter]);
} else {
  $stmt->execute();
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!-- 7. HTML DOCUMENT - Admin dashboard page with filter tabs and orders table -->
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
  <!-- 7a. HEADER - Page title with back link to profile -->
  <div class="admin-header">
    <h1>Orders Dashboard</h1>
    <a href="../profile.php" class="back-link">← Back to Profile</a>
  </div>

  <!-- 7b. SUCCESS MESSAGE - Shown after status update -->
  <?php if ($message): ?>
    <div class="success-message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- 7c. FILTER TABS - Status filter navigation (all, pending, ready, completed, cancelled) -->
  <div class="filter-tabs">
    <a href="?status=all" class="tab <?= $filter === 'all' ? 'active' : '' ?>">All Orders</a>
    <a href="?status=pending" class="tab <?= $filter === 'pending' ? 'active' : '' ?>">⏳ Pending</a>
    <a href="?status=ready_for_pickup" class="tab <?= $filter === 'ready_for_pickup' ? 'active' : '' ?>">✅ Ready</a>
    <a href="?status=completed" class="tab <?= $filter === 'completed' ? 'active' : '' ?>">✔️ Completed</a>
    <a href="?status=cancelled" class="tab <?= $filter === 'cancelled' ? 'active' : '' ?>">❌ Cancelled</a>
  </div>

  <!-- 7d. ORDERS TABLE - Responsive table showing all orders or "no orders" message -->
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
            <!-- 7d-i. ORDER ROWS - Loop through each order with formatted data -->
            <?php foreach ($orders as $order): ?>
              <tr class="order-row">
                <!-- Order ID formatted with leading zeros (6 digits) -->
                <td class="order-id">#<?= str_pad((int)$order['order_id'], 6, '0', STR_PAD_LEFT) ?></td>
                <!-- Customer info (name and email) -->
                <td class="customer">
                  <div class="name"><?= htmlspecialchars($order['name']) ?></div>
                  <div class="email"><?= htmlspecialchars($order['email']) ?></div>
                </td>
                <!-- Amount formatted as USD currency -->
                <td class="amount">$<?= number_format((float)$order['total_price'], 2) ?></td>
                <!-- Status badge with CSS class based on status -->
                <td class="status">
                  <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))) ?>
                  </span>
                </td>
                <!-- Collection info (store with location code or "Delivery") -->
                <td class="collection">
                  <?php if ($order['store_name']): ?>
                    <div class="store"><?= htmlspecialchars($order['store_name']) ?></div>
                    <div class="code"><?= htmlspecialchars($order['location_code']) ?></div>
                  <?php else: ?>
                    <span class="delivery">Delivery</span>
                  <?php endif; ?>
                </td>
                <!-- Date formatted as "Mon DD, YYYY" -->
                <td class="date"><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                <!-- Update button triggers modal (stores order_id and current status) -->
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

  <!-- 7e. STATUS UPDATE MODAL - Modal dialog for updating order status -->
  <div id="statusModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Update Order Status</h2>
      <!-- 7e-i. STATUS FORM - Form with hidden order_id and status select dropdown -->
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

        <!-- 7e-ii. MODAL ACTIONS - Update and Cancel buttons -->
        <div class="modal-actions">
          <button type="submit" class="button primary">Update</button>
          <button type="button" class="button secondary cancel">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</main>

<!-- 8. JAVASCRIPT - Modal interaction and status update functionality -->
<script>
(function() {
  // 8a. CACHE DOM - Store references to modal elements
  const modal = document.getElementById('statusModal');
  const statusForm = document.getElementById('statusForm');
  const modalOrderId = document.getElementById('modalOrderId');
  const modalStatusSelect = document.getElementById('statusSelect');
  const closeBtn = document.querySelector('.close');
  const cancelBtn = document.querySelector('.cancel');

  // 8b. EVENT LISTENERS - Attach click handlers to all "Update" buttons
  document.querySelectorAll('.btn-status').forEach(btn => {
    btn.addEventListener('click', function() {
      // Extract order_id and current status from button data attributes
      const orderId = this.dataset.orderId;
      const currentStatus = this.dataset.status;
      
      // Populate modal form with order_id and pre-select current status
      modalOrderId.value = orderId;
      modalStatusSelect.value = currentStatus;
      // Show modal by adding 'active' class
      modal.classList.add('active');
    });
  });

  // 8c. CLOSE HANDLERS - Close modal when X button clicked
  closeBtn.addEventListener('click', () => modal.classList.remove('active'));
  // Close modal when Cancel button clicked
  cancelBtn.addEventListener('click', () => modal.classList.remove('active'));

  // 8d. OUTSIDE CLICK - Close modal when user clicks outside the modal-content
  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.remove('active');
    }
  });
})();
</script>

</body>
</html>
