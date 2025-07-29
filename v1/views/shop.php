<?php 
$panelProducts = array_slice(selectContent($conn, "panel_products", ['visibility' => 'show']), 0, 50);
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
     <!-- <audio src="images/shopping.mp3" autoplay hidden></audio> -->
</head>
<body class="bg-brand-bg font-sans text-brand-text">
<!-- NEW: REDESIGNED SIDEBAR MENU -->
<div id="sidebar" class="fixed inset-0 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out" aria-labelledby="sidebar-title">
    <!-- Overlay -->
    <div id="sidebar-overlay" class="absolute inset-0 bg-black/40"></div>
    
    <!-- Sidebar Panel -->
    <div class="relative w-80 h-full bg-brand-bg shadow-2xl flex flex-col">
        <!-- Sidebar Header -->
        <div class="p-6 flex justify-between items-center border-b border-gray-200">
            <h2 id="sidebar-title" class="text-2xl font-serif font-semibold">Menu</h2>
            <button id="close-sidebar-btn" class="p-2 text-brand-gray hover:text-brand-text">
                <i data-feather="x" class="h-6 w-6"></i>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <nav class="flex-grow p-6">
            <ul class="space-y-4">
                <li>
                    <a href="/home" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 group transition-colors duration-200">
                        <i data-feather="home" class="w-5 h-5 text-brand-gray mr-4"></i>
                        <span class="tracking-wide">Home</span>
                    </a>
                </li>
                <li>
                    <a href="/shop" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 group transition-colors duration-200">
                        <i data-feather="shopping-bag" class="w-5 h-5 text-brand-gray mr-4"></i>
                        <span class="tracking-wide">Products</span>
                    </a>
                </li>
                <li>
                    <a href="/about" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 group transition-colors duration-200">
                        <i data-feather="info" class="w-5 h-5 text-brand-gray mr-4"></i>
                        <span class="tracking-wide">About Us</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-6 border-t border-gray-200">
            <p class="text-xs text-brand-gray text-center">© <?=date('Y')?> <?=$site_name?></p>
        </div>
    </div>
