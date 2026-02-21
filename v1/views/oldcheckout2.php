<?php
// --- NEW: CURRENCY CONFIGURATION ---
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

$cookie_name = 'cart_token';
if (!isset($_COOKIE[$cookie_name])) {
    header("Location: /view-cart");
    exit;
}
$cartToken = $_COOKIE[$cookie_name];

// --- CORRECTED: The SQL query is now accurate and simplified ---
$cartSql = "
    SELECT 
        ci.*, 
        pp.name AS product_name, 
        pp.image_one AS product_image
    FROM cart_items ci
    JOIN panel_products pp ON ci.product_id = pp.id
    WHERE ci.cart_token = ?
";
$cartStmt = $conn->prepare($cartSql);
$cartStmt->execute([$cartToken]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    header("Location: /view-cart");
    exit;
}

$subtotal = array_sum(array_column($cartItems, 'total_price'));
$shippingLocations = selectContent($conn, "shipping_fees", ['is_active' => TRUE]);
$logo_directory = $logo_directory ?? 'path/to/your/logo.png';
$site_name = $site_name ?? 'Your Site Name';

// --- NEW: INCLUDE COUNTRIES & STATES DATA ---
$countriesData = include 'country_states.php';
// Sort countries alphabetically by name for user-friendliness
uasort($countriesData, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
<title><?=$site_name?> | Checkout</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: { colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', }, fontFamily: { 'sans': ['Lato', 'ui-sans-serif', 'system-ui'], 'serif': ['Playfair Display', 'serif'], } } } };
</script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/feather-icons"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<style>
.form-input-sleek { background-color: transparent; border: 0; border-bottom: 1px solid #d1d5db; border-radius: 0; padding: 0.75rem 0.25rem; width: 100%; transition: border-color 0.2s; -webkit-appearance: none; -moz-appearance: none; appearance: none; } 
.form-input-sleek:focus { outline: none; border-bottom-color: #1A1A1A; }
/* --- NEW: Style for select dropdown arrow --- */
select.form-input-sleek { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; }
.step-header { display: flex; align-items: center; gap: 0.75rem; font-size: 1.25rem; font-family: ['Playfair Display', serif; font-weight: 600; margin-bottom: 1.5rem; }
.step-header .step-circle { display: flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: 9999px; background-color: #1A1A1A; color: white; font-size: 0.875rem; font-weight: bold; font-family: 'Lato', sans-serif; }
.toastify { padding: 12px 20px; font-size: 14px; font-weight: 500; border-radius: 8px; }
.hidden { display: none; }
</style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">

<!-- HEADER -->
<section class="relative h-48 md:h-56 bg-cover bg-center" style="background-image: url('images/1.jpg');">
    <div class="absolute inset-0 bg-black/40"></div>
    <header class="absolute inset-x-0 top-0 z-20">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-center h-20">
                <a href="/home"><img src="<?=$logo_directory?>" alt="<?=$site_name?> Logo" class="h-10 w-auto filter brightness-0 invert"></a>
            </div>
        </div>
    </header>
    <div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4">
        <h1 class="text-5xl md:text-6xl font-serif font-semibold pt-12">Checkout</h1>
    </div>
</section>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
<div class="grid grid-cols-1 lg:grid-cols-2 lg:gap-x-16 xl:gap-x-24">

<!-- Left Column -->
<div class="w-full">
<form id="checkout-form" class="space-y-12" onsubmit="return false;">
    <div>
        <div class="step-header"><div class="step-circle">1</div><h2>Contact Information</h2></div>
        <div><label for="email" class="block text-sm font-medium text-brand-gray mb-1">Email Address</label><input type="email" id="email" name="email" class="form-input-sleek" placeholder="you@example.com" required></div>
    </div>
    <div>
        <div class="step-header"><div class="step-circle">2</div><h2>Shipping Address</h2></div>
        <div class="space-y-6">
            <div><label for="full-name" class="block text-sm font-medium text-brand-gray mb-1">Full Name</label><input type="text" id="full-name" name="full-name" class="form-input-sleek" placeholder="Jane Doe" required></div>
            <div><label for="phone-number" class="block text-sm font-medium text-brand-gray mb-1">Phone Number</label><input type="tel" id="phone-number" name="phone-number" class="form-input-sleek" placeholder="e.g. 08012345678" required></div>
            <div><label for="address" class="block text-sm font-medium text-brand-gray mb-1">Address</label><input type="text" id="address" name="address" class="form-input-sleek" placeholder="123 Main Street" required></div>
            
            <!-- --- UPDATED: COUNTRY SELECTOR --- -->
            <div>
                <label for="country" class="block text-sm font-medium text-brand-gray mb-1">Country</label>
                <select id="country" name="country" class="form-input-sleek" required>
                    <option value="">-- Select a Country --</option>
                    <?php foreach ($countriesData as $code => $country): ?>
                        <option value="<?= $code ?>" <?= ($code === 'NG') ? 'selected' : '' ?>>
                            <?= htmlspecialchars($country['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
                <div><label for="city" class="block text-sm font-medium text-brand-gray mb-1">City</label><input type="text" id="city" name="city" class="form-input-sleek" required></div>
                
                <!-- --- UPDATED: DYNAMIC STATE/PROVINCE FIELD --- -->
                <div>
                    <!-- This container holds the dropdown -->
                    <div id="state-select-wrapper">
                        <label for="state-select" class="block text-sm font-medium text-brand-gray mb-1">State / Province</label>
                        <select id="state-select" name="state" class="form-input-sleek" required>
                            <option value="">-- Select a state --</option>
                        </select>
                    </div>
                    <!-- This container holds the text input (fallback) -->
                    <div id="state-input-wrapper" class="hidden">
                        <label for="state-input" class="block text-sm font-medium text-brand-gray mb-1">State / Province</label>
                        <input type="text" id="state-input" name="state" class="form-input-sleek" required>
                    </div>
                </div>
            </div>

             <div>
                <label for="shipping-location" class="block text-sm font-medium text-brand-gray mb-1">Shipping Location (Nigeria Only)</label>
                <select id="shipping-location" name="shipping-location" class="form-input-sleek" required>
                    <option value="">-- Select your location --</option>
                    <?php foreach ($shippingLocations as $location): ?>
                        <option value="<?= $location['id'] ?>" data-fee="<?= $location['fee'] ?>"><?= htmlspecialchars($location['location_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</form>
</div>

<!-- Right Column (Order Summary) -->
<div class="w-full mt-16 lg:mt-0">
<div class="bg-white p-8 border border-gray-200/70 lg:sticky lg:top-28">
    <h2 class="text-2xl font-serif font-semibold mb-6">Order Summary</h2>
    <div id="summary-items-container" class="space-y-5">
        <?php foreach ($cartItems as $item):
            $options = '';
            if (!empty($item['color_name'])) {
                $options .= $item['color_name'];
            } elseif (!empty($item['custom_color_name'])) {
                $options .= 'Custom Color: ' . $item['custom_color_name'];
            }

            if (!empty($item['size_name'])) {
                $options .= ($options ? ' / ' : '') . $item['size_name'];
            } elseif (!empty($item['custom_size_details']) && $item['custom_size_details'] !== '{}') {
                $options .= ($options ? ' / ' : '') . 'Custom Size';
            }
        ?>
        <div class="flex justify-between items-start gap-4" 
             data-name="<?= htmlspecialchars($item['product_name']) ?>" 
             data-quantity="<?= $item['quantity'] ?>" 
             data-color="<?= htmlspecialchars($item['color_name'] ?: '') ?>"
             data-custom-color="<?= htmlspecialchars($item['custom_color_name'] ?: '') ?>"
             data-size="<?= htmlspecialchars($item['size_name'] ?: '') ?>"
             data-custom-size-details="<?= htmlspecialchars($item['custom_size_details'] ?: '{}') ?>"
        >
            <div class="relative flex-shrink-0">
                <img src="/<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="w-20 h-24 object-cover rounded-md border border-gray-200">
                <span class="absolute -top-2 -right-2 bg-brand-gray text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold"><?= $item['quantity'] ?></span>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-sm"><?= htmlspecialchars($item['product_name']) ?></h3>
                <p class="text-xs text-brand-gray"><?= htmlspecialchars($options) ?></p>
            </div>
            <p class="font-medium text-sm price-display" data-price-ngn="<?= $item['total_price'] ?>">₦<?= number_format($item['total_price'], 2) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-6 pt-6 border-t border-gray-200 space-y-3 text-sm">
        <div class="flex justify-between"><span class="text-brand-gray">Subtotal</span><span id="summary-subtotal" class="font-medium price-display" data-price-ngn="<?= $subtotal ?>">₦<?= number_format($subtotal, 2) ?></span></div>
        <div class="flex justify-between"><span class="text-brand-gray">Shipping</span><span id="summary-shipping" class="font-medium price-display" data-price-ngn="0">₦0.00</span></div>
        <div class="flex justify-between text-lg font-semibold pt-4 mt-2 border-t border-gray-200"><span>Total</span><span id="summary-total" class="price-display" data-price-ngn="<?= $subtotal ?>">₦<?= number_format($subtotal, 2) ?></span></div>
    </div>
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-serif font-semibold mb-4">Payment</h3>
        <p class="text-sm text-brand-gray mb-4">Finalize your order by sending the details via WhatsApp.</p>
        
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 text-sm">
            <div class="flex">
                <div class="flex-shrink-0"><i data-feather="clock" class="h-5 w-5 text-yellow-500"></i></div>
                <div class="ml-3">
                    <p class="font-medium text-yellow-800">Please Note</p>
                    <div class="mt-1 text-yellow-700">
                        <p>Standard processing time is <strong>7-10 working days</strong>.</p>
                        <p>For an express order, please indicate this in your WhatsApp message.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="button" id="whatsapp-button" class="w-full bg-green-600 text-white py-4 text-center font-semibold hover:bg-green-700 transition-colors duration-300 flex items-center justify-center gap-3 rounded-md">
            <i data-feather="message-circle" class="w-4 h-4"></i><span>Order via WhatsApp</span>
        </button>
    </div>
</div>
</div>
</div>
</main>

<footer class="bg-white border-t border-gray-200 mt-16"><div class="container mx-auto px-6 py-8 text-center text-sm text-brand-gray"><p>© <?=date('Y')?> <?=$site_name?>. All Rights Reserved.</p></div></footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
    feather.replace();

    // --- NEW: DYNAMIC STATE/COUNTRY LOGIC ---

    // Pass the PHP array of countries and states to JavaScript
    const countriesData = <?= json_encode($countriesData); ?>;

    const countrySelect = document.getElementById('country');
    const stateSelectWrapper = document.getElementById('state-select-wrapper');
    const stateSelect = document.getElementById('state-select');
    const stateInputWrapper = document.getElementById('state-input-wrapper');
    const stateInput = document.getElementById('state-input');
    const shippingLocationSelect = document.getElementById('shipping-location');

    function updateStateField() {
        const selectedCountryCode = countrySelect.value;
        const country = countriesData[selectedCountryCode];

        // Clear previous states
        stateSelect.innerHTML = '<option value="">-- Select a state --</option>';
        stateInput.value = '';

        // If a country is selected and it has a list of states
        if (country && country.states && Object.keys(country.states).length > 0) {
            // Show the dropdown, hide the text input
            stateSelectWrapper.classList.remove('hidden');
            stateInputWrapper.classList.add('hidden');
            stateSelect.disabled = false;
            stateInput.disabled = true;

            // Populate the state dropdown
            for (const [stateCode, stateName] of Object.entries(country.states)) {
                const option = document.createElement('option');
                option.value = stateCode;
                option.textContent = stateName;
                stateSelect.appendChild(option);
            }
        } else { // If country has no states listed or no country is selected
            // Show the text input, hide the dropdown
            stateSelectWrapper.classList.add('hidden');
            stateInputWrapper.classList.remove('hidden');
            stateSelect.disabled = true;
            stateInput.disabled = false;
        }

        // --- NEW: Logic to show/hide Nigeria-specific shipping ---
        if (selectedCountryCode === 'NG') {
            shippingLocationSelect.parentElement.classList.remove('hidden');
            shippingLocationSelect.required = true;
        } else {
            shippingLocationSelect.parentElement.classList.add('hidden');
            shippingLocationSelect.required = false;
            shippingLocationSelect.value = ''; // Reset selection
            // Manually trigger change to reset shipping fee if another country is selected
            shippingLocationSelect.dispatchEvent(new Event('change'));
        }
    }

    // Add event listener to the country dropdown
    countrySelect.addEventListener('change', updateStateField);
    
    // Initial call to set the state field based on the default selected country (Nigeria)
    updateStateField();


    // --- CURRENCY LOGIC ---
    const USD_RATE = <?= USD_EXCHANGE_RATE ?>;
    const INITIAL_CURRENCY = '<?= $current_currency ?>';
    const formatCurrency = (amount, currency = 'NGN') => {
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

    // --- MAIN CHECKOUT LOGIC ---
    let cartSubtotal = <?= (float)$subtotal ?>;
    let shippingFee = 0;

    const updateOrderSummary = () => {
        const grandTotal = cartSubtotal + shippingFee;
        document.getElementById('summary-subtotal').dataset.priceNgn = cartSubtotal;
        document.getElementById('summary-shipping').dataset.priceNgn = shippingFee;
        document.getElementById('summary-total').dataset.priceNgn = grandTotal;
        const activeCurrency = document.querySelector('.currency-switcher a.active')?.dataset.currency || INITIAL_CURRENCY;
        updateAllPrices(activeCurrency);
    };

    shippingLocationSelect.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        shippingFee = parseFloat(selectedOption.dataset.fee) || 0;
        updateOrderSummary();
    });

    // --- UPDATED: WhatsApp Message Generation ---
    document.getElementById('whatsapp-button').addEventListener('click', () => {
        const form = document.getElementById('checkout-form');
        if (!form.checkValidity()) {
            form.reportValidity();
            Toastify({ text: "Please fill out all required fields.", duration: 3000, gravity: "top", position: "center", backgroundColor: "#ef4444" }).showToast();
            return;
        }

        // Get values from the form
        const fullName = document.getElementById('full-name').value;
        const phone = document.getElementById('phone-number').value;
        const address = document.getElementById('address').value;
        const city = document.getElementById('city').value;
        
        // --- NEW: Smartly get country and state values ---
        const countryName = countrySelect.options[countrySelect.selectedIndex].text;
        let stateValue;
        if (!stateSelectWrapper.classList.contains('hidden')) {
            stateValue = stateSelect.options[stateSelect.selectedIndex].text;
        } else {
            stateValue = stateInput.value;
        }

        const shippingLocationName = shippingLocationSelect.value ? shippingLocationSelect.options[shippingLocationSelect.selectedIndex].text : 'N/A';
        const total = document.getElementById('summary-total').textContent;

        let message = `Hello, I want to place an order:\n\n` +
                      `*Name:* ${fullName}\n` +
                      `*Phone:* ${phone}\n` +
                      `*Address:* ${address}, ${city}, ${stateValue}, ${countryName}\n`;
        
        if (countrySelect.value === 'NG') {
            message += `*Shipping Location (NG):* ${shippingLocationName}\n`;
        }

        message += `*Grand Total:* ${total}\n\n` +
                   `--- *ORDER DETAILS* ---\n`;
        
        document.querySelectorAll('#summary-items-container > div').forEach(itemElement => {
            const itemData = itemElement.dataset;
            const itemTotalPrice = itemElement.querySelector('.price-display').textContent;
            
            message += `\n*${itemData.name}* (Qty: ${itemData.quantity}) - ${itemTotalPrice}\n`;

            if (itemData.customColor) {
                message += `  - Color: *Custom - ${itemData.customColor}*\n`;
            } else if (itemData.color) {
                message += `  - Color: ${itemData.color}\n`;
            }

            if (itemData.size) {
                message += `  - Option: ${itemData.size}\n`;
            } else if (itemData.customSizeDetails && itemData.customSizeDetails !== '{}') {
                try {
                    const measurements = JSON.parse(itemData.customSizeDetails);
                    message += `  - *Custom Measurements:*\n`;
                    for (const [key, value] of Object.entries(measurements)) {
                        const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        if (value) {
                            message += `    - ${formattedKey}: ${value}\n`;
                        }
                    }
                } catch (e) {
                    message += `  - Custom Size Details: ${itemData.customSizeDetails}\n`;
                }
            }
        });

        const whatsappNumber = "+2347030630613";
        const encodedMessage = encodeURIComponent(message);
        window.open(`https://wa.me/${whatsappNumber}?text=${encodedMessage}`, "_blank");
    });

    if (INITIAL_CURRENCY === 'USD') {
        updateAllPrices('USD');
    }
});
</script>
</body>
</html>