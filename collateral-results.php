<?php
/**
 * Collateral Verification Results
 * YouVerify - Kenya Vehicle Collateral Display
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

// Extract YouVerify collateral data according to API documentation
// API response structure: { success, statusCode, message, data: { ... } }
$yvResponse = $combinedResults['data'] ?? $combinedResults;
$collateralData = $yvResponse['data'] ?? $yvResponse;

// Extract collateral details from stored fields
$collaterals = !empty($request['address']) ? json_decode($request['address'], true) : [];
$creditors = !empty($request['business_name']) ? json_decode($request['business_name'], true) : [];

// Extract from API response if not in stored fields
if (empty($collaterals)) {
    $collaterals = $collateralData['collaterals'] ?? [];
}
if (empty($creditors)) {
    $creditors = $collateralData['creditors'] ?? [];
}

$currencyAmounts = $collateralData['currencyAmounts'] ?? [];

// Build collateral information
$collateral = [
    'collateral_id' => $collateralData['idNumber'] ?? $id,
    'status' => $collateralData['status'] ?? 'unknown',
    'verification_id' => $collateralData['id'] ?? '',
    'verification_date' => $collateralData['requestedAt'] ?? '',
    'verification_type' => $collateralData['type'] ?? 'keVehicleCollateral',
    'country' => $collateralData['country'] ?? 'KE',
    'reason' => $collateralData['reason'] ?? null,
    'all_validation_passed' => $collateralData['allValidationPassed'] ?? false,
    'is_consent' => $collateralData['isConsent'] ?? true,
];

// Extract first collateral and creditor for primary display
$primaryCollateral = $collaterals[0] ?? null;
$primaryCreditor = $creditors[0] ?? null;
$primaryAmount = $currencyAmounts[0] ?? null;

// Parse vehicle details from description if available
$vehicleDetails = [];
if ($primaryCollateral && isset($primaryCollateral['description'])) {
    $desc = $primaryCollateral['description'];
    
    // Extract Make/Model
    if (preg_match('/Make\s+([A-Z]+)\s*,\s*([A-Z0-9\s]+)/i', $desc, $matches)) {
        $vehicleDetails['make'] = trim($matches[1]);
        $vehicleDetails['model'] = trim($matches[2]);
    }
    
    // Extract Registration Number
    if (preg_match('/Reg\s+No\.?\s*:\s*([A-Z0-9]+)/i', $desc, $matches)) {
        $vehicleDetails['registration'] = trim($matches[1]);
    }
    
    // Extract Chassis Number
    if (preg_match('/Chassis\s+No\.?\s*:\s*([A-Z0-9\-]+)/i', $desc, $matches)) {
        $vehicleDetails['chassis'] = trim($matches[1]);
    }
    
    // Extract Engine Number
    if (preg_match('/Engine\s+No\.?\s*:\s*([A-Z0-9\-]+)/i', $desc, $matches)) {
        $vehicleDetails['engine'] = trim($matches[1]);
    }
}

$trx_id = 'COL-' . date('YmdHis') . '-' . strtoupper(substr($id, -6));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Kenya Vehicle Collateral Verification Report | Readiwork</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--primary:#22c55e;--primary-dark:#16a34a;--warning:#f97316;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#f8fafc 0%,#ecfdf5 100%);color:#0f172a;padding-top:80px;padding-bottom:100px;}
.card-box{background:white;border-radius:32px;box-shadow:0 30px 80px rgba(0,0,0,0.1);padding:60px;margin:50px auto;max-width:1200px;opacity:0;animation:fadeInUp 0.8s ease forwards 0.2s;}
@keyframes fadeInUp{to{opacity:1;transform:translateY(0);}}
.header{text-align:center;margin-bottom:60px;}
.header h2{font-weight:900;font-size:3rem;background:linear-gradient(135deg,#22c55e,#16a34a);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:16px;}
.header .provider-badge{background:rgba(34,197,94,0.2);color:#22c55e;border:2px solid #22c55e;padding:8px 20px;border-radius:999px;font-weight:700;display:inline-block;margin:12px 8px;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px;}
.status-box{padding:60px;border-radius:24px;text-align:center;margin-bottom:60px;background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%);border-left:8px solid #22c55e;}
.status-box.not-found{background:linear-gradient(135deg,#fef2f2 0%,#fecaca 100%);border-left:8px solid #ef4444;}
.status-box.encumbered{background:linear-gradient(135deg,#fff7ed 0%,#fed7aa 100%);border-left:8px solid #f97316;}
.status-box i{font-size:6rem;margin-bottom:24px;display:block;color:#22c55e;animation:pulse 2s infinite;}
.status-box.not-found i{color:#ef4444;}
.status-box.encumbered i{color:#f97316;}
@keyframes pulse{0%,100%{transform:scale(1);}50%{transform:scale(1.1);}}
.status-box h3{font-weight:900;font-size:3rem;color:#0f172a;margin-bottom:16px;}
.status-box p{color:#64748b;font-size:1.3rem;}
.verification-badges{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:30px;}
.verification-badge{background:white;border:2px solid #22c55e;border-radius:12px;padding:16px 24px;text-align:center;min-width:140px;}
.verification-badge .badge-label{font-size:0.85rem;font-weight:600;text-transform:uppercase;margin-bottom:8px;color:#64748b;}
.verification-badge .badge-value{font-size:1.3rem;font-weight:900;color:#22c55e;}
.verification-badge .badge-value.warning{color:#f97316;}
.verification-badge .badge-value.danger{color:#ef4444;}
.vehicle-box{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:3px solid rgba(34,197,94,0.3);border-radius:24px;padding:50px;margin-bottom:60px;position:relative;overflow:hidden;}
.vehicle-box.encumbered{background:linear-gradient(135deg,#fff7ed 0%,#fed7aa 100%);border:3px solid rgba(249,115,22,0.3);}
.vehicle-box::before{content:'🚗';position:absolute;top:-20px;right:-20px;font-size:180px;opacity:0.05;}
.vehicle-box h3{font-size:2.5rem;font-weight:900;color:#0f172a;margin-bottom:24px;position:relative;z-index:2;}
.vehicle-box .collateral-id{font-size:2.2rem;font-weight:800;color:#22c55e;font-family:'Courier New',monospace;letter-spacing:4px;margin-bottom:30px;padding:20px;background:white;border-radius:12px;display:inline-block;}
.vehicle-box.encumbered .collateral-id{color:#f97316;}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-top:40px;}
.info-item{background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);padding:32px;border-radius:20px;border:2px solid #e2e8f0;transition:all 0.4s ease;position:relative;overflow:hidden;}
.info-item::before{content:'';position:absolute;top:0;left:0;width:4px;height:0;background:#22c55e;transition:height 0.4s;}
.info-item:hover{border-color:#22c55e;transform:translateY(-6px);box-shadow:0 15px 40px rgba(34,197,94,0.15);}
.info-item:hover::before{height:100%;}
.info-item .label{font-size:0.95rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.info-item .label i{color:#22c55e;}
.info-item .value{font-size:1.5rem;font-weight:800;color:#0f172a;word-break:break-word;}
.info-item .value.highlight{color:#22c55e;font-size:1.8rem;}
.info-item .value.warning{color:#f97316;}
.creditor-section{background:linear-gradient(135deg,#fff7ed 0%,#fed7aa 100%);border:2px solid #f97316;border-radius:20px;padding:40px;margin-top:60px;}
.creditor-section h5{font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:24px;}
.creditor-card{background:white;padding:30px;border-radius:16px;margin-bottom:20px;border-left:4px solid #f97316;}
.creditor-card h6{font-weight:800;font-size:1.3rem;color:#f97316;margin-bottom:16px;}
.creditor-detail{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #e2e8f0;}
.creditor-detail:last-child{border-bottom:none;}
.creditor-detail .field{font-weight:700;color:#64748b;}
.creditor-detail .value{font-weight:900;color:#0f172a;}
.collateral-list{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:2px solid #22c55e;border-radius:20px;padding:40px;margin-top:40px;}
.collateral-list h5{font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:24px;}
.collateral-item{background:white;padding:24px;border-radius:12px;margin-bottom:16px;border-left:4px solid #22c55e;}
.btn-action{background:linear-gradient(135deg,#22c55e,#16a34a);color:white;border:none;padding:20px 50px;border-radius:999px;font-weight:800;font-size:1.2rem;margin:20px 10px;box-shadow:0 15px 40px rgba(34,197,94,0.3);transition:all 0.4s ease;display:inline-flex;align-items:center;gap:12px;text-decoration:none;}
.btn-action:hover{transform:translateY(-6px);box-shadow:0 25px 60px rgba(34,197,94,0.5);color:white;}
.btn-action.secondary{background:linear-gradient(135deg,#3b82f6,#2563eb);box-shadow:0 15px 40px rgba(59,130,246,0.3);}
.footer-note{margin-top:100px;padding-top:50px;border-top:3px solid #e2e8f0;font-size:1.05rem;color:#64748b;text-align:center;line-height:2;}
.warning-box{background:rgba(249,115,22,0.1);border:2px solid #f97316;border-left:6px solid #f97316;border-radius:16px;padding:30px;margin:30px 0;}
.warning-box h6{color:#f97316;font-weight:900;font-size:1.2rem;margin-bottom:12px;}
.warning-box p{color:#64748b;margin:0;}
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
      <h2>Kenya Vehicle Collateral Verification</h2>
      <p style="color:#64748b;font-size:1.1rem;">Generated on <?= date('d F Y, h:i A') ?></p>
      <span class="provider-badge"><i class="fa-solid fa-fingerprint me-2"></i>YouVerify</span>
      <span class="provider-badge"><i class="fa-solid fa-shield-check me-2"></i>Movable Property Registry</span>
    </div>

    <!-- VERIFICATION STATUS -->
    <div class="status-box <?= $collateral['status'] !== 'found' ? 'not-found' : (count($creditors) > 0 ? 'encumbered' : '') ?>">
      <i class="fa-solid fa-<?= $collateral['status'] === 'found' ? (count($creditors) > 0 ? 'exclamation-triangle' : 'car') : 'circle-xmark' ?>"></i>
      <h3>
        <?php if ($collateral['status'] === 'found' && count($creditors) > 0): ?>
          COLLATERAL FOUND - ENCUMBERED
        <?php elseif ($collateral['status'] === 'found'): ?>
          COLLATERAL VERIFIED - CLEAR
        <?php else: ?>
          COLLATERAL NOT FOUND
        <?php endif; ?>
      </h3>
      <p>
        <?php if ($collateral['status'] === 'found' && count($creditors) > 0): ?>
          Vehicle found with active security interest registered
        <?php elseif ($collateral['status'] === 'found'): ?>
          Vehicle found with no registered security interests
        <?php else: ?>
          Collateral ID not found in movable property registry
        <?php endif; ?>
      </p>
      
      <div class="verification-badges">
        <div class="verification-badge">
          <div class="badge-label">Registry Status</div>
          <div class="badge-value <?= $collateral['status'] !== 'found' ? 'danger' : '' ?>">
            <?= $collateral['status'] === 'found' ? '✓ FOUND' : '✗ NOT FOUND' ?>
          </div>
        </div>
        
        <div class="verification-badge">
          <div class="badge-label">Security Interest</div>
          <div class="badge-value <?= count($creditors) > 0 ? 'warning' : '' ?>">
            <?= count($creditors) > 0 ? '⚠ ENCUMBERED' : '✓ CLEAR' ?>
          </div>
        </div>
        
        <?php if (count($creditors) > 0): ?>
        <div class="verification-badge">
          <div class="badge-label">Creditors</div>
          <div class="badge-value warning"><?= count($creditors) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="verification-badge">
          <div class="badge-label">Verified On</div>
          <div class="badge-value" style="font-size:0.95rem;"><?= date('M d, Y', strtotime($collateral['verification_date'])) ?></div>
        </div>
      </div>
    </div>

    <!-- COLLATERAL INFORMATION -->
    <?php if ($collateral['status'] === 'found' && $primaryCollateral): ?>
    
    <?php if (count($creditors) > 0): ?>
    <div class="warning-box">
      <h6><i class="fa-solid fa-exclamation-triangle me-2"></i>Security Interest Alert</h6>
      <p>This vehicle has registered security interest(s). The vehicle is encumbered and there are <?= count($creditors) ?> secured creditor(s) with claims on this asset.</p>
    </div>
    <?php endif; ?>
    
    <div class="vehicle-box <?= count($creditors) > 0 ? 'encumbered' : '' ?>">
      <h3>
        <?php if (!empty($vehicleDetails['make']) && !empty($vehicleDetails['model'])): ?>
          <?= htmlspecialchars($vehicleDetails['make']) ?> <?= htmlspecialchars($vehicleDetails['model']) ?>
        <?php else: ?>
          Vehicle Collateral Details
        <?php endif; ?>
      </h3>
      <div class="collateral-id">
        <i class="fa-solid fa-barcode me-3"></i><?= htmlspecialchars($collateral['collateral_id']) ?>
      </div>
    </div>
    
    <!-- VEHICLE DETAILS -->
    <div class="info-grid">
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-barcode"></i> Collateral ID</div>
        <div class="value highlight"><?= htmlspecialchars($collateral['collateral_id']) ?></div>
      </div>
      
      <?php if (!empty($vehicleDetails['make'])): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-industry"></i> Make</div>
        <div class="value"><?= htmlspecialchars($vehicleDetails['make']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($vehicleDetails['model'])): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-car"></i> Model</div>
        <div class="value"><?= htmlspecialchars($vehicleDetails['model']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($vehicleDetails['registration'])): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-id-card"></i> Registration Number</div>
        <div class="value highlight"><?= htmlspecialchars($vehicleDetails['registration']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($vehicleDetails['chassis'])): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-hashtag"></i> Chassis Number</div>
        <div class="value" style="font-size:1.2rem;font-family:monospace;"><?= htmlspecialchars($vehicleDetails['chassis']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($vehicleDetails['engine'])): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-cog"></i> Engine Number</div>
        <div class="value" style="font-size:1.2rem;font-family:monospace;"><?= htmlspecialchars($vehicleDetails['engine']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($primaryCollateral && isset($primaryCollateral['serialNo'])): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-barcode"></i> Serial Number</div>
        <div class="value" style="font-size:1.2rem;font-family:monospace;"><?= htmlspecialchars($primaryCollateral['serialNo']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($primaryCollateral && isset($primaryCollateral['type'])): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-tag"></i> Collateral Type</div>
        <div class="value"><?= ucwords(str_replace('_', ' ', htmlspecialchars($primaryCollateral['type']))) ?></div>
      </div>
      <?php endif; ?>
    </div>
    
    <!-- FULL DESCRIPTION -->
    <?php if ($primaryCollateral && isset($primaryCollateral['description'])): ?>
    <div class="collateral-list">
      <h5><i class="fa-solid fa-file-lines me-2" style="color:#22c55e;"></i>Complete Vehicle Description</h5>
      <div class="collateral-item">
        <pre style="margin:0;font-family:'Inter',sans-serif;white-space:pre-wrap;font-size:1rem;color:#0f172a;"><?= htmlspecialchars($primaryCollateral['description']) ?></pre>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- SECURED CREDITORS -->
    <?php if (count($creditors) > 0): ?>
    <div class="creditor-section">
      <h5><i class="fa-solid fa-building-columns me-2" style="color:#f97316;"></i>Secured Creditor Information</h5>
      
      <?php foreach ($creditors as $index => $creditor): ?>
      <div class="creditor-card">
        <h6>Creditor #<?= $index + 1 ?>: <?= htmlspecialchars($creditor['name'] ?? 'N/A') ?></h6>
        
        <div class="creditor-detail">
          <span class="field">Creditor Name:</span>
          <span class="value"><?= htmlspecialchars($creditor['name'] ?? 'N/A') ?></span>
        </div>
        
        <?php if (isset($creditor['type'])): ?>
        <div class="creditor-detail">
          <span class="field">Creditor Type:</span>
          <span class="value"><?= ucwords(str_replace('_', ' ', htmlspecialchars($creditor['type']))) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if (isset($creditor['categoryOfSecuredCreditor'])): ?>
        <div class="creditor-detail">
          <span class="field">Security Category:</span>
          <span class="value"><?= ucfirst(htmlspecialchars($creditor['categoryOfSecuredCreditor'])) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($primaryAmount && $index === 0): ?>
        <div class="creditor-detail" style="background:rgba(249,115,22,0.1);margin-top:16px;padding:16px;border-radius:8px;">
          <span class="field" style="font-size:1.1rem;">Security Amount:</span>
          <span class="value" style="font-size:1.4rem;color:#f97316;">
            <?= htmlspecialchars($primaryAmount['currency'] ?? 'KES') ?> 
            <?= number_format((float)($primaryAmount['amount'] ?? 0), 2) ?>
          </span>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- ALL CURRENCY AMOUNTS -->
    <?php if (count($currencyAmounts) > 1): ?>
    <div style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:2px solid #e2e8f0;border-radius:20px;padding:40px;margin-top:40px;">
      <h5 style="font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:24px;">
        <i class="fa-solid fa-money-bill-wave me-2" style="color:#22c55e;"></i> Security Interest Amounts
      </h5>
      <?php foreach ($currencyAmounts as $amount): ?>
      <div style="background:white;padding:20px;border-radius:12px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-weight:700;color:#64748b;">Amount:</span>
        <span style="font-weight:900;font-size:1.3rem;color:#f97316;">
          <?= htmlspecialchars($amount['currency'] ?? 'KES') ?> 
          <?= number_format((float)($amount['amount'] ?? 0), 2) ?>
        </span>
      </div>
      <?php endforeach; ?>
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
          <div class="label"><i class="fa-solid fa-check-circle"></i> Verification Status</div>
          <div class="value" style="color:<?= $collateral['status'] === 'found' ? '#22c55e' : '#ef4444' ?>;">
            <?= strtoupper($collateral['status']) ?>
          </div>
        </div>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-fingerprint"></i> Verification Type</div>
          <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($collateral['verification_type']) ?></div>
        </div>
        
        <?php if ($collateral['verification_id']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-barcode"></i> YouVerify Reference</div>
          <div class="value" style="font-size:1rem;font-family:monospace;"><?= htmlspecialchars($collateral['verification_id']) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-clock"></i> Requested At</div>
          <div class="value" style="font-size:1rem;"><?= date('M d, Y H:i', strtotime($collateral['verification_date'])) ?></div>
        </div>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-globe"></i> Country</div>
          <div class="value"><?= htmlspecialchars($collateral['country']) ?></div>
        </div>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-check-double"></i> All Validations</div>
          <div class="value" style="color:<?= $collateral['all_validation_passed'] ? '#22c55e' : '#64748b' ?>;">
            <?= $collateral['all_validation_passed'] ? '✓ PASSED' : 'N/A' ?>
          </div>
        </div>
      </div>
      
      <div style="margin-top:40px;text-align:center;background:white;border-radius:16px;padding:30px;">
        <i class="fa-solid fa-certificate fa-3x" style="color:#22c55e;margin-bottom:16px;"></i>
        <h6 style="font-weight:900;font-size:1.4rem;color:#0f172a;margin-bottom:12px;">
          COLLATERAL VERIFICATION COMPLETE
        </h6>
        <p style="color:#64748b;font-size:1.05rem;margin:0;">
          This vehicle collateral has been verified against official Kenya Movable Property Registry via YouVerify.<br>
          <strong>Verification completed on:</strong> <?= date('F d, Y \a\t h:i A') ?><br>
          <strong>Certificate ID:</strong> <?= htmlspecialchars($trx_id) ?>
        </p>
        
        <?php if (count($creditors) > 0): ?>
        <div style="margin-top:24px;padding:20px;background:rgba(249,115,22,0.1);border-radius:12px;border:2px solid #f97316;">
          <p style="color:#f97316;font-weight:700;margin:0;">
            <i class="fa-solid fa-exclamation-triangle me-2"></i>
            This vehicle has <?= count($creditors) ?> registered secured creditor(s). Due diligence recommended.
          </p>
        </div>
        <?php endif; ?>
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
      <a href="collateral-verification.php" class="btn-action secondary">
        <i class="fa-solid fa-rotate"></i> Verify Another Vehicle
      </a>
    </div>

    <div class="footer-note">
      <strong>READIWORK</strong> • Powered by YouVerify<br>
      This report is confidential and for the intended recipient only.<br>
      <?php if (count($creditors) > 0): ?>
      <strong style="color:#f97316;">IMPORTANT:</strong> This vehicle is encumbered with registered security interest(s).<br>
      <?php endif; ?>
      <small>© <?= date('Y') ?> Readiwork. All rights reserved.</small>
    </div>

  </div>
</div>

</body>
</html>