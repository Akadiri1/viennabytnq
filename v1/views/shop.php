<?php
// --- COLLECTION FILTERING & PERFORMANCE OPTIMIZATION ---
$collection_filter_id = null;
if (isset($_GET['collection']) && filter_var($_GET['collection'], FILTER_VALIDATE_INT)) {
    $collection_filter_id = (int)$_GET['collection'];
}
// --- CURRENCY CONFIGURATION ---
// REMOVED: All currency-related logic (session, cache, API calls, USD_EXCHANGE_RATE, current_currency)

// --- COLLECTION FILTERING & PERFORMANCE OPTIMIZATION ---
$collection_filter_id = null;
if (isset($_GET['collection']) && filter_var($_GET['collection'], FILTER_VALIDATE_INT)) {
    $collection_filter_id = (int)$_GET['collection'];
}
$conditions = ['visibility' => 'show'];
if ($collection_filter_id) {
    $conditions['collection_id'] = $collection_filter_id;
}
// Limit to a reasonable number of products per page, consider pagination if there are many more.
// For now, keeping the limit at 50 as per original.
$panelProducts = array_slice(selectContent($conn, "panel_products", $conditions, "ORDER BY id DESC"), 0, 50);

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
    
    <style>
        /* Keep critical CSS here for faster rendering */
        .collection-filter-btn.active { background-color: #1A1A1A; color: white; border-color: #1A1A1A; }
        /* Image aspect ratio helper */
        .aspect-9-16 {
            padding-bottom: calc(100% / (9 / 16)); /* 16:9 aspect ratio */
            position: relative;
        }
        .aspect-9-16 > img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">

<div id="sidebar" class="fixed inset-0 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out" aria-labelledby="sidebar-title">
    <div id="sidebar-overlay" class="absolute inset-0 bg-black/40"></div>
    <div class="relative w-80 h-full bg-brand-bg shadow-2xl flex flex-col">
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
            <div class="flex-1 flex items-center justify-end space-x-4">
            </div>
        </div>
    </div>
</header>
<section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($productBreadcrumb[0]['input_image'] ?? '') ?>');" role="img" aria-label="Shop Header Banner"><div class="absolute inset-0 bg-black/30"></div><div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4"><nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb"><ol class="list-none p-0 inline-flex"><li class="flex items-center"><a href="/home" class="hover:underline">Home</a><i data-feather="chevron-right" class="h-4 w-4 mx-2"></i></li><li><span>Shop</span></li></ol></nav><h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4"><?= htmlspecialchars($productBreadcrumb[0]['input_title'] ?? 'Our Collection') ?></h1></div></section>

<main class="bg-brand-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-6"><div class="relative w-full sm:w-auto"><input type="search" id="product-search" placeholder="Search products..." class="w-full sm:w-64 bg-transparent border-b border-gray-400 py-2 pl-9 pr-2 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text placeholder-brand-gray"><div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none"><i data-feather="search" class="h-4 w-4 text-brand-gray"></i></div></div><div class="flex items-center space-x-3"><label for="sort-filter-dropdown" class="text-sm font-medium text-brand-gray whitespace-nowrap">Sort by:</label><select id="sort-filter-dropdown" class="bg-transparent border-b border-gray-400 py-2 px-1 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text"><option value="featured">Featured</option><option value="price-asc">Price: Low to High</option><option value="price-desc">Price: High to Low</option></select></div></div>
        <div class="mb-12"><h2 class="text-center text-2xl font-serif font-semibold mb-6">Our Collections</h2><div id="collections-container" class="flex flex-wrap justify-center gap-3"><a href="/shop" class="collection-filter-btn active text-sm font-medium py-2 px-5 rounded-full border border-gray-300 transition-colors duration-200 hover:bg-gray-200" data-collection-id="all">All Products</a><?php foreach ($collections as $collection): ?><button class="collection-filter-btn text-sm font-medium py-2 px-5 rounded-full border border-gray-300 transition-colors duration-200 hover:bg-gray-200" data-collection-id="<?= htmlspecialchars($collection['id']) ?>"><?= htmlspecialchars($collection['name']) ?></button><?php endforeach; ?></div></div>

        <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-12">
            <?php if (empty($panelProducts)): ?>
                <p class="col-span-full text-center text-brand-gray py-10">No products found in this collection.</p>
            <?php else: ?>
                <?php foreach ($panelProducts as $product): ?>
                    <?php
                        $isSoldOut = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0);
                        // REMOVED: $displayPrice logic as price is no longer displayed
                    ?>
                    <div class="product-card">
                        <a <?php if (!$isSoldOut): ?>href="shopdetail?id=<?= urlencode($product['id']) ?>"<?php endif; ?> class="group block <?= $isSoldOut ? 'cursor-not-allowed' : '' ?>">
                            <div class="relative w-full overflow-hidden">
                                <div class="aspect-9-16">
                                    <img src="<?= htmlspecialchars($product['image_one']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="object-cover transition-opacity duration-300 <?= $isSoldOut ? 'opacity-100' : 'opacity-100 group-hover:opacity-0' ?>" loading="lazy">
                                    <img src="<?= htmlspecialchars($product['image_two']) ?>" alt="<?= htmlspecialchars($product['name']) ?> Hover" class="object-cover transition-opacity duration-300 <?= $isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100' ?>" loading="lazy">
                                </div>
                                <?php if ($isSoldOut): ?><div class="absolute inset-0 bg-black/50 flex items-center justify-center"><span class="bg-white text-brand-text text-xs font-semibold tracking-wider uppercase px-4 py-2">Sold Out</span></div><?php endif; ?>
                            </div>
                            <div class="pt-4 text-center">
                                <h3 class="text-base font-medium text-brand-text mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <nav id="pagination" class="flex items-center justify-center pt-24 space-x-2" aria-label="Pagination"></nav>
    </div>
