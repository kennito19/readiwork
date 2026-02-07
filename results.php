<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';

/**
 * Decide which sections to show based on provider and service
 */
function get_visible_sections(string $provider, string $category, string $serviceKey = ''): array {
    $sections = [
        'identity'         => false,
        'passport_info'    => false,
        'license_info'     => false,
        'vehicle_info'     => false,
        'delinquency'      => false,
        'score'            => false,
        'accounts'         => false,
        'scrub'            => false,
        'guarantors'       => false,
        'trend'            => false,
        'metrics'          => false,
        'loan_eligibility' => false,
    ];
    
    // YouVerify services - simpler display
    if ($provider === 'youverify') {
        $sections['identity'] = true;
        
        if ($serviceKey === 'yv-ke-passport') {
            $sections['passport_info'] = true;
        }
        
        if ($serviceKey === 'yv-ke-drivers-license') {
            $sections['license_info'] = true;
        }
        
        if ($serviceKey === 'yv-ke-plate-number') {
            $sections['vehicle_info'] = true;
        }
        
        return $sections;
    }
    
    // Metropol services - full logic (keep existing)
    switch ($category) {
        case 'identity':
            $sections['identity'] = true;
            break;
            
        case 'scrub':
            $sections['identity'] = true;
            $sections['scrub'] = true;
            break;
            
        case 'crb':
            $sections['identity'] = true;
            $sections['delinquency'] = true;
            break;
            
        case 'score':
            $sections['identity'] = true;
            $sections['score'] = true;
            $sections['delinquency'] = true;
            break;
            
        case 'credit_info':
            $sections['identity'] = true;
            $sections['accounts'] = true;
            $sections['delinquency'] = true;
            $sections['metrics'] = true;
            break;
            
        case 'enhanced':
        case 'full_enhanced':
            $sections['identity'] = true;
            $sections['accounts'] = true;
            $sections['delinquency'] = true;
            $sections['guarantors'] = true;
            $sections['metrics'] = true;
            $sections['score'] = true;
            break;
            
        case 'json_report':
        case 'full_json':
            $sections['identity'] = true;
            $sections['accounts'] = true;
            $sections['delinquency'] = true;
            $sections['scrub'] = true;
            $sections['score'] = true;
            $sections['metrics'] = true;
            $sections['guarantors'] = true;
            break;
            
        case 'pdf_report':
            $sections['identity'] = true;
            $sections['score'] = true;
            $sections['delinquency'] = true;
            $sections['accounts'] = true;
            break;
    }

    if ($serviceKey === 'loan-eligibility') {
        $sections['loan_eligibility'] = true;
        $sections['identity'] = true;
        $sections['score'] = true;
        $sections['delinquency'] = true;
    }
    
    if (in_array($serviceKey, ['identity-check', 'fraud-prescreening', 'employment-screening', 'background'])) {
        $sections['scrub'] = true;
        $sections['identity'] = true;
    }
    
    if (in_array($serviceKey, ['full-credit-history', 'debt-exposure', 'credit-exposure', 'repeat-borrower'])) {
        $sections['accounts'] = true;
        $sections['metrics'] = true;
    }

    return $sections;
}

$rid = $_GET['rid'] ?? null;
if (!$rid || !ctype_digit($rid)) {
    die('Invalid request ID. <a href="index.php">Go back</a>');
}

$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

