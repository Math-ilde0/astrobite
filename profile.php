<?php
// profile.php
// -------------------------------------------------------
// 1) Bootstrap & auth guard (aucune sortie AVANT ceci)
// -------------------------------------------------------
session_start();
require_once 'includes/db.php';

// Redirige vers login si non connecté
if (!isset($_SESSION['user_id'])) {
  $return = urlencode($_SERVER['REQUEST_URI'] ?? '/mywebsite/astrobite/profile.php');
  header('Location: login.php?return=' . $return);
  exit;
}

$userId = (int)$_SESSION['user_id'];

// -------------------------------------------------------
// 2) Handle POST actions (update name / change password)
//    -> PRG pattern (redirect après succès/erreur)
// -------------------------------------------------------
function flash(string $key, string $message): void {
  $_SESSION[$key] = $message;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'update_name') {
    $newName = trim($_POST['name'] ?? '');
    if ($newName === '') {
      flash('flash_error', 'Please provide a valid name.');
    } else {
      $stmt = $pdo->prepare('UPDATE users SET name = ?, updated_at = NOW() WHERE user_id = ?');
      $stmt->execute([$newName, $userId]);
      $_SESSION['user_name'] = $newName; // pour le header
      flash('flash_success', 'Your name has been updated.');
    }
    header('Location: profile.php');
    exit;
  }

  if ($action === 'change_password') {
    $current = $_POST['current'] ?? '';
    $new     = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($new === '' || strlen($new) < 8) {
      flash('flash_error', 'New password must be at least 8 characters.');
    } elseif ($new !== $confirm) {
      flash('flash_error', 'New password and confirmation do not match.');
    } else {
      // Récupère le hash actuel
      $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
      $stmt->execute([$userId]);
      $hash = $stmt->fetchColumn();

      if (!$hash || !password_verify($current, $hash)) {
        flash('flash_error', 'Current password is incorrect.');
      } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?');
        $stmt->execute([$newHash, $userId]);
        flash('flash_success', 'Your password has been changed.');
      }
    }
    header('Location: profile.php');
    exit;
  }
}

