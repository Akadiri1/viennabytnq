<?php
$cookie_name = 'cart_token';
if (!isset($_COOKIE[$cookie_name])) {
    header("Location: /cart"); // Redirect to cart if there's no token/cart
    exit;
}
$cartToken = $_COOKIE[$cookie_name];

// Fetch cart data to populate the page initially
$cartSql = "SELECT ci.*, pp.name AS product_name, pp.image_one AS product_image FROM cart_items ci JOIN panel_products pp ON ci.product_id = pp.id WHERE ci.cart_token = ?";
$cartStmt = $conn->prepare($cartSql);
$cartStmt->execute([$cartToken]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    header("Location: /cart"); // Redirect to cart if it's empty
    exit;
}

$subtotal = array_sum(array_column($cartItems, 'total_price'));

// Fetch shipping locations for the dropdown
$shippingLocations = selectContent($conn, "shipping_fees", ['is_active' => TRUE]);

// Assuming these variables are defined elsewhere for your header/footer
$logo_directory = $logo_directory ?? 'path/to/your/logo.png';
$site_name = $site_name ?? 'Your Site Name';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
<title><?=$site_name?> | Checkout</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: { colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', }, fontFamily: { 'sans': ['Inter', 'ui-sans-serif', 'system-ui'], 'serif': ['Cormorant Garamond', 'serif'], } } } };
</script>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/feather-icons"></script>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<style>
.form-input-sleek { background-color: transparent; border: 0; border-bottom: 1px solid #d1d5db; border-radius: 0; padding: 0.75rem 0.25rem; width: 100%; transition: border-color 0.2s; } .form-input-sleek:focus { outline: none; box-shadow: none; ring: 0; border-bottom-color: #1A1A1A; } .step-header { display: flex; align-items: center; gap: 0.75rem; font-size: 1.25rem; font-family: 'Cormorant Garamond', serif; font-weight: 600; margin-bottom: 1.5rem; } .step-header .step-circle { display: flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: 9999px; background-color: #1A1A1A; color: white; font-size: 0.875rem; font-weight: bold; font-family: 'Inter', sans-serif; }
.toastify { padding: 12px 20px; font-size: 14px; font-weight: 500; border-radius: 8px; box-shadow: 0 3px 6px -1px rgba(0,0,0,.12), 0 10px 36px -4px rgba(51,45,45,.25); }
</style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">

<!-- HEADER -->
<section class="relative h-48 md:h-56 bg-cover bg-center" style="background-image: url('images/IMG_63900471.jpg');">
    <div class="absolute inset-0 bg-black/40"></div>
    <header class="absolute inset-x-0 top-0 z-20"><div class="container mx-auto px-4 sm:px-6 lg:px-8"><div class="flex items-center justify-center h-20"><a href="/home"><img src="<?=$logo_directory?>" alt="<?=$site_name?> Logo" class="h-10 w-auto filter brightness-0 invert"></a></div></div></header>
    <div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4"><h1 class="text-5xl md:text-6xl font-serif font-semibold pt-12">Checkout</h1></div>
</section>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
<div class="grid grid-cols-1 lg:grid-cols-2 lg:gap-x-16 xl:gap-x-24">

<!-- Left Column: Customer Information & Shipping -->
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
            <!-- Phone Number is here, as requested -->
            <div><label for="phone-number" class="block text-sm font-medium text-brand-gray mb-1">Phone Number</label><input type="tel" id="phone-number" name="phone-number" class="form-input-sleek" placeholder="e.g. 08012345678" required></div>
            <div><label for="address" class="block text-sm font-medium text-brand-gray mb-1">Address</label><input type="text" id="address" name="address" class="form-input-sleek" placeholder="123 Main Street" required></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-6">
                <div><label for="city" class="block text-sm font-medium text-brand-gray mb-1">City</label><input type="text" id="city" name="city" class="form-input-sleek" required></div>
                <div><label for="state" class="block text-sm font-medium text-brand-gray mb-1">State / Province</label><input type="text" id="state" name="state" class="form-input-sleek" required></div>
                <!-- <div><label for="zip" class="block text-sm font-medium text-brand-gray mb-1">ZIP / Postal Code</label><input type="text" id="zip" name="zip" class="form-input-sleek" required></div> -->
            </div>
            <div><label for="country" class="block text-sm font-medium text-brand-gray mb-1">Country</label><input type="text" id="country" name="country" class="form-input-sleek" value="Nigeria" required></div>
             <div>
                <label for="shipping-location" class="block text-sm font-medium text-brand-gray mb-1">Shipping Location</label>
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

<!-- Right Column: Order Summary -->
<div class="w-full mt-16 lg:mt-0">
<div class="bg-white p-8 border border-gray-200/70 lg:sticky lg:top-28">
    <h2 class="text-2xl font-serif font-semibold mb-6">Order Summary</h2>
    <div id="summary-items-container" class="space-y-5">
        <!-- Products will be loaded here by JavaScript -->
    </div>
    <div class="mt-6 pt-6 border-t border-gray-200 space-y-4">
        <div class="flex items-center gap-4">
            <input type="text" id="discount-code-input" placeholder="Discount code" class="form-input-sleek flex-1">
            <button id="apply-discount-btn" class="bg-gray-200/80 text-brand-text text-sm font-semibold px-4 py-2 hover:bg-gray-300 transition-colors">Apply</button>
        </div>
        <p id="discount-feedback" class="text-xs text-brand-red h-4"></p>
    </div>
    <div class="mt-6 pt-6 border-t border-gray-200 space-y-3 text-sm">
        <div class="flex justify-between"><span class="text-brand-gray">Subtotal</span><span id="summary-subtotal" class="font-medium">₦0.00</span></div>
        <div class="flex justify-between"><span class="text-brand-gray">Discount</span><span id="summary-discount" class="font-medium">- ₦0.00</span></div>
        <div class="flex justify-between"><span class="text-brand-gray">Shipping</span><span id="summary-shipping" class="font-medium">₦0.00</span></div>
        <div class="flex justify-between text-lg font-semibold pt-4 mt-2 border-t border-gray-200"><span>Total</span><span id="summary-total">₦0.00</span></div>
    </div>
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-serif font-semibold mb-4">Payment</h3>
        <p class="text-sm text-brand-gray mb-4">All transactions are secure and encrypted. We do not store your card details.</p>
        <button type="button" id="pay-button" class="w-full bg-brand-text text-white py-4 text-center font-semibold hover:bg-gray-800 transition-colors duration-300 flex items-center justify-center gap-3">
            <i data-feather="lock" class="w-4 h-4"></i><span>Pay Now</span>
        </button>
    </div>
</div>
</div>
</div>
</main>

<!-- FOOTER -->
<footer class="bg-white border-t border-gray-200 mt-16"><div class="container mx-auto px-6 py-8 text-center text-sm text-brand-gray"><p>© <?=date('Y')?> <?=$site_name?>. All Rights Reserved.</p></div></footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
    feather.replace();

    // --- STATE ---
    let cartSubtotal = <?= (float)$subtotal ?>;
    let shippingFee = 0;
    let discountAmount = 0;
    
    // --- SELECTORS ---
    const selectors = {
        payButton: document.getElementById('pay-button'),
        checkoutForm: document.getElementById('checkout-form'),
        summaryItemsContainer: document.getElementById('summary-items-container'),
        shippingLocation: document.getElementById('shipping-location'),
        discountCodeInput: document.getElementById('discount-code-input'),
        applyDiscountBtn: document.getElementById('apply-discount-btn'),
        discountFeedback: document.getElementById('discount-feedback'),
        summarySubtotal: document.getElementById('summary-subtotal'),
        summaryDiscount: document.getElementById('summary-discount'),
        summaryShipping: document.getElementById('summary-shipping'),
        summaryTotal: document.getElementById('summary-total')
    };
    
    // --- HELPER FUNCTIONS ---
    const showToast = (text, type = "success") => { const background = type === "success" ? "linear-gradient(to right, #00b09b, #96c93d)" : "linear-gradient(to right, #ff5f6d, #ffc371)"; Toastify({ text, duration: 2500, newWindow: true, close: true, gravity: "top", position: "right", stopOnFocus: true, style: { background, "font-size": "14px" } }).showToast(); };
    const formatCurrency = (amount) => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);

    // --- MAIN FUNCTIONS ---
    const updateOrderSummary = () => {
        const grandTotal = (cartSubtotal - discountAmount) + shippingFee;
        selectors.summarySubtotal.textContent = formatCurrency(cartSubtotal);
        selectors.summaryDiscount.textContent = `- ${formatCurrency(discountAmount)}`;
        selectors.summaryShipping.textContent = formatCurrency(shippingFee);
        selectors.summaryTotal.textContent = formatCurrency(grandTotal > 0 ? grandTotal : 0);
    };

    const loadCartItems = () => {
        const itemsHtml = `<?php foreach ($cartItems as $item) {
            $options = '';
            if (!empty($item['color_name'])) $options .= $item['color_name'];
            elseif (!empty($item['custom_color_name'])) $options .= 'Custom: ' . $item['custom_color_name'];
            if (!empty($item['size_name'])) $options .= ($options ? ' / ' : '') . $item['size_name'];
            elseif (!empty($item['custom_size_details']) && $item['custom_size_details'] !== '{}') $options .= ($options ? ' / ' : '') . 'Custom Size';
            echo '<div class="flex justify-between items-start gap-4"><div class="relative flex-shrink-0"><img src="' . htmlspecialchars($item['product_image']) . '" alt="' . htmlspecialchars($item['product_name']) . '" class="w-20 h-24 object-cover rounded-md border border-gray-200"><span class="absolute -top-2 -right-2 bg-brand-gray text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold">' . $item['quantity'] . '</span></div><div class="flex-1"><h3 class="font-semibold text-sm">' . htmlspecialchars($item['product_name']) . '</h3><p class="text-xs text-brand-gray">' . htmlspecialchars($options) . '</p></div><p class="font-medium text-sm">' . '₦' . number_format($item['total_price'], 2) . '</p></div>';
        } ?>`;
        selectors.summaryItemsContainer.innerHTML = itemsHtml;
    };

    const applyDiscount = async () => {
        const code = selectors.discountCodeInput.value.trim();
        if (!code) return;

        selectors.applyDiscountBtn.disabled = true;
        try {
            const response = await fetch('apply-discount', { // Assumes you have an apply-discount endpoint
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ discountCode: code, subtotal: cartSubtotal })
            });
            const result = await response.json();
            
            selectors.discountFeedback.textContent = result.message || '';
            if (result.status === 'success') {
                discountAmount = parseFloat(result.discountAmount);
                selectors.discountFeedback.classList.remove('text-red-600');
                selectors.discountFeedback.classList.add('text-green-600');
                showToast('Discount applied successfully!');
            } else {
                discountAmount = 0;
                selectors.discountFeedback.classList.add('text-red-600');
                selectors.discountFeedback.classList.remove('text-green-600');
            }
            updateOrderSummary();
        } catch (error) {
            console.error('Discount Error:', error);
            showToast('Could not apply discount code.', 'error');
        } finally {
            selectors.applyDiscountBtn.disabled = false;
        }
    };
    
    const placeOrderAndPay = async () => {
        if (!selectors.checkoutForm.checkValidity()) {
            selectors.checkoutForm.reportValidity();
            showToast('Please fill in all required fields.', 'error');
            return;
        }

        const btn = selectors.payButton;
        btn.disabled = true;
        btn.innerHTML = `<i data-feather="loader" class="w-5 h-5 animate-spin"></i> Processing...`;
        feather.replace();

        // **IMPORTANT**: This object matches the structure your place-order.php script expects.
        const formData = {
            email: document.getElementById('email').value,
            shippingAddress: {
                fullName: document.getElementById('full-name').value,
                phoneNumber: document.getElementById('phone-number').value, // Phone number is nested here
                address: document.getElementById('address').value,
                city: document.getElementById('city').value,
                state: document.getElementById('state').value,
                // zip: document.getElementById('zip').value,
                country: document.getElementById('country').value,
            },
            shippingId: selectors.shippingLocation.value,
            discountCode: selectors.discountCodeInput.value.trim()
        };

        try {
            const response = await fetch('place-order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                payWithPaystack(result.email, result.amountKobo, result.reference);
            } else {
                throw new Error(result.message || 'Failed to place order.');
            }
        } catch (error) {
            console.error('Order Placement Error:', error);
            showToast(error.message, 'error');
            btn.disabled = false;
            btn.innerHTML = `<i data-feather="lock" class="w-4 h-4"></i><span>Pay Now</span>`;
            feather.replace();
        }
    };

    const payWithPaystack = (email, amountKobo, reference) => {
        let handler = PaystackPop.setup({
            key: 'pk_test_ded4f29b2932a767eccd8c1a145355bb09e0f34a', // IMPORTANT: Replace with your LIVE Paystack Public Key
            email: email,
            amount: amountKobo,
            currency: 'NGN',
            ref: reference,
            callback: function(response) {
                // Redirect to your server for verification
                window.location.href = 'verify-payment?reference=' + response.reference;
            },
            onClose: function() {
                showToast('Payment window closed.', 'error');
                const btn = selectors.payButton;
                btn.disabled = false;
                btn.innerHTML = `<i data-feather="lock" class="w-4 h-4"></i><span>Pay Now</span>`;
                feather.replace();
            },
        });
        handler.openIframe();
    };

    // --- EVENT LISTENERS ---
    selectors.shippingLocation.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        shippingFee = parseFloat(selectedOption.dataset.fee) || 0;
        updateOrderSummary();
    });

    selectors.applyDiscountBtn.addEventListener('click', applyDiscount);
    selectors.payButton.addEventListener('click', placeOrderAndPay);
    
    // --- INITIAL LOAD ---
    loadCartItems();
    updateOrderSummary();
});
</script>
</body>
</html>