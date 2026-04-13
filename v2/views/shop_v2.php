<?php
// --- COLLECTION FILTERING & PERFORMANCE OPTIMIZATION (same as v1) ---
$collection_filter_id = null;
if (isset($_GET['collection']) && filter_var($_GET['collection'], FILTER_VALIDATE_INT)) {
    $collection_filter_id = (int)$_GET['collection'];
}

$conditions = ['visibility' => 'show'];
if ($collection_filter_id) {
    $conditions['collection_id'] = $collection_filter_id;
}

// Currency (handled in header_v2.php but we need the rate here for initial render)
if (!defined('USD_EXCHANGE_RATE')) {
    $apis = ['https://open.er-api.com/v6/latest/USD', 'https://api.exchangerate-api.com/v4/latest/USD'];
    $rate = 1480;
    foreach ($apis as $apiUrl) {
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $resp = @file_get_contents($apiUrl, false, $ctx);
        if ($resp) { $d = json_decode($resp, true); if (isset($d['rates']['NGN'])) { $rate = floatval($d['rates']['NGN']); break; } }
    }
    define('USD_EXCHANGE_RATE', $rate);
}
if (isset($_SESSION['currency'])) { $current_currency = $_SESSION['currency']; }
elseif (isset($_COOKIE['user_currency'])) { $current_currency = $_COOKIE['user_currency']; }
else { $current_currency = 'NGN'; }

// Product query with variant aggregation
$sql = "SELECT p.*,
        (SELECT SUM(stock_quantity) FROM product_price_variants WHERE product_id = p.id) as variant_total_stock,
        (SELECT MIN(price) FROM product_price_variants WHERE product_id = p.id) as min_variant_price,
        (SELECT MAX(price) FROM product_price_variants WHERE product_id = p.id) as max_variant_price,
        (SELECT COUNT(*) FROM product_price_variants WHERE product_id = p.id) as variant_count
        FROM panel_products p";

$whereClauses = ["LOWER(p.visibility) = 'show'"];
$params = [];
if ($collection_filter_id) {
    $whereClauses[] = "p.collection_id = ?";
    $params[] = $collection_filter_id;
}
$sql .= " WHERE " . implode(" AND ", $whereClauses);
$sql .= " ORDER BY p.id DESC LIMIT 12";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$panelProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$collections = selectContent($conn, "collections", [], "ORDER BY name ASC");

