<?php
declare(strict_types=1);

include 'includes/db.php';

/**
 * ==============================
 *  AJAX LIVE SEARCH (JSON only)
 *  /products.php?ajax=search&q=...
 * ==============================
 * IMPORTANT : aucune sortie avant ce bloc (pas d'HTML, pas de BOM),
 * sinon fetch(...).json() cassera côté navigateur.
 */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'search') {
  header('Content-Type: application/json; charset=utf-8');
  try {
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $len = function_exists('mb_strlen') ? mb_strlen($q, 'UTF-8') : strlen($q);
    if ($q === '' || $len < 2) {
      echo json_encode([]);
      exit;
    }

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

/**
 * ==============================
 *  PAGE LISTE DES PRODUITS
 * ==============================
 */
include 'includes/header.php';

/* Recherche plein écran (submit du formulaire du header) */
$q     = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$hasQ  = ($q !== '');
$like  = "%{$q}%";

/* Récup categories pour le menu */
$categoryStmt = $pdo->query("
  SELECT category_id, name
  FROM categories
  ORDER BY name
");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

/* Catégorie sélectionnée (nullable int) */
$selectedCategory = isset($_GET['category_id']) && is_numeric($_GET['category_id'])
  ? (int)$_GET['category_id']
  : null;

/* Requête produits (schéma: products + categories + inventory/stores) */
if ($selectedCategory) {
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

// Debug discret si besoin
// echo "<!-- products array size = " . count($products) . " -->";
?>

<main class="container">
  <!-- Titre + Catégories -->
  <div class="products-header">
    <h1>Our Freeze-Dried Products</h1>

    <nav class="category-menu">
      <a
        href="products.php<?= $hasQ ? ('?q=' . urlencode($q)) : '' ?>"
        class="<?= $selectedCategory === null ? 'active' : '' ?>"
      >All</a>
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

  <?php if (empty($products)): ?>
    <p>No products found.</p>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <!-- La fiche détaillée est servie par product.php (singulier) via ?id=... -->
        <a href="product.php?id=<?= (int)$product['product_id'] ?>" class="product-card-link">
          <div class="product-card">
            <div class="image-wrapper">
              <img src="<?= htmlspecialchars($product['image1']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img main-img" loading="lazy">
              <?php if (!empty($product['image2'])): ?>
                <img src="<?= htmlspecialchars($product['image2']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img hover-img" loading="lazy">
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
