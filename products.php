<?php
/**
 * products.php - Product Listing & Search Page
 * 
 * Purpose: Display all or filtered products with live search, category filtering, and inventory display
 * 
 * Features:
 * - AJAX live search (real-time product search as user types)
 * - Category filtering with multiple category support
 * - Inventory display from multiple store locations (D1, D3)
 * - Dual product image handling (main and hover images)
 * - Responsive product grid layout
 * 
 * Dependencies: db.php (PDO), header.php, footer.php
 * Endpoints:
 * - GET /products.php: Display all products or filtered by category/search
 * - GET /products.php?ajax=search&q=QUERY: Return JSON search results
 */

declare(strict_types=1);

include 'includes/db.php';

// -------------------------------------------------------
// 1) AJAX Live Search Handler (JSON endpoint only)
//    Route: /products.php?ajax=search&q=QUERY
// -------------------------------------------------------
// IMPORTANT: No HTML output before this block (no output buffering or BOM)
// Otherwise fetch(...).json() will fail in browser
if (isset($_GET['ajax']) && $_GET['ajax'] === 'search') {
  header('Content-Type: application/json; charset=utf-8');
  try {
    // Get and validate search query
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $len = function_exists('mb_strlen') ? mb_strlen($q, 'UTF-8') : strlen($q);
    
    // Minimum 2 characters required for search
    if ($q === '' || $len < 2) {
      echo json_encode([]);
      exit;
    }

    // Query products matching search term (limit 8 results)
    $stmt = $pdo->prepare("
      SELECT 
        p.product_id,
        p.name,
        p.image1,
        p.price,
        c.name AS category_name
      FROM products p
      JOIN categories c ON c.category_id = p.category_id
      WHERE p.name LIKE CONCAT('%', ?, '%')
      ORDER BY p.name
      LIMIT 8
    ");
    $stmt->execute([$q]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return results as JSON
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
      'error'   => 'server',
      'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
  }
  exit;
}

// -------------------------------------------------------
// 2) Page Load - Product Listing & Filtering
// -------------------------------------------------------
include 'includes/header.php';

// Get full-text search query from header form
$q     = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$hasQ  = ($q !== '');
$like  = "%{$q}%";

// Fetch all categories for filter menu
$categoryStmt = $pdo->query("
  SELECT category_id, name
  FROM categories
  ORDER BY name
");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected category filter (optional)
$selectedCategory = isset($_GET['category_id']) && is_numeric($_GET['category_id'])
  ? (int)$_GET['category_id']
  : null;

// -------------------------------------------------------
// 3) Query Products with Inventory & Category Data
//    Schema: products + categories + inventory by location
// -------------------------------------------------------
if ($selectedCategory) {
  // Query filtered by selected category
  $sql = "
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
    " . ($hasQ ? " AND p.name LIKE ? " : "") . "
    GROUP BY p.product_id, p.name, p.description, p.price, p.image1, p.image2, c.name
    ORDER BY p.product_id
  ";
  $params = [$selectedCategory];
  if ($hasQ) { $params[] = $like; }
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
} else {
  // Query all products (no category filter)
  $sql = "
    SELECT 
      p.product_id, p.name, p.description, p.price, p.image1, p.image2,
      c.name AS category_name,
      COALESCE(SUM(CASE WHEN s.location_code='D1' THEN i.quantity END),0) AS stock_d1,
      COALESCE(SUM(CASE WHEN s.location_code='D3' THEN i.quantity END),0) AS stock_d3
    FROM products p
    JOIN categories c ON c.category_id = p.category_id
    LEFT JOIN inventory i ON i.product_id = p.product_id
    LEFT JOIN stores s    ON s.store_id = i.store_id
    WHERE 1=1
    " . ($hasQ ? " AND p.name LIKE ? " : "") . "
    GROUP BY p.product_id, p.name, p.description, p.price, p.image1, p.image2, c.name
    ORDER BY p.product_id
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($hasQ ? [$like] : []);
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container">
  <!-- ========== PRODUCTS HEADER WITH TITLE & CATEGORY FILTERS ========== -->
  <!-- Navigation menu for category filtering with active state highlighting -->
  <div class="products-header">
    <h1>Our Freeze-Dried Products</h1>

    <nav class="category-menu">
      <!-- "All Products" filter link -->
      <a
        href="products.php<?= $hasQ ? ('?q=' . urlencode($q)) : '' ?>"
        class="<?= $selectedCategory === null ? 'active' : '' ?>"
      >All</a>
      
      <!-- Category filter links (dynamically generated from database) -->
      <?php foreach ($categories as $cat): ?>
        <a
          href="products.php?category_id=<?= (int)$cat['category_id'] ?><?= $hasQ ? ('&q=' . urlencode($q)) : '' ?>"
          class="<?= ($selectedCategory === (int)$cat['category_id']) ? 'active' : '' ?>"
        >
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <!-- ========== PRODUCTS GRID OR EMPTY STATE ========== -->
  <!-- Display message if no products match filter criteria -->
  <?php if (empty($products)): ?>
    <p>No products found.</p>
  <?php else: ?>
    <!-- Product grid - link to individual product detail page -->
    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <!-- Individual product card - clickable link to product detail page (product.php?id=...) -->
        <a href="product.php?id=<?= (int)$product['product_id'] ?>" class="product-card-link">
          <div class="product-card">
            <!-- Image container with main and hover image support -->
            <div class="image-wrapper">
              <!-- Main product image (displayed by default) -->
              <img src="<?= htmlspecialchars($product['image1']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img main-img" loading="lazy">
              <!-- Hover image (shown on mouseover if available) -->
              <?php if (!empty($product['image2'])): ?>
                <img src="<?= htmlspecialchars($product['image2']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img hover-img" loading="lazy">
              <?php endif; ?>
            </div>
            <!-- Product information: name, description, price -->
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