</main>
<footer class="bg-white border-t border-gray-200"><div class="bg-gray-50 text-center py-4 text-brand-gray text-xs"><p>© <?=date('Y')?> <?= htmlspecialchars($site_name) ?>. All Rights Reserved.</p></div></footer>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>
<script>
    feather.replace();
    const openSidebarBtn = document.getElementById('open-sidebar-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    openSidebarBtn.addEventListener('click', () => sidebar.classList.remove('-translate-x-full'));
    closeSidebarBtn.addEventListener('click', () => sidebar.classList.add('-translate-x-full'));
    sidebarOverlay.addEventListener('click', () => sidebar.classList.add('-translate-x-full'));

    document.addEventListener('DOMContentLoaded', function () {
        
        // REVISED: Simplified to remove any price related data attributes
        function createProductCard(product) {
            const isSoldOut = !product.stock_quantity || product.stock_quantity <= 0;
            
            // REMOVED: Price display HTML and logic
            // NOTE: 'loading="lazy"' has been added to the image tags below for lazy loading new products
            const productHTML = `
                <div class="product-card">
                    <a ${!isSoldOut ? `href="shopdetail?id=${product.id}"` : ''} class="group block ${isSoldOut ? 'cursor-not-allowed' : ''}">
                        <div class="relative w-full overflow-hidden">
                            <div class="aspect-9-16">
                                <img src="${product.image_one}" alt="${product.name}" class="object-cover transition-opacity duration-300 ${isSoldOut ? 'opacity-100' : 'opacity-100 group-hover:opacity-0'}" loading="lazy">
                                <img src="${product.image_two}" alt="${product.name} Hover" class="object-cover transition-opacity duration-300 ${isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100'}" loading="lazy">
                            </div>
                            ${isSoldOut ? `
                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                    <span class="bg-white text-brand-text text-xs font-semibold tracking-wider uppercase px-4 py-2">Sold Out</span>
                                </div>` : ''}
                        </div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text mb-2">${product.name}</h3>
                        </div>
                    </a>
                </div>
            `;
            return productHTML;
        }
        
        const activeCollectionId = '<?= $collection_filter_id ?: "all" ?>';
        document.querySelectorAll('.collection-filter-btn').forEach(btn => btn.classList.remove('active'));
        const buttonToActivate = document.querySelector(`.collection-filter-btn[data-collection-id="${activeCollectionId}"]`);
        if (buttonToActivate) { buttonToActivate.classList.add('active'); }
        const collectionButtons = document.querySelectorAll('.collection-filter-btn');
        const productGrid = document.getElementById('product-grid');
        const paginationNav = document.getElementById('pagination');

        collectionButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                const collectionId = this.dataset.collectionId;
                if (collectionId === 'all') { // If "All Products" is clicked, allow full page reload to clear params
                    window.location.href = '/shop'; 
                } else {
                    e.preventDefault();
                    // Update URL without full reload for filtering
                    window.history.pushState({path:`shop?collection=${collectionId}`},'',`shop?collection=${collectionId}`);
                    document.querySelectorAll('.collection-filter-btn').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    productGrid.innerHTML = `<p class="col-span-full text-center text-brand-gray py-10">Loading products...</p>`;
                    paginationNav.style.display = 'none'; // Hide pagination during loading
                    fetch(`/fetch-collection?id=${collectionId}`) // Ensure this endpoint exists and returns JSON
                        .then(response => {
                            if (!response.ok) {
                                // Handle HTTP errors
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(products => {
                            productGrid.innerHTML = ''; // Clear existing products
                            if (products.length === 0) {
                                productGrid.innerHTML = `<p class="col-span-full text-center text-brand-gray py-10">No products found in this collection.</p>`;
                                return;
                            }
                            products.forEach(product => {
                                const productCardHTML = createProductCard(product);
                                productGrid.insertAdjacentHTML('beforeend', productCardHTML);
                            });
                            // Re-initialize Feather Icons for newly added elements
                            feather.replace(); 
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            productGrid.innerHTML = `<p class="col-span-full text-center text-brand-red py-10">Failed to load products. Please try again.</p>`;
                        });
                }
            });
        });
    });
