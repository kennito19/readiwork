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
          <a href="#how-it-works" class="rw-btn-outline">
            <i class="fa-solid fa-play"></i> How AI Works
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

<!-- ===== HOW AI WORKS ===== -->
<section class="rw-providers" id="how-it-works">
  <div class="container">
    <div class="rw-section-header rw-reveal rw-reveal-trigger">
      <div class="rw-section-label"><i class="fa-solid fa-microchip"></i> How Our AI Works</div>
      <h2 class="rw-section-title">Intelligent Verification in 3 Steps</h2>
      <p class="rw-section-desc">Our AI engine connects to official databases, analyzes data patterns, and delivers verified results in seconds</p>
    </div>
    <div class="row g-4 rw-stagger rw-reveal-trigger">
      <div class="col-md-4">
        <div class="rw-feature-card" style="text-align:center">
          <div class="rw-feature-icon" style="margin:0 auto 20px"><i class="fa-solid fa-keyboard"></i></div>
          <h3>1. Enter Details</h3>
          <p>Input the ID number, name, or document you need verified. Our AI pre-validates the format instantly.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="rw-feature-card" style="text-align:center">
          <div class="rw-feature-icon" style="margin:0 auto 20px;background:var(--green-900);color:#fff"><i class="fa-solid fa-brain"></i></div>
          <h3>2. AI Processes</h3>
          <p>Our AI engine queries official databases (IPRS, NTSA, KRA, CRB), cross-references data, and generates a risk assessment.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="rw-feature-card" style="text-align:center">
          <div class="rw-feature-icon" style="margin:0 auto 20px"><i class="fa-solid fa-chart-column"></i></div>
          <h3>3. Get AI Report</h3>
          <p>Receive a comprehensive AI-generated verification report with confidence scores, risk flags, and actionable insights.</p>
        </div>
      </div>
    </div>
    <div style="text-align:center;margin-top:40px">
      <div class="rw-provider-grid" style="justify-content:center">
        <div class="rw-provider-card">
          <div class="rw-provider-icon metropol"><i class="fa-solid fa-building-columns"></i></div>
          <div class="rw-provider-info"><h4>Metropol CRB</h4><p>Kenya's credit reference bureau</p></div>
        </div>
        <div class="rw-provider-card">
          <div class="rw-provider-icon youverify"><i class="fa-solid fa-shield-check"></i></div>
          <div class="rw-provider-info"><h4>YouVerify</h4><p>Pan-African identity verification</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== SERVICES ===== -->
