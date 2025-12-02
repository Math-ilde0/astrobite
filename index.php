<?php
// index.php
// --------------------------------------------------
// Page meta (header.php will read these if supported)
$pageTitle       = 'AstroBite | Freeze-Dried Space Meals & Snacks';
$pageDescription = 'Astronaut-inspired freeze-dried meals and snacks. Lightweight, long shelf life, and delicious — perfect for hiking, camping, and adventures on Earth.';
$pageCanonical   = '/mywebsite/astrobite/index.php';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Preload 2 featured products for first paint
$featuredProducts = [];
$stmt = $pdo->query("
  SELECT product_id, name, description, image1, image2, price
  FROM products
  ORDER BY RAND()
  LIMIT 2
");
$initial = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($initial as $p) {
  $fp = [
    '@type'       => 'Product',
    'name'        => $p['name'],
    'description' => $p['description'],
    'image'       => array_values(array_filter([$p['image1'] ?: null, $p['image2'] ?: null])),
    'url'         => $basePath . '/product.php?id=' . (int)$p['product_id'],
  ];
  if (isset($p['price'])) {
    $fp['offers'] = [
      '@type'          => 'Offer',
      'price'          => number_format((float)$p['price'], 2, '.', ''),
      'priceCurrency'  => 'USD',
      'availability'   => 'https://schema.org/InStock'
    ];
  }
  $featuredProducts[] = $fp;
}
?>
<main class="container galaxy-bg" itemscope itemtype="https://schema.org/WebPage">
  <!-- Hero Banner -->
  <section class="hero" data-aos="fade-up">
    <div class="hero-content">
      <h1 itemprop="headline">Embark on a Galactic Feast</h1>
      <p itemprop="description">
        Inspired by space missions, AstroBite offers delicious, freeze-dried meals crafted for adventurers, explorers, and Earthlings who dream big.
      </p>
      <a href="<?= $basePath ?>/products.php" class="btn-primary" itemprop="relatedLink">🚀 Explore Our Space Menu</a>
    </div>
  </section>

  <hr class="galactic-separator" />

  <!-- About -->
  <section class="about" data-aos="fade-right" aria-labelledby="why-title">
    <h2 id="why-title">✨ Why Choose AstroBite?</h2>
    <p>At AstroBite, we bring the cosmos to your plate. Our meals are inspired by real astronaut nutrition and engineered for flavor, performance, and lightness.</p>
    <p>Whether you're hiking in the Alps, camping under the stars, or simply craving a futuristic food experience — AstroBite delivers.</p>
    <blockquote class="astro-quote">“Tastes like home. Even in orbit.” — Commander L. Vega</blockquote>
    <p>Developed with Swiss precision, long shelf life, and easy rehydration — zero gravity not required.</p>
  </section>

  <hr class="galactic-separator" />

  <!-- Features -->
  <section class="space-features" data-aos="zoom-in" aria-labelledby="features-title">
    <h2 id="features-title">🌌 Designed for Earth. Inspired by Space.</h2>
    <ul class="feature-list">
      <li>🚀 Freeze-dried with astronaut-grade technology</li>
      <li>🌌 Lightweight & compact — perfect for any mission</li>
      <li>🪐 10+ years shelf life with preserved nutrients</li>
      <li>🌍 Eco-friendly packaging with low carbon impact</li>
      <li>👩‍🚀 Trusted by explorers, athletes & survivalists</li>
    </ul>
  </section>

  <hr class="galactic-separator" />

  <!-- Featured products (auto-rotating) -->
  <section class="highlight-categories" data-aos="fade-up" aria-labelledby="featured-title">
    <h2 id="featured-title">Featured Space Treats</h2>
    <div class="product-grid" id="featured-grid">
      <?php foreach ($initial as $product):
          $id    = (int)$product['product_id'];
          $name  = htmlspecialchars($product['name'] ?? '');
          $desc  = htmlspecialchars($product['description'] ?? '');
          $img   = htmlspecialchars($product['image1'] ?? '');
          $hover = htmlspecialchars($product['image2'] ?? '');
          $url   = $basePath . "/product.php?id={$id}";
      ?>
        <a href="<?= $url ?>" class="product-card-link" itemprop="hasPart" itemscope itemtype="https://schema.org/Product">
          <div class="product-card" data-aos="zoom-in">
            <div class="image-wrapper">
              <img src="<?= $img ?>" class="main-img" alt="<?= $name ?>" width="600" height="600" loading="lazy" decoding="async" itemprop="image" />
              <?php if ($hover): ?>
                <img src="<?= $hover ?>" class="hover-img" alt="<?= $name ?> - alternate view" width="600" height="600" loading="lazy" decoding="async" />
              <?php endif; ?>
            </div>
            <h3 itemprop="name"><?= $name ?></h3>
            <p itemprop="description"><?= $desc ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <hr class="galactic-separator" />

  <!-- CTA -->
  <section class="cta-space" data-aos="fade-up" aria-labelledby="cta-title">
    <h2 id="cta-title">Ready for Lift-Off?</h2>
    <p>Whether you're preparing for a hiking trip, a space-themed party, or your next outdoor expedition — bring a taste of the cosmos with you.</p>
    <a href="<?= $basePath ?>/products.php" class="btn-primary">Shop All Meals</a>
  </section>
</main>

<?php
// ---------------------- JSON-LD ----------------------
$ld = [];

// WebSite + SearchAction
$ld[] = [
  '@context' => 'https://schema.org',
  '@type'    => 'WebSite',
  'name'     => 'AstroBite',
  'url'      => $absBase, // from header.php
  'potentialAction' => [
    '@type'       => 'SearchAction',
    'target'      => $absBase . '/products.php?q={search_term_string}',
    'query-input' => 'required name=search_term_string'
  ]
];

// Organization
$ld[] = [
  '@context' => 'https://schema.org',
  '@type'    => 'Organization',
  'name'     => 'AstroBite',
  'url'      => $absBase,
  'logo'     => $absBase . '/assets/images/logo.png'
];

// Featured products (initial render only; not updated on rotation)
if ($featuredProducts) {
  $ld[] = count($featuredProducts) === 1
    ? $featuredProducts[0]
    : ['@context' => 'https://schema.org', '@graph' => $featuredProducts];
}
?>
<script type="application/ld+json">
<?= json_encode(count($ld) === 1 ? $ld[0] : $ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n" ?>
</script>

<!-- Auto-rotate featured products every 10s -->
<script>
(function () {
  const container = document.getElementById('featured-grid');
  if (!container) return;

  const base = <?= json_encode($basePath) ?>;

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]));
  }

  function renderProducts(list) {
    container.innerHTML = list.map(p => {
      const name = escapeHtml(p.name);
      const desc = escapeHtml(p.description || '');
      const img  = escapeHtml(p.image1 || '');
      const img2 = escapeHtml(p.image2 || '');
      const url  = `${base}/product.php?id=${encodeURIComponent(p.product_id)}`;
      return `
        <a href="${url}" class="product-card-link">
          <div class="product-card" data-aos="zoom-in">
            <div class="image-wrapper">
              <img src="${img}" class="main-img" alt="${name}" loading="lazy" decoding="async">
              ${img2 ? `<img src="${img2}" class="hover-img" alt="${name} - alternate view" loading="lazy" decoding="async">` : ``}
            </div>
            <h3>${name}</h3>
            <p>${desc}</p>
          </div>
        </a>`;
    }).join('');
  }

  function refreshProducts() {
    fetch(`${base}/ajax/random_products.php?limit=2`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => Array.isArray(data) ? data : [])
      .then(renderProducts)
      .catch(err => console.error('Featured products error:', err));
  }

  // First refresh shortly after load (keeps SSR fast, then rotate)
  setTimeout(refreshProducts, 2000);
  // Rotate every 10s
  setInterval(refreshProducts, 10000);
})();
</script>