// -------------------------------------------------------
// 3) Fetch user + recent orders (top 10) + items per order
// -------------------------------------------------------
$stmt = $pdo->prepare('SELECT user_id, name, email, role, created_at FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$orders = [];
$stmt = $pdo->prepare("
  SELECT 
    o.order_id,
    o.total_price,
    o.status,
    o.created_at,
    s.name AS store_name,
    COUNT(oi.product_id)            AS items_count,
    COALESCE(SUM(oi.quantity), 0)   AS qty_total
  FROM orders o
  LEFT JOIN stores s      ON s.store_id = o.store_id
  LEFT JOIN order_items oi ON oi.order_id = o.order_id
  WHERE o.user_id = ?
  GROUP BY o.order_id, o.total_price, o.status, o.created_at, s.name
  ORDER BY o.created_at DESC
  LIMIT 10
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map des items par commande
$orderItems = [];
if ($orders) {
  $in = implode(',', array_fill(0, count($orders), '?'));
  $ids = array_map(fn($o) => (int)$o['order_id'], $orders);
  $sqlItems = "
    SELECT 
      oi.order_id, oi.product_id, oi.quantity, oi.price_at_purchase,
      p.name AS product_name, p.image1
    FROM order_items oi
    JOIN products p ON p.product_id = oi.product_id
    WHERE oi.order_id IN ($in)
    ORDER BY oi.order_id, p.name
  ";
  $stmt = $pdo->prepare($sqlItems);
  $stmt->execute($ids);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $orderItems[(int)$row['order_id']][] = $row;
  }
}

// -------------------------------------------------------
// 4) SEO overrides for this page (noindex profile page)
// -------------------------------------------------------
$pageTitle       = 'My Profile — AstroBite';
$pageDescription = 'Manage your AstroBite account: profile details, security, and recent Click & Collect orders.';
$robots          = 'noindex,nofollow'; // profil : pas d’indexation
require_once 'includes/header.php';
?>

<main class="container">
  <!-- Flash messages -->
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="success-message" style="margin:12px 0; padding:10px; border-radius:8px; background:#0f3a2f; color:#c7f5d9;">
      <?= htmlspecialchars($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="error-message" style="margin:12px 0; padding:10px; border-radius:8px; background:#3a0f12; color:#f5c7cc;">
      <?= htmlspecialchars($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <h1>My Profile</h1>

  <!-- Account Overview -->
  <section class="card" style="margin-top:12px;">
    <h2>Account</h2>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['name'] ?? '') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '') ?></p>
    <p><strong>Member since:</strong> <?= htmlspecialchars(substr((string)($user['created_at'] ?? ''), 0, 10)) ?></p>
    <p><strong>Role:</strong> <?= htmlspecialchars($user['role'] ?? 'user') ?></p>
    <p><a href="logout.php">Log out</a></p>
  </section>

  <!-- Quick actions -->
  <section class="card" style="margin-top:16px;">
    <h2>Quick actions</h2>
    <div style="display:grid; grid-template-columns:1fr; gap:16px;">
      <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
        <!-- Admin Dashboard Link -->
        <div style="background: linear-gradient(135deg, rgba(0, 216, 78, 0.1), rgba(0, 255, 106, 0.1)); border: 1px solid rgba(0, 255, 106, 0.3); padding: 16px; border-radius: 8px;">
          <p style="margin: 0 0 12px 0; color: #00ff6a; font-weight: 600;">⚙️ Admin Tools</p>
          <a href="admin/orders.php" style="display: inline-block; padding: 10px 18px; background: linear-gradient(135deg, #00d84e, #00ff6a); color: #000; text-decoration: none; border-radius: 6px; font-weight: 600; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">📦 Manage Orders</a>
        </div>
      <?php endif; ?>
      
      <!-- Update display name -->
      <form action="profile.php" method="post" class="inline-form" autocomplete="off">
        <input type="hidden" name="action" value="update_name">
        <label for="name"><strong>Edit name</strong></label>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
          <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required style="min-width:260px;">
          <button type="submit">Save</button>
        </div>
      </form>

      <!-- Change password -->
      <form action="profile.php" method="post" autocomplete="off">
        <input type="hidden" name="action" value="change_password">
        <label><strong>Change password</strong></label>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:8px;">
          <input type="password" name="current" placeholder="Current password" required>
          <input type="password" name="new" placeholder="New password (min 8)" minlength="8" required>
          <input type="password" name="confirm" placeholder="Confirm new password" minlength="8" required>
        </div>
        <button type="submit" style="margin-top:8px;">Update password</button>
      </form>
    </div>
  </section>

  <!-- Order History -->
  <section class="card" style="margin-top:16px;">
    <h2>Recent orders</h2>
    <?php if (!$orders): ?>
      <p>You don’t have any orders yet.</p>
      <p><a href="products.php" class="btn">Start shopping</a></p>
    <?php else: ?>
      <ul class="order-list" style="display:grid; gap:14px; list-style:none; padding-left:0;">
        <?php foreach ($orders as $o): ?>
          <li class="order-card" style="border:1px solid rgba(255,255,255,.12); border-radius:10px; padding:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
              <div>
                <strong>Order #<?= (int)$o['order_id'] ?></strong>
                <div style="opacity:.85; font-size:14px;">
                  <?= htmlspecialchars(substr($o['created_at'], 0, 16)) ?> • 
                  <?= htmlspecialchars($o['store_name'] ?? '—') ?> •
                  <?= (int)$o['qty_total'] ?> items (<?= (int)$o['items_count'] ?> lines)
                </div>
              </div>
              <div>
                <span style="padding:4px 8px; border-radius:999px; background:
                  <?= $o['status']==='completed' ? '#0f3a2f' : ($o['status']==='ready_for_pickup' ? '#263a0f' : ($o['status']==='pending' ? '#0f2a3a' : '#3a0f12')); ?>;
                  ">
                  <?= htmlspecialchars($o['status']) ?>
                </span>
                <span style="margin-left:10px; font-weight:700;">
                  $<?= number_format((float)$o['total_price'], 2) ?>
                </span>
              </div>
            </div>

            <?php if (!empty($orderItems[(int)$o['order_id']])): ?>
              <div class="order-items" style="margin-top:10px; display:grid; gap:10px;">
                <?php foreach ($orderItems[(int)$o['order_id']] as $it): ?>
                  <div style="display:grid; grid-template-columns:56px 1fr auto; gap:10px; align-items:center;">
                    <img src="<?= htmlspecialchars($it['image1'] ?? '') ?>" alt="" style="width:56px; height:56px; object-fit:cover; border-radius:8px; background:#142b46;">
                  <img src="<?= htmlspecialchars($it['image1'] ?? '') ?>" alt="" style="width:56px; height:56px; object-fit:cover; border-radius:8px; background:#142b46;" loading="lazy">
                    <div>
                      <div style="font-weight:600;"><?= htmlspecialchars($it['product_name']) ?></div>
                      <div style="opacity:.8; font-size:13px;">Qty: <?= (int)$it['quantity'] ?></div>
                    </div>
                    <div style="font-variant-numeric: tabular-nums;">
                      $<?= number_format((float)$it['price_at_purchase'], 2) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <!-- SEO microdata (Person) - utile pour E-E-A-T si un jour public, mais on reste noindex -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Person",
    "name": <?= json_encode($user['name'] ?? 'AstroBite User') ?>,
    "email": <?= json_encode($user['email'] ?? '') ?>,
    "memberOf": {
      "@type": "Organization",
      "name": "AstroBite"
    }
  }
  </script>
</main>

<?php require_once 'includes/footer.php'; ?>
