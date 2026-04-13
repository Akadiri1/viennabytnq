<?php
// Currency logic (same as v1)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('getLiveUsdToNgnRate')) {
function getLiveUsdToNgnRate() {
    $apis = [
        'https://open.er-api.com/v6/latest/USD',
        'https://api.exchangerate-api.com/v4/latest/USD'
    ];
    foreach ($apis as $apiUrl) {
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $response = @file_get_contents($apiUrl, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['rates']['NGN'])) {
                return floatval($data['rates']['NGN']);
            }
        }
    }
    return 1480;
}
} // end if !function_exists

if (!defined('USD_EXCHANGE_RATE')) {
    define('USD_EXCHANGE_RATE', getLiveUsdToNgnRate());
}

if (isset($_SESSION['currency'])) {
    $current_currency = $_SESSION['currency'];
} elseif (isset($_COOKIE['user_currency'])) {
    $current_currency = $_COOKIE['user_currency'];
} else {
    $current_currency = 'NGN';
}
?>
<!DOCTYPE html>
<html lang="en" class="v2">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#1A1A1A">
  <title><?= $v2_page_title ?? $site_name ?></title>
  <meta name="description" content="<?= $v2_page_description ?? htmlspecialchars($description) ?>">
  <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
  <link rel="apple-touch-icon-precomposed" type="image/png" sizes="152x152" href="<?=$logo_directory?>">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com/">
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- V2 Design System -->
  <link rel="stylesheet" href="/allcss/vienna-v2.css">

  <!-- OG Tags -->
  <meta property="og:site_name" content="<?= htmlspecialchars($site_name) ?>">
  <meta property="og:title" content="<?= $v2_page_title ?? $site_name ?>">
  <meta property="og:image" content="<?=$logo_directory?>">
  <meta property="og:type" content="website">
</head>
<body class="v2" data-page="<?= $v2_page_id ?? 'page' ?>">

<!-- Preloader -->
<div class="v2-preloader" id="v2-preloader">
  <img src="<?=$logo_directory?>" alt="<?= htmlspecialchars($site_name) ?>" class="v2-preloader__logo">
  <div class="v2-preloader__text"><?= htmlspecialchars($site_name) ?></div>
</div>

<!-- Sidebar Menu Drawer (like v1) -->
<div class="v2-sidebar" id="v2-sidebar">
  <div class="v2-sidebar__overlay" id="v2-sidebar-overlay"></div>
  <div class="v2-sidebar__panel">
    <div class="v2-sidebar__header">
      <h2 style="font-size: 1.25rem; font-weight: 600;">Menu</h2>
      <button id="v2-sidebar-close" aria-label="Close menu" style="font-size: 1.25rem; cursor:pointer; background:none; border:none;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <nav class="v2-sidebar__nav">
      <a href="/home"><i class="fa-solid fa-house"></i> Home</a>
      <a href="/shop"><i class="fa-solid fa-bag-shopping"></i> Shop</a>
      <a href="/about"><i class="fa-solid fa-circle-info"></i> About Us</a>
      <a href="/contact-us"><i class="fa-solid fa-envelope"></i> Contact</a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/user-dashboard"><i class="fa-solid fa-user"></i> My Account</a>
      <?php else: ?>
        <a href="/register"><i class="fa-solid fa-user"></i> Login / Register</a>
      <?php endif; ?>
      <a href="/privacy"><i class="fa-solid fa-truck"></i> Shipping Policy</a>
      <a href="/view-cart"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
    </nav>
    <div class="v2-sidebar__currency">
      <span style="font-size:0.65rem; font-weight:600; text-transform:uppercase; letter-spacing:0.15em; color:var(--v2-text-muted); display:block; margin-bottom:var(--v2-space-sm);">Currency</span>
      <div style="display:flex; gap:8px;">
        <a href="#" class="currency-link v2-sidebar__currency-btn <?= ($current_currency === 'NGN') ? 'active' : '' ?>" data-currency="NGN">NGN</a>
        <a href="#" class="currency-link v2-sidebar__currency-btn <?= ($current_currency === 'USD') ? 'active' : '' ?>" data-currency="USD">USD</a>
      </div>
    </div>
    <div class="v2-sidebar__footer">
      <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?></p>
    </div>
  </div>
</div>

