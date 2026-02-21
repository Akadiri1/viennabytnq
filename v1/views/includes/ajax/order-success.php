<?php
// PAGE SETUP & SECURITY
session_start();
// This check ensures the page is only accessed after a successful payment
if (!isset($_GET['ref']) || !isset($_SESSION['last_order_ref']) || $_GET['ref'] !== $_SESSION['last_order_ref']) {
    header("Location: /shop");
    exit;
}

$orderRef = $_SESSION['last_order_ref'];
$whatsappMessage = $_SESSION['whatsapp_notification_message'] ?? null;
$whatsappNumber = "+2349061371973"; // Your business WhatsApp number

$encodedMessage = $whatsappMessage ? urlencode($whatsappMessage) : null;

// Fetch order and customer details for the customer-facing part of the page
// Assuming $conn is your PDO database connection object.
$stmt = $conn->prepare("
    SELECT o.order_number, o.shipping_address, c.email
    FROM orders o JOIN customers c ON o.customer_id = c.id
    WHERE o.payment_reference = ? AND o.order_status = 'paid'
");
$stmt->execute([$orderRef]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// If the order is not found or not paid, redirect to the shop page
if (!$order) {
    header("Location: /shop");
    exit;
}

$shippingDetails = json_decode($order['shipping_address'], true);
$customerName = $shippingDetails['fullName'] ?? 'Valued Customer';
$customerEmail = $order['email'];

// Unset the session variables to prevent re-trigger on refresh
unset($_SESSION['last_order_ref']);
unset($_SESSION['whatsapp_notification_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory ?? 'path/to/your/logo.png'?>">
    <title>Order Confirmed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', 'green-600': '#25D366' }, fontFamily: { 'sans': ['Lato', 'ui-sans-serif', 'system-ui'], 'serif': ['Playfair Display', 'serif'], } } } };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
<main class="bg-brand-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm p-8 md:p-12 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <i data-feather="check" class="w-8 h-8 text-green-600"></i>
            </div>
            <h1 class="mt-6 text-3xl md:text-4xl font-serif font-semibold text-brand-text">Thank you for your order!</h1>
            
            <?php if ($encodedMessage): ?>
                <div class="mt-6 border-l-4 border-green-600 bg-green-50 p-4 rounded-lg text-left">
                    <div class="flex items-center">
                        <i data-feather="alert-circle" class="h-5 w-5 text-green-600 mr-2"></i>
                        <h3 class="text-lg font-semibold text-green-700">Action Required: Confirm Your Order</h3>
                    </div>
                    <p class="mt-2 text-sm text-green-600">
                        For us to process your order, you must click the button below to send us the order details on WhatsApp. This step is compulsory.
                    </p>
                </div>
            <?php endif; ?>

            <p class="mt-4 text-brand-gray">
                Hi <?= htmlspecialchars(explode(' ', $customerName)[0]) ?>, your order has been confirmed. A confirmation email with the full details has been sent to <span class="font-medium text-brand-text"><?= htmlspecialchars($customerEmail) ?></span>.
            </p>
            <div class="mt-8 bg-brand-bg border border-gray-200 rounded-lg p-4">
                <p class="text-sm text-brand-gray">Your Order Number is:</p>
                <p class="text-xl font-bold font-mono tracking-wider text-brand-text mt-1"><?= htmlspecialchars($order['order_number']) ?></p>
            </div>
            
            <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                <?php if ($encodedMessage): ?>
                    <a href="https://wa.me/<?= htmlspecialchars($whatsappNumber) ?>?text=<?= htmlspecialchars($encodedMessage) ?>" **target="_blank"** class="w-full sm:w-auto bg-green-600 text-white py-3 px-6 rounded-md text-sm font-semibold hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                        <i data-feather="message-circle" class="w-4 h-4"></i>
                        Send Order to WhatsApp
                    </a>
                <?php endif; ?>
                <a href="/shop" class="w-full sm:w-auto bg-brand-text text-white py-3 px-6 rounded-md text-sm font-semibold hover:bg-gray-800 transition-colors">
                    Continue Shopping
                </a>
                <a href="/invoice?order_number=<?= urlencode($order['order_number']) ?>" **target="_blank"** class="w-full sm:w-auto bg-transparent text-brand-text border border-gray-300 py-3 px-6 rounded-md text-sm font-semibold hover:bg-gray-100 transition-colors">
                    Download Invoice
                </a>
            </div>
        </div>
    </div>
</main>
<footer class="bg-white border-t border-gray-200">
    <div class="p-6 text-center">
        <p class="text-xs text-brand-gray">© <?=date('Y')?> <?=$site_name ?? 'Your Site Name'?>. All Rights Reserved.</p>
    </div>
</footer>
<script>
    feather.replace();
</script>
</body>
</html>