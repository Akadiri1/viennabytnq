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

// Fetch price variants
$priceVariantsStmt = $conn->prepare("SELECT id, variant_name, price FROM product_price_variants WHERE product_id = ? ORDER BY price ASC");
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
            'price_modifier' => $variant['price'] - $singleProduct['price'] // Store price difference
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


// Set display price to base price since no option is selected initially
$displayPrice = $singleProduct['price'];
$sqlImages = "SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC";
$imagesStmt = $conn->prepare($sqlImages);
$imagesStmt->execute([$id]);
$productImages = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
$mainImageUrl = $singleProduct['image_one'];
$sqlColors = "SELECT c.id, c.name AS color_name, c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id = ? ORDER BY c.id ASC";
$colorsStmt = $conn->prepare($sqlColors);
$colorsStmt->execute([$id]);
$availableColors = $colorsStmt->fetchAll(PDO::FETCH_ASSOC);

$relatedProducts = [];
if (!empty($singleProduct['collection_id'])) {
    $relatedProductsQuery = "SELECT * FROM panel_products WHERE visibility = 'show' AND id != ? AND collection_id = ? ORDER BY RAND() LIMIT 4";
    $relatedStmt = $conn->prepare($relatedProductsQuery);
    $relatedStmt->execute([$id, $singleProduct['collection_id']]);
    $relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
}
if (count($relatedProducts) < 4) {
    $limit = 4 - count($relatedProducts);
    $fallbackQuery = "SELECT * FROM panel_products WHERE visibility = 'show' AND id != ? ORDER BY RAND() LIMIT $limit";
    $fallbackStmt = $conn->prepare($fallbackQuery);
    $fallbackStmt->execute([$id]);
    $relatedProducts = array_merge($relatedProducts, $fallbackStmt->fetchAll(PDO::FETCH_ASSOC));
}


