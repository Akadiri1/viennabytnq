<!-- V2 Footer -->
<footer class="v2-footer">
  <div class="v2-container">
    <div class="v2-footer__grid">
      <!-- Brand & Newsletter -->
      <div>
        <div class="v2-footer__logo">
          <a href="/home">
            <img src="<?=$logo_directory?>" alt="<?= htmlspecialchars($site_name) ?>">
          </a>
        </div>
        <p class="v2-footer__newsletter-text">
          Subscribe to our newsletter for exclusive offers, new arrivals, and styling inspiration.
        </p>
        <form class="v2-footer__newsletter-form" id="v2-newsletter-form">
          <input type="email" name="email" id="v2-newsletter-email" placeholder="Email address" required>
          <button type="submit" aria-label="Subscribe" id="v2-newsletter-btn">
            <i class="fa-solid fa-arrow-right"></i>
          </button>
        </form>
        <p id="v2-newsletter-msg" style="font-size:0.75rem; margin-top:8px; display:none;"></p>
      </div>

      <!-- Quick Links -->
      <div>
        <h4 class="v2-footer__heading">Shop</h4>
        <div class="v2-footer__links">
          <a href="/shop">All Products</a>
          <a href="/shop">New Arrivals</a>
          <a href="/shop">Collections</a>
        </div>
      </div>

      <!-- Company -->
      <div>
        <h4 class="v2-footer__heading">Company</h4>
        <div class="v2-footer__links">
          <a href="/about">About Us</a>
          <a href="/contact-us">Contact Us</a>
          <a href="/privacy">Privacy Policy</a>
        </div>
      </div>

      <!-- Account -->
      <div>
        <h4 class="v2-footer__heading">Account</h4>
        <div class="v2-footer__links">
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/user-dashboard">My Account</a>
            <a href="/view-cart">Shopping Cart</a>
            <a href="/logout">Sign Out</a>
          <?php else: ?>
            <a href="/login">Login</a>
            <a href="/register">Create Account</a>
            <a href="/view-cart">Shopping Cart</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Bottom bar -->
    <div class="v2-footer__bottom">
      <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. All rights reserved.</p>

      <div class="v2-footer__social">
        <?php if (isset($socialLinks) && is_array($socialLinks)): ?>
          <?php foreach ($socialLinks as $link): ?>
            <a href="<?= htmlspecialchars($link['input_link']) ?>" target="_blank" title="<?= htmlspecialchars($link['input_name']) ?>">
              <i class="<?= htmlspecialchars($link['input_icon']) ?>"></i>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</footer>

<!-- Newsletter AJAX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const nlForm = document.getElementById('v2-newsletter-form');
  if (nlForm) {
    nlForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const email = document.getElementById('v2-newsletter-email').value.trim();
      const msg = document.getElementById('v2-newsletter-msg');
      const btn = document.getElementById('v2-newsletter-btn');
      if (!email) return;

      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

      fetch('/contact-backend', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: 'newsletter', email: email })
      })
      .then(r => r.json())
      .then(data => {
        msg.style.display = 'block';
        if (data.status === 'success') {
          msg.style.color = '#2D6A4F';
          msg.textContent = data.message || 'Subscribed successfully!';
          document.getElementById('v2-newsletter-email').value = '';
        } else {
          msg.style.color = '#C0392B';
          msg.textContent = data.message || 'Something went wrong.';
        }
      })
      .catch(() => {
        msg.style.display = 'block';
        msg.style.color = '#C0392B';
        msg.textContent = 'Network error. Please try again.';
      })
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-arrow-right"></i>';
        setTimeout(() => { msg.style.display = 'none'; }, 5000);
      });
    });
  }
});
</script>

</body>
</html>
