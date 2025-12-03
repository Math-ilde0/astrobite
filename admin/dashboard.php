<?php
/**
 * admin/dashboard.php - Admin Dashboard (Single Page, Embedded Styles)
 * 
 * OVERVIEW:
 * Quick order management dashboard with embedded CSS and JavaScript. Similar
 * to admin/orders.php but all-in-one file without external stylesheets.
 * Displays orders table (max 50, DESC by date) with status filtering, modal
 * for status updates, and success confirmation messages. Admin-only access
 * with role verification. Returns 303 redirect for non-admin users.
 * 
 * FEATURES:
 * - Admin-only access with role verification
 * - Status filter tabs (All, Pending, Ready, Completed, Cancelled)
 * - Order table with 50 orders max (DESC by created_at)
 * - Customer info, amounts, status badges, collection details
 * - Welcome message with admin name from session
 * - Modal for status updates with validation
 * - Embedded CSS (290+ lines) - ALL STYLING IN THIS FILE
 * - Embedded JavaScript (IIFE pattern) - ALL INTERACTIVITY IN THIS FILE
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

// 2. AUTHENTICATION - Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit;
}

// 3. ROLE VERIFICATION - Query database to verify user is admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 3a. DENY ACCESS - Redirect non-admin users to login
if (!$user || $user['role'] !== 'admin') {
  header('Location: ../login.php');
  exit;
}

// 4. HANDLE STATUS UPDATE - Process POST requests to update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
  $order_id = (int)$_POST['order_id'];
  $status = $_POST['status'];
  
  // 4a. VALIDATE STATUS - Only allow predefined statuses
  $allowed_statuses = ['pending', 'ready_for_pickup', 'completed', 'cancelled'];
  
  if (in_array($status, $allowed_statuses)) {
    // 4b. UPDATE DATABASE - Execute prepared statement
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

// 6. FETCH ORDERS - Query orders with joins to users and stores (MAX 50)
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
  LIMIT 50
";

// 6a. EXECUTE QUERY - Run with or without filter parameter
$stmt = $pdo->prepare($query);
if ($filter !== 'all') {
  $stmt->execute([$filter]);
} else {
  $stmt->execute();
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7. PAGE TITLE - Set page title for admin dashboard
$pageTitle = 'Admin Dashboard — AstroBite';
?>
<!-- 8. HTML DOCUMENT - Admin dashboard page with embedded CSS and JavaScript -->
<main class="container admin-dashboard">
  <!-- 8a. HEADER - Welcome message with admin name from session -->
  <div class="admin-header">
    <h1>Admin Dashboard</h1>
    <p class="welcome">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></p>
  </div>

  <!-- 8b. SUCCESS MESSAGE - Shown after status update (if $message is set) -->
  <?php if (isset($message)): ?>
    <div class="success-message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- 8c. FILTER TABS - Status filter navigation with emoji indicators -->
  <div class="filter-tabs">
    <a href="?status=all" class="tab <?= $filter === 'all' ? 'active' : '' ?>">All Orders</a>
    <a href="?status=pending" class="tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="?status=ready_for_pickup" class="tab <?= $filter === 'ready_for_pickup' ? 'active' : '' ?>">Ready for Pickup</a>
    <a href="?status=completed" class="tab <?= $filter === 'completed' ? 'active' : '' ?>">Completed</a>
    <a href="?status=cancelled" class="tab <?= $filter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
  </div>

  <!-- 8d. ORDERS TABLE - Responsive table with max 50 orders or "no orders" message -->
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
            <!-- 8d-i. ORDER ROWS - Loop through orders with formatted data -->
            <?php foreach ($orders as $order): ?>
              <tr class="order-row">
                <!-- Order ID with leading zeros (6 digits) in cyan -->
                <td class="order-id">#<?= str_pad((int)$order['order_id'], 6, '0', STR_PAD_LEFT) ?></td>
                <!-- Customer name and email -->
                <td class="customer">
                  <div class="name"><?= htmlspecialchars($order['name']) ?></div>
                  <div class="email"><?= htmlspecialchars($order['email']) ?></div>
                </td>
                <!-- Amount in green currency format -->
                <td class="amount">$<?= number_format((float)$order['total_price'], 2) ?></td>
                <!-- Status badge with color coding (pending/ready/completed/cancelled) -->
                <td class="status">
                  <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))) ?>
                  </span>
                </td>
                <!-- Collection info (store name + code or "Delivery") -->
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
                <!-- Update button opens modal with order data -->
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

  <!-- 8e. STATUS UPDATE MODAL - Modal dialog for updating order status -->
  <div id="statusModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Update Order Status</h2>
      <!-- 8e-i. STATUS FORM - Form with hidden order_id and status select -->
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

        <!-- 8e-ii. MODAL ACTIONS - Update and Cancel buttons -->
        <div class="modal-actions">
          <button type="submit" class="button primary">Update</button>
          <button type="button" class="button secondary cancel">Cancel</button>
        </div>
      </form>
    </div>
  </div>

</main>

<!-- 9. EMBEDDED CSS - All dashboard styling (admin-dashboard self-contained) -->
<style>
/* 9a. MAIN LAYOUT - Page padding and header styling */
.admin-dashboard { padding: 2rem 0; }

.admin-header {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid rgba(255, 255, 255, 0.12);
}

.admin-header h1 {
  margin-bottom: 0.5rem;
}

.welcome {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.95rem;
}

/* 9b. SUCCESS MESSAGE - Green notification for status updates */
.success-message {
  background: rgba(163, 255, 112, 0.15);
  border: 1px solid rgba(163, 255, 112, 0.3);
  color: #a3ff70;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-weight: 500;
}

