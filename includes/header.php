<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Audiowide&family=Inter:wght@400;600;700&family=Orbitron:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css?v=1" />
  <title>AstroBite</title>
</head>
<body>
<header>
  <div class="container nav-container">
    <a href="/mywebsite/astrobite/index.php" class="logo">
      <img src="/mywebsite/astrobite/assets/images/logo.png" alt="AstroBite Logo" />
      <span>AstroBite</span>
    </a>

    <nav class="main-nav">
      <ul class="nav-links">
        <li><a href="/mywebsite/astrobite/index.php">Home</a></li>
        <li><a href="/mywebsite/astrobite/products.php">Products</a></li>
        <li><a href="/mywebsite/astrobite/contact.php">Contact</a></li>
      </ul>
      <form class="nav-search" action="/mywebsite/astrobite/products.php" method="GET">
        <input type="text" name="search" placeholder="Search..." />
        <button type="submit">🔍</button>
      </form>
      <ul class="nav-links">
        <li class="cart-container">
          <a href="<?= $basePath ?>/cart.php" class="cart-link" aria-label="Cart">
            🛒 <span class="cart-count">0</span> <!-- Placeholder for cart count -->
          </a>
          <!-- Dropdown Cart -->
          <div class="cart-dropdown">
            <p>Your cart is empty.</p>
          </div>
        </li>
        <li>
          <a href="<?= $basePath ?>/login.php" class="login-link" aria-label="Login">
            <img src="<?= $basePath ?>/assets/images/loginiconWhite.png" alt="Login" class="login-icon" />
          </a>
        </li>
      </ul>
    </nav>
  </div>
</header>