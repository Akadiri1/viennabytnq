<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="images/viennabg.png">
    <link rel="apple-touch-icon-precomposed" type="image/png" sizes="152x152" href="images/viennabg.png">
  
    <title>VIENNA | Shop</title>
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
                    <a href="index.html" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 group transition-colors duration-200">
                        <i data-feather="home" class="w-5 h-5 text-brand-gray mr-4"></i>
                        <span class="tracking-wide">Home</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 group transition-colors duration-200">
                        <i data-feather="shopping-bag" class="w-5 h-5 text-brand-gray mr-4"></i>
                        <span class="tracking-wide">Products</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 group transition-colors duration-200">
                        <i data-feather="info" class="w-5 h-5 text-brand-gray mr-4"></i>
                        <span class="tracking-wide">About Us</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-6 border-t border-gray-200">
            <p class="text-xs text-brand-gray text-center">© 2025 VIENNA</p>
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
                    <a href="index.html">
                        <div class="text-2xl font-serif font-bold tracking-widest">VIENNA</div>
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
    <section class="relative h-64 md:h-80 bg-cover bg-center" style="background-image: url('images/IMG_638082ef.jpg');">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4">
            <nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb">
                <ol class="list-none p-0 inline-flex">
                    <li class="flex items-center">
                        <a href="index.html" class="hover:underline">Home</a>
                        <i data-feather="chevron-right" class="h-4 w-4 mx-2"></i>
                    </li>
                    <li>
                        <span>Shop</span>
                    </li>
                </ol>
            </nav>
            <h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4">All Products</h1>
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
                
                <!-- Card 1 -->
                <div class="product-card" data-price="210.00">
                    <a href="shop-details.html" class="group block">
                        <div class="relative w-full overflow-hidden">
                            <div class="aspect-[9/16]">
                                <img src="images/LV7A878555a4.jpg" alt="Celeste Dress" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0">
                                <img src="images/IMG_6406cf2e.jpg" alt="Celeste Dress Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100">
                            </div>
                        </div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text">Celeste Ruched Maxi Dress</h3>
                            <p class="mt-1 text-sm text-brand-gray">$210.00</p>
                            <div class="flex justify-center space-x-2 mt-2">
                                <span class="block w-4 h-4 rounded-full bg-blue-900 border border-gray-300"></span>
                                <span class="block w-4 h-4 rounded-full bg-gray-200 border border-gray-300"></span>
                            </div>
                            <div class="mt-2 text-xs text-brand-gray tracking-wider">S | M | L</div>
                        </div>
                    </a>
                </div>

                <!-- Card 2 -->
                <div class="product-card" data-price="185.00">
                     <a href="shop-details.html" class="group block">
                        <div class="relative w-full overflow-hidden">
                           <div class="aspect-[9/16]">
                               <img src="images/IMG_6413ac6d.jpg" alt="Azure Dress" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0">
                               <img src="images/IMG_6414db0a.jpg" alt="Azure Dress Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100">
                           </div>
                        </div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text">Azure Button Halter Dress</h3>
                            <p class="mt-1 text-sm text-brand-gray">$185.00</p>
                            <div class="flex justify-center space-x-2 mt-2">
                                <span class="block w-4 h-4 rounded-full bg-sky-500 border border-gray-300"></span>
                            </div>
                            <div class="mt-2 text-xs text-brand-gray tracking-wider">XS | S | M</div>
                        </div>
                    </a>
                </div>
                
                <!-- Card 3 -->
                <div class="product-card" data-price="150.00">
                    <a href="shop-details.html" class="group block">
                        <div class="relative w-full overflow-hidden">
                           <div class="aspect-[9/16]">
                               <img src="images/IMG_1697d27b.jpg" alt="Onyx Dress" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0">
                               <img src="images/IMG_1698d27b.jpg" alt="Onyx Dress Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100">
                           </div>
                        </div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text">Onyx Cowl Neck Mini Dress</h3>
                            <p class="mt-1 text-sm text-brand-gray">$150.00</p>
                             <div class="flex justify-center space-x-2 mt-2">
                                <span class="block w-4 h-4 rounded-full bg-black border border-gray-300"></span>
                            </div>
                            <div class="mt-2 text-xs text-brand-gray tracking-wider">S | M | L | XL</div>
                        </div>
                    </a>
                </div>
                
                <!-- Card 4 -->
                <div class="product-card" data-price="250.00">
                    <a href="shop-details.html" class="group block">
                        <div class="relative w-full overflow-hidden">
                           <div class="aspect-[9/16]">
                               <img src="images/IMG_17004aa0.jpg" alt="Sequin Skirt" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0">
                               <img src="images/IMG_17012e6a.jpg" alt="Sequin Skirt Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100">
                           </div>
                        </div>
                        <div class="pt-4 text-center">
                            <h3 class="text-base font-medium text-brand-text">Silver Sequin Draped Skirt</h3>
                            <p class="mt-1 text-sm text-brand-gray">$250.00</p>
                             <div class="flex justify-center space-x-2 mt-2">
                                <span class="block w-4 h-4 rounded-full bg-gray-400 border border-gray-300"></span>
                            </div>
                            <div class="mt-2 text-xs text-brand-gray tracking-wider">S | M</div>
                        </div>
                    </a>
                </div>
                
                <!-- Card 5 -->
                <div class="product-card" data-price="195.00">
                    <a href="shop-details.html" class="group block">
                       <div class="relative w-full overflow-hidden">
                          <div class="aspect-[9/16]">
                              <img src="images/IMG_1701_434249c9-5a86-4e77-9afd-933b3355b8cbdacd.jpg" alt="Ivory Blazer" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0">
                              <img src="images/IMG_171609f6.jpg" alt="Ivory Blazer Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100">
                          </div>
                       </div>
                       <div class="pt-4 text-center">
                           <h3 class="text-base font-medium text-brand-text">Ivory Tailored Blazer</h3>
                           <p class="mt-1 text-sm text-brand-gray">$195.00</p>
                           <div class="flex justify-center space-x-2 mt-2">
                                <span class="block w-4 h-4 rounded-full bg-white border border-gray-300"></span>
                            </div>
                           <div class="mt-2 text-xs text-brand-gray tracking-wider">S | M | L</div>
                       </div>
                   </a>
                </div>

                <!-- Card 6 -->
                <div class="product-card" data-price="120.00">
                    <a href="shop-details.html" class="group block">
                       <div class="relative w-full overflow-hidden">
                          <div class="aspect-[9/16]">
                              <img src="images/IMG_17184aa0.jpg" alt="Noir Top" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0">
                              <img src="images/IMG_17192e6a.jpg" alt="Noir Top Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100">
                          </div>
                       </div>
                       <div class="pt-4 text-center">
                           <h3 class="text-base font-medium text-brand-text">Noir Corset Top</h3>
                           <p class="mt-1 text-sm text-brand-gray">$120.00</p>
                           <div class="flex justify-center space-x-2 mt-2">
                                <span class="block w-4 h-4 rounded-full bg-black border border-gray-300"></span>
                            </div>
                           <div class="mt-2 text-xs text-brand-gray tracking-wider">XS | S | M</div>
                       </div>
                   </a>
                </div>
                
                 <!-- Card 7 -->
                <div class="product-card" data-price="310.00">
                    <a href="shop-details.html" class="group block">
                       <div class="relative w-full overflow-hidden">
                          <div class="aspect-[9/16]">
                              <img src="images/IMG_638082ef.jpg" alt="Sequin Gown" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0">
                              <img src="images/IMG_63876502.jpg" alt="Sequin Gown Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100">
                          </div>
                       </div>
                       <div class="pt-4 text-center">
                           <h3 class="text-base font-medium text-brand-text">Opulent Sequin Gown</h3>
                           <p class="mt-1 text-sm text-brand-gray">$310.00</p>
                            <div class="flex justify-center space-x-2 mt-2">
                                <span class="block w-4 h-4 rounded-full" style="background-color: #c5b358;"></span>
                            </div>
                           <div class="mt-2 text-xs text-brand-gray tracking-wider">S | M | L</div>
                       </div>
                   </a>
                </div>

                <!-- Card 8 -->
                <div class="product-card" data-price="95.00">
                    <a href="shop-details.html" class="group block">
                       <div class="relative w-full overflow-hidden">
                          <div class="aspect-[9/16]">
                              <img src="images/LV7A877255a4.jpg" alt="Black Bodysuit" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0">
                              <img src="images/LV7A878555a4.jpg" alt="Black Bodysuit Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100">
                          </div>
                       </div>
                       <div class="pt-4 text-center">
                           <h3 class="text-base font-medium text-brand-text">Classic Black Bodysuit</h3>
                           <p class="mt-1 text-sm text-brand-gray">$95.00</p>
                           <div class="flex justify-center space-x-2 mt-2">
                                <span class="block w-4 h-4 rounded-full bg-black border border-gray-300"></span>
                            </div>
                           <div class="mt-2 text-xs text-brand-gray tracking-wider">XS | S | M | L</div>
                       </div>
                   </a>
                </div>
            </div>

            <!-- NEW: REDESIGNED PAGINATION -->
            <nav class="flex items-center justify-center pt-24 space-x-2" aria-label="Pagination">
                <a href="#" class="w-10 h-10 flex items-center justify-center rounded-full text-brand-gray bg-gray-200/60 cursor-not-allowed opacity-60">
                    <span class="sr-only">Previous</span>
                    <i data-feather="chevron-left" class="h-5 w-5"></i>
                </a>
                <a href="#" aria-current="page" class="w-10 h-10 flex items-center justify-center rounded-full bg-brand-text text-white text-sm font-medium">1</a>
                <a href="#" class="w-10 h-10 flex items-center justify-center rounded-full text-brand-gray hover:bg-gray-200/60 hover:text-brand-text text-sm font-medium transition-colors">2</a>
                <a href="#" class="w-10 h-10 hidden md:flex items-center justify-center rounded-full text-brand-gray hover:bg-gray-200/60 hover:text-brand-text text-sm font-medium transition-colors">3</a>
                <span class="w-10 h-10 flex items-center justify-center text-sm font-medium text-brand-gray">...</span>
                <a href="#" class="w-10 h-10 hidden md:flex items-center justify-center rounded-full text-brand-gray hover:bg-gray-200/60 hover:text-brand-text text-sm font-medium transition-colors">8</a>
                <a href="#" class="w-10 h-10 flex items-center justify-center rounded-full text-brand-gray hover:bg-gray-200/60 hover:text-brand-text transition-colors">
                    <span class="sr-only">Next</span>
                    <i data-feather="chevron-right" class="h-5 w-5"></i>
                </a>
            </nav>

        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-gray-200">
        <div class="bg-gray-50 text-center py-4 text-brand-gray text-xs">
            <p>© 2025 VIENNA. All Rights Reserved.</p>
        </div>
    </footer>


    <!-- MODIFIED: JAVASCRIPT LOGIC now includes search functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- Elements ---
            const searchInput = document.getElementById('product-search');
            const sortDropdown = document.getElementById('sort-filter-dropdown');
            const productGrid = document.getElementById('product-grid');
            const productCards = Array.from(productGrid.querySelectorAll('.product-card'));
            const originalOrder = [...productCards]; // Keep a copy of the initial order

            // --- Combined Search and Sort Function ---
            function updateProductDisplay() {
                const searchTerm = searchInput.value.toLowerCase();
                const sortBy = sortDropdown.value;

                // 1. Filter cards based on search term
                let filteredCards = originalOrder.filter(card => {
                    const title = card.querySelector('h3').textContent.toLowerCase();
                    return title.includes(searchTerm);
                });

                // 2. Sort the filtered cards
                let sortedAndFilteredCards;
                if (sortBy === 'featured') {
                    // 'featured' uses the original DOM order of the filtered items
                    sortedAndFilteredCards = filteredCards;
                } else {
                    sortedAndFilteredCards = [...filteredCards].sort((a, b) => {
                        const priceA = parseFloat(a.dataset.price);
                        const priceB = parseFloat(b.dataset.price);
                        return sortBy === 'price-asc' ? priceA - priceB : priceB - priceA;
                    });
                }

                // 3. Render the final list of cards
                productGrid.innerHTML = ''; // Clear the grid
                if (sortedAndFilteredCards.length > 0) {
                    sortedAndFilteredCards.forEach(card => productGrid.appendChild(card));
                } else {
                    // Optional: Display a message when no products are found
                    productGrid.innerHTML = `<p class="col-span-full text-center text-brand-gray py-8">No products found matching your search.</p>`;
                }
            }
            
            // --- Event Listeners ---
            searchInput.addEventListener('input', updateProductDisplay);
            sortDropdown.addEventListener('change', updateProductDisplay);


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