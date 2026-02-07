<?php
if (!isset($base_path)) {
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($base_path === '' || $base_path === '.') $base_path = '/readiwork';
}
?>
<footer class="rw-footer">
  <div class="container">
    <div class="rw-footer-grid">
      <!-- Brand -->
      <div class="rw-footer-brand">
        <div class="rw-footer-logo">
          <a href="<?= $base_path ?>/" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
            <svg width="36" height="36" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M100 10L185 50V110C185 155 148 185 100 195C52 185 15 155 15 110V50L100 10Z"
                    fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="10" stroke-linejoin="round"/>
              <path d="M55 55V150" stroke="#fff" stroke-width="14" stroke-linecap="round"/>
              <path d="M55 55H95C115 55 130 67 130 82C130 97 115 109 95 109H55"
                    stroke="#fff" stroke-width="14" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
              <path d="M85 109L75 130L95 150L155 75"
                    stroke="#22c55e" stroke-width="14" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
            <span class="rw-footer-logo-text"><span style="font-weight:800">R</span>eadiwork <span class="rw-ai-badge" style="background:rgba(34,197,94,0.2);color:#22c55e;font-size:0.6rem;padding:2px 6px;border-radius:4px;margin-left:4px;">AI</span></span>
          </a>
        </div>
        <p class="rw-footer-desc">Africa's AI-powered verification and risk intelligence platform. Instant identity checks, credit reports, and compliance screening powered by advanced AI algorithms.</p>
        <div class="rw-footer-badge">
          <i class="fa-solid fa-lock"></i>
          <span>256-bit SSL Encrypted</span>
        </div>
      </div>

      <!-- Services -->
      <div class="rw-footer-col">
        <h4>AI Services</h4>
        <a href="<?= $base_path ?>/verification.php?service=id-verification">ID Verification</a>
        <a href="<?= $base_path ?>/verification.php?service=crb-clearance">CRB Clearance</a>
        <a href="<?= $base_path ?>/verification.php?service=credit-score">Credit Score</a>
        <a href="<?= $base_path ?>/verification.php?service=loan-eligibility">Loan Eligibility</a>
        <a href="<?= $base_path ?>/verification.php?service=tenant-screening">Tenant Screening</a>
        <a href="<?= $base_path ?>/services.php">View All Services</a>
      </div>

      <!-- Company -->
      <div class="rw-footer-col">
        <h4>Company</h4>
        <a href="<?= $base_path ?>/developers/">API Documentation</a>
        <a href="<?= $base_path ?>/support/">Support</a>
        <a href="<?= $base_path ?>/privacy.html">Privacy Policy</a>
        <a href="<?= $base_path ?>/terms.html">Terms of Service</a>
      </div>

      <!-- Contact -->
      <div class="rw-footer-col">
        <h4>Get In Touch</h4>
        <a href="mailto:support@readi.work"><i class="fa-solid fa-envelope"></i> support@readi.work</a>
        <a href="tel:+254700000000"><i class="fa-solid fa-phone"></i> +254 700 000 000</a>
        <a href="#"><i class="fa-solid fa-location-dot"></i> Nairobi, Kenya</a>
      </div>
    </div>

    <div class="rw-footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> Readiwork AI. All rights reserved.</p>
      <div class="rw-footer-providers">
        <span><i class="fa-solid fa-robot"></i> AI-Powered</span>
        <span><i class="fa-solid fa-building-columns"></i> Metropol CRB</span>
        <span><i class="fa-solid fa-shield-check"></i> YouVerify</span>
      </div>
    </div>
  </div>
</footer>