function getLiveUsdToNgnRate()
{
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
        tailwind.config = { theme: { extend: { colors: { "brand-bg": "#F9F6F2", "brand-text": "#1A1A1A", "brand-gray": "#6B7280", "brand-red": "#EF4444", }, fontFamily: { sans: ["Inter", "ui-sans-serif", "system-ui"], serif: ["Cormorant Garamond", "serif"], }, }, }, };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin /><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://unpkg.com/feather-icons"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .thumbnail-img { border: 2px solid transparent; transition: border-color 0.2s ease-in-out; } .active-thumbnail { border-color: #1a1a1a; } .color-swatch { width: 1.75rem; height: 1.75rem; border-radius: 9999px; cursor: pointer; border: 1px solid #d1d5db; transition: all 0.2s ease-in-out; } .active-color { transform: scale(1.1); box-shadow: 0 0 0 3px #1a1a1a; border-color: #1a1a1a; } .size-btn { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; font-weight: 500; cursor: pointer; transition: all 0.2s; font-size: 0.875rem; min-width: 40px; text-align: center; } .size-btn:hover { border-color: #1a1a1a; } .active-size { background-color: #1a1a1a; color: white; border-color: #1a1a1a; }
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
        .scrollbar-thin::-webkit-scrollbar { width: 4px; height: 4px; } .scrollbar-thin::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 20px; }
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

    <!-- CART SIDEBAR -->
    <div id="cart-sidebar" class="fixed inset-0 z-[60] transform translate-x-full transition-transform duration-300 ease-in-out"><div id="cart-overlay" class="absolute inset-0 bg-black/40 cursor-pointer"></div><div class="relative w-full max-w-md ml-auto h-full bg-brand-bg shadow-2xl flex flex-col"><div class="p-6 flex justify-between items-center border-b border-gray-200"><h2 id="cart-title" class="text-2xl font-serif font-semibold">Your Cart</h2><button id="close-cart-btn" class="p-2 text-brand-gray hover:text-brand-text"><i data-feather="x" class="h-6 w-6"></i></button></div><div id="cart-items-container" class="flex-grow p-6 overflow-y-auto"></div><div class="p-6 border-t border-gray-200 space-y-4 bg-brand-bg"><div class="flex justify-between font-semibold"><span>Subtotal</span><span id="cart-subtotal" class="price-display" data-price-ngn="0">₦0.00</span></div><a href="/view-cart" class="block w-full bg-transparent text-brand-text border border-brand-text py-3 text-center font-semibold hover:bg-gray-100 transition-colors">VIEW CART</a><a href="/checkout" class="block w-full bg-brand-text text-white py-3 text-center font-semibold hover:bg-gray-800 transition-colors">CHECKOUT</a></div></div></div>

    <!-- HEADER - MODIFIED -->
    <header class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40"><div class="container mx-auto px-4 sm:px-6 lg:px-8"><div class="flex items-center justify-between h-16"><div class="flex-1 flex justify-start"><button id="open-sidebar-btn" class="p-2 text-brand-text hover:text-brand-gray"><i data-feather="menu" class="h-6 w-6"></i></button></div><div class="flex-shrink-0 text-center"><a href="/home"><div class="text-1xl font-serif font-bold tracking-widest"><?=$site_name?></div></a></div><div class="flex-1 flex items-center justify-end space-x-4"><div class="currency-switcher text-sm"><a href="#" class="currency-link <?= ($current_currency === 'NGN') ? 'active' : '' ?>" data-currency="NGN">NGN</a><span class="mx-1 text-brand-gray">/</span><a href="#" class="currency-link <?= ($current_currency === 'USD') ? 'active' : '' ?>" data-currency="USD">USD</a></div><button id="open-cart-btn" class="p-2 text-brand-text hover:text-brand-gray relative"><i data-feather="shopping-bag" class="h-5 w-5"></i><span id="cart-item-count" class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-brand-red text-white text-xs flex items-center justify-center font-bold" style="font-size: 8px; display: none;">0</span></button></div></div></div></header>

    <section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('/<?= htmlspecialchars($singleProduct['image_one'] ?? '') ?>');"><div class="absolute inset-0 bg-black/30"></div><div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4"><nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb"><ol class="list-none p-0 inline-flex items-center"><li class="flex items-center"><a href="/home" class="hover:underline">Home</a><i data-feather="chevron-right" class="h-4 w-4 mx-2"></i></li><li class="flex items-center"><a href="/shop" class="hover:underline">Shop</a></li></ol></nav><h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4"><?= htmlspecialchars($singleProduct['name']) ?></h1></div></section>

    <!-- MAIN CONTENT -->
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 xl:gap-x-16 gap-y-10">
            <div class="flex flex-col md:flex-row-reverse gap-4">
                <div class="flex-1"><img id="main-product-image" src="/<?= htmlspecialchars($mainImageUrl) ?>" alt="<?= htmlspecialchars($singleProduct['name']) ?>" class="w-full h-auto object-cover aspect-[4/5] rounded-lg"/></div>
                <div id="thumbnail-gallery" class="flex flex-row md:flex-col gap-3 overflow-x-auto md:overflow-y-auto md:max-h-[580px] scrollbar-thin pb-2 md:pb-0">
                    <img src="/<?= htmlspecialchars($mainImageUrl) ?>" alt="<?= htmlspecialchars($singleProduct['name']) ?>" class="thumbnail-img active-thumbnail w-20 h-28 object-cover cursor-pointer flex-shrink-0 rounded-md"/>
                    <?php foreach ($productImages as $image): ?><img src="/<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($image['alt_text'] ?? $singleProduct['name']) ?>" class="thumbnail-img w-20 h-28 object-cover cursor-pointer flex-shrink-0 rounded-md"/><?php endforeach; ?>
                </div>
            </div>

            <div class="flex flex-col pt-4 lg:pt-0 space-y-8">
                <div>
                    <h1 id="product-name" class="text-4xl md:text-5xl font-serif font-semibold text-brand-text sr-only"><?= htmlspecialchars($singleProduct['name']) ?></h1>
                    <p id="product-price" class="price-display text-2xl text-brand-gray mt-3 mb-5"
                        data-base-price="<?= $singleProduct['price'] ?>"
                        data-price-ngn="<?= $displayPrice ?>">
                        ₦<?= number_format($displayPrice, 2) ?>
                    </p>
                    <div class="text-gray-600 leading-relaxed text-base prose"><?= nl2br(htmlspecialchars($singleProduct['product_text'])) ?></div>
                </div>
                <div>
                    <!-- Color Selection (Unchanged) -->
                    <h3 class="text-sm font-semibold mb-3">COLOR <span class="<?= !empty($availableColors) ? 'text-brand-red font-semibold' : 'text-brand-gray font-normal' ?>"><?= !empty($availableColors) ? '(Required)' : '(Optional)' ?></span></h3>
                    <?php if (!empty($availableColors)): ?>
                        <div class="relative">
                            <select id="color-select" name="color_id" class="form-select-sleek pr-8">
                                <option value="" data-hex="" selected>-- Select a color --</option>
                                <?php foreach ($groupedColors as $colorGroup): ?>
                                    <option
                                        value="<?= htmlspecialchars($colorGroup['id']) ?>"
                                        data-hex="<?= htmlspecialchars($colorGroup['hex']) ?>"
                                    >
                                        <?= htmlspecialchars($colorGroup['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-brand-gray">
                                <i data-feather="chevron-down" class="h-4 w-4"></i>
                            </div>
                        </div>
                        <button id="open-custom-color-btn" class="text-sm text-brand-gray hover:text-brand-text underline mt-3">Or need a custom color?</button>
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
                    <!-- Option/Size Selection (Modified to be visible by default) -->
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-semibold">OPTION/SIZE <span class="<?= (!empty($productOptions)) ? 'text-brand-red font-semibold' : 'text-brand-gray font-normal' ?>"><?= (!empty($productOptions)) ? '(Required)' : '(Optional)' ?></span></h3>
                        <?php if(!empty($productOptions)): // Show if any standard options exist ?>
                        <button id="open-size-chart-btn" class="text-sm font-medium text-brand-gray hover:text-brand-text underline">Size Guide</button>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($productOptions)): ?>
                        <!-- Select Wrapper (ALWAYS VISIBLE - removed 'hidden' class) -->
                        <div id="product-option-select-wrapper" class="relative mt-4">
                            <select id="product-option-select" class="form-select-sleek pr-8">
                                <option value="" data-type="" data-id="" data-price-modifier="0" selected>-- Select a size --</option>
                                <?php foreach ($productOptions as $index => $option): ?>
                                    <option
                                        value="<?= htmlspecialchars($option['type'] . '-' . $option['id']) ?>"
                                        data-type="<?= htmlspecialchars($option['type']) ?>"
                                        data-id="<?= htmlspecialchars($option['id']) ?>"
                                        data-price-modifier="<?= htmlspecialchars($option['price_modifier']) ?>"
                                    >
                                        <?= htmlspecialchars($option['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-brand-gray">
                                <i data-feather="chevron-down" class="h-4 w-4"></i>
                            </div>
                        </div>
                        <!-- NOTE: If options are present, the custom size toggle button is placed after the dropdown. -->
                        <button id="open-custom-size-btn" class="text-sm text-brand-gray hover:text-brand-text underline mt-4">Or provide custom measurements</button>
                    <?php else: ?>
                        <!-- If NO standard options, only show the custom size toggle button/input. -->
                        <button id="open-custom-size-btn" class="text-sm text-brand-gray hover:text-brand-text underline">Or provide custom measurements</button>
                    <?php endif; ?>

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
                    // Mock data for related products if needed
                    if(isset($conn) && get_class($conn) === 'MockPDO') {
                        $relatedVariants = [32000, 35000]; // Example
                    } else {
                        $relatedVariantStmt = $conn->prepare("SELECT price FROM product_price_variants WHERE product_id = ? ORDER BY price ASC");
                        $relatedVariantStmt->execute([$relatedProduct['id']]);
                        $relatedVariants = $relatedVariantStmt->fetchAll(PDO::FETCH_COLUMN);
                    }
                ?>
                <div class="product-card">
                    <a href="shopdetail?id=<?= urlencode($relatedProduct['id']) ?>" class="group block">
                        <div class="relative w-full overflow-hidden"><div class="aspect-[9/16]"><img src="/<?= htmlspecialchars($relatedProduct['image_one']) ?>" alt="<?= htmlspecialchars($relatedProduct['name']) ?>" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0"><img src="/<?= htmlspecialchars($relatedProduct['image_two']) ?>" alt="<?= htmlspecialchars($relatedProduct['name']) ?> Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100"></div></div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text"><?= htmlspecialchars($relatedProduct['name']) ?></h3>
                            <p class="price-display mt-1 text-sm text-brand-gray" data-price-ngn="<?= !empty($relatedVariants) ? $relatedVariants[0] : $relatedProduct['price'] ?>">
                                <?php if (count($relatedVariants) > 1): ?>From ₦<?= number_format($relatedVariants[0], 2) ?><?php else: ?>₦<?= number_format(!empty($relatedVariants) ? $relatedVariants[0] : $relatedProduct['price'], 2) ?><?php endif; ?>
                            </p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- MODALS -->
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

        // Option/Size validation
        if (selectors.productOptionSelect && selectors.productOptionSelect.options.length > 1) { 
            if (!hasCustomMeasurements && (!selectedOption || selectedOption.value === "")) {
                showToast("Please select an option/size or provide custom measurements.", "error");
                resetAddToCartButton();
                return;
            }
        } else if (hasCustomMeasurements === false) {
             showToast("Custom measurements are required for this item.", "error");
             resetAddToCartButton();
             return;
        }

        if (hasCustomMeasurements) {
            payload.customSizeDetails = customSizeDetails;
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

    const USD_RATE = 1000; // Placeholder value
    const INITIAL_CURRENCY = 'NGN'; // Placeholder value

    const updateAllPrices = (targetCurrency) => {
        document.querySelectorAll('.price-display').forEach(el => {
            const ngnPrice = parseFloat(el.dataset.priceNgn);
            if (!isNaN(ngnPrice)) {
                let newPrice = (targetCurrency === 'USD') ? ngnPrice / USD_RATE : ngnPrice;
                el.textContent = formatCurrency(newPrice, targetCurrency);
            }
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

    // Thumbnail image selection
    selectors.thumbnails.forEach(thumb => {
        thumb.addEventListener("click", function () {
            if(selectors.mainImage) {
                selectors.mainImage.src = this.src;
                selectors.mainImage.alt = this.alt;
            }
            selectors.thumbnails.forEach(t => t.classList.remove("active-thumbnail"));
            this.classList.add("active-thumbnail");
        });
    });

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

    // Handle product option/size dropdown change
    if (selectors.productOptionSelect) {
        selectors.productOptionSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const priceModifier = parseFloat(selectedOption.dataset.priceModifier || 0);
            const basePrice = parseFloat(selectors.productPriceEl.dataset.basePrice);
            const newDisplayPrice = basePrice + priceModifier;

            selectors.productPriceEl.dataset.priceNgn = newDisplayPrice;
            updateAllPrices(document.querySelector('.currency-switcher a.active')?.dataset.currency || 'NGN');

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
                    }
                    const basePrice = parseFloat(selectors.productPriceEl.dataset.basePrice);
                    selectors.productPriceEl.dataset.priceNgn = basePrice;
                    updateAllPrices(document.querySelector('.currency-switcher a.active')?.dataset.currency || 'NGN');
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
            document.querySelectorAll('.currency-switcher a.currency-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
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

});
</script>

</body>
</html>