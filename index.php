<?php
require_once 'config.php';

function get_service_price($key) {
    return service_price($key) ?? 1;
}

// Dynamic base path
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '.') $base_path = '';
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Readiwork AI — Africa's AI-Powered Verification Platform</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Africa's leading AI-powered verification platform with 61+ services. Identity verification, credit checks, CRB clearance, business verification — powered by AI, Metropol CRB & YouVerify.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<!-- Unified Theme CSS -->
<link rel="stylesheet" href="<?= $base_path ?>/assets/css/theme.css">

<!-- Fallback: show all sections even if JS fails -->
<noscript>
<style>
  .rw-reveal, .rw-reveal-left, .rw-reveal-right, .rw-reveal-scale,
  .rw-stagger > * { opacity: 1 !important; transform: none !important; }
</style>
</noscript>
</head>
<body>

<!-- Scroll Progress Bar -->
<div class="rw-scroll-progress" id="scrollProgress"></div>

<!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>

<!-- ===== HERO ===== -->
<section class="rw-hero">
  <!-- Animated blobs -->
  <div class="rw-hero-blob rw-hero-blob-1"></div>
  <div class="rw-hero-blob rw-hero-blob-2"></div>
  <div class="rw-hero-blob rw-hero-blob-3"></div>

  <!-- Floating particles -->
  <div class="rw-particles" id="rwParticles"></div>

  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7 rw-hero-content">
        <div class="rw-hero-badge">
          <span class="rw-badge-pulse"></span>
          <i class="fa-solid fa-robot" style="color:var(--green-500)"></i>
          AI-Powered &mdash; Trusted by 500K+ verifications across Africa
        </div>
        <h1>AI-Powered<br>Verification,<br><span id="heroTyping"></span><span class="rw-type-cursor"></span></h1>
        <p class="rw-hero-sub">
          61+ AI-driven verification services in one platform. Our intelligent algorithms instantly verify IDs, analyze credit scores, screen tenants, and assess risk — all in under 5 seconds.
        </p>
        <div class="rw-hero-btns">
          <a href="<?= $base_path ?>/services.php" class="rw-btn-primary">
            <span>Browse AI Services</span> <i class="fa-solid fa-arrow-right"></i>
          </a>
          <a href="#services" class="rw-btn-outline">
            <i class="fa-solid fa-bolt"></i> View Services
          </a>
        </div>
      </div>

      <div class="col-lg-5 rw-hero-visual">
        <div class="row g-3">
          <div class="col-6" style="opacity:0; animation: rwFadeUp 0.6s ease 1s forwards;">
            <div class="rw-stat-card">
              <div class="rw-stat-card-icon"><i class="fa-solid fa-robot"></i></div>
              <h3><span class="rw-counter" data-target="61">0</span>+</h3>
              <p>AI Services</p>
            </div>
          </div>
          <div class="col-6" style="opacity:0; animation: rwFadeUp 0.6s ease 1.15s forwards; margin-top: 24px;">
            <div class="rw-stat-card">
              <div class="rw-stat-card-icon"><i class="fa-solid fa-bolt"></i></div>
              <h3>&lt;<span class="rw-counter" data-target="5">0</span>s</h3>
              <p>AI Response Time</p>
            </div>
          </div>
          <div class="col-6" style="opacity:0; animation: rwFadeUp 0.6s ease 1.3s forwards;">
            <div class="rw-stat-card">
              <div class="rw-stat-card-icon"><i class="fa-solid fa-shield-check"></i></div>
              <h3><span class="rw-counter" data-target="500">0</span>K+</h3>
              <p>AI Verifications</p>
            </div>
          </div>
          <div class="col-6" style="opacity:0; animation: rwFadeUp 0.6s ease 1.45s forwards; margin-top: 24px;">
            <div class="rw-stat-card">
              <div class="rw-stat-card-icon"><i class="fa-solid fa-brain"></i></div>
              <h3><span class="rw-counter" data-target="99">0</span>.9%</h3>
              <p>AI Accuracy</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== TRUST BAR ===== -->
<section class="rw-trust-bar">
  <div class="container">
    <div class="rw-trust-items rw-stagger rw-reveal-trigger">
      <div class="rw-trust-item"><i class="fa-solid fa-brain"></i> AI-Powered Engine</div>
      <div class="rw-trust-item"><i class="fa-solid fa-lock"></i> Bank-Grade Security</div>
      <div class="rw-trust-item"><i class="fa-solid fa-database"></i> Official Government Data</div>
      <div class="rw-trust-item"><i class="fa-solid fa-mobile-screen"></i> M-Pesa Payments</div>
      <div class="rw-trust-item"><i class="fa-solid fa-globe-africa"></i> Pan-African Coverage</div>
      <div class="rw-trust-item"><i class="fa-solid fa-certificate"></i> DPA Compliant</div>
    </div>
  </div>
</section>

<!-- ===== POPULAR SERVICES ===== -->
<section class="rw-services" id="services">
  <div class="container">
    <div class="rw-section-header rw-reveal rw-reveal-trigger">
      <div class="rw-section-label"><i class="fa-solid fa-robot"></i> Most Popular</div>
      <h2 class="rw-section-title">Top AI Verification Services</h2>
      <p class="rw-section-desc">Our most-used services — instant results powered by AI</p>
    </div>

    <div class="row g-4 rw-stagger rw-reveal-trigger">
      <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=id-verification" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-id-card"></i></div><h4>AI National ID Verification</h4><p>AI-powered Kenyan ID validation with IPRS database cross-referencing</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('id-verification'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
      <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=crb-clearance" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-shield-halved"></i></div><h4>AI CRB Clearance Check</h4><p>AI-analyzed credit bureau status with risk assessment</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('crb-clearance'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
      <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=credit-score" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-chart-line"></i></div><h4>AI Credit Score Analysis</h4><p>AI-generated credit score with predictive risk modeling</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('credit-score'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
      <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-ke-id" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-fingerprint"></i></div><h4>AI Enhanced ID Verification</h4><p>AI-powered ID verification with biometric matching</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-id'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
      <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=loan-eligibility" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-coins"></i></div><h4>AI Loan Eligibility</h4><p>AI-powered loan qualification with predictive scoring</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('loan-eligibility'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
      <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=tenant-screening" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-house-user"></i></div><h4>AI Tenant Screening</h4><p>AI risk assessment for tenants with payment history analysis</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('tenant-screening'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
    </div>

    <div class="rw-view-all rw-reveal rw-reveal-trigger" style="margin-top:40px">
      <a href="<?= $base_path ?>/services.php">View All 61+ AI Services <i class="fa-solid fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- ===== CTA ===== -->
<section class="rw-cta">
  <div class="container">
    <div class="rw-cta-content rw-reveal rw-reveal-trigger">
      <h2>Ready to Experience AI Verification?</h2>
      <p>Join thousands of businesses using Readiwork AI for instant, accurate verification powered by artificial intelligence.</p>
      <a href="<?= $base_path ?>/services.php" class="rw-btn-primary"><span>Explore AI Services</span> <i class="fa-solid fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Unified JS -->
<script src="<?= $base_path ?>/assets/js/main.js"></script>
</body>
</html>
