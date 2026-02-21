<?php
// Always start the session at the beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Include your database connection
//require_once 'includes/db_connection.php';

// --- SECURITY CHECK ---
// If no user is logged in, they cannot access this page.
// Redirect them to the authentication page to sign in.
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth");
    exit();
}

$site_name = 'Vienna by TNQ';
$user_id = $_SESSION['user_id'];

// --- FETCH CART ITEMS FOR THE LOGGED-IN USER ---
// This SQL query is tailored to your 'cart_items' table structure.
$sql = "
    SELECT
        ci.id AS cart_item_id,
        ci.quantity,
        ci.total_price,
        ci.color_name,
        ci.custom_color_name,
        ci.size_name,
        p.name AS product_name,
        p.image_one AS product_image,
        pv.variant_name
    FROM cart_items AS ci
    JOIN panel_products AS p ON ci.product_id = p.id
    LEFT JOIN product_price_variants AS pv ON ci.price_variant_id = pv.id
    WHERE ci.user_id = ?
    ORDER BY ci.added_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate the grand total (subtotal) from all items
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['total_price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Account - <?= htmlspecialchars($site_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: { "brand-bg": "#F9F6F2", "brand-text": "#1A1A1A", "brand-gray": "#6B7280" },
            fontFamily: { sans: ["Lato", "sans-serif"], serif: ["Playfair Display", "serif"] },
          },
        },
      };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
    
    <!-- Include your standard site header -->
    <header class="bg-white border-b border-gray-200/60 sticky top-0 z-40">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8"><div class="flex items-center justify-between h-16"><a href="/home"><div class="text-2xl font-serif font-bold tracking-widest"><?= htmlspecialchars($site_name) ?></div></a><a href="/log-out" class="text-sm font-medium text-brand-gray hover:text-brand-text">Logout</a></div></div>
    </header>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h1 class="text-4xl font-serif font-bold">My Account</h1>
        <p class="mt-4 text-lg text-brand-gray">
            Welcome back, <span class="font-semibold text-brand-text"><?= htmlspecialchars($_SESSION['user_name']) ?></span>!
        </p>

        <div class="mt-12">
            <h2 class="text-3xl font-serif font-semibold border-b border-gray-300 pb-4">Your Shopping Cart</h2>
            
            <?php if (empty($cart_items)): ?>
                <!-- This block shows if the cart is empty -->
                <div class="text-center py-16 px-6 bg-white rounded-lg mt-6">
                    <i data-feather="shopping-bag" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                    <h3 class="text-xl font-semibold text-brand-text">Your cart is empty.</h3>
                    <p class="text-brand-gray mt-2">Looks like you haven't added anything to your cart yet.</p>
                    <a href="/shop" class="inline-block mt-6 bg-brand-text text-white py-2 px-6 font-semibold hover:bg-gray-800 transition-colors rounded-md">
                        Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <!-- This block shows if there are items in the cart -->
                <div class="mt-6 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead>
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-brand-text sm:pl-0">Product</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-brand-text">Unit Price</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-brand-text">Quantity</th>
                                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-brand-text">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td class="py-4 pl-4 pr-3 text-sm sm:pl-6">
                                            <div class="flex items-center">
                                                <div class="h-24 w-20 flex-shrink-0">
                                                    <img class="h-24 w-20 rounded-md object-cover" src="/<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="font-medium text-brand-text"><?= htmlspecialchars($item['product_name']) ?></div>
                                                    <div class="mt-1 text-brand-gray text-xs">
                                                        <?php 
                                                            // Build a clean string of the selected options
                                                            $details = [];
                                                            if (!empty($item['color_name'])) $details[] = htmlspecialchars($item['color_name']);
                                                            if (!empty($item['custom_color_name'])) $details[] = 'Custom: ' . htmlspecialchars($item['custom_color_name']);
                                                            if (!empty($item['size_name'])) $details[] = htmlspecialchars($item['size_name']);
                                                            if (!empty($item['variant_name'])) $details[] = htmlspecialchars($item['variant_name']);
                                                            echo implode(' / ', $details);
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-brand-gray">
                                            <?php 
                                                // Calculate unit price since it's not stored directly
                                                $unit_price = $item['quantity'] > 0 ? $item['total_price'] / $item['quantity'] : 0;
                                                echo '₦' . number_format($unit_price, 2);
                                            ?>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-brand-gray"><?= htmlspecialchars($item['quantity']) ?></td>
                                        <td class="px-3 py-4 text-sm text-brand-text font-medium text-right">₦<?= number_format($item['total_price'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th scope="row" colspan="3" class="pt-4 pb-2 pl-4 pr-3 text-right text-sm font-semibold text-brand-text sm:pl-0">Subtotal</th>
                                        <td class="pt-4 pb-2 pl-3 pr-4 text-right text-sm font-semibold text-brand-text sm:pr-6">₦<?= number_format($subtotal, 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="mt-8 flex justify-end gap-4">
                    <a href="/shop" class="bg-transparent text-brand-text border border-brand-text py-2 px-6 font-semibold hover:bg-gray-100 transition-colors rounded-md">Continue Shopping</a>
                    <a href="/checkout" class="bg-brand-text text-white py-2 px-6 font-semibold hover:bg-gray-800 transition-colors rounded-md">Proceed to Checkout</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Renders the feather icons (like the shopping bag)
        feather.replace();
    </script>
</body>
</html>