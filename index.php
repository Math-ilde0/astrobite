<?php

require_once 'includes/db.php';
require_once 'includes/header.php';

?>

<main class="container galaxy-bg">
  <!-- Hero Banner -->
  <section class="hero" data-aos="fade-up">
    <div class="hero-content">
      <h1>Embark on a Galactic Feast</h1>
      <p>Inspired by space missions, AstroBite offers delicious, freeze-dried meals crafted for adventurers, explorers, and Earthlings who dream big.</p>
      <a href="products.php" class="btn-primary">🚀 Explore Our Space Menu</a>
    </div>
    <div class="hero-image">
      <img src="assets/images/astro-meal.png" alt="AstroBite space meal" />
    </div>
  </section>

  <hr class="galactic-separator" />

  <!-- About Section -->
  <section class="about" data-aos="fade-right">
    <h2>✨ Why Choose AstroBite?</h2>
    <p>
      At AstroBite, we bring the cosmos to your plate. Our meals are inspired by real astronaut nutrition and engineered for flavor, performance, and lightness.
    </p>
    <p>
      Whether you're hiking in the Alps, camping under the stars, or simply craving a futuristic food experience—AstroBite delivers.
    </p>
    <blockquote class="astro-quote">“Tastes like home. Even in orbit.” — Commander L. Vega</blockquote>
    <p>
      Developed with Swiss precision, long shelf life, and easy rehydration — just like in orbit. Zero gravity not required.
    </p>
  </section>

  <!-- Space Features -->
  <section class="space-features" data-aos="zoom-in">
    <h2>🌌 Designed for Earth. Inspired by Space.</h2>
    <ul class="feature-list">
      <li>🚀 Freeze-dried with astronaut-grade technology</li>
      <li>🌌 Lightweight & compact — perfect for any mission</li>
      <li>🪐 10+ years shelf life with preserved nutrients</li>
      <li>🌍 Eco-friendly packaging with low carbon impact</li>
      <li>👩‍🚀 Trusted by explorers, athletes & survivalists</li>
    </ul>
  </section>

  <hr class="galactic-separator" />

  <!-- Highlight Products -->
  <section class="highlight-categories" data-aos="fade-up">
    <h2>Featured Space Treats</h2>
  <div class="product-grid">

    <?php
    $stmt = $pdo->query("SELECT * FROM products ORDER BY RAND() LIMIT 2");

    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $id = $product['id'];
        $name = htmlspecialchars($product['name'] ?? '');
        $description = htmlspecialchars($product['description'] ?? '');
        $image = $product['image'] ?? '';
        $hover = $product['image2'] ?? '';
    ?>
      <a href="product.php?id=<?= $id ?>" class="product-card-link">
          <div class="product-card" data-aos="zoom-in">
          <div class="image-wrapper">
              <img src="<?= htmlspecialchars($image) ?>" class="main-img" alt="<?= $name ?>" />
              <img src="<?= htmlspecialchars($hover) ?>" class="hover-img" alt="<?= $name ?> Hover" />
          </div>
            <h3><?= $name ?></h3>
          <p><?= $description ?></p>
        </div>
      </a>
    <?php } ?>

  </div>
</section>

  <hr class="galactic-separator" />

  <!-- Call to Action -->
  <section class="cta-space" data-aos="fade-up">
    <h2>Ready for Lift-Off?</h2>
    <p>Whether you're preparing for a hiking trip, a space-themed party, or your next outdoor expedition — bring a taste of the cosmos with you.</p>
    <a href="products.php" class="btn-primary">Shop All Meals</a>
  </section>
</main>

<?php
include 'includes/footer.php';
?>