try {
    $stmt = $pdo->prepare("SELECT * FROM verification_requests WHERE id = :rid");
    $stmt->execute([':rid' => $rid]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
   
    if (!$request) die('Request not found. <a href="index.php">Go back</a>');
    if ($request['status'] !== 'paid' && $request['status'] !== 'completed') {
        die('Payment not completed. <a href="payment.php?rid=' . $rid . '">Complete payment</a>');
    }
   
    $combinedResults = json_decode($request['result'], true);
    if (!$combinedResults || !is_array($combinedResults)) {
        die('No verification data available.');
    }
   
    $id = $request['national_id'];
    $serviceKey = $request['service'];
    $provider = $request['provider'] ?? 'metropol';
    $serviceConfig = get_service_config($serviceKey);
    $serviceCategory = $serviceConfig['category'] ?? 'identity';

    $show = get_visible_sections($provider, $serviceCategory, $serviceKey);

} catch (Exception $e) {
    error_log('Results page error: ' . $e->getMessage());
    die('Error loading results.');
}

/* ======================================================
 * INITIALIZE ALL DATA CONTAINERS
 * ====================================================== */
$identity = [];
$fullName = '';
$passportData = null;
$licenseData = null;
$vehicleData = null;
$creditScore = null;
$scoreCategory = null;
$scoreDate = null;
$delinquency = ['code' => '003', 'summary' => 'No delinquency', 'loan_amount' => 0];
$accounts = [];
$scrubData = null;
$phones = [];
$emails = [];
$addresses = [];
$employment = [];
$guarantors = [];
$stakeholders = [];
$bouncedCheques = null;
$enquiries = null;
$lenderSector = null;
$scoreTrend = [];

/* ======================================================
 * YOUVERIFY DATA EXTRACTION
 * ====================================================== */
if ($provider === 'youverify') {
    // YouVerify wraps everything in { success, data { data { ... } } }
    $yvResponse = $combinedResults['data'] ?? $combinedResults;
    $yvData = $yvResponse['data'] ?? $yvResponse;
    
    if ($serviceKey === 'yv-ke-passport') {
        $passportData = $yvData;
        
        $identity = [
            'first_name' => $yvData['firstName'] ?? 'N/A',
            'middle_name' => $yvData['middleName'] ?? '',
            'last_name' => $yvData['lastName'] ?? 'N/A',
            'identity_number' => $yvData['idNumber'] ?? $id,
            'dob' => $yvData['dateOfBirth'] ?? 'N/A',
            'gender' => $yvData['gender'] ?? 'N/A',
            'citizenship' => $yvData['nationality'] ?? 'N/A',
            'verification_status' => $yvData['status'] ?? 'unknown',
            'verification_id' => $yvData['id'] ?? '',
            'verification_date' => $yvData['requestedAt'] ?? '',
            'all_validation_passed' => $yvData['allValidationPassed'] ?? false,
        ];
        
        $fullName = $yvData['fullName'] ?? trim(($identity['first_name'] ?? '') . ' ' . ($identity['middle_name'] ?? '') . ' ' . ($identity['last_name'] ?? ''));
    } 
    elseif ($serviceKey === 'yv-ke-drivers-license') {
        $licenseData = $yvData;
        
        $identity = [
            'first_name' => 'N/A', // License doesn't return separate names
            'middle_name' => '',
            'last_name' => 'N/A',
            'identity_number' => $yvData['nationalId'] ?? 'N/A',
            'license_number' => $yvData['licenseNumber'] ?? $id,
            'interim_number' => $yvData['interimNumber'] ?? '',
            'dob' => $yvData['dateOfBirth'] ?? 'N/A',
            'gender' => $yvData['gender'] ?? 'N/A',
            'citizenship' => 'Kenyan',
            'verification_status' => $yvData['status'] ?? 'unknown',
            'verification_id' => $yvData['id'] ?? '',
            'verification_date' => $yvData['requestedAt'] ?? '',
            'all_validation_passed' => $yvData['allValidationPassed'] ?? false,
        ];
        
        $fullName = $yvData['fullName'] ?? 'N/A';
        
        // Extract phone if available
        if (isset($yvData['mobile']) && $yvData['mobile']) {
            $phones[] = $yvData['mobile'];
        }
        
        // Extract email if available
        if (isset($yvData['email']) && $yvData['email']) {
            $emails[] = $yvData['email'];
        }
        
        // Extract address if available
        if (isset($yvData['address']) && is_array($yvData['address'])) {
            $addrData = $yvData['address'];
            $addresses[] = [
                'type' => 'Physical',
                'address' => $addrData['addressLine'] ?? '',
                'town' => $addrData['city'] ?? $addrData['town'] ?? '',
                'state' => $addrData['state'] ?? '',
                'country' => 'KENYA'
            ];
        }
    }
    elseif ($serviceKey === 'yv-ke-plate-number') {
        $vehicleData = $yvData;
        $ownerData = $yvData['owner'] ?? [];
        
        $identity = [
            'first_name' => 'N/A',
            'middle_name' => '',
            'last_name' => 'N/A',
            'identity_number' => $ownerData['idNumber'] ?? 'N/A',
            'plate_number' => $yvData['plateNumber'] ?? $id,
            'citizenship' => 'Kenyan',
            'verification_status' => $yvData['status'] ?? 'unknown',
            'verification_id' => $yvData['id'] ?? '',
            'verification_date' => $yvData['requestedAt'] ?? '',
            'all_validation_passed' => $yvData['allValidationPassed'] ?? false,
        ];
        
        $fullName = $ownerData['firstName'] ?? 'N/A';
        
        // Extract phone if available
        if (isset($ownerData['phone']) && $ownerData['phone']) {
            $phones[] = $ownerData['phone'];
        }
        
        // Extract address if available
        if (isset($ownerData['address']) && is_array($ownerData['address'])) {
            $addrData = $ownerData['address'];
            $addresses[] = [
                'type' => 'Postal',
                'address' => 'P.O. Box ' . ($addrData['postalAddress'] ?? ''),
                'town' => $addrData['town'] ?? '',
                'code' => $addrData['postalCode'] ?? '',
                'country' => 'KENYA'
            ];
        }
    } 
    else {
        // Other YouVerify services
        $identity = [
            'first_name' => $yvData['firstName'] ?? 'N/A',
            'middle_name' => $yvData['middleName'] ?? '',
            'last_name' => $yvData['lastName'] ?? 'N/A',
            'identity_number' => $yvData['idNumber'] ?? $yvData['id'] ?? $id,
            'dob' => $yvData['dateOfBirth'] ?? 'N/A',
            'gender' => $yvData['gender'] ?? 'N/A',
            'citizenship' => $yvData['nationality'] ?? 'N/A',
            'verification_status' => $yvData['status'] ?? 'unknown',
        ];
        
        $fullName = $yvData['fullName'] ?? trim(($identity['first_name'] ?? '') . ' ' . ($identity['middle_name'] ?? '') . ' ' . ($identity['last_name'] ?? ''));
    }
}

/* ======================================================
 * METROPOL DATA EXTRACTION (keep existing logic)
 * ====================================================== */
else {
    foreach ($combinedResults as $endpoint => $responseData) {
        if (!is_array($responseData)) continue;
        
        // === IDENTITY DATA ===
        if ($endpoint === '/identity/verify' || strpos($endpoint, 'verify') !== false) {
            if (isset($responseData['first_name']) || isset($responseData['names']) || isset($responseData['id_number'])) {
                $identity = array_merge($identity, [
                    'first_name' => $responseData['first_name'] ?? $responseData['names'] ?? '',
                    'other_name' => $responseData['other_name'] ?? $responseData['other_names'] ?? '',
                    'surname' => $responseData['surname'] ?? $responseData['last_name'] ?? '',
                    'last_name' => $responseData['last_name'] ?? $responseData['surname'] ?? '',
                    'identity_number' => $responseData['identity_number'] ?? $responseData['id_number'] ?? $id,
                    'dob' => $responseData['dob'] ?? $responseData['date_of_birth'] ?? '',
                    'gender' => $responseData['gender'] ?? $responseData['sex'] ?? '',
                    'citizenship' => $responseData['citizenship'] ?? 'Kenyan',
                    'serial_number' => $responseData['serial_number'] ?? '',
                ]);
            }
        }
        
        // === DELINQUENCY DATA ===
        if ($endpoint === '/delinquency/status' || strpos($endpoint, 'delinquency') !== false) {
            if (isset($responseData['delinquency_code']) || isset($responseData['status_code'])) {
                $delinquency = [
                    'code' => $responseData['delinquency_code'] ?? $responseData['status_code'] ?? '003',
                    'summary' => $responseData['delinquency_summary'] ?? $responseData['status_description'] ?? 'No delinquency',
                    'loan_amount' => $responseData['loan_amount'] ?? 0,
                ];
            }
        }
        
        // === CREDIT SCORE DATA ===
        if ($endpoint === '/score/consumer' || strpos($endpoint, 'score') !== false) {
            if (isset($responseData['credit_score']) || isset($responseData['metro_score'])) {
                $creditScore = intval($responseData['credit_score'] ?? $responseData['metro_score'] ?? 0);
                $scoreCategory = $responseData['category'] ?? $responseData['score_band'] ?? 
                                ($creditScore >= 700 ? 'Excellent' : ($creditScore >= 600 ? 'Good' : ($creditScore >= 500 ? 'Fair' : 'Poor')));
                $scoreDate = $responseData['as_at'] ?? $responseData['score_date'] ?? date('Y-m-d');
            }
            
            if (isset($responseData['metro_score_trend']) && is_array($responseData['metro_score_trend'])) {
                $scoreTrend = $responseData['metro_score_trend'];
                
                if (!$creditScore && !empty($scoreTrend)) {
                    $latestScore = end($scoreTrend);
                    $creditScore = intval($latestScore['credit_score'] ?? 0);
                    $scoreDate = $latestScore['month'] ?? date('Y-m-d');
                }
            }
        }
        
        // === CREDIT INFO DATA ===
        if (strpos($endpoint, 'credit_info') !== false) {
            if (isset($responseData['account_info']) && is_array($responseData['account_info'])) {
                $accounts = array_merge($accounts, $responseData['account_info']);
            }
            
            if (isset($responseData['guarantors']) && is_array($responseData['guarantors'])) {
                $guarantors = array_merge($guarantors, $responseData['guarantors']);
            }
            
            if (isset($responseData['stakeholders']) && is_array($responseData['stakeholders'])) {
                $stakeholders = array_merge($stakeholders, $responseData['stakeholders']);
            }
            
            if (isset($responseData['no_of_bounced_cheques'])) {
                $bouncedCheques = $responseData['no_of_bounced_cheques'];
            }
            if (isset($responseData['no_of_enquiries'])) {
                $enquiries = $responseData['no_of_enquiries'];
            }
            if (isset($responseData['lender_sector'])) {
                $lenderSector = $responseData['lender_sector'];
            }
        }
        
        // === JSON REPORT DATA ===
        if (strpos($endpoint, '/report/json') !== false) {
            if (isset($responseData['personal_profile'])) {
                $profile = $responseData['personal_profile'];
                $identity = array_merge($identity, [
                    'first_name' => $profile['first_name'] ?? '',
                    'other_name' => $profile['other_name'] ?? '',
                    'surname' => $profile['surname'] ?? '',
                    'last_name' => $profile['surname'] ?? '',
                    'identity_number' => $profile['identity_number'] ?? $id,
                    'dob' => $profile['dob'] ?? '',
                    'gender' => $profile['gender'] ?? '',
                    'citizenship' => $profile['citizenship'] ?? 'Kenyan',
                    'serial_number' => $profile['serial_number'] ?? '',
                ]);
            }
            
            if (isset($responseData['credit_accounts']) && is_array($responseData['credit_accounts'])) {
                $accounts = array_merge($accounts, $responseData['credit_accounts']);
            }
            
            if (isset($responseData['credit_score'])) {
                $creditScore = $creditScore ?? intval($responseData['credit_score']);
                $scoreDate = $scoreDate ?? ($responseData['score_date'] ?? date('Y-m-d'));
            }
        }
    }

    // Fill identity defaults for Metropol
    if ($provider === 'metropol') {
        $identity['first_name'] = $identity['first_name'] ?? 'N/A';
        $identity['other_name'] = $identity['other_name'] ?? '';
        $identity['last_name'] = $identity['last_name'] ?? $identity['surname'] ?? 'N/A';
        $identity['identity_number'] = $identity['identity_number'] ?? $id;
        $identity['dob'] = $identity['dob'] ?? 'N/A';
        $identity['gender'] = $identity['gender'] ?? 'N/A';
        $identity['citizenship'] = $identity['citizenship'] ?? 'Kenyan';
        $identity['serial_number'] = $identity['serial_number'] ?? 'N/A';

        $fullName = trim(($identity['first_name'] ?? '') . ' ' . ($identity['other_name'] ?? '') . ' ' . ($identity['last_name'] ?? ''));

        // Extract scrub data
        foreach ($combinedResults as $endpoint => $responseData) {
            if (!is_array($responseData)) continue;
            
            if ($endpoint === '/identity/scrub' || strpos($endpoint, 'scrub') !== false) {
                $hasActualScrubData = false;
                
                $errorCodes = ['E017', 'E001', '001', 'E018'];
                $apiCode = $responseData['api_code'] ?? '';
                
                if (!in_array($apiCode, $errorCodes)) {
                    if (!empty($responseData['phone']) || !empty($responseData['email']) || 
                        !empty($responseData['physical_address']) || !empty($responseData['postal_address']) ||
                        !empty($responseData['employment']) || !empty($responseData['names']) ||
                        !empty($responseData['date_of_being'])) {
                        $hasActualScrubData = true;
                    }
                }
                
                if ($hasActualScrubData) {
                    $scrubData = $responseData;
                    
                    if (isset($responseData['names']) && is_array($responseData['names'])) {
                        foreach ($responseData['names'] as $name) {
                            if (!empty($name) && $name !== $fullName && $name !== $identity['first_name']) {
                                $scrubData['additional_names'][] = $name;
                            }
                        }
                    }
                    
                    if (isset($responseData['phone']) && is_array($responseData['phone'])) {
                        $phones = array_merge($phones, $responseData['phone']);
                    }
                    
                    if (isset($responseData['email']) && is_array($responseData['email'])) {
                        $emails = array_merge($emails, $responseData['email']);
                    }
                    
                    if (isset($responseData['postal_address']) && is_array($responseData['postal_address'])) {
                        foreach ($responseData['postal_address'] as $addr) {
                            $addresses[] = [
                                'type' => 'Postal',
                                'town' => $addr['town'] ?? '',
                                'number' => $addr['number'] ?? '',
                                'code' => $addr['code'] ?? '',
                                'country' => $addr['country'] ?? 'KENYA'
                            ];
                        }
                    }
                    
                    if (isset($responseData['physical_address']) && is_array($responseData['physical_address'])) {
                        foreach ($responseData['physical_address'] as $addr) {
                            $addresses[] = [
                                'type' => 'Physical',
                                'town' => $addr['town'] ?? $addr['address'] ?? '',
                                'address' => $addr['address'] ?? $addr['town'] ?? '',
                                'country' => $addr['country'] ?? 'KENYA'
                            ];
                        }
                    }
                    
                    if (isset($responseData['employment']) && is_array($responseData['employment'])) {
                        $employment = array_merge($employment, $responseData['employment']);
                    }
                    
                    if (!empty($responseData['gender']) && is_array($responseData['gender'])) {
                        $scrubData['gender_records'] = $responseData['gender'];
                    }
                    if (!empty($responseData['date_of_being']) && is_array($responseData['date_of_being'])) {
                        $scrubData['dob_records'] = $responseData['date_of_being'];
                    }
                }
            }
        }
    }
}

$phones = array_unique($phones);
$emails = array_unique($emails);

// Process accounts (Metropol only)
$totalAccounts = count($accounts);
$activeAccounts = 0;
$npaAccounts = 0;
$totalBalance = 0;
$totalOverdue = 0;

foreach ($accounts as $account) {
    if (in_array($account['account_status'] ?? '', ['Active', 'A', 'ACTIVE'])) $activeAccounts++;
    if (in_array($account['delinquency_code'] ?? '', ['004', '005'])) $npaAccounts++;
    $totalBalance += floatval($account['current_balance'] ?? 0);
    $totalOverdue += floatval($account['overdue_balance'] ?? $account['amount_in_arrears'] ?? 0);
}

// Delinquency status mapping
$delinquencyStatus = [
    '001' => ['title' => 'ID Not Found', 'class' => 'danger', 'message' => 'Identity not found in database'],
    '002' => ['title' => 'No Credit Info', 'class' => 'warning', 'message' => 'No credit accounts found'],
    '003' => ['title' => 'CLEAR', 'class' => 'success', 'message' => 'No current or historical delinquency'],
    '004' => ['title' => 'CURRENTLY DELINQUENT', 'class' => 'danger', 'message' => 'Has at least one current NPA'],
    '005' => ['title' => 'HISTORICAL DELINQUENCY', 'class' => 'warning', 'message' => 'Has historical NPA (now cleared)'],
];

$delStatus = $delinquencyStatus[$delinquency['code']] ?? $delinquencyStatus['003'];

// Determine overall status
$statusClass = 'success';
$statusIcon = 'fa-circle-check';
$statusTitle = 'VERIFIED';
$statusMessage = 'Verification completed successfully';

if ($provider === 'youverify') {
    if ($serviceKey === 'yv-ke-drivers-license') {
        if ($identity['verification_status'] === 'found') {
            $statusClass = 'success';
            $statusTitle = 'DRIVER\'S LICENSE VERIFIED';
            $statusMessage = 'License found in official NTSA records';
            $statusIcon = 'fa-id-card';
        } else {
            $statusClass = 'warning';
            $statusTitle = 'VERIFICATION INCOMPLETE';
            $statusMessage = 'Status: ' . ($identity['verification_status'] ?? 'Unknown');
            $statusIcon = 'fa-circle-question';
        }
    } elseif ($serviceKey === 'yv-ke-plate-number') {
        if ($identity['verification_status'] === 'found') {
            $statusClass = 'success';
            $statusTitle = 'VEHICLE VERIFIED';
            $statusMessage = 'Vehicle found in official NTSA records';
            $statusIcon = 'fa-car';
        } else {
            $statusClass = 'warning';
            $statusTitle = 'VERIFICATION INCOMPLETE';
            $statusMessage = 'Status: ' . ($identity['verification_status'] ?? 'Unknown');
            $statusIcon = 'fa-circle-question';
        }
    } elseif ($identity['verification_status'] === 'found') {
        $statusClass = 'success';
        $statusTitle = 'PASSPORT VERIFIED';
        $statusMessage = 'Passport found in official records';
        $statusIcon = 'fa-passport';
    } else {
        $statusClass = 'warning';
        $statusTitle = 'VERIFICATION INCOMPLETE';
        $statusMessage = 'Status: ' . ($identity['verification_status'] ?? 'Unknown');
        $statusIcon = 'fa-circle-question';
    }
} else {
    // Metropol status logic
    if ($serviceCategory === 'crb') {
        $statusClass = $delStatus['class'];
        $statusTitle = $delStatus['title'];
        $statusMessage = $delStatus['message'];
        $statusIcon = $statusClass === 'success' ? 'fa-shield-check' : 'fa-triangle-exclamation';
    } elseif ($serviceCategory === 'score' && $creditScore) {
        $statusClass = $creditScore >= 700 ? 'success' : ($creditScore >= 600 ? 'warning' : 'danger');
        $statusTitle = $creditScore >= 700 ? 'EXCELLENT' : ($creditScore >= 600 ? 'GOOD' : ($creditScore >= 500 ? 'FAIR' : 'POOR'));
        $statusMessage = "Metro Score: $creditScore/900";
        $statusIcon = 'fa-chart-line';
    } elseif (in_array($serviceCategory, ['credit_info', 'enhanced', 'full_enhanced', 'json_report', 'full_json', 'scrub'])) {
        if ($creditScore) {
            $statusClass = $creditScore >= 700 ? 'success' : ($creditScore >= 600 ? 'warning' : 'danger');
            $statusTitle = 'Credit Score: ' . $creditScore;
            $statusMessage = $scoreCategory ?? 'Credit assessment completed';
        }
        if ($delinquency['code'] === '004') {
            $statusClass = 'danger';
            $statusTitle = 'CURRENTLY DELINQUENT';
            $statusMessage = 'Active NPA detected';
        }
    }
}

$trx_id = 'TRX-' . date('YmdHis') . '-' . strtoupper(substr($id, -6));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($serviceConfig['name'] ?? 'Verification') ?> Report | Readiwork</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {--primary:#22c55e;--primary-dark:#16a34a;--success:#22c55e;--warning:#facc15;--danger:#ef4444;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#f8fafc 0%,#ecfdf5 100%);color:#0f172a;padding-top:80px;padding-bottom:100px;}
.scroll-progress{position:fixed;top:0;left:0;height:4px;background:linear-gradient(90deg,var(--primary),var(--primary-dark));z-index:9999;transform-origin:left;transition:transform 0.1s;}
.card-box{background:white;border-radius:32px;box-shadow:0 30px 80px rgba(0,0,0,0.1);padding:60px;margin:50px auto 100px;max-width:1200px;opacity:0;transform:translateY(30px);animation:fadeInUp 0.8s ease forwards 0.2s;}
@keyframes fadeInUp{to{opacity:1;transform:translateY(0);}}
.header{text-align:center;margin-bottom:60px;opacity:0;animation:fadeInUp 0.8s ease forwards 0.4s;}
.header h2{font-weight:900;font-size:3rem;background:linear-gradient(135deg,var(--primary),var(--primary-dark));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:16px;}
.header p{color:#64748b;font-size:1.1rem;}
.header .badge{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;padding:8px 20px;border-radius:999px;font-weight:700;display:inline-block;margin-top:12px;}
.provider-badge{display:inline-block;padding:6px 16px;border-radius:8px;font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-left:12px;}
.provider-metropol{background:rgba(99,102,241,0.2);color:#818cf8;border:1px solid rgba(99,102,241,0.3);}
.provider-youverify{background:rgba(34,197,94,0.2);color:#22c55e;border:1px solid rgba(34,197,94,0.3);}
.status-box{padding:50px;border-radius:24px;text-align:center;margin-bottom:60px;position:relative;overflow:hidden;opacity:0;animation:fadeInScale 0.8s ease forwards 0.6s;}
@keyframes fadeInScale{from{opacity:0;transform:scale(0.9);}to{opacity:1;transform:scale(1);}}
.status-box::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at top right,rgba(255,255,255,0.8) 0%,transparent 70%);animation:shimmer 3s infinite;}
@keyframes shimmer{0%,100%{opacity:0.5;}50%{opacity:1;}}
.status-box.success{background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%);border-left:8px solid var(--success);}
.status-box.warning{background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);border-left:8px solid var(--warning);}
.status-box.danger{background:linear-gradient(135deg,#fef2f2 0%,#fecaca 100%);border-left:8px solid var(--danger);}
.status-box i{font-size:5rem;margin-bottom:24px;display:block;position:relative;z-index:2;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{transform:scale(1);}50%{transform:scale(1.1);}}
.status-box.success i{color:var(--success);}
.status-box.warning i{color:var(--warning);}
.status-box.danger i{color:var(--danger);}
.status-box h3{font-weight:900;font-size:2.5rem;color:#0f172a;margin-bottom:12px;position:relative;z-index:2;}
.status-box p{color:#64748b;font-size:1.2rem;margin:0;position:relative;z-index:2;}
.identity-box{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:3px solid rgba(34,197,94,0.3);border-radius:24px;padding:50px;margin-bottom:60px;opacity:0;animation:fadeInUp 0.8s ease forwards 0.8s;position:relative;overflow:hidden;}
.identity-box::before{content:'✓';position:absolute;top:-40px;right:-40px;font-size:200px;color:rgba(34,197,94,0.05);font-weight:900;}
.identity-box h3{font-size:2.5rem;font-weight:900;color:#0f172a;margin-bottom:24px;position:relative;z-index:2;}
.identity-box .id-number{font-size:2rem;font-weight:800;color:var(--primary);font-family:'Courier New',monospace;letter-spacing:3px;margin-bottom:30px;padding:16px;background:white;border-radius:12px;display:inline-block;}
.identity-box .meta{display:flex;flex-wrap:wrap;gap:32px;color:#64748b;font-size:1.1rem;position:relative;z-index:2;}
.identity-box .meta span{display:flex;align-items:center;gap:10px;padding:12px 20px;background:white;border-radius:999px;}
.identity-box .meta i{color:var(--primary);}
.section{margin-top:70px;opacity:0;transform:translateY(30px);transition:all 0.8s;}
.section.visible{opacity:1;transform:translateY(0);}
.section h5{font-weight:800;font-size:2rem;margin-bottom:40px;color:#0f172a;border-bottom:4px solid var(--primary);padding-bottom:16px;display:flex;align-items:center;gap:16px;}
.section h5 i{color:var(--primary);font-size:2.2rem;}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;}
.info-item{background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);padding:32px;border-radius:20px;border:2px solid #e2e8f0;transition:all 0.4s ease;position:relative;overflow:hidden;}
.info-item::before{content:'';position:absolute;top:0;left:0;width:4px;height:0;background:var(--primary);transition:height 0.4s;}
.info-item:hover{border-color:var(--primary);transform:translateY(-6px) scale(1.02);box-shadow:0 15px 40px rgba(34,197,94,0.15);}
.info-item:hover::before{height:100%;}
.info-item .label{font-size:0.95rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.info-item .label i{color:var(--primary);}
.info-item .value{font-size:1.5rem;font-weight:800;color:#0f172a;word-break:break-word;}
.info-item .value.highlight{color:var(--primary);font-size:1.8rem;}
.btn-action{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;border:none;padding:20px 50px;border-radius:999px;font-weight:800;font-size:1.2rem;margin:20px 10px;box-shadow:0 15px 40px rgba(34,197,94,0.3);transition:all 0.4s ease;display:inline-flex;align-items:center;gap:12px;text-decoration:none;cursor:pointer;}
.btn-action:hover{transform:translateY(-6px) scale(1.05);box-shadow:0 25px 60px rgba(34,197,94,0.5);color:white;}
.btn-action.secondary{background:linear-gradient(135deg,#3b82f6,#2563eb);box-shadow:0 15px 40px rgba(59,130,246,0.3);}
.btn-action.secondary:hover{box-shadow:0 25px 60px rgba(59,130,246,0.5);}
table{width:100%;margin-top:30px;border-collapse:separate;border-spacing:0 8px;}
table th{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;padding:16px;text-align:left;font-weight:700;border-radius:12px 12px 0 0;}
table td{padding:16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;}
table tr{transition:all 0.3s;}
table tbody tr:hover td{background:#ecfdf5;transform:scale(1.01);}
.footer-note{margin-top:100px;padding-top:50px;border-top:3px solid #e2e8f0;font-size:1.05rem;color:#64748b;text-align:center;line-height:2;}
.footer-note strong{color:var(--primary);font-size:1.3rem;}
.debug-box{background:#1f2937;color:#22c55e;padding:30px;border-radius:16px;margin-bottom:40px;font-family:monospace;font-size:13px;max-height:500px;overflow:auto;border:2px solid #374151;}
.verification-badges{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:30px;}
.verification-badge{background:white;border:2px solid;border-radius:12px;padding:16px 24px;text-align:center;min-width:140px;}
.verification-badge.success{border-color:#22c55e;color:#059669;}
.verification-badge.warning{border-color:#facc15;color:#ca8a04;}
.verification-badge .badge-label{font-size:0.85rem;font-weight:600;text-transform:uppercase;margin-bottom:8px;}
.verification-badge .badge-value{font-size:1.3rem;font-weight:900;}
@media (max-width:768px){.card-box{padding:40px 24px;}.header h2{font-size:2.2rem;}.status-box{padding:40px 24px;}.status-box i{font-size:3.5rem;}.identity-box h3{font-size:2rem;}.info-grid{grid-template-columns:1fr;}.btn-action{padding:16px 36px;font-size:1.05rem;width:100%;justify-content:center;}}
</style>
</head>
<body>

<div class="scroll-progress" id="scrollProgress"></div>

<nav class="navbar navbar-expand-lg fixed-top">
<?php include 'includes/navbar.php'; ?>
</nav>

<div class="container">
  <div class="card-box">
    
    <div class="header">
      <h2><?= htmlspecialchars($serviceConfig['name'] ?? 'Verification') ?> Report</h2>
      <p>
        Generated on <?= date('d F Y, h:i A') ?>
        <span class="provider-badge provider-<?= $provider === 'youverify' ? 'youverify' : 'metropol' ?>">
          <?= $provider === 'youverify' ? 'YouVerify' : 'Metropol' ?>
        </span>
      </p>
      <span class="badge">Reference: <?= htmlspecialchars($trx_id) ?></span>
    </div>

    <?php if ($debug): ?>
    <div class="debug-box">
      <strong style="color:#fbbf24">🛠 DEBUG MODE - Combined API Results:</strong><br><br>
      <?= htmlspecialchars(json_encode($combinedResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?>
    </div>
    <?php endif; ?>

    <!-- VERIFICATION STATUS -->
    <div class="status-box <?= $statusClass ?>">
      <i class="fa-solid <?= $statusIcon ?>"></i>
      <h3><?= htmlspecialchars($statusTitle) ?></h3>
      <p><?= htmlspecialchars($statusMessage) ?></p>
      
      <?php if ($provider === 'youverify' && isset($identity['all_validation_passed'])): ?>
      <div class="verification-badges">
        <div class="verification-badge <?= $identity['all_validation_passed'] ? 'success' : 'warning' ?>">
          <div class="badge-label">Validation Status</div>
          <div class="badge-value">
            <?= $identity['all_validation_passed'] ? '✓ PASSED' : '⚠ REVIEW' ?>
          </div>
        </div>
        
        <?php if ($identity['verification_status'] === 'found'): ?>
        <div class="verification-badge success">
          <div class="badge-label">Database Status</div>
          <div class="badge-value">✓ FOUND</div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($identity['verification_date'])): ?>
        <div class="verification-badge success">
          <div class="badge-label">Verified On</div>
          <div class="badge-value" style="font-size:0.95rem;">
            <?= date('M d, Y', strtotime($identity['verification_date'])) ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- IDENTITY DISPLAY -->
    <?php if ($show['identity'] && $fullName && $fullName !== 'N/A  N/A' && $fullName !== 'N/A'): ?>
    <div class="identity-box">
      <h3><?= htmlspecialchars($fullName) ?></h3>
      <div class="id-number">
        <?php 
        if ($provider === 'youverify' && $serviceKey === 'yv-ke-passport') {
            echo 'PASSPORT: ' . htmlspecialchars($identity['identity_number']);
        } elseif ($provider === 'youverify' && $serviceKey === 'yv-ke-drivers-license') {
            echo 'LICENSE: ' . htmlspecialchars($identity['license_number'] ?? $id);
        } elseif ($provider === 'youverify' && $serviceKey === 'yv-ke-plate-number') {
            echo 'PLATE: ' . htmlspecialchars($identity['plate_number'] ?? $id);
        } else {
            echo 'ID: ' . htmlspecialchars($identity['identity_number']);
        }
        ?>
      </div>
      <div class="meta">
        <?php if (isset($identity['dob']) && $identity['dob'] !== 'N/A' && $identity['dob']): ?>
        <span><i class="fa-solid fa-calendar-days"></i> Born: <?= htmlspecialchars($identity['dob']) ?></span>
        <?php endif; ?>
        
        <?php if (isset($identity['gender']) && $identity['gender'] !== 'N/A' && $identity['gender']): ?>
        <span>
          <i class="fa-solid fa-venus-mars"></i> 
          <?php 
          $genderDisplay = $identity['gender'];
          if ($genderDisplay === 'M' || $genderDisplay === 'male') echo 'Male';
          elseif ($genderDisplay === 'F' || $genderDisplay === 'female') echo 'Female';
          else echo htmlspecialchars($genderDisplay);
          ?>
        </span>
        <?php endif; ?>
        
        <?php if (isset($identity['citizenship']) && $identity['citizenship'] !== 'N/A'): ?>
        <span><i class="fa-solid fa-flag"></i> <?= htmlspecialchars($identity['citizenship']) ?></span>
        <?php endif; ?>
        
        <?php if ($provider === 'youverify' && isset($passportData['country'])): ?>
        <span><i class="fa-solid fa-location-dot"></i> Country: <?= htmlspecialchars($passportData['country']) ?></span>
        <?php endif; ?>
        
        <?php if ($provider === 'youverify' && $serviceKey === 'yv-ke-drivers-license' && isset($identity['national_id'])): ?>
        <span><i class="fa-solid fa-id-card"></i> National ID: <?= htmlspecialchars($identity['identity_number']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- DRIVER'S LICENSE SPECIFIC INFORMATION -->
    <?php if ($show['license_info'] && $licenseData): ?>
    <div class="section visible">
      <h5><i class="fa-solid fa-id-card"></i> Driver's License Information</h5>
      
      <div class="info-grid">
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-id-card"></i> License Number</div>
          <div class="value highlight"><?= htmlspecialchars($licenseData['licenseNumber'] ?? $id) ?></div>
        </div>
        
        <?php if (isset($licenseData['interimNumber']) && $licenseData['interimNumber']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-file-lines"></i> Interim Number</div>
          <div class="value"><?= htmlspecialchars($licenseData['interimNumber']) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-user"></i> Full Name</div>
          <div class="value"><?= htmlspecialchars($licenseData['fullName'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-id-badge"></i> National ID</div>
          <div class="value highlight"><?= htmlspecialchars($licenseData['nationalId'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-car"></i> Class of License</div>
          <div class="value" style="color:#3b82f6;font-size:2rem;"><?= htmlspecialchars($licenseData['classOfLicense'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-calendar-check"></i> Issue Date</div>
          <div class="value">
            <?= isset($licenseData['issuedDate']) ? date('M d, Y', strtotime($licenseData['issuedDate'])) : 'N/A' ?>
          </div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-calendar-xmark"></i> Expiry Date</div>
          <div class="value <?= (isset($licenseData['expiredDate']) && strtotime($licenseData['expiredDate']) < time()) ? '' : 'highlight' ?>">
            <?php 
            if (isset($licenseData['expiredDate'])) {
                $expiryDate = strtotime($licenseData['expiredDate']);
                $isExpired = $expiryDate < time();
                echo date('M d, Y', $expiryDate);
                if ($isExpired) echo ' <span style="color:#ef4444;font-size:1rem;">(EXPIRED)</span>';
            } else {
                echo 'N/A';
            }
            ?>
          </div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-venus-mars"></i> Gender</div>
          <div class="value"><?= ucfirst($licenseData['gender'] ?? 'N/A') ?></div>
        </div>
        
        <?php if (isset($licenseData['dateOfBirth']) && $licenseData['dateOfBirth']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-calendar-days"></i> Date of Birth</div>
          <div class="value"><?= htmlspecialchars($licenseData['dateOfBirth']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($licenseData['mobile']) && $licenseData['mobile']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-phone"></i> Mobile Number</div>
          <div class="value"><?= htmlspecialchars($licenseData['mobile']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($licenseData['email']) && $licenseData['email']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-envelope"></i> Email</div>
          <div class="value" style="font-size:1.2rem;"><?= htmlspecialchars($licenseData['email']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($licenseData['kra']) && $licenseData['kra']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-receipt"></i> KRA PIN</div>
          <div class="value"><?= htmlspecialchars($licenseData['kra']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($licenseData['bloodGroup']) && $licenseData['bloodGroup'] !== 'UNKNOWN'): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-droplet"></i> Blood Group</div>
          <div class="value" style="color:#ef4444;"><?= htmlspecialchars($licenseData['bloodGroup']) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Address Section -->
      <?php if (isset($licenseData['address']) && is_array($licenseData['address'])): 
        $addr = $licenseData['address'];
      ?>
      <h6 style="font-weight:800;font-size:1.5rem;margin-top:50px;margin-bottom:25px;color:#0f172a;display:flex;align-items:center;gap:12px;">
        <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> Address Information
      </h6>
      
      <div class="info-grid">
        <?php if (isset($addr['addressLine']) && $addr['addressLine']): ?>
        <div class="info-item" style="grid-column:span 2;">
          <div class="label"><i class="fa-solid fa-map-marker-alt"></i> Address</div>
          <div class="value"><?= htmlspecialchars($addr['addressLine']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($addr['city']) && $addr['city']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-city"></i> City</div>
          <div class="value"><?= htmlspecialchars($addr['city']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($addr['town']) && $addr['town']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-building"></i> Town</div>
          <div class="value"><?= htmlspecialchars($addr['town']) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Smart DL Details -->
      <?php if (isset($licenseData['smartDlDetails']) && is_array($licenseData['smartDlDetails'])): 
        $smartDL = $licenseData['smartDlDetails'];
      ?>
      <h6 style="font-weight:800;font-size:1.5rem;margin-top:50px;margin-bottom:25px;color:#0f172a;display:flex;align-items:center;gap:12px;">
        <i class="fa-solid fa-microchip" style="color:var(--primary);"></i> Smart DL Status
      </h6>
      
      <div class="info-grid">
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-credit-card"></i> Smart DL Status</div>
          <div class="value" style="color:<?= isset($smartDL['hasSmartDl']) && strpos(strtolower($smartDL['hasSmartDl']), 'waiting') !== false ? '#f97316' : '#22c55e' ?>;">
            <?= htmlspecialchars($smartDL['hasSmartDl'] ?? 'N/A') ?>
          </div>
        </div>
        
        <?php if (isset($smartDL['smartDLBookingStatus']) && $smartDL['smartDLBookingStatus']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-calendar-check"></i> Booking Status</div>
          <div class="value"><?= htmlspecialchars($smartDL['smartDLBookingStatus']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($smartDL['smartDlBookingDate']) && $smartDL['smartDlBookingDate']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-calendar"></i> Booking Date</div>
          <div class="value"><?= htmlspecialchars($smartDL['smartDlBookingDate']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($smartDL['smartDlBookingTestCenter']) && $smartDL['smartDlBookingTestCenter']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-building"></i> Test Center</div>
          <div class="value"><?= htmlspecialchars($smartDL['smartDlBookingTestCenter']) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Verification Details -->
      <div style="background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:2px solid #22c55e;border-radius:20px;padding:40px;margin-top:50px;">
        <h6 style="font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:24px;text-align:center;">
          <i class="fa-solid fa-shield-check" style="color:#22c55e;"></i> Verification Details
        </h6>
        
        <div class="info-grid">
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-check-circle"></i> Verification Status</div>
            <div class="value" style="color:<?= $licenseData['status'] === 'found' ? '#22c55e' : '#f97316' ?>;">
              <?= strtoupper($licenseData['status'] ?? 'Unknown') ?>
            </div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-certificate"></i> All Validations</div>
            <div class="value" style="color:<?= ($licenseData['allValidationPassed'] ?? false) ? '#22c55e' : '#f97316' ?>;">
              <?= ($licenseData['allValidationPassed'] ?? false) ? '✓ PASSED' : '⚠ REVIEW' ?>
            </div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-fingerprint"></i> Data Validation</div>
            <div class="value" style="color:<?= ($licenseData['dataValidation'] ?? false) ? '#22c55e' : '#94a3b8' ?>;">
              <?= ($licenseData['dataValidation'] ?? false) ? '✓ Verified' : 'Not Required' ?>
            </div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-face-smile"></i> Selfie Validation</div>
            <div class="value" style="color:<?= ($licenseData['selfieValidation'] ?? false) ? '#22c55e' : '#94a3b8' ?>;">
              <?= ($licenseData['selfieValidation'] ?? false) ? '✓ Verified' : 'Not Required' ?>
            </div>
          </div>
          
          <?php if (isset($licenseData['id'])): ?>
          <div class="info-item" style="background:white;grid-column:span 2;">
            <div class="label"><i class="fa-solid fa-barcode"></i> YouVerify Reference ID</div>
            <div class="value" style="font-size:1.1rem;font-family:monospace;"><?= htmlspecialchars($licenseData['id']) ?></div>
          </div>
          <?php endif; ?>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-clock"></i> Requested At</div>
            <div class="value" style="font-size:1.1rem;">
              <?= isset($licenseData['requestedAt']) ? date('M d, Y H:i', strtotime($licenseData['requestedAt'])) : 'N/A' ?>
            </div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-user-check"></i> Consent Provided</div>
            <div class="value" style="color:#22c55e;">
              <?= ($licenseData['isConsent'] ?? false) ? '✓ Yes' : '✗ No' ?>
            </div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-globe"></i> Country</div>
            <div class="value"><?= htmlspecialchars($licenseData['country'] ?? 'KE') ?></div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-file-lines"></i> Document Type</div>
            <div class="value"><?= htmlspecialchars($licenseData['type'] ?? 'keDriversLicense') ?></div>
          </div>
        </div>
        
        <div style="margin-top:40px;text-align:center;background:white;border-radius:16px;padding:30px;">
          <i class="fa-solid fa-certificate fa-3x" style="color:#22c55e;margin-bottom:16px;"></i>
          <h6 style="font-weight:900;font-size:1.4rem;color:#0f172a;margin-bottom:12px;">
            DRIVER'S LICENSE VERIFICATION COMPLETE
          </h6>
          <p style="color:#64748b;font-size:1.05rem;margin:0;">
            This driver's license has been verified against official NTSA records via YouVerify.<br>
            <strong>Verification completed on:</strong> <?= date('F d, Y \a\t h:i A') ?>
          </p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- VEHICLE SPECIFIC INFORMATION -->
    <?php if ($show['vehicle_info'] && $vehicleData): 
      $vehicle = $vehicleData['vehicle'] ?? [];
      $owner = $vehicleData['owner'] ?? [];
      $logbook = $vehicle['logbook'] ?? [];
      $entry = $vehicle['entry'] ?? [];
    ?>
    <div class="section visible">
      <h5><i class="fa-solid fa-car"></i> Vehicle Information</h5>
      
      <div class="info-grid">
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-car"></i> Plate Number</div>
          <div class="value highlight"><?= htmlspecialchars($vehicleData['plateNumber'] ?? $id) ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-check-circle"></i> Registration Status</div>
          <div class="value" style="color:<?= ($vehicle['registrationStatus'] ?? '') === 'registered' ? '#22c55e' : '#f97316' ?>;">
            <?= strtoupper($vehicle['registrationStatus'] ?? 'N/A') ?>
          </div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-industry"></i> Make</div>
          <div class="value"><?= htmlspecialchars($vehicle['make'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-tag"></i> Model</div>
          <div class="value"><?= htmlspecialchars($vehicle['model'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-calendar"></i> Year of Manufacture</div>
          <div class="value highlight"><?= htmlspecialchars($vehicle['yearOfManufacture'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-palette"></i> Body Color</div>
          <div class="value"><?= htmlspecialchars($vehicle['bodyColor'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-car-side"></i> Body Type</div>
          <div class="value"><?= htmlspecialchars($vehicle['bodyType'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-truck"></i> Vehicle Type</div>
          <div class="value"><?= htmlspecialchars($vehicle['vehicleType'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-barcode"></i> Chassis Number</div>
          <div class="value" style="font-size:1.1rem;font-family:monospace;"><?= htmlspecialchars($vehicle['chassisNumber'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-cog"></i> Engine Number</div>
          <div class="value" style="font-size:1.1rem;font-family:monospace;"><?= htmlspecialchars($vehicle['engineNumber'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-gauge-high"></i> Engine Capacity</div>
          <div class="value"><?= htmlspecialchars($vehicle['engineCapacity'] ?? 'N/A') ?> cc</div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-gas-pump"></i> Fuel Type</div>
          <div class="value"><?= htmlspecialchars($vehicle['fuelType'] ?? 'N/A') ?></div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-users"></i> Passenger Capacity</div>
          <div class="value"><?= htmlspecialchars($vehicle['passengerCapacity'] ?? 'N/A') ?> persons</div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-weight-hanging"></i> Tare Weight</div>
          <div class="value"><?= htmlspecialchars($vehicle['tareWeight'] ?? 'N/A') ?> kg</div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-weight-scale"></i> Gross Weight</div>
          <div class="value"><?= htmlspecialchars($vehicle['grossWeight'] ?? 'N/A') ?> kg</div>
        </div>
        
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-briefcase"></i> Purpose</div>
          <div class="value"><?= htmlspecialchars($vehicle['purpose'] ?? 'N/A') ?></div>
        </div>
        
        <?php if (isset($vehicle['registrationDate']) && $vehicle['registrationDate']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-calendar-check"></i> Registration Date</div>
          <div class="value"><?= date('M d, Y', strtotime($vehicle['registrationDate'])) ?></div>
        </div>
        <?php endif; ?>
      </div>

      </div>

      <!-- Logbook Information -->
      <?php if (!empty($logbook)): ?>
      <h6 style="font-weight:800;font-size:1.5rem;margin-top:50px;margin-bottom:25px;color:#0f172a;display:flex;align-items:center;gap:12px;">
        <i class="fa-solid fa-book" style="color:var(--primary);"></i> Logbook Information
      </h6>
      
      <div class="info-grid">
        <?php if (isset($logbook['number']) && $logbook['number']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-hashtag"></i> Logbook Number</div>
          <div class="value" style="font-family:monospace;"><?= htmlspecialchars($logbook['number']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($logbook['serialNumber']) && $logbook['serialNumber']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-barcode"></i> Serial Number</div>
          <div class="value" style="font-family:monospace;"><?= htmlspecialchars($logbook['serialNumber']) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Import & Duty Information -->
      <h6 style="font-weight:800;font-size:1.5rem;margin-top:50px;margin-bottom:25px;color:#0f172a;display:flex;align-items:center;gap:12px;">
        <i class="fa-solid fa-ship" style="color:var(--primary);"></i> Import & Duty Information
      </h6>
      
      <div class="info-grid">
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-money-bill-wave"></i> Duty Status</div>
          <div class="value" style="color:<?= ($vehicle['dutyStatus'] ?? '') === 'PAID' ? '#22c55e' : '#f97316' ?>;">
            <?= htmlspecialchars($vehicle['dutyStatus'] ?? 'N/A') ?>
          </div>
        </div>
        
        <?php if (isset($vehicle['dutyAmount']) && $vehicle['dutyAmount']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-dollar-sign"></i> Duty Amount</div>
          <div class="value">KES <?= number_format($vehicle['dutyAmount']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($entry['number']) && $entry['number']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-file-import"></i> Entry Number</div>
          <div class="value" style="font-family:monospace;"><?= htmlspecialchars($entry['number']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($entry['importerPin']) && $entry['importerPin']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-receipt"></i> Importer PIN</div>
          <div class="value"><?= htmlspecialchars($entry['importerPin']) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Owner Information -->
      <?php if (!empty($owner)): ?>
      <h6 style="font-weight:800;font-size:1.5rem;margin-top:50px;margin-bottom:25px;color:#0f172a;display:flex;align-items:center;gap:12px;">
        <i class="fa-solid fa-user-tie" style="color:var(--primary);"></i> Owner Information
      </h6>
      
      <div class="info-grid">
        <?php if (isset($owner['firstName']) && $owner['firstName']): ?>
        <div class="info-item" style="grid-column:span 2;">
          <div class="label"><i class="fa-solid fa-user"></i> Owner Name</div>
          <div class="value"><?= htmlspecialchars($owner['firstName']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($owner['ownerType']) && $owner['ownerType']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-building"></i> Owner Type</div>
          <div class="value"><?= htmlspecialchars($owner['ownerType']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($owner['idNumber']) && $owner['idNumber']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-id-card"></i> ID Number</div>
          <div class="value highlight"><?= htmlspecialchars($owner['idNumber']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($owner['pin']) && $owner['pin']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-receipt"></i> KRA PIN</div>
          <div class="value"><?= htmlspecialchars($owner['pin']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($owner['phone']) && $owner['phone']): ?>
        <div class="info-item">
          <div class="label"><i class="fa-solid fa-phone"></i> Phone Number</div>
          <div class="value"><?= htmlspecialchars($owner['phone']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($owner['address']) && is_array($owner['address'])): 
          $ownerAddr = $owner['address'];
        ?>
        <div class="info-item" style="grid-column:span 2;">
          <div class="label"><i class="fa-solid fa-location-dot"></i> Postal Address</div>
          <div class="value">
            P.O. Box <?= htmlspecialchars($ownerAddr['postalAddress'] ?? 'N/A') ?> - 
            <?= htmlspecialchars($ownerAddr['postalCode'] ?? '') ?>, 
            <?= htmlspecialchars($ownerAddr['town'] ?? '') ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Verification Details -->
      <div style="background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:2px solid #22c55e;border-radius:20px;padding:40px;margin-top:50px;">
        <h6 style="font-weight:900;font-size:1.6rem;color:#0f172a;margin-bottom:24px;text-align:center;">
          <i class="fa-solid fa-shield-check" style="color:#22c55e;"></i> Verification Details
        </h6>
        
        <div class="info-grid">
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-check-circle"></i> Verification Status</div>
            <div class="value" style="color:<?= $vehicleData['status'] === 'found' ? '#22c55e' : '#f97316' ?>;">
              <?= strtoupper($vehicleData['status'] ?? 'Unknown') ?>
            </div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-certificate"></i> All Validations</div>
            <div class="value" style="color:<?= ($vehicleData['allValidationPassed'] ?? false) ? '#22c55e' : '#f97316' ?>;">
              <?= ($vehicleData['allValidationPassed'] ?? false) ? '✓ PASSED' : '⚠ REVIEW' ?>
            </div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-fingerprint"></i> Data Validation</div>
            <div class="value" style="color:<?= ($vehicleData['dataValidation'] ?? false) ? '#22c55e' : '#94a3b8' ?>;">
              <?= ($vehicleData['dataValidation'] ?? false) ? '✓ Verified' : 'Not Required' ?>
            </div>
          </div>
          
          <?php if (isset($vehicleData['id'])): ?>
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-barcode"></i> Reference ID</div>
            <div class="value" style="font-size:1rem;font-family:monospace;"><?= htmlspecialchars($vehicleData['id']) ?></div>
          </div>
          <?php endif; ?>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-clock"></i> Requested At</div>
            <div class="value" style="font-size:1rem;">
              <?= isset($vehicleData['requestedAt']) ? date('M d, Y H:i', strtotime($vehicleData['requestedAt'])) : 'N/A' ?>
            </div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-user-check"></i> Consent Provided</div>
            <div class="value" style="color:#22c55e;">
              <?= ($vehicleData['isConsent'] ?? false) ? '✓ Yes' : '✗ No' ?>
            </div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-globe"></i> Country</div>
            <div class="value"><?= htmlspecialchars($vehicleData['country'] ?? 'KE') ?></div>
          </div>
          
          <div class="info-item" style="background:white;">
            <div class="label"><i class="fa-solid fa-file-lines"></i> Document Type</div>
            <div class="value"><?= htmlspecialchars($vehicleData['type'] ?? 'kePlateNumber') ?></div>
          </div>
        </div>
        
        <div style="margin-top:40px;text-align:center;background:white;border-radius:16px;padding:30px;">
          <i class="fa-solid fa-certificate fa-3x" style="color:#22c55e;margin-bottom:16px;"></i>
          <h6 style="font-weight:900;font-size:1.4rem;color:#0f172a;margin-bottom:12px;">
            VEHICLE VERIFICATION COMPLETE
          </h6>
          <p style="color:#64748b;font-size:1.05rem;margin:0;">
            This vehicle has been verified against official NTSA records via YouVerify.<br>
            <strong>Verification completed on:</strong> <?= date('F d, Y \a\t h:i A') ?>
          </p>
        </div>
      </div>

      <!-- Logbook, Import, Owner sections... see next message for complete code -->
    </div>
    <?php endif; ?>

    <!-- PASSPORT SPECIFIC INFORMATION (keep existing) -->
    <?php if ($show['passport_info'] && $passportData): ?>
    <!-- Keep all existing passport section code -->
    <?php endif; ?>

    <!-- REST OF METROPOL SECTIONS -->
    <?php if ($provider === 'metropol'): ?>
    <!-- Keep all existing Metropol sections -->
    <?php endif; ?>

    <!-- ACTION BUTTONS -->
    <div class="text-center" style="margin-top:80px">
      <a href="generate-pdf.php?rid=<?= $rid ?>" class="btn-action">
        <i class="fa-solid fa-file-pdf"></i> Download PDF Report
      </a>
      <button class="btn-action secondary" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Print Report
      </button>
      
      <?php if ($provider === 'youverify'): ?>
      <a href="<?php 
        if ($serviceKey === 'yv-ke-passport') echo 'passport.php';
        elseif ($serviceKey === 'yv-ke-drivers-license') echo 'license.php';
        elseif ($serviceKey === 'yv-ke-plate-number') echo 'carsearch.php';
        else echo 'verify.php?service=' . htmlspecialchars($serviceKey);
      ?>" class="btn-action secondary">
        <i class="fa-solid fa-rotate"></i> Verify Another
      </a>
      <?php endif; ?>
    </div>

    <div class="footer-note">
      <strong>READIWORK</strong> • Powered by <?= $provider === 'youverify' ? 'YouVerify' : 'Metropol CRB' ?><br>
      This report is confidential and for the intended recipient only.<br>
      <small style="color:#94a3b8">© <?= date('Y') ?> Readiwork. All rights reserved. | Generated: <?= date('F d, Y \a\t h:i A') ?></small>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.addEventListener('scroll', () => {
  const scrollProgress = document.getElementById('scrollProgress');
  const scrollTotal = document.documentElement.scrollHeight - window.innerHeight;
  const scrollPosition = window.scrollY;
  const progress = (scrollPosition / scrollTotal) * 100;
  scrollProgress.style.transform = `scaleX(${progress / 100})`;
});

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, {
  threshold: 0.1,
  rootMargin: '0px 0px -50px 0px'
});

document.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));
</script>
</body>
</html>