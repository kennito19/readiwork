<?php
/**
 * Credit History Verification Results
 * YouVerify - Kenya Credit History Display
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

    $id_number = $request['national_id'];

} catch (Exception $e) {
    error_log('Credit Results Error: ' . $e->getMessage());
    die('Error loading results.');
}

/* ============================
   Extract Credit History API Data
   ============================ */
$yvResponse = $combinedResults['data'] ?? $combinedResults;
$creditData = $yvResponse['data'] ?? $yvResponse;

// Extract contract summary and inquiries
$contractSummary = $creditData['contractSummary'] ?? [];
$inquiries = $creditData['inquiries'] ?? [];

$credit = [
    'status'            => $creditData['status'] ?? 'unknown',
    'reason'            => $creditData['reason'] ?? null,
    
    // Personal Info
    'first_name'        => $creditData['firstName'] ?? 'N/A',
    'middle_name'       => $creditData['middleName'] ?? '',
    'last_name'         => $creditData['lastName'] ?? 'N/A',
    'full_name'         => $creditData['fullName'] ?? 'N/A',
    'dob'               => $creditData['dateOfBirth'] ?? 'N/A',
    'gender'            => $creditData['gender'] ?? 'N/A',
    'marital_status'    => $creditData['maritalStatus'] ?? 'N/A',
    
    // ID Info
    'id_number'         => $creditData['idNumber'] ?? $id_number,
    'identity_type'     => $creditData['identityType'] ?? 'national-id',
    
    // Contract Summary
    'open_contracts'    => $contractSummary['openContracts'] ?? '0',
    'closed_contracts'  => $contractSummary['closedContracts'] ?? '0',
    
    // Inquiries
    'inquiries_1m'      => $inquiries['numberOfInquiriesLast1Month'] ?? '0',
    'inquiries_3m'      => $inquiries['numberOfInquiriesLast3Months'] ?? '0',
    'inquiries_6m'      => $inquiries['numberOfInquiriesLast6Months'] ?? '0',
    'inquiries_12m'     => $inquiries['numberOfInquiriesLast12Months'] ?? '0',
    'inquiries_24m'     => $inquiries['numberOfInquiriesLast24Months'] ?? '0',
    
    // Verification Meta
    'verification_id'   => $creditData['id'] ?? '',
    'requested_at'      => $creditData['requestedAt'] ?? '',
    'type'              => $creditData['type'] ?? 'keCreditHistory',
    'country'           => $creditData['country'] ?? 'KE',
    'consent'           => $creditData['isConsent'] ?? false,
    'all_passed'        => $creditData['allValidationPassed'] ?? false,
];

$trx_id = 'CH-' . date('YmdHis') . '-' . strtoupper(substr($credit['id_number'], -6));

