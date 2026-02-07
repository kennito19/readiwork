<?php
/**
 * Readiwork - Kenya Phone Number Verification
 * Verify phone numbers using national ID
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '' || $base_path === '.') $base_path = '/readiwork';

$error = null;
$errorIcon = 'exclamation-triangle';
$id_number = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_number'])) {
    $id_number = trim($_POST['id_number']);

    // Validate National ID: 7-8 digits
    if (!preg_match('/^\d{7,8}$/', $id_number)) {
        $error = "Invalid National ID format. Must be 7-8 digits.";
        $errorIcon = 'exclamation-circle';
    } else {
        try {
            $service_type = 'yv-ke-phone';
            $service_config = get_service_config($service_type);
            $yv_endpoint = $service_config['yv_endpoint'] ?? null;

            if (!$yv_endpoint) {
                $error = "Service configuration error. Please contact support.";
                $errorIcon = 'circle-exclamation';
            } else {
                // Build YouVerify request - National ID only
                $yv_data = [
                    'id' => $id_number,
                    'idType' => 'national-id',
                    'isSubjectConsent' => true
                ];

                // Call YouVerify API
                $yv_result = youverify_api_request($yv_endpoint, $yv_data);

                if ($yv_result && $yv_result['success']) {
                    // Get reference ID from nested data
                    $api_response = $yv_result['data'] ?? [];
                    $api_data = $api_response['data'] ?? [];
                    $reference_id = $api_data['id'] ?? null;
                    $api_status = $api_data['status'] ?? 'unknown';

                    if (!$reference_id) {
                        $error = "Verification initiated but no reference ID received.";
                        $errorIcon = 'question-circle';
                    } elseif ($api_status === 'not_found') {
                        $error = "No phone records found for ID <strong>" . htmlspecialchars($id_number) . "</strong>.";
                        $errorIcon = 'user-slash';
                    } else {
                        try {
                            // Extract data from response
                            $full_name = $api_data['fullName'] ?? null;
                            $first_name = $api_data['firstName'] ?? null;
                            $middle_name = $api_data['middleName'] ?? null;
                            $last_name = $api_data['lastName'] ?? null;
                            $dob = $api_data['dateOfBirth'] ?? null;
                            $gender = $api_data['gender'] ?? null;
                            $marital_status = $api_data['maritalStatus'] ?? null;
                            $email = $api_data['email'] ?? null;

                            // Extract phone numbers
                            $home_phone = $api_data['homeTelephoneNumber'] ?? null;
                            $mobile_phone = $api_data['mobileTelephoneNumber'] ?? null;
                            $work_phone = $api_data['workTelephoneNumber'] ?? null;

                            // Store phone numbers as JSON in phone field
                            $phone_info = null;
                            if ($home_phone || $mobile_phone || $work_phone) {
                                $phone_info = json_encode([
                                    'mobile' => $mobile_phone,
                                    'home' => $home_phone,
                                    'work' => $work_phone,
                                    'email' => $email
                                ]);
                            }

                            // Insert into database
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
                                ':phone'    => $phone_info,
                                ':bname'    => $gender ? "Gender: $gender" . ($marital_status ? ", Marital: $marital_status" : '') : null,
                                ':addr'     => $email,
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
                    // YouVerify API error
                    $http_code = $yv_result['http_code'] ?? 0;
                    $error_data = $yv_result['data'] ?? [];
                    $api_message = $error_data['message'] ?? '';

                    if ($http_code === 401) {
                        $error = "Authentication error. Please contact support.";
                        $errorIcon = 'key';
                    } elseif ($http_code === 400) {
                        $error = "Invalid data: " . ($api_message ?: "Please check your ID and try again.");
                        $errorIcon = 'exclamation-circle';
                    } elseif ($http_code === 404) {
                        $error = "No phone records found for this ID.";
                        $errorIcon = 'user-slash';
                    } elseif ($http_code >= 500) {
                        $error = "Server error (HTTP $http_code). The service may be temporarily unavailable.";
                        $errorIcon = 'server';
                    } else {
                        $error = $api_message ?: "Verification failed (HTTP $http_code). Please try again later.";
                        $errorIcon = 'server';
                    }

                    error_log("YouVerify Error: HTTP $http_code - " . json_encode($error_data));
                } else {
                    $error = "Network error. Please check your connection and try again.";
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
<title>AI Kenya Phone Verification — Readiwork AI</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Verify phone numbers for Kenyan citizens using official records.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_path ?>/assets/css/theme.css">
<style>
:root{--primary:#6366f1;--primary-soft:#818cf8;--accent:#22c55e;--bg:#020617;--surface:#0f172a;--card:#111827;--text:#f1f5f9;--muted:#94a3b8;--border:#1f2937;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.6;}
.navbar{background:rgba(2,6,23,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:1rem 0;}
.navbar-brand{font-weight:900;font-size:1.8rem;color:#fff !important;letter-spacing:1px;}
.nav-link{color:#cbd5f5 !important;}
.nav-link.active,.nav-link:hover{color:var(--accent) !important;}
.dropdown-menu{background:var(--surface);border:1px solid var(--border);}
.dropdown-item{color:#e5e7eb !important;}
.dropdown-item:hover{background:rgba(34,197,94,.15);color:var(--accent) !important;}
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
.form-label{font-weight:600;}
.form-group{position:relative;margin-bottom:20px;}
.form-group i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--muted);z-index:2;}
.form-control,.form-select{background:var(--surface);border:1px solid var(--border);color:var(--text);padding:16px 16px 16px 48px;border-radius:14px;height:58px;font-size:1rem;}
.form-control::placeholder{color:#94a3b8;}
.form-control:focus,.form-select:focus{border-color:var(--accent);box-shadow:none;}
.form-note{font-size:.9rem;color:var(--muted);margin:18px 0 26px;}
.btn-main{background:linear-gradient(135deg,var(--primary),var(--primary-soft));border:none;padding:16px;font-weight:800;border-radius:999px;font-size:1.05rem;}
.btn-main:hover{background:linear-gradient(135deg,var(--primary-soft),var(--primary));}
.form-footer{display:flex;justify-content:space-between;margin-top:18px;font-size:.9rem;color:var(--muted);}
.form-footer i{color:var(--accent);}
.error-box{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-left:4px solid #ef4444;border-radius:14px;padding:24px;margin-bottom:24px;animation:slideDown 0.3s ease;}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.error-box h4{color:#fca5a5;font-weight:700;margin-bottom:12px;font-size:1.1rem;}
.error-box p{color:#fca5a5;margin-bottom:0;line-height:1.7;font-size:1rem;}
.provider-badge{display:inline-block;padding:6px 12px;border-radius:8px;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-left:12px;}
.provider-youverify{background:rgba(34,197,94,0.2);color:#22c55e;}
footer{background:var(--surface);padding:60px 0;text-align:center;border-top:1px solid var(--border);}
footer h5{font-weight:900;font-size:1.8rem;}
footer p, footer small{color:var(--muted);}
@media (max-width:991px){.page-hero{padding:150px 0 100px;}.page-hero h1{font-size:3rem;}}
@media (max-width:767px){.page-hero{padding:130px 0 80px;}.page-hero h1{font-size:2.6rem;}.content{padding:60px 0 100px;}}
@media (max-width:480px){.page-hero{padding:110px 0 70px;}.page-hero h1{font-size:2.3rem;}}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<section class="page-hero">
  <div class="container">
    <h1><i class="fa-solid fa-robot"></i> Kenya <span>Phone</span> Verification<span class="provider-badge provider-youverify">AI + YouVerify</span></h1>
    <p>Verify phone numbers and contact details using official records.</p>
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

                    <form method="POST" id="verificationForm">
                        <div class="mb-4 form-group">
                            <label class="form-label">National ID Number</label>
                            <i class="fa-solid fa-id-card"></i>
                            <input type="text" name="id_number" id="idNumberInput" class="form-control"
                                   value="<?= htmlspecialchars($id_number) ?>"
                                   placeholder="e.g. 12345678" required maxlength="8">
                        </div>

                        <div class="form-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Your National ID is used strictly for phone number verification purposes.
                        </div>

                        <button type="submit" class="btn btn-main w-100">
                            Verify Phone Numbers
                        </button>

                        <div class="form-footer">
                            <span><i class="fa-solid fa-lock"></i> Encrypted</span>
                            <span><i class="fa-solid fa-bolt"></i> Instant</span>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5 order-2 order-lg-1">
                <div class="info-card">
                    <h4>What this check includes</h4>
                    <ul class="info-list">
                        <li><i class="fa-solid fa-check"></i> Mobile phone number verification</li>
                        <li><i class="fa-solid fa-check"></i> Home telephone number</li>
                        <li><i class="fa-solid fa-check"></i> Work telephone number</li>
                        <li><i class="fa-solid fa-check"></i> Email address verification</li>
                        <li><i class="fa-solid fa-check"></i> Personal details validation</li>
                        <li><i class="fa-solid fa-check"></i> Instant status: found / not found</li>
                    </ul>
                    <div class="info-badges">
                        <div class="info-badge"><i class="fa-solid fa-lock"></i>Secure</div>
                        <div class="info-badge"><i class="fa-solid fa-user-shield"></i>Private</div>
                        <div class="info-badge"><i class="fa-solid fa-bolt"></i>Instant</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer>
<?php include 'includes/footer.php'; ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// National ID validation - numbers only, 7-8 digits
const idNumberInput = document.getElementById('idNumberInput');

idNumberInput.addEventListener('input', function() {
    // Only allow numbers
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>
<script src="<?= $base_path ?>/assets/js/main.js"></script>
</body>
</html>