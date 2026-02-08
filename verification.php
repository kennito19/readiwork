<?php
/**
 * Readiwork Verification Page with Multi-Provider Support
 * Supports: Metropol CRB + YouVerify
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
$service_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['national_id'])) {
    $identity_number = trim($_POST['national_id']);
    $service_type = trim($_POST['service'] ?? '');

    // Validate ID format
    if (!preg_match('/^\d{7,9}$/', $identity_number)) {
        $error = "Invalid format. Please enter 7-9 digits only.";
        $errorIcon = 'exclamation-circle';
    } elseif (empty($service_type) || !is_valid_service($service_type)) {
        $error = "Invalid service type. Please try again.";
        $errorIcon = 'exclamation-circle';
    } else {
        try {
            // Get service configuration
            $service_config = get_service_config($service_type);
            $provider = $service_config['provider'] ?? 'metropol';
            
            // ================================================================
            // METROPOL PROVIDER
            // ================================================================
            if ($provider === 'metropol') {
                // Get all API calls needed for this service
                $api_calls = get_service_api_calls($service_type);
                $combined_results = [];
                $api_errors = [];
                $first_call_success = false;
                
                // Execute each API call sequentially
                foreach ($api_calls as $index => $api_config) {
                    $endpoint = $api_config['endpoint'];
                    $report_type = $api_config['report_type'];
                    
                    // Build API URL
                    $apiUrl = METROPOL_BASE_URL . ':' . METROPOL_PORT . '/' . METROPOL_VERSION . $endpoint;
                    $dt = new DateTime('now', new DateTimeZone('UTC'));
                    $timestamp = $dt->format('YmdHis') . substr(microtime(false), 2, 6);

                    $postData = [
                        "report_type"     => $report_type,
                        "identity_number" => $identity_number,
                        "identity_type"   => "001"
                    ];

                    $jsonBody = json_encode($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                    // Generate hash
                    $orders = [
                        0 => METROPOL_PRIVATE_KEY . $jsonBody . METROPOL_PUBLIC_KEY . $timestamp,
                        1 => METROPOL_PUBLIC_KEY . $timestamp . $jsonBody . METROPOL_PRIVATE_KEY,
                        2 => $timestamp . METROPOL_PUBLIC_KEY . $jsonBody . METROPOL_PRIVATE_KEY,
                    ];

                    $toHash = $orders[$HASH_ORDER] ?? $orders[0];
                    $apiHash = hash('sha256', $toHash);

                    // Make cURL request
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

                    // Handle response
                    if ($curlError) {
                        $api_errors[$endpoint] = "Connection error: $curlError";
                        
                        if ($index === 0) {
                            $error = "We couldn't connect to the verification service. Please check your internet connection and try again.";
                            $errorIcon = 'wifi';
                            break;
                        }
                    } elseif ($httpCode === 500) {
                        $api_errors[$endpoint] = "HTTP 500 error";
                        
                        if ($index === 0) {
                            $error = "The verification service is experiencing technical difficulties right now. This is not an issue with your ID number. Please wait a moment and try again.";
                            $errorIcon = 'server';
                            break;
                        }
                    } elseif ($httpCode !== 200) {
                        $api_errors[$endpoint] = "HTTP $httpCode error";
                        
                        if ($index === 0) {
                            $error = "We're unable to process your request at the moment (Error: HTTP $httpCode). Please try again in a few minutes.";
                            $errorIcon = 'server';
                            break;
                        }
                    } else {
                        $json = json_decode($response, true);
                        
                        if (!$json) {
                            $api_errors[$endpoint] = "Invalid JSON response";
                            if ($index === 0) {
                                $error = "We received an unexpected response from the verification service. Please try again.";
                                $errorIcon = 'question-circle';
                                break;
                            }
                            continue;
                        }
                        
                        // ALWAYS store the response first
                        $combined_results[$endpoint] = $json;

                        // Check for API errors
                        if (isset($json['has_error']) && $json['has_error'] === true) {
                            $apiCode = $json['api_code'] ?? '';
                            $errorMessage = $json['error_message'] ?? '';
                            
                            $api_errors[$endpoint] = $errorMessage ?: $apiCode;
                            error_log("API Error on $endpoint: $apiCode - $errorMessage");
                            
                            if ($index === 0) {
                                if ($apiCode === 'E018' || strpos($errorMessage, 'not found') !== false) {
                                    $errorIcon = 'user-slash';
                                    $error = "The ID number <strong>" . htmlspecialchars($identity_number) . "</strong> could not be found in our records.";
                                    break;
                                } elseif (in_array($apiCode, ['E001', '001'])) {
                                    $errorIcon = 'user-slash';
                                    $error = "National ID <strong>" . htmlspecialchars($identity_number) . "</strong> was not found in our database.";
                                    break;
                                } elseif (in_array($apiCode, ['E401', 'E403', 'E500'])) {
                                    $errorIcon = 'server';
                                    $error = "Service temporarily unavailable. Please try again in a few moments.";
                                    break;
                                } else {
                                    $errorIcon = 'circle-exclamation';
                                    $error = "Verification failed. Please try again.";
                                    break;
                                }
                            }
                            continue;
                        }

                        // Mark first call as successful
                        if ($index === 0) {
                            $first_call_success = true;
                        }
                        
                        // Check if data exists in response
                        $has_data = false;
                        
                        if (isset($json['identity_number']) || isset($json['id_number']) || 
                            isset($json['first_name']) || isset($json['names'])) {
                            $has_data = true;
                        }
                        
                        if (isset($json['response_code']) && $json['response_code'] === '00') {
                            $has_data = true;
                        }
                        
                        if (isset($json['has_error']) && $json['has_error'] === false) {
                            $has_data = true;
                        }
                        
                        if (!$has_data && $index === 0) {
                            $errorIcon = 'user-slash';
                            $error = "National ID <strong>" . htmlspecialchars($identity_number) . "</strong> was not found in our records.";
                            break;
                        }
                    }
                }
                
                // Process Metropol results
                if ($first_call_success && count($combined_results) > 0) {
                    // Extract identity information
                    $full_name = null;
                    $dob = null;
                    $gender = null;
                    $nationality = 'Kenyan';
                    
                    foreach ($combined_results as $endpoint => $response) {
                        if (!is_array($response)) continue;
                        
                        // Extract full name
                        if (!$full_name) {
                            if (isset($response['first_name'])) {
                                $parts = [];
                                if (!empty($response['first_name'])) $parts[] = $response['first_name'];
                                if (!empty($response['other_name'])) $parts[] = $response['other_name'];
                                elseif (!empty($response['other_names'])) $parts[] = $response['other_names'];
                                elseif (!empty($response['middle_name'])) $parts[] = $response['middle_name'];
                                if (!empty($response['surname'])) $parts[] = $response['surname'];
                                elseif (!empty($response['last_name'])) $parts[] = $response['last_name'];
                                
                                $full_name = trim(implode(' ', $parts));
                            }
                            elseif (!empty($response['names'])) {
                                $full_name = is_array($response['names']) ? implode(' ', $response['names']) : $response['names'];
                            }
                            elseif (!empty($response['full_name'])) {
                                $full_name = $response['full_name'];
                            }
                            elseif (!empty($response['name'])) {
                                $full_name = $response['name'];
                            }
                        }
                        
                        if (!$dob) {
                            if (isset($response['dob'])) $dob = $response['dob'];
                            elseif (isset($response['date_of_birth'])) $dob = $response['date_of_birth'];
                        }
                        
                        if (!$gender) {
                            if (isset($response['gender'])) $gender = $response['gender'];
                            elseif (isset($response['sex'])) $gender = $response['sex'];
                        }
                        
                        if (isset($response['citizenship'])) $nationality = $response['citizenship'];
                        elseif (isset($response['nationality'])) $nationality = $response['nationality'];
                        
                        if ($full_name && $dob && $gender) break;
                    }
                    
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO verification_requests
                            (service, provider, national_id, full_name, dob, nationality, gender, price, status, result, api_calls_made, api_errors, ip_address, user_agent, created_at, updated_at)
                            VALUES (:s, 'metropol', :nid, :name, :dob, :nat, :gender, :p, 'pending', :result, :calls, :errors, :ip, :ua, NOW(), NOW())
                        ");
                        $stmt->execute([
                            ':s'      => $service_type,
                            ':nid'    => $identity_number,
                            ':name'   => $full_name,
                            ':dob'    => $dob,
                            ':nat'    => $nationality,
                            ':gender' => $gender,
                            ':p'      => service_price($service_type),
                            ':result' => json_encode($combined_results),
                            ':calls'  => json_encode(array_keys($combined_results)),
                            ':errors' => !empty($api_errors) ? json_encode($api_errors) : null,
                            ':ip'     => client_ip(),
                            ':ua'     => user_agent()
                        ]);

                        $insert_id = $pdo->lastInsertId();
                        header('Location: confirm.php?rid=' . $insert_id);
                        exit;
                        
                    } catch (Exception $e) {
                        error_log('DB Error: ' . $e->getMessage());
                        $error = "ID verified but couldn't save request. Please contact support.";
                        $errorIcon = 'database';
                    }
                } elseif (!$error) {
                    $error = "We couldn't verify this ID at the moment. Please try again.";
                    $errorIcon = 'triangle-exclamation';
                }
            }
            
            // ================================================================
            // YOUVERIFY PROVIDER
            // ================================================================
            elseif ($provider === 'youverify') {
                $yv_type = $service_config['yv_type'] ?? null;
                $yv_endpoint = $service_config['yv_endpoint'] ?? null;
                
                if (!$yv_type || !$yv_endpoint) {
                    $error = "Service configuration error. Please contact support.";
                    $errorIcon = 'circle-exclamation';
                } else {
                    // Get form data
                    $first_name = trim($_POST['first_name'] ?? '');
                    $last_name = trim($_POST['last_name'] ?? '');
                    $dob = trim($_POST['dob'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $business_name = trim($_POST['business_name'] ?? '');
                    $bank_code = trim($_POST['bank_code'] ?? '');
                    $address = trim($_POST['address'] ?? '');
                    $employer_name = trim($_POST['employer_name'] ?? '');
                    $position = trim($_POST['position'] ?? '');
                    $chassis_number = trim($_POST['chassis_number'] ?? '');
                    
                    // Build YouVerify request data based on service type
                    $yv_data = ['isSubjectConsent' => true];
                    $validation_passed = true;
                    
                    switch ($yv_type) {
                        case 'ke_national_id':
                            if (empty($first_name) || empty($last_name)) {
                                $error = "First name and last name are required.";
                                $validation_passed = false;
                            } else {
                                $yv_data['id'] = $identity_number;
                                $yv_data['firstName'] = $first_name;
                                $yv_data['lastName'] = $last_name;
                            }
                            break;
                            
                        case 'ke_passport':
                            // Passport only requires passport number + consent
                            $yv_data['id'] = $identity_number;
                            
                            // Optional: add name matching if provided
                            if (!empty($first_name) || !empty($last_name)) {
                                $yv_data['validations'] = [
                                    'data' => array_filter([
                                        'firstName' => trim($first_name),
                                        'lastName'  => trim($last_name),
                                    ])
                                ];
                            }
                            break;
                            
                        case 'ke_alien_id':
                            if (empty($first_name) || empty($last_name)) {
                                $error = "First name and last name are required.";
                                $validation_passed = false;
                            } else {
                                $yv_data['alienId'] = $identity_number;
                                $yv_data['firstName'] = $first_name;
                                $yv_data['lastName'] = $last_name;
                            }
                            break;
                            
                    
                            
                        case 'ke_bank_account':
                            if (empty($bank_code)) {
                                $error = "Bank code is required.";
                                $validation_passed = false;
                            } else {
                                $yv_data['accountNumber'] = $identity_number;
                                $yv_data['bankCode'] = $bank_code;
                            }
                            break;
                            
                        case 'ke_tax_verification':
                            if (empty($first_name) || empty($last_name)) {
                                $error = "Full name is required.";
                                $validation_passed = false;
                            } else {
                                $yv_data['kraPin'] = $identity_number;
                                $yv_data['fullName'] = $first_name . ' ' . $last_name;
                            }
                            break;
                            
                        case 'ke_address_verification':
                            if (empty($address)) {
                                $error = "Address is required.";
                                $validation_passed = false;
                            } else {
                                $yv_data['idNumber'] = $identity_number;
                                $yv_data['address'] = $address;
                            }
                            break;
                            
                        case 'ke_phone_verification':
                            if (empty($phone)) {
                                $error = "Phone number is required.";
                                $validation_passed = false;
                            } else {
                                $yv_data['phoneNumber'] = $phone;
                                $yv_data['idNumber'] = $identity_number;
                            }
                            break;
                            
                        case 'ke_employment_verification':
                            if (empty($employer_name) || empty($position)) {
                                $error = "Employer name and position are required.";
                                $validation_passed = false;
                            } else {
                                $yv_data['idNumber'] = $identity_number;
                                $yv_data['employerName'] = $employer_name;
                                $yv_data['position'] = $position;
                            }
                            break;
                            
                        case 'ke_plate_number':
                            $yv_data['plateNumber'] = $identity_number;
                            break;
                            
                  
                            
                            
                            
                        case 'ng_bvn':
                        case 'ng_nin':
                            if (empty($first_name) || empty($last_name)) {
                                $error = "First name and last name are required.";
                                $validation_passed = false;
                            } else {
                                $yv_data[$yv_type === 'ng_bvn' ? 'bvn' : 'nin'] = $identity_number;
                                $yv_data['firstName'] = $first_name;
                                $yv_data['lastName'] = $last_name;
                            }
                            break;
                            
                        case 'ke_business_registry':
                            if (empty($business_name)) {
                                $error = "Business name is required.";
                                $validation_passed = false;
                            } else {
                                $yv_data['registrationNumber'] = $identity_number;
                                $yv_data['businessName'] = $business_name;
                            }
                            break;
                            
                        default:
                            // Generic verification - just send ID and names if available
                            $yv_data['identifier'] = $identity_number;
                            if ($first_name) $yv_data['firstName'] = $first_name;
                            if ($last_name) $yv_data['lastName'] = $last_name;
                    }
                    
                    // Proceed if validation passed
                    if ($validation_passed) {
                        // Call YouVerify API
                        $yv_result = youverify_api_request($yv_endpoint, $yv_data);
                        
                        if ($yv_result && $yv_result['success']) {
                            $reference_id = $yv_result['data']['id'] ?? null;
                            
                            if (!$reference_id) {
                                $error = "Verification initiated but no reference ID received.";
                                $errorIcon = 'question-circle';
                            } else {
                                try {
                                    // Store all form data
                                    $stmt = $pdo->prepare("
                                        INSERT INTO verification_requests
                                        (service, provider, national_id, first_name, last_name, full_name, dob, phone, business_name, address, price, status, yv_reference_id, result, ip_address, user_agent, created_at, updated_at)
                                        VALUES (:s, 'youverify', :nid, :fname, :lname, :fullname, :dob, :phone, :bname, :addr, :p, 'processing', :ref, :result, :ip, :ua, NOW(), NOW())
                                    ");
                                    $stmt->execute([
                                        ':s'        => $service_type,
                                        ':nid'      => $identity_number,
                                        ':fname'    => $first_name,
                                        ':lname'    => $last_name,
                                        ':fullname' => trim($first_name . ' ' . $last_name),
                                        ':dob'      => $dob ?: null,
                                        ':phone'    => $phone ?: null,
                                        ':bname'    => $business_name ?: null,
                                        ':addr'     => $address ?: null,
                                        ':p'        => service_price($service_type),
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
                            
                            if ($http_code === 401) {
                                $error = "Authentication error. Please contact support.";
                                $errorIcon = 'key';
                            } elseif ($http_code === 400) {
                                $error = "Invalid data provided. Please check your details and try again.";
                                $errorIcon = 'exclamation-circle';
                            } elseif ($http_code === 404) {
                                $error = "ID number not found in YouVerify database.";
                                $errorIcon = 'user-slash';
                            } else {
                                $error = "Verification service error. Please try again later.";
                                $errorIcon = 'server';
                            }
                            
                            error_log("YouVerify Error: HTTP $http_code - " . json_encode($error_data));
                        }
                    } else {
                        $errorIcon = 'exclamation-circle';
                    }
                }
            }
            
            // ================================================================
            // UNKNOWN PROVIDER
            // ================================================================
            else {
                $error = "Unknown verification provider. Please contact support.";
                $errorIcon = 'circle-exclamation';
            }
            
        } catch (Exception $e) {
            error_log('System Error: ' . $e->getMessage());
            $error = "System error occurred. Please try again or contact support.";
            $errorIcon = 'bug';
        }
    }
}
?>
<?php
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '.') $base_path = '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title id="pageTitle">Readiwork AI — AI-Powered Verification</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" id="pageDesc" content="AI-powered verification platform. Instant AI analysis of identity, credit, and compliance data.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
.form-control{background:var(--surface);border:1px solid var(--border);color:var(--text);padding:16px 16px 16px 48px;border-radius:14px;height:58px;font-size:1rem;}
.form-control::placeholder{color:#94a3b8;}
.form-control:focus{border-color:var(--accent);box-shadow:none;}
.form-note{font-size:.9rem;color:var(--muted);margin:18px 0 26px;}
.btn-main{background:linear-gradient(135deg,var(--primary),var(--primary-soft));border:none;padding:16px;font-weight:800;border-radius:999px;font-size:1.05rem;}
.btn-main:hover{background:linear-gradient(135deg,var(--primary-soft),var(--primary));}
.form-footer{display:flex;justify-content:space-between;margin-top:18px;font-size:.9rem;color:var(--muted);}
.form-footer i{color:var(--accent);}
.error-box{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-left:4px solid #ef4444;border-radius:14px;padding:24px;margin-top:24px;animation:slideDown 0.3s ease;}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.error-box h4{color:#fca5a5;font-weight:700;margin-bottom:12px;font-size:1.1rem;}
.error-box p{color:#fca5a5;margin-bottom:0;line-height:1.7;font-size:1rem;}
.yv-field{display:none;}
.yv-field.show{display:block;}
.provider-badge{display:inline-block;padding:6px 12px;border-radius:8px;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-left:12px;}
.provider-metropol{background:rgba(99,102,241,0.2);color:#818cf8;}
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
    <h1 id="serviceTitle"><i class="fa-solid fa-robot" style="color:var(--accent,#22c55e);margin-right:10px"></i>AI <span>Verification</span>...<span id="providerBadge" class="provider-badge"></span></h1>
    <p id="serviceDesc">Our AI engine is preparing your verification. Enter your details below for instant AI analysis.</p>
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
                        <input type="hidden" name="service" id="serviceInput" value="">
                        
                        <!-- National ID / Primary Identifier Field (always visible) -->
                        <div class="mb-4 form-group">
                            <label class="form-label" id="inputLabel">Document / ID Number</label>
                            <i id="inputIcon" class="fa-solid fa-id-card"></i>
                            <input type="text" name="national_id" id="nationalIdInput" class="form-control"
                                   value="<?= htmlspecialchars($identity_number) ?>"
                                   placeholder="e.g. 12345678" required maxlength="50">
                        </div>

                        <!-- YouVerify Additional Fields (conditionally shown) -->
                        
                        <!-- First Name -->
                        <div class="yv-field" id="yvFirstNameField">
                            <label class="form-label">First Name</label>
                            <i class="fa-solid fa-user"></i>
                            <input type="text" name="first_name" class="form-control"
                                   placeholder="e.g. John" maxlength="50">
                        </div>

                        <!-- Last Name -->
                        <div class="yv-field" id="yvLastNameField">
                            <label class="form-label">Last Name</label>
                            <i class="fa-solid fa-user"></i>
                            <input type="text" name="last_name" class="form-control"
                                   placeholder="e.g. Doe" maxlength="50">
                        </div>

                        <!-- Date of Birth -->
                        <div class="yv-field" id="yvDobField">
                            <label class="form-label">Date of Birth</label>
                            <i class="fa-solid fa-calendar"></i>
                            <input type="date" name="dob" class="form-control">
                        </div>

                        <!-- Phone Number -->
                        <div class="yv-field" id="yvPhoneField">
                            <label class="form-label">Phone Number</label>
                            <i class="fa-solid fa-phone"></i>
                            <input type="tel" name="phone" class="form-control"
                                   placeholder="e.g. 0712345678" maxlength="15">
                        </div>

                        <!-- Business Name -->
                        <div class="yv-field" id="yvBusinessNameField">
                            <label class="form-label">Business Name</label>
                            <i class="fa-solid fa-building"></i>
                            <input type="text" name="business_name" class="form-control"
                                   placeholder="e.g. Sample Ltd" maxlength="255">
                        </div>

                        <!-- Bank Code -->
                        <div class="yv-field" id="yvBankCodeField">
                            <label class="form-label">Bank Code</label>
                            <i class="fa-solid fa-building-columns"></i>
                            <select name="bank_code" class="form-control">
                                <option value="">Select Bank</option>
                                <option value="01">KCB Bank</option>
                                <option value="02">Standard Chartered</option>
                                <option value="03">Barclays Bank</option>
                                <option value="07">Co-operative Bank</option>
                                <option value="11">Equity Bank</option>
                                <option value="63">Diamond Trust Bank</option>
                                <option value="68">Family Bank</option>
                                <option value="74">I&M Bank</option>
                            </select>
                        </div>

                        <!-- Address -->
                        <div class="yv-field" id="yvAddressField">
                            <label class="form-label">Physical Address</label>
                            <i class="fa-solid fa-location-dot"></i>
                            <textarea name="address" class="form-control" rows="3" 
                                      placeholder="e.g. 123 Main Street, Nairobi"></textarea>
                        </div>

                        <!-- Employer Name -->
                        <div class="yv-field" id="yvEmployerField">
                            <label class="form-label">Employer Name</label>
                            <i class="fa-solid fa-briefcase"></i>
                            <input type="text" name="employer_name" class="form-control"
                                   placeholder="e.g. ABC Company Ltd" maxlength="255">
                        </div>

                        <!-- Position/Job Title -->
                        <div class="yv-field" id="yvPositionField">
                            <label class="form-label">Position/Job Title</label>
                            <i class="fa-solid fa-user-tie"></i>
                            <input type="text" name="position" class="form-control"
                                   placeholder="e.g. Accountant" maxlength="100">
                        </div>

                        <!-- Chassis Number (for vehicles) -->
                        <div class="yv-field" id="yvChassisField">
                            <label class="form-label">Chassis Number</label>
                            <i class="fa-solid fa-car"></i>
                            <input type="text" name="chassis_number" class="form-control"
                                   placeholder="e.g. JT123456789" maxlength="50">
                        </div>

                        <div class="form-note" id="formNote">
                            <i class="fa-solid fa-circle-info"></i>
                            Your ID is used strictly for verification purposes.
                        </div>

                        <button type="submit" class="btn btn-main w-100">
                            <span id="buttonText">Continue Secure Check</span>
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
                    <h4 id="infoTitle">What this check includes</h4>
                    <ul id="infoList" class="info-list">
                        <li>Loading...</li>
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
<script src="<?= $base_path ?>/assets/js/main.js"></script>
<script>
const services = {
  // === METROPOL IDENTITY SERVICES ===
  'id-verification': {
    title: "National ID <span>Verification</span>",
    desc: "Instant verification of an identity number or document to confirm validity and authenticity.",
    infoTitle: "What this check includes",
    infoList: ["Identity number validity check","Document authenticity confirmation","Basic identity match status","Safe verification with no score impact"],
    inputLabel: "National ID Number",
    inputPlaceholder: "e.g. 12345678",
    inputIcon: "fa-id-card",
    formNote: "Your ID is used only for verification purposes. Full privacy guaranteed.",
    buttonText: "Verify ID",
    provider: "metropol"
  },
  
  'soft-background': {
    title: "Soft <span>Background</span> Check",
    desc: "Non-invasive identity verification with no credit score impact.",
    infoTitle: "What this check includes",
    infoList: ["Identity verification","Basic background screening","No credit score impact","Privacy-focused check"],
    inputLabel: "National ID Number",
    inputIcon: "fa-user-check",
    formNote: "Non-invasive verification. Full privacy guaranteed.",
    buttonText: "Run Background Check",
    provider: "metropol"
  },
  
  'identity-check': {
    title: "Identity <span>Consistency</span> Check",
    desc: "Detect fake or altered IDs with name, phone, and address verification.",
    infoTitle: "What this check reveals",
    infoList: ["Name consistency verification","Phone number validation","Address verification","Fraud detection indicators"],
    inputLabel: "National ID Number",
    inputIcon: "fa-fingerprint",
    formNote: "Advanced identity consistency verification.",
    buttonText: "Check Identity",
    provider: "metropol"
  },
  
  // === METROPOL CRB SERVICES ===
  'crb': {
    title: "CRB <span>Status</span> Check",
    desc: "Instantly confirm whether you are listed or blacklisted with any Credit Reference Bureau in Kenya.",
    infoTitle: "What this check includes",
    infoList: ["CRB listing or blacklist status","Active and cleared defaults","Delinquency history summary","Safe enquiry (no score impact)"],
    inputLabel: "National ID Number",
    inputIcon: "fa-shield-halved",
    formNote: "Your ID is used strictly for verification purposes.",
    buttonText: "Check CRB Status",
    provider: "metropol"
  },
  
  'crb-clearance': {
    title: "CRB <span>Clearance</span> Check",
    desc: "Check if person is CRB listed - current or historical NPA.",
    infoTitle: "What this check includes",
    infoList: ["CRB listing status","Active defaults","Historical delinquency","Clearance status"],
    inputLabel: "National ID Number",
    inputIcon: "fa-clipboard-check",
    formNote: "Safe enquiry with no credit score impact.",
    buttonText: "Check Clearance",
    provider: "metropol"
  },
  
  'crb-certificate': {
    title: "CRB Status <span>Certificate</span>",
    desc: "Official CRB certificate with full identity and credit verification.",
    infoTitle: "What this certificate includes",
    infoList: ["Official CRB status","Full identity verification","Credit history summary","Digitally signed certificate"],
    inputLabel: "National ID Number",
    inputIcon: "fa-certificate",
    formNote: "Official certificate for formal applications.",
    buttonText: "Get Certificate",
    provider: "metropol"
  },
  
  'loan-defaulter': {
    title: "Loan <span>Defaulter</span> Check",
    desc: "Verify loan default status before issuing credit.",
    infoTitle: "What this check reveals",
    infoList: ["Active loan defaults","Default amounts and duration","CRB listing status","Risk classification"],
    inputLabel: "National ID Number",
    inputIcon: "fa-exclamation-triangle",
    formNote: "Check for defaults before issuing credit.",
    buttonText: "Check Defaults",
    provider: "metropol"
  },
  
  'financial-stress': {
    title: "Financial <span>Stress</span> Indicator",
    desc: "Detect financial stress and over-leverage early.",
    infoTitle: "What this indicator shows",
    infoList: ["Financial stress level","Over-leverage indicators","Default risk signals","Early warning alerts"],
    inputLabel: "National ID Number",
    inputIcon: "fa-heart-pulse",
    formNote: "Early detection of financial stress.",
    buttonText: "Check Stress Level",
    provider: "metropol"
  },
  
  'high-risk-detection': {
    title: "High-Risk <span>Borrower</span> Detection",
    desc: "Automated high-risk detection for rejection rules.",
    infoTitle: "What this detection includes",
    infoList: ["Risk classification","Default probability","Rejection triggers","Automated risk scoring"],
    inputLabel: "National ID Number",
    inputIcon: "fa-triangle-exclamation",
    formNote: "Automated risk detection.",
    buttonText: "Detect Risk",
    provider: "metropol"
  },
  
  // === METROPOL CREDIT SCORE SERVICES ===
  'credit-score': {
    title: "Credit <span>Score</span> Lookup",
    desc: "Metro score (0-900) for loans, BNPL, and hire purchase.",
    infoTitle: "What this report includes",
    infoList: ["Current Metro credit score (0-900)","Credit rating classification","Payment performance index","Score improvement insights"],
    inputLabel: "National ID Number",
    inputIcon: "fa-gauge-high",
    formNote: "View your current credit score.",
    buttonText: "Get My Score",
    provider: "metropol"
  },
  
  'loan-eligibility': {
    title: "Loan <span>Eligibility</span> Check",
    desc: "Credit score based eligibility for microloans and SACCOs.",
    infoTitle: "What this check includes",
    infoList: ["Maximum eligible loan amount","Risk classification & confidence score","Blacklist and default status","AI-powered approval recommendation"],
    inputLabel: "National ID Number",
    inputIcon: "fa-coins",
    formNote: "Assess your loan eligibility.",
    buttonText: "Check Eligibility",
    provider: "metropol"
  },
  
  'creditworthiness': {
    title: "Creditworthiness <span>Certificate</span>",
    desc: "Credit score certificate for visa, tenders, and contracts.",
    infoTitle: "What this certificate includes",
    infoList: ["Credit score and rating","Financial responsibility status","Default and delinquency history","Official digital certificate"],
    inputLabel: "National ID Number",
    inputIcon: "fa-award",
    formNote: "For visa applications, tenders, and contracts.",
    buttonText: "Get Certificate",
    provider: "metropol"
  },
  
  'financial-reputation': {
    title: "Financial <span>Reputation</span> Score",
    desc: "Credit score for trust platforms and marketplaces.",
    infoTitle: "What this report includes",
    infoList: ["Financial reputation score","Credit behavior analysis","Trust rating classification","Default risk assessment"],
    inputLabel: "National ID Number",
    inputIcon: "fa-star",
    formNote: "Build trust with financial reputation.",
    buttonText: "Get Reputation Score",
    provider: "metropol"
  },
  
  'credit-monitoring': {
    title: "Credit <span>Monitoring</span> Check",
    desc: "On-demand credit score monitoring and rechecks.",
    infoTitle: "What this check includes",
    infoList: ["Current credit score","Recent credit inquiries","New account updates","Score change tracking"],
    inputLabel: "National ID Number",
    inputIcon: "fa-eye",
    formNote: "Monitor your credit status.",
    buttonText: "Monitor Credit",
    provider: "metropol"
  },
  
  'job-credit-check': {
    title: "Job Applicant <span>Credit</span> Check",
    desc: "Credit verification for finance and cash-handling roles.",
    infoTitle: "What this check includes",
    infoList: ["Credit score and rating","Financial responsibility","Default history","Suitability assessment"],
    inputLabel: "Applicant National ID",
    inputIcon: "fa-user-tie",
    formNote: "For finance and cash-handling roles.",
    buttonText: "Check Applicant",
    provider: "metropol"
  },
  
  // === METROPOL TENANT SERVICES ===
  'tenant': {
    title: "Tenant <span>Verification</span>",
    desc: "AI-assisted tenant screening for landlords and property managers.",
    infoTitle: "What this check assesses",
    infoList: ["Previous rental payment history","Eviction records and landlord references","Credit behaviour and delinquency status","Employment and income stability signals"],
    inputLabel: "Tenant National ID Number",
    inputIcon: "fa-home",
    formNote: "Tenant ID is used for risk assessment only.",
    buttonText: "Screen Tenant",
    provider: "metropol"
  },
  
  'tenant-screening': {
    title: "Tenant <span>Screening</span>",
    desc: "Rental defaults, eviction records, and financial stability.",
    infoTitle: "What this screening includes",
    infoList: ["Rental default history","Eviction records","Credit behavior","Financial stability assessment"],
    inputLabel: "Tenant National ID",
    inputIcon: "fa-house-user",
    formNote: "Screen tenants for rental risk.",
    buttonText: "Screen Tenant",
    provider: "metropol"
  },
  
  'rent-default': {
    title: "Rent <span>Default</span> History",
    desc: "Rental payment history and default incidents.",
    infoTitle: "What this check includes",
    infoList: ["Rental payment history","Default incidents","Eviction records","Tenant reliability score"],
    inputLabel: "Tenant National ID",
    inputIcon: "fa-house-circle-xmark",
    formNote: "Check rental payment history.",
    buttonText: "Check History",
    provider: "metropol"
  },
  
  // === METROPOL EMPLOYMENT SERVICES ===
  'job': {
    title: "Job <span>Verification</span>",
    desc: "Verify employment history, job title, and professional background.",
    infoTitle: "What this verification confirms",
    infoList: ["Current and previous employment","Job title and duration verification","Reference and performance signals","Identity and qualification check"],
    inputLabel: "Applicant National ID Number",
    inputIcon: "fa-briefcase",
    formNote: "Applicant data is processed securely.",
    buttonText: "Verify Applicant",
    provider: "metropol"
  },
  
  'employment-screening': {
    title: "Employment <span>Background</span> Screening",
    desc: "Identity, employment history, and reference validation.",
    infoTitle: "What this screening includes",
    infoList: ["Identity verification","Employment history","Background check","Reference validation"],
    inputLabel: "Applicant National ID",
    inputIcon: "fa-user-shield",
    formNote: "For HR and recruitment.",
    buttonText: "Screen Applicant",
    provider: "metropol"
  },
  
  // === METROPOL CREDIT REPORT SERVICES ===
  'full-credit-history': {
    title: "Full Credit <span>History</span>",
    desc: "Complete credit history for banks and asset financing.",
    infoTitle: "What this report includes",
    infoList: ["Complete loan history","All credit accounts","Payment performance data","Comprehensive credit analysis"],
    inputLabel: "National ID Number",
    inputIcon: "fa-scroll",
    formNote: "Full credit history report.",
    buttonText: "Get Full History",
    provider: "metropol"
  },
  
  'credit-report-pdf': {
    title: "Credit Report <span>(PDF)</span>",
    desc: "Official PDF credit report for legal and audit purposes.",
    infoTitle: "What this report includes",
    infoList: ["Official PDF format","Complete credit history","Legally acceptable document","Downloadable and printable"],
    inputLabel: "National ID Number",
    inputIcon: "fa-file-pdf",
    formNote: "Official PDF credit report.",
    buttonText: "Get PDF Report",
    provider: "metropol"
  },
  
  'credit-report-json': {
    title: "Credit Report <span>(JSON)</span>",
    desc: "Machine-readable format for system integration.",
    infoTitle: "What this report includes",
    infoList: ["Structured JSON format","All credit data points","System integration ready","API-compatible output"],
    inputLabel: "National ID Number",
    inputIcon: "fa-code",
    formNote: "For system integration.",
    buttonText: "Get JSON Report",
    provider: "metropol"
  },
  
  'consumer-profile': {
    title: "Consumer Financial <span>Profile</span>",
    desc: "Complete financial profile for personal finance apps.",
    infoTitle: "What this profile includes",
    infoList: ["Financial behavior analysis","Spending and borrowing patterns","Credit utilization overview","Financial health score"],
    inputLabel: "National ID Number",
    inputIcon: "fa-user-circle",
    formNote: "Complete financial profile.",
    buttonText: "Get Profile",
    provider: "metropol"
  },
  
  'debt-exposure': {
    title: "Debt <span>Exposure</span> Analysis",
    desc: "Over-borrowing detection and debt assessment.",
    infoTitle: "What this analysis includes",
    infoList: ["Total debt exposure","Multiple loan detection","Over-borrowing indicators","Debt-to-income assessment"],
    inputLabel: "National ID Number",
    inputIcon: "fa-scale-unbalanced",
    formNote: "Detect over-borrowing risks.",
    buttonText: "Analyze Debt",
    provider: "metropol"
  },
  
  'credit-exposure': {
    title: "Credit <span>Exposure</span> Summary",
    desc: "Credit exposure for lender dashboards.",
    infoTitle: "What this summary includes",
    infoList: ["Total credit exposure","Active credit facilities","Risk concentration analysis","Portfolio quality metrics"],
    inputLabel: "National ID Number",
    inputIcon: "fa-chart-pie",
    formNote: "For lender dashboards.",
    buttonText: "Get Summary",
    provider: "metropol"
  },
  
  // === METROPOL ADVANCED SERVICES ===
  'background': {
    title: "Background <span>Check</span>",
    desc: "Personal safety screening with identity verification and risk indicators.",
    infoTitle: "What this check reveals",
    infoList: ["Identity consistency and aliases","Associated contacts and addresses","Credit and behavioural risk signals","Overall safety confidence assessment"],
    inputLabel: "National ID Number",
    inputIcon: "fa-user-shield",
    formNote: "Used for personal safety screening only.",
    buttonText: "Run Safety Check",
    provider: "metropol"
  },
  
  'guarantor-verify': {
    title: "Guarantor <span>Verification</span>",
    desc: "Enhanced check with guarantor information.",
    infoTitle: "What this verification includes",
    infoList: ["Credit score and capacity","Financial stability","Default history","Guarantor suitability"],
    inputLabel: "Guarantor National ID",
    inputIcon: "fa-handshake",
    formNote: "Verify guarantor creditworthiness.",
    buttonText: "Verify Guarantor",
    provider: "metropol"
  },
  
  'business-owner-check': {
    title: "Business Owner <span>Credit</span> Check",
    desc: "Enhanced SME check with stakeholder info.",
    infoTitle: "What this check includes",
    infoList: ["Personal credit score","Business credit behavior","Financial capacity","Lending suitability"],
    inputLabel: "Business Owner ID",
    inputIcon: "fa-building",
    formNote: "For SME lending decisions.",
    buttonText: "Check Owner",
    provider: "metropol"
  },
  
  'repeat-borrower': {
    title: "Repeat <span>Borrower</span> Assessment",
    desc: "Credit analysis for returning customers.",
    infoTitle: "What this assessment includes",
    infoList: ["Previous loan performance","Repayment behavior","Risk evolution","Repeat lending suitability"],
    inputLabel: "National ID Number",
    inputIcon: "fa-rotate",
    formNote: "For returning customers.",
    buttonText: "Assess Borrower",
    provider: "metropol"
  },
  
  'borrower-profiling': {
    title: "Borrower <span>Risk</span> Profiling",
    desc: "Full profile with identity, credit, and score history.",
    infoTitle: "What this profile includes",
    infoList: ["Risk classification","Credit behavior patterns","Default probability","Borrower risk score"],
    inputLabel: "National ID Number",
    inputIcon: "fa-user-gear",
    formNote: "For risk scoring engines.",
    buttonText: "Profile Borrower",
    provider: "metropol"
  },
  
  'fraud-prescreening': {
    title: "Fraud <span>Pre-Screening</span>",
    desc: "Identity scrub for fraud detection in fintech.",
    infoTitle: "What this screening includes",
    infoList: ["Identity validation","Fraud indicators","Risk signals","Security assessment"],
    inputLabel: "National ID Number",
    inputIcon: "fa-shield-virus",
    formNote: "Protect against fraud.",
    buttonText: "Screen for Fraud",
    provider: "metropol"
  },
  
  'enhanced-risk-report': {
    title: "Enhanced <span>Risk</span> Report",
    desc: "Comprehensive report with 12-month score trend.",
    infoTitle: "What this report includes",
    infoList: ["Advanced risk scoring","Behavioral analytics","Fraud indicators","Comprehensive credit assessment"],
    inputLabel: "National ID Number",
    inputIcon: "fa-magnifying-glass-chart",
    formNote: "High-value lending decisions.",
    buttonText: "Get Enhanced Report",
    provider: "metropol"
  },
  
  'financial-due-diligence': {
    title: "Financial <span>Due Diligence</span>",
    desc: "Complete due diligence for serious contracts.",
    infoTitle: "What this check includes",
    infoList: ["Complete financial background","Enhanced verification","Compliance screening","Risk assessment report"],
    inputLabel: "National ID Number",
    inputIcon: "fa-magnifying-glass-dollar",
    formNote: "For serious contracts and compliance.",
    buttonText: "Run Due Diligence",
    provider: "metropol"
  },
  
  // ===================================================
  // YOUVERIFY SERVICES
  // ===================================================
  
  'yv-identity': {
    title: "Identity <span>Verification</span>",
    desc: "General identity verification service.",
    infoTitle: "What this verification includes",
    infoList: ["Identity validation","Personal details confirmation","Document verification","Identity status check"],
    inputLabel: "Identity Number",
    inputPlaceholder: "e.g. 12345678",
    inputIcon: "fa-fingerprint",
    formNote: "General identity verification.",
    buttonText: "Verify Identity",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ke-id': {
    title: "Kenya ID <span>Verification</span>",
    desc: "Verify Kenyan National ID with photo match and biometric validation.",
    infoTitle: "What this verification includes",
    infoList: ["ID number validation with IPRS","Photo match verification","Biometric data validation","Real-time verification status"],
    inputLabel: "National ID Number",
    inputPlaceholder: "e.g. 12345678",
    inputIcon: "fa-id-card",
    formNote: "Enhanced verification with photo matching.",
    buttonText: "Verify ID",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ke-passport': {
    title: "Kenya Passport <span>Verification</span>",
    desc: "Verify Kenyan passport authenticity and validity.",
    infoTitle: "What this verification includes",
    infoList: ["Passport number validation","Official database check","Basic authenticity confirmation","Status: found / not found"],
    inputLabel: "Passport Number",
    inputPlaceholder: "e.g. AK0167656 or A2081731",
    inputIcon: "fa-passport",
    formNote: "Passport number only required. Names optional for enhanced matching.",
    buttonText: "Verify Passport",
    provider: "youverify",
    requiresName: false
  },
  

  'yv-ke-alien-id': {
    title: "Kenya Alien ID <span>Verification</span>",
    desc: "Verify Alien/Foreigner ID in Kenya.",
    infoTitle: "What this verification includes",
    infoList: ["Alien ID validation","Immigration records check","Personal details verification","Residence status confirmation"],
    inputLabel: "Alien ID Number",
    inputPlaceholder: "e.g. 123456789",
    inputIcon: "fa-id-card",
    formNote: "Alien ID verification with immigration database.",
    buttonText: "Verify Alien ID",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ke-bank-account': {
    title: "Kenya Bank Account <span>Verification</span>",
    desc: "Verify Kenyan bank account details and ownership.",
    infoTitle: "What this verification includes",
    infoList: ["Account number validation","Bank verification","Account holder confirmation","Account status check"],
    inputLabel: "Bank Account Number",
    inputPlaceholder: "e.g. 1234567890",
    inputIcon: "fa-building-columns",
    formNote: "Bank account verification with Kenyan banks.",
    buttonText: "Verify Account",
    provider: "youverify",
    requiresBankCode: true
  },
  
  'yv-ke-tax': {
    title: "KRA PIN <span>Verification</span>",
    desc: "Verify Kenya Revenue Authority PIN number.",
    infoTitle: "What this verification includes",
    infoList: ["KRA PIN validation","Tax compliance status","Taxpayer details verification","Registration status"],
    inputLabel: "KRA PIN Number",
    inputPlaceholder: "e.g. A012345678X",
    inputIcon: "fa-receipt",
    formNote: "KRA PIN verification with Kenya Revenue Authority.",
    buttonText: "Verify KRA PIN",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ke-address': {
    title: "Kenya Address <span>Verification</span>",
    desc: "Verify physical address in Kenya with supporting documents.",
    infoTitle: "What this verification includes",
    infoList: ["Address validation","Utility bill verification","Resident confirmation","Geographic location check"],
    inputLabel: "National ID Number",
    inputPlaceholder: "e.g. 12345678",
    inputIcon: "fa-location-dot",
    formNote: "Address verification with supporting documents.",
    buttonText: "Verify Address",
    provider: "youverify",
    requiresName: true,
    requiresAddress: true
  },
  
  'yv-ke-phone': {
    title: "Kenya Phone <span>Verification</span>",
    desc: "Verify Kenyan phone number ownership and validity.",
    infoTitle: "What this verification includes",
    infoList: ["Phone number validation","Ownership verification","Carrier information","Registration status"],
    inputLabel: "National ID Number",
    inputPlaceholder: "e.g. 12345678",
    inputIcon: "fa-mobile-screen",
    formNote: "Phone verification with telecom providers.",
    buttonText: "Verify Phone",
    provider: "youverify",
    requiresPhone: true
  },
  
  'yv-ke-employment': {
    title: "Kenya Employment <span>Verification</span>",
    desc: "Verify employment status and history in Kenya.",
    infoTitle: "What this verification includes",
    infoList: ["Employment status check","Employer verification","Job title confirmation","Employment duration"],
    inputLabel: "National ID Number",
    inputPlaceholder: "e.g. 12345678",
    inputIcon: "fa-briefcase",
    formNote: "Employment verification with employer records.",
    buttonText: "Verify Employment",
    provider: "youverify",
    requiresName: true,
    requiresEmployment: true
  },
  
  'yv-ke-plate-number': {
    title: "Kenya Vehicle Plate <span>Verification</span>",
    desc: "Verify vehicle registration with NTSA.",
    infoTitle: "What this verification includes",
    infoList: ["Plate number validation","NTSA registration check","Vehicle ownership details","Registration status"],
    inputLabel: "Vehicle Plate Number",
    inputPlaceholder: "e.g. KAA 123X",
    inputIcon: "fa-car",
    formNote: "Vehicle verification with NTSA database.",
    buttonText: "Verify Vehicle",
    provider: "youverify"
  },
  

  
  'yv-ng-bvn': {
    title: "Nigeria BVN <span>Verification</span>",
    desc: "Verify Nigerian Bank Verification Number.",
    infoTitle: "What this verification includes",
    infoList: ["BVN validation with NIBSS","Personal details confirmation","Photo match verification","Bank account linkage"],
    inputLabel: "BVN Number",
    inputPlaceholder: "e.g. 12345678901",
    inputIcon: "fa-building-columns",
    formNote: "BVN verification with Nigerian banking system.",
    buttonText: "Verify BVN",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ng-nin': {
    title: "Nigeria NIN <span>Verification</span>",
    desc: "Verify Nigerian National Identification Number.",
    infoTitle: "What this verification includes",
    infoList: ["NIN validation with NIMC","Personal details verification","Biometric data check","Identity document status"],
    inputLabel: "NIN Number",
    inputPlaceholder: "e.g. 12345678901",
    inputIcon: "fa-id-card",
    formNote: "NIN verification with NIMC database.",
    buttonText: "Verify NIN",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-gh-drivers-license': {
    title: "Ghana Drivers License <span>Verification</span>",
    desc: "Verify Ghana driving license.",
    infoTitle: "What this verification includes",
    infoList: ["License validation","DVLA database check","Driver information","License status"],
    inputLabel: "Ghana License Number",
    inputPlaceholder: "e.g. G1234567",
    inputIcon: "fa-id-card-clip",
    formNote: "Ghana drivers license verification.",
    buttonText: "Verify License",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-gh-voters-id': {
    title: "Ghana Voters ID <span>Verification</span>",
    desc: "Verify Ghana Electoral Commission voters ID.",
    infoTitle: "What this verification includes",
    infoList: ["Voters ID validation","Electoral register check","Voter details verification","Registration status"],
    inputLabel: "Voters ID Number",
    inputPlaceholder: "e.g. 123456789",
    inputIcon: "fa-id-card",
    formNote: "Ghana voters ID verification.",
    buttonText: "Verify Voters ID",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-za-id': {
    title: "South Africa ID <span>Verification</span>",
    desc: "Verify South African national ID.",
    infoTitle: "What this verification includes",
    infoList: ["ID number validation","Home Affairs database check","Personal details verification","ID document status"],
    inputLabel: "South African ID Number",
    inputPlaceholder: "e.g. 9001010001088",
    inputIcon: "fa-id-card",
    formNote: "South African ID verification.",
    buttonText: "Verify ID",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ug-nin': {
    title: "Uganda NIN <span>Verification</span>",
    desc: "Verify Uganda National Identification Number.",
    infoTitle: "What this verification includes",
    infoList: ["NIN validation","NIRA database check","Personal details verification","ID status"],
    inputLabel: "Uganda NIN",
    inputPlaceholder: "e.g. CM12345678ABC",
    inputIcon: "fa-id-card",
    formNote: "Uganda NIN verification.",
    buttonText: "Verify NIN",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-tz-nin': {
    title: "Tanzania NIN <span>Verification</span>",
    desc: "Verify Tanzania National Identification Number.",
    infoTitle: "What this verification includes",
    infoList: ["NIN validation","NIDA database check","Personal details verification","ID status"],
    inputLabel: "Tanzania NIN",
    inputPlaceholder: "e.g. 12345678901234567890",
    inputIcon: "fa-id-card",
    formNote: "Tanzania NIN verification.",
    buttonText: "Verify NIN",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-rw-nin': {
    title: "Rwanda NIN <span>Verification</span>",
    desc: "Verify Rwanda National Identification Number.",
    infoTitle: "What this verification includes",
    infoList: ["NIN validation","NIDA database check","Personal details verification","ID status"],
    inputLabel: "Rwanda NIN",
    inputPlaceholder: "e.g. 1234567890123456",
    inputIcon: "fa-id-card",
    formNote: "Rwanda NIN verification.",
    buttonText: "Verify NIN",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ke-business': {
    title: "Kenya Business <span>Verification</span>",
    desc: "Verify business registration with BRS Kenya.",
    infoTitle: "What this verification includes",
    infoList: ["Business registration validation","Company directors verification","Registration status check","Business details confirmation"],
    inputLabel: "Business Registration Number",
    inputPlaceholder: "e.g. PVT-1234567890",
    inputIcon: "fa-building",
    formNote: "Business verification with BRS Kenya.",
    buttonText: "Verify Business",
    provider: "youverify",
    requiresBusinessName: true
  },
  
  'yv-liveness': {
    title: "Biometric <span>Liveness</span> Check",
    desc: "Anti-spoofing face liveness detection for enhanced security.",
    infoTitle: "What this check includes",
    infoList: ["Live face detection","Anti-spoofing verification","Biometric matching","Real-time liveness assessment"],
    inputLabel: "National ID Number",
    inputPlaceholder: "e.g. 12345678",
    inputIcon: "fa-face-smile",
    formNote: "Advanced biometric liveness detection.",
    buttonText: "Check Liveness",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-document': {
    title: "Document <span>Verification</span>",
    desc: "AI-powered document authenticity check for any document type.",
    infoTitle: "What this check includes",
    infoList: ["Document authenticity analysis","AI-powered forgery detection","Data extraction and validation","Tamper detection"],
    inputLabel: "Document Reference Number",
    inputPlaceholder: "e.g. DOC12345678",
    inputIcon: "fa-file-invoice",
    formNote: "AI-powered document verification.",
    buttonText: "Verify Document",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-sanctions-pep': {
    title: "Sanctions & PEP <span>Screening</span>",
    desc: "Screen against global sanctions and PEP lists.",
    infoTitle: "What this screening includes",
    infoList: ["Global sanctions database check","PEP (Politically Exposed Person) screening","Watchlist verification","Compliance reporting"],
    inputLabel: "Full Name",
    inputPlaceholder: "e.g. John Doe",
    inputIcon: "fa-user-shield",
    formNote: "Comprehensive sanctions and PEP screening.",
    buttonText: "Screen Person",
    provider: "youverify",
    requiresName: true,
    requiresDOB: true
  },
  
  'yv-adverse-media': {
    title: "Adverse Media <span>Screening</span>",
    desc: "Screen for negative news and media mentions.",
    infoTitle: "What this screening includes",
    infoList: ["Global news database scan","Negative media mentions","Risk indicators","Reputation assessment"],
    inputLabel: "Full Name",
    inputPlaceholder: "e.g. John Doe",
    inputIcon: "fa-newspaper",
    formNote: "Screen for negative media coverage.",
    buttonText: "Screen Media",
    provider: "youverify",
    requiresName: true
  }
};

// Get service from URL
const urlParams = new URLSearchParams(window.location.search);
const serviceKey = urlParams.get('service') || 'id-verification';
const service = services[serviceKey] || services['id-verification'];

// Update page content
document.getElementById('pageTitle').textContent = "AI " + service.title.replace(/<[^>]*>/g, '') + " | Readiwork AI";
document.getElementById('pageDesc').content = "AI-powered " + service.desc.replace(/<[^>]*>/g, '');
document.querySelector('.page-hero h1').innerHTML = '<i class="fa-solid fa-robot" style="color:var(--accent,#22c55e);margin-right:10px;font-size:0.7em"></i>AI ' + service.title;
document.querySelector('.page-hero p').innerHTML = 'Our AI engine will ' + service.desc.charAt(0).toLowerCase() + service.desc.slice(1);
document.getElementById('infoTitle').textContent = service.infoTitle;
document.getElementById('serviceInput').value = serviceKey;

// Update provider badge
const providerBadge = document.getElementById('providerBadge');
if (service.provider === 'youverify') {
  providerBadge.textContent = 'AI + YouVerify';
  providerBadge.className = 'provider-badge provider-youverify';
} else {
  providerBadge.textContent = 'AI + Metropol';
  providerBadge.className = 'provider-badge provider-metropol';
}

// Update info list
const list = document.getElementById('infoList');
list.innerHTML = '';
service.infoList.forEach(item => {
  const li = document.createElement('li');
  li.innerHTML = '<i class="fa-solid fa-check"></i> ' + item;
  list.appendChild(li);
});

// Update form fields
document.getElementById('inputLabel').textContent = service.inputLabel;
document.getElementById('inputIcon').className = 'fa-solid ' + service.inputIcon;
document.getElementById('formNote').innerHTML = '<i class="fa-solid fa-circle-info"></i> ' + service.formNote;
document.getElementById('buttonText').textContent = service.buttonText;

// Update input placeholder
const inputField = document.getElementById('nationalIdInput');
if (service.inputPlaceholder) {
  inputField.placeholder = service.inputPlaceholder;
} else {
  inputField.placeholder = 'e.g. 12345678';
}

// Update input validation pattern based on service
inputField.removeAttribute('pattern');
inputField.removeAttribute('maxlength');

// Set appropriate validation for different input types
if (serviceKey.includes('passport')) {
  inputField.setAttribute('maxlength', '20');
} else if (serviceKey.includes('plate') || serviceKey.includes('vehicle')) {
  inputField.setAttribute('maxlength', '15');
  inputField.setAttribute('pattern', '[A-Z0-9\\s]+');
} else if (serviceKey.includes('bvn') || serviceKey.includes('nin')) {
  inputField.setAttribute('maxlength', '20');
} else if (serviceKey.includes('kra') || serviceKey === 'yv-ke-tax') {
  inputField.setAttribute('maxlength', '15');
} else {
  inputField.setAttribute('maxlength', '50');
}

// Show/hide YouVerify fields based on service requirements
const yvFields = document.querySelectorAll('.yv-field');
yvFields.forEach(field => {
  field.classList.remove('show');
  const input = field.querySelector('input, select, textarea');
  if (input) input.required = false;
});

if (service.provider === 'youverify') {
  // Name fields
  if (service.requiresName) {
    document.getElementById('yvFirstNameField').classList.add('show');
    document.getElementById('yvLastNameField').classList.add('show');
    document.querySelector('[name="first_name"]').required = true;
    document.querySelector('[name="last_name"]').required = true;
  }
  
  // Date of Birth
  if (service.requiresDOB) {
    document.getElementById('yvDobField').classList.add('show');
    document.querySelector('[name="dob"]').required = true;
  }
  
  // Phone
  if (service.requiresPhone) {
    document.getElementById('yvPhoneField').classList.add('show');
    document.querySelector('[name="phone"]').required = true;
  }
  
  // Business Name
  if (service.requiresBusinessName) {
    document.getElementById('yvBusinessNameField').classList.add('show');
    document.querySelector('[name="business_name"]').required = true;
  }
  
  // Bank Code
  if (service.requiresBankCode) {
    document.getElementById('yvBankCodeField').classList.add('show');
    document.querySelector('[name="bank_code"]').required = true;
  }
  
  // Address
  if (service.requiresAddress) {
    document.getElementById('yvAddressField').classList.add('show');
    document.querySelector('[name="address"]').required = true;
  }
  
  // Employment fields
  if (service.requiresEmployment) {
    document.getElementById('yvEmployerField').classList.add('show');
    document.getElementById('yvPositionField').classList.add('show');
    document.querySelector('[name="employer_name"]').required = true;
    document.querySelector('[name="position"]').required = true;
  }
  
  // Chassis Number
  if (service.requiresChassis) {
    document.getElementById('yvChassisField').classList.add('show');
    document.querySelector('[name="chassis_number"]').required = true;
  }
}

// Update active dropdown item
document.querySelectorAll('.dropdown-item').forEach(item => {
  item.classList.remove('active');
  if (item.getAttribute('href').includes('service=' + serviceKey)) {
    item.classList.add('active');
  }
});
</script>
</body>
</html>