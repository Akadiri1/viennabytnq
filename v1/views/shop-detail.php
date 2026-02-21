<?php
// Start session for currency preference
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /shop");
    exit;
}
$id = (int)$_GET['id'];

// This block runs if your actual db_connection is included
$singleProduct = selectContent($conn, "panel_products", ['visibility' => 'show', 'id' => $id]);
if (!$singleProduct) {
    header("Location: /shop");
    exit;
}
$singleProduct = $singleProduct[0];

// Fetch price variants (including stock)
$priceVariantsStmt = $conn->prepare("SELECT id, variant_name, price, stock_quantity FROM product_price_variants WHERE product_id = ? ORDER BY price ASC");
$priceVariantsStmt->execute([$id]);
$priceVariants = $priceVariantsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available sizes (if no price variants)
$availableSizes = [];
if (empty($priceVariants)) {
    $sqlSizes = "SELECT s.id, s.name AS size_name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id = ? ORDER BY s.id ASC";
    $sizesStmt = $conn->prepare($sqlSizes);
    $sizesStmt->execute([$id]);
    $availableSizes = $sizesStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Combine options for the new dropdown
$productOptions = [];
if (!empty($priceVariants)) {
    foreach ($priceVariants as $variant) {
        $productOptions[] = [
            'type' => 'variant',
            'id' => $variant['id'],
            'name' => $variant['variant_name'],
            'price_modifier' => $variant['price'] - $singleProduct['price'], // Store price difference
            'stock' => $variant['stock_quantity'] // Add stock quantity
        ];
    }
} elseif (!empty($availableSizes)) {
    foreach ($availableSizes as $size) {
        $productOptions[] = [
            'type' => 'size',
            'id' => $size['id'],
            'name' => $size['size_name'],
            'price_modifier' => 0 // Assuming standard sizes don't alter base price by default
        ];
    }
}


// Detect if ALL variants have empty names (nameless variants = price/stock only)
$allVariantsNameless = false;
if (!empty($priceVariants)) {
    $namedVariants = array_filter($priceVariants, fn($v) => trim($v['variant_name']) !== '');
    $allVariantsNameless = empty($namedVariants);
}

// If all variants are nameless, don't build selectable options — use the first variant directly
if ($allVariantsNameless) {
    $productOptions = []; // Clear options so the dropdown won't render
}

// Set display price — calculate range for variant products
$displayPrice = $singleProduct['price'];
$priceRangeMin = null;
$priceRangeMax = null;
if (!empty($priceVariants)) {
    $variantPrices = array_column($priceVariants, 'price');
    $priceRangeMin = min($variantPrices);
    $priceRangeMax = max($variantPrices);
    $displayPrice = $priceRangeMin; // Show lowest price as default
    // For nameless variants, show the variant's price (or base price as fallback)
    if ($allVariantsNameless) {
        $bestPrice = $priceVariants[0]['price'];
        $displayPrice = ($bestPrice > 0) ? $bestPrice : $singleProduct['price'];
        $priceRangeMin = null; // Don't show range for nameless
        $priceRangeMax = null;
    }
}
$sqlImages = "SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC";
$imagesStmt = $conn->prepare($sqlImages);
$imagesStmt->execute([$id]);
$productImages = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
$mainImageUrl = $singleProduct['image_one'];
$sqlColors = "SELECT c.id, c.name AS color_name, c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id = ? ORDER BY c.id ASC";
$colorsStmt = $conn->prepare($sqlColors);
$colorsStmt->execute([$id]);
$availableColors = $colorsStmt->fetchAll(PDO::FETCH_ASSOC);

// --- FETCH REVIEWS ---
// --- FETCH REVIEWS ---
$productReviews = [];
$averageRating = 0;
$totalReviews = 0;

try {
    $sqlReviews = "SELECT * FROM product_reviews WHERE product_id = ? AND is_approved = 1 ORDER BY created_at DESC";
    $stmtReviews = $conn->prepare($sqlReviews);
    $stmtReviews->execute([$id]);
    $productReviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Average Rating
    $totalReviews = count($productReviews);
    if ($totalReviews > 0) {
        $sumRating = array_sum(array_column($productReviews, 'rating'));
        $averageRating = round($sumRating / $totalReviews, 1);
    }
} catch (Exception $e) {
    // Fail silently or log error, but don't kill the page
    error_log("Error fetching reviews: " . $e->getMessage());
}






$relatedProducts = [];
$fetchedIds = [$id]; // always exclude current product
if (!empty($singleProduct['collection_id'])) {
    $relatedProductsQuery = "SELECT * FROM panel_products WHERE visibility = 'show' AND id != ? AND collection_id = ? ORDER BY RAND() LIMIT 4";
    $relatedStmt = $conn->prepare($relatedProductsQuery);
    $relatedStmt->execute([$id, $singleProduct['collection_id']]);
    $relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add these IDs to the exclusion list
    foreach ($relatedProducts as $p) {
        $fetchedIds[] = $p['id'];
    }
}
if (count($relatedProducts) < 4) {
    $limit = 4 - count($relatedProducts);
    
    // Create placeholders for the NOT IN clause
    $placeholders = implode(',', array_fill(0, count($fetchedIds), '?'));
    $fallbackQuery = "SELECT * FROM panel_products WHERE visibility = 'show' AND id NOT IN ($placeholders) ORDER BY RAND() LIMIT $limit";
    $fallbackStmt = $conn->prepare($fallbackQuery);
    
    // The parameters are just the array of fetchedIds (which includes the current product ID)
    $fallbackStmt->execute($fetchedIds);
    $relatedProducts = array_merge($relatedProducts, $fallbackStmt->fetchAll(PDO::FETCH_ASSOC));
}


function getLiveUsdToNgnRate()
{
    // Check session cache (1 hour expiry)
    if (isset($_SESSION['usd_ngn_rate']) && isset($_SESSION['usd_ngn_rate_time']) && (time() - $_SESSION['usd_ngn_rate_time'] < 3600)) {
        return $_SESSION['usd_ngn_rate'];
    }

    // Try multiple free exchange rate APIs
    $apis = [
        'https://open.er-api.com/v6/latest/USD',
        'https://api.exchangerate-api.com/v4/latest/USD'
    ];
    
    foreach ($apis as $apiUrl) {
        // Reduced timeout to 2 seconds to avoid blocking page load
        $context = stream_context_create(['http' => ['timeout' => 2]]);
        $response = @file_get_contents($apiUrl, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['rates']['NGN'])) {
                $rate = floatval($data['rates']['NGN']);
                // Cache result
                $_SESSION['usd_ngn_rate'] = $rate;
                $_SESSION['usd_ngn_rate_time'] = time();
                return $rate;
            }
        }
    }
    
    // Use last cached value if available (even if expired)
    if (isset($_SESSION['usd_ngn_rate'])) {
        return $_SESSION['usd_ngn_rate'];
    }
    
    // Fallback in case all APIs fail - use current market rate (Jan 2025)
    return 1480; 
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

// === LOGIC FOR COLOR OPTIONS (Kept consistent with previous response) ===
$colorNames = array_column($availableColors, 'color_name', 'id');
$colorHexes = array_column($availableColors, 'hex_code', 'id');
$colorCount = count($colorNames);
$groupedColors = [];

