<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>

<main class="container">
  <?php
  if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Invalid product ID.</p>";
    include 'includes/footer.php';
    exit;
  }

  $productId = (int) $_GET['id'];

  $stmt = $pdo->prepare("
    SELECT p.*, 
      (SELECT quantity FROM stock WHERE product_id = p.id AND location = 'D1') AS stock_d1,
      (SELECT quantity FROM stock WHERE product_id = p.id AND location = 'D4') AS stock_d4
    FROM products p
    WHERE id = ?
  ");
  $stmt->execute([$productId]);
  $product = $stmt->fetch();

  if (!$product) {
    echo "<p>Product not found.</p>";
    include 'includes/footer.php';
    exit;
  }
  ?>

  <div class="product-detail">
    <div class="product-detail-images">
      <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
      <img src="<?= htmlspecialchars($product['image2']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
    </div>

    <div class="product-detail-info">
      <h1><?= htmlspecialchars($product['name']) ?></h1>
      <p><?= htmlspecialchars($product['description']) ?></p>
      <p><strong><?= number_format($product['price'], 2) ?> $</strong></p>
      <p>Stock D1: <?= $product['stock_d1'] ?? 0 ?> | D4: <?= $product['stock_d4'] ?? 0 ?></p>
      <a href="products.php" class="button primary">← Back to Products</a>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
