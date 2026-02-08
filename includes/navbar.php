<?php
// Determine base path dynamically
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '.') $base_path = '';
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
?>
<div class="rw-navbar" id="rwNavbar">
  <div class="container">
    <div class="rw-nav-inner">
      <!-- Logo -->
      <a class="rw-logo" href="<?= $base_path ?>/">
        <svg class="rw-logo-icon" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M100 10L185 50V110C185 155 148 185 100 195C52 185 15 155 15 110V50L100 10Z"
                fill="none" stroke="#14532d" stroke-width="10" stroke-linejoin="round" opacity="0.85"/>
          <path d="M55 55V150" stroke="#14532d" stroke-width="14" stroke-linecap="round"/>
          <path d="M55 55H95C115 55 130 67 130 82C130 97 115 109 95 109H55"
                stroke="#14532d" stroke-width="14" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          <path d="M85 109L75 130L95 150L155 75"
                stroke="#22c55e" stroke-width="14" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
        <span class="rw-logo-text">
          <span class="rw-logo-r">R</span>eadiwork
          <span class="rw-ai-badge">AI</span>
        </span>
      </a>

      <!-- Mobile Toggle -->
      <button class="rw-nav-toggle" id="navToggle" aria-label="Toggle navigation">
        <span></span><span></span><span></span>
      </button>

      <!-- Navigation -->
      <div class="rw-nav-links" id="navLinks">
        <a href="<?= $base_path ?>/" class="rw-nav-link <?php echo (rtrim($current_uri, '/') === rtrim($base_path, '/') || strpos($current_uri, 'index.php') !== false ? 'active' : ''); ?>">Home</a>
        <a href="<?= $base_path ?>/services.php" class="rw-nav-link <?php echo (strpos($current_uri, 'services.php') !== false ? 'active' : ''); ?>">Services</a>

        <!-- Dropdown -->
        <div class="rw-nav-dropdown" id="navDropdown">
          <a href="#" class="rw-nav-link rw-dropdown-toggle" id="dropdownToggle">
            Quick Verify
            <i class="fa-solid fa-chevron-down rw-arrow"></i>
          </a>
          <div class="rw-dropdown-menu" id="dropdownMenu">
            <div class="rw-dropdown-grid">
              <div class="rw-dropdown-col">
                <div class="rw-dropdown-heading"><i class="fa-solid fa-building-columns"></i> Metropol CRB</div>
                <a href="<?= $base_path ?>/verification.php?service=id-verification"><i class="fa-solid fa-id-card"></i> National ID Check</a>
                <a href="<?= $base_path ?>/verification.php?service=crb-clearance"><i class="fa-solid fa-shield-halved"></i> CRB Clearance</a>
                <a href="<?= $base_path ?>/verification.php?service=credit-score"><i class="fa-solid fa-chart-line"></i> Credit Score</a>
                <a href="<?= $base_path ?>/verification.php?service=loan-eligibility"><i class="fa-solid fa-coins"></i> Loan Eligibility</a>
                <a href="<?= $base_path ?>/verification.php?service=tenant-screening"><i class="fa-solid fa-house-user"></i> Tenant Screening</a>
              </div>
              <div class="rw-dropdown-col">
                <div class="rw-dropdown-heading"><i class="fa-solid fa-robot"></i> AI-Powered Verify</div>
                <a href="<?= $base_path ?>/verification.php?service=yv-ke-id"><i class="fa-solid fa-fingerprint"></i> Enhanced ID Verify</a>
                <a href="<?= $base_path ?>/verification.php?service=yv-ke-passport"><i class="fa-solid fa-passport"></i> Passport Verify</a>
                <a href="<?= $base_path ?>/verification.php?service=yv-ke-drivers-license"><i class="fa-solid fa-car"></i> Drivers License</a>
                <a href="<?= $base_path ?>/verification.php?service=yv-ke-plate-number"><i class="fa-solid fa-car-side"></i> Vehicle Plate</a>
                <a href="<?= $base_path ?>/verification.php?service=yv-ke-tax"><i class="fa-solid fa-receipt"></i> KRA PIN</a>
              </div>
            </div>
            <div class="rw-dropdown-footer">
              <a href="<?= $base_path ?>/services.php">View All 61+ AI Services <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </div>

        <a href="<?= $base_path ?>/developers/" class="rw-nav-link">API</a>
        <a href="<?= $base_path ?>/support/" class="rw-nav-link">Support</a>
        <a href="<?= $base_path ?>/services.php" class="rw-nav-cta">
          <span>Get Started</span>
          <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
    </div>
  </div>
</div>
