<?php
require_once __DIR__ . '/config.php';

$rid = $_GET['rid'] ?? null;
if (!$rid || !ctype_digit($rid)) {
    die('Invalid request ID.');
}

$stmt = $pdo->prepare("SELECT national_id, service, price, status, result, phone FROM verification_requests WHERE id = :rid");
$stmt->execute([':rid' => $rid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) die('Request not found.');

if ($row['status'] === 'completed' && !empty($row['result'])) {
    header('Location: results.php?rid=' . $rid);
    exit;
}

$national_id = htmlspecialchars($row['national_id']);
$serviceKey  = $row['service'];
$price       = number_format($row['price'], 2);
$priceRaw    = (int)$row['price'];

// Get service configuration from config.php
$serviceConfig = get_service_config($serviceKey);

if (!$serviceConfig) {
    die('Invalid service type. <a href="index.php">Start a new check</a>');
}

$serviceName = $serviceConfig['name'];

function getMpesaErrorMessage($errorCode, $defaultMessage = '') {
    $errorCodes = [
        '1' => 'Insufficient funds in M-PESA account.',
        '17' => 'Phone number not registered for M-PESA.',
        '1032' => 'Transaction cancelled by user.',
        '1037' => 'Request timeout - please try again.',
        '2001' => 'Wrong PIN entered.',
        '26' => 'System busy - please try again.',
        '20' => 'Invalid phone number format.'
    ];
    
    $errorCode = (string)$errorCode;
    if (isset($errorCodes[$errorCode])) return $errorCodes[$errorCode];
    
    // Check message content
    $message = strtolower($defaultMessage);
    if (strpos($message, 'cancel') !== false) return 'Transaction cancelled by user.';
    if (strpos($message, 'timeout') !== false) return 'Request timeout - please try again.';
    if (strpos($message, 'insufficient') !== false) return 'Insufficient funds.';
    if (strpos($message, 'invalid') !== false && strpos($message, 'phone') !== false) return 'Invalid phone number.';
    
    return $defaultMessage ?: 'Payment request failed. Please try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone'])) {
    header('Content-Type: application/json');
    
    $phone = trim($_POST['phone']);
    $phone = preg_replace('/\s+/', '', $phone);
    
    // Convert 0712345678 or 0112345678 to 254712345678 or 254112345678
    if (preg_match('/^0([17]\d{8})$/', $phone, $matches)) {
        $phone = '254' . $matches[1];
    }
    
    // Validate format - ACCEPT BOTH SAFARICOM (2547) AND AIRTEL (2541)
    if (!preg_match('/^254[17]\d{8}$/', $phone)) {
        echo json_encode(['success' => false, 'error' => 'Invalid phone number. Use format: 254712345678 or 0712345678']);
        exit;
    }
    
    try {
        // Check if already paid
        $checkStmt = $pdo->prepare("SELECT status FROM verification_requests WHERE id = :rid");
        $checkStmt->execute([':rid' => $rid]);
        $currentStatus = $checkStmt->fetchColumn();
        
        if (in_array($currentStatus, ['paid', 'completed'])) {
            echo json_encode(['success' => false, 'error' => 'Payment already completed for this request.']);
            exit;
        }
        
        // Update phone and set status
        $updateStmt = $pdo->prepare("UPDATE verification_requests SET phone = :phone, status = 'pending_payment', payment_error = NULL, updated_at = NOW() WHERE id = :rid");
        $updateStmt->execute([':phone' => $phone, ':rid' => $rid]);
        
        // Check if send_stk_push function exists
        if (!function_exists('send_stk_push')) {
            echo json_encode(['success' => false, 'error' => 'Payment system not configured.']);
            exit;
        }
        
        // Send STK Push
        $stkResult = send_stk_push($phone, $priceRaw, $rid);
        
        error_log("STK PUSH RESULT FOR RID $rid: " . json_encode($stkResult));
        
        if ($stkResult['success']) {
            // Update with M-Pesa request IDs
            $updateMpesaStmt = $pdo->prepare("UPDATE verification_requests SET checkout_request_id = :checkout_id, merchant_request_id = :merchant_id, updated_at = NOW() WHERE id = :rid");
            $updateMpesaStmt->execute([
                ':checkout_id' => $stkResult['checkout_request_id'] ?? null,
                ':merchant_id' => $stkResult['merchant_request_id'] ?? null,
                ':rid' => $rid
            ]);
            
            echo json_encode(['success' => true, 'message' => 'STK Push sent successfully']);
            
        } else {
            // Payment request failed
            $errorCode = $stkResult['error_code'] ?? $stkResult['errorCode'] ?? null;
            $errorMsg = getMpesaErrorMessage($errorCode, $stkResult['message'] ?? 'Payment request failed');
            
            // Update status with error
            $failStmt = $pdo->prepare("UPDATE verification_requests SET status = 'payment_failed', payment_error = :error, updated_at = NOW() WHERE id = :rid");
            $failStmt->execute([':error' => $errorMsg, ':rid' => $rid]);
            
            echo json_encode(['success' => false, 'error' => $errorMsg]);
        }
        
    } catch (Exception $e) {
        error_log('Payment processing error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'System error occurred. Please try again.']);
    }
    
    exit;
}

$alreadyPaid = in_array($row['status'], ['paid', 'completed']);

// ============================================================
// SMART REDIRECT - Map services to their dedicated results pages
// ============================================================
$resultsPage = 'results.php'; // Default fallback

$resultsPageMap = [
    // National ID services → idresults.php
    'id-verification' => 'idresults.php',
    'soft-background' => 'idresults.php',
    'identity-check' => 'idresults.php',
    
    // CRB services → crbresults.php
    'crb' => 'crbresults.php',
    'crb-clearance' => 'crbresults.php',
    'crb-certificate' => 'crbresults.php',
    'loan-defaulter' => 'crbresults.php',
    
    // Passport → passportresults.php
    'yv-ke-passport' => 'passportresults.php',
    'yv-ke-alien-id' => 'alien-results.php',
    'yv-ke-collateral' => 'collateral-results.php',  // ✅ ADD THIS LINE
    'yv-ke-drivers-license' => 'license-results.php',
    'yv-ke-bank-account' => 'account-results.php',
    'yv-ke-credit-history' => 'credit-results.php',
    'yv-ke-employment' =>'employment-results.php',
        'yv-ke-phone' => 'phone-results.php',  
    
    'yv-ke-address' => 'address-results.php',  // ✅ ADD THIS
    
            // 'yv-ke-drivers-license' => 'licenseresults.php',
                // 'yv-ke-drivers-license' => 'licenseresults.php',
    // Add more mappings as you create more results pages:
    // 'yv-ke-drivers-license' => 'licenseresults.php',
    // 'yv-ke-plate-number' => 'vehicleresults.php',
    // 'credit-score' => 'creditresults.php',
    // 'loan-eligibility' => 'loanresults.php',
];





if (isset($resultsPageMap[$serviceKey])) {
    $resultsPage = $resultsPageMap[$serviceKey];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Payment | Readiwork</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--green:#22c55e;--green-dark:#16a34a;--card:#111a2f;--text:#f1f5f9;--muted:#94a3b8;--border:rgba(255,255,255,.1);}
body{background:radial-gradient(70% 60% at top,#0b1220 0%,#050b1a 60%);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;}
.navbar{background:rgba(5,11,26,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:1rem 0;}
.page{padding:160px 0 120px;}
.card-box{background:var(--card);border:1px solid var(--border);border-radius:28px;padding:48px;box-shadow:0 40px 100px rgba(0,0,0,.7);}
.step{display:flex;gap:16px;margin-bottom:32px;}
.step span{width:42px;height:42px;background:var(--green);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;}
.step h4{margin:0;font-weight:800;}
.step p{margin:4px 0 0;color:var(--muted);}
.summary{background:rgba(15,23,42,.6);border:1px solid var(--border);border-radius:20px;padding:28px;margin-bottom:32px;}
.summary-row{display:flex;justify-content:space-between;margin-bottom:16px;}
.summary-row:last-child{margin-bottom:0;padding-top:16px;border-top:1px solid var(--border);}
.summary-row span{color:var(--muted);}
.summary-row strong{color:var(--text);}
.form-group{position:relative;margin-bottom:24px;}
.form-group label{display:block;margin-bottom:12px;font-weight:600;}
.form-group i{position:absolute;left:16px;top:58%;transform:translateY(-50%);color:var(--muted);}
.form-control{background:rgba(15,23,42,.6);border:1px solid var(--border);color:#fff;padding:18px 18px 18px 48px;border-radius:16px;height:64px;}
.form-control:focus{border-color:var(--green);background:rgba(15,23,42,.8);color:#fff;box-shadow:none;}
.form-control::placeholder{color:#64748b;}
.btn-main{background:linear-gradient(135deg,var(--green),var(--green-dark));border:none;border-radius:999px;padding:18px;font-weight:800;width:100%;color:#fff;transition:all .3s;}
.btn-main:hover:not(:disabled){background:var(--green-dark);transform:translateY(-2px);}
.btn-main:disabled{opacity:0.6;cursor:not-allowed;}
.btn-secondary{background:rgba(255,255,255,.1);border:1px solid var(--border);border-radius:999px;padding:14px;font-weight:600;width:100%;color:var(--text);margin-top:12px;}
.btn-secondary:hover{background:rgba(255,255,255,.15);}
#loading{display:none;text-align:center;margin-top:40px;}
.spinner{width:64px;height:64px;border:6px solid rgba(15,23,42,.6);border-top:6px solid var(--green);border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 20px;}
@keyframes spin{to{transform:rotate(360deg);}}
.error-msg{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;padding:16px;border-radius:12px;margin-top:20px;display:none;}
.debug{background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:12px;padding:12px;margin-top:16px;font-size:0.85rem;font-family:monospace;color:var(--muted);}
.info-note{background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:14px;padding:16px;margin-top:24px;font-size:0.9rem;color:var(--muted);display:flex;gap:12px;}
.info-note i{color:var(--green);margin-top:2px;flex-shrink:0;}
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
            <span>3</span>
            <div>
              <h4>Payment</h4>
              <p>Secure M-PESA Payment</p>
            </div>
          </div>

          <div class="summary">
            <div class="summary-row">
              <span>Service</span>
              <strong><?= htmlspecialchars($serviceName) ?></strong>
            </div>
            <div class="summary-row">
              <span>National ID</span>
              <strong><?= $national_id ?></strong>
            </div>
            <div class="summary-row">
              <span>Amount Payable</span>
              <strong style="color:var(--green);font-size:1.2rem;">KES <?= $price ?></strong>
            </div>
          </div>

          <?php if (!$alreadyPaid): ?>
          <form id="payForm">
            <div class="form-group">
              <label>M-PESA Phone Number</label>
              <i class="fa-solid fa-mobile-screen"></i>
              <input type="tel" id="phone" class="form-control" placeholder="254712345678 or 0712345678" required value="<?= htmlspecialchars($row['phone'] ?? '') ?>" autocomplete="tel">
            </div>
            
            <div class="info-note">
              <i class="fa-solid fa-circle-info"></i>
              <div>
                <strong>How it works:</strong><br>
                1. Enter your M-PESA phone number<br>
                2. You'll receive an STK push prompt on your phone<br>
                3. Enter your M-PESA PIN to complete payment<br>
                4. Your report will be generated instantly
              </div>
            </div>
            
            <div class="error-msg" id="errorMsg"></div>
            
            <button type="submit" class="btn btn-main" id="payBtn">
              <i class="fa-solid fa-lock me-2"></i> Pay KES <?= $price ?>
            </button>
          </form>
          <?php endif; ?>

          <div id="loading" <?= $alreadyPaid ? 'style="display:block;"' : '' ?>>
            <div class="spinner"></div>
            <p style="font-weight:700;font-size:1.2rem;" id="loadingText">Checking Payment Status...</p>
            <p style="font-size:0.9rem;color:var(--muted);" id="loadingSubtext">Please wait while we confirm your payment...</p>
            
            <button type="button" class="btn btn-secondary" onclick="manualCheck()">
              <i class="fa-solid fa-rotate me-2"></i> Refresh Status
            </button>
            
            <div class="debug" id="debugInfo">Waiting for payment confirmation...</div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<script>
const RID = <?= $rid ?>;
const ALREADY_PAID = <?= $alreadyPaid ? 'true' : 'false' ?>;
const RESULTS_PAGE = '<?= $resultsPage ?>'; // Smart redirect based on service type
let paymentCheckInterval = null;
let paymentCheckTimeout = null;
let checkCount = 0;

if (ALREADY_PAID) {
  console.log('Already paid - starting status checks');
  startPaymentCheck();
}

document.getElementById('payForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const phone = document.getElementById('phone').value.trim();
  const payBtn = document.getElementById('payBtn');
  
  if(phone.length < 10) {
    showError('Please enter a valid phone number');
    return;
  }
  
  payBtn.disabled = true;
  payBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Sending STK Push...';
  
  // Hide any previous errors
  document.getElementById('errorMsg').style.display = 'none';

  try {
    const formData = new FormData();
    formData.append('phone', phone);
    
    const response = await fetch('payment.php?rid=' + RID, {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    console.log('Payment response:', data);
    
    if (data.success) {
      // Hide form, show loading
      document.getElementById('payForm').style.display = 'none';
      document.getElementById('loading').style.display = 'block';
      document.getElementById('loadingText').textContent = 'STK Push Sent!';
      document.getElementById('loadingSubtext').textContent = 'Please check your phone and enter your M-PESA PIN...';
      
      // Start checking payment status
      startPaymentCheck();
    } else {
      showError(data.error || 'Payment request failed. Please try again.');
      payBtn.disabled = false;
      payBtn.innerHTML = '<i class="fa-solid fa-lock me-2"></i> Pay KES <?= $price ?>';
    }
    
  } catch (error) {
    console.error('Payment error:', error);
    showError('Network error. Please check your connection and try again.');
    payBtn.disabled = false;
    payBtn.innerHTML = '<i class="fa-solid fa-lock me-2"></i> Pay KES <?= $price ?>';
  }
});

function startPaymentCheck() {
  checkPaymentStatus();
  paymentCheckInterval = setInterval(checkPaymentStatus, 2000); // Check every 2 seconds
  
  // Timeout after 2 minutes
  paymentCheckTimeout = setTimeout(() => {
    stopPaymentCheck();
    document.getElementById('loadingText').textContent = 'Payment Check Timeout';
    document.getElementById('loadingSubtext').innerHTML = 
      'We\'re still waiting for confirmation.<br>Your payment may still be processing.<br>Request ID: <strong>' + RID + '</strong>';
  }, 120000);
}

function stopPaymentCheck() {
  if (paymentCheckInterval) clearInterval(paymentCheckInterval);
  if (paymentCheckTimeout) clearTimeout(paymentCheckTimeout);
}

async function checkPaymentStatus() {
  checkCount++;
  try {
    const response = await fetch('check_payment_status.php?rid=' + RID + '&t=' + Date.now());
    const data = await response.json();
    
    console.log(`Check #${checkCount}:`, data);
    
    // Update debug info
    const debugInfo = document.getElementById('debugInfo');
    if (debugInfo) {
      debugInfo.innerHTML = `
        Check #${checkCount} at ${new Date().toLocaleTimeString()}<br>
        Status: <strong>${data.status}</strong><br>
        Has Results: ${data.has_results ? '✓ YES' : '✗ NO'}<br>
        Has Receipt: ${data.has_receipt ? '✓ YES' : '✗ NO'}<br>
        Can View Results: ${data.can_view_results ? '✓ YES' : '✗ NO'}<br>
        ${data.payment_error ? '<span style="color:#fca5a5;">Error: ' + data.payment_error + '</span><br>' : ''}
        Last Updated: ${data.updated_at || 'N/A'}
      `;
    }
    
    // Check if we can view results (payment complete AND results ready)
    if (data.can_view_results) {
      stopPaymentCheck();
      console.log('PAYMENT COMPLETE - REDIRECTING TO:', RESULTS_PAGE);
      document.getElementById('loadingText').textContent = 'Payment Confirmed! ✓';
      document.getElementById('loadingSubtext').textContent = 'Redirecting to your report...';
      
      setTimeout(() => {
        window.location.href = RESULTS_PAGE + '?rid=' + RID;
      }, 1000);
      
    } else if (data.status === 'paid') {
      document.getElementById('loadingText').textContent = 'Payment Received! ✓';
      document.getElementById('loadingSubtext').textContent = 'Generating your report...';
      
    } else if (data.status === 'completed' && !data.has_results) {
      document.getElementById('loadingText').textContent = 'Processing Report...';
      document.getElementById('loadingSubtext').textContent = 'Almost ready...';
      
    } else if (data.status === 'payment_failed') {
      stopPaymentCheck();
      showError(data.payment_error || 'Payment failed. Please try again.');
      
    } else if (data.status === 'pending_payment') {
      document.getElementById('loadingText').textContent = 'Waiting for Payment...';
      document.getElementById('loadingSubtext').textContent = 'Please approve the M-PESA prompt on your phone';
    }
    
  } catch (error) {
    console.error('Status check error:', error);
    const debugInfo = document.getElementById('debugInfo');
    if (debugInfo) {
      debugInfo.innerHTML = `<span style="color:#fca5a5;">ERROR: ${error.message}</span>`;
    }
  }
}

function manualCheck() {
  console.log('Manual status check triggered');
  checkPaymentStatus();
}

function showError(message) {
  stopPaymentCheck();
  
  const payBtn = document.getElementById('payBtn');
  const errorMsg = document.getElementById('errorMsg');
  const loading = document.getElementById('loading');
  const payForm = document.getElementById('payForm');
  
  if (payBtn) {
    payBtn.disabled = false;
    payBtn.innerHTML = '<i class="fa-solid fa-lock me-2"></i> Pay KES <?= $price ?>';
  }
  
  if (loading) loading.style.display = 'none';
  if (payForm) payForm.style.display = 'block';
  
  if (errorMsg) {
    errorMsg.innerHTML = '<i class="fa-solid fa-exclamation-circle me-2"></i> ' + message;
    errorMsg.style.display = 'block';
  }
}

// Clean up intervals when leaving page
window.addEventListener('beforeunload', stopPaymentCheck);
</script>

</body>
</html>