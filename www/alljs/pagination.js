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

    // --- Fetch Products via AJAX (with cache busting) ---
    function fetchProducts(page = 1, search = '', sort = 'featured') {
        const cacheBuster = Date.now() + Math.random();
        fetch(`pagination?page=${page}&search=${encodeURIComponent(search)}&sort=${encodeURIComponent(sort)}&cb=${cacheBuster}`, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            cache: 'no-store'
        })
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