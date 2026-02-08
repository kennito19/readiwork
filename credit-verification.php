<?php
/**
 * Readiwork - Kenya Credit History Verification
 * API Endpoint: POST /v2/verifications/identity/ke/credit-history
 * Request: { "id": "1234567", "idType": "national-id", "isSubjectConsent": true }
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '.') $base_path = '';

$error = null;
$errorIcon = 'exclamation-triangle';
$id_number = '';
$id_type = 'national-id'; // Always use national-id

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_number'])) {
    $id_number = trim($_POST['id_number']);
    $id_type = 'national-id'; // Force national-id only

    // Validate National ID: 7-8 digits
    $is_valid = false;
    
    if (preg_match('/^\d{7,8}$/', $id_number)) {
        $is_valid = true;
    } else {
        $error = "Invalid National ID format. Must be 7-8 digits (e.g. 1234567).";
        $errorIcon = 'exclamation-circle';
    }
    
    if ($is_valid) {
        try {
            $service_type = 'yv-ke-credit-history';
            $service_config = get_service_config($service_type);
            $yv_endpoint = $service_config['yv_endpoint'] ?? null;

            if (!$yv_endpoint) {
                error_log("Credit History Service Config Error - Service not found or endpoint missing");
                error_log("Service type: $service_type");
                error_log("Available services: " . implode(', ', array_keys(ALL_SERVICES)));
                $error = "Service configuration error. Credit history service not found in config.php.<br><small>Check error logs for details.</small>";
                $errorIcon = 'circle-exclamation';
            } else {
                // Build YouVerify request
                $yv_data = [
                    'id' => $id_number,
                    'idType' => 'national-id',
                    'isSubjectConsent' => true
                ];
                
                error_log("Credit History API Request - Endpoint: $yv_endpoint, ID: $id_number, Type: national-id");

                // Call YouVerify API
                $yv_result = youverify_api_request($yv_endpoint, $yv_data);
                
                // Log full response for debugging
                error_log("Credit History API Response - Success: " . ($yv_result['success'] ? 'true' : 'false'));
                error_log("Credit History API Response - HTTP Code: " . ($yv_result['http_code'] ?? 'none'));
                error_log("Credit History API Response - Full: " . json_encode($yv_result));

                if ($yv_result && $yv_result['success']) {
                    $api_response = $yv_result['data'] ?? [];
                    $api_data = $api_response['data'] ?? [];
                    $reference_id = $api_data['id'] ?? null;
                    $api_status = $api_data['status'] ?? 'unknown';

                    if (!$reference_id) {
                        $error = "Verification initiated but no reference ID received.";
                        $errorIcon = 'question-circle';
                    } elseif ($api_status === 'not_found') {
                        $error = "No credit history found for ID <strong>" . htmlspecialchars($id_number) . "</strong>.";
                        $errorIcon = 'user-slash';
                    } else {
                        try {
                            // Extract data from response
                            $full_name = $api_data['fullName'] ?? null;
                            $first_name = $api_data['firstName'] ?? null;
                            $last_name = $api_data['lastName'] ?? null;
                            $dob = $api_data['dateOfBirth'] ?? null;
                            
                            // Store contract summary and inquiries as JSON in address field
                            $credit_data = [
                                'contractSummary' => $api_data['contractSummary'] ?? [],
                                'inquiries' => $api_data['inquiries'] ?? [],
                                'gender' => $api_data['gender'] ?? null,
                                'maritalStatus' => $api_data['maritalStatus'] ?? null
                            ];
                            
                            // Store in database
                            $stmt = $pdo->prepare("
                                INSERT INTO verification_requests
                                (service, provider, national_id, first_name, last_name, full_name, dob, phone, business_name, address, price, status, yv_reference_id, result, ip_address, user_agent, created_at, updated_at)
                                VALUES (:s, 'youverify', :nid, :fname, :lname, :fullname, :dob, :phone, :bname, :addr, :p, :status, :ref, :result, :ip, :ua, NOW(), NOW())
                            ");
                            $stmt->execute([
                                ':s'        => $service_type,
                                ':nid'      => $id_number,
                                ':fname'    => $first_name,
                                ':lname'    => $last_name,
                                ':fullname' => $full_name,
                                ':dob'      => $dob,
                                ':phone'    => 'national-id', // Store ID type in phone field
                                ':bname'    => null,
                                ':addr'     => json_encode($credit_data), // Store credit data as JSON
                                ':p'        => service_price($service_type),
                                ':status'   => 'pending',
                                ':ref'      => $reference_id,
                                ':result'   => json_encode($yv_result),
                                ':ip'       => client_ip(),
                                ':ua'       => user_agent()
                            ]);

                            $insert_id = $pdo->lastInsertId();
                            header('Location: confirm.php?rid=' . $insert_id);
                            exit;

                        } catch (Exception $e) {
                            error_log('DB Error: ' . $e->getMessage());
                            $error = "Verification initiated but couldn't save request. Please contact support.";
                            $errorIcon = 'database';
                        }
                    }
                } elseif ($yv_result) {
                    $http_code = $yv_result['http_code'] ?? 0;
                    $error_message = $yv_result['message'] ?? 'Unknown error';
                    
                    error_log("YouVerify Credit History Error - HTTP: $http_code, Message: $error_message");

                    if ($http_code === 401) {
                        $error = "Authentication error. API credentials invalid.";
                        $errorIcon = 'key';
                    } elseif ($http_code === 400) {
                        $error = "Invalid data: " . htmlspecialchars($error_message);
                        $errorIcon = 'exclamation-circle';
                    } elseif ($http_code === 404) {
                        $error = "No credit history found or endpoint incorrect.<br><small>Endpoint: $yv_endpoint<br>This could mean: (1) ID has no credit history, (2) Wrong endpoint, or (3) API sandbox limitation.</small>";
                        $errorIcon = 'user-slash';
                    } elseif ($http_code === 429) {
                        $error = "Too many requests. Please wait a few minutes and try again.";
                        $errorIcon = 'hourglass-half';
                    } else {
                        $error = "API Error (HTTP $http_code): " . htmlspecialchars($error_message);
                        $errorIcon = 'server';
                    }
                } else {
                    $error = "Network error. Check API configuration and connection.";
                    $errorIcon = 'wifi';
                }
            }
        } catch (Exception $e) {
            error_log('System Error: ' . $e->getMessage());
            $error = "System error occurred. Please try again or contact support.";
            $errorIcon = 'bug';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>AI Kenya Credit History Verification — Readiwork AI</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Verify Kenya credit history with contract summary and inquiry statistics.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_path ?>/assets/css/theme.css">
<style>
:root{--primary:#6366f1;--primary-soft:#818cf8;--accent:#22c55e;--bg:#020617;--surface:#0f172a;--card:#111827;--text:#f1f5f9;--muted:#94a3b8;--border:#1f2937;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.6;}
.navbar{background:rgba(2,6,23,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:1rem 0;}
.page-hero{padding:170px 0 120px;text-align:center;}
.page-hero h1{font-size:3.4rem;font-weight:900;margin-bottom:20px;}
.page-hero span{color:var(--accent);}
.page-hero p{max-width:760px;margin:0 auto;font-size:1.15rem;color:var(--muted);}
.content{padding:80px 0 120px;}
.info-card,.form-card{background:var(--card);border:1px solid var(--border);border-radius:24px;padding:40px;height:100%;}
.info-card h4{font-weight:800;margin-bottom:24px;}
.info-list{list-style:none;padding:0;}
.info-list li{display:flex;gap:12px;margin-bottom:14px;color:var(--muted);}
.info-list i{color:var(--accent);margin-top:4px;}
.info-badges{display:flex;gap:14px;margin-top:28px;flex-wrap:wrap;}
.info-badge{flex:1;min-width:130px;text-align:center;background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:14px;font-size:.85rem;color:var(--muted);}
.info-badge i{display:block;margin-bottom:6px;color:var(--accent);font-size:1.2rem;}
.form-label{font-weight:600;margin-bottom:8px;}
.form-group{position:relative;margin-bottom:20px;}
.form-group i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--muted);z-index:2;}
.form-control,.form-select{background:var(--surface);border:1px solid var(--border);color:var(--text);padding:16px 16px 16px 48px;border-radius:14px;height:58px;font-size:1rem;}
.form-select{padding-left:48px;}
.form-control::placeholder{color:#94a3b8;}
.form-control:focus,.form-select:focus{border-color:var(--accent);box-shadow:none;background:var(--surface);color:var(--text);}
.form-select option{background:var(--surface);color:var(--text);}
.form-note{font-size:.9rem;color:var(--muted);margin:18px 0 26px;}
.btn-main{background:linear-gradient(135deg,var(--primary),var(--primary-soft));border:none;padding:16px;font-weight:800;border-radius:999px;font-size:1.05rem;}
.btn-main:hover{background:linear-gradient(135deg,var(--primary-soft),var(--primary));}
.form-footer{display:flex;justify-content:space-between;margin-top:18px;font-size:.9rem;color:var(--muted);}
.form-footer i{color:var(--accent);}
.error-box{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-left:4px solid #ef4444;border-radius:14px;padding:24px;margin-bottom:24px;animation:slideDown 0.3s ease;}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.error-box h4{color:#fca5a5;font-weight:700;margin-bottom:12px;font-size:1.1rem;}
.error-box p{color:#fca5a5;margin-bottom:0;line-height:1.7;font-size:1rem;}
.provider-badge{display:inline-block;padding:6px 12px;border-radius:8px;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-left:12px;background:rgba(34,197,94,0.2);color:#22c55e;}
footer{background:var(--surface);padding:60px 0;text-align:center;border-top:1px solid var(--border);}
@media (max-width:767px){.page-hero h1{font-size:2.6rem;}}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<section class="page-hero">
  <div class="container">
    <h1><i class="fa-solid fa-robot"></i> Kenya <span>Credit History</span> Verification<span class="provider-badge">AI + YouVerify</span></h1>
    <p>Get comprehensive credit history with contract summary and inquiry statistics.</p>
  </div>
</section>

<section class="content">
    <div class="container">
        <div class="row g-5 align-items-stretch">
            <div class="col-lg-7 order-1 order-lg-2">
                <div class="form-card">
                    <?php if ($error): ?>
                    <div class="error-box">
                      <h4><i class="fa-solid fa-<?= $errorIcon ?>"></i> Verification Failed</h4>
                      <p><?= $error ?></p>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4 form-group">
                            <label class="form-label">National ID Number</label>
                            <i class="fa-solid fa-fingerprint"></i>
                            <input type="text" name="id_number" id="idInput" class="form-control"
                                   value="<?= htmlspecialchars($id_number) ?>"
                                   placeholder="1234567" required maxlength="8">
                        </div>

                        <div class="form-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Enter National ID (7-8 digits) to check credit history.
                        </div>

                        <button type="submit" class="btn btn-main w-100">Check Credit History</button>

                        <div class="form-footer">
                            <span><i class="fa-solid fa-lock"></i> Encrypted</span>
                            <span><i class="fa-solid fa-bolt"></i> Instant</span>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5 order-2 order-lg-1">
                <div class="info-card">
                    <h4>What this credit check includes</h4>
                    <ul class="info-list">
                        <li><i class="fa-solid fa-check"></i> Personal information verification</li>
                        <li><i class="fa-solid fa-check"></i> Contract summary (open & closed)</li>
                        <li><i class="fa-solid fa-check"></i> Credit inquiry statistics (1-24 months)</li>
                        <li><i class="fa-solid fa-check"></i> Full name and date of birth</li>
                        <li><i class="fa-solid fa-check"></i> Gender and marital status</li>
                        <li><i class="fa-solid fa-check"></i> National ID verification</li>
                    </ul>
                    <div class="info-badges">
                        <div class="info-badge"><i class="fa-solid fa-lock"></i>Secure</div>
                        <div class="info-badge"><i class="fa-solid fa-bolt"></i>Instant</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer><?php include 'includes/footer.php'; ?></footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// National ID input handling - numbers only, max 8 digits
const idInput = document.getElementById('idInput');

idInput.addEventListener('input', function() {
    // National ID: numbers only
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>
<script src="<?= $base_path ?>/assets/js/main.js"></script>
</body>
</html>