<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// require_once 'includes/db_connection.php';

// Mock data for demonstration
$site_name = 'Vienna by TNQ';
$logo_directory = 'images/favicon.png'; // Path to your favicon
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($logo_directory) ?>" />
    <title>About Us - <?= htmlspecialchars($site_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              "brand-bg": "#F9F6F2",
              "brand-text": "#1A1A1A",
              "brand-gray": "#6B7280",
              "brand-red": "#EF4444",
            },
            fontFamily: {
              sans: ["Inter", "ui-sans-serif", "system-ui"],
              serif: ["Cormorant Garamond", "serif"],
            },
          },
        },
      };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
      /* You can include any specific styles from your main CSS file here for consistency */
      .prose { max-width: 65ch; } /* Standardize prose width for readability */
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">

    <!-- Include your site's header here -->
    <!-- For demonstration, I'll add a simplified header -->
    <header class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-1 flex justify-start">
                    <!-- Placeholder for menu button if you have one -->
                </div>
                <div class="flex-shrink-0 text-center">
                    <a href="/home">
                        <div class="text-2xl font-serif font-bold tracking-widest"><?= htmlspecialchars($site_name) ?></div>
                    </a>
                </div>
                <div class="flex-1 flex items-center justify-end space-x-4">
                    <a href="<?= isset($_SESSION['user_id']) ? '/user-dashboard' : '/register' ?>" class="p-2 text-brand-text hover:text-brand-gray" title="<?= isset($_SESSION['user_id']) ? 'My Account' : 'Login / Register' ?>">
                        <i data-feather="user" class="h-5 w-5"></i>
                    </a>
                    <a href="/view-cart" class="p-2 text-brand-text hover:text-brand-gray">
                        <i data-feather="shopping-bag" class="h-5 w-5"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main>
        <!-- Hero Section -->
        <section class="relative h-80 md:h-96 bg-cover bg-center" style="background-image: url('/images/2.jpg');"> <!-- Replace with a high-quality lifestyle or studio image -->
            <div class="absolute inset-0 bg-black/30"></div>
            <div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4">
                <h1 class="text-5xl md:text-7xl font-serif font-semibold">The Vienna Story</h1>
                <p class="mt-4 text-lg md:text-xl max-w-2xl font-light tracking-wide">Crafting Modern Elegance for the Discerning Woman.</p>
            </div>
        </section>

        <!-- Our Philosophy Section -->
        <section class="py-20 md:py-28">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-20 items-center">
                    <div>
                        <h2 class="text-3xl md:text-4xl font-serif font-semibold text-brand-text mb-6">Our Philosophy</h2>
                        <div class="prose text-brand-gray text-base leading-relaxed space-y-4">
                            <p>
                                Vienna by TNQ is a ready-to-wear fashion label born from a desire to celebrate enduring style. In a world of fleeting trends, we create pieces that transcend seasons—wardrobe heirlooms designed for the woman who navigates life with grace, confidence, and a quiet sense of self.
                            </p>
                            <p>
                                Inspired by the timeless sophistication of European classicism and the dynamic energy of the modern woman, our collections are a study in contrasts: structured yet fluid, bold yet understated, contemporary yet classic.
                            </p>
                            <p>
                                We believe that true luxury lies in the details: the perfect fit, the feel of exquisite fabric against the skin, and the impeccable craftsmanship that ensures a garment is loved for years to come.
                            </p>
                        </div>
                    </div>
                    <div class="w-full h-auto aspect-[4/5]">
                        <img src="images/FUJIFILM-0332.jpg" alt="A detailed shot of fabric and craftsmanship" class="w-full h-full object-cover rounded-lg shadow-lg"> <!-- Replace with a detail-oriented image -->
                    </div>
                </div>
            </div>
        </section>

        <!-- A Note from the Founder Section -->
        <section class="bg-white py-20 md:py-28">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center max-w-4xl">
                <h3 class="text-sm font-semibold tracking-widest text-brand-gray uppercase mb-4">A Note From The Founder</h3>
                <blockquote class="text-2xl md:text-3xl font-serif font-semibold text-brand-text leading-snug italic">
                    "Fashion, to me, is a language. It’s how we introduce ourselves without saying a word. I created Vienna to give every woman a vocabulary of grace and strength, crafting pieces that empower her to tell her own beautiful story."
                </blockquote>
                <p class="mt-6 font-bold text-brand-text tracking-wider">— TNQ, Founder & Creative Director</p>
            </div>
        </section>

        <!-- Our Craftsmanship Section -->
        <section class="py-20 md:py-28">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-20 items-center">
                    <div class="w-full h-auto aspect-[4/5] order-last md:order-first">
                         <img src="images/P_ST5396.jpg" alt="Designer sketching or working with fabric" class="w-full h-full object-cover rounded-lg shadow-lg"> <!-- Replace with a behind-the-scenes image -->
                    </div>
                    <div>
                        <h2 class="text-3xl md:text-4xl font-serif font-semibold text-brand-text mb-6">Our Commitment to Craft</h2>
                        <div class="prose text-brand-gray text-base leading-relaxed space-y-4">
                            <p>
                                Every Vienna by TNQ garment begins with a thoughtful design process and a commitment to unparalleled quality. We source premium materials from around the world, selecting fabrics not only for their beauty but for their durability and comfort.
                            </p>
                            <ul class="list-disc pl-5 space-y-2">
                                <li><strong>Impeccable Tailoring:</strong> Our silhouettes are meticulously constructed to flatter the female form, ensuring every piece drapes and moves with elegance.</li>
                                <li><strong>Conscious Production:</strong> We partner with skilled artisans and ethical manufacturers who share our dedication to craftsmanship and responsible practices.</li>
                                <li><strong>Lasting Quality:</strong> From the integrity of the seams to the finish of the buttons, we oversee every detail to create clothing that is made to last.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="container mx-auto px-4 sm:px-6 lg:px-8 text-center my-20">
             <h2 class="text-3xl md:text-4xl font-serif font-semibold text-brand-text">Join Our World</h2>
             <p class="mt-4 max-w-2xl mx-auto text-brand-gray">
                Discover a wardrobe that is as timeless and unique as you are. Explore our latest collection and find the pieces that will journey with you.
             </p>
             <a href="/shop" class="inline-block mt-8 bg-brand-text text-white py-3 px-10 font-semibold hover:bg-gray-800 transition-colors">
                Explore The Collection
             </a>
        </section>
    </main>

    <!-- Include your site's footer here -->
    <footer class="bg-white border-t border-gray-200">
        <div class="p-6 text-center">
            <p class="text-xs text-brand-gray">© <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        feather.replace();
    </script>
</body>
</html>