// Calculate total contracts
$total_contracts = intval($credit['open_contracts']) + intval($credit['closed_contracts']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Kenya Credit History Report | Readiwork</title>
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
.credit-box{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:3px solid rgba(34,197,94,0.3);border-radius:24px;padding:50px;margin-bottom:60px;position:relative;overflow:hidden;}
.credit-box::before{content:'✓';position:absolute;top:-40px;right:-40px;font-size:200px;color:rgba(34,197,94,0.05);font-weight:900;}
.credit-box h3{font-size:2.5rem;font-weight:900;color:#0f172a;margin-bottom:24px;position:relative;z-index:2;}
.stat-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin:40px 0;}
.stat-card{background:white;border:2px solid #22c55e;border-radius:16px;padding:30px;text-align:center;}
.stat-card .stat-label{font-size:0.9rem;font-weight:600;color:#64748b;text-transform:uppercase;margin-bottom:10px;}
.stat-card .stat-value{font-size:3rem;font-weight:900;color:#22c55e;}
.inquiry-timeline{background:white;border-radius:20px;padding:40px;margin:40px 0;}
.inquiry-item{display:flex;align-items:center;gap:20px;padding:20px;border-bottom:1px solid #e2e8f0;}
.inquiry-item:last-child{border-bottom:none;}
.inquiry-period{min-width:120px;font-weight:700;color:#64748b;}
.inquiry-bar{flex:1;background:#f1f5f9;border-radius:999px;height:40px;position:relative;overflow:hidden;}
.inquiry-fill{background:linear-gradient(135deg,#22c55e,#16a34a);height:100%;border-radius:999px;display:flex;align-items:center;justify-content:flex-end;padding:0 15px;transition:width 0.8s ease;}
.inquiry-count{color:white;font-weight:900;font-size:1.1rem;}
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
      <h2>Kenya Credit History Report</h2>
      <p style="color:#64748b;font-size:1.1rem;">Generated on <?= date('d F Y, h:i A') ?></p>
      <span class="provider-badge"><i class="fa-solid fa-fingerprint me-2"></i>YouVerify</span>
      <span class="provider-badge"><i class="fa-solid fa-chart-line me-2"></i>Credit Bureau</span>
    </div>

    <!-- VERIFICATION STATUS -->
    <div class="status-box <?= $credit['status'] !== 'found' ? 'not-found' : '' ?>">
      <i class="fa-solid fa-<?= $credit['status'] === 'found' ? 'file-invoice' : 'circle-xmark' ?>"></i>
      <h3><?= $credit['status'] === 'found' ? 'CREDIT HISTORY FOUND' : 'NO CREDIT HISTORY' ?></h3>
      <p><?= $credit['status'] === 'found' ? 'Credit history verified successfully' : ($credit['reason'] ?: 'No credit history found for this ID') ?></p>
    </div>

    <!-- PERSONAL INFORMATION -->
    <?php if ($credit['status'] === 'found' && $credit['full_name'] !== 'N/A'): ?>
    <div class="credit-box">
      <h3><?= htmlspecialchars($credit['full_name']) ?></h3>
      <p style="font-size:1.2rem;color:#64748b;margin:20px 0 0;position:relative;z-index:2;">
        <i class="fa-solid fa-id-card me-2"></i><?= htmlspecialchars($credit['id_number']) ?> 
        <span style="margin-left:20px;"><i class="fa-solid fa-id-badge me-2"></i><?= ucwords(str_replace('-', ' ', $credit['identity_type'])) ?></span>
      </p>
    </div>
    
    <!-- CONTRACT SUMMARY -->
    <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:2px solid #22c55e;border-radius:20px;padding:40px;margin-bottom:40px;">
      <h5 style="font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:30px;text-align:center;">
        <i class="fa-solid fa-file-contract me-2" style="color:#22c55e;"></i> Contract Summary
      </h5>
      
      <div class="stat-cards">
        <div class="stat-card">
          <div class="stat-label">Open Contracts</div>
          <div class="stat-value"><?= htmlspecialchars($credit['open_contracts']) ?></div>
        </div>
        
        <div class="stat-card">
          <div class="stat-label">Closed Contracts</div>
          <div class="stat-value" style="color:#3b82f6;"><?= htmlspecialchars($credit['closed_contracts']) ?></div>
        </div>
        
        <div class="stat-card">
          <div class="stat-label">Total Contracts</div>
          <div class="stat-value" style="color:#6366f1;"><?= $total_contracts ?></div>
        </div>
      </div>
    </div>
    
    <!-- CREDIT INQUIRIES -->
    <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:2px solid #3b82f6;border-radius:20px;padding:40px;margin-bottom:40px;">
      <h5 style="font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:30px;text-align:center;">
        <i class="fa-solid fa-magnifying-glass-chart me-2" style="color:#3b82f6;"></i> Credit Inquiry Timeline
      </h5>
      
      <div class="inquiry-timeline">
        <?php
        $inquiries_data = [
            '1 Month' => intval($credit['inquiries_1m']),
            '3 Months' => intval($credit['inquiries_3m']),
            '6 Months' => intval($credit['inquiries_6m']),
            '12 Months' => intval($credit['inquiries_12m']),
            '24 Months' => intval($credit['inquiries_24m']),
        ];
        $max_inquiries = max($inquiries_data) ?: 1;
        
        foreach ($inquiries_data as $period => $count):
            $width_percent = ($count / $max_inquiries) * 100;
        ?>
        <div class="inquiry-item">
          <div class="inquiry-period">Last <?= $period ?></div>
          <div class="inquiry-bar">
            <div class="inquiry-fill" style="width:<?= $width_percent ?>%">
              <span class="inquiry-count"><?= $count ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <!-- PERSONAL DETAILS -->
    <div class="info-grid">
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-user"></i> Full Name</div>
        <div class="value"><?= htmlspecialchars($credit['full_name']) ?></div>
      </div>
      
      <?php if ($credit['dob'] !== 'N/A'): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-calendar-days"></i> Date of Birth</div>
        <div class="value" style="font-size:1.2rem;"><?= date('M d, Y', strtotime($credit['dob'])) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($credit['gender'] !== 'N/A'): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-venus-mars"></i> Gender</div>
        <div class="value"><?= ucfirst($credit['gender']) ?></div>
      </div>
      <?php endif; ?>
      
      <?php if ($credit['marital_status'] !== 'N/A'): ?>
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-heart"></i> Marital Status</div>
        <div class="value"><?= ucfirst($credit['marital_status']) ?></div>
      </div>
      <?php endif; ?>
      
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-id-badge"></i> ID Type</div>
        <div class="value" style="font-size:1.2rem;"><?= ucwords(str_replace('-', ' ', $credit['identity_type'])) ?></div>
      </div>
      
      <div class="info-item">
        <div class="label"><i class="fa-solid fa-globe"></i> Country</div>
        <div class="value"><?= htmlspecialchars($credit['country']) ?></div>
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
          <div class="value" style="color:<?= $credit['status'] === 'found' ? '#22c55e' : '#ef4444' ?>;">
            <?= strtoupper($credit['status']) ?>
          </div>
        </div>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-fingerprint"></i> Type</div>
          <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($credit['type']) ?></div>
        </div>
        
        <?php if ($credit['verification_id']): ?>
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-barcode"></i> Reference ID</div>
          <div class="value" style="font-size:1rem;font-family:monospace;"><?= htmlspecialchars($credit['verification_id']) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="info-item" style="background:white;">
          <div class="label"><i class="fa-solid fa-clock"></i> Requested At</div>
          <div class="value" style="font-size:1rem;"><?= date('M d, Y H:i', strtotime($credit['requested_at'])) ?></div>
        </div>
      </div>
      
      <div style="margin-top:40px;text-align:center;background:white;border-radius:16px;padding:30px;">
        <i class="fa-solid fa-certificate fa-3x" style="color:#22c55e;margin-bottom:16px;"></i>
        <h6 style="font-weight:900;font-size:1.4rem;color:#0f172a;margin-bottom:12px;">
          CREDIT HISTORY VERIFICATION COMPLETE
        </h6>
        <p style="color:#64748b;font-size:1.05rem;margin:0;">
          This credit history has been verified via YouVerify's Kenya Credit Bureau integration.<br>
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
      <a href="credit-verification.php" class="btn-action secondary">
        <i class="fa-solid fa-rotate"></i> Check Another Credit History
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