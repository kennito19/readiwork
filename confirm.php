<?php
require_once __DIR__ . '/config.php';

$rid = $_GET['rid'] ?? null;

if (!$rid || !ctype_digit((string)$rid)) {
    die('Invalid or missing request ID. <a href="index.php">Start a new verification</a>');
}

// Fetch request from DB
try {
    $stmt = $pdo->prepare("
        SELECT 
            national_id, 
            service, 
            provider,
            price, 
            status, 
            result, 
            mpesa_receipt_number, 
            full_name,
            first_name,
            last_name,
            yv_reference_id,
            business_name,
            phone,
            dob
        FROM verification_requests
        WHERE id = :rid
        LIMIT 1
    ");
    
    $stmt->execute([':rid' => $rid]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request || empty(trim($request['national_id']))) {
        die('Request not found or missing identifier. <a href="index.php">Start a new check</a>');
    }

    $national_id = htmlspecialchars($request['national_id']);
    $serviceKey = $request['service'];
    $provider = $request['provider'] ?? 'metropol';
    $price = number_format($request['price'], 2);
    $status = $request['status'];
    $full_name = htmlspecialchars($request['full_name'] ?? '');
    $first_name = htmlspecialchars($request['first_name'] ?? '');
    $last_name = htmlspecialchars($request['last_name'] ?? '');
    $business_name = htmlspecialchars($request['business_name'] ?? '');
    $phone = htmlspecialchars($request['phone'] ?? '');
    $dob = htmlspecialchars($request['dob'] ?? '');
    $yv_reference = htmlspecialchars($request['yv_reference_id'] ?? '');

    // Only redirect to results if BOTH payment confirmed AND results ready
    if ($status === 'completed' && !empty($request['result']) && !empty($request['mpesa_receipt_number'])) {
        header('Location: results.php?rid=' . $rid);
        exit;
    }

    // If already paid but results not ready, redirect to payment page (it will show status)
    if ($status === 'paid' || ($status === 'completed' && empty($request['mpesa_receipt_number']))) {
        header('Location: payment.php?rid=' . $rid);
        exit;
    }
    
    // For YouVerify processing status, show special message
    $isYouVerifyProcessing = ($provider === 'youverify' && $status === 'processing');

    // Get service configuration from config.php
    $serviceConfig = get_service_config($serviceKey);
    
    if (!$serviceConfig) {
        die('Invalid service type. <a href="index.php">Start a new check</a>');
    }

    // === LOAN ELIGIBILITY TEASER CALCULATION ===
    $loanTeaser = null;
    if ($serviceKey === 'loan-eligibility' && !empty($request['result'])) {
        $apiResults = json_decode($request['result'], true);
        
        // Extract credit score from API results
        $creditScore = 0;
        $delinquencyCode = '003'; // default: no delinquency
        
        foreach ($apiResults as $endpoint => $data) {
            if (!is_array($data)) continue;
            
            // Get credit score
            if (isset($data['credit_score']) || isset($data['metro_score'])) {
                $creditScore = intval($data['credit_score'] ?? $data['metro_score'] ?? 0);
            }
            
            // Get delinquency status
            if (isset($data['delinquency_code'])) {
                $delinquencyCode = $data['delinquency_code'];
            }
        }
        
        // Calculate teaser loan amount
        if ($creditScore > 0) {
            $baseAmount = 0;
            
            if ($creditScore >= 750) $baseAmount = 500000;
            elseif ($creditScore >= 700) $baseAmount = 300000;
            elseif ($creditScore >= 650) $baseAmount = 200000;
            elseif ($creditScore >= 600) $baseAmount = 150000;
            elseif ($creditScore >= 550) $baseAmount = 100000;
            elseif ($creditScore >= 500) $baseAmount = 50000;
            elseif ($creditScore >= 450) $baseAmount = 30000;
            else $baseAmount = 10000;
            
            // Reduce if has delinquency
            if ($delinquencyCode === '004') $baseAmount *= 0.2; // Current NPA
            if ($delinquencyCode === '005') $baseAmount *= 0.6; // Historical NPA
            
            $loanTeaser = [
                'amount' => round($baseAmount, -3),
                'score' => $creditScore,
                'status' => $delinquencyCode === '003' ? 'excellent' : ($delinquencyCode === '005' ? 'good' : 'review')
            ];
        } else {
            // Fallback if no score available
            $loanTeaser = [
                'amount' => 50000,
                'score' => 0,
                'status' => 'pending'
            ];
        }
    }

} catch (Exception $e) {
    error_log('Confirm page DB error: ' . $e->getMessage());
    die('Unable to load details. Please try again later.');
}

/* ======================================================
 * DYNAMIC SERVICE DISPLAY GENERATOR
 * Generates appropriate display information based on service category
 * ====================================================== */

function get_service_display_info($serviceConfig, $serviceKey, $provider) {
    $category = $serviceConfig['category'];
    $serviceName = $serviceConfig['name'];
    
    // Provider-specific branding
    $providerBadge = $provider === 'youverify' 
        ? '<span class="provider-badge provider-youverify">YouVerify</span>' 
        : '<span class="provider-badge provider-metropol">Metropol</span>';
    
    // Category-based configurations
    $categoryDisplays = [
        'identity' => [
            'stepTitle' => 'Confirm ID Verification',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Identity Validation',
            'infoText' => 'This verification is <strong>100% confidential</strong> and secure.<br>Results are displayed <strong>immediately</strong> after successful payment.',
            'identifierLabel' => 'National ID' // Default label
        ],
        'crb' => [
            'stepTitle' => 'Confirm CRB Check',
            'stepDesc' => 'Review details before secure payment',
            'reportType' => 'Credit Bureau Status',
            'infoText' => 'This check is <strong>100% confidential</strong> and does not affect your credit score.<br>Results are displayed <strong>immediately</strong> after successful payment.',
            'identifierLabel' => 'National ID'
        ],
        'score' => [
            'stepTitle' => 'Confirm Credit Score',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Credit Score & Rating',
            'infoText' => 'Your personal credit score report with AI insights.<br>No impact on your score. Instant delivery after payment.',
            'identifierLabel' => 'National ID'
        ],
        'pdf_report' => [
            'stepTitle' => 'Confirm PDF Report',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Official PDF Document',
            'infoText' => 'Official credit report in PDF format.<br>Suitable for legal, audit, and personal records. Instant download after payment.',
            'identifierLabel' => 'National ID'
        ],
        'json_report' => [
            'stepTitle' => 'Confirm Credit Report',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Structured Data Report',
            'infoText' => 'Complete credit report in machine-readable format.<br>Instant delivery after payment.',
            'identifierLabel' => 'National ID'
        ],
        'scrub' => [
            'stepTitle' => 'Confirm Background Check',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Identity & Background Screening',
            'infoText' => 'Comprehensive background screening – completely private.<br>Results available instantly after payment.',
            'identifierLabel' => 'National ID'
        ],
        'credit_info' => [
            'stepTitle' => 'Confirm Credit Analysis',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Detailed Credit Information',
            'infoText' => 'Comprehensive credit analysis with account details.<br>Confidential and instant delivery after payment.',
            'identifierLabel' => 'National ID'
        ],
        'enhanced' => [
            'stepTitle' => 'Confirm Enhanced Check',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Enhanced Credit Assessment',
            'infoText' => 'Enhanced verification with additional stakeholder information.<br>Secure and confidential. Instant report after payment.',
            'identifierLabel' => 'National ID'
        ],
        'full_enhanced' => [
            'stepTitle' => 'Confirm Comprehensive Report',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Full Enhanced Assessment',
            'infoText' => 'Most comprehensive report with complete credit history and risk analysis.<br>Professional-grade report delivered instantly after payment.',
            'identifierLabel' => 'National ID'
        ],
        'full_json' => [
            'stepTitle' => 'Confirm Full Report',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Complete Data Package',
            'infoText' => 'Full report with identity, credit, and stakeholder information.<br>Instant delivery after payment.',
            'identifierLabel' => 'National ID'
        ],
        'financial' => [
            'stepTitle' => 'Confirm Financial Check',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Financial Account Verification',
            'infoText' => 'Secure financial account verification.<br>Instant delivery after payment.',
            'identifierLabel' => 'Account Number'
        ],
        'tax' => [
            'stepTitle' => 'Confirm Tax Verification',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Tax Identification Check',
            'infoText' => 'Tax identification verification with KRA.<br>Instant delivery after payment.',
            'identifierLabel' => 'KRA PIN'
        ],
        'address' => [
            'stepTitle' => 'Confirm Address Verification',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Physical Address Verification',
            'infoText' => 'Address verification with supporting documentation.<br>Results delivered after payment.',
            'identifierLabel' => 'Address'
        ],
        'contact' => [
            'stepTitle' => 'Confirm Phone Verification',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Phone Number Verification',
            'infoText' => 'Phone ownership and validity verification.<br>Instant delivery after payment.',
            'identifierLabel' => 'Phone Number'
        ],
        'employment' => [
            'stepTitle' => 'Confirm Employment Check',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Employment Status Verification',
            'infoText' => 'Employment history and status verification.<br>Results delivered after payment.',
            'identifierLabel' => 'Employee ID'
        ],
        'vehicle' => [
            'stepTitle' => 'Confirm Vehicle Verification',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Vehicle Registration Check',
            'infoText' => 'Vehicle registration and ownership verification with NTSA.<br>Instant delivery after payment.',
            'identifierLabel' => 'Registration Number'
        ],
        'business' => [
            'stepTitle' => 'Confirm Business Verification',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Business Registration Check',
            'infoText' => 'Business registration and details verification.<br>Instant delivery after payment.',
            'identifierLabel' => 'Registration Number'
        ],
        'biometric' => [
            'stepTitle' => 'Confirm Biometric Check',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Biometric Verification',
            'infoText' => 'Advanced biometric liveness and face verification.<br>Secure and instant results after payment.',
            'identifierLabel' => 'Subject ID'
        ],
        'document' => [
            'stepTitle' => 'Confirm Document Verification',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Document Authenticity Check',
            'infoText' => 'AI-powered document verification and authenticity check.<br>Results delivered after payment.',
            'identifierLabel' => 'Document ID'
        ],
        'compliance' => [
            'stepTitle' => 'Confirm Compliance Screening',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Compliance & Risk Screening',
            'infoText' => 'Comprehensive compliance screening against global databases.<br>Professional-grade report delivered after payment.',
            'identifierLabel' => 'Subject ID'
        ]
    ];
    
    // Get base display from category
    $display = $categoryDisplays[$category] ?? $categoryDisplays['identity'];
    
    // Override with service name
    $display['serviceName'] = $serviceName . ' ' . $providerBadge;
    
    // YouVerify-specific adjustments
    if ($provider === 'youverify') {
        $display['infoText'] = 'This verification is processed through <strong>YouVerify</strong> platform.<br>Results will be available after payment confirmation and processing completion (typically within minutes).';
    }
    
    // Special cases for specific services (including passport)
    $specialCases = [
        'yv-ke-passport' => [
            'stepTitle' => 'Confirm Passport Verification',
            'stepDesc' => 'Review passport details before payment',
            'reportType' => 'Passport Authentication',
            'infoText' => 'Kenyan passport verification with official immigration records.<br>Results available after payment.',
            'identifierLabel' => 'Passport Number'
        ],
        'yv-ke-national-id' => [
            'stepTitle' => 'Confirm National ID Verification',
            'stepDesc' => 'Review ID details before payment',
            'reportType' => 'National ID Validation',
            'infoText' => 'Kenyan National ID verification with official government records.<br>Results available after payment.',
            'identifierLabel' => 'National ID'
        ],
        
        
        'yv-ke-collateral' => [
    'stepTitle' => 'Confirm Vehicle Collateral Verification',
    'stepDesc' => 'Review collateral details before payment',
    'reportType' => 'Vehicle Collateral & Security Interest Check',
    'infoText' => 'Kenya vehicle collateral verification with Movable Property Security Rights Registry.<br>Checks for registered security interests and creditor information. Results available after payment.',
    'identifierLabel' => 'Collateral ID'
],


'yv-ke-drivers-license' => [
    'stepTitle' => 'Confirm Driver\'s License Verification',
    'stepDesc' => 'Review license details before payment',
    'reportType' => 'Driver\'s License & NTSA Validation',
    'infoText' => 'Kenyan driver\'s license verification with official NTSA records.<br>Accepts DL/IDL format, 9-digit internal ID, or National ID. Results available after payment.',
    'identifierLabel' => 'License / National ID'
],
    
    'yv-ke-bank-account' => [
    'stepTitle' => 'Confirm Bank Account Verification',
    'stepDesc' => 'Review account details before payment',
    'reportType' => 'Bank Account Validation',
    'infoText' => 'Kenyan bank account verification with account holder name confirmation.<br>Results available after payment.',
    'identifierLabel' => 'Account Number'
], 
'yv-ke-employment' => [
    'stepTitle' => 'Confirm Employment History Verification',
    'stepDesc' => 'Review employment details before proceeding',
    'reportType' => 'Employment History Verification',
    'infoText' => 'Kenyan employment history verification to confirm employer details and employment status.<br>Results available after payment.',
    'identifierLabel' => 'Employment ID or Employer Reference'
],
 
 'yv-ke-employment' => [
    'stepTitle' => 'Confirm Employment History Verification',
    'stepDesc' => 'Review employment details before proceeding',
    'reportType' => 'Employment History Verification',
    'infoText' => 'Kenyan employment history verification to confirm employer details and employment status.<br>Results available after payment.',
    'identifierLabel' => 'Employment ID or Employer Reference'
],
'yv-ke-employment' => [
    'stepTitle' => 'Confirm Employment History Verification',
    'stepDesc' => 'Review employment details before proceeding',
    'reportType' => 'Employment History Verification',
    'infoText' => 'Kenyan employment history verification to confirm employer details and employment status.<br>Results available after payment.',
    'identifierLabel' => 'Employment ID or Employer Reference'
],
'yv-ke-employment' => [
    'stepTitle' => 'Confirm Employment History Verification',
    'stepDesc' => 'Review employment details before proceeding',
    'reportType' => 'Employment History Verification',
    'infoText' => 'Kenyan employment history verification to confirm employer details and employment status.<br>Results available after payment.',
    'identifierLabel' => 'Employment ID or Employer Reference'
],
        'tenant' => [
            'stepTitle' => 'Confirm Tenant Screening',
            'stepDesc' => 'Review tenant details before payment',
            'reportType' => 'Rental Risk Assessment',
            'infoText' => 'This screening is fully private and helps landlords make informed decisions.<br>Report delivered instantly after payment.',
            'identifierLabel' => 'National ID'
        ],
        'job' => [
            'stepTitle' => 'Confirm Employment Check',
            'stepDesc' => 'Review applicant details before payment',
            'reportType' => 'Employment Verification',
            'infoText' => 'Professional verification for HR and recruiters.<br>Secure and confidential. Instant report after payment.',
            'identifierLabel' => 'National ID'
        ],
        'loan-eligibility' => [
            'stepTitle' => 'Confirm Loan Eligibility',
            'stepDesc' => 'Review your pre-qualification details',
            'reportType' => 'Loan Eligibility Assessment',
            'infoText' => 'AI-powered loan eligibility and risk assessment.<br>Confidential and instant delivery after payment.',
            'identifierLabel' => 'National ID'
        ],
        'guarantor-verify' => [
            'stepTitle' => 'Confirm Guarantor Check',
            'stepDesc' => 'Review guarantor details before payment',
            'reportType' => 'Guarantor Verification',
            'infoText' => 'Comprehensive guarantor creditworthiness assessment.<br>Instant delivery after payment.',
            'identifierLabel' => 'National ID'
        ],
        'fraud-prescreening' => [
            'stepTitle' => 'Confirm Fraud Screening',
            'stepDesc' => 'Review details before payment',
            'reportType' => 'Fraud Risk Assessment',
            'infoText' => 'Advanced fraud detection and identity validation.<br>Secure and instant report delivery.',
            'identifierLabel' => 'National ID'
        ]
    ];
    
    // Override with special case if exists
    if (isset($specialCases[$serviceKey])) {
        $display = array_merge($display, $specialCases[$serviceKey]);
        $display['serviceName'] = $serviceName . ' ' . $providerBadge;
    }
    
    return $display;
}

// Get display information
$display = get_service_display_info($serviceConfig, $serviceKey, $provider);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Confirm Details | Readiwork</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--green:#22c55e;--green-dark:#16a34a;--bg:#050b1a;--card:#111a2f;--text:#f1f5f9;--muted:#94a3b8;--border:rgba(255,255,255,.1);--accent:rgba(34,197,94,.15);}
body{background:radial-gradient(70% 60% at top,#0b1220 0%,#050b1a 60%);color:var(--text);font-family:'Inter', system-ui, sans-serif;min-height:100vh;}
.navbar{background:rgba(5,11,26,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:1rem 0;}
.navbar-brand{font-weight:900;color:#fff;font-size:1.4rem;letter-spacing:-0.5px;}
.page{padding:160px 0 120px;}
.card-box{background:var(--card);border:1px solid var(--border);border-radius:28px;padding:48px;box-shadow:0 40px 100px rgba(0,0,0,.7);backdrop-filter:blur(10px);}
.step{display:flex;gap:16px;margin-bottom:32px;align-items:center;}
.step span{width:42px;height:42px;background:var(--green);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;flex-shrink:0;}
.step h4{margin:0;font-weight:800;font-size:1.5rem;}
.step p{margin:4px 0 0;color:var(--muted);font-size:0.95rem;}
.summary{background:rgba(15,23,42,.6);border:1px solid var(--border);border-radius:20px;padding:28px;margin-bottom:32px;}
.summary-row{display:flex;justify-content:space-between;margin-bottom:16px;font-size:1.05rem;align-items:center;}
.summary-row:last-child{margin-bottom:0;}
.summary-row span{color:var(--muted);}
.summary-row strong{color:var(--text);font-weight:600;}
.summary hr{border-color:var(--border);margin:20px 0;}
.total strong{font-size:1.3rem;font-weight:800;color:var(--green);}
.info-note{background:var(--accent);border:1px solid rgba(34,197,94,.3);border-radius:16px;padding:18px;margin-bottom:32px;font-size:0.95rem;color:var(--text);display:flex;align-items:flex-start;gap:12px;}
.info-note i{color:var(--green);margin-top:2px;flex-shrink:0;}
.btn-main{background:linear-gradient(135deg,var(--green),var(--green-dark));border:none;border-radius:999px;padding:18px 32px;font-weight:800;font-size:1.1rem;color:#fff;width:100%;transition:all .3s ease;box-shadow:0 8px 25px rgba(34,197,94,.3);}
.btn-main:hover{background:var(--green-dark);transform:translateY(-2px);box-shadow:0 12px 30px rgba(34,197,94,.4);}
.btn-main:active{transform:translateY(0);}
.trust{margin-top:24px;font-size:0.9rem;display:flex;justify-content:center;gap:32px;flex-wrap:wrap;color:var(--muted);}
.trust span{display:flex;align-items:center;gap:8px;}
.trust i{color:var(--green);font-size:1.1rem;}
footer{background:rgba(15,23,42,.6);padding:60px 0;text-align:center;border-top:1px solid var(--border);}
footer h5{font-weight:900;font-size:1.8rem;margin-bottom:8px;}
footer p, footer small{color:var(--muted);}

.provider-badge{display:inline-block;padding:4px 12px;border-radius:8px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-left:8px;vertical-align:middle;}
.provider-metropol{background:rgba(99,102,241,0.2);color:#818cf8;border:1px solid rgba(99,102,241,0.3);}
.provider-youverify{background:rgba(34,197,94,0.2);color:#22c55e;border:1px solid rgba(34,197,94,0.3);}

.yv-processing-note{background:rgba(234,179,8,0.15);border:1px solid rgba(234,179,8,0.3);border-radius:16px;padding:18px;margin-bottom:24px;font-size:0.95rem;color:#fef08a;display:flex;align-items:flex-start;gap:12px;}
.yv-processing-note i{color:#facc15;margin-top:2px;flex-shrink:0;}

/* Loan Eligibility Teaser Styles */
.loan-teaser{background:linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);border:3px solid var(--green);border-radius:24px;padding:48px 40px;margin:40px 0;text-align:center;position:relative;overflow:hidden;}
.loan-teaser::before{content:'';position:absolute;top:-50%;right:-50%;width:200%;height:200%;background:radial-gradient(circle, rgba(34,197,94,0.1) 0%, transparent 70%);animation:pulse 3s ease-in-out infinite;}
@keyframes pulse{0%,100%{transform:scale(1);}50%{transform:scale(1.1);}}
.loan-teaser-icon{font-size:4rem;color:var(--green);margin-bottom:24px;animation:bounce 2s ease-in-out infinite;}
@keyframes bounce{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}
.loan-teaser h3{font-weight:900;font-size:2.2rem;color:#0f172a;margin-bottom:16px;position:relative;z-index:2;}
.loan-teaser-amount{font-size:3.5rem;font-weight:900;color:var(--green);margin:24px 0;position:relative;z-index:2;text-shadow:0 2px 10px rgba(34,197,94,0.2);}
.loan-teaser-label{font-size:1.1rem;color:#64748b;margin-bottom:32px;position:relative;z-index:2;}
.loan-teaser-badge{display:inline-block;background:white;border:2px solid var(--green);border-radius:999px;padding:12px 32px;font-weight:800;color:#0f172a;margin:20px 0;position:relative;z-index:2;font-size:1.1rem;}
.loan-teaser-cta{font-size:1.15rem;color:#059669;font-weight:700;margin-top:24px;position:relative;z-index:2;}
.loan-teaser-cta i{margin-right:8px;animation:arrow 1s ease-in-out infinite;}
@keyframes arrow{0%,100%{transform:translateX(0);}50%{transform:translateX(5px);}}
.loan-highlights{background:white;border-radius:16px;padding:24px;margin:32px 0;display:inline-block;text-align:left;position:relative;z-index:2;}
.loan-highlights ul{list-style:none;padding:0;margin:0;}
.loan-highlights li{padding:10px 0;color:#0f172a;font-size:1.05rem;font-weight:600;}
.loan-highlights li i{color:var(--green);margin-right:12px;font-size:1.2rem;}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
<?php include 'includes/navbar.php'; ?>
</nav>

<section class="page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7 col-xl-6">
        <div class="card-box">
          <div class="step">
            <span>2</span>
            <div>
              <h4><?php echo $display['stepTitle']; ?></h4>
              <p><?php echo htmlspecialchars($display['stepDesc']); ?></p>
            </div>
          </div>

          <?php if ($isYouVerifyProcessing): ?>
          <!-- YouVerify Processing Notice -->
          <div class="yv-processing-note">
            <i class="fa-solid fa-clock fa-lg"></i>
            <div>
              <strong>YouVerify Verification Initiated</strong><br>
              Your verification request has been submitted. Results will be available after payment and processing completion (typically within minutes).
              <?php if ($yv_reference): ?>
              <br><small>Reference ID: <?= $yv_reference ?></small>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($serviceKey === 'loan-eligibility' && $loanTeaser): ?>
          <!-- LOAN ELIGIBILITY TEASER -->
          <div class="loan-teaser">
              <div class="loan-teaser-icon">🎉</div>
              
              <h3>
                <?php if (!empty($full_name) && $full_name !== 'N/A'): ?>
                  Congratulations, <?= $full_name ?>!
                <?php else: ?>
                  Great News!
                <?php endif; ?>
              </h3>
              
              <p class="loan-teaser-label">Based on your credit profile, you pre-qualify for loans up to:</p>
              
              <div class="loan-teaser-amount">
                KES <?= number_format($loanTeaser['amount']) ?>
              </div>
              
              <?php if ($loanTeaser['status'] === 'excellent'): ?>
                <div class="loan-teaser-badge">
                  ✓ EXCELLENT CREDIT PROFILE
                </div>
              <?php elseif ($loanTeaser['status'] === 'good'): ?>
                <div class="loan-teaser-badge" style="border-color:#facc15;">
                  ✓ GOOD STANDING
                </div>
              <?php else: ?>
                <div class="loan-teaser-badge" style="border-color:#3b82f6;">
                  ✓ ELIGIBLE FOR REVIEW
                </div>
              <?php endif; ?>
              
              <div class="loan-highlights">
                  <ul>
                      <li><i class="fa-solid fa-check-circle"></i> Instant approval probability score</li>
                      <li><i class="fa-solid fa-building-columns"></i> <?= $loanTeaser['status'] === 'excellent' ? '15+' : ($loanTeaser['status'] === 'good' ? '10+' : '5+') ?> pre-matched lenders ready to approve</li>
                      <li><i class="fa-solid fa-percent"></i> Personalized interest rates & repayment plans</li>
                      <li><i class="fa-solid fa-chart-line"></i> Tips to increase your eligible amount</li>
                  </ul>
              </div>
              
              <p class="loan-teaser-cta">
                <i class="fa-solid fa-arrow-right"></i> Complete payment to unlock your full eligibility report & lender contacts
              </p>
          </div>
          <?php endif; ?>

          <div class="summary">
            <div class="summary-row">
              <span>Service</span>
              <strong><?php echo $display['serviceName']; ?></strong>
            </div>
            
            <?php if ($provider === 'youverify'): ?>
            <!-- YouVerify specific fields -->
            <div class="summary-row">
              <span><?php echo $display['identifierLabel'] ?? 'Identifier'; ?></span>
              <strong><?php echo $national_id; ?></strong>
            </div>
            <?php if ($first_name && $last_name): ?>
            <div class="summary-row">
              <span>Name</span>
              <strong><?php echo $first_name . ' ' . $last_name; ?></strong>
            </div>
            <?php endif; ?>
            
            
            
      <?php if ($business_name && $serviceKey !== 'yv-ke-collateral'): ?>
<div class="summary-row">
  <span>Business</span>
  <strong><?php echo $business_name; ?></strong>
</div>

            
            
            
            <?php endif; ?>
            <?php if ($phone): ?>
            <div class="summary-row">
              <span>Phone</span>
              <strong><?php echo $phone; ?></strong>
            </div>
            <?php endif; ?>
            
    
            
            
            
     
            <?php else: ?>
            <!-- Metropol specific fields -->
            <div class="summary-row">
              <span><?php echo $display['identifierLabel'] ?? 'National ID'; ?></span>
              <strong><?php echo $national_id; ?></strong>
            </div>
            <?php if (!empty($full_name) && $full_name !== 'N/A' && $serviceKey !== 'loan-eligibility'): ?>
            <div class="summary-row">
              <span>Name</span>
              <strong><?php echo $full_name; ?></strong>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <div class="summary-row">
              <span>Report Type</span>
              <strong><?php echo htmlspecialchars($display['reportType']); ?></strong>
            </div>
            <div class="summary-row">
              <span>Delivery</span>
              <strong><?= $provider === 'youverify' ? 'After processing' : 'Instant (after payment)' ?></strong>
            </div>
            <hr>
            <div class="summary-row total">
              <span>Total Payable</span>
              <strong>KES <?php echo $price; ?></strong>
            </div>
          </div>

          <div class="info-note">
            <i class="fa-solid fa-circle-info fa-lg"></i>
            <div><?php echo $display['infoText']; ?></div>
          </div>

          <form method="GET" action="payment.php">
            <input type="hidden" name="rid" value="<?php echo $rid; ?>">
            <button type="submit" class="btn btn-main">
              <i class="fa-solid fa-lock me-2"></i> 
              <?= $serviceKey === 'loan-eligibility' ? 'Unlock My Full Report' : 'Proceed to Secure Payment' ?>
            </button>
          </form>

          <div class="trust">
            <span><i class="fa-solid fa-lock"></i> 256-bit Encrypted</span>
            <span><i class="fa-solid fa-shield-halved"></i> Fully Private</span>
            <span><i class="fa-solid fa-bolt"></i> <?= $provider === 'youverify' ? 'Verified Results' : 'Instant Results' ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="container">
    <h5>READIWORK</h5>
    <p>AI-Driven Verification Platform</p>
    <small>© 2025 Readiwork. All rights reserved.</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>