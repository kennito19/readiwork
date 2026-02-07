<?php
/**
 * Metropol CRB Verification - Success Results Page
 */
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Check if we have success data in session
if (!isset($_SESSION['verification_result']) || !isset($_GET['success'])) {
    header('Location: verify.php');
    exit;
}

$result = $_SESSION['verification_result'];
$identity_number = $result['identity_number'];
$response = $result['response'];
$httpCode = $result['http_code'];
$timestamp = $result['timestamp'];

// Extract personal information from response
$fullName = trim(($response['first_name'] ?? '') . ' ' . ($response['other_name'] ?? '') . ' ' . ($response['last_name'] ?? $response['surname'] ?? ''));
$dob = $response['dob'] ?? $response['date_of_birth'] ?? 'N/A';
$gender = $response['gender'] ?? 'N/A';
$phone = $response['phone'] ?? 'N/A';
$phone2 = $response['phone2'] ?? $response['alternative_phone'] ?? 'N/A';
$address = $response['place_of_live'] ?? $response['address'] ?? 'N/A';
$occupation = $response['occupation'] ?? 'N/A';
$citizenship = $response['citizenship'] ?? 'N/A';
$serialNumber = $response['serial_number'] ?? 'N/A';
$placeOfBirth = $response['place_of_birth'] ?? 'N/A';
$email = $response['email'] ?? 'N/A';

// Calculate credit score info if available
$creditScore = $response['metro_score'] ?? $response['credit_score'] ?? null;
$creditStatus = 'Valid & Verified';
$creditBadge = 'success';
if ($creditScore) {
    if ($creditScore >= 700) {
        $creditStatus = 'Excellent Standing';
        $creditBadge = 'success';
    } elseif ($creditScore >= 600) {
        $creditStatus = 'Good Standing';
        $creditBadge = 'success';
    } elseif ($creditScore >= 500) {
        $creditStatus = 'Fair Standing';
        $creditBadge = 'warning';
    } else {
        $creditStatus = 'Needs Attention';
        $creditBadge = 'danger';
    }
}

// Generate verification reference number
$verificationRef = 'VRF-' . date('Ymd', strtotime($timestamp)) . '-' . strtoupper(substr(md5($identity_number . $timestamp), 0, 8));

