<?php
$v2_page_title = 'Contact Us | ' . $site_name;
$v2_page_id = 'contact';
$v2_header_transparent = false;
include APP_PATH."/views/includes/header_v2.php";
?>

<main style="padding-top: var(--v2-header-height);">
  <div class="v2-section">
    <div class="v2-page-width" style="max-width: 900px;">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--v2-space-3xl);">

        <!-- Contact Info -->
        <div>
          <p class="v2-subheading" style="margin-bottom: var(--v2-space-md);">Get in Touch</p>
          <h1 style="font-size: 2rem; margin-bottom: var(--v2-space-xl);">Contact Us</h1>

          <div style="color: var(--v2-text-light); font-size: 0.875rem; line-height: 2;">
            <?php if (!empty($site_email)): ?>
              <p><strong style="color: var(--v2-text);">Email:</strong> <a href="mailto:<?= htmlspecialchars($site_email) ?>"><?= htmlspecialchars($site_email) ?></a></p>
            <?php endif; ?>
            <?php if (!empty($site_phone)): ?>
              <p><strong style="color: var(--v2-text);">Phone:</strong> <a href="tel:<?= htmlspecialchars($site_phone) ?>"><?= htmlspecialchars($site_phone) ?></a></p>
            <?php endif; ?>
            <?php if (!empty($site_address)): ?>
              <p><strong style="color: var(--v2-text);">Address:</strong> <?= htmlspecialchars($site_address) ?></p>
            <?php endif; ?>
          </div>

          <?php if (isset($socialLinks) && !empty($socialLinks)): ?>
          <div class="v2-footer__social" style="margin-top: var(--v2-space-xl);">
            <?php foreach ($socialLinks as $link): ?>
              <a href="<?= htmlspecialchars($link['input_link']) ?>" target="_blank" title="<?= htmlspecialchars($link['input_name']) ?>" style="color: var(--v2-text);">
                <i class="<?= htmlspecialchars($link['input_icon']) ?>"></i>
              </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Contact Form -->
        <div>
          <form id="contact-form">
            <div class="v2-form-group">
              <label class="v2-label" for="contact-name">Name</label>
              <input type="text" id="contact-name" name="name" class="v2-input" required placeholder="Your name">
            </div>
            <div class="v2-form-group">
              <label class="v2-label" for="contact-email">Email</label>
              <input type="email" id="contact-email" name="email" class="v2-input" required placeholder="your@email.com">
            </div>
            <div class="v2-form-group">
              <label class="v2-label" for="contact-subject">Subject</label>
              <input type="text" id="contact-subject" name="subject" class="v2-input" placeholder="Subject">
            </div>
            <div class="v2-form-group">
              <label class="v2-label" for="contact-message">Message</label>
              <textarea id="contact-message" name="message" class="v2-input" rows="5" required placeholder="Your message..." style="resize: vertical;"></textarea>
            </div>
            <button type="submit" class="v2-btn v2-btn--primary v2-btn--full" id="contact-submit-btn">
              <span class="btn-text">Send Message</span>
              <i class="fa-solid fa-spinner fa-spin btn-loader" style="display:none;"></i>
            </button>
            <p id="contact-msg" style="font-size:0.8125rem; margin-top: var(--v2-space-md); display:none; text-align:center;"></p>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<style>
@media (max-width: 768px) {
  main [style*="grid-template-columns: 1fr 1fr"] {
    grid-template-columns: 1fr !important;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('contact-form');
  if (!form) return;

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('contact-submit-btn');
    const msg = document.getElementById('contact-msg');
    const btnText = btn.querySelector('.btn-text');
    const btnLoader = btn.querySelector('.btn-loader');

    btn.disabled = true;
    btnText.textContent = 'Sending...';
    btnLoader.style.display = 'inline-block';

    const formData = new FormData(form);

    fetch('/contact-backend', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      msg.style.display = 'block';
      if (data.status === 'success') {
        msg.style.color = '#2D6A4F';
        msg.textContent = data.message || 'Message sent successfully!';
        form.reset();
      } else {
        msg.style.color = '#C0392B';
        msg.textContent = data.message || 'Failed to send. Please try again.';
      }
    })
    .catch(() => {
      msg.style.display = 'block';
      msg.style.color = '#C0392B';
      msg.textContent = 'Network error. Please try again.';
    })
    .finally(() => {
      btn.disabled = false;
      btnText.textContent = 'Send Message';
      btnLoader.style.display = 'none';
      setTimeout(() => { msg.style.display = 'none'; }, 6000);
    });
  });
});
</script>

<?php include APP_PATH."/views/includes/footer_v2.php"; ?>
