<?php
/**
 * Phone Verification Results
 * YouVerify - Kenya Phone Number Display
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';

$rid = $_GET['rid'] ?? null;
if (!$rid || !ctype_digit($rid)) {
    die('Invalid request ID. <a href="index.php">Go back</a>');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM verification_requests WHERE id = :rid");
    $stmt->execute([':rid' => $rid]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
   
    if (!$request) die('Request not found.');
    if ($request['status'] !== 'paid' && $request['status'] !== 'completed') {
        die('Payment not completed. <a href="payment.php?rid=' . $rid . '">Complete payment</a>');
    }
   
    $combinedResults = json_decode($request['result'], true);
    if (!$combinedResults || !is_array($combinedResults)) {
        die('No verification data available.');
    }
   
    $id = $request['national_id'];

} catch (Exception $e) {
    error_log('Results page error: ' . $e->getMessage());
    die('Error loading results.');
}

// Extract YouVerify phone data according to API documentation
$yvResponse = $combinedResults['data'] ?? $combinedResults;
$phoneData = $yvResponse['data'] ?? $yvResponse;

// Extract phone information
$phone = [
    'first_name' => $phoneData['firstName'] ?? 'N/A',
    'middle_name' => $phoneData['middleName'] ?? '',
    'last_name' => $phoneData['lastName'] ?? 'N/A',
    'full_name' => $phoneData['fullName'] ?? '',
    'id_number' => $phoneData['idNumber'] ?? $id,
    'id_type' => $phoneData['identityType'] ?? 'national-id',
    'dob' => $phoneData['dateOfBirth'] ?? 'N/A',
    'gender' => $phoneData['gender'] ?? 'N/A',
    'marital_status' => $phoneData['maritalStatus'] ?? 'N/A',
    'email' => $phoneData['email'] ?? null,
    'mobile_phone' => $phoneData['mobileTelephoneNumber'] ?? null,
    'home_phone' => $phoneData['homeTelephoneNumber'] ?? null,
    'work_phone' => $phoneData['workTelephoneNumber'] ?? null,
    'verification_status' => $phoneData['status'] ?? 'unknown',
    'verification_id' => $phoneData['id'] ?? '',
    'verification_date' => $phoneData['requestedAt'] ?? '',
    'verification_type' => $phoneData['type'] ?? 'keCreditInfoPhone',
    'reason' => $phoneData['reason'] ?? null,
    'country' => $phoneData['country'] ?? 'KE',
];

$fullName = $phone['full_name'] ?: trim($phone['first_name'] . ' ' . ($phone['middle_name'] ? $phone['middle_name'] . ' ' : '') . $phone['last_name']);
$trx_id = 'PHONE-' . date('YmdHis') . '-' . strtoupper(substr($id, -6));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Kenya Phone Verification Report | Readiwork</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--primary:#22c55e;--primary-dark:#16a34a;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#f8fafc 0%,#ecfdf5 100%);color:#0f172a;padding-top:80px;padding-bottom:100px;}
.card-box{background:white;border-radius:32px;box-shadow:0 30px 80px rgba(0,0,0,0.1);padding:60px;margin:50px auto;max-width:1200px;opacity:0;animation:fadeInUp 0.8s ease forwards 0.2s;}
@keyframes fadeInUp{to{opacity:1;transform:translateY(0);}}
.header{text-align:center;margin-bottom:60px;}
.header h2{font-weight:900;font-size:3rem;background:linear-gradient(135deg,#22c55e,#16a34a);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:16px;}
.header .provider-badge{background:rgba(34,197,94,0.2);color:#22c55e;border:2px solid #22c55e;padding:8px 20px;border-radius:999px;font-weight:700;display:inline-block;margin:12px 8px;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px;}
.status-box{padding:60px;border-radius:24px;text-align:center;margin-bottom:60px;background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%);border-left:8px solid #22c55e;}
.status-box.not-found{background:linear-gradient(135deg,#fef2f2 0%,#fecaca 100%);border-left:8px solid #ef4444;}
.status-box i{font-size:6rem;margin-bottom:24px;display:block;color:#22c55e;animation:pulse 2s infinite;}
.status-box.not-found i{color:#ef4444;}
@keyframes pulse{0%,100%{transform:scale(1);}50%{transform:scale(1.1);}}
.status-box h3{font-weight:900;font-size:3rem;color:#0f172a;margin-bottom:16px;}
.status-box p{color:#64748b;font-size:1.3rem;}
.contact-box{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:3px solid rgba(34,197,94,0.3);border-radius:24px;padding:50px;margin-bottom:60px;position:relative;overflow:hidden;}
.contact-box::before{content:'📞';position:absolute;top:-40px;right:-40px;font-size:200px;opacity:0.05;}
.contact-box h3{font-size:2.5rem;font-weight:900;color:#0f172a;margin-bottom:24px;position:relative;z-index:2;}
.contact-section{background:white;border-radius:20px;padding:40px;margin-bottom:30px;border:2px solid #e2e8f0;}
.contact-section h4{font-weight:900;font-size:1.8rem;color:#0f172a;margin-bottom:24px;display:flex;align-items:center;gap:12px;}
.contact-section h4 i{color:#22c55e;}
.contact-card{background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%);border:2px solid #3b82f6;border-radius:16px;padding:32px;margin-bottom:20px;display:flex;align-items:center;gap:20px;}
.contact-card.mobile{background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%);border-color:#3b82f6;}
.contact-card.home{background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border-color:#f59e0b;}
.contact-card.work{background:linear-gradient(135deg,#e9d5ff 0%,#d8b4fe 100%);border-color:#a855f7;}
.contact-card.email{background:linear-gradient(135deg,#fce7f3 0%,#fbcfe8 100%);border-color:#ec4899;}
.contact-icon{width:70px;height:70px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0;}
.contact-card.mobile .contact-icon{background:#3b82f6;color:white;}
.contact-card.home .contact-icon{background:#f59e0b;color:white;}
.contact-card.work .contact-icon{background:#a855f7;color:white;}
.contact-card.email .contact-icon{background:#ec4899;color:white;}
.contact-details{flex:1;}
.contact-details .label{font-size:0.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:6px;}
.contact-details .value{font-size:1.6rem;font-weight:800;color:#0f172a;word-break:break-all;}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-top:40px;}
.info-item{background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);padding:32px;border-radius:20px;border:2px solid #e2e8f0;transition:all 0.4s ease;position:relative;overflow:hidden;}
.info-item::before{content:'';position:absolute;top:0;left:0;width:4px;height:0;background:#22c55e;transition:height 0.4s;}
.info-item:hover{border-color:#22c55e;transform:translateY(-6px);box-shadow:0 15px 40px rgba(34,197,94,0.15);}
.info-item:hover::before{height:100%;}
.info-item .label{font-size:0.95rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.info-item .label i{color:#22c55e;}
.info-item .value{font-size:1.5rem;font-weight:800;color:#0f172a;word-break:break-word;}
.info-item .value.highlight{color:#22c55e;font-size:1.8rem;}
.btn-action{background:linear-gradient(135deg,#22c55e,#16a34a);color:white;border:none;padding:20px 50px;border-radius:999px;font-weight:800;font-size:1.2rem;margin:20px 10px;box-shadow:0 15px 40px rgba(34,197,94,0.3);transition:all 0.4s ease;display:inline-flex;align-items:center;gap:12px;text-decoration:none;}
.btn-action:hover{transform:translateY(-6px);box-shadow:0 25px 60px rgba(34,197,94,0.5);color:white;}
.btn-action.secondary{background:linear-gradient(135deg,#3b82f6,#2563eb);box-shadow:0 15px 40px rgba(59,130,246,0.3);}
.footer-note{margin-top:100px;padding-top:50px;border-top:3px solid #e2e8f0;font-size:1.05rem;color:#64748b;text-align:center;line-height:2;}
@media (max-width:768px){.card-box{padding:40px 24px;}.status-box{padding:40px 24px;}.status-box i{font-size:4rem;}.info-grid{grid-template-columns:1fr;}.contact-card{flex-direction:column;text-align:center;}}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
<?php include 'includes/navbar.php'; ?>
</nav>

<div class="container">
  <div class="card-box">
    
    <div class="header">
      <h2>Kenya Phone Verification</h2>
      <p style="color:#64748b;font-size:1.1rem;">Generated on <?= date('d F Y, h:i A') ?></p>
      <span class="provider-badge"><i class="fa-solid fa-fingerprint me-2"></i>YouVerify</span>
      <span class="provider-badge"><i class="fa-solid fa-shield-check me-2"></i>Official Records</span>
    </div>

    <!-- VERIFICATION STATUS -->
    <div class="status-box <?= $phone['verification_status'] !== 'found' ? 'not-found' : '' ?>">
      <i class="fa-solid fa-<?= $phone['verification_status'] === 'found' ? 'phone-volume' : 'circle-xmark' ?>"></i>
      <h3><?= $phone['verification_status'] === 'found' ? 'PHONE VERIFIED' : 'NO RECORDS FOUND' ?></h3>
      <p><?= $phone['verification_status'] === 'found' ? 'Phone records found in official database' : 'No phone records found for this ID' ?></p>
      <?php if ($phone['reason']): ?>
      <p style="margin-top:16px;font-size:1rem;color:#ef4444;">Reason: <?= htmlspecialchars($phone['reason']) ?></p>
      <?php endif; ?>
    </div>

    <!-- CONTACT INFORMATION -->
    <?php if ($phone['verification_status'] === 'found' && $fullName && $fullName !== 'N/A  N/A'): ?>
    <div class="contact-box">
      <h3><?= htmlspecialchars($fullName) ?></h3>
      <p style="font-size:1.2rem;color:#64748b;margin:0;">
        <i class="fa-solid fa-id-card me-2"></i><?= strtoupper($phone['id_type']) ?>: <?= htmlspecialchars($phone['id_number']) ?>
      </p>
    </div>
    
    <!-- PHONE NUMBERS -->
    <div class="contact-section">
      <h4><i class="fa-solid fa-phone"></i> Contact Information</h4>
      
      <?php if ($phone['mobile_phone']): ?>
      <div class="contact-card mobile">
        <div class="contact-icon">
          <i class="fa-solid fa-mobile-screen-button"></i>
        </div>
        <div class="contact-details">
          <div class="label"><i class="fa-solid fa-mobile"></i> Mobile Phone</div>
          <div class="value"><?= htmlspecialchars($phone['mobile_phone']) ?></div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if ($phone['home_phone']): ?>
      <div class="contact-card home">
        <div class="contact-icon">
          <i class="fa-solid fa-house"></i>
        </div>
        <div class="contact-details">
          <div class="label"><i class="fa-solid fa-phone"></i> Home Telephone</div>
          <div class="value"><?= htmlspecialchars($phone['home_phone']) ?></div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if ($phone['work_phone']): ?>
      <div class="contact-card work">
        <div class="contact-icon">
          <i class="fa-solid fa-briefcase"></i>
        </div>
        <div class="contact-details">
          <div class="label"><i class="fa-solid fa-phone-office"></i> Work Telephone</div>
          <div class="value"><?= htmlspecialchars($phone['work_phone']) ?></div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if ($phone['email']): ?>
      <div class="contact-card email">
        <div class="contact-icon">
          <i class="fa-solid fa-envelope"></i>
        </div>
        <div class="contact-details">
          <div class="label"><i class="fa-solid fa-at"></i> Email Address</div>
          <div class="value"><?= htmlspecialchars($phone['email']) ?></div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if (!$phone['mobile_phone'] && !$phone['home_phone'] && !$phone['work_phone'] && !$phone['email']): ?>
      <div style="text-align:center;padding:40px;color:#64748b;">
        <i class="fa-solid fa-phone-slash" style="font-size:3rem;margin-bottom:16px;display:block;"></i>
        <p style="font-size:1.1rem;margin:0;">No contact information available</p>
      </div>
      <?php endif; ?>
    </div>
    
    <!-- PERSONAL DETAILS -->
    <div class="contact-section">
      <h4><i class="fa-solid fa-user-circle"></i> Personal Information</h4>
      <div class="info-grid">
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-id-card"></i> <?= strtoupper($phone['id_type']) ?></div>
          <div class="value highlight"><?= htmlspecialchars($phone['id_number']) ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-user"></i> Full Name</div>
          <div class="value"><?= htmlspecialchars($fullName) ?></div>
        </div>
        
        <?php if ($phone['first_name'] !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-user"></i> First Name</div>
          <div class="value"><?= htmlspecialchars($phone['first_name']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($phone['middle_name']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-user"></i> Middle Name</div>
          <div class="value"><?= htmlspecialchars($phone['middle_name']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($phone['last_name'] !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-user"></i> Last Name</div>
          <div class="value"><?= htmlspecialchars($phone['last_name']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($phone['dob'] !== 'N/A' && $phone['dob']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-calendar-days"></i> Date of Birth</div>
          <div class="value"><?= htmlspecialchars(date('M d, Y', strtotime($phone['dob']))) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($phone['gender'] !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-venus-mars"></i> Gender</div>
          <div class="value"><?= ucfirst(htmlspecialchars($phone['gender'])) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($phone['marital_status'] !== 'N/A'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-heart"></i> Marital Status</div>
          <div class="value"><?= ucfirst(htmlspecialchars($phone['marital_status'])) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-globe"></i> Country</div>
          <div class="value"><?= htmlspecialchars($phone['country']) ?></div>
        </div>
      </div>
    </div>
    
    <?php endif; ?>

    <!-- VERIFICATION DETAILS -->
    <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:2px solid #22c55e;border-radius:20px;padding:40px;margin-top:60px;">
      <h5 style="font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:24px;text-align:center;">
        <i class="fa-solid fa-shield-check me-2" style="color:#22c55e;"></i> Verification Details
      </h5>
      
      <div class="info-grid">
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-check-circle"></i> Verification Status</div>
          <div class="value" style="color:<?= $phone['verification_status'] === 'found' ? '#22c55e' : '#ef4444' ?>;">
            <?= strtoupper($phone['verification_status']) ?>
          </div>
        </div>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-fingerprint"></i> Verification Type</div>
          <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($phone['verification_type']) ?></div>
        </div>
        
        <?php if ($phone['verification_id']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-barcode"></i> YouVerify Reference</div>
          <div class="value" style="font-size:1rem;font-family:monospace;"><?= htmlspecialchars($phone['verification_id']) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-clock"></i> Verified On</div>
          <div class="value" style="font-size:1rem;"><?= date('M d, Y H:i', strtotime($phone['verification_date'])) ?></div>
        </div>
      </div>
      
      <div style="margin-top:40px;text-align:center;background:white;border-radius:16px;padding:30px;">
        <i class="fa-solid fa-certificate fa-3x" style="color:#22c55e;margin-bottom:16px;"></i>
        <h6 style="font-weight:900;font-size:1.4rem;color:#0f172a;margin-bottom:12px;">
          PHONE VERIFICATION COMPLETE
        </h6>
        <p style="color:#64748b;font-size:1.05rem;margin:0;">
          This phone verification has been completed against official Kenyan records via YouVerify.<br>
          <strong>Verification completed on:</strong> <?= date('F d, Y \a\t h:i A') ?><br>
          <strong>Certificate ID:</strong> <?= htmlspecialchars($trx_id) ?>
        </p>
      </div>
    </div>

    <!-- ACTIONS -->
    <div class="text-center" style="margin-top:80px">
      <a href="generate-pdf.php?rid=<?= $rid ?>" class="btn-action">
        <i class="fa-solid fa-file-pdf"></i> Download PDF Report
      </a>
      <button class="btn-action secondary" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Print Report
      </button>
      <a href="phone-verification.php" class="btn-action secondary">
        <i class="fa-solid fa-rotate"></i> Verify Another Phone
      </a>
    </div>

    <div class="footer-note">
      <strong>READIWORK</strong> • Powered by YouVerify<br>
      This report is confidential and for the intended recipient only.<br>
      <small>© <?= date('Y') ?> Readiwork. All rights reserved.</small>
    </div>

  </div>
</div>

</body>
</html>