// Clear session data
unset($_SESSION['verification_result']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Verification Successful - Readiwork AI Verification Platform</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Identity verification completed successfully via Readiwork's AI-powered CRB verification system">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --primary:#22c55e;
  --primary-dark:#16a34a;
  --primary-soft:#86efac;
  --accent-warning:#facc15;
  --accent-danger:#ef4444;
  --bg:#f8fafc;
  --surface:#ffffff;
  --card:#ffffff;
  --text:#0f172a;
  --muted:#475569;
  --border:#e2e8f0;
  --shadow:rgba(0,0,0,0.08);
  --gradient-primary:linear-gradient(135deg,#22c55e,#86efac);
}
body{
  font-family:'Inter',sans-serif;
  background:var(--bg);
  color:var(--text);
  line-height:1.7;
  padding-top:80px;
}
.navbar{
  background:rgba(255,255,255,0.95);
  backdrop-filter:blur(16px);
  border-bottom:1px solid var(--border);
  box-shadow:0 4px 20px var(--shadow);
}
.navbar-brand{
  font-weight:900;
  font-size:1.8rem;
  background:var(--gradient-primary);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
}
.nav-link{
  color:var(--muted)!important;
  font-weight:600;
  position:relative;
  transition:all 0.3s ease;
}
.nav-link:hover,.nav-link.active{
  color:var(--primary)!important;
}
.nav-link::after{
  content:'';
  position:absolute;
  bottom:-4px;
  left:0;
  width:0;
  height:2px;
  background:var(--primary);
  transition:width 0.3s ease;
}
.nav-link:hover::after,.nav-link.active::after{
  width:100%;
}
.result-card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:28px;
  padding:50px;
  margin:40px auto 60px;
  max-width:1200px;
  box-shadow:0 20px 60px var(--shadow);
}
.success-badge{
  display:inline-block;
  padding:16px 40px;
  border-radius:999px;
  font-weight:800;
  font-size:1.1rem;
  margin-bottom:32px;
  background:rgba(34,197,94,0.1);
  color:var(--primary);
  border:2px solid var(--primary);
  animation:pulse 2s infinite;
}
@keyframes pulse{
  0%,100%{transform:scale(1);}
  50%{transform:scale(1.05);}
}
.success-hero{
  background:linear-gradient(135deg,#ecfdf5 0%,#f0fdfa 100%);
  border:1px solid rgba(34,197,94,0.2);
  border-left:6px solid var(--primary);
  border-radius:20px;
  padding:48px;
  margin-top:20px;
}
.success-hero h1{
  color:var(--primary);
  font-weight:900;
  margin-bottom:20px;
  font-size:2.2rem;
  display:flex;
  align-items:center;
  gap:16px;
}
.success-hero h1 i{
  font-size:2.5rem;
}
.success-hero p{
  color:var(--text);
  font-size:1.2rem;
  line-height:1.8;
  margin-bottom:0;
}
.identity-card{
  margin-top:32px;
  padding:36px;
  background:linear-gradient(135deg,#f0fdf4 0%,#ecfdf5 100%);
  border-radius:20px;
  border:2px solid rgba(34,197,94,0.2);
}
.identity-card h3{
  color:var(--muted);
  font-size:1rem;
  font-weight:800;
  margin-bottom:20px;
  text-transform:uppercase;
  letter-spacing:1.5px;
  display:flex;
  align-items:center;
  gap:10px;
}
.identity-card .name{
  font-size:2rem;
  font-weight:900;
  color:var(--text);
  margin-bottom:16px;
  letter-spacing:-0.5px;
}
.identity-card .details{
  color:var(--muted);
  margin:0;
  font-size:1.05rem;
  display:flex;
  flex-wrap:wrap;
  gap:24px;
}
.identity-card .details span{
  display:flex;
  align-items:center;
  gap:8px;
}
.id-display{
  background:var(--primary);
  background:var(--gradient-primary);
  border:none;
  padding:36px;
  border-radius:20px;
  margin-top:40px;
  text-align:center;
  box-shadow:0 10px 30px rgba(34,197,94,0.2);
}
.id-display h3{
  color:rgba(255,255,255,0.9);
  font-size:1rem;
  font-weight:800;
  margin-bottom:20px;
  text-transform:uppercase;
  letter-spacing:1.5px;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
}
.id-display .id-number{
  color:#fff;
  font-size:2.8rem;
  font-weight:900;
  letter-spacing:4px;
  font-family:'Courier New',monospace;
  text-shadow:0 2px 10px rgba(0,0,0,0.1);
}
.verification-stamp{
  display:inline-flex;
  align-items:center;
  gap:12px;
  background:rgba(34,197,94,0.1);
  border:2px solid var(--primary);
  padding:12px 28px;
  border-radius:999px;
  font-weight:700;
  color:var(--primary);
  margin-top:20px;
}
.section{
  margin-top:50px;
}
.section-title{
  color:var(--text);
  font-size:1.5rem;
  font-weight:900;
  margin-bottom:28px;
  padding-bottom:16px;
  border-bottom:3px solid var(--primary);
  display:flex;
  align-items:center;
  gap:12px;
}
.info-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:20px;
  margin-top:28px;
}
.info-item{
  background:var(--surface);
  border:2px solid var(--border);
  padding:28px;
  border-radius:16px;
  transition:all 0.3s ease;
}
.info-item:hover{
  border-color:var(--primary);
  transform:translateY(-4px);
  box-shadow:0 10px 30px var(--shadow);
}
.info-item .label{
  color:var(--muted);
  font-size:.9rem;
  font-weight:700;
  margin-bottom:12px;
  text-transform:uppercase;
  letter-spacing:0.5px;
  display:flex;
  align-items:center;
  gap:8px;
}
.info-item .value{
  color:var(--text);
  font-size:1.2rem;
  font-weight:700;
  word-break:break-word;
}
.info-item .value.highlight{
  color:var(--primary);
  font-size:1.3rem;
}
.credit-score-box{
  background:var(--gradient-primary);
  border:none;
  border-radius:24px;
  padding:48px;
  text-align:center;
  margin-top:40px;
  box-shadow:0 20px 60px rgba(34,197,94,0.2);
}
.credit-score-box .score{
  font-size:5rem;
  font-weight:900;
  color:#fff;
  line-height:1;
  margin-bottom:12px;
  text-shadow:0 4px 20px rgba(0,0,0,0.1);
}
.credit-score-box .label{
  color:rgba(255,255,255,0.95);
  font-size:1.2rem;
  font-weight:700;
}
.trust-indicators{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:20px;
  margin-top:40px;
}
.trust-item{
  background:linear-gradient(135deg,#f0fdf4 0%,#ecfdf5 100%);
  border:2px solid rgba(34,197,94,0.2);
  padding:24px;
  border-radius:16px;
  text-align:center;
}
.trust-item i{
  font-size:2.5rem;
  color:var(--primary);
  margin-bottom:12px;
}
.trust-item h5{
  font-weight:800;
  font-size:1rem;
  color:var(--text);
  margin-bottom:8px;
}
.trust-item p{
  font-size:.9rem;
  color:var(--muted);
  margin:0;
}
.btn-group-actions{
  display:flex;
  gap:16px;
  margin-top:50px;
  flex-wrap:wrap;
  justify-content:center;
}
.btn-action{
  padding:18px 40px;
  border-radius:999px;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  gap:12px;
  font-weight:800;
  font-size:1.1rem;
  transition:all 0.3s ease;
  border:none;
}
.btn-primary-action{
  background:var(--gradient-primary);
  color:white;
  box-shadow:0 10px 30px rgba(34,197,94,0.2);
}
.btn-primary-action:hover{
  transform:translateY(-4px);
  box-shadow:0 20px 40px rgba(34,197,94,0.3);
  color:white;
}
.btn-secondary-action{
  background:var(--surface);
  color:var(--text);
  border:2px solid var(--border);
}
.btn-secondary-action:hover{
  border-color:var(--primary);
  color:var(--primary);
  transform:translateY(-4px);
}
.security-notice{
  background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);
  border:2px solid rgba(59,130,246,0.2);
  border-left:6px solid #3b82f6;
  border-radius:16px;
  padding:32px;
  margin-top:50px;
}
.security-notice h5{
  color:#3b82f6;
  font-weight:800;
  margin-bottom:16px;
  display:flex;
  align-items:center;
  gap:10px;
}
.security-notice p{
  color:var(--muted);
  font-size:1rem;
  margin:0;
  line-height:1.8;
}
footer{
  background:#0f172a;
  color:#e2e8f0;
  padding:80px 0 40px;
  margin-top:80px;
}
footer .brand{
  font-size:2.2rem;
  font-weight:900;
  background:var(--gradient-primary);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  margin-bottom:20px;
}
footer .footer-links{
  display:flex;
  flex-wrap:wrap;
  gap:30px;
  justify-content:center;
  margin:30px 0;
}
footer .footer-links a{
  color:#94a3b8;
  text-decoration:none;
  font-weight:600;
  transition:color 0.3s ease;
}
footer .footer-links a:hover{
  color:var(--primary);
}
footer .social-links{
  display:flex;
  gap:20px;
  justify-content:center;
  margin-top:30px;
}
footer .social-links a{
  width:48px;
  height:48px;
  background:rgba(255,255,255,0.1);
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  color:#94a3b8;
  transition:all 0.3s ease;
}
footer .social-links a:hover{
  background:var(--primary);
  color:white;
  transform:translateY(-4px);
}
@media (max-width: 768px){
  .result-card{
    padding:30px 20px;
    margin:20px auto 40px;
  }
  .success-hero{
    padding:32px 24px;
  }
  .success-hero h1{
    font-size:1.8rem;
  }
  .id-display .id-number{
    font-size:2rem;
  }
  .info-grid{
    grid-template-columns:1fr;
  }
  .credit-score-box .score{
    font-size:4rem;
  }
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">READIWORK</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="verify.php">Verify ID</a></li>
        <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <div class="result-card">
    
    <div class="text-center">
      <span class="success-badge">
        <i class="fa-solid fa-circle-check"></i> VERIFICATION SUCCESSFUL
      </span>
    </div>

    <div class="success-hero">
      <h1>
        <i class="fa-solid fa-shield-check"></i> 
        Identity Successfully Verified!
      </h1>
      <p>
        Excellent! The National ID has been successfully verified through the Credit Reference Bureau (CRB) database via Metropol CRB. 
        This confirms the identity is <strong>valid, active, and officially registered</strong> in Kenya's national identification system.
      </p>
      
      <?php if ($fullName && $fullName !== ''): ?>
      <div class="identity-card">
        <h3>
          <i class="fa-solid fa-user-check"></i> VERIFIED IDENTITY
        </h3>
        <div class="name"><?= htmlspecialchars($fullName) ?></div>
        <div class="details">
          <?php if ($dob !== 'N/A'): ?>
          <span>
            <i class="fa-solid fa-calendar-days"></i> Born: <?= htmlspecialchars($dob) ?>
          </span>
          <?php endif; ?>
          <?php if ($gender !== 'N/A'): ?>
          <span>
            <i class="fa-solid fa-venus-mars"></i> 
            <?= $gender === 'M' ? 'Male' : ($gender === 'F' ? 'Female' : htmlspecialchars($gender)) ?>
          </span>
          <?php endif; ?>
          <?php if ($citizenship !== 'N/A'): ?>
          <span>
            <i class="fa-solid fa-flag"></i> <?= htmlspecialchars($citizenship) ?>
          </span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="id-display">
      <h3><i class="fa-solid fa-id-card-clip"></i> VERIFIED NATIONAL ID NUMBER</h3>
      <div class="id-number"><?= htmlspecialchars($identity_number) ?></div>
      <div class="verification-stamp">
        <i class="fa-solid fa-stamp"></i>
        Ref: <?= $verificationRef ?>
      </div>
    </div>

    <?php if ($creditScore): ?>
    <div class="credit-score-box">
      <div class="score"><?= htmlspecialchars($creditScore) ?></div>
      <div class="label">Credit Score (Metro-Score®)</div>
    </div>
    <?php endif; ?>

    <div class="section">
      <h5 class="section-title">
        <i class="fa-solid fa-clipboard-check"></i> Verification Summary
      </h5>
      <div class="info-grid">
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-badge-check"></i> Status</div>
          <div class="value highlight"><?= htmlspecialchars($creditStatus) ?></div>
        </div>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-shield-check"></i> Verified By</div>
          <div class="value">Metropol CRB</div>
        </div>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-calendar-check"></i> Verification Date</div>
          <div class="value"><?= date('M d, Y', strtotime($timestamp)) ?></div>
        </div>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-clock"></i> Time</div>
          <div class="value"><?= date('h:i A', strtotime($timestamp)) ?></div>
        </div>
      </div>
    </div>

    <div class="trust-indicators">
      <div class="trust-item">
        <i class="fa-solid fa-database"></i>
        <h5>CRB Verified</h5>
        <p>Official Metropol Credit Reference Bureau</p>
      </div>
      <div class="trust-item">
        <i class="fa-solid fa-fingerprint"></i>
        <h5>Authentic Identity</h5>
        <p>Confirmed in National Database</p>
      </div>
      <div class="trust-item">
        <i class="fa-solid fa-lock"></i>
        <h5>Secure Check</h5>
        <p>256-bit Encrypted Connection</p>
      </div>
      <div class="trust-item">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <h5>Real-Time</h5>
        <p>Instant API Verification</p>
      </div>
    </div>

    <?php if ($phone !== 'N/A' || $address !== 'N/A' || $occupation !== 'N/A' || $email !== 'N/A'): ?>
    <div class="section">
      <h5 class="section-title">
        <i class="fa-solid fa-address-card"></i> Additional Information
      </h5>
      <div class="info-grid">
        <?php if ($phone !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-phone"></i> Primary Phone</div>
          <div class="value"><?= htmlspecialchars($phone) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($phone2 !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-mobile-screen"></i> Alternative Phone</div>
          <div class="value"><?= htmlspecialchars($phone2) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($email !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-envelope"></i> Email Address</div>
          <div class="value"><?= htmlspecialchars($email) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($occupation !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-briefcase"></i> Occupation</div>
          <div class="value"><?= htmlspecialchars($occupation) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($address !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-location-dot"></i> Residential Address</div>
          <div class="value"><?= htmlspecialchars($address) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($placeOfBirth !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-map-pin"></i> Place of Birth</div>
          <div class="value"><?= htmlspecialchars($placeOfBirth) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($serialNumber !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-barcode"></i> Serial Number</div>
          <div class="value"><?= htmlspecialchars($serialNumber) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="security-notice">
      <h5>
        <i class="fa-solid fa-shield-halved"></i> Privacy & Data Protection
      </h5>
      <p>
        This verification was performed with explicit consent and is compliant with Kenya's Data Protection Act, 2019. 
        All data is encrypted using industry-standard 256-bit SSL/TLS protocols. No personal information is stored or shared 
        without authorization. This report is generated for legitimate verification purposes only and expires after viewing.
      </p>
    </div>

    <div class="btn-group-actions">
      <a href="verify.php" class="btn-action btn-primary-action">
        <i class="fa-solid fa-rotate"></i> Verify Another ID
      </a>
      <a href="report.php?id=<?= htmlspecialchars($identity_number) ?>&service=credit-score" class="btn-action btn-secondary-action">
        <i class="fa-solid fa-file-chart-column"></i> View Full Credit Report
      </a>
      <a href="services.php" class="btn-action btn-secondary-action">
        <i class="fa-solid fa-grid-2"></i> Browse All Services
      </a>
    </div>

  </div>
</div>

<footer>
  <div class="container text-center">
    <div class="brand">READIWORK</div>
    <p style="color:#94a3b8;font-size:1.1rem;max-width:700px;margin:20px auto;">
      AI-powered verification platform providing secure, instant identity and credit checks across Kenya
    </p>
    
    <div class="footer-links">
      <a href="index.php">Home</a>
      <a href="services.php">Services</a>
      <a href="verify.php">Verify ID</a>
      <a href="about.php">About Us</a>
      <a href="privacy.php">Privacy Policy</a>
      <a href="terms.php">Terms of Service</a>
      <a href="contact.php">Contact</a>
    </div>

    <div class="social-links">
      <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
      <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
      <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
      <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
    </div>

    <div style="margin-top:40px;padding-top:30px;border-top:1px solid rgba(255,255,255,0.1);">
      <p style="color:#64748b;font-size:0.9rem;margin:0;">
        &copy; <?= date('Y') ?> Readiwork. All rights reserved. | Powered by Metropol CRB
      </p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Add print functionality
function printReport() {
  window.print();
}

// Smooth scroll to sections
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

// Auto-expire notice after 5 minutes
setTimeout(() => {
  const notice = document.createElement('div');
  notice.style = 'position:fixed;bottom:20px;right:20px;background:#ef4444;color:white;padding:20px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.2);z-index:9999;max-width:300px;';
  notice.innerHTML = '<strong><i class="fa-solid fa-clock"></i> Session Expiring</strong><p style="margin:8px 0 0;font-size:0.9rem;">This verification report will expire soon. Please save or print if needed.</p>';
  document.body.appendChild(notice);
}, 300000);
</script>
</body>
</html>