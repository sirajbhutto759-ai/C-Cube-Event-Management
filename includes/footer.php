<?php
// ============================================================
// C Cube – Footer Include
// ============================================================
?>
<!-- ─── FOOTER ──────────────────────────────────────────────── -->
<footer class="footer">
  <div class="footer-top">
    <div class="container">
      <div class="footer-grid">
        <!-- Brand -->
        <div class="footer-col footer-brand-col">
          <div class="footer-logo">
            <span class="brand-icon">C³</span>
            <span class="brand-text">C <span class="brand-accent">Cube</span></span>
          </div>
          <p class="footer-tagline"><?= e(setting('site_tagline','Where Innovation Meets Competition')) ?></p>
          <div class="footer-social">
            <?php if ($fb = setting('facebook_url','#')): ?>
              <a href="<?= e($fb) ?>" class="social-link" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <?php endif; ?>
            <?php if ($ig = setting('instagram_url','#')): ?>
              <a href="<?= e($ig) ?>" class="social-link" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <?php endif; ?>
            <?php if ($tw = setting('twitter_url','#')): ?>
              <a href="<?= e($tw) ?>" class="social-link" target="_blank" rel="noopener" aria-label="Twitter/X"><i class="fab fa-x-twitter"></i></a>
            <?php endif; ?>
            <?php if ($li = setting('linkedin_url','#')): ?>
              <a href="<?= e($li) ?>" class="social-link" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-col">
          <h4 class="footer-heading">Quick Links</h4>
          <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>">Home</a></li>
            <li><a href="<?= BASE_URL ?>events.php">Explore Events</a></li>
            <li><a href="<?= BASE_URL ?>#about">About Us</a></li>
            <li><a href="<?= BASE_URL ?>#how-it-works">How It Works</a></li>
          </ul>
        </div>

        <!-- Account -->
        <div class="footer-col">
          <h4 class="footer-heading">Account</h4>
          <ul class="footer-links">
            <?php if (!isset($_SESSION['user'])): ?>
              <li><a href="<?= BASE_URL ?>register.php">Register</a></li>
              <li><a href="<?= BASE_URL ?>login.php">Login</a></li>
            <?php else: ?>
              <li><a href="<?= BASE_URL ?>participant/dashboard.php">Dashboard</a></li>
              <li><a href="<?= BASE_URL ?>participant/my-registrations.php">My Registrations</a></li>
              <li><a href="<?= BASE_URL ?>participant/profile.php">Profile</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Contact -->
        <div class="footer-col">
          <h4 class="footer-heading">Contact</h4>
          <ul class="footer-contact">
            <li><i class="fa fa-envelope"></i> <a href="mailto:<?= e(setting('contact_email','')) ?>"><?= e(setting('contact_email','hello@ccube.dev')) ?></a></li>
            <li><i class="fa fa-phone"></i> <?= e(setting('contact_phone','+92-300-0000000')) ?></li>
            <li><i class="fa fa-map-marker-alt"></i> <?= e(setting('contact_address','Pakistan')) ?></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      <p>&copy; <?= date('Y') ?> <?= e(setting('site_name','C Cube')) ?>. All rights reserved.</p>
      <p>Built with <i class="fa fa-heart text-accent"></i> for innovators.</p>
    </div>
  </div>
</footer>

<!-- Core JS -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script src="<?= BASE_URL ?>assets/js/animations.js"></script>
</body>
</html>
