<?php
// --- PERSISTENT CART TOKEN LOGIC ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

$productBreadcrumb = selectContent($conn, "product_breadcrumb", ['visibility' => 'show']);
$relatedProductsQuery = "SELECT * FROM panel_products WHERE visibility = 'show' AND id != ? ORDER BY RAND() LIMIT 4";
$relatedStmt = $conn->prepare($relatedProductsQuery);
$relatedStmt->execute([$id]);
$relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
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
      .toastify { padding: 12px 20px; font-size: 14px; font-weight: 500; border-radius: 8px; box-shadow: 0 3px 6px -1px rgba(0,0,0,.12), 0 10px 36px -4px rgba(51,45,45,.25); }
    </style>
  </head>
  <body class="bg-brand-bg font-sans text-brand-text">

    <!-- SIDEBAR MENU -->
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

    <!-- CART SIDEBAR -->
    <div id="cart-sidebar" class="fixed inset-0 z-[60] transform translate-x-full transition-transform duration-300 ease-in-out">
        <div id="cart-overlay" class="absolute inset-0 bg-black/40 cursor-pointer"></div>
        <div class="relative w-full max-w-md ml-auto h-full bg-brand-bg shadow-2xl flex flex-col">
            <div class="p-6 flex justify-between items-center border-b border-gray-200"><h2 id="cart-title" class="text-2xl font-serif font-semibold">Your Cart</h2><button id="close-cart-btn" class="p-2 text-brand-gray hover:text-brand-text"><i data-feather="x" class="h-6 w-6"></i></button></div>
            <div id="cart-items-container" class="flex-grow p-6 overflow-y-auto">
                <!-- Content is generated by JavaScript -->
            </div>
            <div class="p-6 border-t border-gray-200 space-y-4 bg-brand-bg"><div class="flex justify-between font-semibold"><span>Subtotal</span><span id="cart-subtotal">₦0.00</span></div><a href="/view-cart" class="block w-full bg-transparent text-brand-text border border-brand-text py-3 text-center font-semibold hover:bg-gray-100 transition-colors">VIEW CART</a><a href="/checkout" class="block w-full bg-brand-text text-white py-3 text-center font-semibold hover:bg-gray-800 transition-colors">CHECKOUT</a></div>
        </div>
    </div>

    <!-- HEADER -->
    <header class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-1 flex justify-start"><button id="open-sidebar-btn" class="p-2 text-brand-text hover:text-brand-gray"><i data-feather="menu" class="h-6 w-6"></i></button></div>
                <div class="flex-shrink-0 text-center"><a href="/home"><div class="text-2xl font-serif font-bold tracking-widest"><?=$site_name?></div></a></div>
                <div class="flex-1 flex items-center justify-end space-x-4"><button id="open-cart-btn" class="p-2 text-brand-text hover:text-brand-gray relative"><i data-feather="shopping-bag" class="h-5 w-5"></i><span id="cart-item-count" class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-brand-red text-white text-xs flex items-center justify-center font-bold" style="font-size: 8px; display: none;">0</span></button></div>
            </div>
        </div>
    </header>

    <!-- HERO/BREADCRUMB IMAGE SECTION -->
    <section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($singleProduct[0]['image_one'] ?? '') ?>');">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4">
            <nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb">
                <ol class="list-none p-0 inline-flex items-center">
                    <li class="flex items-center"><a href="/home" class="hover:underline">Home</a><i data-feather="chevron-right" class="h-4 w-4 mx-2"></i></li>
                    <li class="flex items-center"><a href="/shop" class="hover:underline">Shop</a></li>
                    <!-- <li><span><?= htmlspecialchars($singleProduct[0]['name']) ?></span></li> -->
                </ol>
            </nav>
            <h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4"><?= htmlspecialchars($singleProduct[0]['name']) ?></h1>
        </div>
    </section>

    <!-- MAIN CONTENT -->
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 xl:gap-x-16 gap-y-10">
            <div class="flex flex-col-reverse md:flex-row gap-4"><div id="thumbnail-gallery" class="flex md:flex-col gap-3"><img src="<?= htmlspecialchars($mainImageUrl) ?>" alt="<?= htmlspecialchars($singleProduct[0]['name']) ?>" class="thumbnail-img active-thumbnail w-20 h-28 object-cover cursor-pointer"/><?php foreach ($productImages as $image): ?><img src="<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($image['alt_text'] ?? $singleProduct[0]['name']) ?>" class="thumbnail-img w-20 h-28 object-cover cursor-pointer"/><?php endforeach; ?></div><div class="flex-1"><img id="main-product-image" src="<?= htmlspecialchars($mainImageUrl) ?>" alt="<?= htmlspecialchars($singleProduct[0]['name']) ?>" class="w-full h-auto object-cover aspect-[4/5]"/></div></div>
            <div class="flex flex-col pt-4 lg:pt-0 space-y-8">
                <div><h1 id="product-name" class="text-4xl md:text-5xl font-serif font-semibold text-brand-text sr-only"><?= htmlspecialchars($singleProduct[0]['name']) ?></h1><p id="product-price" class="text-2xl text-brand-gray mt-3 mb-5" data-price="<?= $singleProduct[0]['price'] ?>">₦<?= number_format($singleProduct[0]['price'], 2) ?></p><p class="text-gray-600 leading-relaxed text-base"><?= nl2br(htmlspecialchars($singleProduct[0]['product_text'])) ?></p></div>
                <div><h3 class="text-sm font-semibold mb-3">COLOR: <span id="selected-color-name" class="font-normal text-brand-gray">Please select a color</span></h3><div id="color-selector" class="flex items-center space-x-3"><?php foreach ($availableColors as $color): ?><button data-id="<?= $color['id'] ?>" data-color="<?= htmlspecialchars($color['color_name']) ?>" class="color-swatch" style="background-color: <?= htmlspecialchars($color['hex_code']) ?>"></button><?php endforeach; ?></div><button id="open-custom-color-btn" class="text-sm text-brand-gray hover:text-brand-text underline mt-3">Need a custom color?</button><div id="custom-color-input-container" class="is-closed"><label for="custom-color" class="text-sm font-medium text-brand-gray">Custom Color</label><input type="text" id="custom-color" class="form-input-sleek mt-1" placeholder="e.g., Emerald Green"/></div></div>
                <div><div class="flex justify-between items-center mb-3"><h3 class="text-sm font-semibold">SIZE</h3><button id="open-size-chart-btn" class="text-sm font-medium text-brand-gray hover:text-brand-text underline">Size Guide</button></div><div id="size-selector" class="flex flex-wrap gap-2"><?php foreach ($availableSizes as $size): ?><button data-id="<?= $size['id'] ?>" class="size-btn"><?= htmlspecialchars($size['size_name']) ?></button><?php endforeach; ?></div><button id="open-custom-size-btn" class="text-sm text-brand-gray hover:text-brand-text underline mt-3">Need a custom size?</button></div>
                <div class="flex items-center gap-6"><div class="flex items-center border border-gray-300"><button id="quantity-minus" class="px-3 py-2 text-brand-gray hover:text-brand-text">-</button><span id="quantity-display" class="px-4 py-2 font-medium">1</span><button id="quantity-plus" class="px-3 py-2 text-brand-gray hover:text-brand-text">+</button></div><button id="add-to-cart-btn" data-product-id="<?= $id ?>" class="w-full bg-brand-text text-white py-3 font-semibold hover:bg-gray-800 transition-colors flex items-center justify-center gap-3"><i data-feather="shopping-cart" class="w-5 h-5"></i><span>ADD TO CART</span></button></div>
            </div>
        </div>
    </main>

    <?php if (!empty($relatedProducts)): ?>
    <section class="container mx-auto px-4 sm:px-6 lg:px-8 mt-24 mb-16">
        <h2 class="text-3xl font-serif font-semibold text-center mb-10">You Might Also Like</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-12">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
            <div class="product-card">
                    <a href="shopdetail?id=<?= urlencode($relatedProduct['id']) ?>&name=<?= urlencode($relatedProduct['name']) ?>&t=<?= time() ?>" class="group block">
                    <div class="relative w-full overflow-hidden"><div class="aspect-[9/16]"><img src="<?= htmlspecialchars($relatedProduct['image_one']) ?>" alt="<?= htmlspecialchars($relatedProduct['name']) ?>" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0"><img src="<?= htmlspecialchars($relatedProduct['image_two']) ?>" alt="<?= htmlspecialchars($relatedProduct['name']) ?> Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100"></div></div>
                    <div class="pt-4 text-center"><h3 class="text-base font-medium text-brand-text"><?= htmlspecialchars($relatedProduct['name']) ?></h3><p class="mt-1 text-sm text-brand-gray">₦<?= number_format($relatedProduct['price'], 2) ?></p></div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Modals -->
    <div id="custom-size-modal" class="modal-container hidden"><div id="custom-size-overlay" class="modal-overlay"></div><div class="modal-panel p-6"><div class="flex justify-between items-start pb-4"><div><h3 class="text-xl font-serif font-semibold">Custom Measurements</h3><p class="text-sm text-brand-gray mt-1">Enter your measurements for a bespoke fit.</p></div><button id="close-custom-size-btn" class="p-1 text-brand-gray hover:text-brand-text"><i data-feather="x" class="w-5 h-5"></i></button></div><div class="mt-4 space-y-6 modal-form-scrollable"><div id="custom-measurements-form" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5"><div><label for="dress-length" class="text-sm font-medium text-brand-gray">Dress Length</label><input type="text" id="dress-length" class="form-input-sleek mt-1" placeholder="e.g., 45 in"/></div><div><label for="blouse-length" class="text-sm font-medium text-brand-gray">Blouse Length</label><input type="text" id="blouse-length" class="form-input-sleek mt-1" placeholder="e.g., 18 in"/></div><div><label for="bust-size" class="text-sm font-medium text-brand-gray">Bust</label><input type="text" id="bust-size" class="form-input-sleek mt-1" placeholder="e.g., 36 in"/></div><div><label for="under-bust" class="text-sm font-medium text-brand-gray">Under Bust</label><input type="text" id="under-bust" class="form-input-sleek mt-1" placeholder="e.g., 14 in"></div><div><label for="waist-size" class="text-sm font-medium text-brand-gray">Waist</label><input type="text" id="waist-size" class="form-input-sleek mt-1" placeholder="e.g., 29 in"></div><div><label for="hips-size" class="text-sm font-medium text-brand-gray">Hips</label><input type="text" id="hips-size" class="form-input-sleek mt-1" placeholder="e.g., 39 in"></div><div><label for="mini-skirt-length" class="text-sm font-medium text-brand-gray">Mini Skirt Length</label><input type="text" id="mini-skirt-length" class="form-input-sleek mt-1" placeholder="e.g., 17 in"></div><div><label for="half-length" class="text-sm font-medium text-brand-gray">Half Length</label><input type="text" id="half-length" class="form-input-sleek mt-1" placeholder="e.g., 16 in"></div><div><label for="cup-size" class="text-sm font-medium text-brand-gray">Cup Size</label><input type="text" id="cup-size" class="form-input-sleek mt-1" placeholder="e.g., 34C"></div></div><button id="save-measurements-btn" class="w-full bg-brand-text text-white py-3 mt-4 font-semibold hover:bg-gray-800 transition-colors">Confirm Measurements</button></div></div></div>
    <div id="size-chart-modal" class="modal-container hidden"><div id="size-chart-overlay" class="modal-overlay"></div><div class="modal-panel p-6 max-w-xl"><div class="flex justify-between items-center pb-3"><h3 class="text-xl font-serif font-semibold">Size Guide</h3><button id="close-size-chart-btn" class="p-1 text-brand-gray hover:text-brand-text"><i data-feather="x" class="w-5 h-5"></i></button></div><div class="mt-4 modal-content-scrollable pr-2"><img src="images/chart.jpg" alt="Size Chart" class="w-full" /></div></div></div>
    
    <footer class="bg-white border-t border-gray-200">
      <div class="p-6 text-center">
        <p class="text-xs text-brand-gray">© <?=date('Y')?> <?=$site_name?>. All Rights Reserved.</p>
      </div>
    </footer>

  <script>