<style>
#featured-grid {
  position: relative;
  transition: opacity 1.2s cubic-bezier(.4,0,.2,1);
}
#featured-grid.fading {
  opacity: 0.15;
  pointer-events: none;
  filter: blur(2px) brightness(1.2) drop-shadow(0 0 24px #5dd9ff) drop-shadow(0 0 48px #00ff6a);
  background: radial-gradient(ellipse at 60% 40%, rgba(93,217,255,0.12) 0%, rgba(0,0,0,0) 80%);
}
#featured-grid::after {
  content: '';
  display: block;
  pointer-events: none;
  position: absolute;
  left: 0; top: 0; right: 0; bottom: 0;
  z-index: 2;
  opacity: 0;
  transition: opacity 1.2s cubic-bezier(.4,0,.2,1);
  background: radial-gradient(ellipse at 60% 40%, rgba(93,217,255,0.18) 0%, rgba(0,0,0,0) 80%);
}
#featured-grid.fading::after {
  opacity: 1;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const grid = document.getElementById('featured-grid');
  let lastIds = [];
  function updateProducts() {
    grid.classList.add('fading');
    fetch('ajax/random-products.php')
      .then(res => res.json())
      .then(products => {
        if (!Array.isArray(products) || products.length < 2) return;
        // Ensure the two products are different from last time
        let tries = 0;
        while (tries < 5 && lastIds.length === 2 && products.length === 2 && (products[0].product_id === lastIds[0] || products[1].product_id === lastIds[1] || products[0].product_id === lastIds[1] || products[1].product_id === lastIds[0])) {
          // Force a new fetch if same as last
          tries++;
          return setTimeout(updateProducts, 200);
        }
        setTimeout(() => {
          grid.innerHTML = products.map(product => {
            const img = product.image1 ? product.image1 : '';
            const hover = product.image2 ? `<img src=\"${product.image2}\" class=\"hover-img\" alt=\"${product.name} - alternate view\" width=\"600\" height=\"600\" loading=\"lazy\" decoding=\"async\" />` : '';
            return `
              <a href=\"product.php?id=${product.product_id}\" class=\"product-card-link\" itemprop=\"hasPart\" itemscope itemtype=\"https://schema.org/Product\">
                <div class=\"product-card\">
                  <div class=\"image-wrapper\">
                    <img src=\"${img}\" class=\"main-img\" alt=\"${product.name}\" width=\"600\" height=\"600\" loading=\"lazy\" decoding=\"async\" itemprop=\"image\" />
                    ${hover}
                  </div>
                  <h3 itemprop=\"name\">${product.name}</h3>
                  <p itemprop=\"description\">${product.description}</p>
                </div>
              </a>
            `;
          }).join('');
          lastIds = [products[0].product_id, products[1].product_id];
          grid.classList.remove('fading');
        }, 1100);
      });
  }
  setInterval(updateProducts, 10000);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
