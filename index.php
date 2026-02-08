<?php
require_once 'config.php';

// Dynamic base path
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '.') $base_path = '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Readiwork AI — Kenya's Complete Verification Platform</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="61+ verification services powered by AI. Identity verification, credit checks, CRB clearance, business verification — instantly delivered with accuracy.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_path ?>/assets/css/theme.css">

<style>
/* ===== HOMEPAGE SPECIFIC STYLES ===== */

/* Hero - Clean centered */
.hp-hero {
  background: linear-gradient(135deg, #0f2027 0%, #1a3a3a 50%, #203a43 100%);
  padding: 100px 0 80px;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.hp-hero::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(34,197,94,0.08) 0%, transparent 70%);
  border-radius: 50%;
}
.hp-hero h1 {
  font-size: clamp(2rem, 5vw, 3.2rem);
  font-weight: 800;
  color: #fff;
  margin-bottom: 8px;
  line-height: 1.2;
}
.hp-hero h1 span {
  color: #22c55e;
  font-style: italic;
}
.hp-hero .hp-sub {
  font-size: 1.1rem;
  color: rgba(255,255,255,0.7);
  max-width: 540px;
  margin: 16px auto 32px;
  line-height: 1.6;
}
.hp-hero-btns {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}
.hp-btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 32px;
  background: #22c55e;
  color: #fff;
  font-weight: 600;
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.3s;
}
.hp-btn-primary:hover { background: #16a34a; color: #fff; transform: translateY(-2px); }
.hp-btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 32px;
  border: 2px solid rgba(255,255,255,0.3);
  color: #fff;
  font-weight: 600;
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.3s;
}
.hp-btn-outline:hover { border-color: #22c55e; color: #22c55e; }

/* Category Cards */
.hp-categories {
  padding: 60px 0;
  background: #f8fafb;
}
.hp-categories .section-title {
  text-align: center;
  font-size: 1.1rem;
  color: #64748b;
  margin-bottom: 40px;
  font-weight: 500;
}
.hp-cat-card {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 28px 24px;
  text-decoration: none;
  color: inherit;
  transition: all 0.3s;
  height: 100%;
  position: relative;
}
.hp-cat-card:hover {
  border-color: #22c55e;
  box-shadow: 0 8px 30px rgba(34,197,94,0.1);
  transform: translateY(-4px);
  color: inherit;
}
.hp-cat-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}
.hp-cat-icon.identity { background: #e0f2fe; color: #0284c7; }
.hp-cat-icon.business { background: #d1fae5; color: #059669; }
.hp-cat-icon.credit   { background: #fef3c7; color: #d97706; }
.hp-cat-icon.fraud    { background: #fce7f3; color: #db2777; }
.hp-cat-icon.tenant   { background: #ede9fe; color: #7c3aed; }
.hp-cat-icon.vehicle  { background: #e0e7ff; color: #4f46e5; }
.hp-cat-body h4 {
  font-size: 1.05rem;
  font-weight: 700;
  margin-bottom: 4px;
  color: #1e293b;
}
.hp-cat-body p {
  font-size: 0.88rem;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}
.hp-cat-badge {
  display: inline-block;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  background: #d1fae5;
  color: #059669;
  margin-top: 8px;
}
.hp-cat-arrow {
  position: absolute;
  bottom: 20px;
  right: 20px;
  color: #94a3b8;
  font-size: 1rem;
  transition: color 0.3s;
}
.hp-cat-card:hover .hp-cat-arrow { color: #22c55e; }

/* Quick Verify Search */
.hp-search {
  padding: 0 0 60px;
  background: #f8fafb;
}
.hp-search-box {
  max-width: 700px;
  margin: 0 auto;
  background: #fff;
  border-radius: 16px;
  border: 2px solid #e2e8f0;
  padding: 8px 8px 8px 24px;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: border-color 0.3s;
}
.hp-search-box:focus-within { border-color: #22c55e; box-shadow: 0 0 0 4px rgba(34,197,94,0.1); }
.hp-search-box i { color: #94a3b8; font-size: 1.1rem; }
.hp-search-box input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 1rem;
  color: #1e293b;
  background: transparent;
}
.hp-search-box input::placeholder { color: #94a3b8; }
.hp-search-btn {
  padding: 12px 28px;
  background: #1e293b;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: background 0.3s;
  white-space: nowrap;
}
.hp-search-btn:hover { background: #22c55e; }

/* How it Works - Compact */
.hp-how {
  padding: 70px 0;
  background: #fff;
}
.hp-how .section-header {
  text-align: center;
  margin-bottom: 48px;
}
.hp-how .section-header h2 {
  font-size: 1.8rem;
  font-weight: 800;
  color: #1e293b;
}
.hp-how .section-header p {
  color: #64748b;
  margin-top: 8px;
}
.hp-step {
  text-align: center;
  padding: 0 16px;
}
.hp-step-num {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 800;
  margin: 0 auto 16px;
  background: #f0fdf4;
  color: #22c55e;
  border: 2px solid #bbf7d0;
}
.hp-step h4 {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 6px;
}
.hp-step p {
  font-size: 0.9rem;
  color: #64748b;
  line-height: 1.6;
}

/* Stats Strip */
.hp-stats {
  padding: 40px 0;
  background: #1e293b;
}
.hp-stat-item {
  text-align: center;
  padding: 16px;
}
.hp-stat-item h3 {
  font-size: 2rem;
  font-weight: 800;
  color: #22c55e;
  margin-bottom: 4px;
}
.hp-stat-item p {
  color: rgba(255,255,255,0.6);
  font-size: 0.85rem;
  margin: 0;
}

/* CTA */
.hp-cta {
  padding: 80px 0;
  background: linear-gradient(135deg, #0f2027 0%, #1a3a3a 100%);
  text-align: center;
}
.hp-cta h2 {
  font-size: 2rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: 12px;
}
.hp-cta p {
  color: rgba(255,255,255,0.7);
  max-width: 500px;
  margin: 0 auto 32px;
}

/* Responsive */
@media (max-width: 768px) {
  .hp-hero { padding: 70px 0 50px; }
  .hp-hero h1 { font-size: 1.8rem; }
  .hp-search-box { flex-direction: column; padding: 16px; }
  .hp-search-btn { width: 100%; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>

<!-- ===== HERO ===== -->
<section class="hp-hero">
  <div class="container">
    <h1>Kenya's Complete<br><span>Verification Platform</span></h1>
    <p class="hp-sub">
      61+ verification services powered by trusted providers &mdash; instantly delivered with accuracy.
    </p>
    <div class="hp-hero-btns">
      <a href="<?= $base_path ?>/services.php" class="hp-btn-primary">
        Browse All Services <i class="fa-solid fa-arrow-right"></i>
      </a>
      <a href="#how-it-works" class="hp-btn-outline">
        Learn More
      </a>
    </div>
  </div>
</section>

<!-- ===== CATEGORY CARDS ===== -->
<section class="hp-categories">
  <div class="container">
    <p class="section-title">What would you like to verify today?</p>
    <div class="row g-4">

      <div class="col-md-6">
        <a href="<?= $base_path ?>/services.php#identity" class="hp-cat-card">
          <div class="hp-cat-icon identity"><i class="fa-solid fa-id-card"></i></div>
          <div class="hp-cat-body">
            <h4>ID Verification</h4>
            <p>Validate National IDs, passports, alien IDs, and drivers licenses instantly.</p>
            <span class="hp-cat-badge"><i class="fa-solid fa-check"></i> 12 Services</span>
          </div>
          <i class="fa-solid fa-arrow-right hp-cat-arrow"></i>
        </a>
      </div>

      <div class="col-md-6">
        <a href="<?= $base_path ?>/services.php#business" class="hp-cat-card">
          <div class="hp-cat-icon business"><i class="fa-solid fa-building"></i></div>
          <div class="hp-cat-body">
            <h4>Business Registration Check</h4>
            <p>Verify a company's legal registration status, directors, and compliance.</p>
            <span class="hp-cat-badge"><i class="fa-solid fa-check"></i> 6 Services</span>
          </div>
          <i class="fa-solid fa-arrow-right hp-cat-arrow"></i>
        </a>
      </div>

      <div class="col-md-6">
        <a href="<?= $base_path ?>/services.php#credit" class="hp-cat-card">
          <div class="hp-cat-icon credit"><i class="fa-solid fa-chart-line"></i></div>
          <div class="hp-cat-body">
            <h4>Credit Risk Score</h4>
            <p>Assess and report on an individual's credit risk, CRB status, and loan eligibility.</p>
            <span class="hp-cat-badge"><i class="fa-solid fa-check"></i> 18 Services</span>
          </div>
          <i class="fa-solid fa-arrow-right hp-cat-arrow"></i>
        </a>
      </div>

      <div class="col-md-6">
        <a href="<?= $base_path ?>/services.php#compliance" class="hp-cat-card">
          <div class="hp-cat-icon fraud"><i class="fa-solid fa-shield-halved"></i></div>
          <div class="hp-cat-body">
            <h4>Fraud Screening</h4>
            <p>Detect and prevent fraudulent activities with identity scrub and risk profiling.</p>
            <span class="hp-cat-badge"><i class="fa-solid fa-check"></i> 8 Services</span>
          </div>
          <i class="fa-solid fa-arrow-right hp-cat-arrow"></i>
        </a>
      </div>

      <div class="col-md-6">
        <a href="<?= $base_path ?>/services.php#screening" class="hp-cat-card">
          <div class="hp-cat-icon tenant"><i class="fa-solid fa-house-user"></i></div>
          <div class="hp-cat-body">
            <h4>Tenant & Employee Screening</h4>
            <p>Screen tenants, job applicants, and domestic staff with background checks.</p>
            <span class="hp-cat-badge"><i class="fa-solid fa-check"></i> 9 Services</span>
          </div>
          <i class="fa-solid fa-arrow-right hp-cat-arrow"></i>
        </a>
      </div>

      <div class="col-md-6">
        <a href="<?= $base_path ?>/services.php#vehicle" class="hp-cat-card">
          <div class="hp-cat-icon vehicle"><i class="fa-solid fa-car"></i></div>
          <div class="hp-cat-body">
            <h4>Vehicle & Asset Verification</h4>
            <p>Verify plate numbers, vehicle collateral, and asset ownership with NTSA.</p>
            <span class="hp-cat-badge"><i class="fa-solid fa-check"></i> 4 Services</span>
          </div>
          <i class="fa-solid fa-arrow-right hp-cat-arrow"></i>
        </a>
      </div>

    </div>
  </div>
</section>

<!-- ===== QUICK SEARCH ===== -->
<section class="hp-search">
  <div class="container">
    <form class="hp-search-box" action="<?= $base_path ?>/verification.php" method="GET">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" name="q" placeholder="Enter National ID / Phone / Business Name" autocomplete="off">
      <input type="hidden" name="service" value="id-verification">
      <button type="submit" class="hp-search-btn">Verify Now</button>
    </form>
  </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="hp-how" id="how-it-works">
  <div class="container">
    <div class="section-header">
      <h2>How It Works</h2>
      <p>Get verified results in 3 simple steps</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="hp-step">
          <div class="hp-step-num">1</div>
          <h4>Choose a Service</h4>
          <p>Select from 61+ verification services across identity, credit, business, and more.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="hp-step">
          <div class="hp-step-num">2</div>
          <h4>Enter Details & Pay</h4>
          <p>Input the ID or document number. Pay securely with M-Pesa STK Push.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="hp-step">
          <div class="hp-step-num">3</div>
          <h4>Get Instant Results</h4>
          <p>Receive your verified report in under 5 seconds with full details and risk assessment.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== STATS ===== -->
<section class="hp-stats">
  <div class="container">
    <div class="row">
      <div class="col-6 col-md-3">
        <div class="hp-stat-item">
          <h3>61+</h3>
          <p>Verification Services</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="hp-stat-item">
          <h3>&lt;5s</h3>
          <p>Response Time</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="hp-stat-item">
          <h3>99.9%</h3>
          <p>Accuracy Rate</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="hp-stat-item">
          <h3>8</h3>
          <p>African Countries</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== CTA ===== -->
<section class="hp-cta">
  <div class="container">
    <h2>Ready to Get Started?</h2>
    <p>Join thousands of businesses using Readiwork for instant, accurate verification.</p>
    <a href="<?= $base_path ?>/services.php" class="hp-btn-primary">
      Explore All Services <i class="fa-solid fa-arrow-right"></i>
    </a>
  </div>
</section>

<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base_path ?>/assets/js/main.js"></script>
</body>
</html>
