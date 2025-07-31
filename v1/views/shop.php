<?php 
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$panelProducts = array_slice(selectContent($conn, "panel_products", ['visibility' => 'show'], "ORDER BY id DESC"), 0, 50);
$productBreadcrumb = selectContent($conn, "product_breadcrumb", ['visibility' => 'show']);
?>

<!DOCTYPE html>
<html lang="en">    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
    <link rel="apple-touch-icon-precomposed" type="image/png" sizes="152x152" href="<?=$logo_directory?>">
  
    <title><?=$site_name?> | Shop</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      // Custom configuration for Tailwind
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'brand-bg': '#F9F6F2',
              'brand-text': '#1A1A1A', // Darker text for more contrast
              'brand-gray': '#6B7280',
              'brand-red': '#EF4444',
            },
            fontFamily: {
              // NEW: Added a professional serif font for headings
              'sans': ['Inter', 'ui-sans-serif', 'system-ui'],
              'serif': ['Cormorant Garamond', 'serif'],
            }
          }
        }
      }
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="/alljs/pagination.js"></script>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
<!-- SIDEBAR MENU -->
<div id="sidebar" class="fixed inset-0 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out" aria-labelledby="sidebar-title">
    <div id="sidebar-overlay" class="absolute inset-0 bg-black/40"></div>
    <div class="relative w-80 h-full bg-brand-bg shadow-2xl flex flex-col">
        <div class="p-6 flex justify-between items-center border-b border-gray-200">
            <h2 id="sidebar-title" class="text-2xl font-serif font-semibold">Menu</h2>
            <button id="close-sidebar-btn" class="p-2 text-brand-gray hover:text-brand-text">
                <i data-feather="x" class="h-6 w-6"></i>
            </button>
        </div>
        <nav class="flex-grow p-6">
            <ul class="space-y-4">
                <li><a href="/home" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 group transition-colors duration-200"><i data-feather="home" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Home</span></a></li>
                <li><a href="/shop" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 group transition-colors duration-200"><i data-feather="shopping-bag" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">Products</span></a></li>
                <li><a href="/about" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 group transition-colors duration-200"><i data-feather="info" class="w-5 h-5 text-brand-gray mr-4"></i><span class="tracking-wide">About Us</span></a></li>
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
            <div class="flex-1 flex items-center justify-end space-x-4"><a href="/view-cart" class="p-2 text-brand-text hover:text-brand-gray relative"><i data-feather="shopping-bag" class="h-5 w-5"></i><span class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-brand-red text-white text-xs flex items-center justify-center font-bold" style="font-size: 8px;"></span></a></div>
        </div>
    </div>
</header>

<!-- HERO/BREADCRUMB SECTION -->
<section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($productBreadcrumb[0]['input_image'] ?? '') ?>');">
    <div class="absolute inset-0 bg-black/30"></div>
    <div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4">
        <nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center"><a href="/home" class="hover:underline">Home</a><i data-feather="chevron-right" class="h-4 w-4 mx-2"></i></li>
                <li><span>Shop</span></li>
            </ol>
        </nav>
        <h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4"><?= htmlspecialchars($productBreadcrumb[0]['input_title'] ?? 'Our Collection') ?></h1>
    </div>
</section>

<!-- MAIN CONTENT -->
<main class="bg-brand-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-6">
            <div class="relative w-full sm:w-auto">
                <input type="search" id="product-search" placeholder="Search products..." class="w-full sm:w-64 bg-transparent border-b border-gray-400 py-2 pl-9 pr-2 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text placeholder-brand-gray">
                <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none"><i data-feather="search" class="h-4 w-4 text-brand-gray"></i></div>
            </div>
            <div class="flex items-center space-x-3">
                <label for="sort-filter-dropdown" class="text-sm font-medium text-brand-gray whitespace-nowrap">Sort by:</label>
                <select id="sort-filter-dropdown" class="bg-transparent border-b border-gray-400 py-2 px-1 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text">
                    <option value="featured">Featured</option><option value="price-asc">Price: Low to High</option><option value="price-desc">Price: High to Low</option>
                </select>
            </div>
        </div>

        <!-- PRODUCT GRID -->
        <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-12">
            
            <?php foreach ($panelProducts as $product): ?>
                <?php
                    // **FIX 1**: Check the stock quantity. A product is sold out if its quantity is 0 or less.
                    $isSoldOut = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0);

                    // These queries can stay, but we will conditionally hide their output.
                    $colorStmt = $conn->prepare("SELECT c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id = ?");
                    $colorStmt->execute([$product['id']]);
                    $colors = $colorStmt->fetchAll(PDO::FETCH_COLUMN);

                    $sizeStmt = $conn->prepare("SELECT s.name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id = ?");
                    $sizeStmt->execute([$product['id']]);
                    $sizes = $sizeStmt->fetchAll(PDO::FETCH_COLUMN);
                ?>
                <div class="product-card" data-price="₦<?= htmlspecialchars($product['price']) ?>">
                    <!-- **FIX 2**: Make the link conditional. If sold out, the `href` attribute is removed. Also add a 'not-allowed' cursor. -->
                    <a <?php if (!$isSoldOut): ?>href="shopdetail?id=<?= urlencode($product['id']) ?>"<?php endif; ?> class="group block <?= $isSoldOut ? 'cursor-not-allowed' : '' ?>">
                        <div class="relative w-full overflow-hidden">
                            <div class="aspect-[9/16]">
                                <!-- **FIX 3**: Disable the image hover effect if the product is sold out. -->
                                <img src="<?= htmlspecialchars($product['image_one']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 <?= $isSoldOut ? 'opacity-100' : 'opacity-100 group-hover:opacity-0' ?>">
                                <img src="<?= htmlspecialchars($product['image_two']) ?>" alt="<?= htmlspecialchars($product['name']) ?> Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 <?= $isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100' ?>">
                            </div>

                            <!-- **FIX 4**: Add the "Sold Out" overlay badge only when the item is sold out. -->
                            <?php if ($isSoldOut): ?>
                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                    <span class="bg-white text-brand-text text-xs font-semibold tracking-wider uppercase px-4 py-2">Sold Out</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="mt-1 text-sm text-brand-gray">₦<?= number_format($product['price'], 2) ?></p>
                            
                            <!-- **FIX 5**: Hide the color and size swatches if the product is sold out. -->
                            <?php if (!$isSoldOut): ?>
                                <div class="flex flex-col items-center mt-2">
                                    <div class="flex justify-center space-x-2 mb-2">
                                        <?php foreach ($colors as $color): ?>
                                            <span class="block w-4 h-4 rounded-full border border-gray-300" style="background-color: <?= htmlspecialchars($color) ?>;"></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="flex justify-center space-x-2">
                                        <?php foreach ($sizes as $size): ?>
                                            <span class="px-2 py-1 rounded text-xs text-brand-text"><?= htmlspecialchars($size) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <nav id="pagination" class="flex items-center justify-center pt-24 space-x-2" aria-label="Pagination">
            <!-- Pagination buttons will be rendered here by JS -->
        </nav>
    </div>
</main>

<!-- FOOTER -->
<footer class="bg-white border-t border-gray-200">
    <div class="bg-gray-50 text-center py-4 text-brand-gray text-xs">
        <p>© <?=date('Y')?> <?=$site_name?>. All Rights Reserved.</p>
    </div>
</footer>

<script>
    // Your existing JavaScript for sidebar, search, pagination, etc. can remain here.
    // No changes are needed in the JavaScript for this "Sold Out" feature.
</script>

</body>
</html>