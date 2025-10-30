<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>

<main class="container">
  <?php
  // Récupération des catégories
  $categoryStmt = $pdo->query("SELECT DISTINCT category FROM products");
  $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

  // Catégorie sélectionnée ?
  $selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';

  // Requête produit avec ou sans filtre
  if ($selectedCategory) {
    $stmt = $pdo->prepare("
      SELECT p.*, 
        (SELECT quantity FROM stock WHERE product_id = p.id AND location = 'D1') AS stock_d1,
        (SELECT quantity FROM stock WHERE product_id = p.id AND location = 'D4') AS stock_d4
      FROM products p
      WHERE category = ?
    ");
    $stmt->execute([$selectedCategory]);
  } else {
    $stmt = $pdo->query("
      SELECT p.*, 
        (SELECT quantity FROM stock WHERE product_id = p.id AND location = 'D1') AS stock_d1,
        (SELECT quantity FROM stock WHERE product_id = p.id AND location = 'D4') AS stock_d4
      FROM products p
    ");
  }

  $products = $stmt->fetchAll();
  ?>

  <!-- Titre + Catégories -->
  <div class="products-header">
    <h1>Our Freeze-Dried Products</h1>

    <nav class="category-menu">
      <a href="products.php" class="<?= $selectedCategory === '' ? 'active' : '' ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a href="products.php?category=<?= urlencode($cat) ?>" class="<?= $selectedCategory === $cat ? 'active' : '' ?>">
          <?= htmlspecialchars($cat) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <div class="product-grid">
    <?php foreach ($products as $product): ?>
      <a href="product.php?id=<?= $product['id'] ?>" class="product-card-link">
  <div class="product-card">
          <div class="image-wrapper">
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img main-img">
            <img src="<?= htmlspecialchars($product['image2']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img hover-img">
          </div>
          <h2><?= htmlspecialchars($product['name']) ?></h2>
          <p><?= htmlspecialchars($product['description']) ?></p>
          <p><strong><?= number_format($product['price'], 2) ?> $</strong></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