</script>
</body>
</html>
$conditions = ['visibility' => 'show'];
if ($collection_filter_id) {
    $conditions['collection_id'] = $collection_filter_id;
}
// Limit to a reasonable number of products per page, consider pagination if there are many more.
// For now, keeping the limit at 50 as per original.
$panelProducts = array_slice(selectContent($conn, "panel_products", $conditions, "ORDER BY id DESC"), 0, 50);

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* Keep critical CSS here for faster rendering, or link it */
        .collection-filter-btn.active { background-color: #1A1A1A; color: white; border-color: #1A1A1A; }
        /* REMOVED: Currency switcher styles */
        /* Image aspect ratio helper */
        .aspect-9-16 {
            padding-bottom: calc(100% / (9 / 16)); /* 16:9 aspect ratio */
            position: relative;
        }
        .aspect-9-16 > img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
<div id="sidebar" class="fixed inset-0 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out" aria-labelledby="sidebar-title">
    <div id="sidebar-overlay" class="absolute inset-0 bg-black/40"></div>
    <div class="relative w-80 h-full bg-brand-bg shadow-2xl flex flex-col">
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
            <div class="flex-1 flex items-center justify-end space-x-4">
                </div>
        </div>
    </div>
</header>
<section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($productBreadcrumb[0]['input_image'] ?? '') ?>');"><div class="absolute inset-0 bg-black/30"></div><div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4"><nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb"><ol class="list-none p-0 inline-flex"><li class="flex items-center"><a href="/home" class="hover:underline">Home</a><i data-feather="chevron-right" class="h-4 w-4 mx-2"></i></li><li><span>Shop</span></li></ol></nav><h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4"><?= htmlspecialchars($productBreadcrumb[0]['input_title'] ?? 'Our Collection') ?></h1></div></section>

<main class="bg-brand-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-6"><div class="relative w-full sm:w-auto"><input type="search" id="product-search" placeholder="Search products..." class="w-full sm:w-64 bg-transparent border-b border-gray-400 py-2 pl-9 pr-2 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text placeholder-brand-gray"><div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none"><i data-feather="search" class="h-4 w-4 text-brand-gray"></i></div></div><div class="flex items-center space-x-3"><label for="sort-filter-dropdown" class="text-sm font-medium text-brand-gray whitespace-nowrap">Sort by:</label><select id="sort-filter-dropdown" class="bg-transparent border-b border-gray-400 py-2 px-1 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text"><option value="featured">Featured</option><option value="price-asc">Price: Low to High</option><option value="price-desc">Price: High to Low</option></select></div></div>
        <div class="mb-12"><h2 class="text-center text-2xl font-serif font-semibold mb-6">Our Collections</h2><div id="collections-container" class="flex flex-wrap justify-center gap-3"><a href="/shop" class="collection-filter-btn active text-sm font-medium py-2 px-5 rounded-full border border-gray-300 transition-colors duration-200 hover:bg-gray-200" data-collection-id="all">All Products</a><?php foreach ($collections as $collection): ?><button class="collection-filter-btn text-sm font-medium py-2 px-5 rounded-full border border-gray-300 transition-colors duration-200 hover:bg-gray-200" data-collection-id="<?= htmlspecialchars($collection['id']) ?>"><?= htmlspecialchars($collection['name']) ?></button><?php endforeach; ?></div></div>

        <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-12">
            <?php if (empty($panelProducts)): ?>
                <p class="col-span-full text-center text-brand-gray py-10">No products found in this collection.</p>
            <?php else: ?>
                <?php foreach ($panelProducts as $product): ?>
                    <?php
                        $isSoldOut = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0);
                        // REMOVED: $displayPrice logic as price is no longer displayed
                    ?>
                    <div class="product-card">
                        <a <?php if (!$isSoldOut): ?>href="shopdetail?id=<?= urlencode($product['id']) ?>"<?php endif; ?> class="group block <?= $isSoldOut ? 'cursor-not-allowed' : '' ?>">
                            <div class="relative w-full overflow-hidden">
                                <div class="aspect-9-16">
                                    <img src="<?= htmlspecialchars($product['image_one']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="object-cover transition-opacity duration-300 <?= $isSoldOut ? 'opacity-100' : 'opacity-100 group-hover:opacity-0' ?>" loading="lazy">
                                    <img src="<?= htmlspecialchars($product['image_two']) ?>" alt="<?= htmlspecialchars($product['name']) ?> Hover" class="object-cover transition-opacity duration-300 <?= $isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100' ?>" loading="lazy">
                                </div>
                                <?php if ($isSoldOut): ?><div class="absolute inset-0 bg-black/50 flex items-center justify-center"><span class="bg-white text-brand-text text-xs font-semibold tracking-wider uppercase px-4 py-2">Sold Out</span></div><?php endif; ?>
                            </div>
                            <div class="pt-4 text-center">
                                <h3 class="text-base font-medium text-brand-text mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                                </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <nav id="pagination" class="flex items-center justify-center pt-24 space-x-2" aria-label="Pagination"></nav>
    </div>