<section class="rw-services" id="services">
  <div class="container">
    <div class="rw-section-header rw-reveal rw-reveal-trigger">
      <div class="rw-section-label"><i class="fa-solid fa-robot"></i> AI-Powered Services</div>
      <h2 class="rw-section-title">AI Verification Services</h2>
      <p class="rw-section-desc">Browse by category — 61+ AI-powered services covering every verification need in Kenya and Africa</p>
    </div>

    <div class="rw-tabs rw-reveal rw-reveal-trigger">
      <button class="rw-tab active" data-tab="popular">Most Popular</button>
      <button class="rw-tab" data-tab="kenya">Kenya Services</button>
      <button class="rw-tab" data-tab="credit">Credit & Finance</button>
      <button class="rw-tab" data-tab="business">Business & Employment</button>
      <button class="rw-tab" data-tab="africa">Pan-African</button>
    </div>

    <!-- POPULAR -->
    <div class="rw-tab-panel active" id="panel-popular">
      <div class="row g-4 rw-stagger rw-reveal-trigger">
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=id-verification" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-id-card"></i></div><h4>AI National ID Verification</h4><p>AI-powered Kenyan ID validation with IPRS database cross-referencing</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('id-verification'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=crb-clearance" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-shield-halved"></i></div><h4>AI CRB Clearance Check</h4><p>AI-analyzed credit bureau status with risk assessment</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('crb-clearance'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=credit-score" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-chart-line"></i></div><h4>AI Credit Score Analysis</h4><p>AI-generated credit score with predictive risk modeling</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('credit-score'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-ke-id" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-fingerprint"></i></div><h4>AI Enhanced ID Verification</h4><p>AI-powered ID verification with biometric matching</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-id'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=loan-eligibility" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-coins"></i></div><h4>AI Loan Eligibility</h4><p>AI-powered loan qualification with predictive scoring</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('loan-eligibility'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=tenant-screening" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-house-user"></i></div><h4>AI Tenant Screening</h4><p>AI risk assessment for tenants with payment history analysis</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('tenant-screening'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
      </div>
    </div>

    <!-- KENYA -->
    <div class="rw-tab-panel" id="panel-kenya">
      <div class="row g-4">
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/id-verification.php" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-id-card"></i></div><h4>AI National ID Verification</h4><p>AI-powered Kenyan National ID verification with IPRS</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-id'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/passport.php" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-passport"></i></div><h4>AI Passport Verification</h4><p>AI-verified Kenyan international passport</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-passport'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/alien-verification.php" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-id-card-clip"></i></div><h4>AI Alien ID Verification</h4><p>AI verification of Alien/Foreigner IDs in Kenya</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-alien-id'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/license.php" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-car"></i></div><h4>AI Drivers License Verification</h4><p>AI-powered license verification with NTSA</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-drivers-license'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/carsearch.php" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-car-side"></i></div><h4>AI Plate Number Verification</h4><p>AI vehicle registration verification with NTSA</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-plate-number'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/account-verification.php" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-building-columns"></i></div><h4>AI Bank Account Verification</h4><p>AI-powered Kenyan bank account validation</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-bank-account'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/pin-verification.php" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-receipt"></i></div><h4>AI KRA PIN Verification</h4><p>AI tax identification verification with KRA</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-tax'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/phone-verification.php" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-mobile-screen"></i></div><h4>AI Phone Verification</h4><p>AI phone ownership and validity check</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-phone'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/employment-verification.php" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-briefcase"></i></div><h4>AI Employment Verification</h4><p>AI-verified employment status and history</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-employment'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
      </div>
    </div>

    <!-- CREDIT & FINANCE -->
    <div class="rw-tab-panel" id="panel-credit">
      <div class="row g-4">
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=crb-certificate" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-certificate"></i></div><h4>AI CRB Certificate</h4><p>AI-generated digital CRB status certificate</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('crb-certificate'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=full-credit-history" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-scroll"></i></div><h4>AI Credit History Report</h4><p>AI-analyzed complete credit report</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('full-credit-history'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=creditworthiness" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-award"></i></div><h4>AI Creditworthiness Score</h4><p>AI credit assessment for visa & tenders</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('creditworthiness'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=debt-exposure" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-scale-unbalanced"></i></div><h4>AI Debt Analysis</h4><p>AI-powered over-borrowing & debt assessment</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('debt-exposure'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=financial-stress" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-heart-pulse"></i></div><h4>AI Financial Stress Detection</h4><p>AI early detection of financial stress indicators</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('financial-stress'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=guarantor-verify" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-handshake"></i></div><h4>AI Guarantor Verification</h4><p>AI-enhanced guarantor creditworthiness check</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('guarantor-verify'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
      </div>
    </div>

    <!-- BUSINESS -->
    <div class="rw-tab-panel" id="panel-business">
      <div class="row g-4">
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-ke-business" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-building"></i></div><h4>AI Business Verification</h4><p>AI-verified business registration with BRS Kenya</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-business'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=business-owner-check" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-user-tie"></i></div><h4>AI Business Owner Check</h4><p>AI-analyzed SME owner credit & capacity</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('business-owner-check'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-ke-employment" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-briefcase"></i></div><h4>AI Employment Verification</h4><p>AI-verified employment status & history</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-employment'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=employment-screening" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-user-shield"></i></div><h4>AI Employment Screening</h4><p>AI background check for job applicants</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('employment-screening'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-ke-collateral" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-car"></i></div><h4>AI Vehicle Collateral Check</h4><p>AI vehicle assessment for loan collateral</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ke-collateral'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=financial-due-diligence" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-magnifying-glass-dollar"></i></div><h4>AI Due Diligence</h4><p>AI-powered complete financial due diligence</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('financial-due-diligence'), 0) ?></span><span class="rw-svc-badge rw-badge-metropol">AI + Metropol</span></div></a></div>
      </div>
    </div>

    <!-- PAN-AFRICAN -->
    <div class="rw-tab-panel" id="panel-africa">
      <div class="row g-4">
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-ng-bvn" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-building-columns"></i></div><h4>AI Nigeria BVN Check</h4><p>AI-powered Bank Verification Number validation</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ng-bvn'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-ng-nin" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-id-card"></i></div><h4>AI Nigeria NIN Verification</h4><p>AI-verified Nigerian National ID with NIMC</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ng-nin'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-gh-drivers-license" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-id-card-clip"></i></div><h4>AI Ghana License Check</h4><p>AI verification of Ghana driving license</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-gh-drivers-license'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-za-id" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-id-card"></i></div><h4>AI South Africa ID</h4><p>AI-verified South African national ID</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-za-id'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-ug-nin" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-id-card"></i></div><h4>AI Uganda NIN Check</h4><p>AI-powered Uganda National ID verification</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-ug-nin'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
        <div class="col-md-6 col-lg-4"><a href="<?= $base_path ?>/verification.php?service=yv-liveness" class="rw-svc-card"><div class="rw-svc-icon"><i class="fa-solid fa-face-smile"></i></div><h4>AI Biometric Liveness</h4><p>AI anti-spoofing face verification technology</p><div class="rw-svc-footer"><span class="rw-svc-price">KES <?= number_format(get_service_price('yv-liveness'), 0) ?></span><span class="rw-svc-badge rw-badge-youverify">AI + YouVerify</span></div></a></div>
      </div>
    </div>

    <div class="rw-view-all rw-reveal rw-reveal-trigger">
      <a href="<?= $base_path ?>/services.php">View All 61+ AI Services <i class="fa-solid fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- ===== WHY CHOOSE ===== -->
<section class="rw-why">
  <div class="container">
    <div class="rw-section-header rw-reveal rw-reveal-trigger">
      <div class="rw-section-label"><i class="fa-solid fa-brain"></i> Why Readiwork AI</div>
      <h2 class="rw-section-title">AI-Driven Speed, Accuracy & Trust</h2>
      <p class="rw-section-desc">Our artificial intelligence engine delivers results that humans simply can't match</p>
    </div>
    <div class="row g-4 rw-stagger rw-reveal-trigger">
      <div class="col-md-6 col-lg-4"><div class="rw-feature-card"><div class="rw-feature-icon"><i class="fa-solid fa-bolt"></i></div><h3>AI Instant Results</h3><p>Our AI processes verification requests in under 5 seconds. No human bottlenecks, no delays.</p></div></div>
      <div class="col-md-6 col-lg-4"><div class="rw-feature-card"><div class="rw-feature-icon"><i class="fa-solid fa-lock"></i></div><h3>AI Security Layer</h3><p>AI-monitored 256-bit encryption with anomaly detection. Fully DPA 2019 compliant.</p></div></div>
      <div class="col-md-6 col-lg-4"><div class="rw-feature-card"><div class="rw-feature-icon"><i class="fa-solid fa-brain"></i></div><h3>99.9% AI Accuracy</h3><p>Machine learning models trained on millions of records deliver near-perfect accuracy.</p></div></div>
      <div class="col-md-6 col-lg-4"><div class="rw-feature-card"><div class="rw-feature-icon"><i class="fa-solid fa-globe-africa"></i></div><h3>Pan-African AI</h3><p>AI models optimized for African identity systems — Kenya, Nigeria, Ghana, SA, Uganda & more.</p></div></div>
      <div class="col-md-6 col-lg-4"><div class="rw-feature-card"><div class="rw-feature-icon"><i class="fa-solid fa-database"></i></div><h3>Official Data + AI</h3><p>Direct API connections to IPRS, NTSA, KRA & Metropol CRB, enhanced with AI analysis.</p></div></div>
      <div class="col-md-6 col-lg-4"><div class="rw-feature-card"><div class="rw-feature-icon"><i class="fa-solid fa-mobile-screen"></i></div><h3>M-Pesa Integration</h3><p>Pay instantly with M-Pesa STK Push. Works with Safaricom and Airtel Money.</p></div></div>
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
