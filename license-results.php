<?php
/**
 * Driver's License Verification Results
 * YouVerify - Kenya Driver's License Display
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
    if (!in_array($request['status'], ['paid','completed'])) {
        die('Payment not completed. <a href="payment.php?rid='.$rid.'">Complete payment</a>');
    }

    $combinedResults = json_decode($request['result'], true);
    if (!$combinedResults || !is_array($combinedResults)) {
        die('No verification data available.');
    }

    $id = $request['national_id'];

} catch (Exception $e) {
    error_log('License Results Error: ' . $e->getMessage());
    die('Error loading results.');
}

/* ============================
   Extract Driver's License API Data
   Exact API Response Structure from Documentation
   ============================ */
$yvResponse = $combinedResults['data'] ?? $combinedResults;
$licenseData = $yvResponse['data'] ?? $yvResponse;

// Extract address data
$addressData = $licenseData['address'] ?? [];

// Extract Smart DL details
$smartDlData = $licenseData['smartDlDetails'] ?? [];

$license = [
    'status'            => $licenseData['status'] ?? 'unknown',
    'reason'            => $licenseData['reason'] ?? null,
    
    // Personal Info
    'full_name'         => $licenseData['fullName'] ?? 'N/A',
    'national_id'       => $licenseData['nationalId'] ?? 'N/A',
    'dob'               => $licenseData['dateOfBirth'] ?? 'N/A',
    'gender'            => $licenseData['gender'] ?? 'N/A',
    'blood_group'       => $licenseData['bloodGroup'] ?? null,
    
    // License Info
    'license_number'    => $licenseData['licenseNumber'] ?? $id,
    'interim_number'    => $licenseData['interimNumber'] ?? null,
    'id_number'         => $licenseData['idNumber'] ?? $id,
    'class_of_license'  => $licenseData['classOfLicense'] ?? 'N/A',
    'issued_date'       => $licenseData['issuedDate'] ?? null,
    'expired_date'      => $licenseData['expiredDate'] ?? null,
    
    // Contact Info
    'mobile'            => $licenseData['mobile'] ?? null,
    'email'             => $licenseData['email'] ?? null,
    'kra'               => $licenseData['kra'] ?? null,
    
    // Address
    'address_line'      => $addressData['addressLine'] ?? null,
    'city'              => $addressData['city'] ?? null,
    'town'              => $addressData['town'] ?? null,
    'state'             => $addressData['state'] ?? null,
    'lga'               => $addressData['lga'] ?? null,
    
    // Smart DL Details
    'has_smart_dl'              => $smartDlData['hasSmartDl'] ?? null,
    'smart_dl_booking_center'   => $smartDlData['smartDlBookingTestCenter'] ?? null,
    'smart_dl_booking_status'   => $smartDlData['smartDLBookingStatus'] ?? null,
    'smart_dl_booking_date'     => $smartDlData['smartDlBookingDate'] ?? null,
    'smart_dl_booking_start'    => $smartDlData['smartDlBookingStartDate'] ?? null,
    
    // Verification Meta
    'verification_id'   => $licenseData['id'] ?? '',
    'requested_at'      => $licenseData['requestedAt'] ?? '',
    'type'              => $licenseData['type'] ?? 'keDriversLicense',
    'country'           => $licenseData['country'] ?? 'KE',
    'consent'           => $licenseData['isConsent'] ?? false,
    'all_passed'        => $licenseData['allValidationPassed'] ?? false,
    'data_validation'   => $licenseData['dataValidation'] ?? false,
    'selfie_validation' => $licenseData['selfieValidation'] ?? false,
];

// Build full address string
$addressParts = array_filter([
    $license['address_line'],
    $license['city'],
    $license['town'],
    $license['state']
]);
$license['full_address'] = !empty($addressParts) ? implode(', ', $addressParts) : null;