// Price range
$priceRangeStmt = $conn->query("
    SELECT
        LEAST(
            COALESCE((SELECT MIN(price) FROM product_price_variants WHERE price > 0), 999999999),
            COALESCE((SELECT MIN(price) FROM panel_products WHERE price > 0 AND LOWER(visibility) = 'show'), 999999999)
        ) as global_min,
        GREATEST(
            COALESCE((SELECT MAX(price) FROM product_price_variants), 0),
            COALESCE((SELECT MAX(price) FROM panel_products WHERE LOWER(visibility) = 'show'), 0)
        ) as global_max
");
$priceRange = $priceRangeStmt->fetch(PDO::FETCH_ASSOC);
$globalMin = max(0, (int)($priceRange['global_min'] ?? 0));
$globalMax = max($globalMin + 1000, (int)($priceRange['global_max'] ?? 100000));

$countStmt = $conn->query("SELECT COUNT(*) as total FROM panel_products WHERE LOWER(visibility) = 'show'");
$totalProducts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Page meta
$v2_page_title = $site_name . ' | Shop';
$v2_page_description = 'Browse our full collection of clothing at ' . $site_name;
$v2_page_id = 'shop';
$v2_header_transparent = false;

include APP_PATH."/views/includes/header_v2.php";
?>

<main style="padding-top: var(--v2-header-height);">
  <!-- Shop Header -->
  <div class="v2-section v2-section--compact v2-text-center" style="background: var(--v2-bg-alt);">
    <div class="v2-page-width">
      <h1 style="margin-bottom: var(--v2-space-sm);">Shop</h1>
      <p class="v2-body-sm" style="color: var(--v2-text-light);"><?= $totalProducts ?> products</p>
    </div>
  </div>

  <div class="v2-page-width">
    <!-- Filters Bar -->
    <div class="v2-shop-filters">
      <div class="v2-shop-filters__left">
        <!-- Collection filters -->
        <button class="v2-filter-btn collection-filter-btn <?= !$collection_filter_id ? 'active' : '' ?>" data-collection-id="all">All</button>
        <?php foreach ($collections as $col): ?>
          <button class="v2-filter-btn collection-filter-btn <?= ($collection_filter_id == $col['id']) ? 'active' : '' ?>" data-collection-id="<?= $col['id'] ?>"><?= htmlspecialchars($col['name']) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="v2-shop-filters__right">
        <input type="text" id="product-search" class="v2-input" placeholder="Search..." style="width: 180px; padding: 8px 12px; font-size: 0.75rem;">
        <select id="sort-filter-dropdown" class="v2-select" style="width: 160px; padding: 8px 12px; font-size: 0.75rem;">
          <option value="featured">Featured</option>
          <option value="price-asc">Price: Low to High</option>
          <option value="price-desc">Price: High to Low</option>
          <option value="name-asc">A - Z</option>
          <option value="name-desc">Z - A</option>
          <option value="newest">Newest</option>
        </select>
        <span class="v2-product-count" id="product-count"><span id="product-count-number"><?= $totalProducts ?></span> products</span>
      </div>
    </div>

    <!-- Active filter pills -->
    <div id="active-filters" style="display: flex; gap: 8px; flex-wrap: wrap; padding: var(--v2-space-sm) 0;"></div>

    <!-- Product Grid -->
    <div class="v2-product-grid" id="product-grid" style="margin-bottom: var(--v2-space-2xl);">
      <?php if (!empty($panelProducts)): ?>
        <?php foreach ($panelProducts as $index => $product):
          // Stock logic
          if (!empty($product['variant_total_stock'])) {
              $stock = (int)$product['variant_total_stock'];
          } else {
              $stock = (int)($product['stock_quantity'] ?? 0);
          }
          $isSoldOut = ($stock <= 0);
          $productSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $product['name']), '-'));

          // Price logic
          if (!empty($product['min_variant_price']) && $product['min_variant_price'] > 0) {
              $displayPrice = $product['min_variant_price'];
          } else {
              $displayPrice = $product['price'];
          }
          $hasMultipleVariants = (int)($product['variant_count'] ?? 0) > 1;
          $hasVariantPriceRange = $hasMultipleVariants && ($product['min_variant_price'] != $product['max_variant_price']);
        ?>
        <div class="v2-product-card v2-fade-in">
          <a <?php if (!$isSoldOut): ?>href="/product/<?= urlencode($productSlug) ?>?id=<?= urlencode($product['id']) ?>"<?php endif; ?> class="v2-product-card__link <?= $isSoldOut ? 'cursor-not-allowed' : '' ?>">
            <div class="v2-product-card__image">
              <img
                data-src="<?= htmlspecialchars($product['image_one']) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>"
                loading="lazy"
                class="lazy-img"
              >
              <?php if (!$isSoldOut && !empty($product['image_two'])): ?>
                <img
                  data-hover-src="<?= htmlspecialchars($product['image_two']) ?>"
                  alt="<?= htmlspecialchars($product['name']) ?> Hover"
                  class="product-hover-img"
                  style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0; transition: opacity 0.4s ease;"
                >
              <?php endif; ?>
              <?php if ($isSoldOut): ?>
                <span class="v2-product-card__badge">Sold Out</span>
              <?php endif; ?>
            </div>
            <div class="v2-product-card__info">
              <span class="v2-product-card__title"><?= htmlspecialchars($product['name']) ?></span>
              <span class="v2-product-card__price">
                <span class="price-display" data-price-ngn="<?= $displayPrice ?>">
                  <?php if ($hasVariantPriceRange): ?>From <?php endif; ?>
                  <?= ($current_currency === 'USD') ? '$' . number_format($displayPrice / USD_EXCHANGE_RATE, 2) : '₦' . number_format($displayPrice, 2) ?>
                </span>
              </span>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Load More -->
    <div id="load-more-container" style="text-align: center; padding: var(--v2-space-2xl) 0; <?= count($panelProducts) < 12 ? 'display:none;' : '' ?>">
      <button id="load-more-btn" class="v2-btn v2-btn--secondary">Load More</button>
      <div id="load-more-spinner" style="display:none; margin: 0 auto;">
        <div style="width:32px; height:32px; border:2px solid var(--v2-border); border-top-color: var(--v2-text); border-radius:50%; animation: v2-spin 0.6s linear infinite; margin: 0 auto;"></div>
      </div>
    </div>
  </div>
