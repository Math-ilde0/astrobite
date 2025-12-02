<?php
// =========================================
//  INIT + BASE PATH
// =========================================

// Get base path so all links work in subfolders (ex: /mywebsite/astrobite)
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Build absolute base URL (for SEO links, images, OG, etc.)
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$absBase  = $scheme . '://' . $host . ($basePath ?: '');

// Canonical URL (clean URL for SEO)
$pathOnly = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$canonicalUrl = $absBase . ($pathOnly === '/' ? '' : $pathOnly);

// Default SEO meta (can be overridden per page BEFORE including header.php)
$pageTitle       = $pageTitle       ?? 'AstroBite — Freeze-Dried Snacks & Space-Inspired Treats';
$pageDescription = $pageDescription ?? 'Discover AstroBite: freeze-dried fruits and space snacks.';
$pageImage       = $pageImage       ?? ($absBase . '/assets/images/logo_social.png');
$pageType        = $pageType        ?? 'website';
$robots          = $robots          ?? 'index,follow';

// =========================================
//  SESSION
// =========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLogged = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'Account';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Basic -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>" />
  <meta name="robots" content="<?= htmlspecialchars($robots) ?>" />
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>" />

  <!-- Performance -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Audiowide&family=Inter:wght@400;600;700&family=Orbitron:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- CSS -->
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css?v=1" />

  <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $basePath ?>/assets/images/favicon-32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $basePath ?>/assets/images/favicon-16.png">
  <link rel="apple-touch-icon" href="<?= $basePath ?>/assets/images/apple-touch-icon.png">
  <meta name="theme-color" content="#0A3D62" />

  <!-- Open Graph -->
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>" />
  <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>" />
  <meta property="og:type" content="<?= $pageType === 'product' ? 'product' : 'website' ?>" />
  <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>" />
  <meta property="og:image" content="<?= htmlspecialchars($pageImage) ?>" />

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>" />
  <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>" />
  <meta name="twitter:image" content="<?= htmlspecialchars($pageImage) ?>" />

  <!-- Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "name": "AstroBite",
        "url": "<?= $absBase ?>",
        "logo": "<?= $absBase ?>/assets/images/logo.png"
      },
      {
        "@type": "WebSite",
        "name": "AstroBite",
        "url": "<?= $absBase ?>",
        "potentialAction": {
          "@type": "SearchAction",
          "target": "<?= $absBase ?>/products.php?q={search_term_string}",
          "query-input": "required name=search_term_string"
        }
      }
    ]
  }
  </script>

</head>
<body>

<header>
  <div class="container nav-container">

    <!-- Logo -->
    <a href="<?= $basePath ?>/index.php" class="logo" aria-label="AstroBite Home">
  <img src="<?= $basePath ?>/assets/images/logo.png" alt="AstroBite Logo" loading="lazy" />
      <span>AstroBite</span>
    </a>

    <!-- HAMBURGER MENU TOGGLE (Mobile only) -->
    <button class="hamburger-toggle" id="hamburger-toggle" aria-label="Toggle menu" aria-expanded="false">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- NAVIGATION -->
    <nav class="main-nav" id="main-nav" aria-label="Primary">

      <!-- Left menu -->
      <ul class="nav-links">
        <li><a href="<?= $basePath ?>/index.php">Home</a></li>
        <li><a href="<?= $basePath ?>/products.php">Products</a></li>
        <li><a href="<?= $basePath ?>/contact.php">Contact</a></li>
      </ul>

      <!-- SEARCH -->
      <form id="nav-search-form"
            class="nav-search"
            action="<?= $basePath ?>/products.php"
            method="GET"
            autocomplete="off"
            style="position:relative;"
            data-base="<?= $basePath ?>">
  <input id="nav-search-input" type="text" name="q" placeholder="Search..." aria-label="Search products" autocomplete="off" />
  <button type="submit">🔍</button>
  <div id="nav-search-dropdown" class="nav-search-dropdown" style="display:none;"></div>
      </form>

      <!-- RIGHT SIDE (Cart + Account) -->
      <ul class="nav-links">

        <!-- CART -->
        <li class="cart-container">
          <a href="<?= $basePath ?>/cart.php" class="cart-link" aria-label="Cart">
            🛒 <span class="cart-count">0</span>
          </a>
          <div class="cart-dropdown">
            <p>Your cart is empty.</p>
          </div>
        </li>

        <!-- ACCOUNT (Login or Profile) -->
        <li class="profile-menu">
          <?php if ($isLogged): ?>
            <a href="<?= $basePath ?>/profile.php" class="login-link" aria-label="Profile">
              <img src="<?= $basePath ?>/assets/images/loginiconWhite.png" class="login-icon" alt="Profile" loading="lazy" />
              <span style="margin-left:6px;"><?= htmlspecialchars($userName) ?></span>
            </a>
          <?php else: ?>
            <a href="<?= $basePath ?>/login.php" class="login-link" aria-label="Login">
              <img src="<?= $basePath ?>/assets/images/loginiconWhite.png" class="login-icon" alt="Login" loading="lazy" />
            </a>
          <?php endif; ?>
        </li>

      </ul>
    </nav>
  </div>
