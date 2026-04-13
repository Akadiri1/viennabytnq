<?php
$v2_page_title = '404 - Page Not Found | ' . $site_name;
$v2_page_id = '404';
$v2_header_transparent = false;
include APP_PATH."/views/includes/header_v2.php";
?>

<main class="v2-404">
  <h1>404</h1>
  <p>The page you're looking for doesn't exist.</p>
  <a href="/home" class="v2-btn v2-btn--primary">Back to Home</a>
  <a href="/shop" class="v2-btn v2-btn--secondary" style="margin-top: var(--v2-space-sm);">Browse Shop</a>
</main>

<?php include APP_PATH."/views/includes/footer_v2.php"; ?>
