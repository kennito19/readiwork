<?php
/**
 * Tax PIN Verification Results
 * YouVerify - Kenya KRA PIN Display
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
   
    $kra_pin = $request['national_id'];

} catch (Exception $e) {
    error_log('Results page error: ' . $e->getMessage());
    die('Error loading results.');
}

// Extract YouVerify PIN data according to API documentation
$yvResponse = $combinedResults['data'] ?? $combinedResults;
$pinData = $yvResponse['data'] ?? $yvResponse;

// Extract PIN information
$pin = [
    'pin' => $pinData['pin'] ?? $kra_pin,
    'taxpayer_name' => $pinData['taxpayerName'] ?? 'N/A',
    'pin_status' => $pinData['pinStatus'] ?? 'N/A',
    'pin_current_status' => $pinData['pinCurrentStatus'] ?? 'N/A',
    'obligation_name' => $pinData['obligationName'] ?? 'N/A',
    'itax_status' => $pinData['itaxStatus'] ?? 'N/A',
    'effective_from' => $pinData['effectiveFromDate'] ?? null,
    'effective_to' => $pinData['effectiveToDate'] ?? null,
    'verification_status' => $pinData['status'] ?? 'unknown',
    'verification_id' => $pinData['id'] ?? '',
    'verification_date' => $pinData['requestedAt'] ?? '',
    'verification_type' => $pinData['type'] ?? 'kePinCheck',
    'reason' => $pinData['reason'] ?? null,
    'country' => $pinData['country'] ?? 'KE',
];

$trx_id = 'PIN-' . date('YmdHis') . '-' . strtoupper(substr($kra_pin, -6));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Kenya Tax PIN Verification Report | Readiwork</title>
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
.pin-box{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:3px solid rgba(34,197,94,0.3);border-radius:24px;padding:50px;margin-bottom:60px;position:relative;overflow:hidden;}
.pin-box::before{content:'📋';position:absolute;top:-40px;right:-40px;font-size:200px;opacity:0.05;}
.pin-box h3{font-size:2.5rem;font-weight:900;color:#0f172a;margin-bottom:16px;position:relative;z-index:2;}
.pin-box .pin-number{font-size:2rem;font-weight:800;font-family:monospace;color:#22c55e;letter-spacing:4px;margin-bottom:24px;}
.status-badge{display:inline-block;padding:12px 24px;border-radius:999px;font-weight:800;font-size:1rem;text-transform:uppercase;letter-spacing:1px;margin:8px;}
.status-badge.active{background:#22c55e;color:white;}
.status-badge.inactive{background:#ef4444;color:white;}
.status-badge.registered{background:#3b82f6;color:white;}
.tax-section{background:white;border-radius:20px;padding:40px;margin-bottom:30px;border:2px solid #e2e8f0;}
.tax-section h4{font-weight:900;font-size:1.8rem;color:#0f172a;margin-bottom:24px;display:flex;align-items:center;gap:12px;}
.tax-section h4 i{color:#22c55e;}
.tax-detail{display:flex;justify-content:space-between;padding:20px 0;border-bottom:1px solid #e2e8f0;}
.tax-detail:last-child{border-bottom:none;}
.tax-detail .label{font-weight:700;color:#64748b;font-size:1.1rem;}
.tax-detail .value{font-weight:800;color:#0f172a;font-size:1.2rem;text-align:right;}
.tax-detail .value.highlight{color:#22c55e;font-size:1.4rem;}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-top:40px;}
.info-item{background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);padding:32px;border-radius:20px;border:2px solid #e2e8f0;transition:all 0.4s ease;position:relative;overflow:hidden;}
.info-item::before{content:'';position:absolute;top:0;left:0;width:4px;height:0;background:#22c55e;transition:height 0.4s;}
.info-item:hover{border-color:#22c55e;transform:translateY(-6px);box-shadow:0 15px 40px rgba(34,197,94,0.15);}
.info-item:hover::before{height:100%;}
.info-item .label{font-size:0.95rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.info-item .label i{color:#22c55e;}
.info-item .value{font-size:1.5rem;font-weight:800;color:#0f172a;word-break:break-word;}
.btn-action{background:linear-gradient(135deg,#22c55e,#16a34a);color:white;border:none;padding:20px 50px;border-radius:999px;font-weight:800;font-size:1.2rem;margin:20px 10px;box-shadow:0 15px 40px rgba(34,197,94,0.3);transition:all 0.4s ease;display:inline-flex;align-items:center;gap:12px;text-decoration:none;}
.btn-action:hover{transform:translateY(-6px);box-shadow:0 25px 60px rgba(34,197,94,0.5);color:white;}
.btn-action.secondary{background:linear-gradient(135deg,#3b82f6,#2563eb);box-shadow:0 15px 40px rgba(59,130,246,0.3);}
.footer-note{margin-top:100px;padding-top:50px;border-top:3px solid #e2e8f0;font-size:1.05rem;color:#64748b;text-align:center;line-height:2;}
@media (max-width:768px){.card-box{padding:40px 24px;}.status-box{padding:40px 24px;}.status-box i{font-size:4rem;}.info-grid{grid-template-columns:1fr;}.tax-detail{flex-direction:column;gap:8px;}.tax-detail .value{text-align:left;}}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
<?php include 'includes/navbar.php'; ?>
</nav>

<div class="container">
  <div class="card-box">
    
    <div class="header">
      <h2>Kenya Tax PIN Verification</h2>
      <p style="color:#64748b;font-size:1.1rem;">Generated on <?= date('d F Y, h:i A') ?></p>
      <span class="provider-badge"><i class="fa-solid fa-fingerprint me-2"></i>YouVerify</span>
      <span class="provider-badge"><i class="fa-solid fa-building-columns me-2"></i>KRA Official</span>
    </div>

    <!-- VERIFICATION STATUS -->
    <div class="status-box <?= $pin['verification_status'] !== 'found' ? 'not-found' : '' ?>">
      <i class="fa-solid fa-<?= $pin['verification_status'] === 'found' ? 'circle-check' : 'circle-xmark' ?>"></i>
      <h3><?= $pin['verification_status'] === 'found' ? 'PIN VERIFIED' : 'PIN NOT FOUND' ?></h3>
      <p><?= $pin['verification_status'] === 'found' ? 'Tax PIN found in KRA database' : 'Tax PIN not found in database' ?></p>
      <?php if ($pin['reason']): ?>
      <p style="margin-top:16px;font-size:1rem;color:#ef4444;">Reason: <?= htmlspecialchars($pin['reason']) ?></p>
      <?php endif; ?>
    </div>

    <!-- PIN INFORMATION -->
    <?php if ($pin['verification_status'] === 'found' && $pin['taxpayer_name'] !== 'N/A'): ?>
    <div class="pin-box">
      <h3><?= htmlspecialchars($pin['taxpayer_name']) ?></h3>
      <div class="pin-number">KRA PIN: <?= htmlspecialchars($pin['pin']) ?></div>
      
      <div style="margin-top:24px;">
        <?php if ($pin['pin_status'] !== 'N/A'): ?>
        <span class="status-badge <?= strtolower($pin['pin_status']) === 'active' ? 'active' : 'inactive' ?>">
          <i class="fa-solid fa-<?= strtolower($pin['pin_status']) === 'active' ? 'check-circle' : 'times-circle' ?> me-2"></i>
          <?= htmlspecialchars($pin['pin_status']) ?>
        </span>
        <?php endif; ?>
        
        <?php if ($pin['pin_current_status'] !== 'N/A'): ?>
        <span class="status-badge registered">
          <i class="fa-solid fa-id-badge me-2"></i>
          <?= htmlspecialchars($pin['pin_current_status']) ?>
        </span>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- TAX DETAILS -->
    <div class="tax-section">
      <h4><i class="fa-solid fa-file-invoice-dollar"></i> Tax Information</h4>
      
      <?php if ($pin['obligation_name'] !== 'N/A'): ?>
      <div class="tax-detail">
        <div class="label"><i class="fa-solid fa-receipt me-2"></i> Tax Obligation</div>
        <div class="value"><?= htmlspecialchars($pin['obligation_name']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($pin['itax_status'] !== 'N/A'): ?>
      <div class="tax-detail">
        <div class="label"><i class="fa-solid fa-computer me-2"></i> iTax Status</div>
        <div class="value highlight"><?= htmlspecialchars($pin['itax_status']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($pin['effective_from']): ?>
      <div class="tax-detail">
        <div class="label"><i class="fa-solid fa-calendar-check me-2"></i> Effective From</div>
        <div class="value"><?= date('F d, Y', strtotime($pin['effective_from'])) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($pin['effective_to']): ?>
      <div class="tax-detail">
        <div class="label"><i class="fa-solid fa-calendar-xmark me-2"></i> Effective To</div>
        <div class="value"><?= date('F d, Y', strtotime($pin['effective_to'])) ?></div>
      </div>
      <?php else: ?>
      <div class="tax-detail">
        <div class="label"><i class="fa-solid fa-infinity me-2"></i> Expiration</div>
        <div class="value highlight">No Expiry Date</div>
      </div>
      <?php endif; ?>
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
          <div class="value" style="color:<?= $pin['verification_status'] === 'found' ? '#22c55e' : '#ef4444' ?>;">
            <?= strtoupper($pin['verification_status']) ?>
          </div>
        </div>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-fingerprint"></i> Verification Type</div>
          <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($pin['verification_type']) ?></div>
        </div>
        
        <?php if ($pin['verification_id']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-barcode"></i> YouVerify Reference</div>
          <div class="value" style="font-size:1rem;font-family:monospace;"><?= htmlspecialchars(substr($pin['verification_id'], 0, 20)) ?>...</div>
        </div>
        <?php endif; ?>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-clock"></i> Verified On</div>
          <div class="value" style="font-size:1rem;"><?= date('M d, Y H:i', strtotime($pin['verification_date'])) ?></div>
        </div>
      </div>
      
      <div style="margin-top:40px;text-align:center;background:white;border-radius:16px;padding:30px;">
        <i class="fa-solid fa-certificate fa-3x" style="color:#22c55e;margin-bottom:16px;"></i>
        <h6 style="font-weight:900;font-size:1.4rem;color:#0f172a;margin-bottom:12px;">
          TAX PIN VERIFICATION COMPLETE
        </h6>
        <p style="color:#64748b;font-size:1.05rem;margin:0;">
          This Tax PIN has been verified against Kenya Revenue Authority official records via YouVerify.<br>
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
      <a href="pin-verification.php" class="btn-action secondary">
        <i class="fa-solid fa-rotate"></i> Verify Another PIN
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