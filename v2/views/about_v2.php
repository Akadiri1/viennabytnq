<?php
$v2_page_title = 'About Us | ' . $site_name;
$v2_page_id = 'about';
$v2_header_transparent = false;
include APP_PATH."/views/includes/header_v2.php";
?>

<main style="padding-top: var(--v2-header-height);">
  <!-- Hero -->
  <section style="position:relative; height: 350px; background: url('/images/2.jpg') center/cover no-repeat;">
    <div style="position:absolute; inset:0; background:rgba(0,0,0,0.35);"></div>
    <div style="position:relative; z-index:1; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; color:white; padding: var(--v2-space-xl);">
      <h1 class="v2-heading-serif" style="font-size: clamp(2.5rem, 5vw, 4rem); color:white;">The <?= htmlspecialchars($site_name) ?> Story</h1>
      <p style="margin-top: var(--v2-space-md); font-size: 1rem; font-weight: 300; letter-spacing: 0.05em; max-width: 600px; opacity:0.9;">Crafting Modern Elegance for the Discerning Woman.</p>
    </div>
  </section>

  <!-- Our Philosophy -->
  <section class="v2-section">
    <div class="v2-page-width">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--v2-space-4xl); align-items: center;">
        <div>
          <h2 style="font-size: 1.75rem; margin-bottom: var(--v2-space-lg);">Our Philosophy</h2>
          <div style="color: var(--v2-text-light); font-size: 0.9375rem; line-height: 1.8;">
            <p style="margin-bottom: var(--v2-space-lg);">
              <?= htmlspecialchars($site_name) ?> is a ready-to-wear fashion label born from a desire to celebrate enduring style. In a world of fleeting trends, we create pieces that transcend seasons — wardrobe heirlooms designed for the woman who navigates life with grace, confidence, and a quiet sense of self.
            </p>
            <p style="margin-bottom: var(--v2-space-lg);">
              Inspired by the timeless sophistication of European classicism and the dynamic energy of the modern woman, our collections are a study in contrasts: structured yet fluid, bold yet understated, contemporary yet classic.
            </p>
            <p>
              We believe that true luxury lies in the details: the perfect fit, the feel of exquisite fabric against the skin, and the impeccable craftsmanship that ensures a garment is loved for years to come.
            </p>
          </div>
        </div>
        <div style="aspect-ratio: 4/5; overflow: hidden;">
          <img src="/images/FUJIFILM-0332.jpg" alt="Craftsmanship" style="width:100%; height:100%; object-fit:cover;">
        </div>
      </div>
    </div>
  </section>

  <!-- Founder Quote -->
  <section style="background: var(--v2-bg-alt); padding: var(--v2-space-4xl) 0;">
    <div class="v2-page-width v2-text-center" style="max-width: 800px;">
      <p class="v2-subheading" style="margin-bottom: var(--v2-space-md);">A Note From The Founder</p>
      <blockquote class="v2-heading-serif" style="font-size: clamp(1.25rem, 2.5vw, 1.75rem); font-style: italic; line-height: 1.5; margin-bottom: var(--v2-space-lg);">
        "Fashion, to me, is a language. It's how we introduce ourselves without saying a word. I created <?= htmlspecialchars($site_name) ?> to give every woman a vocabulary of grace and strength, crafting pieces that empower her to tell her own beautiful story."
      </blockquote>
      <p style="font-weight: 600; letter-spacing: 0.1em; font-size: 0.875rem;">— TNQ, Founder &amp; Creative Director</p>
    </div>
  </section>

  <!-- Craftsmanship -->
  <section class="v2-section">
    <div class="v2-page-width">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--v2-space-4xl); align-items: center;">
        <div style="aspect-ratio: 4/5; overflow: hidden;">
          <img src="/images/P_ST5396.jpg" alt="Behind the scenes" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <div>
          <h2 style="font-size: 1.75rem; margin-bottom: var(--v2-space-lg);">Our Commitment to Craft</h2>
          <div style="color: var(--v2-text-light); font-size: 0.9375rem; line-height: 1.8;">
            <p style="margin-bottom: var(--v2-space-lg);">
              Every <?= htmlspecialchars($site_name) ?> garment begins with a thoughtful design process and a commitment to unparalleled quality. We source premium materials from around the world, selecting fabrics not only for their beauty but for their durability and comfort.
            </p>
            <ul style="list-style: disc; padding-left: 1.5rem; margin-bottom: var(--v2-space-lg);">
              <li style="margin-bottom: var(--v2-space-sm);"><strong style="color:var(--v2-text);">Impeccable Tailoring:</strong> Our silhouettes are meticulously constructed to flatter the female form.</li>
              <li style="margin-bottom: var(--v2-space-sm);"><strong style="color:var(--v2-text);">Conscious Production:</strong> We partner with skilled artisans and ethical manufacturers.</li>
              <li><strong style="color:var(--v2-text);">Lasting Quality:</strong> From seams to buttons, every detail is crafted to endure.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="v2-section v2-text-center" style="padding-bottom: var(--v2-space-5xl);">
    <div class="v2-page-width" style="max-width: 600px;">
      <h2 style="font-size: 1.75rem; margin-bottom: var(--v2-space-md);">Join Our World</h2>
      <p style="color: var(--v2-text-light); margin-bottom: var(--v2-space-xl);">
        Discover a wardrobe that is as timeless and unique as you are. Explore our latest collection and find the pieces that will journey with you.
      </p>
      <a href="/shop" class="v2-btn v2-btn--primary">Explore the Collection</a>
    </div>
  </section>
</main>

<style>
@media (max-width: 768px) {
  [style*="grid-template-columns: 1fr 1fr"] {
    grid-template-columns: 1fr !important;
  }
}
</style>

<?php include APP_PATH."/views/includes/footer_v2.php"; ?>
