<?php
include 'includes/header.php';
include 'includes/db.php';

// --- Récupération des catégories (id + nom) ---
$categoryStmt = $pdo->query("
  SELECT category_id, name
  FROM categories
  ORDER BY name
");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Catégorie sélectionnée (nullable int)
$selectedCategory = isset($_GET['category_id']) && is_numeric($_GET['category_id'])
  ? (int)$_GET['category_id']
  : null;

// Requête produits (schéma actuel: products + categories + inventory/stores)
if ($selectedCategory) {
  $stmt = $pdo->prepare("
    SELECT 
      p.product_id, p.name, p.description, p.price, p.image1, p.image2,
      c.name AS category_name,
      COALESCE(SUM(CASE WHEN s.location_code='D1' THEN i.quantity END),0) AS stock_d1,
      COALESCE(SUM(CASE WHEN s.location_code='D3' THEN i.quantity END),0) AS stock_d3
    FROM products p
    JOIN categories c ON c.category_id = p.category_id
    LEFT JOIN inventory i ON i.product_id = p.product_id
    LEFT JOIN stores s    ON s.store_id = i.store_id
    WHERE p.category_id = ?
    GROUP BY p.product_id, p.name, p.description, p.price, p.image1, p.image2, c.name
    ORDER BY p.product_id
  ");
  $stmt->execute([$selectedCategory]);
} else {
  $stmt = $pdo->query("
    SELECT 
      p.product_id, p.name, p.description, p.price, p.image1, p.image2,
      c.name AS category_name,
      COALESCE(SUM(CASE WHEN s.location_code='D1' THEN i.quantity END),0) AS stock_d1,
      COALESCE(SUM(CASE WHEN s.location_code='D3' THEN i.quantity END),0) AS stock_d3
    FROM products p
    JOIN categories c ON c.category_id = p.category_id
    LEFT JOIN inventory i ON i.product_id = p.product_id
    LEFT JOIN stores s    ON s.store_id = i.store_id
    GROUP BY p.product_id, p.name, p.description, p.price, p.image1, p.image2, c.name
    ORDER BY p.product_id
  ");
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// petit debug discret (optionnel)
echo "<!-- products array size = " . count($products) . " -->";
?>

<main class="container">
  <!-- Titre + Catégories -->
  <div class="products-header">
    <h1>Our Freeze-Dried Products</h1>

    <nav class="category-menu">
      <a href="products.php" class="<?= $selectedCategory === null ? 'active' : '' ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a
          href="products.php?category_id=<?= (int)$cat['category_id'] ?>"
          class="<?= ($selectedCategory === (int)$cat['category_id']) ? 'active' : '' ?>"
        >
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <?php if (empty($products)): ?>
    <p>No products found.</p>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <a href="product.php?id=<?= (int)$product['product_id'] ?>" class="product-card-link">
          <div class="product-card">
            <div class="image-wrapper">
              <img src="<?= htmlspecialchars($product['image1']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img main-img">
              <?php if (!empty($product['image2'])): ?>
                <img src="<?= htmlspecialchars($product['image2']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img hover-img">
              <?php endif; ?>
            </div>
            <h2><?= htmlspecialchars($product['name']) ?></h2>
            <p><?= htmlspecialchars($product['description']) ?></p>
            <p><strong><?= number_format((float)$product['price'], 2) ?> $</strong></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