</main>
<footer class="bg-white border-t border-gray-200"><div class="bg-gray-50 text-center py-4 text-brand-gray text-xs"><p>© <?=date('Y')?> <?= htmlspecialchars($site_name) ?>. All Rights Reserved.</p></div></footer>

<script>
    feather.replace();
    const openSidebarBtn = document.getElementById('open-sidebar-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    openSidebarBtn.addEventListener('click', () => sidebar.classList.remove('-translate-x-full'));
    closeSidebarBtn.addEventListener('click', () => sidebar.classList.add('-translate-x-full'));
    sidebarOverlay.addEventListener('click', () => sidebar.classList.add('-translate-x-full'));

    document.addEventListener('DOMContentLoaded', function () {
        // REMOVED: USD_RATE and INITIAL_CURRENCY constants
        
        // REMOVED: formatPrice function
        // REMOVED: updateAllPrices function
        // REMOVED: Currency switcher event listeners
        
        // REVISED: Simplified to remove any price related data attributes
        function createProductCard(product) {
            const isSoldOut = !product.stock_quantity || product.stock_quantity <= 0;
            
            // REMOVED: Price display HTML and logic
            const productHTML = `
                <div class="product-card">
                    <a ${!isSoldOut ? `href="shopdetail?id=${product.id}"` : ''} class="group block ${isSoldOut ? 'cursor-not-allowed' : ''}">
                        <div class="relative w-full overflow-hidden">
                            <div class="aspect-9-16">
                                <img src="${product.image_one}" alt="${product.name}" class="object-cover transition-opacity duration-300 ${isSoldOut ? 'opacity-100' : 'opacity-100 group-hover:opacity-0'}" loading="lazy">
                                <img src="${product.image_two}" alt="${product.name} Hover" class="object-cover transition-opacity duration-300 ${isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100'}" loading="lazy">
                            </div>
                            ${isSoldOut ? `
                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                    <span class="bg-white text-brand-text text-xs font-semibold tracking-wider uppercase px-4 py-2">Sold Out</span>
                                </div>` : ''}
                        </div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text mb-2">${product.name}</h3>
                        </div>
                    </a>
                </div>
            `;
            return productHTML;
        }
        
        const activeCollectionId = '<?= $collection_filter_id ?: "all" ?>';
        document.querySelectorAll('.collection-filter-btn').forEach(btn => btn.classList.remove('active'));
        const buttonToActivate = document.querySelector(`.collection-filter-btn[data-collection-id="${activeCollectionId}"]`);
        if (buttonToActivate) { buttonToActivate.classList.add('active'); }
        const collectionButtons = document.querySelectorAll('.collection-filter-btn');
        const productGrid = document.getElementById('product-grid');
        const paginationNav = document.getElementById('pagination');

        collectionButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                const collectionId = this.dataset.collectionId;
                if (collectionId === 'all') { // If "All Products" is clicked, allow full page reload to clear params
                    window.location.href = '/shop'; 
                } else {
                    e.preventDefault();
                    // Update URL without full reload for filtering
                    window.history.pushState({path:`shop?collection=${collectionId}`},'',`shop?collection=${collectionId}`);
                    document.querySelectorAll('.collection-filter-btn').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    productGrid.innerHTML = `<p class="col-span-full text-center text-brand-gray py-10">Loading products...</p>`;
                    paginationNav.style.display = 'none'; // Hide pagination during loading
                    fetch(`/fetch-collection?id=${collectionId}`) // Ensure this endpoint exists and returns JSON
                        .then(response => {
                            if (!response.ok) {
                                // Handle HTTP errors
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(products => {
                            productGrid.innerHTML = ''; // Clear existing products
                            if (products.length === 0) {
                                productGrid.innerHTML = `<p class="col-span-full text-center text-brand-gray py-10">No products found in this collection.</p>`;
                                return;
                            }
                            products.forEach(product => {
                                const productCardHTML = createProductCard(product);
                                productGrid.insertAdjacentHTML('beforeend', productCardHTML);
                            });
                            // REMOVED: Initial currency update for newly loaded products
                            // Re-initialize Feather Icons for newly added elements
                            feather.replace(); 
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            productGrid.innerHTML = `<p class="col-span-full text-center text-brand-red py-10">Failed to load products. Please try again.</p>`;
                        });
                }
            });
        });

        // REMOVED: Initial price update based on current currency
    });
</script>
</body>
</html>