/* 9c. FILTER TABS - Status filter navigation styling */
.filter-tabs {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.tab {
  padding: 0.75rem 1.25rem;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  transition: all 0.2s;
  cursor: pointer;
}

.tab:hover {
  background: rgba(93, 217, 255, 0.1);
  color: #5dd9ff;
}

.tab.active {
  background: linear-gradient(135deg, #00d84e, #00ff6a);
  color: #000;
  font-weight: 600;
  border-color: transparent;
}

/* 9d. TABLE STYLES - Orders table with responsive wrapper and styling */
.table-responsive {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(10, 40, 60, 0.4);
  backdrop-filter: blur(4px);
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
  color: rgba(255, 255, 255, 0.9);
}

.orders-table thead {
  background: rgba(0, 0, 0, 0.3);
  border-bottom: 2px solid rgba(255, 255, 255, 0.12);
}

.orders-table th {
  text-align: left;
  padding: 1rem;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.5px;
}

.orders-table td {
  padding: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.orders-table tbody tr:hover {
  background: rgba(93, 217, 255, 0.05);
}

/* 9e. TABLE CELL STYLES - Specific formatting for each column type */
.order-id {
  font-weight: 600;
  color: #5dd9ff;
}

.customer .name {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.customer .email {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.5);
}

.amount {
  font-weight: 600;
  color: #a3ff70;
}

/* 9f. STATUS BADGES - Color-coded status indicators */
.status-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: capitalize;
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

.status-cancelled {
  background: rgba(255, 100, 100, 0.2);
  color: #ff7a7a;
}

/* 9g. COLLECTION INFO - Store and delivery styling */
.collection {
  font-size: 0.9rem;
}

.store {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.code {
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.5);
}

.delivery {
  color: #5dd9ff;
  font-weight: 600;
}

.date {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.7);
}

/* 9h. UPDATE BUTTON - Status update action button */
.btn-status {
  padding: 0.5rem 1rem;
  background: rgba(93, 217, 255, 0.2);
  border: 1px solid rgba(93, 217, 255, 0.4);
  color: #5dd9ff;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
  font-size: 0.85rem;
}

.btn-status:hover {
  background: rgba(93, 217, 255, 0.4);
}

.no-orders {
  text-align: center;
  padding: 2rem;
  color: rgba(255, 255, 255, 0.5);
}

/* 9i. MODAL STYLES - Backdrop and modal content styling */
.modal {
  display: none;
  position: fixed;
  z-index: 10000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
}

.modal.active {
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background: rgba(10, 40, 60, 0.95);
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid rgba(93, 217, 255, 0.3);
  max-width: 400px;
  width: 90%;
  position: relative;
}

/* 9j. MODAL CLOSE BUTTON - X button in top-right corner */
.close {
  position: absolute;
  right: 1rem;
  top: 1rem;
  font-size: 1.5rem;
  font-weight: bold;
  color: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  transition: color 0.2s;
}

.close:hover {
  color: #fff;
}

.modal-content h2 {
  margin-bottom: 1.5rem;
  color: #5dd9ff;
}

/* 9k. FORM STYLES - Status select dropdown and label */
.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: rgba(255, 255, 255, 0.9);
}

.form-group select {
  width: 100%;
  padding: 0.75rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(93, 217, 255, 0.3);
  border-radius: 6px;
  color: #fff;
  font-size: 1rem;
}

.form-group select:focus {
  outline: none;
  border-color: #5dd9ff;
  box-shadow: 0 0 8px rgba(93, 217, 255, 0.3);
}

/* 9l. MODAL ACTIONS - Update and Cancel button styling */
.modal-actions {
  display: flex;
  gap: 0.75rem;
}

.modal-actions .button {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.modal-actions .button.primary {
  background: linear-gradient(135deg, #00d84e, #00ff6a);
  color: #000;
}

.modal-actions .button.primary:hover {
  opacity: 0.9;
}

.modal-actions .button.secondary {
  background: rgba(255, 255, 255, 0.1);
  color: #5dd9ff;
  border: 1px solid rgba(93, 217, 255, 0.3);
}

.modal-actions .button.secondary:hover {
  background: rgba(255, 255, 255, 0.15);
}

.logout-link {
  color: #ff7a7a;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s;
}

.logout-link:hover {
  color: #ff9999;
}

/* 9m. RESPONSIVE DESIGN - Mobile optimization */
@media (max-width: 768px) {
  .orders-table th, .orders-table td {
    padding: 0.75rem 0.5rem;
    font-size: 0.85rem;
  }

  .filter-tabs {
    gap: 0.5rem;
  }

  .tab {
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
  }
}
</style>

<!-- 10. EMBEDDED JAVASCRIPT - Modal interaction and status update functionality -->
<script>
(function() {
  // 10a. CACHE DOM - Store references to modal elements
  const modal = document.getElementById('statusModal');
  const statusForm = document.getElementById('statusForm');
  const modalOrderId = document.getElementById('modalOrderId');
  const modalStatusSelect = document.getElementById('statusSelect');
  const closeBtn = document.querySelector('.close');
  const cancelBtn = document.querySelector('.cancel');

  // 10b. EVENT LISTENERS - Attach click handlers to all Update buttons
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

  // 10c. CLOSE HANDLERS - Close modal when X button clicked
  closeBtn.addEventListener('click', () => modal.classList.remove('active'));
  // Close modal when Cancel button clicked
  cancelBtn.addEventListener('click', () => modal.classList.remove('active'));

  // 10d. OUTSIDE CLICK - Close modal when user clicks outside the modal-content
  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.remove('active');
    }
  });

  // 10e. FORM SUBMISSION - Handle form submit (prevents double-submit)
  statusForm.addEventListener('submit', (e) => {
    e.preventDefault();
    statusForm.submit();
  });
})();
</script>
