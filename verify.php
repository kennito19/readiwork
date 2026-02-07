<?php
/**
 * Metropol CRB Identity Verification - Form Page with Inline Errors
 */
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$HASH_ORDER = 0;
$error = null;
$errorIcon = 'exclamation-triangle';
$identity_number = '';
$showTestIds = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_number'])) {
    $identity_number = trim($_POST['id_number']);

    if (!preg_match('/^\d{7,9}$/', $identity_number)) {
        $error = "Invalid format. Please enter 7-9 digits only.";
        $errorIcon = 'exclamation-circle';
    } else {
        try {
            $apiUrl = METROPOL_BASE_URL . ':' . METROPOL_PORT . '/' . METROPOL_VERSION . '/identity/verify';
            $dt = new DateTime('now', new DateTimeZone('UTC'));
            $timestamp = $dt->format('YmdHis') . substr(microtime(false), 2, 6);

            $postData = [
                "report_type"     => 1,
                "identity_number" => $identity_number,
                "identity_type"   => "001"
            ];

            $jsonBody = json_encode($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $orders = [
                0 => METROPOL_PRIVATE_KEY . $jsonBody . METROPOL_PUBLIC_KEY . $timestamp,
                1 => METROPOL_PUBLIC_KEY . $timestamp . $jsonBody . METROPOL_PRIVATE_KEY,
                2 => $timestamp . METROPOL_PUBLIC_KEY . $jsonBody . METROPOL_PRIVATE_KEY,
            ];

            $toHash = $orders[$HASH_ORDER] ?? $orders[0];
            $apiHash = hash('sha256', $toHash);

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $jsonBody,
                CURLOPT_HTTPHEADER     => [
                    "Content-Type: application/json",
                    "X-METROPOL-REST-API-KEY: " . METROPOL_PUBLIC_KEY,
                    "X-METROPOL-REST-API-HASH: $apiHash",
                    "X-METROPOL-REST-API-TIMESTAMP: $timestamp"
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_VERBOSE        => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $error = "We couldn't connect to the verification service. Please check your internet connection and try again.";
                $errorIcon = 'wifi';
            } elseif ($httpCode === 500) {
                $error = "The verification service is experiencing technical difficulties right now. This is not an issue with your ID number. Please wait a moment and try again.";
                $errorIcon = 'server';
            } elseif ($httpCode !== 200) {
                $error = "We're unable to process your request at the moment (Error: HTTP $httpCode). Please try again in a few minutes.";
                $errorIcon = 'server';
            } else {
                $json = json_decode($response, true);
                
                // Check for API errors first
                if (isset($json['has_error']) && $json['has_error'] === true) {
                    // Has error = true means there's an actual error
                    $apiCode = $json['api_code'] ?? '';
                    $apiDescription = $json['api_code_description'] ?? '';
                    $errorMessage = $json['error_message'] ?? '';
                    
                    if ($apiCode === 'E018' || strpos($errorMessage, 'test set') !== false) {
                        $errorIcon = 'user-slash';
                        $error = "The ID number <strong>" . htmlspecialchars($identity_number) . "</strong> could not be found in our records. Please verify that you've entered the correct National ID number.";
                        $showTestIds = false;
                    } elseif (in_array($apiCode, ['001', '01', 'E001'])) {
                        $errorIcon = 'user-slash';
                        $error = "National ID <strong>" . htmlspecialchars($identity_number) . "</strong> was not found in our database. Please double-check that you've entered the correct ID number.";
                        $showTestIds = false;
                    } elseif ($apiCode === 'E401' || $apiCode === 'E403') {
                        $errorIcon = 'lock';
                        $error = "We're having trouble connecting to our verification service. This is a technical issue on our end, not a problem with your ID. Please try again shortly or contact support if the issue persists.";
                    } else {
                        $errorIcon = 'circle-exclamation';
                        $error = "We couldn't complete the verification at this time. Please try again in a few moments.";
                        if ($apiDescription || $errorMessage) {
                            $error .= "<br><small style='color:var(--muted);font-size:0.85rem;margin-top:8px;display:block;'>Technical details: " . htmlspecialchars($apiDescription ?: $errorMessage) . "</small>";
                        }
                        $showTestIds = false;
                    }
                } elseif (isset($json['has_error']) && $json['has_error'] === false) {
                    // has_error = false AND we have identity data = SUCCESS
                    if (isset($json['identity_number']) || isset($json['id_number']) || isset($json['first_name'])) {
                        // SUCCESS - Save data and redirect to results page
                        $_SESSION['verification_result'] = [
                            'identity_number' => $identity_number,
                            'response' => $json,
                            'http_code' => $httpCode,
                            'timestamp' => $timestamp
                        ];
                        header('Location: test-results.php?success=1');
                        exit;
                    } else {
                        $errorIcon = 'user-slash';
                        $error = "National ID <strong>" . htmlspecialchars($identity_number) . "</strong> was not found in our records. Please verify the ID number and try again.";
                        $showTestIds = false;
                    }
                } elseif (isset($json['response_code'])) {
                    // Old response format with response_code
                    $code = $json['response_code'];
                    
                    if ($code === '00') {
                        // SUCCESS
                        $_SESSION['verification_result'] = [
                            'identity_number' => $identity_number,
                            'response' => $json,
                            'http_code' => $httpCode,
                            'timestamp' => $timestamp
                        ];
                        header('Location: test-results.php?success=1');
                        exit;
                    } else {
                        // Failed response codes
                        if (in_array($code, ['001', '01'])) {
                            $errorIcon = 'user-slash';
                            $error = "National ID <strong>" . htmlspecialchars($identity_number) . "</strong> was not found in our records. Please double-check the ID number.";
                            $showTestIds = false;
                        } else {
                            $errorIcon = 'triangle-exclamation';
                            $error = "We couldn't verify this ID at the moment. Please try again.";
                            $showTestIds = false;
                        }
                    }
                } else {
                    // Completely unexpected response
                    $error = "We received an unexpected response from the verification service. Please try again in a moment.";
                    $errorIcon = 'question-circle';
                    $showTestIds = false;
                }
            }
        } catch (Exception $e) {
            $error = "System error: " . htmlspecialchars($e->getMessage());
            $errorIcon = 'bug';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Metropol CRB Test - Readiwork Theme</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --primary:#6366f1;
  --primary-soft:#818cf8;
  --accent:#22c55e;
  --bg:#020617;
  --surface:#0f172a;
  --card:#111827;
  --text:#f1f5f9;
  --muted:#94a3b8;
  --border:#1f2937;
}
body{
  font-family:'Inter',sans-serif;
  background:var(--bg);
  color:var(--text);
  line-height:1.6;
  padding:60px 0;
}
.navbar{
  background:rgba(2,6,23,.95);
  backdrop-filter:blur(12px);
  border-bottom:1px solid var(--border);
  padding:1rem 0;
  position:fixed;
  top:0;
  width:100%;
  z-index:1000;
}
.navbar-brand{
  font-weight:900;
  font-size:1.8rem;
  color:#fff !important;
}
.form-card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:24px;
  padding:40px;
  margin-top:80px;
}
.form-card h1{
  font-weight:900;
  margin-bottom:10px;
}
.form-card h1 span{
  color:var(--accent);
}
.form-card p{
  color:var(--muted);
  margin-bottom:30px;
}
.form-label{
  font-weight:600;
  margin-bottom:10px;
  display:block;
}
.form-group{
  position:relative;
  margin-bottom:24px;
}
.form-group i{
  position:absolute;
  left:16px;
  top:50%;
  transform:translateY(-50%);
  color:var(--muted);
}
.form-control{
  background:var(--surface);
  border:1px solid var(--border);
  color:var(--text);
  padding:16px 16px 16px 48px;
  border-radius:14px;
  height:58px;
  font-size:1rem;
  width:100%;
}
.form-control:focus{
  border-color:var(--accent);
  box-shadow:none;
  background:var(--surface);
  color:var(--text);
}
.form-note{
  font-size:.9rem;
  color:var(--muted);
  margin-bottom:24px;
}
.btn-main{
  background:linear-gradient(135deg,var(--primary),var(--primary-soft));
  border:none;
  padding:16px;
  font-weight:800;
  border-radius:999px;
  font-size:1.05rem;
  width:100%;
}
.btn-main:hover{
  background:linear-gradient(135deg,var(--primary-soft),var(--primary));
}
.error-box{
  background:rgba(239,68,68,0.1);
  border:1px solid rgba(239,68,68,0.3);
  border-left:4px solid #ef4444;
  border-radius:14px;
  padding:24px;
  margin-top:24px;
  animation:slideDown 0.3s ease;
}
@keyframes slideDown{
  from{opacity:0;transform:translateY(-10px);}
  to{opacity:1;transform:translateY(0);}
}
.error-box h4{
  color:#fca5a5;
  font-weight:700;
  margin-bottom:12px;
  font-size:1.1rem;
}
.error-box p{
  color:#fca5a5;
  margin-bottom:0;
  line-height:1.7;
  font-size:1rem;
}
.test-ids-box{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:12px;
  padding:20px;
  margin-top:20px;
}
.test-ids-box p{
  color:var(--text);
  margin-bottom:12px;
  font-weight:600;
}
.test-ids-box code{
  background:var(--surface);
  padding:10px 18px;
  border-radius:8px;
  color:var(--accent);
  font-weight:700;
  font-size:1rem;
  display:inline-block;
  margin:5px;
}
</style>
</head>
<body>
<nav class="navbar">
  <div class="container">
    <a class="navbar-brand" href="index.php">READIWORK</a>
  </div>
</nav>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="form-card">
        <h1>Metropol <span>CRB</span> Test</h1>
        <p>Test your Metropol API connection with live verification</p>

        <?php if ($error): ?>
        <div class="error-box">
          <h4><i class="fa-solid fa-<?= $errorIcon ?>"></i> Verification Failed</h4>
          <p><?= $error ?></p>
        </div>
        <?php endif; ?>

        <form method="POST">
          <div class="form-group">
            <label class="form-label">National ID Number</label>
            <i class="fa-solid fa-id-card"></i>
            <input 
              type="text" 
              name="id_number" 
              class="form-control"
              value="<?= htmlspecialchars($identity_number) ?>"
              placeholder="880000088" 
              required 
              pattern="\d{7,9}" 
              maxlength="9"
            />
          </div>
          <div class="form-note">
            <i class="fa-solid fa-circle-info"></i>
            Test IDs: <strong>550000055</strong> • <strong>660000066</strong> • <strong>770000077</strong> • <strong>880000088</strong> • <strong>990000099</strong>
          </div>
          <button type="submit" class="btn btn-main">
            <i class="fa-solid fa-shield-halved"></i> Check Now
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>