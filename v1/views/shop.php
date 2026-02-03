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
        (SELECT MIN(price) FROM product_price_variants WHERE product_id = p.id) as min_variant_price
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($logo_directory) ?>">
    <link rel="apple-touch-icon-precomposed" type="image/png" sizes="152x152" href="<?= htmlspecialchars($logo_directory) ?>">
    <title><?= htmlspecialchars($site_name) ?> | Shop</title>
    
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
    
    <script>
        // --- CRITICAL: Define Image Handler ---
        function handleImageLoad(imgElement) {
            // 1. Reveal the image (ONLY if it's NOT a hover image)
            if (!imgElement.classList.contains('product-hover-img')) {
                imgElement.classList.remove('opacity-0');
            }
            
            // 2. Find the skeleton container and stop animation
            const container = imgElement.closest('.skeleton-container');
            if (container) {
                container.classList.add('loaded');
            }
        }
    </script>

    <style>
        .collection-filter-btn.active { background-color: #1A1A1A; color: white; border-color: #1A1A1A; }
        
        .aspect-9-16 {
            padding-bottom: calc(100% / (9 / 16));
            position: relative;
            background-color: #f3f4f6;
            overflow: hidden;
        }
        
        .aspect-9-16 > img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            will-change: opacity; 
        }
        
        /* Skeleton loader styles */
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
        
        .skeleton-container.loaded {
            background: none;
            animation: none;
        }
        
        .product-main-img, .product-hover-img {
            transition: opacity 0.4s ease-in-out;
        }
        
        .product-card {
            content-visibility: auto;
            contain-intrinsic-size: 300px 500px;
        }

        /* OUT OF STOCK STYLING */
        /* Grayscale effect for sold out items */
        a.cursor-not-allowed .aspect-9-16 {
            filter: grayscale(100%);
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        a.cursor-not-allowed:hover .aspect-9-16 {
            opacity: 1; /* Slight highlight on hover even if sold out */
        }
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">

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
        <div class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-6">
            <div class="relative w-full sm:w-auto">
                <input type="search" id="product-search" placeholder="Search products..." class="w-full sm:w-64 bg-transparent border-b border-gray-400 py-2 pl-9 pr-2 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text placeholder-brand-gray">
                <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none"><i data-feather="search" class="h-4 w-4 text-brand-gray"></i></div>
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
                        
                        // DISPLAY PRICE LOGIC: Use min_variant_price if available and base price is 0 (or distinct)
                        if (!empty($product['min_variant_price']) && $product['min_variant_price'] > 0) {
                             $displayPrice = $product['min_variant_price'];
                        } else {
                             $displayPrice = $product['price'];
                        }
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
                                     
                                    <img data-src="<?= htmlspecialchars($product['image_two']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?> Hover" 
                                         decoding="async" 
                                         loading="lazy"
                                         class="lazy-img product-hover-img object-cover <?= $isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100' ?>">
                                </div>
                                
                                <?php if ($isSoldOut): ?>
                                    <div class="absolute inset-0 z-10 flex items-center justify-center">
                                        <div class="absolute inset-0 bg-white/40 backdrop-blur-[1px]"></div>
                                        <span class="relative bg-brand-text text-white text-[10px] font-bold uppercase tracking-[0.2em] px-4 py-3 border border-brand-text">
                                            Sold Out
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="pt-4 text-center">
                                <h3 class="text-base font-medium text-brand-text mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                                <div class="text-sm text-brand-gray space-y-1">
                                    <p class="price-display" data-price-ngn="<?= $displayPrice ?>"><?= $formattedPrice ?></p>
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

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>
<script>
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

            fetch(`/fetch-collection?${params.toString()}`)
                .then(res => res.ok ? res.json() : Promise.reject(res))
                .then(products => {
                    if (reset) productGrid.innerHTML = '';
                    
                    if (products.length === 0) {
                        if (reset) {
                            productGrid.innerHTML = `<p class="col-span-full text-center text-brand-gray py-10">No products found matching your criteria.</p>`;
                        }
                        if(loadMoreContainer) loadMoreContainer.classList.add('hidden');
                    } else {
                        products.forEach(product => productGrid.insertAdjacentHTML('beforeend', createProductCard(product)));
                        observeLazyImages();
                        feather.replace();
                        
                        if (reset) {
                            currentOffset = products.length;
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
                
                fetchProducts(true);
            });
        });

        function createProductCard(product) {
            const isSoldOut = !product.stock_quantity || product.stock_quantity <= 0;
            const productSlug = product.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            const displayPrice = product.display_price || product.price;
            const formattedPrice = formatPrice(displayPrice, currentCurrency);

            return `
                <div class="product-card">
                    <a ${!isSoldOut ? `href="/product/${encodeURIComponent(productSlug)}?id=${product.id}"` : ''} class="group block ${isSoldOut ? 'cursor-not-allowed' : ''}">
                        <div class="relative w-full overflow-hidden">
                            <div class="aspect-9-16 skeleton-container">
                                <img data-src="${product.image_one}" 
                                     alt="${product.name}" 
                                     decoding="async"
                                     loading="lazy"
                                     class="lazy-img product-main-img object-cover opacity-0 ${isSoldOut ? '' : 'group-hover:opacity-0'}">
                                <img data-src="${product.image_two}" 
                                     alt="${product.name} Hover" 
                                     decoding="async"
                                     loading="lazy"
                                     class="lazy-img product-hover-img object-cover ${isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100'}">
                            </div>
                            ${isSoldOut ? `
                                <div class="absolute inset-0 z-10 flex items-center justify-center">
                                    <div class="absolute inset-0 bg-white/40 backdrop-blur-[1px]"></div>
                                    <span class="relative bg-brand-text text-white text-[10px] font-bold uppercase tracking-[0.2em] px-4 py-3 border border-brand-text">
                                        Sold Out
                                    </span>
                                </div>
                            ` : ''}
                        </div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text mb-2">${product.name}</h3>
                            <div class="text-sm text-brand-gray space-y-1">
                                <p class="price-display" data-price-ngn="${displayPrice}">${formattedPrice}</p>
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