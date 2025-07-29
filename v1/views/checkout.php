<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="images/viennabg.png">
    <link rel="apple-touch-icon-precomposed" type="image/png" sizes="152x152" href="images/viennabg.png">
  
    <title>VIENNA | Checkout</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      // Custom configuration for Tailwind
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'brand-bg': '#F9F6F2',
              'brand-text': '#1A1A1A',
              'brand-gray': '#6B7280',
            },
            fontFamily: {
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
    <!-- Paystack Inline Script -->
    <script src="https://js.paystack.co/v1/inline.js"></script>

    <style>
        .form-input-sleek {
            background-color: transparent;
            border: 0;
            border-bottom: 1px solid #d1d5db;
            border-radius: 0;
            padding: 0.75rem 0.25rem;
            width: 100%;
            transition: border-color 0.2s;
        }
        .form-input-sleek:focus {
            outline: none;
            box-shadow: none;
            ring: 0;
            border-bottom-color: #1A1A1A;
        }
        .step-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .step-header .step-circle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 9999px;
            background-color: #1A1A1A;
            color: white;
            font-size: 0.875rem;
            font-weight: bold;
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
    <!-- MODIFICATION: The header is now merged with the breadcrumb section -->
    <section class="relative h-48 md:h-56 bg-cover bg-center" style="background-image: url('images/IMG_63900471.jpg');">
        <!-- Dark Overlay for readability -->
        <div class="absolute inset-0 bg-black/40"></div>
        
        <!-- Header positioned absolutely on top of the image -->
        <header class="absolute inset-x-0 top-0 z-20">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-center h-20">
                    <a href="index.html">
                        <!-- The filter class makes the dark logo appear white against the background -->
                        <img src="images/viennabg.png" alt="VIENNA Logo" class="h-10 w-auto filter brightness-0 invert">
                    </a>
                </div>
            </div>
        </header>

        <!-- Centered "Checkout" Title -->
        <div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4">
            <h1 class="text-5xl md:text-6xl font-serif font-semibold pt-12">Checkout</h1>
        </div>
    </section>

    <!-- MAIN CONTENT (No changes below this line) -->
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 lg:gap-x-16 xl:gap-x-24">
            
            <!-- Left Column: Customer Information & Shipping -->
            <div class="w-full">
                <form id="checkout-form" class="space-y-12">
                    <!-- Contact Information -->
                    <div>
                        <div class="step-header">
                            <div class="step-circle">1</div>
                            <h2>Contact Information</h2>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-brand-gray mb-1">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input-sleek" placeholder="you@example.com" required>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div>
                        <div class="step-header">
                            <div class="step-circle">2</div>
                            <h2>Shipping Address</h2>
                        </div>
                        <div class="space-y-6">
                            <div>
                                <label for="full-name" class="block text-sm font-medium text-brand-gray mb-1">Full Name</label>
                                <input type="text" id="full-name" name="full-name" class="form-input-sleek" placeholder="Jane Doe" required>
                            </div>
                            <div>
                                <label for="address" class="block text-sm font-medium text-brand-gray mb-1">Address</label>
                                <input type="text" id="address" name="address" class="form-input-sleek" placeholder="123 Main Street" required>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-6">
                                <div><label for="city" class="block text-sm font-medium text-brand-gray mb-1">City</label><input type="text" id="city" name="city" class="form-input-sleek" required></div>
                                <div><label for="state" class="block text-sm font-medium text-brand-gray mb-1">State / Province</label><input type="text" id="state" name="state" class="form-input-sleek" required></div>
                                <div><label for="zip" class="block text-sm font-medium text-brand-gray mb-1">ZIP / Postal Code</label><input type="text" id="zip" name="zip" class="form-input-sleek" required></div>
                            </div>
                            <div>
                                <label for="country" class="block text-sm font-medium text-brand-gray mb-1">Country</label>
                                <input type="text" id="country" name="country" class="form-input-sleek" value="Nigeria" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Column: Order Summary -->
            <div class="w-full mt-16 lg:mt-0">
                <div class="bg-white p-8 border border-gray-200/70 lg:sticky lg:top-28">
                    <h2 class="text-2xl font-serif font-semibold mb-6">Order Summary</h2>
                    
                    <div class="space-y-5">
                        <!-- Sample Product 1 -->
                        <div class="flex justify-between items-start gap-4">
                            <div class="relative flex-shrink-0"><img src="images/LV7A878555a4.jpg" alt="Celeste Dress" class="w-20 h-24 object-cover rounded-md border border-gray-200"><span class="absolute -top-2 -right-2 bg-brand-gray text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold">1</span></div>
                            <div class="flex-1"><h3 class="font-semibold text-sm">Celeste Ruched Maxi Dress</h3><p class="text-xs text-brand-gray">Sky Blue / M</p></div>
                            <p class="font-medium text-sm">$210.00</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200 space-y-4">
                        <div class="flex items-center gap-4">
                            <input type="text" placeholder="Discount code" class="form-input-sleek flex-1">
                            <button class="bg-gray-200/80 text-brand-text text-sm font-semibold px-4 py-2 hover:bg-gray-300 transition-colors">Apply</button>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200 space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-brand-gray">Subtotal</span><span class="font-medium">$210.00</span></div>
                        <div class="flex justify-between"><span class="text-brand-gray">Shipping</span><span class="font-medium">$15.00</span></div>
                        <div class="flex justify-between text-lg font-semibold pt-4 mt-2 border-t border-gray-200"><span>Total</span><span id="total-price" data-total-kobo="22500">$225.00</span></div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-serif font-semibold mb-4">Payment</h3>
                        <p class="text-sm text-brand-gray mb-4">All transactions are secure and encrypted. We do not store your card details.</p>
                        <img src="https://upload.wikimedia.org/wikipedia/commons/1/1f/Paystack.png" alt="Paystack" class="h-6 mb-5">
                        <button type="button" id="pay-button" class="w-full bg-brand-text text-white py-4 text-center font-semibold hover:bg-gray-800 transition-colors duration-300 flex items-center justify-center gap-3">
                            <i data-feather="lock" class="w-4 h-4"></i>
                            <span>Pay Now</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-gray-200 mt-16"><div class="container mx-auto px-6 py-8 text-center text-sm text-brand-gray"><p>© 2025 VIENNA. All Rights Reserved.</p></div></footer>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();

            const payButton = document.getElementById('pay-button');
            const checkoutForm = document.getElementById('checkout-form');
            
            payButton.addEventListener('click', (e) => {
                e.preventDefault();
                
                if (!checkoutForm.checkValidity()) {
                    checkoutForm.reportValidity();
                    return;
                }
                
                payWithPaystack();
            });

            function payWithPaystack() {
                const email = document.getElementById('email').value;
                const totalElement = document.getElementById('total-price');
                const amountInKobo = parseInt(totalElement.dataset.totalKobo); 

                let handler = PaystackPop.setup({
                    key: 'pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxx', // IMPORTANT: Replace with your Paystack Public Key
                    email: email,
                    amount: amountInKobo,
                    currency: 'NGN', 
                    ref: 'VIENNA-' + new Date().getTime().toString(), 
                    
                    callback: function(response) {
                        alert('Payment complete! Reference: ' + response.reference);
                        // In a real app, you'd redirect to a server endpoint to verify the transaction
                        // window.location.href = '/verify-payment?reference=' + response.reference; 
                    },
                    onClose: function() {
                        // User closed the popup
                    },
                });

                handler.openIframe();
            }
        });
    </script>
</body>
</html>