</div>

    <!-- HEADER - Glassmorphic -->
    <header class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-1 flex justify-start">
                    <button id="open-sidebar-btn" class="p-2 text-brand-text hover:text-brand-gray">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                </div>
                <div class="flex-shrink-0 text-center">
                    <a href="/home">
                        <div class="text-2xl font-serif font-bold tracking-widest"><?=$site_name?></div>
                    </a>
                </div>
                <!-- MODIFIED: Removed the search icon from the header -->
                <div class="flex-1 flex items-center justify-end space-x-4">
                    <a href="#" class="p-2 text-brand-text hover:text-brand-gray relative">
                        <i data-feather="shopping-bag" class="h-5 w-5"></i>
                        <span class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-brand-red text-white text-xs flex items-center justify-center font-bold" style="font-size: 8px;">1</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- NEW: HERO/BREADCRUMB IMAGE SECTION -->
    <section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?= $productBreadcrumb[0]['input_image'] ?>');">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4">
            <nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb">
                <ol class="list-none p-0 inline-flex">
                    <li class="flex items-center">
                        <a href="/home" class="hover:underline">Home</a>
                        <i data-feather="chevron-right" class="h-4 w-4 mx-2"></i>
                    </li>
                    <li>
                        <span>Shop</span>
                    </li>
                </ol>
            </nav>
            <h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4"><?= $productBreadcrumb[0]['input_title'] ?></h1>
        </div>
    </section>

    <!-- MAIN CONTENT -->
    <main class="bg-brand-bg">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
            
            <!-- MODIFIED: Top Bar now includes Search and Sort -->
            <div class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-6">
                <!-- NEW: Search Input -->
                <div class="relative w-full sm:w-auto">
                    <input type="search" id="product-search" placeholder="Search products..." class="w-full sm:w-64 bg-transparent border-b border-gray-400 py-2 pl-9 pr-2 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text placeholder-brand-gray">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                        <i data-feather="search" class="h-4 w-4 text-brand-gray"></i>
                    </div>
                </div>

                <!-- Sort by Dropdown -->
                <div class="flex items-center space-x-3">
                    <label for="sort-filter-dropdown" class="text-sm font-medium text-brand-gray whitespace-nowrap">Sort by:</label>
                    <select id="sort-filter-dropdown" class="bg-transparent border-b border-gray-400 py-2 px-1 text-sm font-medium focus:outline-none focus:ring-0 focus:border-brand-text">
                        <option value="featured">Featured</option>
                        <option value="price-asc">Price: Low to High</option>
                        <option value="price-desc">Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- PRODUCT GRID - REDESIGNED CARDS -->
            <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-12">
                
                <?php foreach ($panelProducts as $product): ?>
                    <?php
                    // Query colors for this product
                    $colorStmt = $conn->prepare("SELECT c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id = ?");
                    $colorStmt->execute([$product['id']]);
                    $colors = $colorStmt->fetchAll(PDO::FETCH_COLUMN);

                    // Query sizes for this product
                    $sizeStmt = $conn->prepare("SELECT s.name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id = ?");
                    $sizeStmt->execute([$product['id']]);
                    $sizes = $sizeStmt->fetchAll(PDO::FETCH_COLUMN);
                    ?>
                    <div class="product-card" data-price="<?= htmlspecialchars($product['price']) ?>">
                        <a href="shopdetail?id=<?= urlencode($product['id']) ?>&name=<?= urlencode($product['name']) ?>&t=<?= time() ?>" class="group block">
                            <div class="relative w-full overflow-hidden">
                                <div class="aspect-[9/16]">
                                    <img src="<?= htmlspecialchars($product['image_one']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0">
                                    <img src="<?= htmlspecialchars($product['image_two']) ?>" alt="<?= htmlspecialchars($product['name']) ?> Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100">
                                </div>
                            </div>
                            <div class="pt-4 text-center">
                                <h3 class="text-base font-medium text-brand-text"><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="mt-1 text-sm text-brand-gray">$<?= number_format($product['price'], 2) ?></p>
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
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- AJAX PAGINATION -->
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


    <!-- MODIFIED: JAVASCRIPT LOGIC now includes search functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- Elements ---
            const searchInput = document.getElementById('product-search');
            const sortDropdown = document.getElementById('sort-filter-dropdown');
            const productGrid = document.getElementById('product-grid');
            const paginationNav = document.getElementById('pagination');

            // --- AJAX Pagination State ---
            let currentPage = 1;
            let totalPages = 1;
            let lastSearch = '';
            let lastSort = 'featured';

            // --- Fetch Products via AJAX ---
            function fetchProducts(page = 1, search = '', sort = 'featured') {
                fetch(`pagination?page=${page}&search=${encodeURIComponent(search)}&sort=${encodeURIComponent(sort)}`)
                    .then(res => res.json())
                    .then(data => {
                        console.log('AJAX pagination response:', data); // DEBUG: See backend response
                        productGrid.innerHTML = data.productsHtml;
                        renderPagination(data.currentPage, data.totalPages);
                        feather.replace();
                    });
            }

            // --- Render Pagination Buttons ---
            function renderPagination(page, total) {
                currentPage = page;
                totalPages = total;
                let html = '';
                // Previous button
                html += `<a href="#" class="w-10 h-10 flex items-center justify-center rounded-full text-brand-gray bg-gray-200/60 ${page === 1 ? 'cursor-not-allowed opacity-60' : 'hover:bg-gray-200/60 hover:text-brand-text'}" data-page="${page-1}" ${page === 1 ? 'tabindex="-1" aria-disabled="true"' : ''}><span class="sr-only">Previous</span><i data-feather="chevron-left" class="h-5 w-5"></i></a>`;
                // Page numbers (show up to 5 pages)
                let start = Math.max(1, page - 2);
                let end = Math.min(total, page + 2);
                if (start > 1) html += `<span class="w-10 h-10 flex items-center justify-center text-sm font-medium text-brand-gray">...</span>`;
                for (let i = start; i <= end; i++) {
                    html += `<a href="#" class="w-10 h-10 flex items-center justify-center rounded-full ${i === page ? 'bg-brand-text text-white' : 'text-brand-gray hover:bg-gray-200/60 hover:text-brand-text'} text-sm font-medium transition-colors" data-page="${i}">${i}</a>`;
                }
                if (end < total) html += `<span class="w-10 h-10 flex items-center justify-center text-sm font-medium text-brand-gray">...</span>`;
                // Next button
                html += `<a href="#" class="w-10 h-10 flex items-center justify-center rounded-full text-brand-gray bg-gray-200/60 ${page === total ? 'cursor-not-allowed opacity-60' : 'hover:bg-gray-200/60 hover:text-brand-text'}" data-page="${page+1}" ${page === total ? 'tabindex="-1" aria-disabled="true"' : ''}><span class="sr-only">Next</span><i data-feather="chevron-right" class="h-5 w-5"></i></a>`;
                paginationNav.innerHTML = html;
            }

            // --- Pagination Click Handler ---
            paginationNav.addEventListener('click', function(e) {
                const target = e.target.closest('a[data-page]');
                if (target && !target.classList.contains('cursor-not-allowed')) {
                    e.preventDefault();
                    const page = parseInt(target.getAttribute('data-page'));
                    if (page >= 1 && page <= totalPages) {
                        fetchProducts(page, lastSearch, lastSort);
                    }
                }
            });

            // --- Search & Sort Integration ---
            function triggerSearchSort() {
                lastSearch = searchInput.value.trim();
                lastSort = sortDropdown.value;
                fetchProducts(1, lastSearch, lastSort);
            }
            searchInput.addEventListener('input', triggerSearchSort);
            sortDropdown.addEventListener('change', triggerSearchSort);

            // --- Initial Load ---
            fetchProducts(1);

            // --- SIDEBAR LOGIC (Unchanged) ---
            const sidebar = document.getElementById('sidebar');
            const openSidebarBtn = document.getElementById('open-sidebar-btn');
            const closeSidebarBtn = document.getElementById('close-sidebar-btn');
            const sidebarOverlay = document.getElementById('sidebar-overlay');

            const openSidebar = () => {
                if (sidebar) {
                    sidebar.classList.remove('-translate-x-full');
                    document.body.classList.add('overflow-hidden');
                }
            };

            const closeSidebar = () => {
                if (sidebar) {
                    sidebar.classList.add('-translate-x-full');
                    document.body.classList.remove('overflow-hidden');
                }
            };

            openSidebarBtn.addEventListener('click', openSidebar);
            closeSidebarBtn.addEventListener('click', closeSidebar);
            sidebarOverlay.addEventListener('click', closeSidebar);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                    closeSidebar();
                }
            });
            feather.replace();
        });
    </script>

</body>
</html>