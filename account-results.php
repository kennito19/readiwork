<?php
/**
 * Bank Account Verification Results
 * YouVerify - Kenya Bank Account Display
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

    $account_number = $request['national_id'];

} catch (Exception $e) {
    error_log('Account Results Error: ' . $e->getMessage());
    die('Error loading results.');
}

/* ============================
   Extract Bank Account API Data
   API Response Structure: { success, statusCode, message, data: { data: { ... } } }
   ============================ */
$yvResponse = $combinedResults['data'] ?? $combinedResults;
$accountData = $yvResponse['data'] ?? $yvResponse;

// Extract bank details
$bankDetails = $accountData['bankDetails'] ?? [];

$account = [
    'status'            => $accountData['status'] ?? 'unknown',
    'reason'            => $accountData['reason'] ?? null,
    
    // Bank Account Info
    'account_number'    => $bankDetails['accountNumber'] ?? $account_number,
    'account_name'      => $bankDetails['accountName'] ?? 'N/A',
    'bank_name'         => $bankDetails['bankName'] ?? 'N/A',
    'bank_code'         => $accountData['bankCode'] ?? 'N/A',
    'id_number'         => $accountData['idNumber'] ?? null,
    
    // Verification Meta
    'verification_id'   => $accountData['id'] ?? '',
    'requested_at'      => $accountData['requestedAt'] ?? '',
    'type'              => $accountData['type'] ?? 'keBAV',
    'country'           => $accountData['country'] ?? 'KE',
    'consent'           => $accountData['isConsent'] ?? false,
    'all_passed'        => $accountData['allValidationPassed'] ?? false,
    'data_validation'   => $accountData['dataValidation'] ?? false,
    'selfie_validation' => $accountData['selfieValidation'] ?? false,
];

