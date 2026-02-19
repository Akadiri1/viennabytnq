<?php
// --- COLLECTION FILTERING & PERFORMANCE OPTIMIZATION ---
$collection_filter_id = null;
if (isset($_GET['collection']) && filter_var($_GET['collection'], FILTER_VALIDATE_INT)) {
    $collection_filter_id = (int)$_GET['collection'];
}

$conditions = ['visibility' => 'show'];
if ($collection_filter_id) {
    $conditions['collection_id'] = $collection_filter_id;
}

// OPTIMIZATION: Custom Query with Variant Stock Aggregation
$sql = "SELECT p.*, 
        (SELECT SUM(stock_quantity) FROM product_price_variants WHERE product_id = p.id) as variant_total_stock,
        (SELECT MIN(price) FROM product_price_variants WHERE product_id = p.id) as min_variant_price,
        (SELECT MAX(price) FROM product_price_variants WHERE product_id = p.id) as max_variant_price,
        (SELECT COUNT(*) FROM product_price_variants WHERE product_id = p.id) as variant_count
        FROM panel_products p";

$whereClauses = [];
$params = [];

// Apply Visibility Filter
$whereClauses[] = "LOWER(p.visibility) = 'show'";

// Apply Collection Filter
if ($collection_filter_id) {
    $whereClauses[] = "p.collection_id = ?";
    $params[] = $collection_filter_id;
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY p.id DESC LIMIT 6";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$panelProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$productBreadcrumb = selectContent($conn, "product_breadcrumb", ['visibility' => 'show']);
$collections = selectContent($conn, "collections", [], "ORDER BY name ASC");

// Compute global price range for the slider
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

// Count total visible products
$countStmt = $conn->query("SELECT COUNT(*) as total FROM panel_products WHERE LOWER(visibility) = 'show'");
$totalProducts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($logo_directory) ?>">
    <link rel="apple-touch-icon-precomposed" type="image/png" sizes="152x152" href="<?= htmlspecialchars($logo_directory) ?>">
    <title><?= htmlspecialchars($site_name) ?> | Shop</title>

    <!-- CRITICAL: Tailwind MUST load in <head> to prevent FOUC -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', 'brand-red': '#EF4444', },
                    fontFamily: { 'sans': ['Inter', 'ui-sans-serif', 'system-ui'], 'serif': ['Cormorant Garamond', 'serif'], }
                }
            }
        }
    </script>

    <!-- Google Fonts in <head> for immediate availability -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        // --- CRITICAL: Define Image Handler ---
        function handleImageLoad(imgElement) {
            if (!imgElement.classList.contains('product-hover-img')) {
                imgElement.classList.remove('opacity-0');
            }
            const container = imgElement.closest('.skeleton-container');
            if (container) {
                container.classList.add('loaded');
            }
        }
    </script>

    <style>
        /* === PAGE PRELOADER === */
        #page-preloader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: #F9F6F2;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }
        #page-preloader.loaded {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .preloader-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #e5e7eb;
            border-top-color: #1A1A1A;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* === COLLECTION FILTER PILLS === */
        .collection-filter-btn.active { background-color: #1A1A1A; color: white; border-color: #1A1A1A; }
        
        /* === PRODUCT IMAGE ASPECT RATIO === */
        .aspect-9-16 {
            padding-bottom: calc(100% / (9 / 16));
            position: relative;
            background-color: #f3f4f6;
            overflow: hidden;
        }
        .aspect-9-16 > img {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            will-change: opacity; 
        }
        
        /* === SKELETON LOADER === */
        .skeleton-container {
            background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.5s infinite ease-in-out;
            transform: translateZ(0); 
        }
        @keyframes skeleton-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .skeleton-container.loaded { background: none; animation: none; }
        
        .product-main-img, .product-hover-img { transition: opacity 0.4s ease-in-out; }
        
        .product-card {
            content-visibility: auto;
            contain-intrinsic-size: 300px 500px;
            opacity: 0;
            transform: translateY(12px);
            animation: cardFadeIn 0.5s ease forwards;
        }
        .product-card:nth-child(1) { animation-delay: 0.05s; }
        .product-card:nth-child(2) { animation-delay: 0.1s; }
        .product-card:nth-child(3) { animation-delay: 0.15s; }
        .product-card:nth-child(4) { animation-delay: 0.2s; }
        .product-card:nth-child(5) { animation-delay: 0.25s; }
        .product-card:nth-child(6) { animation-delay: 0.3s; }

        @keyframes cardFadeIn {
            to { opacity: 1; transform: translateY(0); }
        }

        /* === SOLD OUT STYLING === */
        a.cursor-not-allowed .aspect-9-16 {
            filter: grayscale(100%) blur(1.5px);
            opacity: 0.9;
            transition: all 0.3s ease;
        }
        a.cursor-not-allowed:hover .aspect-9-16 { opacity: 1; }

        /* === PRICE RANGE SLIDER === */
        .range-slider {
            position: relative;
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            margin: 16px 0 8px;
        }
        .range-slider .track-fill {
            position: absolute;
            height: 100%;
            background: #1A1A1A;
            border-radius: 3px;
            pointer-events: none;
        }
        .range-slider input[type="range"] {
            position: absolute;
            width: 100%;
            height: 6px;
            top: 0;
            margin: 0;
            -webkit-appearance: none;
            appearance: none;
            background: transparent;
            pointer-events: none;
            outline: none;
        }
        .range-slider input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: #1A1A1A;
            border: 2px solid white;
            border-radius: 50%;
            cursor: pointer;
            pointer-events: auto;
            box-shadow: 0 1px 4px rgba(0,0,0,0.2);
            transition: transform 0.15s ease;
        }
        .range-slider input[type="range"]::-webkit-slider-thumb:hover {
            transform: scale(1.2);
        }
        .range-slider input[type="range"]::-moz-range-thumb {
            width: 18px;
            height: 18px;
            background: #1A1A1A;
            border: 2px solid white;
            border-radius: 50%;
            cursor: pointer;
            pointer-events: auto;
            box-shadow: 0 1px 4px rgba(0,0,0,0.2);
        }

        /* === FILTER PILLS === */
        .filter-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: #1A1A1A;
            color: white;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .filter-pill:hover { background: #333; }
        .filter-pill .pill-close {
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.15s;
        }
        .filter-pill .pill-close:hover { opacity: 1; }

        /* === "FROM" PRICE LABEL === */
        .price-from {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9CA3AF;
            margin-right: 2px;
        }

        /* === FILTER PANEL TOGGLE === */
        .filter-panel { 
            max-height: 0; 
            overflow: hidden; 
            transition: max-height 0.3s ease, opacity 0.3s ease;
            opacity: 0;
        }
        .filter-panel.open { 
            max-height: 200px; 
            opacity: 1; 
        }
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">

<!-- Page Preloader -->
<div id="page-preloader">
    <div class="preloader-spinner"></div>
</div>

<div id="sidebar" class="fixed inset-0 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out" aria-labelledby="sidebar-title">
    <div id="sidebar-overlay" class="absolute inset-0 bg-black/40"></div>
    <div class="relative w-80 h-full bg-white shadow-2xl flex flex-col">
        <div class="p-6 flex justify-between items-center border-b border-gray-200">
            <h2 id="sidebar-title" class="text-2xl font-serif font-semibold">Menu</h2>
            <button id="close-sidebar-btn" class="p-2 text-brand-gray hover:text-brand-text"><i data-feather="x" class="h-6 w-6"></i></button>
        </div>
        <nav class="flex-grow p-6">
            <ul class="space-y-4">
                <li><a href="/home" class="flex items-center p-3 text-base font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="home" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Home</span></a></li>
                <li><a href="/shop" class="flex items-center p-3 text-base font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="shopping-bag" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Recent Products</span></a></li>
                <li><a href="/about" class="flex items-center p-3 text-base font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="info" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">About Us</span></a></li>
                <li><a href="/register" class="flex items-center p-3 text-base font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="user" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Login / Register</span></a></li>
                <li><a href="/privacy" class="flex items-center p-3 text-base font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="truck" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Shipping Policy</span></a></li>
            </ul>
        </nav>
        <div class="p-6 border-t border-gray-200"><p class="text-xs text-brand-gray text-center">© <?=date('Y')?> <?= htmlspecialchars($site_name) ?></p></div>
    </div>
</div>

<header class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex-1 flex justify-start"><button id="open-sidebar-btn" class="p-2 text-brand-text hover:text-brand-gray"><i data-feather="menu" class="h-6 w-6"></i></button></div>
            <div class="flex-shrink-0 text-center"><a href="/home"><div class="text-1xl font-serif font-bold tracking-widest"><?= htmlspecialchars($site_name) ?></div></a></div>
            <div class="flex-1 flex items-center justify-end space-x-4"></div>
        </div>
    </div>
</header>

<section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($productBreadcrumb[0]['input_image'] ?? '') ?>');" role="img" aria-label="Shop Header Banner"><div class="absolute inset-0 bg-black/30"></div><div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4"><nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb"><ol class="list-none p-0 inline-flex"><li class="flex items-center"><a href="/home" class="hover:underline">Home</a><i data-feather="chevron-right" class="h-4 w-4 mx-2"></i></li><li><span>Shop</span></li></ol></nav><h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4"><?= htmlspecialchars($productBreadcrumb[0]['input_title'] ?? 'Our Collection') ?></h1></div></section>

<main class="bg-brand-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">

        <!-- TOP BAR: Search + Sort + Filter Toggle -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-6">
            <div class="flex items-center gap-4 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-none">
                    <input type="search" id="product-search" placeholder="Search products..." class="w-full sm:w-64 bg-transparent border-b border-gray-400 py-2 pl-9 pr-2 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text placeholder-brand-gray">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none"><i data-feather="search" class="h-4 w-4 text-brand-gray"></i></div>
                </div>
                <button id="toggle-filters-btn" class="flex items-center gap-2 text-sm font-medium text-brand-gray hover:text-brand-text transition-colors py-2 px-3 border border-gray-300 rounded-lg hover:border-brand-text">
                    <i data-feather="sliders" class="h-4 w-4"></i>
                    <span>Filters</span>
                </button>
            </div>
            <div class="flex items-center space-x-3">
                <label for="sort-filter-dropdown" class="text-sm font-medium text-brand-gray whitespace-nowrap">Sort by:</label>
                <select id="sort-filter-dropdown" class="bg-transparent border-b border-gray-400 py-2 px-1 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text">
                    <option value="featured">Featured</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                </select>
            </div>
        </div>

        <!-- FILTER PANEL (collapsible) -->
        <div id="filter-panel" class="filter-panel mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex flex-col sm:flex-row gap-8">
                    <!-- Price Range -->
                    <div class="flex-1">
                        <h4 class="text-xs font-bold text-brand-gray uppercase tracking-widest mb-2">Price Range</h4>
                        <div class="range-slider">
                            <div id="track-fill" class="track-fill"></div>
                            <input type="range" id="price-min" min="<?= $globalMin ?>" max="<?= $globalMax ?>" value="<?= $globalMin ?>" step="1000">
                            <input type="range" id="price-max" min="<?= $globalMin ?>" max="<?= $globalMax ?>" value="<?= $globalMax ?>" step="1000">
                        </div>
                        <div class="flex justify-between text-sm font-medium text-brand-text mt-1">
                            <span id="price-min-label">₦<?= number_format($globalMin) ?></span>
                            <span id="price-max-label">₦<?= number_format($globalMax) ?></span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end mt-4 gap-3">
                    <button id="clear-price-filter" class="text-xs font-medium text-brand-gray hover:text-brand-text transition-colors underline">Clear</button>
                    <button id="apply-price-filter" class="text-xs font-semibold bg-brand-text text-white px-5 py-2 rounded-lg hover:bg-gray-800 transition-colors">Apply Filter</button>
                </div>
            </div>
        </div>

        <!-- ACTIVE FILTER PILLS + PRODUCT COUNT -->
        <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
            <div id="active-filters" class="flex flex-wrap items-center gap-2">
                <!-- pills inserted by JS -->
            </div>
            <p id="product-count" class="text-sm text-brand-gray">
                <span id="product-count-number" class="font-semibold text-brand-text"><?= count($panelProducts) ?></span> product<?= count($panelProducts) !== 1 ? 's' : '' ?>
            </p>
        </div>

        <!-- COLLECTIONS -->
        <div class="mb-12">
            <h2 class="text-center text-2xl font-serif font-semibold mb-6">Our Collections</h2>
            <div id="collections-container" class="flex flex-wrap justify-center gap-3">
                <a href="/shop" class="collection-filter-btn active text-sm font-medium py-2 px-5 rounded-full border border-gray-300 transition-colors duration-200 hover:bg-gray-200" data-collection-id="all">All Products</a>
                <?php foreach ($collections as $collection): ?>
                    <button class="collection-filter-btn text-sm font-medium py-2 px-5 rounded-full border border-gray-300 transition-colors duration-200 hover:bg-gray-200" data-collection-id="<?= htmlspecialchars($collection['id']) ?>">
                        <?= htmlspecialchars($collection['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PRODUCT GRID -->
        <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-12">
            <?php if (empty($panelProducts)): ?>
                <p class="col-span-full text-center text-brand-gray py-10">No products found in this collection.</p>
            <?php else: ?>
                <?php foreach ($panelProducts as $product): ?>
                    <?php 
                        // EFFECTIVE STOCK LOGIC
                        if (isset($product['variant_total_stock']) && $product['variant_total_stock'] !== null) {
                            $stock = (int)$product['variant_total_stock'];
                        } else {
                            $stock = (int)($product['stock_quantity'] ?? 0);
                        }
                        $isSoldOut = ($stock <= 0);

                        $productSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $product['name']), '-'));
                        
                        // DISPLAY PRICE LOGIC: Use min_variant_price if available
                        if (!empty($product['min_variant_price']) && $product['min_variant_price'] > 0) {
                             $displayPrice = $product['min_variant_price'];
                        } else {
                             $displayPrice = $product['price'];
                        }
                        $hasMultipleVariants = (int)($product['variant_count'] ?? 0) > 1;
                        $hasVariantPriceRange = $hasMultipleVariants && ($product['min_variant_price'] != $product['max_variant_price']);
                        $formattedPrice = '₦' . number_format($displayPrice, 2);
                    ?>
                    <div class="product-card">
                        <a <?php if (!$isSoldOut): ?>href="/product/<?= urlencode($productSlug) ?>?id=<?= urlencode($product['id']) ?>"<?php endif; ?> class="group block <?= $isSoldOut ? 'cursor-not-allowed' : '' ?>">
                            <div class="relative w-full overflow-hidden">
                                
                                <div class="aspect-9-16 skeleton-container">
                                    <img data-src="<?= htmlspecialchars($product['image_one']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         decoding="async" 
                                         loading="lazy"
                                         class="lazy-img product-main-img object-cover opacity-0 <?= $isSoldOut ? '' : 'group-hover:opacity-0' ?>">
                                     
                                    <?php if (!$isSoldOut): ?>
                                    <img data-hover-src="<?= htmlspecialchars($product['image_two']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?> Hover" 
                                         decoding="async" 
                                         class="product-hover-img object-cover opacity-0 group-hover:opacity-100">
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($isSoldOut): ?>
                                    <div class="absolute inset-0 z-10 flex items-center justify-center">
                                        <span class="bg-[#1a1a1a] text-white text-xs font-bold uppercase tracking-[0.2em] px-6 py-3 shadow-xl">
                                            Sold Out
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="pt-4 text-center">
                                <h3 class="text-base font-medium text-brand-text mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                                <div class="text-sm text-brand-gray space-y-1">
                                    <p class="price-display" data-price-ngn="<?= $displayPrice ?>">
                                        <?php if ($hasVariantPriceRange): ?><span class="price-from">From</span><?php endif; ?>
                                        <?= $formattedPrice ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Load More Section -->
        <div id="load-more-container" class="flex justify-center pt-16 pb-8 <?= count($panelProducts) < 6 ? 'hidden' : '' ?>">
            <button id="load-more-btn" class="px-8 py-3 bg-white border border-brand-text text-brand-text text-sm font-medium hover:bg-gray-100 hover:text-black transition-colors duration-300 uppercase tracking-widest">
                Load More
            </button>
            <div id="load-more-spinner" class="hidden w-8 h-8 border-4 border-gray-200 border-t-brand-text rounded-full animate-spin"></div>
        </div>
        
        <!-- Pagination Nav (Hidden by default as we use Load More) -->
        <nav id="pagination" class="hidden" aria-label="Pagination"></nav>
    </div>