document.addEventListener("DOMContentLoaded", () => {
    feather.replace();

    const selectors = {
        sidebar: document.getElementById("sidebar"), openSidebarBtn: document.getElementById("open-sidebar-btn"), closeSidebarBtn: document.getElementById("close-sidebar-btn"), sidebarOverlay: document.getElementById("sidebar-overlay"),
        cartSidebar: document.getElementById("cart-sidebar"), openCartBtn: document.getElementById("open-cart-btn"), closeCartBtn: document.getElementById("close-cart-btn"), cartOverlay: document.getElementById("cart-overlay"),
        cartItemsContainer: document.getElementById("cart-items-container"), cartSubtotalEl: document.getElementById("cart-subtotal"), cartItemCountEl: document.getElementById("cart-item-count"),
        addToCartBtn: document.getElementById("add-to-cart-btn"),
        quantityMinusBtn: document.getElementById("quantity-minus"), quantityPlusBtn: document.getElementById("quantity-plus"), quantityDisplay: document.getElementById("quantity-display"),
        openCustomColorBtn: document.getElementById("open-custom-color-btn"), customColorContainer: document.getElementById("custom-color-input-container"), customColorInput: document.getElementById("custom-color"),
        mainImage: document.getElementById("main-product-image"), thumbnails: document.querySelectorAll(".thumbnail-img"),
        colorSwatches: document.querySelectorAll(".color-swatch"), selectedColorName: document.getElementById("selected-color-name"),
        sizeBtns: document.querySelectorAll(".size-btn"), saveMeasurementsBtn: document.getElementById("save-measurements-btn"),
        modals: [
            { modal: document.getElementById("custom-size-modal"), openBtn: document.getElementById("open-custom-size-btn"), closeBtn: document.getElementById("close-custom-size-btn"), overlay: document.getElementById("custom-size-overlay") },
            { modal: document.getElementById("size-chart-modal"), openBtn: document.getElementById("open-size-chart-btn"), closeBtn: document.getElementById("close-size-chart-btn"), overlay: document.getElementById("size-chart-overlay") }
        ]
    };

    const showToast = (text, type = "success") => {
        const background = type === "success" ? "linear-gradient(to right, #00b09b, #96c93d)" : "linear-gradient(to right, #ff5f6d, #ffc371)";
        Toastify({ text, duration: 2500, newWindow: true, close: true, gravity: "top", position: "right", stopOnFocus: true, style: { background, "font-size": "14px" } }).showToast();
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN',
            minimumFractionDigits: 2
        }).format(amount);
    };

    const toggleSidebar = (sidebar, shouldOpen) => { if (!sidebar) return; const isMenuSidebar = sidebar.id === 'sidebar'; if (shouldOpen) { sidebar.classList.remove(isMenuSidebar ? "-translate-x-full" : "translate-x-full"); document.body.classList.add("overflow-hidden"); } else { sidebar.classList.add(isMenuSidebar ? "-translate-x-full" : "translate-x-full"); const isAnotherSidebarOpen = document.querySelector("#sidebar:not(.-translate-x-full), #cart-sidebar:not(.translate-x-full)"); if (!isAnotherSidebarOpen) document.body.classList.remove("overflow-hidden"); } };
    const toggleModal = (modal, shouldOpen) => { if (!modal) return; if (shouldOpen) { modal.classList.remove("hidden"); document.body.classList.add("overflow-hidden"); } else { modal.classList.add("hidden"); if (!document.querySelector(".modal-container:not(.hidden)")) { document.body.classList.remove("overflow-hidden"); } } };
    const resetAddToCartButton = () => { if(selectors.addToCartBtn) { selectors.addToCartBtn.disabled = false; selectors.addToCartBtn.innerHTML = `<i data-feather="shopping-cart" class="w-5 h-5"></i><span>ADD TO CART</span>`; feather.replace(); } };
    
    const updateCartDisplay = async () => {
        try {
            const response = await fetch('update-cart', { method: 'POST' }); 
            if (!response.ok) throw new Error('Network response was not ok.');
            const cartData = await response.json();

            if (cartData && cartData.status === 'success') {
                const container = selectors.cartItemsContainer;
                container.innerHTML = '';

                if (cartData.items && cartData.items.length > 0) {
                    cartData.items.forEach(item => {
                        let optionsHtml = '';
                        if (item.color_name) optionsHtml += `${item.color_name}`; else if (item.custom_color_name) optionsHtml += `Custom: ${item.custom_color_name}`;
                        if (item.size_name) optionsHtml += ` / ${item.size_name}`; else if (item.custom_size_details && item.custom_size_details !== '{}') optionsHtml += ' / Custom Size';

                        const itemHtml = `
                            <div class="flex gap-4 py-4 border-b border-gray-200 last:border-b-0">
                                <img src="${item.product_image}" alt="${item.product_name}" class="w-20 h-24 object-cover">
                                <div class="flex-1 flex flex-col justify-between">
                                    <div><h4 class="font-semibold text-sm">${item.product_name}</h4><p class="text-xs text-brand-gray">${optionsHtml}</p></div>
                                    <div class="flex justify-between items-center mt-2"><p class="text-sm font-medium">${formatCurrency(item.total_price)}</p><p class="text-xs text-brand-gray">Qty: ${item.quantity}</p></div>
                                </div>
                                <div class="flex flex-col items-center gap-2 self-start">
                                    <button class="remove-from-cart-btn p-1 text-brand-gray hover:text-brand-red" data-cart-item-id="${item.id}">
                                        <i data-feather="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>`;
                        container.insertAdjacentHTML('beforeend', itemHtml);
                    });
                } else {
                    const emptyCartHtml = `<div id="empty-cart-message" class="flex flex-col items-center justify-center h-full text-center"><i data-feather="shopping-bag" class="w-16 h-16 text-gray-300 mb-4"></i><p class="text-brand-gray">Your cart is empty.</p></div>`;
                    container.innerHTML = emptyCartHtml;
                }

                selectors.cartSubtotalEl.textContent = formatCurrency(cartData.subtotal);
                
                // --- **FIX**: Cart count now reflects the number of unique items (lines) instead of total quantity.
                const lineItemCount = cartData.items.length;
                selectors.cartItemCountEl.textContent = lineItemCount;
                selectors.cartItemCountEl.style.display = lineItemCount > 0 ? 'flex' : 'none';
                
                feather.replace();
            } else {
                 throw new Error(cartData.message || 'Failed to parse cart data.');
            }
        } catch (error) {
            console.error('Failed to update cart display:', error);
            const container = selectors.cartItemsContainer;
            container.innerHTML = `<div class="text-center text-brand-gray">Could not load cart.</div>`;
        }
    };
    
    const handleDeleteFromCart = async (cartItemId) => {
        try {
            const response = await fetch('delete-cart', {
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

    const handleAddToCart = async () => { const btn = selectors.addToCartBtn; if(!btn) return; btn.disabled = true; btn.innerHTML = `<i data-feather="loader" class="w-5 h-5 animate-spin"></i><span>ADDING...</span>`; feather.replace(); const payload = { productId: parseInt(btn.dataset.productId), quantity: parseInt(selectors.quantityDisplay.textContent) }; const activeColorSwatch = document.querySelector("#color-selector .active-color"); const customColorValue = selectors.customColorInput.value.trim(); const isCustomColorActive = !selectors.customColorContainer.classList.contains("is-closed"); if (isCustomColorActive && customColorValue) { payload.customColor = customColorValue; } else if (activeColorSwatch) { payload.colorId = parseInt(activeColorSwatch.dataset.id); } else { showToast("Please select a color.", "error"); resetAddToCartButton(); return; } const activeSizeBtn = document.querySelector("#size-selector .active-size"); if (activeSizeBtn) { payload.sizeId = parseInt(activeSizeBtn.dataset.id); } else { payload.customSizeDetails = Array.from(document.querySelectorAll("#custom-measurements-form input")).reduce((acc, input) => { const val = input.value.trim(); if(val) acc[input.id.replace(/-/g, '_')] = val; return acc; }, {}); if (Object.keys(payload.customSizeDetails).length === 0) { showToast("Please select a size or provide custom measurements.", "error"); resetAddToCartButton(); return; } } try { const response = await fetch("cart", { method: "POST", headers: { "Content-Type": "application/json", "Accept": "application/json" }, body: JSON.stringify(payload) }); const result = await response.json(); if (response.ok && result.status === "success") { showToast("Item added to cart!"); await updateCartDisplay(); toggleSidebar(selectors.cartSidebar, true); } else { showToast(result.message || "Could not add item.", "error"); } } catch (error) { console.error("Fetch Error:", error); showToast("A network error occurred.", "error"); } finally { resetAddToCartButton(); } };

    // --- EVENT LISTENERS ---
    if(selectors.openSidebarBtn) selectors.openSidebarBtn.addEventListener("click", () => toggleSidebar(selectors.sidebar, true));
    if(selectors.closeSidebarBtn) selectors.closeSidebarBtn.addEventListener("click", () => toggleSidebar(selectors.sidebar, false));
    if(selectors.sidebarOverlay) selectors.sidebarOverlay.addEventListener("click", () => toggleSidebar(selectors.sidebar, false));
    
    if(selectors.openCartBtn) selectors.openCartBtn.addEventListener("click", async () => { await updateCartDisplay(); toggleSidebar(selectors.cartSidebar, true); });
    if(selectors.closeCartBtn) selectors.closeCartBtn.addEventListener("click", () => toggleSidebar(selectors.cartSidebar, false));
    if(selectors.cartOverlay) selectors.cartOverlay.addEventListener("click", () => toggleSidebar(selectors.cartSidebar, false));
    
    if(selectors.cartItemsContainer) {
        selectors.cartItemsContainer.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-from-cart-btn');
            if (removeBtn) {
                const cartItemId = parseInt(removeBtn.dataset.cartItemId);
                handleDeleteFromCart(cartItemId);
            }
        });
    }
    
    if(selectors.quantityPlusBtn) selectors.quantityPlusBtn.addEventListener("click", () => (selectors.quantityDisplay.textContent = parseInt(selectors.quantityDisplay.textContent) + 1));
    if(selectors.quantityMinusBtn) selectors.quantityMinusBtn.addEventListener("click", () => { if (parseInt(selectors.quantityDisplay.textContent) > 1) { selectors.quantityDisplay.textContent = parseInt(selectors.quantityDisplay.textContent) - 1; } });
    if(selectors.addToCartBtn) selectors.addToCartBtn.addEventListener("click", handleAddToCart);

    selectors.thumbnails.forEach(thumb => { thumb.addEventListener("click", function () { if(selectors.mainImage) { selectors.mainImage.src = this.src; selectors.mainImage.alt = this.alt; } selectors.thumbnails.forEach(t => t.classList.remove("active-thumbnail")); this.classList.add("active-thumbnail"); }); });
    selectors.colorSwatches.forEach(swatch => { swatch.addEventListener("click", function() { selectors.colorSwatches.forEach(s => s.classList.remove("active-color")); this.classList.add("active-color"); if(selectors.selectedColorName) selectors.selectedColorName.textContent = this.dataset.color; if (selectors.customColorContainer) selectors.customColorContainer.classList.add("is-closed"); if (selectors.customColorInput) selectors.customColorInput.value = ""; }); });
    selectors.sizeBtns.forEach(btn => { btn.addEventListener("click", function() { selectors.sizeBtns.forEach(b => b.classList.remove("active-size")); this.classList.add("active-size"); document.querySelectorAll("#custom-measurements-form input").forEach(input => input.value = ''); }); });
    
    if (selectors.openCustomColorBtn) { selectors.openCustomColorBtn.addEventListener("click", (e) => { e.preventDefault(); if (selectors.customColorContainer) { const isNowClosed = selectors.customColorContainer.classList.toggle("is-closed"); if (!isNowClosed) { selectors.colorSwatches.forEach(s => s.classList.remove("active-color")); if (selectors.selectedColorName) selectors.selectedColorName.textContent = "Custom"; if (selectors.customColorInput) selectors.customColorInput.focus(); } } }); }
    
    if (selectors.saveMeasurementsBtn) { selectors.saveMeasurementsBtn.addEventListener("click", (e) => { e.preventDefault(); showToast("Measurements noted. Click 'Add to Cart' to confirm."); toggleModal(document.getElementById("custom-size-modal"), false); }); }
    
    selectors.modals.forEach(({ modal, openBtn, closeBtn, overlay }) => {
        if (openBtn) { openBtn.addEventListener("click", (e) => { e.preventDefault(); if (modal && modal.id === "custom-size-modal") { selectors.sizeBtns.forEach(b => b.classList.remove("active-size")); } toggleModal(modal, true); }); }
        if (closeBtn) { closeBtn.addEventListener("click", () => toggleModal(modal, false)); }
        if (overlay) { overlay.addEventListener("click", () => toggleModal(modal, false)); }
    });

    document.addEventListener("keydown", (event) => { if (event.key === "Escape") { toggleSidebar(selectors.sidebar, false); toggleSidebar(selectors.cartSidebar, false); selectors.modals.forEach(({ modal }) => { if (modal && !modal.classList.contains("hidden")) toggleModal(modal, false); }); } });
    
    updateCartDisplay();
});
</script>
  </body>
</html>