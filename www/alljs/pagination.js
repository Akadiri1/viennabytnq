document.addEventListener('DOMContentLoaded', () => {
    // --- Elements ---
    const searchInput = document.getElementById('product-search');
    const sortDropdown = document.getElementById('sort-filter-dropdown');
    const productGrid = document.getElementById('product-grid');
    const paginationNav = document.getElementById('pagination');

    // --- State ---
    let currentPage = 1;
    let totalPages = 1;
    let lastSearch = '';
    let lastSort = 'featured';
    
    // --- NEW: CURRENCY CONVERSION LOGIC ---
    // We need this logic here so it can be re-applied after fetching new products.
    const USD_RATE = 1450; // IMPORTANT: This must match the rate in your shop.php file.
    
    function formatPrice(amount, currency, variantName = '') {
        let formattedPrice = '';
        if (currency === 'USD') {
            formattedPrice = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
        } else {
            formattedPrice = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount).replace('NGN', '₦');
        }
        return variantName ? `${variantName} - ${formattedPrice}` : formattedPrice;
    }

    function updateAllPrices(targetCurrency) {
        // Find all price elements within the product grid
        productGrid.querySelectorAll('.price-display').forEach(el => {
            const ngnPrice = parseFloat(el.dataset.priceNgn);
            const variantName = el.textContent.includes('-') ? el.textContent.split('-')[0].trim() : '';
            if (!isNaN(ngnPrice)) {
                let newPrice = (targetCurrency === 'USD') ? ngnPrice / USD_RATE : ngnPrice;
                el.textContent = formatPrice(newPrice, targetCurrency, variantName);
            }
        });
    }
    // --- END OF NEW CURRENCY LOGIC ---

    // --- Fetch Products via AJAX ---
    function fetchProducts(page = 1, search = '', sort = 'featured') {
        const cacheBuster = Date.now();
        // NOTE: Ensure your API endpoint is located at '/pagination' or the correct path.
        fetch(`pagination?page=${page}&search=${encodeURIComponent(search)}&sort=${encodeURIComponent(sort)}&cb=${cacheBuster}`)
        .then(res => {
            if (!res.ok) {
                throw new Error('Network response was not ok');
            }
            return res.json();
        })
        .then(data => {
            productGrid.innerHTML = data.productsHtml;
            
            // --- THE FIX: Re-apply currency conversion after loading new products ---
            const currentActiveCurrency = document.querySelector('.currency-switcher a.active')?.dataset.currency || 'NGN';
            if (currentActiveCurrency === 'USD') {
                updateAllPrices('USD');
            }
            // --- END OF FIX ---
            
            renderPagination(data.currentPage, data.totalPages);
            feather.replace();
        })
        .catch(error => {
            console.error('Failed to fetch products:', error);
            productGrid.innerHTML = `<p class="col-span-full text-center text-brand-red py-8">Error loading products. Please try again later.</p>`;
        });
    }

    // --- Render Pagination Buttons ---
    function renderPagination(page, total) {
        currentPage = parseInt(page);
        totalPages = parseInt(total);
        paginationNav.innerHTML = '';
        
        if (totalPages <= 1) return;

        let html = '';
        // Previous button
        html += `<a href="#" class="w-10 h-10 flex items-center justify-center rounded-full text-brand-gray bg-gray-200/60 ${currentPage === 1 ? 'cursor-not-allowed opacity-60' : 'hover:bg-gray-200 hover:text-brand-text'}" data-page="${currentPage - 1}" ${currentPage === 1 ? 'tabindex="-1" aria-disabled="true"' : ''}><span class="sr-only">Previous</span><i data-feather="chevron-left" class="h-5 w-5"></i></a>`;
        
        // Page numbers
        let start = Math.max(1, currentPage - 2);
        let end = Math.min(totalPages, currentPage + 2);
        if (start > 1) {
            html += `<a href="#" class="w-10 h-10 flex items-center justify-center rounded-full text-brand-gray hover:bg-gray-200/60 hover:text-brand-text text-sm font-medium transition-colors" data-page="1">1</a>`;
            if (start > 2) {
                 html += `<span class="w-10 h-10 flex items-center justify-center text-sm font-medium text-brand-gray">...</span>`;
            }
        }
        for (let i = start; i <= end; i++) {
            html += `<a href="#" class="w-10 h-10 flex items-center justify-center rounded-full ${i === currentPage ? 'bg-brand-text text-white' : 'text-brand-gray hover:bg-gray-200/60 hover:text-brand-text'} text-sm font-medium transition-colors" data-page="${i}">${i}</a>`;
        }
        if (end < totalPages) {
            if (end < totalPages - 1) {
                html += `<span class="w-10 h-10 flex items-center justify-center text-sm font-medium text-brand-gray">...</span>`;
            }
            html += `<a href="#" class="w-10 h-10 flex items-center justify-center rounded-full text-brand-gray hover:bg-gray-200/60 hover:text-brand-text text-sm font-medium transition-colors" data-page="${totalPages}">${totalPages}</a>`;
        }
        
        // Next button
        html += `<a href="#" class="w-10 h-10 flex items-center justify-center rounded-full text-brand-gray bg-gray-200/60 ${currentPage === totalPages ? 'cursor-not-allowed opacity-60' : 'hover:bg-gray-200 hover:text-brand-text'}" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'tabindex="-1" aria-disabled="true"' : ''}><span class="sr-only">Next</span><i data-feather="chevron-right" class="h-5 w-5"></i></a>`;
        
        paginationNav.innerHTML = html;
        feather.replace();
    }

    // --- Event Handlers ---
    paginationNav.addEventListener('click', function(e) {
        const target = e.target.closest('a[data-page]');
        if (target && !target.hasAttribute('aria-disabled')) {
            e.preventDefault();
            const page = parseInt(target.getAttribute('data-page'));
            if (page >= 1 && page <= totalPages) {
                fetchProducts(page, lastSearch, lastSort);
            }
        }
    });

    let debounceTimer;
    function triggerSearchSort() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            lastSearch = searchInput.value.trim();
            lastSort = sortDropdown.value;
            fetchProducts(1, lastSearch, lastSort);
        }, 300);
    }
    searchInput.addEventListener('input', triggerSearchSort);
    sortDropdown.addEventListener('change', triggerSearchSort);

    // Initial Load (if product grid exists on the page)
    if (productGrid) {
        // On the shop page, the initial products are already loaded by PHP.
        // We just need to render the pagination for them.
        // Let's assume the initial page details are available in the pagination element.
        const initialPage = parseInt(paginationNav.dataset.currentPage) || 1;
        const initialTotal = parseInt(paginationNav.dataset.totalPages) || 1;
        renderPagination(initialPage, initialTotal);
    }
});