$trx_id = 'BAV-' . date('YmdHis') . '-' . strtoupper(substr($account['account_number'], -6));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Kenya Bank Account Verification Report | Readiwork</title>
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
.account-box{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:3px solid rgba(34,197,94,0.3);border-radius:24px;padding:50px;margin-bottom:60px;position:relative;overflow:hidden;}
.account-box::before{content:'✓';position:absolute;top:-40px;right:-40px;font-size:200px;color:rgba(34,197,94,0.05);font-weight:900;}
.account-box h3{font-size:2.5rem;font-weight:900;color:#0f172a;margin-bottom:24px;position:relative;z-index:2;}
.account-box .account-number{font-size:2.2rem;font-weight:800;color:#22c55e;font-family:'Courier New',monospace;letter-spacing:4px;margin-bottom:30px;padding:20px;background:white;border-radius:12px;display:inline-block;}
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
      <h2>Kenya Bank Account Verification</h2>
      <p style="color:#64748b;font-size:1.1rem;">Generated on <?= date('d F Y, h:i A') ?></p>
      <span class="provider-badge"><i class="fa-solid fa-fingerprint me-2"></i>YouVerify</span>
      <span class="provider-badge"><i class="fa-solid fa-building-columns me-2"></i>Bank Verified</span>
    </div>

    <!-- VERIFICATION STATUS -->
    <div class="status-box <?= $account['status'] !== 'found' ? 'not-found' : '' ?>">
      <i class="fa-solid fa-<?= $account['status'] === 'found' ? 'building-columns' : 'circle-xmark' ?>"></i>
      <h3><?= $account['status'] === 'found' ? 'ACCOUNT VERIFIED' : 'ACCOUNT NOT FOUND' ?></h3>
      <p><?= $account['status'] === 'found' ? 'Bank account verified successfully' : 'Account not found in bank records' ?></p>
      
      <div class="verification-badges">
        <div class="verification-badge">
          <div class="badge-label">Status</div>
          <div class="badge-value" style="<?= $account['status'] !== 'found' ? 'color:#ef4444;' : '' ?>">
            <?= $account['status'] === 'found' ? '✓ FOUND' : '✗ NOT FOUND' ?>
          </div>
        </div>
        
        <?php if ($account['bank_name'] !== 'N/A'): ?>
        <div class="verification-badge">
          <div class="badge-label">Bank</div>
          <div class="badge-value" style="font-size:1rem;"><?= strtoupper(htmlspecialchars($account['bank_name'])) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="verification-badge">
          <div class="badge-label">Verified On</div>
          <div class="badge-value" style="font-size:0.95rem;"><?= date('M d, Y', strtotime($account['requested_at'])) ?></div>
        </div>
      </div>
    </div>

    <!-- ACCOUNT INFORMATION -->
    <?php if ($account['status'] === 'found' && $account['account_name'] !== 'N/A'): ?>
    <div class="account-box">
      <h3><?= htmlspecialchars($account['account_name']) ?></h3>
      <div class="account-number">
        <i class="fa-solid fa-hashtag me-3"></i><?= htmlspecialchars($account['account_number']) ?>
      </div>
      <p style="font-size:1.2rem;color:#64748b;margin:0;position:relative;z-index:2;">
        <i class="fa-solid fa-building-columns me-2"></i><?= htmlspecialchars($account['bank_name']) ?>
      </p>
    </div>
    
    <!-- ACCOUNT DETAILS -->
    <div class="info-grid">
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-hashtag"></i> Account Number</div>
        <div class="value highlight"><?= htmlspecialchars($account['account_number']) ?></div>
      </div>
      
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-user"></i> Account Name</div>
        <div class="value"><?= htmlspecialchars($account['account_name']) ?></div>
      </div>
      
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-building-columns"></i> Bank Name</div>
        <div class="value"><?= htmlspecialchars($account['bank_name']) ?></div>
      </div>
      
      <?php if ($account['bank_code'] !== 'N/A'): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-barcode"></i> Bank Code</div>
        <div class="value"><?= htmlspecialchars($account['bank_code']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($account['id_number']): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-id-card"></i> ID Number</div>
        <div class="value"><?= htmlspecialchars($account['id_number']) ?></div>
      </div>
      <?php endif; ?>
      
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-globe"></i> Country</div>
        <div class="value"><?= htmlspecialchars($account['country']) ?></div>
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
          <div class="label"><i class="fa-solid fa-check-circle"></i> Status</div>
          <div class="value" style="color:<?= $account['status'] === 'found' ? '#22c55e' : '#ef4444' ?>;">
            <?= strtoupper($account['status']) ?>
          </div>
        </div>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-fingerprint"></i> Type</div>
          <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($account['type']) ?></div>
        </div>
        
        <?php if ($account['verification_id']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-barcode"></i> Reference ID</div>
          <div class="value" style="font-size:1rem;font-family:monospace;"><?= htmlspecialchars($account['verification_id']) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-clock"></i> Requested At</div>
          <div class="value" style="font-size:1rem;"><?= date('M d, Y H:i', strtotime($account['requested_at'])) ?></div>
        </div>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-user-check"></i> Consent Given</div>
          <div class="value" style="color:<?= $account['consent'] ? '#22c55e' : '#64748b' ?>;">
            <?= $account['consent'] ? 'YES' : 'NO' ?>
          </div>
        </div>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-check-double"></i> All Validations</div>
          <div class="value" style="color:<?= $account['all_passed'] ? '#22c55e' : '#64748b' ?>;">
            <?= $account['all_passed'] ? '✓ PASSED' : 'N/A' ?>
          </div>
        </div>
      </div>
      
      <div style="margin-top:40px;text-align:center;background:white;border-radius:16px;padding:30px;">
        <i class="fa-solid fa-certificate fa-3x" style="color:#22c55e;margin-bottom:16px;"></i>
        <h6 style="font-weight:900;font-size:1.4rem;color:#0f172a;margin-bottom:12px;">
          BANK ACCOUNT VERIFICATION COMPLETE
        </h6>
        <p style="color:#64748b;font-size:1.05rem;margin:0;">
          This bank account has been verified with <?= htmlspecialchars($account['bank_name']) ?> via YouVerify.<br>
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
      <a href="account-verification.php" class="btn-action secondary">
        <i class="fa-solid fa-rotate"></i> Verify Another Account
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