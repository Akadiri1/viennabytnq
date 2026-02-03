<?php
// Retrieve site settings if available, otherwise default
$site_name_404 = isset($site_name) ? $site_name : 'Vienna by TNQ';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found | <?= htmlspecialchars($site_name_404) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', 'brand-red': '#EF4444', },
                    fontFamily: { 'sans': ['Inter', 'ui-sans-serif', 'system-ui'], 'serif': ['Cormorant Garamond', 'serif'], },
                    animation: { 'float': 'float 6s ease-in-out infinite', },
                    keyframes: { float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-20px)' }, } }
                }
            }
        }
    </script>
</head>
<body class="bg-brand-bg font-sans text-brand-text h-screen flex flex-col overflow-hidden">

    <!-- Minimal Header -->
    <header class="absolute top-0 left-0 right-0 p-6 z-10 flex justify-center">
        <a href="/home" class="text-2xl font-serif font-bold tracking-widest"><?= htmlspecialchars($site_name_404) ?></a>
    </header>

    <main class="flex-grow flex flex-col items-center justify-center text-center px-4 relative">
        <!-- Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-10">
            <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-gray-300 rounded-full mix-blend-multiply filter blur-xl animate-float"></div>
            <div class="absolute top-1/3 right-1/4 w-64 h-64 bg-gray-400 rounded-full mix-blend-multiply filter blur-xl animate-float" style="animation-delay: 2s"></div>
        </div>

        <div class="relative z-20 max-w-lg">
            <h1 class="text-9xl font-serif font-bold text-gray-200 select-none">404</h1>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <span class="text-3xl font-serif font-semibold text-brand-text bg-brand-bg px-4">Page Not Found</span>
            </div>
            
            <p class="mt-8 text-brand-gray text-lg max-w-md mx-auto">
                We couldn't assign a page to this address. It might have been moved or doesn't exist.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/home" class="group px-8 py-3 bg-brand-text text-white text-sm font-medium hover:bg-black transition-colors duration-300 uppercase tracking-widest flex items-center">
                    <i data-feather="home" class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform"></i>
                    Back to Home
                </a>
                <a href="/shop" class="group px-8 py-3 border border-brand-text text-brand-text text-sm font-medium hover:bg-gray-100 transition-colors duration-300 uppercase tracking-widest flex items-center">
                    <i data-feather="shopping-bag" class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform"></i>
                    View Shop
                </a>
            </div>
        </div>
    </main>

    <footer class="absolute bottom-6 w-full text-center">
        <p class="text-xs text-brand-gray">© <?=date('Y')?> <?= htmlspecialchars($site_name_404) ?>. All Rights Reserved.</p>
    </footer>

    <script>
        feather.replace();
    </script>
</body>
</html>
