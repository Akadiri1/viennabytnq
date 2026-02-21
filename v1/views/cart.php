<?php
// --- NEW: CURRENCY CONFIGURATION (Copied from checkout) ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function getLiveUsdToNgnRate() {
    // Try multiple free exchange rate APIs
    $apis = [
        'https://open.er-api.com/v6/latest/USD',
        'https://api.exchangerate-api.com/v4/latest/USD'
    ];
    
    foreach ($apis as $apiUrl) {
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $response = @file_get_contents($apiUrl, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['rates']['NGN'])) {
                return floatval($data['rates']['NGN']);
            }
        }
    }
    
    // Fallback in case all APIs fail - use current market rate (Jan 2025)
    return 1480; // Current market rate (Jan 2025)
}

if (!defined('USD_EXCHANGE_RATE')) {
    define('USD_EXCHANGE_RATE', getLiveUsdToNgnRate());
}

if (isset($_SESSION['currency'])) {
    $current_currency = $_SESSION['currency'];
} elseif (isset($_COOKIE['user_currency'])) {
    $current_currency = $_COOKIE['user_currency'];
} else {
    $current_currency = 'NGN'; // Default currency
}

// We need the cookie logic here too, in case a user lands on this page first.
$cookie_name = 'cart_token';
$thirty_days = time() + (86400 * 30);
if (!isset($_COOKIE[$cookie_name])) {
    $token = bin2hex(random_bytes(32)); 
    setcookie($cookie_name, $token, ['expires' => $thirty_days, 'path' => '/', 'secure' => isset($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax']);
    // New user = Empty cart = Redirect
    header("Location: /shop");
    exit;
} else {
    // Check if the existing token actually has items
    $token = $_COOKIE[$cookie_name];
    $stmt = $conn->prepare("SELECT id FROM cart_items WHERE cart_token = ? LIMIT 1");
    $stmt->execute([$token]);
    if ($stmt->rowCount() === 0) {
        header("Location: /shop");
        exit;
    }
}

// Fetch breadcrumb image from DB
$productBreadcrumb = selectContent($conn, "product_breadcrumb", ['visibility' => 'show']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
    <title><?=$site_name?> | Your Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', 'brand-red': '#EF4444', }, fontFamily: { 'sans': ['Lato', 'ui-sans-serif', 'system-ui'], 'serif': ['Playfair Display', 'serif'], } } } };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .toastify { padding: 12px 20px; font-size: 14px; font-weight: 500; border-radius: 8px; box-shadow: 0 3px 6px -1px rgba(0,0,0,.12), 0 10px 36px -4px rgba(51,45,45,.25); }
        /* --- NEW: CURRENCY SWITCHER STYLE --- */
        .currency-switcher a.active { background-color: #1A1A1A; color: white; }
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">

<!-- SIDEBAR MENU -->
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
        <div class="p-6 border-t border-gray-200"><p class="text-xs text-brand-gray text-center">© <?=date('Y')?> <?=$site_name?></p></div>
    </div>
</div>

<!-- HEADER -->
<header class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex-1 flex justify-start"><button id="open-sidebar-btn" class="p-2 text-brand-text hover:text-brand-gray"><i data-feather="menu" class="h-6 w-6"></i></button></div>
            <div class="flex-shrink-0 text-center"><a href="/home"><div class="text-2xl font-serif font-bold tracking-widest"><?=$site_name?></div></a></div>
            <div class="flex-1 flex items-center justify-end space-x-4">
                <a href="<?= isset($_SESSION['user_id']) ? '/user-dashboard' : '/register' ?>" class="p-2 text-brand-text hover:text-brand-gray" title="<?= isset($_SESSION['user_id']) ? 'My Account' : 'Login / Register' ?>">
                    <i data-feather="user" class="h-5 w-5"></i>
                </a>
                <a href="/view-cart" class="p-2 text-brand-text hover:text-brand-gray relative">
                    <i data-feather="shopping-bag" class="h-5 w-5"></i>
                    <span id="header-cart-count" class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-brand-red text-white text-xs flex items-center justify-center font-bold" style="font-size: 8px; display: none;">0</span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- IMAGE BREADCRUMB SECTION -->
<section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($productBreadcrumb[0]['input_image'] ?? '') ?>');">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4">
        <nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb">
            <ol class="list-none p-0 inline-flex items-center">
                <li class="flex items-center"><a href="/home" class="hover:underline">Home</a><i data-feather="chevron-right" class="h-4 w-4 mx-2"></i></li>
                <li><span>Your Cart</span></li>
            </ol>
        </nav>
        <h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4">Your Cart</h1>
    </div>  
</section>

<!-- MAIN CART CONTENT -->
<main class="bg-brand-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div id="cart-layout" class="lg:grid lg:grid-cols-3 lg:gap-12">
            
            <section class="lg:col-span-2" id="cart-container"><!-- Populated by JS --></section>

            <aside class="mt-12 lg:mt-0 lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6 lg:p-8 sticky top-24">
                    <!-- --- NEW: ADDED CURRENCY SWITCHER --- -->
                    <div class="flex justify-between items-center border-b border-gray-200 pb-4">
                        <h2 class="text-xl font-serif font-semibold">Order Summary</h2>
                        <div class="currency-switcher flex items-center border border-gray-300 rounded-full text-sm font-medium">
                            <a href="#" data-currency="NGN" class="px-3 py-1 rounded-full <?= ($current_currency === 'NGN') ? 'active' : '' ?>">NGN</a>
                            <a href="#" data-currency="USD" class="px-3 py-1 rounded-full <?= ($current_currency === 'USD') ? 'active' : '' ?>">USD</a>
                        </div>
                    </div>
                    <dl class="mt-6 space-y-4">
                        <!-- --- MODIFIED: Added price-display class and data attributes --- -->
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-brand-gray">Subtotal</dt>
                            <dd id="summary-subtotal" class="text-sm font-medium text-brand-text price-display" data-price-ngn="0">₦0.00</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-brand-gray">Shipping</dt>
                            <dd class="text-sm font-medium text-brand-text">Calculated at next step</dd>
                        </div>
                        <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
                            <dt class="text-base font-semibold">Order total</dt>
                            <dd id="summary-ordertotal" class="text-base font-semibold price-display" data-price-ngn="0">₦0.00</dd>
                        </div>
                    </dl>
                    <div class="mt-8"><a href="checkout" id="checkout-link" class="block w-full bg-brand-text text-white py-3 px-4 rounded-md text-center font-semibold hover:bg-gray-800 transition-colors">Proceed to Checkout</a></div>
                    <p class="text-xs text-center text-brand-gray mt-4">Taxes and shipping calculated at checkout</p>
                </div>
            </aside>
        </div>
    </div>
</main>

<!-- FOOTER -->
<footer class="bg-white border-t border-gray-200">
    <div class="p-6 text-center">
        <p class="text-xs text-brand-gray">© <?=date('Y')?> <?=$site_name?>. All Rights Reserved.</p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
    feather.replace();
    const selectors = { cartContainer: document.getElementById('cart-container'), summarySubtotalEl: document.getElementById('summary-subtotal'), summaryOrdertotalEl: document.getElementById('summary-ordertotal'), headerCartCountEl: document.getElementById('header-cart-count'), checkoutLink: document.getElementById('checkout-link'), cartLayout: document.getElementById('cart-layout'), sidebar: document.getElementById('sidebar'), openSidebarBtn: document.getElementById('open-sidebar-btn'), closeSidebarBtn: document.getElementById('close-sidebar-btn'), sidebarOverlay: document.getElementById('sidebar-overlay') };
    const showToast = (text, type = "success") => { Toastify({ text, duration: 2500, newWindow: true, close: true, gravity: "top", position: "right", stopOnFocus: true, style: { background: type === "success" ? "linear-gradient(to right, #00b09b, #96c93d)" : "linear-gradient(to right, #ff5f6d, #ffc371)", "font-size": "14px" } }).showToast(); };
    
    // --- NEW: CURRENCY LOGIC ---
    const USD_RATE = <?= USD_EXCHANGE_RATE ?>;
    const INITIAL_CURRENCY = '<?= $current_currency ?>';
    
    const formatCurrency = (amount, currency) => {
        if (currency === 'USD') {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
        }
        return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount).replace('NGN', '₦');
    };

    function updateAllPrices(targetCurrency) {
        document.querySelectorAll('.price-display').forEach(el => {
            const ngnPrice = parseFloat(el.dataset.priceNgn);
            if (!isNaN(ngnPrice)) {
                let newPrice = (targetCurrency === 'USD') ? ngnPrice / USD_RATE : ngnPrice;
                el.textContent = formatCurrency(newPrice, targetCurrency);
            }
        });
    }

    // --- NEW: CURRENCY SWITCHER EVENT LISTENER ---
    document.querySelector('.currency-switcher').addEventListener('click', (e) => {
        e.preventDefault();
        const target = e.target.closest('a');
        if (!target || target.classList.contains('active')) return;
        const newCurrency = target.dataset.currency;
        document.querySelectorAll('.currency-switcher a').forEach(a => a.classList.remove('active'));
        target.classList.add('active');
        updateAllPrices(newCurrency);
        fetch('/currency', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'currency=' + newCurrency
        }).catch(error => console.error('Error updating currency on server:', error));
    });

    // --- MODIFIED: renderCart function to support currency switching ---
    const renderCart = (cartData) => {
        selectors.cartContainer.innerHTML = '';
        let subtotal = cartData.subtotal || 0;
        let lineItemCount = (cartData.items && cartData.items.length) ? cartData.items.length : 0;
        
        if (lineItemCount === 0) {
            selectors.cartLayout.classList.remove('lg:grid');
            selectors.cartContainer.innerHTML = `<div class="text-center bg-white rounded-lg shadow-sm p-12"><i data-feather="shopping-bag" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i><h2 class="text-2xl font-serif font-semibold text-brand-text">Your cart is empty</h2><p class="mt-2 text-sm text-brand-gray">Looks like you haven't added anything to your cart yet.</p><a href="/shop" class="mt-6 inline-block bg-brand-text text-white py-2 px-6 rounded-md text-sm font-semibold hover:bg-gray-800 transition-colors">Continue Shopping</a></div>`;
        } else {
            selectors.cartLayout.classList.add('lg:grid');
            const cartItemsHTML = `<h2 class="sr-only">Shopping Cart Items</h2><div class="hidden md:grid grid-cols-6 gap-4 text-left text-xs font-medium text-brand-gray tracking-wider uppercase border-b border-gray-200 pb-3"><div class="col-span-3">Product</div><div class="col-span-1 text-center">Price</div><div class="col-span-1 text-center">Quantity</div><div class="col-span-1 text-right">Total</div></div><ul role="list" class="divide-y divide-gray-200">${cartData.items.map(item => {
                let optionsHtml = '';
                if (item.color_name) { optionsHtml += item.color_name;
                } else if (item.custom_color_name) { optionsHtml += `Custom: ${item.custom_color_name}`; }
                if (item.size_name) { optionsHtml += (optionsHtml ? ' / ' : '') + item.size_name;
                } else if (item.custom_size_details && item.custom_size_details !== '{}') { optionsHtml += (optionsHtml ? ' / ' : '') + 'Custom Size'; }
                
                // MODIFIED: Added price-display class and data-price-ngn attributes to the generated HTML
                return `<li class="py-6 flex flex-col md:grid md:grid-cols-6 md:gap-4 md:items-center" data-id="${item.id}" data-quantity="${item.quantity}">
                            <!-- Product Column -->
                            <div class="flex items-center space-x-4 col-span-3">
                                <img src="${item.product_image}" alt="${item.product_name}" class="w-24 h-32 object-cover rounded-md">
                                <div>
                                    <h3 class="text-base font-medium text-brand-text">${item.product_name}</h3>
                                    <p class="mt-1 text-sm text-brand-gray">${optionsHtml}</p>
                                </div>
                            </div>

                            <!-- Price Column (md+) -->
                            <div class="hidden md:block col-span-1 text-center">
                                <p class="text-sm text-brand-text price-display" data-price-ngn="${item.unit_price}"></p>
                            </div>

                            <!-- Quantity Column -->
                            <div class="mt-4 md:mt-0 col-span-3 md:col-span-1">
                                <div class="flex items-center justify-between md:justify-center">
                                    <span class="text-sm text-brand-gray md:hidden">Quantity</span>
                                    <div class="flex items-center border border-gray-300 rounded-md">
                                        <button class="quantity-minus p-2 text-brand-gray hover:text-brand-text"><i data-feather="minus" class="w-4 h-4"></i></button>
                                        <input type="text" value="${item.quantity}" class="w-10 text-center bg-transparent border-none focus:ring-0" readonly>
                                        <button class="quantity-plus p-2 text-brand-gray hover:text-brand-text"><i data-feather="plus" class="w-4 h-4"></i></button>
                                    </div>
                                </div>
                            </div>

                            <!-- Total & Action Column -->
                            <div class="mt-4 md:mt-0 col-span-3 md:col-span-1">
                                <div class="flex items-center justify-between md:justify-end space-x-3">
                                    <span class="text-sm text-brand-gray md:hidden">Total</span>
                                    <div class="flex items-center space-x-3">
                                        <p class="text-base font-semibold text-brand-text price-display" data-price-ngn="${item.total_price}"></p>
                                        <button class="remove-item p-1 text-brand-gray hover:text-brand-red"><i data-feather="trash-2" class="w-4 h-4"></i></button>
                                    </div>
                                </div>
                            </div>
                        </li>`;}).join('')}</ul><div class="mt-8"><a href="/shop" class="inline-flex items-center text-sm font-medium text-brand-text hover:text-brand-gray group"><i data-feather="arrow-left" class="w-4 h-4 mr-2 transition-transform group-hover:-translate-x-1"></i>Continue Shopping</a></div>`;
            selectors.cartContainer.innerHTML = cartItemsHTML;
        }
        
        // MODIFIED: Set data attributes for summary and then update all prices
        selectors.summarySubtotalEl.dataset.priceNgn = subtotal;
        selectors.summaryOrdertotalEl.dataset.priceNgn = subtotal;
        
        if (lineItemCount > 0) {
            selectors.headerCartCountEl.textContent = lineItemCount;
            selectors.headerCartCountEl.style.display = 'flex';
            selectors.checkoutLink.classList.remove('pointer-events-none', 'opacity-50');
        } else {
            selectors.headerCartCountEl.style.display = 'none';
            selectors.checkoutLink.classList.add('pointer-events-none', 'opacity-50');
        }
        feather.replace();
        
        // NEW: Call updateAllPrices to format all new and existing prices correctly
        const activeCurrency = document.querySelector('.currency-switcher a.active').dataset.currency;
        updateAllPrices(activeCurrency);
    };

    const fetchCart = async () => { try { const response = await fetch('update-cart', { method: 'POST' }); if (!response.ok) throw new Error('Failed to fetch cart.'); const cartData = await response.json(); if (cartData.status === 'success') { renderCart(cartData); } else { showToast(cartData.message || 'Error loading cart.', 'error'); } } catch (error) { console.error('Fetch Cart Error:', error); showToast('Could not connect to cart service.', 'error'); } };
    const updateQuantity = async (itemId, newQuantity) => { try { const response = await fetch('update-quantity', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ cartItemId: itemId, quantity: newQuantity }) }); const result = await response.json(); if (!response.ok || result.status !== 'success') { throw new Error(result.message || 'Failed to update quantity.'); } await fetchCart(); } catch (error) { console.error('Update Quantity Error:', error); showToast(error.toString(), 'error'); await fetchCart(); } };
    const deleteItem = async (itemId) => { try { const response = await fetch('delete-cart', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ cartItemId: itemId }) }); const result = await response.json(); if (response.ok && result.status === 'success') { showToast(result.message); await fetchCart(); } else { throw new Error(result.message || 'Failed to delete item.'); } } catch (error) { console.error('Delete Item Error:', error); showToast(error.toString(), 'error'); } };
    
    selectors.cartContainer.addEventListener('click', (e) => {
        const target = e.target;
        const parentLi = target.closest('li');
        if (!parentLi) return;
        const itemId = parseInt(parentLi.dataset.id);
        let currentQuantity = parseInt(parentLi.dataset.quantity);
        if (target.closest('.quantity-plus')) { updateQuantity(itemId, currentQuantity + 1); }
        if (target.closest('.quantity-minus')) { if (currentQuantity > 1) { updateQuantity(itemId, currentQuantity - 1); } }
        if (target.closest('.remove-item')) { deleteItem(itemId); }
    });

    const toggleSidebar = (shouldOpen) => { if (selectors.sidebar) { if(shouldOpen) { selectors.sidebar.classList.remove('-translate-x-full'); document.body.classList.add('overflow-hidden'); } else { selectors.sidebar.classList.add('-translate-x-full'); document.body.classList.remove('overflow-hidden'); } } };
    selectors.openSidebarBtn.addEventListener('click', () => toggleSidebar(true));
    selectors.closeSidebarBtn.addEventListener('click', () => toggleSidebar(false));
    selectors.sidebarOverlay.addEventListener('click', () => toggleSidebar(false));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') toggleSidebar(false); });
    
    // --- NEW: Run initial price formatting and then fetch the cart ---
    updateAllPrices(INITIAL_CURRENCY);
    fetchCart();
});
</script>

</body>
</html>