// Check the new flag from the database to determine if we should group colors
if ($singleProduct['use_color_combinations'] == 1 && $colorCount >= 2) {
    // Logic for products that use "AND" (Color Combinations)
    for ($i = 0; $i < $colorCount; $i += 2) {
        $color1_id = array_keys($colorNames)[$i];
        $color1_name = $colorNames[$color1_id];
        $color1_hex = $colorHexes[$color1_id];

        if ($i + 1 < $colorCount) {
            $color2_id = array_keys($colorNames)[$i+1];
            $color2_name = $colorNames[$color2_id];
            
            $groupedColors[] = [
                'id' => $color1_id . '-' . $color2_id,
                'name' => $color1_name . ' and ' . $color2_name,
                'hex' => $color1_hex
            ];
        } else {
            // If there's an odd one left, just list it as a single color
            $groupedColors[] = [
                'id' => $color1_id,
                'name' => $color1_name,
                'hex' => $color1_hex
            ];
        }
    }
} else {
    // Logic for products that use SINGLE colors only
    foreach ($availableColors as $color) {
        $groupedColors[] = [
            'id' => $color['id'],
            'name' => $color['color_name'],
            'hex' => $color['hex_code']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>" />
    <title><?= htmlspecialchars($singleProduct['name']) ?> - <?=$site_name?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { "brand-bg": "#F9F6F2", "brand-text": "#1A1A1A", "brand-gray": "#6B7280", "brand-red": "#EF4444", }, fontFamily: { sans: ["Lato", "ui-sans-serif", "system-ui"], serif: ["Playfair Display", "serif"], }, }, }, };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin /><link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .thumbnail-img { border: 2px solid transparent; transition: border-color 0.2s ease-in-out; } .active-thumbnail { border-color: #1a1a1a; } .color-swatch { width: 1.75rem; height: 1.75rem; border-radius: 9999px; cursor: pointer; border: 1px solid #d1d5db; transition: all 0.2s ease-in-out; } .active-color { transform: scale(1.1); box-shadow: 0 0 0 3px #1a1a1a; border-color: #1a1a1a; } .size-btn { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; font-weight: 500; cursor: pointer; transition: all 0.2s; font-size: 0.875rem; min-width: 40px; text-align: center; } .size-btn:hover { border-color: #1a1a1a; } .active-size { background-color: #1a1a1a; color: white; border-color: #1a1a1a; }
        /* OUT OF STOCK STYLING */
        a.cursor-not-allowed .aspect-\[9\/16\] { filter: grayscale(100%) blur(1.5px); opacity: 0.9; transition: all 0.3s ease; }
        a.cursor-not-allowed:hover .aspect-\[9\/16\] { opacity: 1; }
        .price-variant-label { display: flex; align-items: center; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; cursor: pointer; transition: all 0.2s; border-radius: 0.25rem; } .price-variant-label:hover { border-color: #1a1a1a; } input[type="radio"]:checked + .price-variant-label { background-color: #1a1a1a; color: white; border-color: #1a1a1a; }
        .form-input-sleek, .form-select-sleek { background-color: transparent; border: 0; border-bottom: 1px solid #d1d5db; border-radius: 0; padding: 0.5rem 0.1rem; width: 100%; transition: border-color 0.2s ease-in-out; } .form-input-sleek:focus, .form-select-sleek:focus { outline: none; box-shadow: none; ring: 0; border-bottom-color: #1a1a1a; }
        .modal-container { display: flex; align-items: center; justify-content: center; position: fixed; inset: 0; z-index: 100; transition: opacity 0.3s ease-in-out; opacity: 1; pointer-events: auto; } .modal-container.hidden { opacity: 0; pointer-events: none; } .modal-overlay { position: absolute; inset: 0; background-color: rgba(0, 0, 0, 0.4); } .modal-panel { position: relative; width: 95%; max-w: 500px; background-color: #f9f6f2; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); transform: scale(1); opacity: 1; } .modal-container.hidden .modal-panel { transform: scale(0.95); opacity: 0; } .modal-content-scrollable { max-height: 60vh; overflow-y: auto; } .modal-form-scrollable { max-height: 60vh; overflow-y: auto; padding-right: 0.75rem; }
        /* CSS for Custom Color Input Toggle */
        #custom-color-input-container { transition: max-height 0.35s ease-in-out, opacity 0.3s ease-in-out, margin-top 0.35s ease-in-out; overflow: hidden; max-height: 100px; opacity: 1; margin-top: 1rem; }
        #custom-color-input-container.is-closed { max-height: 0; opacity: 0; margin-top: 0; }
        /* CSS for Custom Measurement Input Toggle */
        #custom-measurement-container { transition: max-height 0.35s ease-in-out, opacity 0.3s ease-in-out, margin-top 0.35s ease-in-out; overflow: hidden; max-height: 150px; opacity: 1; margin-top: 1rem; }
        #custom-measurement-container.is-closed { max-height: 0; opacity: 0; margin-top: 0; }
        .toastify { padding: 12px 20px; font-size: 14px; font-weight: 500; border-radius: 8px; box-shadow: 0 3px 6px -1px rgba(0,0,0,.12), 0 10px 36px -4px rgba(51,45,45,.25); }
        /* Dynamic Price Animation */
        .price-pop {
            animation: pricePop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes pricePop {
            0% { transform: scale(0.85); opacity: 0.3; }
            50% { transform: scale(1.08); }
            100% { transform: scale(1); opacity: 1; }
        }
        #product-price { transition: color 0.3s ease; }
        .price-from-label { font-size: 0.55em; font-weight: 500; letter-spacing: 0.05em; text-transform: uppercase; opacity: 0.6; vertical-align: middle; }
        .scrollbar-thin::-webkit-scrollbar { width: 4px; height: 4px; } .scrollbar-thin::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 20px; }
        .currency-switcher a { color: #6B7280; font-weight: 500; transition: color 0.2s ease-in-out; }
        .currency-switcher a:hover { color: #1A1A1A; }
        .currency-switcher a.active { color: #1A1A1A; font-weight: 700; text-decoration: underline; }
        
        /* Skeleton loader styles */
        .skeleton-container {
            background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.5s infinite ease-in-out;
        }
        @keyframes skeleton-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .skeleton-container.loaded {
            background: none;
            animation: none;
        }
        .skeleton-img {
            transition: opacity 0.4s ease-in-out;
        }
    </style>
    <script>
        function handleImageLoad(imgElement) {
            imgElement.classList.remove('opacity-0');
            const container = imgElement.closest('.skeleton-container');
            if (container) container.classList.add('loaded');
        }
    </script>
</head>
<body class="bg-brand-bg font-sans text-brand-text">

    <!-- SIDEBAR MENU - MODIFIED -->
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
                
                <!-- Mobile Currency Switcher -->
                <li class="border-t border-gray-100 pt-4 mt-2">
                    <div class="px-3">
                        <span class="block text-xs font-bold text-brand-gray uppercase tracking-widest mb-3">Currency</span>
                        <div class="flex gap-2 currency-switcher">
                            <a href="#" class="currency-link flex-1 py-2 text-center rounded-md text-sm font-medium border <?= ($current_currency === 'NGN') ? 'border-brand-text bg-brand-text text-white' : 'border-gray-200 text-brand-gray hover:border-brand-text' ?>" data-currency="NGN">NGN</a>
                            <a href="#" class="currency-link flex-1 py-2 text-center rounded-md text-sm font-medium border <?= ($current_currency === 'USD') ? 'border-brand-text bg-brand-text text-white' : 'border-gray-200 text-brand-gray hover:border-brand-text' ?>" data-currency="USD">USD</a>
                        </div>
                    </div>
                </li>
                </ul>
            </nav>
            <div class="p-6 border-t border-gray-200"><p class="text-xs text-brand-gray text-center">© <?=date('Y')?> <?=$site_name?></p></div>
        </div>
    </div>

    <!-- CART SIDEBAR -->
    <div id="cart-sidebar" class="fixed inset-0 z-[60] transform translate-x-full transition-transform duration-300 ease-in-out"><div id="cart-overlay" class="absolute inset-0 bg-black/40 cursor-pointer"></div><div class="relative w-full max-w-md ml-auto h-full bg-brand-bg shadow-2xl flex flex-col"><div class="p-6 flex justify-between items-center border-b border-gray-200"><h2 id="cart-title" class="text-2xl font-serif font-semibold">Your Cart</h2><button id="close-cart-btn" class="p-2 text-brand-gray hover:text-brand-text"><i data-feather="x" class="h-6 w-6"></i></button></div><div id="cart-items-container" class="flex-grow p-6 overflow-y-auto"></div><div class="p-6 border-t border-gray-200 space-y-4 bg-brand-bg"><div class="flex justify-between font-semibold"><span>Subtotal</span><span id="cart-subtotal" class="price-display" data-price-ngn="0">₦0.00</span></div><a href="/view-cart" class="block w-full bg-transparent text-brand-text border border-brand-text py-3 text-center font-semibold hover:bg-gray-100 transition-colors">VIEW CART</a><a href="/checkout" class="block w-full bg-brand-text text-white py-3 text-center font-semibold hover:bg-gray-800 transition-colors">CHECKOUT</a></div></div></div>

    <!-- HEADER - MODIFIED -->
    <header class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40"><div class="container mx-auto px-4 sm:px-6 lg:px-8"><div class="flex items-center justify-between h-16"><div class="flex-1 flex justify-start"><button id="open-sidebar-btn" class="p-2 text-brand-text hover:text-brand-gray"><i data-feather="menu" class="h-6 w-6"></i></button></div><div class="flex-shrink-0 text-center"><a href="/home"><img src="<?=$logo_directory?>" alt="<?=$site_name?>" class="h-8 w-auto object-contain"></a></div><div class="flex-1 flex items-center justify-end space-x-2 md:space-x-4">
                            <div class="hidden md:flex bg-gray-100 rounded-full p-0.5 items-center currency-switcher scale-90 origin-right md:scale-100 md:p-1">
                                <a href="#" class="currency-link px-2 py-0.5 md:px-3 md:py-1 rounded-full text-[10px] md:text-xs font-bold transition-all duration-200 <?= ($current_currency === 'NGN') ? 'bg-white shadow-sm text-brand-text' : 'text-brand-gray hover:text-brand-text' ?>" data-currency="NGN">NGN</a>
                                <a href="#" class="currency-link px-2 py-0.5 md:px-3 md:py-1 rounded-full text-[10px] md:text-xs font-bold transition-all duration-200 <?= ($current_currency === 'USD') ? 'bg-white shadow-sm text-brand-text' : 'text-brand-gray hover:text-brand-text' ?>" data-currency="USD">USD</a>
                            </div>
                            <a href="<?= isset($_SESSION['user_id']) ? '/user-dashboard' : '/register' ?>" class="p-1 md:p-2 text-brand-text hover:text-brand-gray" title="<?= isset($_SESSION['user_id']) ? 'My Account' : 'Login / Register' ?>"><i data-feather="user" class="h-5 w-5"></i></a>
                            <button id="open-cart-btn" class="p-1 md:p-2 text-brand-text hover:text-brand-gray relative"><i data-feather="shopping-bag" class="h-5 w-5"></i><span id="cart-item-count" class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-brand-red text-white text-xs flex items-center justify-center font-bold" style="font-size: 8px; display: none;">0</span></button>
</div></div></div></header>

    <section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('/<?= htmlspecialchars($singleProduct['image_one'] ?? '') ?>');"><div class="absolute inset-0 bg-black/30"></div><div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4"><nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb"><ol class="list-none p-0 inline-flex items-center"><li class="flex items-center"><a href="/home" class="hover:underline">Home</a><i data-feather="chevron-right" class="h-4 w-4 mx-2"></i></li><li class="flex items-center"><a href="/shop" class="hover:underline">Shop</a></li></ol></nav><h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4"><?= htmlspecialchars($singleProduct['name']) ?></h1></div></section>

    <!-- MAIN CONTENT -->
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 xl:gap-x-16 gap-y-10">
            <div class="flex flex-col gap-4">
                <!-- Main Image Slider -->
                <div class="w-full relative group">
                    <div id="main-image-slider" class="flex overflow-x-auto snap-x snap-mandatory hide-scrollbar cursor-grab active:cursor-grabbing rounded-sm" style="scrollbar-width: none; -ms-overflow-style: none;">
                        <style>#main-image-slider::-webkit-scrollbar { display: none; }</style>
                        
                        <!-- First Image -->
                        <div class="w-full shrink-0 snap-center relative" style="padding-top: 133.33%;">
                            <div class="skeleton-container absolute inset-0 bg-[#f9f9f9] overflow-hidden">
                                <img src="/<?= htmlspecialchars($mainImageUrl) ?>" alt="<?= htmlspecialchars($singleProduct['name']) ?>" class="slider-img skeleton-img object-cover w-full h-full opacity-0 pointer-events-none select-none transition-opacity duration-500" loading="eager" decoding="async" onload="handleImageLoad(this)"/>
                            </div>
                        </div>
                        
                        <!-- Subsequent Images -->
                        <?php foreach ($productImages as $image): ?>
                        <div class="w-full shrink-0 snap-center relative" style="padding-top: 133.33%;">
                            <div class="skeleton-container absolute inset-0 bg-[#f9f9f9] overflow-hidden">
                                <img src="/<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($image['alt_text'] ?? $singleProduct['name']) ?>" class="slider-img skeleton-img object-cover w-full h-full opacity-0 pointer-events-none select-none transition-opacity duration-500" loading="lazy" decoding="async" onload="handleImageLoad(this)"/>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Navigation Arrows -->
                    <?php if (count($productImages) > 0): ?>
                    <button id="slider-prev" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/80 text-brand-text shadow-md flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white z-10" aria-label="Previous image">
                        <i data-feather="chevron-left" class="w-5 h-5"></i>
                    </button>
                    <button id="slider-next" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/80 text-brand-text shadow-md flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white z-10" aria-label="Next image">
                        <i data-feather="chevron-right" class="w-5 h-5"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div id="thumbnail-gallery" class="flex flex-row gap-3 overflow-x-auto hide-scrollbar pb-2">
                    <style>#thumbnail-gallery::-webkit-scrollbar { display: none; } .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }</style>
                    <div class="skeleton-container rounded-sm w-20 md:w-24 aspect-[3/4] flex-shrink-0 cursor-pointer border border-brand-text active-thumbnail overflow-hidden transition-all opacity-80 hover:opacity-100">
                        <img src="/<?= htmlspecialchars($mainImageUrl) ?>" alt="<?= htmlspecialchars($singleProduct['name']) ?>" class="thumbnail-img skeleton-img w-full h-full object-cover opacity-0 transition-opacity duration-300" loading="lazy" decoding="async" onload="handleImageLoad(this)"/>
                    </div>
                    <?php foreach ($productImages as $image): ?>
                    <div class="skeleton-container rounded-sm w-20 md:w-24 aspect-[3/4] flex-shrink-0 cursor-pointer border border-transparent hover:border-gray-300 transition-all overflow-hidden opacity-60 hover:opacity-100">
                        <img src="/<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($image['alt_text'] ?? $singleProduct['name']) ?>" class="thumbnail-img skeleton-img w-full h-full object-cover opacity-0 transition-opacity duration-300" loading="lazy" decoding="async" onload="handleImageLoad(this)"/>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex flex-col pt-4 lg:pt-0 space-y-8">
                <div>
                    <h1 id="product-name" class="text-4xl md:text-5xl font-serif font-semibold text-brand-text sr-only"><?= htmlspecialchars($singleProduct['name']) ?></h1>
                    <div class="flex items-center gap-4 mt-3 mb-5">
                        <p id="product-price" class="price-display text-3xl font-semibold text-brand-text mb-0"
                            data-base-price="<?= $singleProduct['price'] ?>"
                            data-price-ngn="<?= $displayPrice ?>"
                            data-range-min="<?= $priceRangeMin ?? '' ?>"
                            data-range-max="<?= $priceRangeMax ?? '' ?>"
                            data-auto-variant-id="<?= $allVariantsNameless ? $priceVariants[0]['id'] : '' ?>">
                            <?php if ($priceRangeMin !== null && $priceRangeMax !== null && $priceRangeMin != $priceRangeMax): ?>
                                <span class="price-from-label">From </span>₦<?= number_format($priceRangeMin, 2) ?> <span class="text-lg text-brand-gray">–</span> ₦<?= number_format($priceRangeMax, 2) ?>
                            <?php elseif ($priceRangeMin !== null): ?>
                                ₦<?= number_format($priceRangeMin, 2) ?>
                            <?php else: ?>
                                ₦<?= number_format($displayPrice, 2) ?>
                            <?php endif; ?>
                        </p>
                        
                        <!-- Currency Switcher Beside Price -->
                        <div class="flex bg-gray-100 rounded-full p-0.5 items-center currency-switcher scale-90 origin-left md:scale-100 md:p-1">
                            <a href="#" class="currency-link px-2 py-0.5 md:px-3 md:py-1 rounded-full text-[10px] md:text-xs font-bold transition-all duration-200 <?= ($current_currency === 'NGN') ? 'bg-white shadow-sm text-brand-text' : 'text-brand-gray hover:text-brand-text' ?>" data-currency="NGN">NGN</a>
                            <a href="#" class="currency-link px-2 py-0.5 md:px-3 md:py-1 rounded-full text-[10px] md:text-xs font-bold transition-all duration-200 <?= ($current_currency === 'USD') ? 'bg-white shadow-sm text-brand-text' : 'text-brand-gray hover:text-brand-text' ?>" data-currency="USD">USD</a>
                        </div>
                    </div>
                    <p id="stock-display" class="text-sm font-medium mb-5">
                        <?php if (empty($priceVariants)): ?>
                            <?= $singleProduct['stock_quantity'] > 0 ? '<span class="text-emerald-600">In Stock (' . $singleProduct['stock_quantity'] . ' available)</span>' : '<span class="text-brand-red">Out of Stock</span>' ?>
                        <?php elseif ($allVariantsNameless): ?>
                            <?php $autoStock = $priceVariants[0]['stock_quantity']; ?>
                            <?= $autoStock > 0 ? '<span class="text-emerald-600">In Stock (' . $autoStock . ' available)</span>' : '<span class="text-brand-red">Out of Stock</span>' ?>
                        <?php else: ?>
                            <span class="text-brand-gray">Select an option to view stock</span>
                        <?php endif; ?>
                    </p>
                    <div class="text-gray-600 leading-relaxed text-base prose"><?= nl2br(htmlspecialchars($singleProduct['product_text'])) ?></div>
                </div>
                <div>
                    <!-- Color Selection (Unchanged) -->
                    <h3 class="text-xs uppercase tracking-widest font-bold text-brand-text mb-3">COLOR</h3>
                    <?php if (!empty($availableColors)): ?>
                        <div class="custom-dropdown-container relative group mb-4">
                            <!-- Hidden original select to maintain form and JS hooks -->
                            <select id="color-select" name="color_id" class="hidden">
                                <option value="" data-hex="" selected>Select Color</option>
                                <?php foreach ($groupedColors as $colorGroup): ?>
                                    <option value="<?= htmlspecialchars($colorGroup['id']) ?>" data-hex="<?= htmlspecialchars($colorGroup['hex']) ?>">
                                        <?= htmlspecialchars($colorGroup['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- Custom UI -->
                            <div class="custom-dropdown-trigger flex justify-between items-center w-full bg-transparent border border-gray-200 rounded-none md:rounded-sm py-3.5 pl-4 pr-4 text-sm font-medium text-brand-text hover:border-gray-300 transition-colors cursor-pointer" tabindex="0">
                                <span class="selected-text">Select Color</span>
                                <i data-feather="chevron-down" class="h-4 w-4 text-brand-gray transition-transform duration-200 dropdown-icon"></i>
                            </div>
                            
                            <!-- Dropdown Menu -->
                            <div class="custom-dropdown-menu absolute z-30 w-full bg-white border border-gray-200 mt-1 rounded-sm shadow-xl hidden opacity-0 translate-y-[-10px] transition-all duration-200">
                                <ul class="py-1 max-h-60 overflow-y-auto hide-scrollbar">
                                    <li class="custom-option px-4 py-3 text-sm text-brand-gray hover:bg-gray-50 cursor-pointer transition-colors" data-value="">Select Color</li>
                                    <?php foreach ($groupedColors as $colorGroup): ?>
                                        <li class="custom-option px-4 py-3 text-sm text-brand-text hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors" data-value="<?= htmlspecialchars($colorGroup['id']) ?>">
                                            <?= htmlspecialchars($colorGroup['name']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <button id="open-custom-color-btn" class="text-sm text-brand-gray hover:text-brand-text underline transition-colors">Or need a custom color?</button>
                        <div id="custom-color-input-container" class="is-closed">
                            <label for="custom-color" class="text-sm font-medium text-brand-gray">Custom Color</label>
                            <input type="text" id="custom-color" class="form-input-sleek mt-1" placeholder="e.g., Emerald Green"/>
                        </div>
                    <?php else: ?>
                        <!-- If no standard colors, only show custom color input -->
                        <div id="custom-color-input-container">
                            <input type="text" id="custom-color" class="form-input-sleek" placeholder="e.g., Emerald Green"/>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!$allVariantsNameless): // Only show variant section if variants have names ?>
                    <div class="flex justify-between items-center mb-3 mt-8">
                        <h3 class="text-xs uppercase tracking-widest font-bold text-brand-text">VARIANT</h3>
                        <?php if(!empty($productOptions)): // Show if any standard options exist ?>
                        <button id="open-size-chart-btn" class="text-xs font-semibold tracking-wider text-brand-gray hover:text-brand-text underline uppercase transition-colors">Size Guide</button>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($productOptions)): ?>
                        <div class="custom-dropdown-container relative group mt-1 mb-4" id="product-option-select-wrapper">
                            <!-- Hidden original select -->
                            <select id="product-option-select" class="hidden">
                                <option value="" data-type="" data-id="" data-price-modifier="0" selected>Select Variant</option>
                                <?php foreach ($productOptions as $index => $option): ?>
                                    <option
                                        value="<?= htmlspecialchars($option['type'] . '-' . $option['id']) ?>"
                                        data-type="<?= htmlspecialchars($option['type']) ?>"
                                        data-id="<?= htmlspecialchars($option['id']) ?>"
                                        data-price-modifier="<?= htmlspecialchars($option['price_modifier']) ?>"
                                        data-stock="<?= htmlspecialchars($option['stock'] ?? '') ?>"
                                    >
                                        <?= htmlspecialchars($option['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Custom UI -->
                            <div class="custom-dropdown-trigger flex justify-between items-center w-full bg-transparent border border-gray-200 rounded-none md:rounded-sm py-3.5 pl-4 pr-4 text-sm font-medium text-brand-text hover:border-gray-300 transition-colors cursor-pointer" tabindex="0">
                                <span class="selected-text">Select Variant</span>
                                <i data-feather="chevron-down" class="h-4 w-4 text-brand-gray transition-transform duration-200 dropdown-icon"></i>
                            </div>
                            
                            <!-- Dropdown Menu -->
                            <div class="custom-dropdown-menu absolute z-30 w-full bg-white border border-gray-200 mt-1 rounded-sm shadow-xl hidden opacity-0 translate-y-[-10px] transition-all duration-200">
                                <ul class="py-1 max-h-60 overflow-y-auto hide-scrollbar">
                                    <li class="custom-option px-4 py-3 text-sm text-brand-gray hover:bg-gray-50 cursor-pointer transition-colors" data-value="">Select Variant</li>
                                    <?php foreach ($productOptions as $option): ?>
                                        <li class="custom-option px-4 py-3 text-sm text-brand-text hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors" data-value="<?= htmlspecialchars($option['type'] . '-' . $option['id']) ?>">
                                            <?= htmlspecialchars($option['name']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php endif; // end !$allVariantsNameless ?>

                    <!-- Custom size toggle button — always visible -->
                    <button id="open-custom-size-btn" class="text-sm text-brand-gray hover:text-brand-text underline mt-2">Or provide custom measurements</button>

                    <!-- Custom Measurement Inputs (Visibility toggled via JS/CSS class) -->
                    <div id="custom-measurement-container" class="is-closed">
                        <h4 class="text-sm font-semibold mt-4 mb-2 text-brand-text">Custom Measurements (in inches)</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="custom-measurement-chest" class="text-xs text-brand-gray">Chest</label>
                                <input type="text" id="custom-measurement-chest" class="form-input-sleek" placeholder="e.g., 40" pattern="[0-9]*"/>
                            </div>
                            <div>
                                <label for="custom-measurement-waist" class="text-xs text-brand-gray">Waist</label>
                                <input type="text" id="custom-measurement-waist" class="form-input-sleek" placeholder="e.g., 32" pattern="[0-9]*"/>
                            </div>
                            <div>
                                <label for="custom-measurement-length" class="text-xs text-brand-gray">Length</label>
                                <input type="text" id="custom-measurement-length" class="form-input-sleek" placeholder="e.g., 30" pattern="[0-9]*"/>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center border border-gray-300"><button id="quantity-minus" class="px-3 py-2 text-brand-gray hover:text-brand-text">-</button><span id="quantity-display" class="px-4 py-2 font-medium">1</span><button id="quantity-plus" class="px-3 py-2 text-brand-gray hover:text-brand-text">+</button></div>
                    <button id="add-to-cart-btn" data-product-id="<?= $id ?>" class="w-full bg-brand-text text-white py-3 font-semibold hover:bg-gray-800 transition-colors flex items-center justify-center gap-3"><i data-feather="shopping-cart" class="w-5 h-5"></i><span>ADD TO CART</span></button>
                </div>
            </div>
        </div>
    </main>

    <!-- "YOU MIGHT ALSO LIKE" SECTION -->
    <?php if (!empty($relatedProducts)): ?>
    <section class="container mx-auto px-4 sm:px-6 lg:px-8 mt-24 mb-16">
        <h2 class="text-3xl font-serif font-semibold text-center mb-10">You Might Also Like</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-12">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
                <?php
                    // Fetch Text-Only Variant Prices for Display
                    // AND Stock for Validation
                    $relatedVariantsData = [];
                    if(isset($conn) && get_class($conn) === 'MockPDO') {
                        $relatedVariantsData = [['price' => 32000, 'stock_quantity' => 10], ['price' => 35000, 'stock_quantity' => 5]]; 
                    } else {
                        $relatedVariantStmt = $conn->prepare("SELECT price, stock_quantity FROM product_price_variants WHERE product_id = ? ORDER BY price ASC");
                        $relatedVariantStmt->execute([$relatedProduct['id']]);
                        $relatedVariantsData = $relatedVariantStmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                    
                    // Determine Price Range
                    $relatedPrices = array_column($relatedVariantsData, 'price');
                    
                    // Determine Stock Status
                    if (!empty($relatedVariantsData)) {
                        $totalStock = array_sum(array_column($relatedVariantsData, 'stock_quantity'));
                        $isRelatedSoldOut = $totalStock <= 0;
                    } else {
                        $isRelatedSoldOut = ($relatedProduct['stock_quantity'] ?? 0) <= 0;
                    }
                ?>
                <?php $relatedSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $relatedProduct['name']), '-')); ?>
                <div class="product-card">
                    <a <?php if (!$isRelatedSoldOut): ?>href="/product/<?= urlencode($relatedSlug) ?>?id=<?= urlencode($relatedProduct['id']) ?>"<?php endif; ?> class="group block <?= $isRelatedSoldOut ? 'cursor-not-allowed' : '' ?>">
                        <div class="relative w-full overflow-hidden">
                            <div class="aspect-[9/16] skeleton-container">
                                <img src="/<?= htmlspecialchars($relatedProduct['image_one']) ?>" alt="<?= htmlspecialchars($relatedProduct['name']) ?>" class="skeleton-img absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 <?= $isRelatedSoldOut ? '' : 'group-hover:opacity-0' ?>" loading="lazy" decoding="async" onload="handleImageLoad(this)">
                                <img src="/<?= htmlspecialchars($relatedProduct['image_two']) ?>" alt="<?= htmlspecialchars($relatedProduct['name']) ?> Hover" class="skeleton-img absolute inset-0 w-full h-full object-cover transition-opacity duration-300 <?= $isRelatedSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100' ?>" loading="lazy" decoding="async">
                            </div>
                            
                            <?php if ($isRelatedSoldOut): ?>
                                <div class="absolute inset-0 z-10 flex items-center justify-center">
                                    <span class="bg-[#1a1a1a] text-white text-xs font-bold uppercase tracking-[0.2em] px-6 py-3 shadow-xl">
                                        Sold Out
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text"><?= htmlspecialchars($relatedProduct['name']) ?></h3>
                            <p class="price-display mt-1 text-sm text-brand-gray" data-price-ngn="<?= !empty($relatedPrices) ? $relatedPrices[0] : $relatedProduct['price'] ?>">
                                <?php if (count($relatedPrices) > 1): ?>From ₦<?= number_format($relatedPrices[0], 2) ?><?php else: ?>₦<?= number_format(!empty($relatedPrices) ? $relatedPrices[0] : $relatedProduct['price'], 2) ?><?php endif; ?>
                            </p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php endif; ?>

    <!-- =================================================================== -->
    <!-- REVIEWS SECTION                                                     -->
    <!-- =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 border-t border-gray-200" id="reviews-section">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-serif font-semibold text-brand-text mb-2">Customer Reviews</h2>
            <p class="text-brand-gray font-lato text-sm uppercase tracking-widest">See what others are saying</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16">
            <!-- LEFT: Summary & Form -->
            <div class="lg:col-span-4 space-y-10">
                
                <!-- Rating Summary -->
                <div class="text-center lg:text-left">
                    <div id="avg-rating-value" class="text-6xl font-serif text-brand-text mb-4"><?= $averageRating ?></div>
                    <div id="avg-rating-stars" class="flex justify-center lg:justify-start gap-1 text-yellow-400 mb-3">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i data-feather="star" class="w-5 h-5 <?= $i <= round($averageRating) ? 'fill-current' : 'text-gray-300' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p id="reviews-count" class="text-sm font-lato text-brand-gray">Based on <?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?></p>
                </div>

                <hr class="border-gray-100 hidden lg:block">

                <!-- Review Form (Logged In Only) -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form id="review-form" class="space-y-6">
                        <h3 class="text-xl font-serif text-brand-text mb-2">Write a Review</h3>
                        <input type="hidden" name="product_id" value="<?= $id ?>">
                        
                        <!-- Star Input -->
                        <div>
                            <label class="block text-xs uppercase tracking-widest font-bold text-brand-text mb-3">RATING</label>
                            <div class="flex gap-2" id="star-rating-input">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <button type="button" class="star-btn text-gray-300 hover:text-yellow-400 transition-colors" data-value="<?= $i ?>">
                                        <i data-feather="star" class="w-6 h-6 fill-current"></i>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="rating-value" required>
                        </div>

                        <div>
                            <label class="block text-xs uppercase tracking-widest font-bold text-brand-text mb-3">YOUR EXPERIENCE</label>
                            <textarea name="review_text" rows="5" class="w-full bg-transparent border border-gray-200 rounded-sm p-4 text-sm font-lato focus:outline-none focus:border-brand-text resize-none transition-colors" placeholder="Share your thoughts about this piece..." required></textarea>
                        </div>

                        <button type="submit" class="w-full bg-brand-text text-white py-4 font-semibold hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed uppercase tracking-wider text-sm" id="submit-review-btn">Submit Review</button>
                    </form>
                <?php else: ?>
                    <div class="bg-gray-50/50 p-8 rounded-sm border border-gray-100 text-center">
                        <i data-feather="lock" class="w-6 h-6 mx-auto mb-4 text-brand-gray"></i>
                        <h3 class="font-serif text-lg mb-2">Join the Conversation</h3>
                        <p class="text-sm font-lato text-brand-gray mb-6">Create an account or log in to leave a review.</p>
                        <a href="/register?form=login&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="inline-block bg-brand-text text-white px-8 py-3 rounded-none font-medium hover:bg-gray-800 transition-colors text-sm tracking-wider uppercase">Log In</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Reviews List -->
            <div class="lg:col-span-8">
                <div id="reviews-list" class="space-y-8 lg:space-y-12">
                    <?php if ($totalReviews > 0): ?>
                        <?php foreach ($productReviews as $review): ?>
                            <div class="flex gap-6 pb-8 border-b border-gray-100 last:border-0 last:pb-0">
                                <div class="flex-shrink-0 hidden sm:block">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-brand-text font-serif text-lg border border-gray-200">
                                        <?= strtoupper(substr($review['reviewer_name'], 0, 1)) ?>
                                    </div>
                                </div>
                                <div class="flex-grow">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 gap-2">
                                        <div class="flex items-center gap-3">
                                            <h4 class="font-serif font-medium text-lg text-brand-text"><?= htmlspecialchars($review['reviewer_name']) ?></h4>
                                            <span class="w-1 h-1 rounded-full bg-gray-300 hidden sm:block"></span>
                                            <span class="text-xs font-lato text-brand-gray uppercase tracking-wider"><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                                        </div>
                                        <div class="flex gap-0.5 text-yellow-400">
                                            <?php for($i=1; $i<=5; $i++): ?>
                                                <i data-feather="star" class="w-4 h-4 <?= $i <= $review['rating'] ? 'fill-current' : 'text-gray-300' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="text-sm font-lato text-gray-600 leading-relaxed max-w-3xl"><?= nl2br(htmlspecialchars($review['review_text'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div id="no-reviews-msg" class="text-center py-16 bg-gray-50/30 border border-gray-100 rounded-sm">
                            <i data-feather="message-square" class="w-10 h-10 mx-auto mb-4 text-gray-300 font-light"></i>
                            <p class="font-serif text-lg text-brand-text mb-1">No reviews yet</p>
                            <p class="font-lato text-sm text-brand-gray">Be the first to share your thoughts on this piece.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div id="custom-size-modal" class="modal-container hidden"><div id="custom-size-overlay" class="modal-overlay"></div><div class="modal-panel p-6"><div class="flex justify-between items-start pb-4"><div><h3 class="text-xl font-serif font-semibold">Custom Measurements</h3><p class="text-sm text-brand-gray mt-1">Please make sure you’re not wearing a padded bra when measuring.</p></div><button id="close-custom-size-btn" class="p-1 text-brand-gray hover:text-brand-text"><i data-feather="x" class="w-5 h-5"></i></button></div><div class="mt-4 space-y-6 modal-form-scrollable"><div id="custom-measurements-form" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
        <div><label for="back_size" class="text-sm font-medium text-brand-gray">Back</label><input type="text" id="back_size" class="form-input-sleek mt-1" placeholder="e.g., 16 in"/></div>
        <div><label for="bust_size" class="text-sm font-medium text-brand-gray">Bust</label><input type="text" id="bust_size" class="form-input-sleek mt-1" placeholder="e.g., 36 in"/></div>
        <div><label for="waist_size" class="text-sm font-medium text-brand-gray">Waist</label><input type="text" id="waist_size" class="form-input-sleek mt-1" placeholder="e.g., 29 in"/></div>
        <div><label for="hip_size" class="text-sm font-medium text-brand-gray">Hip</label><input type="text" id="hip_size" class="form-input-sleek mt-1" placeholder="e.g., 39 in"/></div>
        <div><label for="dress_length" class="text-sm font-medium text-brand-gray">Preferred dress length</label><input type="text" id="dress_length" class="form-input-sleek mt-1" placeholder="e.g., 45 in"/></div>
        <div><label for="underbust_size" class="text-sm font-medium text-brand-gray">Underbust</label><input type="text" id="underbust_size" class="form-input-sleek mt-1" placeholder="e.g., 30 in"></div>
        <div><label for="sleeve_length" class="text-sm font-medium text-brand-gray">Sleeve length</label><input type="text" id="sleeve_length" class="form-input-sleek mt-1" placeholder="e.g., 24 in"/></div>
        <div><label for="round_sleeve" class="text-sm font-medium text-brand-gray">Round sleeve</label><input type="text" id="round_sleeve" class="form-input-sleek mt-1" placeholder="e.g., 12 in"/></div>
        <div><label for="trouser_length" class="text-sm font-medium text-brand-gray">Trouser length with heels</label><input type="text" id="trouser_length" class="form-input-sleek mt-1" placeholder="e.g., 47 in"/></div>
        <div><label for="round_thigh" class="text-sm font-medium text-brand-gray">Round thigh</label><input type="text" id="round_thigh" class="form-input-sleek mt-1" placeholder="e.g., 24 in"/></div>
        <div><label for="round_knee" class="text-sm font-medium text-brand-gray">Round knee</label><input type="text" id="round_knee" class="form-input-sleek mt-1" placeholder="e.g., 16 in"/></div>
        <div><label for="shoulder_to_knee" class="text-sm font-medium text-brand-gray">Shoulder to knee</label><input type="text" id="shoulder_to_knee" class="form-input-sleek mt-1" placeholder="e.g., 38 in"/></div>
        <div><label for="shoulder_to_underbust" class="text-sm font-medium text-brand-gray">Shoulder to underbust</label><input type="text" id="shoulder_to_underbust" class="form-input-sleek mt-1" placeholder="e.g., 14 in"/></div>
        <div><label for="mini_skirt_length" class="text-sm font-medium text-brand-gray">Mini skirt length</label><input type="text" id="mini_skirt_length" class="form-input-sleek mt-1" placeholder="e.g., 15 in"/></div>
        <div><label for="waist_to_ankle_length" class="text-sm font-medium text-brand-gray">Waist to ankle length</label><input type="text" id="waist_to_ankle_length" class="form-input-sleek mt-1" placeholder="e.g., 40 in"/></div>
    </div><button id="save-measurements-btn" class="w-full bg-brand-text text-white py-3 mt-4 font-semibold hover:bg-gray-800 transition-colors">Confirm Measurements</button></div></div></div>
    
    <div id="size-chart-modal" class="modal-container hidden">
        <div id="size-chart-overlay" class="modal-overlay"></div>
        <div class="modal-panel p-6 max-w-2xl">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-xl font-serif font-semibold">Size Guide</h3>
                <button id="close-size-chart-btn" class="p-1 text-brand-gray hover:text-brand-text"><i data-feather="x" class="w-5 h-5"></i></button>
            </div>
            <div class="mt-4 modal-content-scrollable pr-2">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                        <button class="size-chart-tab whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-brand-text text-brand-text" data-target="size-chart-panel-1" type="button" role="tab" aria-controls="size-chart-panel-1" aria-selected="true">
                            Size Chart 1
                        </button>
                        <button class="size-chart-tab whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-target="size-chart-panel-2" type="button" role="tab" aria-controls="size-chart-panel-2" aria-selected="false">
                            Size Chart 2
                        </button>
                        <button class="size-chart-tab whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-target="size-chart-panel-3" type="button" role="tab" aria-controls="size-chart-panel-3" aria-selected="false">
                            Size Chart 3
                        </button>
                    </nav>
                </div>
                <!-- Tab Content Panels -->
                <div class="mt-5">
                    <div id="size-chart-panel-1" class="size-chart-content" role="tabpanel">
                        <img src="/images/sizechart1.jpg" alt="Size Chart 1" class="w-full h-auto rounded" />
                    </div>
                    <div id="size-chart-panel-2" class="size-chart-content hidden" role="tabpanel">
                        <img src="/images/sizechart2.jpg" alt="Size Chart 2" class="w-full h-auto rounded" />
                    </div>
                    <div id="size-chart-panel-3" class="size-chart-content hidden" role="tabpanel">
                        <img src="/images/sizechart3.jpg" alt="Size Chart 3" class="w-full h-auto rounded" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-white border-t border-gray-200"><div class="p-6 text-center"><p class="text-xs text-brand-gray">© <?=date('Y')?> <?=$site_name?>. All Rights Reserved.</p></div></footer>

<!-- ================================================================= -->
<!-- START OF UPDATED SCRIPT BLOCK                                     -->
<!-- ================================================================= -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Ensure feather is loaded and replaced early
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    const selectors = {
        sidebar: document.getElementById("sidebar"),
        openSidebarBtn: document.getElementById("open-sidebar-btn"),
        closeSidebarBtn: document.getElementById("close-sidebar-btn"),
        sidebarOverlay: document.getElementById("sidebar-overlay"),
        cartSidebar: document.getElementById("cart-sidebar"),
        openCartBtn: document.getElementById("open-cart-btn"),
        closeCartBtn: document.getElementById("close-cart-btn"),
        cartOverlay: document.getElementById("cart-overlay"),
        cartItemsContainer: document.getElementById("cart-items-container"),
        cartSubtotalEl: document.getElementById("cart-subtotal"),
        cartItemCountEl: document.getElementById("cart-item-count"),
        addToCartBtn: document.getElementById("add-to-cart-btn"),
        quantityMinusBtn: document.getElementById("quantity-minus"),
        quantityPlusBtn: document.getElementById("quantity-plus"),
        quantityDisplay: document.getElementById("quantity-display"),
        openCustomColorBtn: document.getElementById("open-custom-color-btn"),
        customColorContainer: document.getElementById("custom-color-input-container"),
        customColorInput: document.getElementById("custom-color"),
        mainImage: document.getElementById("main-product-image"),
        thumbnails: document.querySelectorAll(".thumbnail-img"),
        
        // --- COLOR & SIZE SELECTORS ---
        colorSelect: document.getElementById("color-select"), 
        selectedColorName: document.getElementById("selected-color-name"), // Used to display selected color text on product page
        
        productPriceEl: document.getElementById("product-price"),
        productOptionSelect: document.getElementById("product-option-select"),
        modals: [
            { modal: document.getElementById("custom-size-modal"), openBtn: document.getElementById("open-custom-size-btn"), closeBtn: document.getElementById("close-custom-size-btn"), overlay: document.getElementById("custom-size-overlay") },
            { modal: document.getElementById("size-chart-modal"), openBtn: document.getElementById("open-size-chart-btn"), closeBtn: document.getElementById("close-size-chart-btn"), overlay: document.getElementById("size-chart-overlay") }
        ],
        saveMeasurementsBtn: document.getElementById("save-measurements-btn"),

    };

    const showToast = (text, type = "success") => {
        Toastify({
            text, duration: 2500, newWindow: true, close: true, gravity: "top", position: "right", stopOnFocus: true,
            style: {
                background: type === "success" ? "linear-gradient(to right, #00b09b, #96c93d)" : "linear-gradient(to right, #ff5f6d, #ffc371)",
                "font-size": "14px"
            }
        }).showToast();
    };

    const formatCurrency = (amount, currency = 'NGN') => {
        if (currency === 'USD') {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
        }
        return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount).replace('NGN', '₦');
    };

    const toggleSidebar = (sidebar, shouldOpen) => {
        if (!sidebar) return;
        const isMenuSidebar = sidebar.id === 'sidebar';
        if (shouldOpen) {
            sidebar.classList.remove(isMenuSidebar ? "-translate-x-full" : "translate-x-full");
            document.body.classList.add("overflow-hidden");
        } else {
            sidebar.classList.add(isMenuSidebar ? "-translate-x-full" : "translate-x-full");
            // Only remove overflow-hidden if no other sidebar/modal is open
            if (!document.querySelector("#sidebar:not(.-translate-x-full), #cart-sidebar:not(.translate-x-full)") &&
                !document.querySelector(".modal-container:not(.hidden)")) {
                document.body.classList.remove("overflow-hidden");
            }
        }
    };

    const toggleModal = (modal, shouldOpen) => {
        if (!modal) return;
        if (shouldOpen) {
            modal.classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
        } else {
            modal.classList.add("hidden");
            // Only remove overflow-hidden if no other sidebar/modal is open
            if (!document.querySelector(".modal-container:not(.hidden)") &&
                !document.querySelector("#sidebar:not(.-translate-x-full), #cart-sidebar:not(.translate-x-full)")) {
                document.body.classList.remove("overflow-hidden");
            }
        }
    };

    const resetAddToCartButton = () => {
        if(selectors.addToCartBtn) {
            selectors.addToCartBtn.disabled = false;
            selectors.addToCartBtn.innerHTML = `<i data-feather="shopping-cart" class="w-5 h-5"></i><span>ADD TO CART</span>`;
            if (typeof feather !== 'undefined') feather.replace(); 
        }
    };

    const updateCartDisplay = async () => {
        try {
            // This fetch simulates getting the current cart state from the server/session
            const response = await fetch('/update-cart', { method: 'POST' });
            if (!response.ok) throw new Error('Network response was not ok.');
            const cartData = await response.json();
            if (cartData && cartData.status === 'success') {
                const container = selectors.cartItemsContainer;
                container.innerHTML = ''; 

                selectors.cartSubtotalEl.dataset.priceNgn = cartData.subtotal;

                if (cartData.items && cartData.items.length > 0) {
                    cartData.items.forEach(item => {
                        let optionsHtml = '';
                        
                        // 1. CHECK FOR COLOR INFORMATION
                        if (item.color_name) {
                            optionsHtml += `Color: ${item.color_name}`;
                        } else if (item.custom_color_name) {
                            optionsHtml += `Color: Custom (${item.custom_color_name})`;
                        }

                        // 2. CHECK FOR SIZE/OPTION INFORMATION
                        if (item.option_name) {
                            optionsHtml += (optionsHtml ? ' / ' : '') + item.option_name;
                        } else if (item.size_name) {
                            optionsHtml += (optionsHtml ? ' / ' : '') + item.size_name;
                        } else if (item.custom_size_details && item.custom_size_details !== '{}') {
                            optionsHtml += (optionsHtml ? ' / ' : '') + 'Custom Size';
                        }
                        // If only size/option is showing, it means the keys color_name or custom_color_name 
                        // were missing from the server response (item object).

                        const itemHtml = `
                            <div class="flex gap-4 py-4 border-b border-gray-200 last:border-b-0">
                                <img src="/${item.product_image}" alt="${item.product_name}" class="w-20 h-24 object-cover">
                                <div class="flex-1 flex flex-col justify-between">
                                    <div>
                                        <h4 class="font-semibold text-sm">${item.product_name}</h4>
                                        <!-- Displays the combined optionsHtml string -->
                                        <p class="text-xs text-brand-gray">${optionsHtml}</p>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <p class="text-sm font-medium price-display" data-price-ngn="${item.total_price}">${formatCurrency(item.total_price)}</p>
                                        <p class="text-xs text-brand-gray">Qty: ${item.quantity}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-center gap-2 self-start">
                                    <button class="remove-from-cart-btn p-1 text-brand-gray hover:text-brand-red" data-cart-item-id="${item.id}">
                                        <i data-feather="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        container.insertAdjacentHTML('beforeend', itemHtml);
                    });
                } else {
                    container.innerHTML = `
                        <div id="empty-cart-message" class="flex flex-col items-center justify-center h-full text-center">
                            <i data-feather="shopping-bag" class="w-16 h-16 text-gray-300 mb-4"></i>
                            <p class="text-brand-gray">Your cart is empty.</p>
                        </div>
                    `;
                }

                const lineItemCount = cartData.items.length;
                selectors.cartItemCountEl.textContent = lineItemCount;
                selectors.cartItemCountEl.style.display = lineItemCount > 0 ? 'flex' : 'none';
                
                if (typeof feather !== 'undefined') feather.replace(); 
                
                updateAllPrices(document.querySelector('.currency-switcher a.active')?.dataset.currency || 'NGN');
            } else {
                throw new Error(cartData.message || 'Failed to parse cart data.');
            }
        } catch (error) {
            console.error('Failed to update cart display:', error);
            selectors.cartItemsContainer.innerHTML = `<div class="text-center text-brand-gray">Could not load cart.</div>`;
        }
    };

    const handleDeleteFromCart = async (cartItemId) => {
        try {
            const response = await fetch('/delete-cart', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ cartItemId: cartItemId })
            });
            const result = await response.json();
            if(response.ok && result.status === 'success') {
                showToast(result.message);
                await updateCartDisplay();
            } else {
                showToast(result.message || 'Failed to remove item.', 'error');
            }
        } catch (error) {
            console.error('Delete Error:', error);
            showToast('A network error occurred while deleting.', 'error');
        }
    };

    const handleAddToCart = async () => {
        const btn = selectors.addToCartBtn;
        if (!btn) return;

        btn.disabled = true;
        btn.innerHTML = `<i data-feather="loader" class="w-5 h-5 animate-spin"></i><span>ADDING...</span>`;
        if (typeof feather !== 'undefined') feather.replace();

        const payload = {
            productId: parseInt(btn.dataset.productId),
            quantity: parseInt(selectors.quantityDisplay.textContent)
        };

        const customColorValue = selectors.customColorInput.value.trim();
        const selectedColorOption = selectors.colorSelect ? selectors.colorSelect.options[selectors.colorSelect.selectedIndex] : null; 

        const customSizeDetails = Array.from(document.querySelectorAll("#custom-measurements-form input")).reduce((acc, input) => {
            const val = input.value.trim();
            if(val) acc[input.id] = val;
            return acc;
        }, {});
        const hasCustomMeasurements = Object.keys(customSizeDetails).length > 0;

        const selectedOption = selectors.productOptionSelect ? selectors.productOptionSelect.options[selectors.productOptionSelect.selectedIndex] : null;

        // --- COLOR VALIDATION ---
        if (selectors.colorSelect && selectors.colorSelect.options.length > 1) { 
            if ((!selectedColorOption || selectedColorOption.value === "") && !customColorValue) {
                showToast("Please select a color or provide a custom one.", "error");
                resetAddToCartButton();
                return;
            }
        } else if (selectors.customColorInput && !customColorValue) {
            showToast("A custom color is required for this item.", "error");
            resetAddToCartButton();
            return;
        }

        // --- COLOR PAYLOAD (This sends the color to the server) ---
        if (customColorValue) {
            payload.customColor = customColorValue;
        } else if (selectedColorOption && selectedColorOption.value !== "") {
            payload.colorId = selectedColorOption.value;
        }

        // Option/Size validation — only required when named options exist
        const autoVariantId = selectors.productPriceEl?.dataset.autoVariantId;
        const hasStandardOptions = selectors.productOptionSelect && selectors.productOptionSelect.options.length > 1;
        if (hasStandardOptions && !autoVariantId) { 
            if (!hasCustomMeasurements && (!selectedOption || selectedOption.value === "")) {
                showToast("Please select an option/size or provide custom measurements.", "error");
                resetAddToCartButton();
                return;
            }
        }
        // If no standard options exist, both variant selection and custom measurements are optional

        if (hasCustomMeasurements) {
            payload.customSizeDetails = customSizeDetails;
        } else if (autoVariantId) {
            // Nameless variant — auto-attach the variant ID
            payload.priceVariantId = parseInt(autoVariantId);
        } else if (selectedOption && selectedOption.value !== "") {
            const optionType = selectedOption.dataset.type;
            const optionId = parseInt(selectedOption.dataset.id);
            if (optionType === 'variant') {
                payload.priceVariantId = optionId;
            } else if (optionType === 'size') {
                payload.sizeId = optionId;
            }
        }

        try {
            const response = await fetch("/cart", {
                method: "POST",
                headers: { "Content-Type": "application/json", "Accept": "application/json" },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (response.ok && result.status === "success") {
                showToast("Item added to cart!");
                await updateCartDisplay();
                toggleSidebar(selectors.cartSidebar, true);
            } else {
                showToast(result.message || "Could not add item.", "error");
            }
        } catch (error) {
            console.error("Fetch Error:", error);
            showToast("A network error occurred.", "error");
        } finally {
            resetAddToCartButton();
        }
    };

    const USD_RATE = <?= USD_EXCHANGE_RATE ?>;
    const INITIAL_CURRENCY = '<?= $current_currency ?>';

    const updateAllPrices = (targetCurrency) => {
        const productOptionSelect = document.getElementById('product-option-select');
        const hasSelection = productOptionSelect && productOptionSelect.value !== "";

        document.querySelectorAll('.price-display').forEach(el => {
            const ngnPrice = parseFloat(el.dataset.priceNgn);
            if (isNaN(ngnPrice)) return;

            // Handle main product price with potential range
            if (el.id === 'product-price') {
                const minRaw = el.dataset.rangeMin;
                const maxRaw = el.dataset.rangeMax;
                
                // Show range if no specific variant is selected and range exists
                if (!hasSelection && minRaw && maxRaw) {
                    const min = parseFloat(minRaw);
                    const max = parseFloat(maxRaw);
                    
                    if (!isNaN(min) && !isNaN(max) && min !== max) {
                        const fMin = (targetCurrency === 'USD') ? formatCurrency(min / USD_RATE, 'USD') : formatCurrency(min, 'NGN');
                        const fMax = (targetCurrency === 'USD') ? formatCurrency(max / USD_RATE, 'USD') : formatCurrency(max, 'NGN');
                        el.innerHTML = `<span class="price-from-label">From </span>${fMin} <span class="text-lg text-brand-gray">–</span> ${fMax}`;
                        return;
                    }
                }
            }

            // Fallback: simple price display (single value)
            let newPrice = (targetCurrency === 'USD') ? ngnPrice / USD_RATE : ngnPrice;
            el.textContent = formatCurrency(newPrice, targetCurrency);
        });
    };
    // --- BIND EVENT LISTENERS ---
    if(selectors.addToCartBtn) selectors.addToCartBtn.addEventListener("click", handleAddToCart);

    // Sidebar and Cart Sidebar
    if(selectors.openSidebarBtn) selectors.openSidebarBtn.addEventListener("click", () => toggleSidebar(selectors.sidebar, true));
    if(selectors.closeSidebarBtn) selectors.closeSidebarBtn.addEventListener("click", () => toggleSidebar(selectors.sidebar, false));
    if(selectors.sidebarOverlay) selectors.sidebarOverlay.addEventListener("click", () => toggleSidebar(selectors.sidebar, false));

    if(selectors.openCartBtn) selectors.openCartBtn.addEventListener("click", async () => { await updateCartDisplay(); toggleSidebar(selectors.cartSidebar, true); });
    if(selectors.closeCartBtn) selectors.closeCartBtn.addEventListener("click", () => toggleSidebar(selectors.cartSidebar, false));
    if(selectors.cartOverlay) selectors.cartOverlay.addEventListener("click", () => toggleSidebar(selectors.cartSidebar, false));

    // Cart item removal
    if(selectors.cartItemsContainer) {
        selectors.cartItemsContainer.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-from-cart-btn');
            if (removeBtn) {
                const cartItemId = parseInt(removeBtn.dataset.cartItemId);
                if (!isNaN(cartItemId)) {
                    handleDeleteFromCart(cartItemId);
                } else {
                    console.error("Invalid cart item ID.");
                }
            }
        });
    }

    // Quantity controls
    if(selectors.quantityPlusBtn) selectors.quantityPlusBtn.addEventListener("click", () => (selectors.quantityDisplay.textContent = parseInt(selectors.quantityDisplay.textContent) + 1));
    if(selectors.quantityMinusBtn) selectors.quantityMinusBtn.addEventListener("click", () => {
        if (parseInt(selectors.quantityDisplay.textContent) > 1) {
            selectors.quantityDisplay.textContent = parseInt(selectors.quantityDisplay.textContent) - 1;
        }
    });

    // Custom color input toggle
    if (selectors.openCustomColorBtn) {
        selectors.openCustomColorBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (selectors.customColorContainer) {
                const isNowClosed = selectors.customColorContainer.classList.toggle("is-closed");
                if (!isNowClosed) {
                    if (selectors.colorSelect) selectors.colorSelect.value = ""; 
                    if (selectors.customColorInput) selectors.customColorInput.focus();
                    // Update display name
                    if (selectors.selectedColorName) selectors.selectedColorName.textContent = "Custom Color"; 
                } else if (selectors.selectedColorName) {
                    selectors.selectedColorName.textContent = ""; 
                }
            }
        });
    }

    // Old thumbnail logic removed to be replaced by scroll sync logic

    // --- Color Dropdown listener (Updates color name display on product page) ---
    if (selectors.colorSelect) {
        selectors.colorSelect.addEventListener("change", function() {
            const selectedOption = this.options[this.selectedIndex];
            const colorName = selectedOption.textContent.trim(); 
            
            if (selectedOption.value !== "") {
                if (selectors.customColorContainer) selectors.customColorContainer.classList.add("is-closed");
                if (selectors.customColorInput) selectors.customColorInput.value = "";
                // Show the selected color name
                if (selectors.selectedColorName) selectors.selectedColorName.textContent = colorName; 
            } else {
                if (selectors.selectedColorName) selectors.selectedColorName.textContent = "";
            }
        });
    }

    // Animate price change helper
    const animatePrice = (priceEl, newPriceNgn) => {
        priceEl.classList.remove('price-pop');
        // Force reflow to restart the animation
        void priceEl.offsetWidth;
        priceEl.dataset.priceNgn = newPriceNgn;
        priceEl.classList.add('price-pop');
        updateAllPrices(document.querySelector('.currency-switcher a.active')?.dataset.currency || 'NGN');
    };

    // Handle product option/size dropdown change
    if (selectors.productOptionSelect) {
        selectors.productOptionSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption) return;
            const priceModifier = parseFloat(selectedOption.dataset.priceModifier || 0);
            const basePrice = parseFloat(selectors.productPriceEl.dataset.basePrice);

            if (selectedOption.value === "") {
                const rMin = selectors.productPriceEl.dataset.rangeMin;
                animatePrice(selectors.productPriceEl, rMin || basePrice);
            } else {
                animatePrice(selectors.productPriceEl, basePrice + priceModifier);
            }

            // Stock Display Logic & Button State
            const stockDisplay = document.getElementById('stock-display');
            let isOutOfStock = false;

            if (stockDisplay) {
                if (selectedOption.value !== "") {
                    const stock = selectedOption.dataset.stock;
                    if (stock !== "" && stock !== undefined && stock !== "null") {
                        if (parseInt(stock) > 0) {
                            stockDisplay.innerHTML = `<span class="text-emerald-600">In Stock (${stock} available)</span>`;
                            isOutOfStock = false;
                        } else {
                            stockDisplay.innerHTML = `<span class="text-brand-red">Out of Stock</span>`;
                            isOutOfStock = true;
                        }
                    } else {
                         // Fallback for standard sizes or if stock not set (assume in stock)
                         stockDisplay.innerHTML = `<span class="text-emerald-600">In Stock</span>`;
                         isOutOfStock = false;
                    }
                } else {
                    stockDisplay.innerHTML = `<span class="text-brand-gray">Select an option to view stock</span>`;
                    isOutOfStock = false; // logic: keep enabled or depend on validation
                }
            }
            
            // Update Button State
            if (selectors.addToCartBtn) {
                if (isOutOfStock) {
                    selectors.addToCartBtn.disabled = true;
                    selectors.addToCartBtn.innerHTML = `<span>Out of Stock</span>`;
                    selectors.addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    selectors.addToCartBtn.disabled = false;
                    selectors.addToCartBtn.innerHTML = `<i data-feather="shopping-cart" class="w-5 h-5"></i><span>ADD TO CART</span>`;
                    selectors.addToCartBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    if (typeof feather !== 'undefined') feather.replace();
                }
            }

            if (selectedOption.value !== "") {
                document.querySelectorAll("#custom-measurements-form input").forEach(input => input.value = '');
            }
        });
    }

    // Save custom measurements button
    if (selectors.saveMeasurementsBtn) {
        selectors.saveMeasurementsBtn.addEventListener("click", (e) => {
            e.preventDefault();
            showToast("Measurements noted. Click 'Add to Cart' to confirm.");
            toggleModal(document.getElementById("custom-size-modal"), false);

            if (selectors.productOptionSelect) {
                selectors.productOptionSelect.value = "";
                // Dispatch a change event so the price reverts correctly 
                // and any other standard option logic resets
                selectors.productOptionSelect.dispatchEvent(new Event('change'));
            }
        });
    }

    // Modal handling
    selectors.modals.forEach(({ modal, openBtn, closeBtn, overlay }) => {
        if (openBtn) {
            openBtn.addEventListener("click", (e) => {
                e.preventDefault();
                if (modal && modal.id === "custom-size-modal") {
                    if (selectors.productOptionSelect) {
                        selectors.productOptionSelect.value = "";
                        selectors.productOptionSelect.dispatchEvent(new Event('change'));
                    } else if (selectors.productPriceEl) {
                        const basePrice = parseFloat(selectors.productPriceEl.dataset.basePrice);
                        const rMin = selectors.productPriceEl.dataset.rangeMin;
                        animatePrice(selectors.productPriceEl, rMin || basePrice);
                    }
                }
                toggleModal(modal, true);
            });
        }
        if (closeBtn) { closeBtn.addEventListener("click", () => toggleModal(modal, false)); }
        if (overlay) { overlay.addEventListener("click", () => toggleModal(modal, false)); }
    });

    // Escape key listener
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            toggleSidebar(selectors.sidebar, false);
            toggleSidebar(selectors.cartSidebar, false);
            selectors.modals.forEach(({ modal }) => {
                if (modal && !modal.classList.contains("hidden")) toggleModal(modal, false);
            });
        }
    });

    // Currency switcher
    document.querySelectorAll('.currency-switcher a.currency-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetCurrency = this.dataset.currency;
            
            // Toggle active classes across all switcher instances
            document.querySelectorAll(`.currency-switcher a.currency-link`).forEach(l => {
                if (l.dataset.currency === targetCurrency) {
                    l.classList.add('active', 'bg-white', 'shadow-sm', 'text-brand-text', 'border-brand-text');
                    l.classList.remove('text-brand-gray', 'hover:text-brand-text', 'border-gray-200');
                } else {
                    l.classList.remove('active', 'bg-white', 'shadow-sm', 'text-brand-text', 'border-brand-text');
                    l.classList.add('text-brand-gray', 'hover:text-brand-text', 'border-gray-200');
                }
            });

            updateAllPrices(targetCurrency);
            const expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + 30);
            document.cookie = `user_currency=${targetCurrency}; expires=${expiryDate.toUTCString()}; path=/`;
            fetch('/currency', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ currency: targetCurrency }) });
        });
    });

    // --- INITIALIZATION ---
    if (selectors.productOptionSelect) {
        const initialSelectedOption = selectors.productOptionSelect.options[selectors.productOptionSelect.selectedIndex];
        if (initialSelectedOption && initialSelectedOption.value !== "") {
            const priceModifier = parseFloat(initialSelectedOption.dataset.priceModifier || 0);
            const basePrice = parseFloat(selectors.productPriceEl.dataset.basePrice);
            const initialDisplayPrice = basePrice + priceModifier;
            selectors.productPriceEl.dataset.priceNgn = initialDisplayPrice;
        } else {
            selectors.productPriceEl.dataset.priceNgn = selectors.productPriceEl.dataset.basePrice;
        }
    }

    if (INITIAL_CURRENCY === 'USD') {
        updateAllPrices('USD');
    } else {
        updateAllPrices('NGN');
    }

    updateCartDisplay();

    // --- Size Chart Tabs Logic ---
    const sizeChartTabs = document.querySelectorAll('.size-chart-tab');
    const sizeChartPanels = document.querySelectorAll('.size-chart-content');

    if (sizeChartTabs.length > 0 && sizeChartPanels.length > 0) {
        sizeChartTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetId = tab.dataset.target;
                const targetPanel = document.getElementById(targetId);

                sizeChartTabs.forEach(t => {
                    t.classList.remove('border-brand-text', 'text-brand-text');
                    t.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    t.setAttribute('aria-selected', 'false');
                });

                tab.classList.add('border-brand-text', 'text-brand-text');
                tab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                tab.setAttribute('aria-selected', 'true');

                sizeChartPanels.forEach(panel => {
                    panel.classList.add('hidden');
                });

                if (targetPanel) {
                    targetPanel.classList.remove('hidden');
                }
            });
        });
    }
    // --- Size Chart Modal Open/Close Logic ---
    const openSizeChartBtn = document.getElementById('open-size-chart-btn');
    const closeSizeChartBtn = document.getElementById('close-size-chart-btn');
    const sizeChartModal = document.getElementById('size-chart-modal');
    const sizeChartOverlay = document.getElementById('size-chart-overlay');

    if (openSizeChartBtn && sizeChartModal) {
        openSizeChartBtn.addEventListener('click', (e) => {
            e.preventDefault();
            sizeChartModal.classList.remove('hidden');
        });
    }

    if (closeSizeChartBtn && sizeChartModal) {
        closeSizeChartBtn.addEventListener('click', () => {
             sizeChartModal.classList.add('hidden');
        });
    }

    if (sizeChartOverlay && sizeChartModal) {
        sizeChartOverlay.addEventListener('click', () => {
             sizeChartModal.classList.add('hidden');
        });
    }


    // --- REVIEW SYSTEM JS ---
    const starBtns = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('rating-value');
    
    // Star Rating Click Interaction
    if (starBtns.length) {
        starBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const value = parseInt(btn.dataset.value);
                ratingInput.value = value;
                // Update UI visually
                starBtns.forEach(b => {
                    const bVal = parseInt(b.dataset.value);
                    const icon = b.querySelector('svg.feather') || b.querySelector('i'); // Handle both cases (before/after replace)
                    
                    if (bVal <= value) {
                        b.classList.remove('text-gray-300');
                        b.classList.add('text-yellow-400');
                        if(icon) icon.classList.add('fill-current'); 
                    } else {
                        b.classList.add('text-gray-300');
                        b.classList.remove('text-yellow-400');
                        if(icon) icon.classList.remove('fill-current');
                    }
                });
            });
        });
    }


    // AJAX Review Submission
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Basic validation
            if (!ratingInput.value) {
                if(typeof Toastify !== 'undefined') {
                     Toastify({ text: "Please select a star rating", duration: 3000, style: { background: "linear-gradient(to right, #ff5f6d, #ffc371)" } }).showToast();
                } else alert("Please select a star rating");
                return;
            }

            const submitBtn = document.getElementById('submit-review-btn');
            const originalBtnText = submitBtn.textContent;
            submitBtn.textContent = "Submitting...";
            submitBtn.disabled = true;

            const formData = new FormData(reviewForm);

            try {
                const response = await fetch('/submit-review', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();

                if (result.status === 'success') {
                     if(typeof Toastify !== 'undefined') {
                        Toastify({ text: result.message, duration: 3000, style: { background: "linear-gradient(to right, #00b09b, #96c93d)" } }).showToast();
                    } else alert(result.message);

                    // Reset form
                    reviewForm.reset();
                    ratingInput.value = '';
                    starBtns.forEach(b => {
                        b.classList.add('text-gray-300');
                        b.classList.remove('text-yellow-400');
                        const icon = b.querySelector('svg.feather') || b.querySelector('i');
                        if(icon) icon.classList.remove('fill-current');
                    });
                    if (typeof feather !== 'undefined') feather.replace();

                    // Update Summary Stats
                    if (result.new_total !== undefined) {
                        const avgValEl = document.getElementById('avg-rating-value');
                        const countEl = document.getElementById('reviews-count');
                        const starsContainer = document.getElementById('avg-rating-stars');

                        if(avgValEl) avgValEl.textContent = result.new_average;
                        if(countEl) countEl.textContent = result.new_total + (result.new_total === 1 ? " review" : " reviews");
                        
                        if(starsContainer) {
                            let avgStarsHtml = '';
                            for(let i=1; i<=5; i++) {
                                const fClass = i <= Math.round(result.new_average) ? 'fill-current' : 'text-gray-300';
                                avgStarsHtml += `<i data-feather="star" class="w-5 h-5 ${fClass}"></i>`;
                            }
                            starsContainer.innerHTML = avgStarsHtml;
                        }
                    }

                    // Prepend new review to list
                    const reviewsList = document.getElementById('reviews-list');
                    const noReviewsMsg = document.getElementById('no-reviews-msg');
                    if (noReviewsMsg) noReviewsMsg.remove();

                    const review = result.review;
                    let starsHtml = '';
                    for(let i=1; i<=5; i++) {
                         let fillClass = i <= review.rating ? 'fill-current' : 'text-gray-300';
                         starsHtml += `<i data-feather="star" class="w-4 h-4 ${fillClass}"></i>`;
                    }
                    
                    const newReviewHtml = `
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex gap-4 animate-fade-in-up">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-brand-gray font-bold text-lg">
                                    ${review.avatar_letter}
                                </div>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-semibold text-brand-text">${review.reviewer_name}</h4>
                                    <span class="text-xs text-brand-gray">${review.created_at}</span>
                                </div>
                                <div class="flex gap-0.5 text-yellow-400 mb-2">
                                    ${starsHtml}
                                </div>
                                <p class="text-sm text-gray-600 leading-relaxed">${review.review_text}</p>
                            </div>
                        </div>
                    `;
                    
                    reviewsList.insertAdjacentHTML('afterbegin', newReviewHtml);
                    if (typeof feather !== 'undefined') feather.replace();

                } else {
                     if(typeof Toastify !== 'undefined') {
                        Toastify({ text: result.message, duration: 3000, style: { background: "linear-gradient(to right, #ff5f6d, #ffc371)" } }).showToast();
                    } else alert(result.message);
                }

            } catch (error) {
                console.error("Review Submission Error:", error);
                if(typeof Toastify !== 'undefined') {
                    Toastify({ text: "An error occurred. Please try again.", duration: 3000, style: { background: "linear-gradient(to right, #ff5f6d, #ffc371)" } }).showToast();
                } else alert("An error occurred.");
            } finally {
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    }

    // --- Draggable Carousel with Navigation ---
    const mainCarousel = document.getElementById('main-image-slider');
    const prevBtn = document.getElementById('slider-prev');
    const nextBtn = document.getElementById('slider-next');
    const thumbs = document.querySelectorAll('.thumbnail-img');
    const thumbContainers = document.querySelectorAll('#thumbnail-gallery .skeleton-container');

    if (mainCarousel) {
        let isDown = false;
        let startX;
        let scrollLeft;

        // Mouse drag logic
        mainCarousel.addEventListener('mousedown', (e) => {
            isDown = true;
            mainCarousel.classList.add('cursor-grabbing');
            mainCarousel.classList.remove('snap-x'); // remove snapping while dragging
            startX = e.pageX - mainCarousel.offsetLeft;
            scrollLeft = mainCarousel.scrollLeft;
        });

        const stopDrag = () => {
            if(!isDown) return;
            isDown = false;
            mainCarousel.classList.remove('cursor-grabbing');
            mainCarousel.classList.add('snap-x');
        };

        mainCarousel.addEventListener('mouseleave', stopDrag);
        mainCarousel.addEventListener('mouseup', stopDrag);

        mainCarousel.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - mainCarousel.offsetLeft;
            const walk = (x - startX) * 2;
            mainCarousel.scrollLeft = scrollLeft - walk;
        });

        // Navigation Arrows
        const scrollAmount = mainCarousel.clientWidth;

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                mainCarousel.scrollBy({ left: -mainCarousel.clientWidth, behavior: 'smooth' });
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                mainCarousel.scrollBy({ left: mainCarousel.clientWidth, behavior: 'smooth' });
            });
        }

        // Thumbnail Click Logic
        thumbs.forEach((thumb, index) => {
            thumb.parentElement.addEventListener('click', () => {
                const scrollPos = index * mainCarousel.clientWidth;
                mainCarousel.scrollTo({ left: scrollPos, behavior: 'smooth' });
            });
        });

        // Sync Thumbnail Active State on scroll
        mainCarousel.addEventListener('scroll', () => {
            const index = Math.round(mainCarousel.scrollLeft / mainCarousel.clientWidth);
            thumbContainers.forEach((container, i) => {
                if (i === index) {
                    container.classList.add('border-brand-text', 'opacity-80', 'active-thumbnail');
                    container.classList.remove('border-transparent', 'opacity-60');
                } else {
                    container.classList.remove('border-brand-text', 'opacity-80', 'active-thumbnail');
                    container.classList.add('border-transparent', 'opacity-60');
                }
            });
        });
        
        // Handle window resize dynamically adjusting scroll amounts
        window.addEventListener('resize', () => {
             const index = Math.round(mainCarousel.scrollLeft / mainCarousel.clientWidth);
             mainCarousel.scrollTo({ left: index * mainCarousel.clientWidth, behavior: 'auto' });
        });
    }

    // --- Custom Dropdowns Logic ---
    const customDropdowns = document.querySelectorAll('.custom-dropdown-container');

    // Close all open dropdowns
    function closeAllDropdowns(except = null) {
        customDropdowns.forEach(dropdown => {
            if (dropdown !== except) {
                const menu = dropdown.querySelector('.custom-dropdown-menu');
                const icon = dropdown.querySelector('.dropdown-icon');
                if (menu && !menu.classList.contains('hidden')) {
                    menu.classList.add('opacity-0', 'translate-y-[-10px]');
                    setTimeout(() => menu.classList.add('hidden'), 200);
                    icon.style.transform = 'rotate(0deg)';
                }
            }
        });
    }

    // Handle clicks outside of dropdowns
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-dropdown-container')) {
            closeAllDropdowns();
        }
    });

    customDropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.custom-dropdown-trigger');
        const menu = dropdown.querySelector('.custom-dropdown-menu');
        const icon = dropdown.querySelector('.dropdown-icon');
        const selectedText = dropdown.querySelector('.selected-text');
        const hiddenSelect = dropdown.querySelector('select.hidden');
        const options = dropdown.querySelectorAll('.custom-option');

        if (!trigger || !menu || !hiddenSelect) return;

        // Toggle dropdown open/close
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const isOpen = !menu.classList.contains('hidden');
            
            closeAllDropdowns(dropdown); // close others

            if (isOpen) {
                menu.classList.add('opacity-0', 'translate-y-[-10px]');
                setTimeout(() => menu.classList.add('hidden'), 200);
                icon.style.transform = 'rotate(0deg)';
            } else {
                menu.classList.remove('hidden');
                // trigger reflow
                void menu.offsetWidth;
                menu.classList.remove('opacity-0', 'translate-y-[-10px]');
                icon.style.transform = 'rotate(180deg)';
            }
        });

        // Handle option selection
        options.forEach(option => {
            option.addEventListener('click', (e) => {
                e.stopPropagation();
                
                const val = option.dataset.value;
                const textHTML = option.innerHTML; // get the HTML (including color dots if they exist)
                
                // Update trigger display
                selectedText.innerHTML = textHTML;
                
                // Update hidden select and MUST trigger 'change' event manually so the rest of the JS catches it
                hiddenSelect.value = val;
                hiddenSelect.dispatchEvent(new Event('change'));

                // Close menu
                menu.classList.add('opacity-0', 'translate-y-[-10px]');
                setTimeout(() => menu.classList.add('hidden'), 200);
                icon.style.transform = 'rotate(0deg)';
            });
        });
        
        // Listen to hidden select physical changes (e.g., if JS resets it)
        hiddenSelect.addEventListener('change', () => {
            const currentVal = hiddenSelect.value;
            // find matching custom option
            const matchingOption = Array.from(options).find(opt => opt.dataset.value === currentVal);
            if (matchingOption) {
                 selectedText.innerHTML = matchingOption.innerHTML;
            } else {
                 // fallback if no match (e.g., reset to empty string)
                 const emptyOpt = Array.from(options).find(opt => opt.dataset.value === "");
                 if(emptyOpt) selectedText.innerHTML = emptyOpt.innerHTML;
            }
        });

        // Initialize display if hidden select has a pre-selected value
        if (hiddenSelect.value !== '') {
            const initialOption = Array.from(options).find(opt => opt.dataset.value === hiddenSelect.value);
            if(initialOption) {
                selectedText.innerHTML = initialOption.innerHTML;
            }
        }
    });

});

</script>

</body>
</html>