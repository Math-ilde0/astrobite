<?php
declare(strict_types=1);
require_once 'includes/db.php';

/**
 * product.php — single product page with stock per store
 */

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  http_response_code(400);
  include 'includes/header.php';
  echo "<main class='container'><p>Invalid product ID.</p></main>";
  include 'includes/footer.php';
  exit;
}

$productId = (int) $_GET['id'];

/* Fetch product + category */
$stmt = $pdo->prepare("
  SELECT 
    p.product_id, p.name, p.description, p.price, p.image1, p.image2,
    p.meta_title, p.meta_description, p.image1_alt, p.image2_alt,
    c.name AS category_name
  FROM products p
  JOIN categories c ON c.category_id = p.category_id
  WHERE p.product_id = ?
  LIMIT 1
");
$stmt->execute([$productId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
  http_response_code(404);
  include 'includes/header.php';
  echo "<main class='container'><p>Product not found.</p></main>";
  include 'includes/footer.php';
  exit;
}

/* ---------- Per-page SEO variables (read by header.php) ---------- */
$pageTitle       = !empty($item['meta_title']) ? $item['meta_title'] : ($item['name'].' | AstroBite');
$pageDescription = !empty($item['meta_description'])
  ? $item['meta_description']
  : (function($s){ $s = trim(strip_tags((string)$s)); if (function_exists('mb_substr')) return mb_substr($s,0,155,'UTF-8'); return substr($s,0,155);} )($item['description'] ?? '');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$imgAbs = $item['image1'] ?? '';
if ($imgAbs) {
  $imgAbs = (strpos($imgAbs, '/') === 0)
    ? ($scheme.'://'.$host.$imgAbs)
    : ($scheme.'://'.$host.($base ? $base.'/' : '/').$imgAbs);
}
$pageImage    = $imgAbs ?: ($scheme.'://'.$host.($base ? $base : '').'/assets/images/logo_social.png');
$canonicalUrl = $scheme.'://'.$host.($base ? $base : '').'/product.php?id='.$productId;
$pageType     = 'product';

/* Fetch per-store stock for this product (D1, D3, etc.) */
$storeStock = [];
$stockStmt = $pdo->prepare("
  SELECT s.store_id, s.name, s.location_code, s.address, i.quantity
  FROM stores s
  LEFT JOIN inventory i
    ON i.store_id = s.store_id
   AND i.product_id = ?
  ORDER BY s.location_code ASC, s.name ASC
");
$stockStmt->execute([$productId]);
$storeStock = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

/* Include header AFTER setting SEO vars */
include 'includes/header.php';

/* Build an images array for the slider (single <img> swapped by JS) */
$images = [];
if (!empty($item['image1'])) {
  $images[] = ['src' => $item['image1'], 'alt' => $item['image1_alt'] ?? $item['name']];
}
if (!empty($item['image2'])) {
  $images[] = ['src' => $item['image2'], 'alt' => $item['image2_alt'] ?? ($item['name'].' alternate view')];
}
if (empty($images)) {
  $images[] = ['src' => ($base ? $base : '').'/assets/images/placeholder.png', 'alt' => 'Product image placeholder'];
}

/* Helper for availability badge */
function availability_badge(?int $qty): string {
  if ($qty === null) return '<span class="badge badge-unknown">Unknown</span>';
  if ($qty <= 0)     return '<span class="badge badge-out">Out of stock</span>';
  if ($qty <= 3)     return '<span class="badge badge-low">Low stock</span>';
  return '<span class="badge badge-in">In stock</span>';
}
?>

<main class="container product-detail" id="main-content" itemscope itemtype="https://schema.org/Product">
  <!-- Breadcrumb -->
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= htmlspecialchars(($base ?: '').'/index.php') ?>">Home</a> ›
    <a href="<?= htmlspecialchars(($base ?: '').'/products.php') ?>">Products</a> ›
    <span aria-current="page"><?= htmlspecialchars($item['name']) ?></span>
  </nav>

  <div class="product-layout">
    <!-- Image (one at a time) -->
    <div class="product-detail-images" role="region" aria-label="Product images">
      <div
        class="image-wrapper"
        tabindex="0"
        data-images='<?= htmlspecialchars(json_encode($images), ENT_QUOTES) ?>'
      >
        <img id="pd-img"
             src="<?= htmlspecialchars($images[0]['src']) ?>"
             alt="<?= htmlspecialchars($images[0]['alt']) ?>"
             itemprop="image" loading="lazy" />

        <?php if (count($images) > 1): ?>
          <button type="button" class="switch-arrow left"  aria-label="Previous image" data-dir="-1">‹</button>
          <button type="button" class="switch-arrow right" aria-label="Next image"     data-dir="1">›</button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Info -->
    <div class="product-detail-info" role="region" aria-labelledby="pd-title">
      <h1 id="pd-title" itemprop="name"><?= htmlspecialchars($item['name']) ?></h1>

      <p class="product-detail__price">
        <span class="sr-only">Price:</span>
        <data value="<?= number_format((float)$item['price'], 2, '.', '') ?>" itemprop="price">
          <?= number_format((float)$item['price'], 2) ?> $
        </data>
        <meta itemprop="priceCurrency" content="USD" />
      </p>

      <?php if (!empty($item['description'])): ?>
        <p itemprop="description"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
      <?php endif; ?>

      <p>Category: <span itemprop="category"><?= htmlspecialchars($item['category_name']) ?></span></p>

      <!-- Store stock (Click & Collect visibility) -->
      <section class="store-stock" aria-labelledby="stock-title">
        <h2 id="stock-title">Availability in stores</h2>
        <?php if (!$storeStock): ?>
          <p>No store data.</p>
        <?php else: ?>
          <ul class="store-stock-list">
            <?php foreach ($storeStock as $row): 
              $qty = isset($row['quantity']) ? (int)$row['quantity'] : null;
            ?>
              <li class="store-stock-item">
                <div class="store-row">
                  <div class="store-meta">
                    <strong><?= htmlspecialchars($row['name']) ?></strong>
                    <span class="muted"> • <?= htmlspecialchars($row['location_code']) ?></span>
                    <div class="muted small"><?= htmlspecialchars($row['address']) ?></div>
                  </div>
                  <div class="store-qty">
                    <?= availability_badge($qty) ?>
                    <span class="qty-num<?= ($qty !== null && $qty <= 0) ? ' dim' : '' ?>">
                      <?= ($qty === null) ? '—' : (int)$qty ?> pcs
                    </span>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>

      <div class="actions">
        <label class="qty-label" for="qty">Quantity</label>
        <input id="qty" type="number" min="1" value="1" inputmode="numeric" aria-label="Quantity" class="qty-input" />
        <button class="button primary" type="button" aria-label="Add to cart">Add to cart</button>
        <a href="products.php" class="button secondary">Back to products</a>
      </div>

      <!-- screen-reader announcements -->
      <div class="sr-live" aria-live="polite" aria-atomic="true"></div>
    </div>
  </div>

  <!-- Product structured data for richer snippets -->
  <script type="application/ld+json">
  <?= json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'Product',
      'name' => $item['name'],
      'description' => strip_tags($item['description'] ?? ''),
      'image' => array_values(array_filter([$item['image1'] ?? null, $item['image2'] ?? null])),
      'category' => $item['category_name'],
      'offers' => [
        '@type' => 'Offer',
        'price' => number_format((float)$item['price'], 2, '.', ''),
        'priceCurrency' => 'USD',
        'availability' => 'https://schema.org/InStock',
        'url' => $canonicalUrl
      ]
  ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?>
  </script>
</main>

<style>
/* --- Product page layout (bigger image) --- */
.product-layout{
  display:grid;
  grid-template-columns: 1.35fr 1fr; /* give more room to the image */
  gap:28px;
}

/* Center image area and let it grow */
product-detail-images {
  display: flex;
  justify-content: center;
  align-items: flex-start;

  /* NEW: shift slightly left */
  transform: translateX(-80px);   /* adjust -20px to -80px depending on taste */
}

/* Make the image large but responsive */
.product-detail-images .image-wrapper{
  position:relative;
  width: clamp(380px, 46vw, 760px); /* MIN, PREFERRED, MAX */
  max-width:100%;
}

/* The image itself */
#pd-img{
  width:100%;
  height:auto;
  display:block;
  border-radius:16px;
  box-shadow: 0 18px 60px rgba(0,0,0,.35);
  object-fit: contain;
}

/* Arrows: larger, vertically centered, outside a bit */
.switch-arrow{
  position:absolute;
  top:50%;
  transform: translateY(-50%);
  width:44px;height:44px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.18);
  color:#fff;
  font-size:26px;
  line-height:42px;
  text-align:center;
  cursor:pointer;
  user-select:none;
}
.switch-arrow.left{  left:-16px;  }
.switch-arrow.right{ right:-16px; }


/* Stock list styling (unchanged, included for completeness) */
.store-stock { margin:16px 0; }
.store-stock-list { list-style:none; padding-left:0; display:grid; gap:10px; }
.store-stock-item { border:1px solid rgba(255,255,255,.12); border-radius:10px; padding:10px; backdrop-filter: blur(4px); }
.store-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.store-meta .muted { opacity:.8; margin-left:6px; }
.small{ font-size:12px; }
.store-qty{ display:flex; align-items:center; gap:10px; }
.qty-num.dim{ opacity:.6; }
.badge{ padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; display:inline-block; }
.badge-in{ background:#0f3a2f; color:#c7f5d9; }
.badge-low{ background:#3a3510; color:#fff1b8; }
.badge-out{ background:#3a0f12; color:#f5c7cc; }
.badge-unknown{ background:#0f2a3a; color:#c7e5f5; }

/* Responsive: stack on mobile and keep a good image width */
@media (max-width: 900px){
  .product-layout{ grid-template-columns:1fr; }
  .product-detail-images .image-wrapper{ width: clamp(320px, 88vw, 680px); }
  .switch-arrow.left{  left:6px; }
  .switch-arrow.right{ right:6px; }
}
</style>


<script>
/* One-image slider: swap src/alt, arrows + keyboard */
(function () {
  const wrap = document.querySelector('.product-detail-images .image-wrapper');
  if (!wrap) return;

  let imgs = [];
  try { imgs = JSON.parse(wrap.getAttribute('data-images')) || []; } catch(e){ imgs = []; }
  if (!imgs.length) return;

  const imgEl = document.getElementById('pd-img');
  const prevBtn = wrap.querySelector('[data-dir="-1"]');
  const nextBtn = wrap.querySelector('[data-dir="1"]');
  const live    = document.querySelector('.sr-live');

  let idx = 0;

  function show(i){
    idx = (i + imgs.length) % imgs.length;
    imgEl.src = imgs[idx].src;
    imgEl.alt = imgs[idx].alt || '';
  }

  if (prevBtn) prevBtn.addEventListener('click', () => show(idx - 1));
  if (nextBtn) nextBtn.addEventListener('click', () => show(idx + 1));

  wrap.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft')  { e.preventDefault(); show(idx - 1); }
    if (e.key === 'ArrowRight') { e.preventDefault(); show(idx + 1); }
  });
  wrap.tabIndex = 0;

  show(0);
})();

/* Add to cart button handler */
(function () {
  const addToCartBtn = document.querySelector('.product-detail-info .button.primary');
  if (!addToCartBtn) return;

  const qtyInput = document.getElementById('qty');
  const liveRegion = document.querySelector('.sr-live');

  addToCartBtn.addEventListener('click', async function(e) {
    e.preventDefault();
    
    const quantity = parseInt(qtyInput.value) || 1;
    const productId = new URLSearchParams(window.location.search).get('id');

    if (!productId) {
      alert('Product ID not found.');
      return;
    }

    // Disable button and show loading state
    addToCartBtn.disabled = true;
    const originalText = addToCartBtn.textContent;
    addToCartBtn.textContent = 'Adding...';

    try {
      const formData = new FormData();
      formData.append('product_id', productId);
      formData.append('quantity', quantity);

      const response = await fetch('<?= $base ?>/ajax/add-to-cart.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (!response.ok) {
        // Not logged in - redirect to login
        if (response.status === 401) {
          alert(data.message);
          window.location.href = data.redirect_url ? '<?= $base ?>/' + data.redirect_url : '<?= $base ?>/login.php';
          return;
        }
        throw new Error(data.message || 'Failed to add to cart');
      }

      if (data.success) {
        // Success feedback
        addToCartBtn.textContent = '✓ Added!';
        addToCartBtn.style.background = 'linear-gradient(135deg, #00d84e, #00ff6a)';
        
        if (liveRegion) {
          liveRegion.textContent = data.message;
        }

        // Update cart count in header
        updateHeaderCartCount(data.cart_count);

        // Reset button after 2 seconds
        setTimeout(() => {
          addToCartBtn.textContent = originalText;
          addToCartBtn.style.background = '';
          addToCartBtn.disabled = false;
        }, 2000);
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Add to cart error:', error);
      addToCartBtn.textContent = originalText;
      addToCartBtn.disabled = false;
      alert('Error: ' + error.message);
    }
  });

  function updateHeaderCartCount(count) {
    const cartCountEl = document.querySelector('.cart-count');
    if (cartCountEl) {
      cartCountEl.textContent = count;
    }
  }
})();
</script>

<?php include 'includes/footer.php'; ?>
