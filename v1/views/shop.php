<?php 
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
// Include your functions and connection files here

// --- CURRENCY CONFIGURATION ---

// --- CURRENCY CONFIGURATION ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function getLiveUsdToNgnRate() {
    $apiUrl = 'https://api.exchangerate.host/latest?base=USD&symbols=NGN';
    $response = @file_get_contents($apiUrl);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['rates']['NGN'])) {
            return floatval($data['rates']['NGN']);
        }
    }
    // Fallback in case API fails
    return 1533.04; // Last known rate
}

// Define exchange rate constant (only once)
if (!defined('USD_EXCHANGE_RATE')) {
    define('USD_EXCHANGE_RATE', getLiveUsdToNgnRate());
}

// Set the active currency
if (isset($_SESSION['currency'])) {
    $current_currency = $_SESSION['currency'];
} elseif (isset($_COOKIE['user_currency'])) {
    $current_currency = $_COOKIE['user_currency'];
} else {
    $current_currency = 'NGN'; // Default currency
}


// --- COLLECTION FILTERING & PERFORMANCE OPTIMIZATION ---
$collection_filter_id = null;
if (isset($_GET['collection']) && filter_var($_GET['collection'], FILTER_VALIDATE_INT)) {
    $collection_filter_id = (int)$_GET['collection'];
}
$conditions = ['visibility' => 'show'];
if ($collection_filter_id) {
    $conditions['collection_id'] = $collection_filter_id;
}
$panelProducts = array_slice(selectContent($conn, "panel_products", $conditions, "ORDER BY id DESC"), 0, 50);

$productIds = [];
if (!empty($panelProducts)) {
    $productIds = array_column($panelProducts, 'id');
}
$allColors = [];
$allVariants = [];
if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $colorSql = "SELECT pc.product_id, c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id IN ($placeholders)";
    $colorStmt = $conn->prepare($colorSql);
    $colorStmt->execute($productIds);
    $colorResults = $colorStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($colorResults as $row) {
        $allColors[$row['product_id']][] = $row['hex_code'];
    }
    $variantSql = "SELECT product_id, variant_name, price FROM product_price_variants WHERE product_id IN ($placeholders) ORDER BY price ASC";
    $variantStmt = $conn->prepare($variantSql);
    $variantStmt->execute($productIds);
    $variantResults = $variantStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($variantResults as $row) {
        $allVariants[$row['product_id']][] = $row;
    }
}

