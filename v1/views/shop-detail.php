<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" type="image/png" href="images/viennabg.png" />
    <link
      rel="apple-touch-icon-precomposed"
      type="image/png"
      sizes="152x152"
      href="images/viennabg.png"
    />

    <title>Product Details - Celeste Ruched Maxi Dress</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      // Custom configuration for Tailwind
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
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- Custom CSS -->
    <style>
      .thumbnail-img {
        border: 2px solid transparent;
        transition: border-color 0.2s ease-in-out;
      }
      .active-thumbnail {
        border-color: #1a1a1a;
      }
      .color-swatch {
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 9999px;
        cursor: pointer;
        border: 1px solid #d1d5db;
        transition: all 0.2s ease-in-out;
      }
      .active-color {
        transform: scale(1.1);
        box-shadow: 0 0 0 3px #1a1a1a;
        border-color: #1a1a1a;
      }
      .size-btn {
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.875rem;
        min-width: 40px;
        text-align: center;
      }
      .size-btn:hover {
        border-color: #1a1a1a;
      }
      .active-size {
        background-color: #1a1a1a;
        color: white;
        border-color: #1a1a1a;
      }
      .form-input-sleek {
        background-color: transparent;
        border: 0;
        border-bottom: 1px solid #d1d5db;
        border-radius: 0;
        padding: 0.5rem 0.1rem;
        width: 100%;
        transition: border-color 0.2s ease-in-out;
      }
      .form-input-sleek:focus {
        outline: none;
        box-shadow: none;
        ring: 0;
        border-bottom-color: #1a1a1a;
      }

      .modal-container {
        display: flex;
        align-items: center;
        justify-content: center;
        position: fixed;
        inset: 0;
        z-index: 100;
        transition: opacity 0.3s ease-in-out;
        opacity: 1;
        pointer-events: auto;
      }
      .modal-container.hidden {
        opacity: 0;
        pointer-events: none;
      }
      .modal-overlay {
        position: absolute;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.4);
      }
      .modal-panel {
        position: relative;
        width: 95%;
        max-width: 500px;
        background-color: #f9f6f2;
        box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: scale(1);
        opacity: 1;
      }
      .modal-container.hidden .modal-panel {
        transform: scale(0.95);
        opacity: 0;
      }

      .modal-content-scrollable {
        max-height: 60vh;
        overflow-y: auto;
        &::-webkit-scrollbar {
          width: 6px;
        }
        &::-webkit-scrollbar-track {
          background: #e5e7eb;
        }
        &::-webkit-scrollbar-thumb {
          background: #9ca3af;
        }
      }
      
      /* ADDED: Scrollable area for custom size modal form */
      .modal-form-scrollable {
          max-height: 60vh; /* Set a max height */
          overflow-y: auto; /* Enable vertical scrolling */
          padding-right: 0.75rem; /* Add some padding so content doesn't touch the scrollbar */
      }
      
      /* ADDED: Very thin scrollbar styling for the custom size modal */
      .modal-form-scrollable::-webkit-scrollbar {
        width: 4px;
      }
      .modal-form-scrollable::-webkit-scrollbar-track {
        background: transparent;
      }
      .modal-form-scrollable::-webkit-scrollbar-thumb {
        background-color: #bdbdbd;
        border-radius: 20px;
        border: 3px solid transparent;
      }


      #scroller-container {
        overflow-x: auto;
        -webkit-mask: linear-gradient(
          to right,
          transparent,
          white 10%,
          white 90%,
          transparent
        );
        mask: linear-gradient(
          to right,
          transparent,
          white 10%,
          white 90%,
          transparent
        );
        scrollbar-width: thin;
        scrollbar-color: #9ca3af #e5e7eb;
      }
      #scroller-container::-webkit-scrollbar {
        height: 6px;
      }
      #scroller-container::-webkit-scrollbar-track {
        background: #e5e7eb;
      }
      #scroller-container::-webkit-scrollbar-thumb {
        background-color: #9ca3af;
        border-radius: 6px;
      }
      #scroller-inner {
        display: flex;
        width: max-content;
        padding-bottom: 1rem;
      }

      #cart-items-container::-webkit-scrollbar {
        width: 6px;
      }
      #cart-items-container::-webkit-scrollbar-track {
        background: #e5e7eb;
      }
      #cart-items-container::-webkit-scrollbar-thumb {
        background: #9ca3af;
      }

      /* ADDED: Custom Color Input Animation */
      #custom-color-input-container {
        /* Define the transition properties and duration */
        transition: max-height 0.35s ease-in-out, opacity 0.3s ease-in-out,
          margin-top 0.35s ease-in-out;
        overflow: hidden;
        /* This is the "open" state */
        max-height: 100px; /* A value larger than the content's height */
        opacity: 1;
        margin-top: 1rem; /* Equivalent to mt-4 */
      }

      #custom-color-input-container.is-closed {
        /* This is the "closed" (hidden) state */
        max-height: 0;
        opacity: 0;
        margin-top: 0;
      }
    </style>
  </head>
  <body class="bg-brand-bg font-sans text-brand-text">
    <!-- SIDEBAR MENU -->
    <div
      id="sidebar"
      class="fixed inset-0 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out"
      aria-labelledby="sidebar-title"
    >
      <div id="sidebar-overlay" class="absolute inset-0 bg-black/40"></div>
      <div class="relative w-80 h-full bg-brand-bg shadow-2xl flex flex-col">
        <div
          class="p-6 flex justify-between items-center border-b border-gray-200"
        >
          <h2 id="sidebar-title" class="text-2xl font-serif font-semibold">
            Menu
          </h2>
          <button
            id="close-sidebar-btn"
            class="p-2 text-brand-gray hover:text-brand-text"
          >
            <i data-feather="x" class="h-6 w-6"></i>
          </button>
        </div>
        <nav class="flex-grow p-6">
          <ul class="space-y-4">
            <li>
              <a
                href="#"
                class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"
                ><i data-feather="home" class="w-5 h-5 text-brand-gray mr-4"></i
                ><span class="tracking-wide">Home</span></a
              >
            </li>
            <li>
              <a
                href="#"
                class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"
                ><i
                  data-feather="shopping-bag"
                  class="w-5 h-5 text-brand-gray mr-4"
                ></i
                ><span class="tracking-wide">Products</span></a
              >
            </li>
            <li>
              <a
                href="#"
                class="flex items-center p-3 text-lg font-medium text-brand-text rounded-md hover:bg-gray-200/60 transition-colors duration-200"
                ><i data-feather="info" class="w-5 h-5 text-brand-gray mr-4"></i
                ><span class="tracking-wide">About Us</span></a
              >
            </li>
          </ul>
        </nav>
        <div class="p-6 border-t border-gray-200">
          <p class="text-xs text-brand-gray text-center">© 2025 VIENNA</p>
        </div>
      </div>
    </div>

    <!-- CART SIDEBAR -->
    <div
      id="cart-sidebar"
      class="fixed inset-0 z-[60] transform translate-x-full transition-transform duration-300 ease-in-out"
      aria-labelledby="cart-title"
    >
      <div
        id="cart-overlay"
        class="absolute inset-0 bg-black/40 cursor-pointer"
      ></div>
      <div
        class="relative w-full max-w-md ml-auto h-full bg-brand-bg shadow-2xl flex flex-col"
      >
        <div
          class="p-6 flex justify-between items-center border-b border-gray-200"
        >
          <h2 id="cart-title" class="text-2xl font-serif font-semibold">
            Your Cart
          </h2>
          <button
            id="close-cart-btn"
            class="p-2 text-brand-gray hover:text-brand-text"
          >
            <i data-feather="x" class="h-6 w-6"></i>
          </button>
        </div>
        <div id="cart-items-container" class="flex-grow p-6 overflow-y-auto">
          <div
            id="empty-cart-message"
            class="flex flex-col items-center justify-center h-full text-center"
          >
            <i
              data-feather="shopping-bag"
              class="w-16 h-16 text-gray-300 mb-4"
            ></i>
            <p class="text-brand-gray">Your cart is empty.</p>
          </div>
        </div>
        <!-- MODIFICATION: Changed button to an A tag -->
        <div class="p-6 border-t border-gray-200 space-y-4 bg-brand-bg">
          <div class="flex justify-between font-semibold">
            <span>Subtotal</span><span id="cart-subtotal">$0.00</span>
          </div>
          <!-- ADDED: View Cart Button -->
          <a
            href="cart.html"
            class="block w-full bg-transparent text-brand-text border border-brand-text py-3 text-center font-semibold hover:bg-gray-100 transition-colors"
          >
            VIEW CART
          </a>
          <a
            href="checkout.html"
            class="block w-full bg-brand-text text-white py-3 text-center font-semibold hover:bg-gray-800 transition-colors"
          >
            CHECKOUT
          </a>
        </div>
      </div>
    </div>

    <!-- HEADER -->
    <header
      class="bg-white/10 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-40"
    >
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex-1 flex justify-start">
            <button
              id="open-sidebar-btn"
              class="p-2 text-brand-text hover:text-brand-gray"
            >
              <i data-feather="menu" class="h-6 w-6"></i>
            </button>
          </div>
          <div class="flex-shrink-0 text-center">
            <a href="index.html"
              ><div class="text-2xl font-serif font-bold tracking-widest">
                VIENNA
              </div></a
            >
          </div>
          <div class="flex-1 flex items-center justify-end space-x-4">
            <button
              id="open-cart-btn"
              class="p-2 text-brand-text hover:text-brand-gray relative"
            >
              <i data-feather="shopping-bag" class="h-5 w-5"></i
              ><span
                id="cart-item-count"
                class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-brand-red text-white text-xs flex items-center justify-center font-bold"
                style="font-size: 8px; display: none"
                >0</span
              >
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- HERO/BREADCRUMB IMAGE SECTION -->
    <section
      class="relative h-96 bg-cover bg-center"
      style="background-image: url('images/LV7A878555a4.jpg')"
    >
      <div class="absolute inset-0 bg-black/30"></div>
      <div
        class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4"
      >
        <nav class="text-sm font-light tracking-wider" aria-label="Breadcrumb">
          <ol class="list-none p-0 inline-flex items-center">
            <li class="flex items-center">
              <a href="#" class="hover:underline">Home</a
              ><i data-feather="chevron-right" class="h-4 w-4 mx-2"></i>
            </li>
            <li class="flex items-center">
              <a href="#" class="hover:underline">Shop</a>
            </li>
          </ol>
        </nav>
        <h1 class="text-5xl md:text-6xl font-serif font-semibold mt-4">
          Celeste Ruched Maxi Dress
        </h1>
      </div>
    </section>

    <!-- MAIN CONTENT -->
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div
        class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 xl:gap-x-16 gap-y-10"
      >
        <!-- Left Column: Image Gallery -->
        <div class="flex flex-col-reverse md:flex-row gap-4">
          <div id="thumbnail-gallery" class="flex md:flex-col gap-3">
            <img
              src="images/LV7A878555a4.jpg"
              alt="Thumbnail 1"
              class="thumbnail-img active-thumbnail w-20 h-28 object-cover cursor-pointer"
            /><img
              src="images/IMG_6406cf2e.jpg"
              alt="Thumbnail 2"
              class="thumbnail-img w-20 h-28 object-cover cursor-pointer"
            /><img
              src="images/IMG_6413ac6d.jpg"
              alt="Thumbnail 3"
              class="thumbnail-img w-20 h-28 object-cover cursor-pointer"
            />
          </div>
          <div class="flex-1">
            <img
              id="main-product-image"
              src="images/LV7A878555a4.jpg"
              alt="Celeste Ruched Maxi Dress"
              class="w-full h-auto object-cover aspect-[4/5]"
            />
          </div>
        </div>

        <!-- Right Column with options visible by default -->
        <div class="flex flex-col pt-4 lg:pt-0 space-y-8">
          <div>
            <h1
              id="product-name"
              class="text-4xl md:text-5xl font-serif font-semibold text-brand-text sr-only"
            >
              Celeste Ruched Maxi Dress
            </h1>
            <p
              id="product-price"
              class="text-2xl text-brand-gray mt-3 mb-5"
              data-price="210.00"
            >
              $210.00
            </p>
            <p class="text-gray-600 leading-relaxed text-base">
              An embodiment of grace, the Celeste dress features delicate
              ruching that flatters the silhouette. Crafted from a soft,
              breathable fabric in a serene sky blue, it's perfect for daytime
              events and evening soirées alike.
            </p>
          </div>

          <!-- Color Options -->
          <div>
            <h3 class="text-sm font-semibold mb-3">
              COLOR:
              <span id="selected-color-name" class="font-normal text-brand-gray"
                >Sky Blue</span
              >
            </h3>
            <div id="color-selector" class="flex items-center space-x-3">
              <button
                data-color="Sky Blue"
                data-image="images/LV7A878555a4.jpg"
                data-hero-image="images/LV7A878555a4.jpg"
                class="color-swatch active-color"
                style="background-color: #a7c7e7"
              ></button
              ><button
                data-color="Onyx Black"
                data-image="images/IMG_1697d27b.jpg"
                data-hero-image="images/IMG_1697d27b.jpg"
                class="color-swatch"
                style="background-color: #000000"
              ></button
              ><button
                data-color="Ivory White"
                data-image="images/IMG_1701_434249c9-5a86-4e77-9afd-933b3355b8cbdacd.jpg"
                data-hero-image="images/IMG_1701_434249c9-5a86-4e77-9afd-933b3355b8cbdacd.jpg"
                class="color-swatch"
                style="background-color: #f5f5dc"
              ></button>
            </div>
            <button
              id="open-custom-color-btn"
              class="text-sm text-brand-gray hover:text-brand-text underline mt-3"
            >
              Need a custom color?
            </button>
            <!-- MODIFIED: Replaced `hidden mt-4` with `is-closed` for animation control -->
            <div id="custom-color-input-container" class="is-closed">
              <label
                for="custom-color"
                class="text-sm font-medium text-brand-gray"
                >Custom Color</label
              >
              <input
                type="text"
                id="custom-color"
                class="form-input-sleek mt-1"
                placeholder="e.g., Emerald Green"
              />
            </div>
          </div>

          <!-- Size Options -->
          <div>
            <div class="flex justify-between items-center mb-3">
              <h3 class="text-sm font-semibold">SIZE</h3>
              <button
                id="open-size-chart-btn"
                class="text-sm font-medium text-brand-gray hover:text-brand-text underline"
              >
                Size Guide
              </button>
            </div>
            <div id="size-selector" class="flex flex-wrap gap-2">
              <button class="size-btn">XS</button
              ><button class="size-btn">S</button
              ><button class="size-btn active-size">M</button
              ><button class="size-btn">L</button
              ><button class="size-btn">XL</button>
            </div>
            <button
              id="open-custom-size-btn"
              class="text-sm text-brand-gray hover:text-brand-text underline mt-3"
            >
              Need a custom size?
            </button>
          </div>

          <!-- Quantity and Add to Cart Section -->
          <div class="flex items-center gap-6">
            <!-- Quantity Selector -->
            <div class="flex items-center border border-gray-300">
              <button
                id="quantity-minus"
                class="px-3 py-2 text-brand-gray hover:text-brand-text"
              >
                -
              </button>
              <span id="quantity-display" class="px-4 py-2 font-medium">1</span>
              <button
                id="quantity-plus"
                class="px-3 py-2 text-brand-gray hover:text-brand-text"
              >
                +
              </button>
            </div>
            <!-- Add to Cart Button -->
            <button
              id="add-to-cart-btn"
              class="w-full bg-brand-text text-white py-3 font-semibold hover:bg-gray-800 transition-colors flex items-center justify-center gap-3"
            >
              <i data-feather="shopping-cart" class="w-5 h-5"></i>
              <span>ADD TO CART</span>
            </button>
          </div>
        </div>
      </div>

      <!-- You Might Also Like Section -->
      <section class="mt-24 mb-16">
        <h2 class="text-3xl font-serif font-semibold text-center mb-10">
          You Might Also Like
        </h2>
        <div id="scroller-container">
          <div id="scroller-inner">
            <div class="w-64 flex-shrink-0 mx-3">
              <a href="#" class="group block"
                ><div
                  class="relative w-full overflow-hidden aspect-[9/16] bg-white"
                >
                  <img
                    src="images/IMG_6413ac6d.jpg"
                    alt="Azure Dress"
                    class="w-full h-full object-cover"
                  />
                </div>
                <div class="pt-4 text-center">
                  <h3 class="text-base font-medium text-brand-text">
                    Azure Button Halter Dress
                  </h3>
                  <p class="mt-1 text-sm text-brand-gray">$185.00</p>
                </div></a
              >
            </div>
            <div class="w-64 flex-shrink-0 mx-3">
              <a href="#" class="group block"
                ><div
                  class="relative w-full overflow-hidden aspect-[9/16] bg-white"
                >
                  <img
                    src="images/IMG_1697d27b.jpg"
                    alt="Onyx Dress"
                    class="w-full h-full object-cover"
                  />
                </div>
                <div class="pt-4 text-center">
                  <h3 class="text-base font-medium text-brand-text">
                    Onyx Cowl Neck Mini Dress
                  </h3>
                  <p class="mt-1 text-sm text-brand-gray">$150.00</p>
                </div></a
              >
            </div>
            <div class="w-64 flex-shrink-0 mx-3">
              <a href="#" class="group block"
                ><div
                  class="relative w-full overflow-hidden aspect-[9/16] bg-white"
                >
                  <img
                    src="images/IMG_17004aa0.jpg"
                    alt="Sequin Skirt"
                    class="w-full h-full object-cover"
                  />
                </div>
                <div class="pt-4 text-center">
                  <h3 class="text-base font-medium text-brand-text">
                    Silver Sequin Draped Skirt
                  </h3>
                  <p class="mt-1 text-sm text-brand-gray">$250.00</p>
                </div></a
              >
            </div>
            <div class="w-64 flex-shrink-0 mx-3">
              <a href="#" class="group block"
                ><div
                  class="relative w-full overflow-hidden aspect-[9/16] bg-white"
                >
                  <img
                    src="images/IMG_1701_434249c9-5a86-4e77-9afd-933b3355b8cbdacd.jpg"
                    alt="Ivory Blazer"
                    class="w-full h-full object-cover"
                  />
                </div>
                <div class="pt-4 text-center">
                  <h3 class="text-base font-medium text-brand-text">
                    Ivory Tailored Blazer
                  </h3>
                  <p class="mt-1 text-sm text-brand-gray">$195.00</p>
                </div></a
              >
            </div>
            <div class="w-64 flex-shrink-0 mx-3">
              <a href="#" class="group block"
                ><div
                  class="relative w-full overflow-hidden aspect-[9/16] bg-white"
                >
                  <img
                    src="images/IMG_638082ef.jpg"
                    alt="Sequin Gown"
                    class="w-full h-full object-cover"
                  />
                </div>
                <div class="pt-4 text-center">
                  <h3 class="text-base font-medium text-brand-text">
                    Opulent Sequin Gown
                  </h3>
                  <p class="mt-1 text-sm text-brand-gray">$310.00</p>
                </div></a
              >
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-gray-200">
      <div
        class="container mx-auto px-6 py-8 text-center text-sm text-brand-gray"
      >
        <p>© 2025 VIENNA. All Rights Reserved.</p>
      </div>
    </footer>

    <!-- MODALS -->
    <div id="custom-size-modal" class="modal-container hidden">
      <div id="custom-size-overlay" class="modal-overlay"></div>
      <div class="modal-panel p-6">
        <div class="flex justify-between items-start pb-4">
          <div>
            <h3 class="text-xl font-serif font-semibold">
              Custom Measurements
            </h3>
            <p class="text-sm text-brand-gray mt-1">
              Enter your measurements for a bespoke fit.
            </p>
          </div>
          <button
            id="close-custom-size-btn"
            class="p-1 text-brand-gray hover:text-brand-text"
          >
            <i data-feather="x" class="w-5 h-5"></i>
          </button>
        </div>
        <!-- MODIFIED: Added scrollable class and updated content -->
        <div class="mt-4 space-y-6 modal-form-scrollable">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
            <div>
              <label for="dress-length" class="text-sm font-medium text-brand-gray"
                >Dress Length (applicable to dress)</label
              ><input
                type="text"
                id="dress-length"
                class="form-input-sleek mt-1"
                placeholder="e.g., 45 in / 114 cm"
              />
            </div>
            <div>
              <label for="blouse-length" class="text-sm font-medium text-brand-gray"
                >Blouse Length (applicable to corsets)</label
              ><input
                type="text"
                id="blouse-length"
                class="form-input-sleek mt-1"
                placeholder="e.g., 18 in / 46 cm"
              />
            </div>
            <div>
              <label for="bust-size" class="text-sm font-medium text-brand-gray"
                >Bust</label
              ><input
                type="text"
                id="bust-size"
                class="form-input-sleek mt-1"
                placeholder="e.g., 36 in / 91 cm"
              />
            </div>
            <div>
              <label for="under-bust" class="text-sm font-medium text-brand-gray"
                >Under Bust</label
              ><input type="text" id="under-bust" class="form-input-sleek mt-1"
              placeholder="e.g., 14 inch">
            </div>
            <div>
              <label for="waist-size" class="text-sm font-medium text-brand-gray"
                >Waist</label
              ><input type="text" id="waist-size" class="form-input-sleek mt-1"
              placeholder="e.g., 29 in / 74 cm">
            </div>
            <div>
              <label for="hips-size" class="text-sm font-medium text-brand-gray"
                >Hips</label
              ><input type="text" id="hips-size" class="form-input-sleek mt-1"
              placeholder="e.g., 39 in / 99 cm">
            </div>
            <div>
              <label for="mini-skirt-length" class="text-sm font-medium text-brand-gray"
                >Mini Skirt Length</label
              ><input type="text" id="mini-skirt-length" class="form-input-sleek mt-1"
              placeholder="e.g., 17 in / 43 cm">
            </div>
             <div>
              <label for="half-length" class="text-sm font-medium text-brand-gray"
                >Half Length</label
              ><input type="text" id="half-length" class="form-input-sleek mt-1"
              placeholder="e.g., 16 in / 41 cm">
            </div>
             <div>
              <label for="cup-size" class="text-sm font-medium text-brand-gray"
                >Cup Size</label
              ><input type="text" id="cup-size" class="form-input-sleek mt-1"
              placeholder="e.g., 34C">
            </div>
          </div>
          <button
            class="w-full bg-brand-text text-white py-3 mt-4 font-semibold hover:bg-gray-800 transition-colors"
          >
            Save Measurements
          </button>
        </div>
      </div>
    </div>
    <div id="size-chart-modal" class="modal-container hidden">
      <div id="size-chart-overlay" class="modal-overlay"></div>
      <div class="modal-panel p-6 max-w-xl">
        <div class="flex justify-between items-center pb-3">
          <h3 class="text-xl font-serif font-semibold">Size Guide</h3>
          <button
            id="close-size-chart-btn"
            class="p-1 text-brand-gray hover:text-brand-text"
          >
            <i data-feather="x" class="w-5 h-5"></i>
          </button>
        </div>
        <div class="mt-4 modal-content-scrollable pr-2">
          <img src="images/chart.jpg" alt="Size Chart" class="w-full" />
        </div>
      </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        feather.replace();

        // --- STATE & ELEMENT SELECTORS ---
        let cart = [];
        // Menu Sidebar
        const sidebar = document.getElementById("sidebar");
        const openSidebarBtn = document.getElementById("open-sidebar-btn");
        const closeSidebarBtn = document.getElementById("close-sidebar-btn");
        const sidebarOverlay = document.getElementById("sidebar-overlay");
        // Cart Sidebar
        const cartSidebar = document.getElementById("cart-sidebar");
        const openCartBtn = document.getElementById("open-cart-btn");
        const closeCartBtn = document.getElementById("close-cart-btn");
        const cartOverlay = document.getElementById("cart-overlay");
        const cartItemsContainer = document.getElementById(
          "cart-items-container"
        );
        const cartSubtotalEl = document.getElementById("cart-subtotal");
        const cartItemCountEl = document.getElementById("cart-item-count");
        const emptyCartMessage = document.getElementById("empty-cart-message");
        // Product Page
        const addToCartBtn = document.getElementById("add-to-cart-btn");
        const quantityMinusBtn = document.getElementById("quantity-minus");
        const quantityPlusBtn = document.getElementById("quantity-plus");
        const quantityDisplay = document.getElementById("quantity-display");
        // Custom Color elements
        const openCustomColorBtn = document.getElementById(
          "open-custom-color-btn"
        );
        const customColorContainer = document.getElementById(
          "custom-color-input-container"
        );
        const customColorInput = document.getElementById("custom-color");

        // --- MENU SIDEBAR LOGIC ---
        const openSidebar = () => {
          if (sidebar) {
            sidebar.classList.remove("-translate-x-full");
            document.body.classList.add("overflow-hidden");
          }
        };
        const closeSidebar = () => {
          if (sidebar) {
            sidebar.classList.add("-translate-x-full");
            if (
              cartSidebar &&
              !cartSidebar.classList.contains("translate-x-full")
            )
              return;
            document.body.classList.remove("overflow-hidden");
          }
        };
        openSidebarBtn.addEventListener("click", openSidebar);
        closeSidebarBtn.addEventListener("click", closeSidebar);
        sidebarOverlay.addEventListener("click", closeSidebar);

        // --- CART LOGIC ---
        const openCart = () => {
          if (cartSidebar) {
            cartSidebar.classList.remove("translate-x-full");
            document.body.classList.add("overflow-hidden");
          }
        };
        const closeCart = () => {
          if (cartSidebar) {
            cartSidebar.classList.add("translate-x-full");
            if (sidebar && !sidebar.classList.contains("-translate-x-full"))
              return;
            document.body.classList.remove("overflow-hidden");
          }
        };

        const renderCart = () => {
          cartItemsContainer.innerHTML = "";
          let subtotal = 0;
          let totalItems = 0;
          if (cart.length === 0) {
            cartItemsContainer.appendChild(emptyCartMessage);
            emptyCartMessage.style.display = "flex";
          } else {
            emptyCartMessage.style.display = "none";
            cart.forEach((item) => {
              subtotal += item.price * item.quantity;
              totalItems += item.quantity;
              const cartItemHTML = `
                            <div class="flex gap-4 py-4 border-b border-gray-200 last:border-b-0">
                                <img src="${item.image}" alt="${
                item.name
              }" class="w-20 h-24 object-cover">
                                <div class="flex-1 flex flex-col justify-between">
                                    <div><h4 class="font-semibold text-sm">${
                                      item.name
                                    }</h4><p class="text-xs text-brand-gray">${
                item.color
              } / ${item.size}</p></div>
                                    <div class="flex justify-between items-center mt-2"><p class="text-sm font-medium">$${(
                                      item.price * item.quantity
                                    ).toFixed(
                                      2
                                    )}</p><p class="text-xs text-brand-gray">Qty: ${
                item.quantity
              }</p></div>
                                </div>
                                <div class="flex flex-col items-center gap-2 self-start">
                                    <button class="remove-from-cart-btn p-1 text-brand-gray hover:text-brand-red" data-id="${
                                      item.id
                                    }">
                                        <i data-feather="trash-2" class="w-4 h-4"></i>
                                    </button>
                                    <a href="cart.html" class="p-1 text-brand-gray hover:text-brand-text">
                                        <i data-feather="edit-3" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </div>`;
              cartItemsContainer.insertAdjacentHTML("beforeend", cartItemHTML);
            });
          }
          cartSubtotalEl.textContent = `$${subtotal.toFixed(2)}`;
          cartItemCountEl.textContent = totalItems;
          cartItemCountEl.style.display = totalItems > 0 ? "flex" : "none";
          feather.replace();
        };

        const handleAddToCart = () => {
          const name =
            document.getElementById("product-name").textContent.trim() ||
            "Celeste Ruched Maxi Dress";
          const price = parseFloat(
            document.getElementById("product-price").dataset.price
          );
          const image = document.getElementById("main-product-image").src;

          let color;
          const activeColorSwatch = document.querySelector(
            "#color-selector .active-color"
          );
          const customColorValue = customColorInput
            ? customColorInput.value.trim()
            : "";

          if (
            customColorValue &&
            customColorContainer &&
            !customColorContainer.classList.contains("is-closed")
          ) {
            color = customColorValue;
          } else if (activeColorSwatch) {
            color = activeColorSwatch.dataset.color;
          }

          if (!color || color.trim() === "") {
            alert("Please select or enter a valid color.");
            return;
          }

          const size = document.querySelector(
            "#size-selector .active-size"
          )?.textContent;
          const quantity = parseInt(quantityDisplay.textContent);
          if (!size) {
            alert("Please select a size.");
            return;
          }
          const itemId = `${name}-${color}-${size}`
            .toLowerCase()
            .replace(/\s+/g, "-");
          const existingItem = cart.find((item) => item.id === itemId);
          if (existingItem) {
            existingItem.quantity += quantity;
          } else {
            cart.push({
              id: itemId,
              name,
              price,
              image,
              color,
              size,
              quantity,
            });
          }
          renderCart();
          openCart();
        };

        cartItemsContainer.addEventListener("click", (e) => {
          const removeBtn = e.target.closest(".remove-from-cart-btn");
          if (removeBtn) {
            cart = cart.filter((item) => item.id !== removeBtn.dataset.id);
            renderCart();
          }
        });
        quantityPlusBtn.addEventListener(
          "click",
          () =>
            (quantityDisplay.textContent =
              parseInt(quantityDisplay.textContent) + 1)
        );
        quantityMinusBtn.addEventListener("click", () => {
          if (parseInt(quantityDisplay.textContent) > 1) {
            quantityDisplay.textContent =
              parseInt(quantityDisplay.textContent) - 1;
          }
        });
        addToCartBtn.addEventListener("click", handleAddToCart);
        openCartBtn.addEventListener("click", openCart);
        closeCartBtn.addEventListener("click", closeCart);
        cartOverlay.addEventListener("click", closeCart);

        // --- IMAGE GALLERY & HERO LOGIC ---
        const mainImage = document.getElementById("main-product-image");
        const thumbnails = document.querySelectorAll(".thumbnail-img");
        thumbnails.forEach((thumb) => {
          thumb.addEventListener("click", () => {
            mainImage.src = thumb.src;
            thumbnails.forEach((t) => t.classList.remove("active-thumbnail"));
            thumb.classList.add("active-thumbnail");
          });
        });

        // --- COLOR SELECTOR LOGIC ---
        const colorSwatches = document.querySelectorAll(".color-swatch");
        const selectedColorName = document.getElementById(
          "selected-color-name"
        );
        colorSwatches.forEach((swatch) => {
          swatch.addEventListener("click", () => {
            const newImageSrc = swatch.dataset.image;
            mainImage.src = newImageSrc;
            if (thumbnails[0]) thumbnails[0].src = newImageSrc;
            selectedColorName.textContent = swatch.dataset.color;
            colorSwatches.forEach((s) => s.classList.remove("active-color"));
            swatch.classList.add("active-color");
            thumbnails.forEach((t) => t.classList.remove("active-thumbnail"));
            if (thumbnails[0]) thumbnails[0].classList.add("active-thumbnail");

            // MODIFIED: Hide custom color input using our new animation class
            if (customColorContainer)
              customColorContainer.classList.add("is-closed");
            if (customColorInput) customColorInput.value = "";
          });
        });

        // --- SIZE SELECTOR LOGIC ---
        const sizeBtns = document.querySelectorAll(".size-btn");
        sizeBtns.forEach((btn) => {
          btn.addEventListener("click", () => {
            sizeBtns.forEach((b) => b.classList.remove("active-size"));
            btn.classList.add("active-size");
          });
        });

        // --- MODIFIED: CUSTOM COLOR INPUT LOGIC ---
        if (openCustomColorBtn) {
          openCustomColorBtn.addEventListener("click", (e) => {
            e.preventDefault();

            // Toggle our new animation class
            const isNowClosed =
              customColorContainer.classList.toggle("is-closed");

            // If the container was just opened (the 'is-closed' class was removed)
            if (!isNowClosed) {
              colorSwatches.forEach((s) => s.classList.remove("active-color"));
              if (selectedColorName) selectedColorName.textContent = "Custom";
              if (customColorInput) customColorInput.focus();
            }
          });
        }
        if (customColorInput) {
          customColorInput.addEventListener("input", () => {
            // If user starts typing, ensure swatches are deselected
            if (customColorInput.value.trim() !== "") {
              colorSwatches.forEach((s) => s.classList.remove("active-color"));
              if (selectedColorName) selectedColorName.textContent = "Custom";
            }
          });
        }

        // --- MODAL LOGIC ---
        const modalElements = [
          {
            modal: document.getElementById("custom-size-modal"),
            openBtn: document.getElementById("open-custom-size-btn"),
            closeBtn: document.getElementById("close-custom-size-btn"),
            overlay: document.getElementById("custom-size-overlay"),
          },
          {
            modal: document.getElementById("size-chart-modal"),
            openBtn: document.getElementById("open-size-chart-btn"),
            closeBtn: document.getElementById("close-size-chart-btn"),
            overlay: document.getElementById("size-chart-overlay"),
          },
        ];
        const openModal = (modal) => {
          if (modal) {
            modal.classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
          }
        };
        const closeModal = (modal) => {
          if (modal) {
            modal.classList.add("hidden");
            if (!document.querySelector(".modal-container:not(.hidden)")) {
              document.body.classList.remove("overflow-hidden");
            }
          }
        };
        modalElements.forEach(({ modal, openBtn, closeBtn, overlay }) => {
          if (openBtn) {
            openBtn.addEventListener("click", (e) => {
              e.preventDefault();
              if (modal.id === "custom-size-modal")
                sizeBtns.forEach((b) => b.classList.remove("active-size"));
              openModal(modal);
            });
          }
          if (closeBtn)
            closeBtn.addEventListener("click", () => closeModal(modal));
          if (overlay)
            overlay.addEventListener("click", () => closeModal(modal));
        });

        // --- GLOBAL KEYDOWN LISTENER ---
        document.addEventListener("keydown", (event) => {
          if (event.key === "Escape") {
            closeSidebar();
            closeCart();
            modalElements.forEach(({ modal }) => {
              if (modal && !modal.classList.contains("hidden"))
                closeModal(modal);
            });
          }
        });

        // --- INITIAL RENDER ---
        renderCart();
      });
    </script>
  </body>
</html>