$trx_id = 'DL-' . date('YmdHis') . '-' . strtoupper(substr($license['license_number'], -6));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Kenya Driver's License Verification Report | Readiwork</title>
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
.provider-badge{background:rgba(34,197,94,0.2);color:#22c55e;border:2px solid #22c55e;padding:8px 20px;border-radius:999px;font-weight:700;display:inline-block;margin:12px 8px;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px;}
.status-box{padding:60px;border-radius:24px;text-align:center;margin-bottom:60px;background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%);border-left:8px solid #22c55e;}
.status-box.not-found{background:linear-gradient(135deg,#fef2f2 0%,#fecaca 100%);border-left:8px solid #ef4444;}
.status-box i{font-size:6rem;margin-bottom:24px;display:block;color:#22c55e;animation:pulse 2s infinite;}
.status-box.not-found i{color:#ef4444;}
@keyframes pulse{0%,100%{transform:scale(1);}50%{transform:scale(1.1);}}
.status-box h3{font-weight:900;font-size:3rem;color:#0f172a;margin-bottom:16px;}
.status-box p{color:#64748b;font-size:1.3rem;}
.verification-badges{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:30px;}
.verification-badge{background:white;border:2px solid #22c55e;border-radius:12px;padding:16px 24px;text-align:center;min-width:140px;}
.verification-badge .badge-label{font-size:0.85rem;font-weight:600;text-transform:uppercase;margin-bottom:8px;color:#64748b;}
.verification-badge .badge-value{font-size:1.3rem;font-weight:900;color:#22c55e;}
.license-box{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:3px solid rgba(34,197,94,0.3);border-radius:24px;padding:50px;margin-bottom:60px;position:relative;overflow:hidden;}
.license-box::before{content:'✓';position:absolute;top:-40px;right:-40px;font-size:200px;color:rgba(34,197,94,0.05);font-weight:900;}
.license-box h3{font-size:2.5rem;font-weight:900;color:#0f172a;margin-bottom:24px;position:relative;z-index:2;}
.license-box .license-number{font-size:2.2rem;font-weight:800;color:#22c55e;font-family:'Courier New',monospace;letter-spacing:4px;margin-bottom:30px;padding:20px;background:white;border-radius:12px;display:inline-block;}
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
@media (max-width:768px){.card-box{padding:40px 24px;}.status-box{padding:40px 24px;}.status-box i{font-size:4rem;}.info-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
<?php include 'includes/navbar.php'; ?>
</nav>

<div class="container">
  <div class="card-box">
    
    <div class="header">
      <h2>Kenya Driver's License Verification</h2>
      <p style="color:#64748b;font-size:1.1rem;">Generated on <?= date('d F Y, h:i A') ?></p>
      <span class="provider-badge"><i class="fa-solid fa-fingerprint me-2"></i>YouVerify</span>
      <span class="provider-badge"><i class="fa-solid fa-shield-check me-2"></i>NTSA Records</span>
    </div>

    <!-- VERIFICATION STATUS -->
    <div class="status-box <?= $license['status'] !== 'found' ? 'not-found' : '' ?>">
      <i class="fa-solid fa-<?= $license['status'] === 'found' ? 'id-card-clip' : 'circle-xmark' ?>"></i>
      <h3><?= $license['status'] === 'found' ? 'LICENSE VERIFIED' : 'LICENSE NOT FOUND' ?></h3>
      <p><?= $license['status'] === 'found' ? 'Driver\'s license found in official NTSA records' : 'License not found in database' ?></p>
      
      <div class="verification-badges">
        <div class="verification-badge">
          <div class="badge-label">Database Status</div>
          <div class="badge-value" style="<?= $license['status'] !== 'found' ? 'color:#ef4444;' : '' ?>">
            <?= $license['status'] === 'found' ? '✓ FOUND' : '✗ NOT FOUND' ?>
          </div>
        </div>
        
        <?php if ($license['class_of_license'] !== 'N/A'): ?>
        <div class="verification-badge">
          <div class="badge-label">License Class</div>
          <div class="badge-value"><?= strtoupper($license['class_of_license']) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="verification-badge">
          <div class="badge-label">Verified On</div>
          <div class="badge-value" style="font-size:0.95rem;"><?= date('M d, Y', strtotime($license['requested_at'])) ?></div>
        </div>
      </div>
    </div>

    <!-- LICENSE INFORMATION -->
    <?php if ($license['status'] === 'found' && $license['full_name'] !== 'N/A'): ?>
    <div class="license-box">
      <h3><?= htmlspecialchars($license['full_name']) ?></h3>
      <div class="license-number">
        <i class="fa-solid fa-id-card-clip me-3"></i><?= htmlspecialchars($license['license_number']) ?>
      </div>
    </div>
    
    <!-- LICENSE DETAILS -->
    <div class="info-grid">
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-id-card-clip"></i> License Number</div>
        <div class="value highlight"><?= htmlspecialchars($license['license_number']) ?></div>
      </div>
      
      <?php if ($license['interim_number']): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-id-badge"></i> Interim Number</div>
        <div class="value"><?= htmlspecialchars($license['interim_number']) ?></div>
      </div>
      <?php endif; ?>
      
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-user"></i> Full Name</div>
        <div class="value"><?= htmlspecialchars($license['full_name']) ?></div>
      </div>
      
      <?php if ($license['national_id'] !== 'N/A'): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-id-card"></i> National ID</div>
        <div class="value"><?= htmlspecialchars($license['national_id']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($license['dob'] !== 'N/A'): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-calendar-days"></i> Date of Birth</div>
        <div class="value"><?= htmlspecialchars($license['dob']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($license['gender'] !== 'N/A'): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-venus-mars"></i> Gender</div>
        <div class="value"><?= ucfirst($license['gender']) ?></div>
      </div>
      <?php endif; ?>
      
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-list-check"></i> License Class</div>
        <div class="value highlight"><?= strtoupper($license['class_of_license']) ?></div>
      </div>
      
      <?php if ($license['issued_date']): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-calendar-plus"></i> Issued Date</div>
        <div class="value"><?= date('M d, Y', strtotime($license['issued_date'])) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($license['expired_date']): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-calendar-xmark"></i> Expiry Date</div>
        <div class="value"><?= date('M d, Y', strtotime($license['expired_date'])) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($license['mobile']): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-mobile-screen"></i> Mobile</div>
        <div class="value"><?= htmlspecialchars($license['mobile']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($license['email']): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-envelope"></i> Email</div>
        <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($license['email']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($license['kra']): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-receipt"></i> KRA PIN</div>
        <div class="value" style="font-family:monospace;"><?= htmlspecialchars($license['kra']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($license['blood_group']): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-droplet"></i> Blood Group</div>
        <div class="value"><?= strtoupper($license['blood_group']) ?></div>
      </div>
      <?php endif; ?>
    </div>
    
    <!-- ADDRESS INFORMATION -->
    <?php if ($license['full_address']): ?>
    <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:2px solid #22c55e;border-radius:20px;padding:40px;margin-top:60px;">
      <h5 style="font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:24px;text-align:center;">
        <i class="fa-solid fa-location-dot me-2" style="color:#22c55e;"></i> Address Information
      </h5>
      
      <div class="info-grid">
        <?php if ($license['address_line']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-road"></i> Address Line</div>
          <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($license['address_line']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($license['city']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-city"></i> City</div>
          <div class="value"><?= htmlspecialchars($license['city']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($license['town']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-building"></i> Town</div>
          <div class="value"><?= htmlspecialchars($license['town']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($license['state']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-map-location-dot"></i> State/County</div>
          <div class="value"><?= htmlspecialchars($license['state']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- SMART DL DETAILS -->
    <?php if ($license['has_smart_dl']): ?>
    <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:2px solid #facc15;border-radius:20px;padding:40px;margin-top:40px;">
      <h5 style="font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:24px;text-align:center;">
        <i class="fa-solid fa-microchip me-2" style="color:#facc15;"></i> Smart DL Details
      </h5>
      
      <div class="info-grid">
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-circle-check"></i> Has Smart DL</div>
          <div class="value"><?= htmlspecialchars($license['has_smart_dl']) ?></div>
        </div>
        
        <?php if ($license['smart_dl_booking_status']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-calendar-check"></i> Booking Status</div>
          <div class="value"><?= htmlspecialchars($license['smart_dl_booking_status']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($license['smart_dl_booking_center']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-location-pin"></i> Test Center</div>
          <div class="value"><?= htmlspecialchars($license['smart_dl_booking_center']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($license['smart_dl_booking_date']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-calendar"></i> Booking Date</div>
          <div class="value"><?= htmlspecialchars($license['smart_dl_booking_date']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>

    <!-- VERIFICATION DETAILS -->
    <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:2px solid #22c55e;border-radius:20px;padding:40px;margin-top:60px;">
      <h5 style="font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:24px;text-align:center;">
        <i class="fa-solid fa-shield-check me-2" style="color:#22c55e;"></i> Verification Details
      </h5>
      
      <div class="info-grid">
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-check-circle"></i> Status</div>
          <div class="value" style="color:<?= $license['status'] === 'found' ? '#22c55e' : '#ef4444' ?>;">
            <?= strtoupper($license['status']) ?>
          </div>
        </div>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-fingerprint"></i> Type</div>
          <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($license['type']) ?></div>
        </div>
        
        <?php if ($license['verification_id']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-barcode"></i> Reference ID</div>
          <div class="value" style="font-size:1rem;font-family:monospace;"><?= htmlspecialchars($license['verification_id']) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-clock"></i> Requested At</div>
          <div class="value" style="font-size:1rem;"><?= date('M d, Y H:i', strtotime($license['requested_at'])) ?></div>
        </div>
      </div>
      
      <div style="margin-top:40px;text-align:center;background:white;border-radius:16px;padding:30px;">
        <i class="fa-solid fa-certificate fa-3x" style="color:#22c55e;margin-bottom:16px;"></i>
        <h6 style="font-weight:900;font-size:1.4rem;color:#0f172a;margin-bottom:12px;">
          DRIVER'S LICENSE VERIFICATION COMPLETE
        </h6>
        <p style="color:#64748b;font-size:1.05rem;margin:0;">
          This driver's license has been verified against official NTSA records via YouVerify.<br>
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
      <a href="drivers-license-verification.php" class="btn-action secondary">
        <i class="fa-solid fa-rotate"></i> Verify Another License
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