$productBreadcrumb = selectContent($conn, "product_breadcrumb", ['visibility' => 'show']);
$collections = selectContent($conn, "collections", [], "ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
    <link rel="apple-touch-icon-precomposed" type="image/png" sizes="152x152" href="<?=$logo_directory?>">
    <title><?=$site_name?> | Shop</title>
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .collection-filter-btn.active { background-color: #1A1A1A; color: white; border-color: #1A1A1A; }
        .currency-switcher a { color: #6B7280; font-weight: 500; transition: color 0.2s ease-in-out; }
        .currency-switcher a:hover { color: #1A1A1A; }
        .currency-switcher a.active { color: #1A1A1A; font-weight: 700; text-decoration: underline; }
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
<!-- SIDEBAR MENU - MODIFIED -->
<div id="sidebar" class="fixed inset-0 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out" aria-labelledby="sidebar-title">
    <div id="sidebar-overlay" class="absolute inset-0 bg-black/40"></div>
    <div class="relative w-80 h-full bg-brand-bg shadow-2xl flex flex-col">
        <div class="p-6 flex justify-between items-center border-b border-gray-200">
            <h2 id="sidebar-title" class="text-2xl font-serif font-semibold">Menu</h2>
            <button id="close-sidebar-btn" class="p-2 text-brand-gray hover:text-brand-text"><i data-feather="x" class="h-6 w-6"></i></button>
        </div>
        <nav class="flex-grow p-6">
            <!-- --- NEW SIDEBAR LINKS --- -->
            <ul class="space-y-4">
                <li><a href="/home" class="flex items-center p-3 text-base font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="home" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Home</span></a></li>
                <li><a href="/shop" class="flex items-center p-3 text-base font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="shopping-bag" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Recent Products</span></a></li>
                <li><a href="/about" class="flex items-center p-3 text-base font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="info" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">About Us</span></a></li>
                <li><a href="/register" class="flex items-center p-3 text-base font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="user" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Login / Register</span></a></li>
                <li><a href="/privacy" class="flex items-center p-3 text-base font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="truck" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Shipping Policy</span></a></li>
            </ul>
        </nav>
        <div class="p-6 border-t border-gray-200"><p class="text-xs text-brand-gray text-center">© <?=date('Y')?> <?=$site_name?></p></div>
    </div>
</div>
<!-- HEADER - MODIFIED -->
<header class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex-1 flex justify-start"><button id="open-sidebar-btn" class="p-2 text-brand-text hover:text-brand-gray"><i data-feather="menu" class="h-6 w-6"></i></button></div>
            <div class="flex-shrink-0 text-center"><a href="/home"><div class="text-1xl font-serif font-bold tracking-widest"><?=$site_name?></div></a></div>
            <div class="flex-1 flex items-center justify-end space-x-4">
                <!-- --- Currency Switcher now visible on all screen sizes --- -->
                <div class="currency-switcher text-sm">
                    <a href="#" class="currency-link <?= ($current_currency === 'NGN') ? 'active' : '' ?>" data-currency="NGN">NGN</a>
                    <span class="mx-1 text-brand-gray">/</span>
                    <a href="#" class="currency-link <?= ($current_currency === 'USD') ? 'active' : '' ?>" data-currency="USD">USD</a>
                </div>
                <!-- <a href="/view-cart" class="p-2 text-brand-text hover:text-brand-gray relative"><i data-feather="shopping-bag" class="h-5 w-5"></i><span class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-brand-red text-white text-xs flex items-center justify-center font-bold" style="font-size: 8px;"></span></a> -->
            </div>
        </div>
    </div>
</header>
<section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($productBreadcrumb[0]['input_image'] ?? '') ?>');"><div class="absolute inset-0 bg-black/30"></div><div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4"><nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb"><ol class="list-none p-0 inline-flex"><li class="flex items-center"><a href="/home" class="hover:underline">Home</a><i data-feather="chevron-right" class="h-4 w-4 mx-2"></i></li><li><span>Shop</span></li></ol></nav><h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4"><?= htmlspecialchars($productBreadcrumb[0]['input_title'] ?? 'Our Collection') ?></h1></div></section>

<!-- MAIN CONTENT -->
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
                        $colors = $allColors[$product['id']] ?? [];
                        $priceVariants = $allVariants[$product['id']] ?? [];
                        $displayPrice = !empty($priceVariants) ? $priceVariants[0]['price'] : $product['price'];
                    ?>
                    <div class="product-card" data-price="<?= htmlspecialchars($displayPrice) ?>">
                        <a <?php if (!$isSoldOut): ?>href="shopdetail?id=<?= urlencode($product['id']) ?>"<?php endif; ?> class="group block <?= $isSoldOut ? 'cursor-not-allowed' : '' ?>">
                            <div class="relative w-full overflow-hidden">
                                <div class="aspect-[9/16]"><img src="<?= htmlspecialchars($product['image_one']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 <?= $isSoldOut ? 'opacity-100' : 'opacity-100 group-hover:opacity-0' ?>"><img src="<?= htmlspecialchars($product['image_two']) ?>" alt="<?= htmlspecialchars($product['name']) ?> Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 <?= $isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100' ?>"></div>
                                <?php if ($isSoldOut): ?><div class="absolute inset-0 bg-black/50 flex items-center justify-center"><span class="bg-white text-brand-text text-xs font-semibold tracking-wider uppercase px-4 py-2">Sold Out</span></div><?php endif; ?>
                            </div>
                            <div class="pt-4 text-center">
                                <h3 class="text-base font-medium text-brand-text mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                                <div class="text-sm text-brand-gray space-y-1">
                                    <?php if (!empty($priceVariants)): ?>
                                        <?php foreach($priceVariants as $variant): ?>
                                            <p class="price-display" data-price-ngn="<?= htmlspecialchars($variant['price']) ?>"><?= htmlspecialchars($variant['variant_name']) ?> - ₦<?= number_format($variant['price'], 2) ?></p>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="price-display" data-price-ngn="<?= htmlspecialchars($product['price']) ?>">₦<?= number_format($product['price'], 2) ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if (!$isSoldOut && !empty($colors)): ?>
                                    <div class="flex justify-center space-x-2 mt-3"><?php foreach ($colors as $color): ?><span class="block w-4 h-4 rounded-full border border-gray-300" style="background-color: <?= htmlspecialchars($color) ?>;"></span><?php endforeach; ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <nav id="pagination" class="flex items-center justify-center pt-24 space-x-2" aria-label="Pagination"></nav>
    </div>
</main>
<footer class="bg-white border-t border-gray-200"><div class="bg-gray-50 text-center py-4 text-brand-gray text-xs"><p>© <?=date('Y')?> <?=$site_name?>. All Rights Reserved.</p></div></footer>

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
        const USD_RATE = <?= USD_EXCHANGE_RATE ?>;
        const INITIAL_CURRENCY = '<?= $current_currency ?>';
        
        function formatPrice(amount, currency, variantName = '') {
            let formattedPrice;
            if (currency === 'USD') {
                formattedPrice = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
            } else {
                formattedPrice = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount).replace('NGN', '₦');
            }
            return variantName ? `${variantName} - ${formattedPrice}` : formattedPrice;
        }

        function updateAllPrices(targetCurrency) {
            document.querySelectorAll('.price-display').forEach(el => {
                const ngnPrice = parseFloat(el.dataset.priceNgn);
                const variantName = el.textContent.includes('-') ? el.textContent.split('-')[0].trim() : '';
                if (!isNaN(ngnPrice)) {
                    let newPrice = (targetCurrency === 'USD') ? ngnPrice / USD_RATE : ngnPrice;
                    el.textContent = formatPrice(newPrice, targetCurrency, variantName);
                }
            });
        }

        document.querySelectorAll('.currency-switcher a.currency-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetCurrency = this.dataset.currency;
                document.querySelectorAll('.currency-switcher a.currency-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                updateAllPrices(targetCurrency);
                const expiryDate = new Date();
                expiryDate.setDate(expiryDate.getDate() + 30);
                document.cookie = `user_currency=${targetCurrency}; expires=${expiryDate.toUTCString()}; path=/`;
                fetch('/currency', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ currency: targetCurrency }) });
            });
        });

        function createProductCard(product) {
            const isSoldOut = !product.stock_quantity || product.stock_quantity <= 0;
            let colorsHTML = '';
            if (!isSoldOut && product.colors && product.colors.length > 0) {
                const colorSpans = product.colors.map(color => `<span class="block w-4 h-4 rounded-full border border-gray-300" style="background-color: ${color};"></span>`).join('');
                colorsHTML = `<div class="flex justify-center space-x-2 mt-3">${colorSpans}</div>`;
            }
            let priceHTML = '';
            if (product.price_variants && product.price_variants.length > 0) {
                const variantList = product.price_variants.map(variant =>
                    `<p class="price-display" data-price-ngn="${variant.price}">${variant.variant_name} - ₦${new Intl.NumberFormat().format(variant.price)}</p>`
                ).join('');
                priceHTML = `<div class="text-sm text-brand-gray space-y-1">${variantList}</div>`;
            } else {
                priceHTML = `<div class="text-sm text-brand-gray space-y-1"><p class="price-display" data-price-ngn="${product.price}">₦${new Intl.NumberFormat().format(product.price)}</p></div>`;
            }
            return `<div class="product-card" data-price="${product.display_price}"><a ${!isSoldOut ? `href="shopdetail?id=${product.id}"` : ''} class="group block ${isSoldOut ? 'cursor-not-allowed' : ''}"><div class="relative w-full overflow-hidden"><div class="aspect-[9/16]"><img src="${product.image_one}" alt="${product.name}" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 ${isSoldOut ? 'opacity-100' : 'opacity-100 group-hover:opacity-0'}"><img src="${product.image_two}" alt="${product.name} Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 ${isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100'}"></div>${isSoldOut ? `<div class="absolute inset-0 bg-black/50 flex items-center justify-center"><span class="bg-white text-brand-text text-xs font-semibold tracking-wider uppercase px-4 py-2">Sold Out</span></div>` : ''}</div><div class="pt-4 text-center"><h3 class="text-base font-medium text-brand-text mb-2">${product.name}</h3>${priceHTML}${colorsHTML}</div></a></div>`;
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
                if (collectionId === 'all') { window.location.href = '/shop'; } 
                else {
                    e.preventDefault();
                    window.history.pushState({path:`shop?collection=${collectionId}`},'',`shop?collection=${collectionId}`);
                    document.querySelectorAll('.collection-filter-btn').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    productGrid.innerHTML = `<p class="col-span-full text-center text-brand-gray py-10">Loading products...</p>`;
                    paginationNav.style.display = 'none';
                    fetch(`/fetch-collection?id=${collectionId}`)
                        .then(response => response.ok ? response.json() : Promise.reject(response))
                        .then(products => {
                            productGrid.innerHTML = '';
                            if (products.length === 0) {
                                productGrid.innerHTML = `<p class="col-span-full text-center text-brand-gray py-10">No products found in this collection.</p>`;
                                return;
                            }
                            products.forEach(product => {
                                const productCardHTML = createProductCard(product);
                                productGrid.insertAdjacentHTML('beforeend', productCardHTML);
                            });
                            const currentActiveCurrency = document.querySelector('.currency-switcher a.active')?.dataset.currency || 'NGN';
                            if (currentActiveCurrency === 'USD') {
                                updateAllPrices('USD');
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            productGrid.innerHTML = `<p class="col-span-full text-center text-brand-red py-10">Failed to load products. Please try again.</p>`;
                        });
                }
            });
        });

        if (INITIAL_CURRENCY === 'USD') {
            updateAllPrices('USD');
        }
    });
</script>

</body>
</html>