<?php
// Fetch video and text from database (same as v1)
$homeVideo = selectContent($conn, 'home_video', ['visibility' => 'show']);
$videoSrc = $homeVideo[0]['video_url'];
$videoText = $homeVideo[0]['video_text'];

// Page meta
$v2_page_title = $site_name . ' | Exclusive Collections';
$v2_page_description = 'Discover ' . $site_name . ' curated collection of stylish clothing. Shop quality designs that empower and inspire.';
$v2_page_id = 'home';
$v2_header_transparent = true;

// Include v2 header
include APP_PATH."/views/includes/header_v2.php";
?>

<main>
  <!-- Hero Section - Full screen video like Niovo -->
  <section class="v2-hero">
    <div class="v2-hero__media">
      <video autoplay muted loop playsinline preload="auto" poster="" onerror="this.style.display='none'">
        <source src="<?= htmlspecialchars($videoSrc) ?>" type="video/mp4">
      </video>
    </div>
    <div class="v2-hero__overlay"></div>
    <div class="v2-hero__content">
      <div>
        <p class="v2-hero__tagline"><?= htmlspecialchars($site_name) ?></p>
      </div>
      <div>
        <p class="v2-hero__subtitle"><?= htmlspecialchars($videoText) ?></p>
      </div>
    </div>
    <div class="v2-hero__scroll">
      <span>Scroll</span>
    </div>
  </section>

  <!-- Featured Products Section -->
  <?php
  // Fetch newest products for the homepage grid
  $featuredProducts = [];
  try {
      $stmt = $conn->prepare("
          SELECT p.*, c.name as collection_name
          FROM panel_products p
          LEFT JOIN collections c ON p.collection_id = c.id
          WHERE p.visibility = 'show' AND p.image_one IS NOT NULL AND p.image_one != ''
          ORDER BY p.id DESC LIMIT 8
      ");
      $stmt->execute();
      $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) { /* silently fail */ }
  ?>

  <?php if (!empty($featuredProducts)): ?>
  <section class="v2-section">
    <div class="v2-page-width">
      <div class="v2-section-header v2-fade-in">
        <h2>New Arrivals</h2>
        <a href="/shop">Shop All &gt;</a>
      </div>
      <div class="v2-product-grid">
        <?php foreach ($featuredProducts as $index => $product): ?>
        <div class="v2-product-card v2-fade-in">
          <a href="/product/<?= htmlspecialchars($product['slug'] ?? $product['id']) ?>" class="v2-product-card__link">
            <div class="v2-product-card__image">
              <img
                src="<?= htmlspecialchars($product['image_one']) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>"
                loading="<?= $index < 4 ? 'eager' : 'lazy' ?>"
              >
              <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                <span class="v2-product-card__badge v2-product-card__badge--sale">Sale</span>
              <?php endif; ?>
            </div>
            <div class="v2-product-card__info">
              <span class="v2-product-card__title"><?= htmlspecialchars($product['name']) ?></span>
              <span class="v2-product-card__price">
                <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                  <span class="price-display v2-product-card__price--sale" data-price-ngn="<?= $product['price'] ?>">
                    <?= ($current_currency === 'USD')
                        ? '$' . number_format($product['price'] / USD_EXCHANGE_RATE, 2)
                        : '₦' . number_format($product['price']) ?>
                  </span>
                  <s class="price-display" data-price-ngn="<?= $product['compare_price'] ?>">
                    <?= ($current_currency === 'USD')
                        ? '$' . number_format($product['compare_price'] / USD_EXCHANGE_RATE, 2)
                        : '₦' . number_format($product['compare_price']) ?>
                  </s>
                <?php else: ?>
                  <span class="price-display" data-price-ngn="<?= $product['price'] ?>">
                    <?= ($current_currency === 'USD')
                        ? '$' . number_format($product['price'] / USD_EXCHANGE_RATE, 2)
                        : '₦' . number_format($product['price']) ?>
                  </span>
                <?php endif; ?>
              </span>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Collections Grid Section -->
  <?php
  $collections = selectContent($conn, "collections", [], "ORDER BY name ASC");
  $collectionsWithData = [];
  foreach ($collections as $collection) {
      $productStmt = $conn->prepare("
          SELECT image_one
          FROM panel_products
          WHERE collection_id = ? AND visibility = 'show' AND image_one IS NOT NULL AND image_one != ''
          ORDER BY id DESC LIMIT 1
      ");
      $productStmt->execute([$collection['id']]);
      $firstProductImage = $productStmt->fetchColumn();
      $collection['display_image'] = $firstProductImage ?: '';
      if ($collection['display_image']) {
          $collectionsWithData[] = $collection;
      }
  }
  ?>

  <?php if (!empty($collectionsWithData)): ?>
  <section class="v2-section v2-section--compact" style="background: var(--v2-bg-alt);">
    <div class="v2-page-width">
      <div class="v2-section-header v2-fade-in">
        <h2>Collections</h2>
        <a href="/shop">View All &gt;</a>
      </div>
      <div class="v2-collection-grid">
        <?php foreach ($collectionsWithData as $collection): ?>
        <a href="/shop" class="v2-collection-card v2-fade-in">
          <img
            src="<?= htmlspecialchars($collection['display_image']) ?>"
            alt="<?= htmlspecialchars($collection['name']) ?>"
            class="v2-collection-card__image"
            loading="lazy"
          >
          <div class="v2-collection-card__overlay">
            <span class="v2-collection-card__title"><?= htmlspecialchars($collection['name']) ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Brand Story Teaser -->
  <section class="v2-section v2-text-center">
    <div class="v2-page-width" style="max-width: 700px;">
      <div class="v2-fade-in">
        <p class="v2-subheading" style="margin-bottom: var(--v2-space-md);"><?= htmlspecialchars($site_name) ?></p>
        <h2 class="v2-heading-serif" style="font-size: clamp(1.5rem, 3vw, 2.25rem); margin-bottom: var(--v2-space-lg);">
          Effortlessly Elegant. Timeless Style.
        </h2>
        <p style="color: var(--v2-text-light); margin-bottom: var(--v2-space-xl); line-height: 1.8;">
          <?= $description ?>
        </p>
        <a href="/about" class="v2-btn v2-btn--secondary">Our Story</a>
      </div>
    </div>
  </section>
</main>

<?php include APP_PATH."/views/includes/footer_v2.php"; ?>