<!-- Header -->
<header class="v2-header <?= ($v2_header_transparent ?? true) ? 'v2-header--transparent' : 'v2-header--scrolled' ?>" id="v2-header">
  <div class="v2-header__inner">
    <div class="v2-header__left">
      <!-- Sidebar menu toggle - mobile only -->
      <button class="v2-menu-toggle" id="v2-menu-toggle" aria-label="Open menu">
        <span></span>
        <span></span>
      </button>

      <a href="/home" class="v2-header__logo" aria-label="<?= htmlspecialchars($site_name) ?>">
        <img src="<?=$logo_directory?>" alt="<?= htmlspecialchars($site_name) ?>">
        <span class="v2-header__site-name"><?= htmlspecialchars($site_name) ?></span>
      </a>
    </div>

    <!-- Desktop nav links -->
    <nav class="v2-header__nav">
      <a href="/home">Home</a>
      <a href="/shop">Shop</a>
      <a href="/about">About</a>
      <a href="/contact-us">Contact</a>
    </nav>

    <div class="v2-header__right">
      <div class="v2-currency">
        <a href="#" class="currency-link <?= ($current_currency === 'NGN') ? 'active' : '' ?>" data-currency="NGN">NGN</a>
        <span style="opacity:0.5">/</span>
        <a href="#" class="currency-link <?= ($current_currency === 'USD') ? 'active' : '' ?>" data-currency="USD">USD</a>
      </div>

      <a href="<?= isset($_SESSION['user_id']) ? '/user-dashboard' : '/login' ?>" class="v2-header__action" title="<?= isset($_SESSION['user_id']) ? 'My Account' : 'Login' ?>">
        <i class="fa-regular fa-user"></i>
      </a>

      <a href="/view-cart" class="v2-header__action v2-cart-icon" id="v2-header-cart-link">
        <i class="fa-solid fa-bag-shopping"></i>
        <span id="v2-cart-count" class="v2-cart-badge">0</span>
      </a>
    </div>
  </div>
</header>

<script>
// Currency switcher logic
document.addEventListener('DOMContentLoaded', () => {
  const USD_RATE = <?= USD_EXCHANGE_RATE ?>;
  const INITIAL_CURRENCY = '<?= $current_currency ?>';
  const currencyLinks = document.querySelectorAll('.currency-link');

  function formatPrice(amount, currency) {
    if (currency === 'USD') {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
    }
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount).replace('NGN', "\u20A6");
  }

  function updateAllPrices(targetCurrency) {
    document.querySelectorAll('.price-display').forEach(el => {
      const ngnPrice = parseFloat(el.dataset.priceNgn);
      if (!isNaN(ngnPrice)) {
        let newPrice = targetCurrency === 'USD' ? ngnPrice / USD_RATE : ngnPrice;
        el.textContent = formatPrice(newPrice, targetCurrency);
      }
    });
  }

  currencyLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const targetCurrency = this.dataset.currency;
      currencyLinks.forEach(l => l.classList.remove('active'));
      document.querySelectorAll(`[data-currency="${targetCurrency}"]`).forEach(l => l.classList.add('active'));
      updateAllPrices(targetCurrency);
      const expiryDate = new Date();
      expiryDate.setDate(expiryDate.getDate() + 30);
      document.cookie = `user_currency=${targetCurrency}; expires=${expiryDate.toUTCString()}; path=/`;
      fetch('/currency', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ currency: targetCurrency }) });
    });
  });

  if (INITIAL_CURRENCY === 'USD') updateAllPrices('USD');

  // Header scroll behavior
  const header = document.getElementById('v2-header');
  if (header && header.classList.contains('v2-header--transparent')) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 80) {
        header.classList.remove('v2-header--transparent');
        header.classList.add('v2-header--scrolled');
      } else {
        header.classList.add('v2-header--transparent');
        header.classList.remove('v2-header--scrolled');
      }
    });
  }

  // Sidebar menu
  const menuToggle = document.getElementById('v2-menu-toggle');
  const sidebar = document.getElementById('v2-sidebar');
  const sidebarClose = document.getElementById('v2-sidebar-close');
  const sidebarOverlay = document.getElementById('v2-sidebar-overlay');

  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', () => sidebar.classList.add('is-open'));
    sidebarClose.addEventListener('click', () => sidebar.classList.remove('is-open'));
    sidebarOverlay.addEventListener('click', () => sidebar.classList.remove('is-open'));
  }

  // Preloader
  const preloader = document.getElementById('v2-preloader');
  let loaded = false, timerDone = false;
  function tryHide() {
    if (loaded && timerDone && preloader) {
      preloader.classList.add('loaded');
    }
  }
  window.addEventListener('load', () => { loaded = true; tryHide(); });
  setTimeout(() => { timerDone = true; tryHide(); }, 800);

  // Scroll animations
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.v2-fade-in').forEach(el => observer.observe(el));
  }

  // Cart count badge
  const cartBadge = document.getElementById('v2-cart-count');
  if (cartBadge) {
    fetch('/update-cart', { method: 'POST' })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success' && data.items && data.items.length > 0) {
          cartBadge.textContent = data.items.length;
          cartBadge.style.display = 'flex';
        } else {
          cartBadge.style.display = 'none';
        }
      })
      .catch(() => { cartBadge.style.display = 'none'; });
  }
});
</script>