</main>

<style>
@keyframes v2-spin { to { transform: rotate(360deg); } }

/* Hover image effect for product cards */
.v2-product-card:hover .product-hover-img { opacity: 1 !important; }
.v2-product-card:hover .lazy-img { opacity: 0 !important; }

/* Lazy loading skeleton */
.lazy-img { transition: opacity 0.4s ease; }
.lazy-img[data-src] { opacity: 0 !important; background: var(--v2-bg-alt); }

/* Filter button active state */
.v2-filter-btn.active { border-color: var(--v2-text); font-weight: 600; }

/* Filter pills */
.filter-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 12px; background: var(--v2-bg-alt); font-size: 0.75rem;
  border: 1px solid var(--v2-border);
}
.pill-close { cursor: pointer; opacity: 0.5; }
.pill-close:hover { opacity: 1; }

/* Mobile responsiveness */
@media (max-width: 768px) {
  .v2-shop-filters { flex-direction: column; gap: var(--v2-space-sm); align-items: stretch; }
  .v2-shop-filters__left { overflow-x: auto; white-space: nowrap; padding-bottom: var(--v2-space-xs); -webkit-overflow-scrolling: touch; }
  .v2-shop-filters__right { flex-wrap: wrap; }
  .v2-shop-filters__right input, .v2-shop-filters__right select { flex: 1; min-width: 120px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // --- LAZY LOADING ---
  const lazyImageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
          img.onload = function() { this.style.opacity = '1'; };
          if (img.complete) img.style.opacity = '1';
        }
        observer.unobserve(img);
      }
    });
  }, { rootMargin: '200px 0px', threshold: 0.01 });

  function observeLazyImages() {
    document.querySelectorAll('.lazy-img[data-src]').forEach(img => lazyImageObserver.observe(img));
  }
  observeLazyImages();

  // Hover image deferred loading
  function initHoverImages() {
    document.querySelectorAll('.v2-product-card').forEach(card => {
      if (card.dataset.hoverInit) return;
      card.dataset.hoverInit = '1';
      card.addEventListener('mouseenter', function loadHover() {
        const hoverImg = this.querySelector('img[data-hover-src]');
        if (hoverImg && !hoverImg.src) {
          hoverImg.src = hoverImg.dataset.hoverSrc;
          hoverImg.removeAttribute('data-hover-src');
        }
        this.removeEventListener('mouseenter', loadHover);
      }, { once: true });
    });
  }
  initHoverImages();

  // --- FILTER & LOAD MORE SYSTEM ---
  let activeCollectionId = '<?= $collection_filter_id ?: "all" ?>';
  let currentOffset = <?= count($panelProducts) ?>;
  const LOAD_STEP = 12;
  const INITIAL_LIMIT = 12;
  const globalMin = <?= $globalMin ?>;
  const globalMax = <?= $globalMax ?>;

  const USD_RATE = <?= defined('USD_EXCHANGE_RATE') ? USD_EXCHANGE_RATE : 1480 ?>;
  const currencyActive = document.querySelector('.currency-link.active');
  let currentCurrency = currencyActive ? (currencyActive.dataset.currency || 'NGN') : 'NGN';

  function formatPrice(amount, currency) {
    if (currency === 'USD') return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount / USD_RATE);
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount).replace('NGN', '\u20A6');
  }

  const searchInput = document.getElementById('product-search');
  const sortDropdown = document.getElementById('sort-filter-dropdown');
  const productGrid = document.getElementById('product-grid');
  const loadMoreContainer = document.getElementById('load-more-container');
  const loadMoreBtn = document.getElementById('load-more-btn');
  const loadMoreSpinner = document.getElementById('load-more-spinner');
  const activeFiltersContainer = document.getElementById('active-filters');
  const productCountEl = document.getElementById('product-count');

  let searchQuery = '';
  let sortOption = 'featured';
  let activePriceMin = null, activePriceMax = null;
  let activeVariantNames = [];

  function updateProductCount(count) {
    productCountEl.innerHTML = '<span id="product-count-number" style="font-weight:600;">' + count + '</span> product' + (count !== 1 ? 's' : '');
  }

  function updateFilterPills() {
    activeFiltersContainer.innerHTML = '';
    if (activeCollectionId !== 'all') {
      const btn = document.querySelector('.collection-filter-btn[data-collection-id="' + activeCollectionId + '"]');
      if (btn) {
        const pill = document.createElement('div');
        pill.className = 'filter-pill';
        pill.innerHTML = btn.textContent.trim() + ' <span class="pill-close" data-filter="collection">\u2715</span>';
        pill.querySelector('.pill-close').addEventListener('click', () => {
          document.querySelectorAll('.collection-filter-btn').forEach(b => b.classList.remove('active'));
          document.querySelector('.collection-filter-btn[data-collection-id="all"]').classList.add('active');
          activeCollectionId = 'all';
          window.history.pushState({}, '', 'shop');
          updateFilterPills();
          fetchProducts(true);
        });
        activeFiltersContainer.appendChild(pill);
      }
    }
    if (searchQuery) {
      const pill = document.createElement('div');
      pill.className = 'filter-pill';
      pill.innerHTML = '"' + searchQuery + '" <span class="pill-close">\u2715</span>';
      pill.querySelector('.pill-close').addEventListener('click', () => {
        searchQuery = '';
        searchInput.value = '';
        updateFilterPills();
        fetchProducts(true);
      });
      activeFiltersContainer.appendChild(pill);
    }
  }

  function createProductCard(product) {
    const isSoldOut = !product.stock_quantity || product.stock_quantity <= 0;
    const slug = product.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const displayPrice = product.display_price || product.price;
    const formattedPrice = formatPrice(displayPrice, currentCurrency);
    const hasRange = product.price_variants && product.price_variants.length > 1;
    const fromPrefix = hasRange ? 'From ' : '';

    return '<div class="v2-product-card v2-fade-in is-visible">' +
      '<a ' + (!isSoldOut ? 'href="/product/' + encodeURIComponent(slug) + '?id=' + product.id + '"' : '') + ' class="v2-product-card__link ' + (isSoldOut ? 'cursor-not-allowed' : '') + '">' +
        '<div class="v2-product-card__image">' +
          '<img data-src="' + product.image_one + '" alt="' + product.name + '" loading="lazy" class="lazy-img">' +
          (!isSoldOut && product.image_two ? '<img data-hover-src="' + product.image_two + '" alt="' + product.name + ' Hover" class="product-hover-img" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 0.4s ease;">' : '') +
          (isSoldOut ? '<span class="v2-product-card__badge">Sold Out</span>' : '') +
        '</div>' +
        '<div class="v2-product-card__info">' +
          '<span class="v2-product-card__title">' + product.name + '</span>' +
          '<span class="v2-product-card__price"><span class="price-display" data-price-ngn="' + displayPrice + '">' + fromPrefix + formattedPrice + '</span></span>' +
        '</div>' +
      '</a>' +
    '</div>';
  }

  function fetchProducts(reset) {
    if (reset) {
      currentOffset = 0;
      productGrid.innerHTML = '<div style="grid-column:1/-1; display:flex; justify-content:center; padding:60px 0;"><div style="width:32px;height:32px;border:2px solid var(--v2-border);border-top-color:var(--v2-text);border-radius:50%;animation:v2-spin 0.6s linear infinite;"></div></div>';
      loadMoreContainer.style.display = 'none';
    } else {
      loadMoreBtn.style.display = 'none';
      loadMoreSpinner.style.display = 'block';
    }

    const limit = reset ? INITIAL_LIMIT : LOAD_STEP;
    const params = new URLSearchParams({
      id: activeCollectionId, offset: currentOffset, limit: limit,
      search: searchQuery, sort: sortOption
    });
    if (activePriceMin !== null) params.set('min_price', activePriceMin);
    if (activePriceMax !== null) params.set('max_price', activePriceMax);
    if (activeVariantNames.length) params.set('variant_names', activeVariantNames.join(','));

    fetch('/fetch-collection?' + params.toString())
      .then(res => res.ok ? res.json() : Promise.reject(res))
      .then(data => {
        let products, totalCount;
        if (Array.isArray(data)) { products = data; totalCount = null; }
        else { products = data.products || []; totalCount = data.total_count ?? null; }

        if (reset) productGrid.innerHTML = '';

        if (products.length === 0) {
          if (reset) productGrid.innerHTML = '<p style="grid-column:1/-1; text-align:center; color:var(--v2-text-muted); padding:60px 0;">No products found.</p>';
          loadMoreContainer.style.display = 'none';
        } else {
          products.forEach(p => productGrid.insertAdjacentHTML('beforeend', createProductCard(p)));
          observeLazyImages();
          initHoverImages();

          if (reset) { currentOffset = products.length; }
          else { currentOffset += products.length; }

          if (products.length < limit) {
            loadMoreContainer.style.display = 'none';
          } else {
            loadMoreContainer.style.display = 'block';
            loadMoreBtn.style.display = 'inline-flex';
            loadMoreSpinner.style.display = 'none';
          }
        }

        if (totalCount !== null) updateProductCount(totalCount);
      })
      .catch(err => {
        console.error(err);
        if (reset) productGrid.innerHTML = '<p style="grid-column:1/-1; text-align:center; color:var(--v2-error); padding:60px 0;">Failed to load products.</p>';
      })
      .finally(() => {
        if (!reset) {
          loadMoreBtn.style.display = 'inline-flex';
          loadMoreSpinner.style.display = 'none';
        }
      });
  }

  // Event listeners
  loadMoreBtn.addEventListener('click', () => fetchProducts(false));

  let debounceTimer;
  searchInput.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      searchQuery = e.target.value.trim();
      updateFilterPills();
      fetchProducts(true);
    }, 300);
  });

  sortDropdown.addEventListener('change', (e) => {
    sortOption = e.target.value;
    fetchProducts(true);
  });

  // Collection filter buttons
  document.querySelectorAll('.collection-filter-btn').forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      document.querySelectorAll('.collection-filter-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      activeCollectionId = this.dataset.collectionId;
      const url = activeCollectionId === 'all' ? 'shop' : 'shop?collection=' + activeCollectionId;
      window.history.pushState({}, '', url);
      updateFilterPills();
      fetchProducts(true);
    });
  });

  // Listen for currency changes
  document.querySelectorAll('.currency-link').forEach(link => {
    link.addEventListener('click', function() {
      currentCurrency = this.dataset.currency;
    });
  });
});
</script>

<?php include APP_PATH."/views/includes/footer_v2.php"; ?>