</header>

<style>
  .profile-menu { position: relative; }
  .profile-menu .dropdown {
    display:none;
    position:absolute;
    right:0;
    top:100%;
    background:#0e2640;
    border:1px solid rgba(255,255,255,.12);
    border-radius:10px;
    min-width:180px;
    padding:8px;
    z-index:9999;
  }
  .profile-menu:hover .dropdown { display:block; }
  .profile-menu .dropdown a {
    display:block;
    padding:8px 10px;
    color:#fff;
    text-decoration:none;
  }
  .profile-menu .dropdown a:hover {
    background:rgba(255,255,255,.06);
  }

  /* Cart dropdown enhancements */
  .cart-container { position: relative; }
  .cart-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: rgba(10, 40, 60, 0.95);
    border: 1px solid rgba(93, 217, 255, 0.3);
    border-radius: 10px;
    min-width: 320px;
    padding: 12px;
    z-index: 9999;
    backdrop-filter: blur(8px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
  }

  .cart-container:hover .cart-dropdown,
  .cart-dropdown.active {
    display: block;
  }

  .cart-dropdown-items {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .cart-item-preview {
    padding: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 0.85rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .cart-item-preview:last-child {
    border-bottom: none;
  }

  .cart-item-preview-name {
    flex: 1;
    color: #5dd9ff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .cart-item-preview-qty {
    color: rgba(255, 255, 255, 0.7);
    margin: 0 8px;
  }

  .cart-total-preview {
    padding: 12px 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: #5dd9ff;
    margin-bottom: 12px;
  }

  .cart-dropdown a.button,
  .cart-dropdown button.button {
    width: 100%;
    padding: 10px 12px;
    margin-bottom: 8px;
    font-size: 0.9rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    display: inline-block;
    transition: all 0.2s;
  }

  .cart-dropdown a.button.primary {
    background: linear-gradient(135deg, #00d84e, #00ff6a);
    color: #000;
    font-weight: 700;
  }

  .cart-dropdown a.button.primary:hover {
    opacity: 0.9;
  }

  .cart-dropdown a.button.secondary {
    background: rgba(255, 255, 255, 0.1);
    color: #5dd9ff;
    border: 1px solid rgba(93, 217, 255, 0.3);
  }

  .cart-dropdown a.button.secondary:hover {
    background: rgba(93, 217, 255, 0.2);
  }

  .cart-empty-message {
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
    padding: 12px 8px;
    font-size: 0.9rem;
  }
</style>

<script>
(function() {
  const basePath = <?= json_encode($basePath) ?>;
  const isLogged = <?= json_encode($isLogged) ?>;

  async function updateCartDropdown() {
    const cartDropdown = document.querySelector('.cart-dropdown');
    if (!cartDropdown) return;

    try {
      const response = await fetch(basePath + '/ajax/get-cart.php');
      const data = await response.json();

      if (data.items.length === 0) {
        cartDropdown.innerHTML = '<p class="cart-empty-message">Your cart is empty.</p>';
        updateCartCount(0);
        return;
      }

      let html = '<div class="cart-dropdown-items">';
      
      data.items.forEach(item => {
        html += `
          <div class="cart-item-preview">
            <span class="cart-item-preview-name">${escapeHtml(item.name)}</span>
            <span class="cart-item-preview-qty">×${item.quantity}</span>
          </div>
        `;
      });

      html += '</div>';
      html += `
        <div class="cart-total-preview">
          <span>Total:</span>
          <span>$${parseFloat(data.total).toFixed(2)}</span>
        </div>
        <a href="${basePath}/cart.php" class="button secondary">View Cart</a>
        <a href="${basePath}/checkout.php" class="button primary">Place Order</a>
      `;

      cartDropdown.innerHTML = html;
      updateCartCount(data.cart_count);
    } catch (error) {
      console.error('Error fetching cart:', error);
      cartDropdown.innerHTML = '<p class="cart-empty-message">Error loading cart</p>';
    }
  }

  function updateCartCount(count) {
    const cartCountEl = document.querySelector('.cart-count');
    if (cartCountEl) {
      cartCountEl.textContent = count;
    }
  }

  function escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
  }

  // Initialize cart dropdown on page load
  if (isLogged) {
    updateCartDropdown();

    // Update on hover
    const cartContainer = document.querySelector('.cart-container');
    if (cartContainer) {
      cartContainer.addEventListener('mouseenter', updateCartDropdown);
    }
  }

  // Listen for cart updates from other scripts
  window.addEventListener('cartUpdated', updateCartDropdown);
})();

</script>
<script src="<?= $basePath ?>/assets/js/ajax-search.js?v=1"></script>
