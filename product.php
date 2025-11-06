<?php
include 'includes/header.php';
include 'includes/db.php';

// --- Filters (optional) ---
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;

// Fetch categories for sidebar/filter
$cats = $pdo->query("
  SELECT c.category_id, c.name
  FROM categories c
  ORDER BY c.name
")->fetchAll(PDO::FETCH_ASSOC);

// Build product query
$sql = "
  SELECT 
    p.product_id, p.name, p.description, p.price, p.image1, p.image2,
    c.name AS category_name
  FROM products p
  JOIN categories c ON c.category_id = p.category_id
  WHERE 1=1
";
$params = [];

if ($q !== '') {
  $sql .= " AND p.name LIKE CONCAT('%', ?, '%') ";
  $params[] = $q;
}

if ($categoryId !== null) {
  $sql .= " AND p.category_id = ? ";
  $params[] = $categoryId;
}

$sql .= " ORDER BY p.product_id ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main class="container">
  <h1>Products</h1>

  <form method="get" class="filters" style="margin-bottom:1rem; display:flex; gap:.5rem; align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search products…" />
    <select name="category_id">
      <option value="">All categories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['category_id'] ?>" <?= $categoryId===$c['category_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button class="button">Filter</button>
  </form>

  <?php if (empty($products)): ?>
    <p>No products found.</p>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $p): ?>
        <a class="product-card" href="product.php?id=<?= (int)$p['product_id'] ?>">
          <div class="image-wrapper" style="position:relative; aspect-ratio:1/1; overflow:hidden;">
            <img src="<?= htmlspecialchars($p['image1']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:100%; height:100%; object-fit:cover;" />
          </div>
          <div class="product-info" style="padding:.5rem 0;">
            <div style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></div>
            <div style="font-size:.9rem; color:#666;"><?= htmlspecialchars($p['category_name']) ?></div>
            <div style="margin-top:.25rem; font-weight:700;"><?= number_format((float)$p['price'], 2) ?> $</div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
