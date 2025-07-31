<?php
// This page should only be accessible right after a successful payment.
if (!isset($_GET['ref']) || !isset($_SESSION['last_order_ref']) || $_GET['ref'] !== $_SESSION['last_order_ref']) {
    header("Location: /shop");
    exit;
}

$orderRef = $_SESSION['last_order_ref'];

// **FIX**: Updated SQL to match your database schema
// We JOIN with the customers table to get the email.
$stmt = $conn->prepare(
    "SELECT o.order_number, o.shipping_address, c.email
     FROM orders o
     JOIN customers c ON o.customer_id = c.id
     WHERE o.payment_reference = ? AND o.order_status = 'paid'" // Assuming 'paid' is the status
);
$stmt->execute([$orderRef]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: /shop");
    exit;
}

// **FIX**: Decode the JSON shipping address to get customer details
$shippingDetails = json_decode($order['shipping_address'], true);
$customerName = $shippingDetails['fullName'] ?? 'Valued Customer';
$customerEmail = $order['email'];

// Unset the session variable so this page can't be refreshed.
unset($_SESSION['last_order_ref']);

// Fetch breadcrumb image from DB
$productBreadcrumb = selectContent($conn, "product_breadcrumb", ['visibility' => 'show']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
    <title><?=$site_name?> | Order Confirmed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', 'brand-red': '#EF4444', }, fontFamily: { 'sans': ['Inter', 'ui-sans-serif', 'system-ui'], 'serif': ['Cormorant Garamond', 'serif'], } } } };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
<!-- MAIN SUCCESS CONTENT -->
<main class="bg-brand-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm p-8 md:p-12 text-center">
            
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <i data-feather="check" class="w-8 h-8 text-green-600"></i>
            </div>

            <h1 class="mt-6 text-3xl md:text-4xl font-serif font-semibold text-brand-text">Thank you for your order!</h1>
            
            <p class="mt-4 text-brand-gray">
                Hi <?= htmlspecialchars(explode(' ', $customerName)[0]) ?>, your order has been confirmed. A confirmation email with the full details has been sent to <span class="font-medium text-brand-text"><?= htmlspecialchars($customerEmail) ?></span>.
            </p>

            <div class="mt-8 bg-brand-bg border border-gray-200 rounded-lg p-4">
                <p class="text-sm text-brand-gray">Your Order Number is:</p>
                <p class="text-xl font-bold font-mono tracking-wider text-brand-text mt-1"><?= htmlspecialchars($order['order_number']) ?></p>
            </div>
            
            <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                <a href="/shop" class="w-full sm:w-auto bg-brand-text text-white py-3 px-6 rounded-md text-sm font-semibold hover:bg-gray-800 transition-colors">
                    Continue Shopping
                </a>
                
                <!-- **FIX**: Link now uses `order_number` as the reference -->
                <a href="/invoice?order_number=<?= urlencode($order['order_number']) ?>" target="_blank" class="w-full sm:w-auto bg-transparent text-brand-text border border-gray-300 py-3 px-6 rounded-md text-sm font-semibold hover:bg-gray-100 transition-colors">
                    Download Invoice
                </a>
            </div>

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
    feather.replace();
</script>

</body>
</html>