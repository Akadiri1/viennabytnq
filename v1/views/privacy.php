<?php
// You can include any necessary PHP files here if needed, like your database connection
// for fetching the site name and logo directory dynamically.
// For example: require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
    <title><?=$site_name?> | Our Policies</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      // Custom configuration for Tailwind to match your site
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'brand-bg': '#F9F6F2',
              'brand-text': '#1A1A1A',
              'brand-gray': '#6B7280',
              'brand-red': '#EF4444',
            },
            fontFamily: {
              'sans': ['Lato', 'ui-sans-serif', 'system-ui'],
              'serif': ['Playfair Display', 'serif'],
            }
          }
        }
      }
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-brand-bg font-sans text-brand-text">

<!-- HEADER -->
<header class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-center h-16">
            <div class="flex-shrink-0 text-center">
                <a href="/home">
                    <div class="text-2xl font-serif font-bold tracking-widest"><?=$site_name?></div>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<main class="bg-brand-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-4xl md:text-5xl font-serif font-semibold text-center mb-12">Order & Delivery Information</h1>

            <div class="space-y-10">

                <!-- Order Processing Section -->
                <div class="p-8 bg-white/60 rounded-lg shadow-sm border border-gray-200/80">
                    <h2 class="text-2xl font-serif font-semibold mb-4">Order Processing & Production</h2>
                    <ul class="space-y-3 list-disc list-inside text-brand-gray leading-relaxed">
                        <li><span class="font-medium text-brand-text">Processing Time:</span> All outfits are made to order and take 7–10 working days to complete.</li>
                        <li><span class="font-medium text-brand-text">Express Orders:</span> If you need your outfit sooner, we offer an Express Service for an additional ₦30,000. Please contact us to arrange this.</li>
                        <li><span class="font-medium text-brand-text">Measurements:</span> We work strictly with your provided measurements to ensure the perfect fit. It is the client's responsibility to provide accurate measurements.</li>
                    </ul>
                </div>

                <!-- Domestic Delivery Section -->
                <div class="p-8 bg-white/60 rounded-lg shadow-sm border border-gray-200/80">
                    <h2 class="text-2xl font-serif font-semibold mb-4">Domestic Delivery (Within Nigeria)</h2>
                    <ul class="space-y-3 list-disc list-inside text-brand-gray leading-relaxed">
                        <li>Delivery is typically executed within 24 hours or the next day after production completion.</li>
                    </ul>
                </div>

                <!-- International Delivery Section -->
                <div class="p-8 bg-white/60 rounded-lg shadow-sm border border-gray-200/80">
                    <h2 class="text-2xl font-serif font-semibold mb-4">International Delivery (Outside Nigeria)</h2>
                    <ul class="space-y-3 list-disc list-inside text-brand-gray leading-relaxed">
                        <li>Delivery will commence upon receipt of shipping costs.</li>
                        <li>Outfits will be shipped via DHL for reliable and trackable service.</li>
                        <li>Delivery costs are calculated based on the outfit's weight and the destination country.</li>
                    </ul>
                </div>

                <!-- Payment Terms Section -->
                <div class="p-8 bg-white/60 rounded-lg shadow-sm border border-gray-200/80">
                    <h2 class="text-2xl font-serif font-semibold mb-4">Order and Payment Terms</h2>
                    <ul class="space-y-3 list-disc list-inside text-brand-gray leading-relaxed">
                        <li>Outfit production will only begin after full payment, order confirmation, and all required measurements have been received.</li>
                    </ul>
                </div>

                <!-- Important Notes Section -->
                <div class="mt-12 p-6 bg-red-50 border-l-4 border-red-400 rounded-r-lg">
                    <h3 class="font-serif font-semibold text-lg text-brand-text mb-2">Please Note</h3>
                    <div class="space-y-3 text-sm text-red-800">
                        <p>These policies aim to ensure clarity and professionalism in our transactions with all clients. Not reading the policy will not be considered a valid excuse.</p>
                        <p>Please be aware that there may be potential delivery delays due to unforeseen circumstances. We will promptly communicate any delays to our customers.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<!-- FOOTER -->
<footer class="bg-white border-t border-gray-200">
    <div class="bg-gray-50 text-center py-4 text-brand-gray text-xs">
        <p>© <?=date('Y')?> <?=$site_name?>. All Rights Reserved.</p>
    </div>
</footer>

<script>
    feather.replace();
</script>

</body>
</html>