</main>
<footer class="bg-white border-t border-gray-200"><div class="bg-gray-50 text-center py-4 text-brand-gray text-xs"><p>© <?=date('Y')?> <?= htmlspecialchars($site_name) ?>. All Rights Reserved.</p></div></footer>

<script src="https://unpkg.com/feather-icons"></script>
<script>
    // Dismiss preloader as soon as DOM is interactive
    document.addEventListener('DOMContentLoaded', () => {
        const preloader = document.getElementById('page-preloader');
        if (preloader) {
            // Small delay to let paint settle
            setTimeout(() => preloader.classList.add('loaded'), 150);
            // Remove from DOM after transition
            preloader.addEventListener('transitionend', () => preloader.remove());
        }
    });

    feather.replace();
    
    // --- SIDEBAR LOGIC ---
    const openSidebarBtn = document.getElementById('open-sidebar-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    if(openSidebarBtn) openSidebarBtn.addEventListener('click', () => sidebar.classList.remove('-translate-x-full'));
    if(closeSidebarBtn) closeSidebarBtn.addEventListener('click', () => sidebar.classList.add('-translate-x-full'));
    if(sidebarOverlay) sidebarOverlay.addEventListener('click', () => sidebar.classList.add('-translate-x-full'));

    document.addEventListener('DOMContentLoaded', function () {
        
        // --- LAZY LOADING ---
        const lazyImageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.onload = function() { handleImageLoad(this); };
                        if(img.complete) handleImageLoad(img);
                    }
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '200px 0px', threshold: 0.01 });

        function observeLazyImages() {
            document.querySelectorAll('.lazy-img').forEach(img => {
                if (img.dataset.src) lazyImageObserver.observe(img);
            });
        }
        observeLazyImages();

        // --- HOVER IMAGE DEFERRAL ---
        // Only load hover images when user first hovers on a product card
        function initHoverImages() {
            document.querySelectorAll('.product-card').forEach(card => {
                if (card.dataset.hoverInit) return; // already initialized
                card.dataset.hoverInit = '1';
                card.addEventListener('mouseenter', function loadHover() {
                    const hoverImg = this.querySelector('img[data-hover-src]');
                    if (hoverImg && !hoverImg.src) {
                        hoverImg.src = hoverImg.dataset.hoverSrc;
                        hoverImg.removeAttribute('data-hover-src');
                    }
                    // Only need to load once
                    this.removeEventListener('mouseenter', loadHover);
                }, { once: true });
            });
        }
        initHoverImages();

        // --- PRICE RANGE SLIDER ---
        const priceMinInput = document.getElementById('price-min');
        const priceMaxInput = document.getElementById('price-max');
        const priceMinLabel = document.getElementById('price-min-label');
        const priceMaxLabel = document.getElementById('price-max-label');
        const trackFill = document.getElementById('track-fill');
        const globalMin = <?= $globalMin ?>;
        const globalMax = <?= $globalMax ?>;
        let activePriceMin = null;
        let activePriceMax = null;

        function updateRangeTrack() {
            const min = parseInt(priceMinInput.value);
            const max = parseInt(priceMaxInput.value);
            const range = globalMax - globalMin;
            const leftPct = ((min - globalMin) / range) * 100;
            const rightPct = ((max - globalMin) / range) * 100;
            trackFill.style.left = leftPct + '%';
            trackFill.style.width = (rightPct - leftPct) + '%';
            priceMinLabel.textContent = '₦' + min.toLocaleString();
            priceMaxLabel.textContent = '₦' + max.toLocaleString();
        }

        priceMinInput.addEventListener('input', () => {
            if (parseInt(priceMinInput.value) > parseInt(priceMaxInput.value)) {
                priceMinInput.value = priceMaxInput.value;
            }
            updateRangeTrack();
        });
        priceMaxInput.addEventListener('input', () => {
            if (parseInt(priceMaxInput.value) < parseInt(priceMinInput.value)) {
                priceMaxInput.value = priceMinInput.value;
            }
            updateRangeTrack();
        });
        updateRangeTrack();

        // --- FILTER PANEL TOGGLE ---
        const toggleFiltersBtn = document.getElementById('toggle-filters-btn');
        const filterPanel = document.getElementById('filter-panel');

        toggleFiltersBtn.addEventListener('click', () => {
            filterPanel.classList.toggle('open');
            const icon = toggleFiltersBtn.querySelector('span');
            if (filterPanel.classList.contains('open')) {
                icon.textContent = 'Close';
            } else {
                icon.textContent = 'Filters';
            }
        });

        // --- APPLY / CLEAR PRICE FILTER ---
        document.getElementById('apply-price-filter').addEventListener('click', () => {
            activePriceMin = parseInt(priceMinInput.value);
            activePriceMax = parseInt(priceMaxInput.value);
            updateFilterPills();
            fetchProducts(true);
            filterPanel.classList.remove('open');
            toggleFiltersBtn.querySelector('span').textContent = 'Filters';
        });

        document.getElementById('clear-price-filter').addEventListener('click', () => {
            priceMinInput.value = globalMin;
            priceMaxInput.value = globalMax;
            updateRangeTrack();
            activePriceMin = null;
            activePriceMax = null;
            updateFilterPills();
            fetchProducts(true);
        });

        // --- ACTIVE FILTER PILLS ---
        const activeFiltersContainer = document.getElementById('active-filters');

        function updateFilterPills() {
            activeFiltersContainer.innerHTML = '';
            // Price range pill
            if (activePriceMin !== null && activePriceMax !== null && 
                (activePriceMin > globalMin || activePriceMax < globalMax)) {
                const pill = document.createElement('div');
                pill.className = 'filter-pill';
                pill.innerHTML = `₦${activePriceMin.toLocaleString()} – ₦${activePriceMax.toLocaleString()} <span class="pill-close" data-filter="price">✕</span>`;
                pill.querySelector('.pill-close').addEventListener('click', () => {
                    activePriceMin = null;
                    activePriceMax = null;
                    priceMinInput.value = globalMin;
                    priceMaxInput.value = globalMax;
                    updateRangeTrack();
                    updateFilterPills();
                    fetchProducts(true);
                });
                activeFiltersContainer.appendChild(pill);
            }
            // Collection pill
            if (activeCollectionId !== 'all') {
                const activeBtn = document.querySelector(`.collection-filter-btn[data-collection-id="${activeCollectionId}"]`);
                if (activeBtn) {
                    const pill = document.createElement('div');
                    pill.className = 'filter-pill';
                    pill.innerHTML = `${activeBtn.textContent.trim()} <span class="pill-close" data-filter="collection">✕</span>`;
                    pill.querySelector('.pill-close').addEventListener('click', () => {
                        document.querySelectorAll('.collection-filter-btn').forEach(b => b.classList.remove('active'));
                        document.querySelector('.collection-filter-btn[data-collection-id="all"]').classList.add('active');
                        activeCollectionId = 'all';
                        window.history.pushState({path:'shop'},'','shop');
                        updateFilterPills();
                        fetchProducts(true);
                    });
                    activeFiltersContainer.appendChild(pill);
                }
            }
            // Search pill
            if (searchQuery) {
                const pill = document.createElement('div');
                pill.className = 'filter-pill';
                pill.innerHTML = `"${searchQuery}" <span class="pill-close" data-filter="search">✕</span>`;
                pill.querySelector('.pill-close').addEventListener('click', () => {
                    searchQuery = '';
                    searchInput.value = '';
                    updateFilterPills();
                    fetchProducts(true);
                });
                activeFiltersContainer.appendChild(pill);
            }
        }

        // --- PRODUCT COUNT ---
        const productCountNumber = document.getElementById('product-count-number');
        const productCountEl = document.getElementById('product-count');

        function updateProductCount(count) {
            productCountNumber.textContent = count;
            productCountEl.innerHTML = `<span id="product-count-number" class="font-semibold text-brand-text">${count}</span> product${count !== 1 ? 's' : ''}`;
        }

        // --- LOAD MORE & FILTERING SYSTEM ---
        
        let activeCollectionId = '<?= $collection_filter_id ?: "all" ?>';
        let currentOffset = 6; 
        const LOAD_STEP = 6;   
        const INITIAL_LIMIT = 6;
        
        // Currency
        const USD_RATE = 1450; 
        const currencySwitcher = document.querySelector('.currency-switcher a.active');
        let currentCurrency = currencySwitcher ? (currencySwitcher.dataset.currency || 'NGN') : 'NGN';

        function formatPrice(amount, currency) {
            if (currency === 'USD') {
                return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount / USD_RATE);
            }
            return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount).replace('NGN', '₦');
        }

        // Elements
        const searchInput = document.getElementById('product-search');
        const sortDropdown = document.getElementById('sort-filter-dropdown');
        const productGrid = document.getElementById('product-grid');
        const loadMoreContainer = document.getElementById('load-more-container');
        const loadMoreBtn = document.getElementById('load-more-btn');
        const loadMoreSpinner = document.getElementById('load-more-spinner');

        let searchQuery = '';
        let sortOption = 'featured';
        let runningProductCount = <?= count($panelProducts) ?>;

        // Fetch Function
        function fetchProducts(reset = false) {
            if (reset) {
                currentOffset = 0;
                productGrid.innerHTML = `<div class="col-span-full flex justify-center py-20"><div class="w-12 h-12 border-4 border-gray-200 border-t-brand-text rounded-full animate-spin"></div></div>`;
                if(loadMoreContainer) loadMoreContainer.classList.add('hidden');
            } else {
                if(loadMoreBtn) loadMoreBtn.classList.add('hidden');
                if(loadMoreSpinner) loadMoreSpinner.classList.remove('hidden');
            }

            const limit = reset ? INITIAL_LIMIT : LOAD_STEP;
            const params = new URLSearchParams({
                id: activeCollectionId,
                offset: currentOffset,
                limit: limit,
                search: searchQuery,
                sort: sortOption
            });

            // Add price filter params if active
            if (activePriceMin !== null) params.set('min_price', activePriceMin);
            if (activePriceMax !== null) params.set('max_price', activePriceMax);

            fetch(`/fetch-collection?${params.toString()}`)
                .then(res => res.ok ? res.json() : Promise.reject(res))
                .then(data => {
                    // Support both array (old) and object with products/total_count (new)
                    let products, totalCount;
                    if (Array.isArray(data)) {
                        products = data;
                        totalCount = null;
                    } else {
                        products = data.products || [];
                        totalCount = data.total_count ?? null;
                    }

                    if (reset) productGrid.innerHTML = '';
                    
                    if (products.length === 0) {
                        if (reset) {
                            productGrid.innerHTML = `<p class="col-span-full text-center text-brand-gray py-10">No products found matching your criteria.</p>`;
                            runningProductCount = 0;
                        }
                        if(loadMoreContainer) loadMoreContainer.classList.add('hidden');
                    } else {
                        products.forEach(product => productGrid.insertAdjacentHTML('beforeend', createProductCard(product)));
                        observeLazyImages();
                        feather.replace();
                        initHoverImages();
                        
                        if (reset) {
                            currentOffset = products.length;
                            runningProductCount = totalCount !== null ? totalCount : products.length;
                        } else {
                            currentOffset += products.length;
                        }

                        if(loadMoreContainer && loadMoreBtn && loadMoreSpinner) {
                            if (products.length < limit) {
                                loadMoreContainer.classList.add('hidden');
                            } else {
                                loadMoreContainer.classList.remove('hidden');
                                loadMoreBtn.classList.remove('hidden');
                                loadMoreSpinner.classList.add('hidden');
                            }
                        }
                    }

                    // Update count
                    if (totalCount !== null) {
                        updateProductCount(totalCount);
                    } else {
                        updateProductCount(runningProductCount);
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (reset) productGrid.innerHTML = `<p class="col-span-full text-center text-brand-red py-10">Failed to load products.</p>`;
                })
                .finally(() => {
                    if (!reset && loadMoreContainer && !loadMoreContainer.classList.contains('hidden')) {
                         if(loadMoreBtn) loadMoreBtn.classList.remove('hidden');
                         if(loadMoreSpinner) loadMoreSpinner.classList.add('hidden');
                    }
                });
        }

        // Listeners
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => fetchProducts(false));
        }

        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    searchQuery = e.target.value.trim();
                    updateFilterPills();
                    fetchProducts(true);
                }, 300);
            });
        }

        if (sortDropdown) {
            sortDropdown.addEventListener('change', (e) => {
                sortOption = e.target.value;
                fetchProducts(true);
            });
        }

        document.querySelectorAll('.collection-filter-btn').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.collection-filter-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                activeCollectionId = this.dataset.collectionId;
                
                const url = activeCollectionId === 'all' ? 'shop' : `shop?collection=${activeCollectionId}`;
                window.history.pushState({path:url},'',url);
                
                updateFilterPills();
                fetchProducts(true);
            });
        });

        function createProductCard(product) {
            const isSoldOut = !product.stock_quantity || product.stock_quantity <= 0;
            const productSlug = product.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            const displayPrice = product.display_price || product.price;
            const formattedPrice = formatPrice(displayPrice, currentCurrency);
            const hasVariantRange = product.price_variants && product.price_variants.length > 1;
            const fromPrefix = hasVariantRange ? '<span class="price-from">From</span> ' : '';

            return `
                <div class="product-card" style="animation-delay:0s; opacity:0; transform:translateY(12px); animation: cardFadeIn 0.5s ease forwards;">
                    <a ${!isSoldOut ? `href="/product/${encodeURIComponent(productSlug)}?id=${product.id}"` : ''} class="group block ${isSoldOut ? 'cursor-not-allowed' : ''}">
                        <div class="relative w-full overflow-hidden">
                            <div class="aspect-9-16 skeleton-container">
                                <img data-src="${product.image_one}" 
                                     alt="${product.name}" 
                                     decoding="async"
                                     loading="lazy"
                                     class="lazy-img product-main-img object-cover opacity-0 ${isSoldOut ? '' : 'group-hover:opacity-0'}">
                                ${!isSoldOut ? `<img data-hover-src="${product.image_two}" 
                                     alt="${product.name} Hover" 
                                     decoding="async"
                                     class="product-hover-img object-cover opacity-0 group-hover:opacity-100">` : ''}
                            </div>
                            ${isSoldOut ? `
                                <div class="absolute inset-0 z-10 flex items-center justify-center">
                                    <span class="bg-[#1a1a1a] text-white text-xs font-bold uppercase tracking-[0.2em] px-6 py-3 shadow-xl">
                                        Sold Out
                                    </span>
                                </div>
                            ` : ''}
                        </div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text mb-2">${product.name}</h3>
                            <div class="text-sm text-brand-gray space-y-1">
                                <p class="price-display" data-price-ngn="${displayPrice}">${fromPrefix}${formattedPrice}</p>
                            </div>
                        </div>
                    </a>
                </div>
            `;
        }
    });

</script>
</body>
</html>