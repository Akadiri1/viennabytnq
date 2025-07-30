<?php
// --- PERSISTENT CART TOKEN LOGIC ---
$cookie_name = 'cart_token';
$thirty_days = time() + (86400 * 30);
if (!isset($_COOKIE[$cookie_name])) {
    $token = bin2hex(random_bytes(32)); 
    setcookie($cookie_name, $token, [ 'expires' => $thirty_days, 'path' => '/', 'secure' => isset($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax' ]);
    $cartToken = $token;
} else {
    $cartToken = $_COOKIE[$cookie_name];
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- CLEANED & CONSOLIDATED DATA FETCHING ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header("Location: /shop"); exit; }
$id = (int)$_GET['id'];

$singleProduct = selectContent($conn, "panel_products", ['visibility' => 'show', 'id' => $id]);
if (!$singleProduct) { header("Location: /shop"); exit; }

$sqlImages = "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC";
$imagesStmt = $conn->prepare($sqlImages);
$imagesStmt->execute([$id]);
$productImages = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
$mainImageUrl = $singleProduct[0]['image_one'];

$sqlColors = "SELECT c.id, c.name AS color_name, c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id = ? ORDER BY c.id ASC";
$colorsStmt = $conn->prepare($sqlColors);
$colorsStmt->execute([$id]);
$availableColors = $colorsStmt->fetchAll(PDO::FETCH_ASSOC);

$sqlSizes = "SELECT s.id, s.name AS size_name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id = ? ORDER BY s.id ASC";
$sizesStmt = $conn->prepare($sqlSizes);
$sizesStmt->execute([$id]);
$availableSizes = $sizesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>" />
    <title><?= htmlspecialchars($singleProduct[0]['name']) ?> - <?=$site_name?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { "brand-bg": "#F9F6F2", "brand-text": "#1A1A1A", "brand-gray": "#6B7280", "brand-red": "#EF4444", }, fontFamily: { sans: ["Inter", "ui-sans-serif", "system-ui"], serif: ["Cormorant Garamond", "serif"], }, }, }, };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin /><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://unpkg.com/feather-icons"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
      .thumbnail-img { border: 2px solid transparent; transition: border-color 0.2s ease-in-out; } .active-thumbnail { border-color: #1a1a1a; } .color-swatch { width: 1.75rem; height: 1.75rem; border-radius: 9999px; cursor: pointer; border: 1px solid #d1d5db; transition: all 0.2s ease-in-out; } .active-color { transform: scale(1.1); box-shadow: 0 0 0 3px #1a1a1a; border-color: #1a1a1a; } .size-btn { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; font-weight: 500; cursor: pointer; transition: all 0.2s; font-size: 0.875rem; min-width: 40px; text-align: center; } .size-btn:hover { border-color: #1a1a1a; } .active-size { background-color: #1a1a1a; color: white; border-color: #1a1a1a; } .form-input-sleek { background-color: transparent; border: 0; border-bottom: 1px solid #d1d5db; border-radius: 0; padding: 0.5rem 0.1rem; width: 100%; transition: border-color 0.2s ease-in-out; } .form-input-sleek:focus { outline: none; box-shadow: none; ring: 0; border-bottom-color: #1a1a1a; } .modal-container { display: flex; align-items: center; justify-content: center; position: fixed; inset: 0; z-index: 100; transition: opacity 0.3s ease-in-out; opacity: 1; pointer-events: auto; } .modal-container.hidden { opacity: 0; pointer-events: none; } .modal-overlay { position: absolute; inset: 0; background-color: rgba(0, 0, 0, 0.4); } .modal-panel { position: relative; width: 95%; max-w: 500px; background-color: #f9f6f2; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); transform: scale(1); opacity: 1; } .modal-container.hidden .modal-panel { transform: scale(0.95); opacity: 0; } .modal-content-scrollable { max-height: 60vh; overflow-y: auto; } .modal-form-scrollable { max-height: 60vh; overflow-y: auto; padding-right: 0.75rem; } #custom-color-input-container { transition: max-height 0.35s ease-in-out, opacity 0.3s ease-in-out, margin-top 0.35s ease-in-out; overflow: hidden; max-height: 100px; opacity: 1; margin-top: 1rem; } #custom-color-input-container.is-closed { max-height: 0; opacity: 0; margin-top: 0; }
    </style>
  </head>
  <body class="bg-brand-bg font-sans text-brand-text">
    
    <div id="sidebar" class="fixed inset-0 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out">
        <div id="sidebar-overlay" class="absolute inset-0 bg-black/40"></div>
        <div class="relative w-80 h-full bg-brand-bg shadow-2xl flex flex-col">
            <div class="p-6 flex justify-between items-center border-b border-gray-200">
                <h2 class="text-2xl font-serif font-semibold">Menu</h2>
                <button id="close-sidebar-btn" class="p-2 text-brand-gray hover:text-brand-text"><i data-feather="x" class="h-6 w-6"></i></button>
            </div>
            <nav class="flex-grow p-6"><ul class="space-y-4"><li><a href="/home" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="home" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Home</span></a></li><li><a href="/shop" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"><i data-feather="shopping-bag" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Products</span></a></li></ul></nav>
        </div>
    </div>

    <div id="cart-sidebar" class="fixed inset-0 z-[60] transform translate-x-full transition-transform duration-300 ease-in-out">
        <div id="cart-overlay" class="absolute inset-0 bg-black/40 cursor-pointer"></div>
        <div class="relative w-full max-w-md ml-auto h-full bg-brand-bg shadow-2xl flex flex-col">
            <div class="p-6 flex justify-between items-center border-b border-gray-200"><h2 id="cart-title" class="text-2xl font-serif font-semibold">Your Cart</h2><button id="close-cart-btn" class="p-2 text-brand-gray hover:text-brand-text"><i data-feather="x" class="h-6 w-6"></i></button></div>
            <div id="cart-items-container" class="flex-grow p-6 overflow-y-auto"><div id="empty-cart-message" class="flex flex-col items-center justify-center h-full text-center"><i data-feather="shopping-bag" class="w-16 h-16 text-gray-300 mb-4"></i><p class="text-brand-gray">Your cart is empty.</p></div></div>
            <div class="p-6 border-t border-gray-200 space-y-4 bg-brand-bg"><div class="flex justify-between font-semibold"><span>Subtotal</span><span id="cart-subtotal">₦0.00</span></div><a href="cart" class="block w-full bg-transparent text-brand-text border border-brand-text py-3 text-center font-semibold hover:bg-gray-100 transition-colors">VIEW CART</a><a href="checkout" class="block w-full bg-brand-text text-white py-3 text-center font-semibold hover:bg-gray-800 transition-colors">CHECKOUT</a></div>
        </div>
    </div>

    <header class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-1 flex justify-start"><button id="open-sidebar-btn" class="p-2 text-brand-text hover:text-brand-gray"><i data-feather="menu" class="h-6 w-6"></i></button></div>
                <div class="flex-shrink-0 text-center"><a href="/home"><div class="text-2xl font-serif font-bold tracking-widest"><?=$site_name?></div></a></div>
                <div class="flex-1 flex items-center justify-end space-x-4"><button id="open-cart-btn" class="p-2 text-brand-text hover:text-brand-gray relative"><i data-feather="shopping-bag" class="h-5 w-5"></i><span id="cart-item-count" class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-brand-red text-white text-xs flex items-center justify-center font-bold" style="font-size: 8px; display: none;">0</span></button></div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 xl:gap-x-16 gap-y-10">
            <div class="flex flex-col-reverse md:flex-row gap-4"><div id="thumbnail-gallery" class="flex md:flex-col gap-3"><img src="<?= htmlspecialchars($mainImageUrl) ?>" alt="<?= htmlspecialchars($singleProduct[0]['name']) ?>" class="thumbnail-img active-thumbnail w-20 h-28 object-cover cursor-pointer"/><?php foreach ($productImages as $image): ?><img src="<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($image['alt_text'] ?? $singleProduct[0]['name']) ?>" class="thumbnail-img w-20 h-28 object-cover cursor-pointer"/><?php endforeach; ?></div><div class="flex-1"><img id="main-product-image" src="<?= htmlspecialchars($mainImageUrl) ?>" alt="<?= htmlspecialchars($singleProduct[0]['name']) ?>" class="w-full h-auto object-cover aspect-[4/5]"/></div></div>
            <div class="flex flex-col pt-4 lg:pt-0 space-y-8">
                <div><h1 id="product-name" class="text-4xl md:text-5xl font-serif font-semibold text-brand-text sr-only"><?= htmlspecialchars($singleProduct[0]['name']) ?></h1><p id="product-price" class="text-2xl text-brand-gray mt-3 mb-5" data-price="<?= $singleProduct[0]['price'] ?>">₦<?= number_format($singleProduct[0]['price'], 2) ?></p><p class="text-gray-600 leading-relaxed text-base"><?= nl2br(htmlspecialchars($singleProduct[0]['product_text'])) ?></p></div>
                <div><h3 class="text-sm font-semibold mb-3">COLOR: <span id="selected-color-name" class="font-normal text-brand-gray"><?= !empty($availableColors) ? htmlspecialchars($availableColors[0]['color_name']) : 'Not Available' ?></span></h3><div id="color-selector" class="flex items-center space-x-3"><?php foreach ($availableColors as $index => $color): ?><button data-id="<?= $color['id'] ?>" data-color="<?= htmlspecialchars($color['color_name']) ?>" class="color-swatch <?= $index === 0 ? 'active-color' : '' ?>" style="background-color: <?= htmlspecialchars($color['hex_code']) ?>"></button><?php endforeach; ?></div><button id="open-custom-color-btn" class="text-sm text-brand-gray hover:text-brand-text underline mt-3">Need a custom color?</button><div id="custom-color-input-container" class="is-closed"><label for="custom-color" class="text-sm font-medium text-brand-gray">Custom Color</label><input type="text" id="custom-color" class="form-input-sleek mt-1" placeholder="e.g., Emerald Green"/></div></div>
                <div><div class="flex justify-between items-center mb-3"><h3 class="text-sm font-semibold">SIZE</h3><button id="open-size-chart-btn" class="text-sm font-medium text-brand-gray hover:text-brand-text underline">Size Guide</button></div><div id="size-selector" class="flex flex-wrap gap-2"><?php foreach ($availableSizes as $index => $size): ?><button data-id="<?= $size['id'] ?>" class="size-btn <?= $index === 0 ? 'active-size' : '' ?>"><?= htmlspecialchars($size['size_name']) ?></button><?php endforeach; ?></div><button id="open-custom-size-btn" class="text-sm text-brand-gray hover:text-brand-text underline mt-3">Need a custom size?</button></div>
                <div class="flex items-center gap-6"><div class="flex items-center border border-gray-300"><button id="quantity-minus" class="px-3 py-2 text-brand-gray hover:text-brand-text">-</button><span id="quantity-display" class="px-4 py-2 font-medium">1</span><button id="quantity-plus" class="px-3 py-2 text-brand-gray hover:text-brand-text">+</button></div><button id="add-to-cart-btn" data-product-id="<?= $id ?>" class="w-full bg-brand-text text-white py-3 font-semibold hover:bg-gray-800 transition-colors flex items-center justify-center gap-3"><i data-feather="shopping-cart" class="w-5 h-5"></i><span>ADD TO CART</span></button></div>
            </div>
        </div>
    </main>
    
    <div id="custom-size-modal" class="modal-container hidden"><div id="custom-size-overlay" class="modal-overlay"></div><div class="modal-panel p-6"><div class="flex justify-between items-start pb-4"><div><h3 class="text-xl font-serif font-semibold">Custom Measurements</h3><p class="text-sm text-brand-gray mt-1">Enter your measurements for a bespoke fit.</p></div><button id="close-custom-size-btn" class="p-1 text-brand-gray hover:text-brand-text"><i data-feather="x" class="w-5 h-5"></i></button></div><div class="mt-4 space-y-6 modal-form-scrollable"><div id="custom-measurements-form" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5"><div><label for="dress-length" class="text-sm font-medium text-brand-gray">Dress Length</label><input type="text" id="dress-length" class="form-input-sleek mt-1" placeholder="e.g., 45 in"/></div><div><label for="blouse-length" class="text-sm font-medium text-brand-gray">Blouse Length</label><input type="text" id="blouse-length" class="form-input-sleek mt-1" placeholder="e.g., 18 in"/></div><div><label for="bust-size" class="text-sm font-medium text-brand-gray">Bust</label><input type="text" id="bust-size" class="form-input-sleek mt-1" placeholder="e.g., 36 in"/></div><div><label for="under-bust" class="text-sm font-medium text-brand-gray">Under Bust</label><input type="text" id="under-bust" class="form-input-sleek mt-1" placeholder="e.g., 14 in"></div><div><label for="waist-size" class="text-sm font-medium text-brand-gray">Waist</label><input type="text" id="waist-size" class="form-input-sleek mt-1" placeholder="e.g., 29 in"></div><div><label for="hips-size" class="text-sm font-medium text-brand-gray">Hips</label><input type="text" id="hips-size" class="form-input-sleek mt-1" placeholder="e.g., 39 in"></div><div><label for="mini-skirt-length" class="text-sm font-medium text-brand-gray">Mini Skirt Length</label><input type="text" id="mini-skirt-length" class="form-input-sleek mt-1" placeholder="e.g., 17 in"></div><div><label for="half-length" class="text-sm font-medium text-brand-gray">Half Length</label><input type="text" id="half-length" class="form-input-sleek mt-1" placeholder="e.g., 16 in"></div><div><label for="cup-size" class="text-sm font-medium text-brand-gray">Cup Size</label><input type="text" id="cup-size" class="form-input-sleek mt-1" placeholder="e.g., 34C"></div></div><button id="save-measurements-btn" class="w-full bg-brand-text text-white py-3 mt-4 font-semibold hover:bg-gray-800 transition-colors">Confirm Measurements</button></div></div></div>
    <div id="size-chart-modal" class="modal-container hidden"><div id="size-chart-overlay" class="modal-overlay"></div><div class="modal-panel p-6 max-w-xl"><div class="flex justify-between items-center pb-3"><h3 class="text-xl font-serif font-semibold">Size Guide</h3><button id="close-size-chart-btn" class="p-1 text-brand-gray hover:text-brand-text"><i data-feather="x" class="w-5 h-5"></i></button></div><div class="mt-4 modal-content-scrollable pr-2"><img src="images/chart.jpg" alt="Size Chart" class="w-full" /></div></div></div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        feather.replace();

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
            emptyCartMessage: document.getElementById("empty-cart-message"),
            addToCartBtn: document.getElementById("add-to-cart-btn"),
            quantityMinusBtn: document.getElementById("quantity-minus"),
            quantityPlusBtn: document.getElementById("quantity-plus"),
            quantityDisplay: document.getElementById("quantity-display"),
            openCustomColorBtn: document.getElementById("open-custom-color-btn"),
            customColorContainer: document.getElementById("custom-color-input-container"),
            customColorInput: document.getElementById("custom-color"),
            mainImage: document.getElementById("main-product-image"),
            thumbnails: document.querySelectorAll(".thumbnail-img"),
            colorSwatches: document.querySelectorAll(".color-swatch"),
            selectedColorName: document.getElementById("selected-color-name"),
            sizeBtns: document.querySelectorAll(".size-btn"),
            saveMeasurementsBtn: document.getElementById("save-measurements-btn"),
            modals: [
                { modal: document.getElementById("custom-size-modal"), openBtn: document.getElementById("open-custom-size-btn"), closeBtn: document.getElementById("close-custom-size-btn"), overlay: document.getElementById("custom-size-overlay") },
                { modal: document.getElementById("size-chart-modal"), openBtn: document.getElementById("open-size-chart-btn"), closeBtn: document.getElementById("close-size-chart-btn"), overlay: document.getElementById("size-chart-overlay") }
            ]
        };

        const showToast = (text, type = "success") => {
            const backgroundColor = type === "success" ? "#4CAF50" : "#F44336";
            Toastify({ text: text, duration: 3000, close: true, gravity: "top", position: "right", style: { background: backgroundColor } }).showToast();
        };

        const toggleSidebar = (sidebar, shouldOpen) => {
            if (!sidebar) return;
            if (shouldOpen) {
                sidebar.classList.remove("-translate-x-full");
                document.body.classList.add("overflow-hidden");
            } else {
                sidebar.classList.add("-translate-x-full");
                if (!document.querySelector("#sidebar:not(.-translate-x-full)") && !document.querySelector("#cart-sidebar:not(.-translate-x-full)")) {
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
                if (!document.querySelector(".modal-container:not(.hidden)")) {
                    document.body.classList.remove("overflow-hidden");
                }
            }
        };

        // const updateCartDisplay = async () => {
        //     try {
        //         const response = await fetch("cart"); 
        //         if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
        //         const cartData = await response.json();
                
        //         if (selectors.cartItemsContainer && selectors.emptyCartMessage) {
        //             selectors.cartItemsContainer.innerHTML = "";
        //             if (cartData.items && cartData.items.length > 0) {
        //                 selectors.emptyCartMessage.style.display = "none";
        //                 cartData.items.forEach((item) => {
        //                     let desc = item.color_name || (item.custom_color_name ? `Custom: ${item.custom_color_name}` : "");
        //                     let sizeDetails = item.size_name ? ` / ${item.size_name}` : "";
        //                     if (!item.size_name && item.custom_size_details) {
        //                         try {
        //                             if(Object.keys(JSON.parse(item.custom_size_details)).length > 0) sizeDetails = " / Custom Size";
        //                         } catch(e){}
        //                     }
        //                     desc += sizeDetails;
        //                     const itemHTML = `<div class="flex gap-4 py-4 border-b border-gray-200 last:border-b-0"><img src="${item.product_image}" alt="${item.product_name}" class="w-20 h-24 object-cover"><div class="flex-1 flex flex-col justify-between"><div><h4 class="font-semibold text-sm">${item.product_name}</h4><p class="text-xs text-brand-gray">${desc}</p></div><div class="flex justify-between items-center mt-2"><p class="text-sm font-medium">₦${(item.total_price * item.quantity).toFixed(2)}</p><p class="text-xs text-brand-gray">Qty: ${item.quantity}</p></div></div></div>`;
        //                     selectors.cartItemsContainer.insertAdjacentHTML("beforeend", itemHTML);
        //                 });
        //             } else { selectors.emptyCartMessage.style.display = "flex"; }
        //         }
                
        //         if(selectors.cartSubtotalEl) selectors.cartSubtotalEl.textContent = `₦${cartData.subtotal}`;
        //         if(selectors.cartItemCountEl) {
        //             selectors.cartItemCountEl.textContent = cartData.totalItems;
        //             selectors.cartItemCountEl.style.display = cartData.totalItems > 0 ? "flex" : "none";
        //         }
        //     } catch (error) { console.error("Failed to update cart display:", error); }
        // };

        const handleAddToCart = async () => {
            const btn = selectors.addToCartBtn;
            if(!btn) return;
            btn.disabled = true; btn.innerHTML = `<i data-feather="loader" class="w-5 h-5 animate-spin"></i><span>ADDING...</span>`; feather.replace();
            
            const payload = { productId: parseInt(btn.dataset.productId), quantity: parseInt(selectors.quantityDisplay.textContent) };
            const activeColorSwatch = document.querySelector("#color-selector .active-color");
            const customColorValue = selectors.customColorInput.value.trim();
            const isCustomColorActive = !selectors.customColorContainer.classList.contains("is-closed");

            if (isCustomColorActive && customColorValue) { payload.customColor = customColorValue; } 
            else if (activeColorSwatch) { payload.colorId = parseInt(activeColorSwatch.dataset.id); } 
            else { showToast("Please select or enter a color.", "error"); resetAddToCartButton(); return; }

            const activeSizeBtn = document.querySelector("#size-selector .active-size");
            if (activeSizeBtn) { payload.sizeId = parseInt(activeSizeBtn.dataset.id); } 
            else {
                payload.customSizeDetails = Array.from(document.querySelectorAll("#custom-measurements-form input")).reduce((acc, input) => { const val = input.value.trim(); if(val) acc[input.id.replace(/-/g, '_')] = val; return acc; }, {});
                if (Object.keys(payload.customSizeDetails).length === 0) { showToast("Please select a standard size or enter custom measurements.", "error"); resetAddToCartButton(); return; }
            }

            try {
                const response = await fetch("cart", { method: "POST", headers: { "Content-Type": "application/json", "Accept": "application/json" }, body: JSON.stringify(payload) });
                const result = await response.json();

                if (response.ok && result.status === "success") { 
                    showToast("Item added to cart!"); 
                    if(selectors.cartItemCountEl) { selectors.cartItemCountEl.textContent = result.cartItemCount; selectors.cartItemCountEl.style.display = result.cartItemCount > 0 ? "flex" : "none"; }
                    await updateCartDisplay();
                    toggleSidebar(selectors.cartSidebar, true);
                } else { showToast(result.message || "Could not add item.", "error"); }
            } catch (error) { console.error("Fetch Error:", error); showToast("A network error occurred.", "error"); } 
            finally { resetAddToCartButton(); }
        };
        
        const resetAddToCartButton = () => { if(selectors.addToCartBtn) { selectors.addToCartBtn.disabled = false; selectors.addToCartBtn.innerHTML = `<i data-feather="shopping-cart" class="w-5 h-5"></i><span>ADD TO CART</span>`; feather.replace(); } };

        // --- Event Listeners Setup ---
        if(selectors.openSidebarBtn) selectors.openSidebarBtn.addEventListener("click", () => toggleSidebar(selectors.sidebar, true));
        if(selectors.closeSidebarBtn) selectors.closeSidebarBtn.addEventListener("click", () => toggleSidebar(selectors.sidebar, false));
        if(selectors.sidebarOverlay) selectors.sidebarOverlay.addEventListener("click", () => toggleSidebar(selectors.sidebar, false));
        
        if(selectors.openCartBtn) selectors.openCartBtn.addEventListener("click", async () => { await updateCartDisplay(); toggleSidebar(selectors.cartSidebar, true); });
        if(selectors.closeCartBtn) selectors.closeCartBtn.addEventListener("click", () => toggleSidebar(selectors.cartSidebar, false));
        if(selectors.cartOverlay) selectors.cartOverlay.addEventListener("click", () => toggleSidebar(selectors.cartSidebar, false));

        if(selectors.quantityPlusBtn) selectors.quantityPlusBtn.addEventListener("click", () => (selectors.quantityDisplay.textContent = parseInt(selectors.quantityDisplay.textContent) + 1));
        if(selectors.quantityMinusBtn) selectors.quantityMinusBtn.addEventListener("click", () => { if (parseInt(selectors.quantityDisplay.textContent) > 1) { selectors.quantityDisplay.textContent = parseInt(selectors.quantityDisplay.textContent) - 1; } });
        if(selectors.addToCartBtn) selectors.addToCartBtn.addEventListener("click", handleAddToCart);

        selectors.thumbnails.forEach(thumb => { thumb.addEventListener("click", function () { if(selectors.mainImage) { selectors.mainImage.src = this.src; selectors.mainImage.alt = this.alt; } selectors.thumbnails.forEach(t => t.classList.remove("active-thumbnail")); this.classList.add("active-thumbnail"); }); });
        selectors.colorSwatches.forEach(swatch => { swatch.addEventListener("click", () => { if(selectors.selectedColorName) selectors.selectedColorName.textContent = swatch.dataset.color; selectors.colorSwatches.forEach(s => s.classList.remove("active-color")); swatch.classList.add("active-color"); if (selectors.customColorContainer) selectors.customColorContainer.classList.add("is-closed"); if (selectors.customColorInput) selectors.customColorInput.value = ""; showToast(`Selected color: ${swatch.dataset.color}`); }); });
        selectors.sizeBtns.forEach(btn => { btn.addEventListener("click", () => { selectors.sizeBtns.forEach(b => b.classList.remove("active-size")); btn.classList.add("active-size"); }); });
        
        if (selectors.openCustomColorBtn) { selectors.openCustomColorBtn.addEventListener("click", (e) => { e.preventDefault(); if (selectors.customColorContainer) { const isNowClosed = selectors.customColorContainer.classList.toggle("is-closed"); if (!isNowClosed) { selectors.colorSwatches.forEach(s => s.classList.remove("active-color")); if (selectors.selectedColorName) selectors.selectedColorName.textContent = "Custom"; if (selectors.customColorInput) selectors.customColorInput.focus(); } } }); }
        if (selectors.saveMeasurementsBtn) { selectors.saveMeasurementsBtn.addEventListener("click", (e) => { e.preventDefault(); showToast("Measurements noted. Click 'Add to Cart' to confirm."); toggleModal(document.getElementById("custom-size-modal"), false); }); }
        
        selectors.modals.forEach(({ modal, openBtn, closeBtn, overlay }) => {
            if (openBtn) { openBtn.addEventListener("click", (e) => { e.preventDefault(); if (modal && modal.id === "custom-size-modal") { selectors.sizeBtns.forEach(b => b.classList.remove("active-size")); } toggleModal(modal, true); }); }
            if (closeBtn) { closeBtn.addEventListener("click", () => toggleModal(modal, false)); }
            if (overlay) { overlay.addEventListener("click", () => toggleModal(modal, false)); }
        });

        document.addEventListener("keydown", (event) => { if (event.key === "Escape") { toggleSidebar(selectors.sidebar, false); toggleSidebar(selectors.cartSidebar, false); selectors.modals.forEach(({ modal }) => { if (modal && !modal.classList.contains("hidden")) toggleModal(modal, false); }); } });
        
        // Initial load of cart data for the top icon
        updateCartDisplay();
    });
    </script>
  </body>
</html>