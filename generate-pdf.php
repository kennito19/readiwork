<?php
// generate-id-pdf.php
session_start();

// FIX: Accept both 'rid' and 'id' parameters for backward compatibility
$id = null;
if (isset($_GET['rid']) && is_numeric($_GET['rid'])) {
    $id = (int)$_GET['rid'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
}

if (!$id) {
    die('Invalid request. Missing or invalid request ID.');
}

require_once 'vendor/tcpdf/tcpdf.php';
require_once 'config.php';

try {
    $stmt = $pdo->prepare("
        SELECT 
            id, national_id, full_name, dob, nationality, gender, 
            phone, email, created_at, status, result, ip_address, service
        FROM verification_requests
        WHERE id = :id 
        LIMIT 1
    ");
    $stmt->execute(['id' => $id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        die('Record not found. Please check the request ID and try again.');
    }

    // Check if payment is completed
    if ($report['status'] !== 'paid' && $report['status'] !== 'completed') {
        die('Payment not completed. Please complete payment first. <a href="payment.php?rid=' . $id . '">Complete payment</a>');
    }

    $resultData = json_decode($report['result'] ?? '{}', true);
    
    if (!$resultData || !is_array($resultData)) {
        die('No verification data available for this report.');
    }

} catch (Exception $e) {
    error_log('PDF generation error: ' . $e->getMessage());
    die('Error loading report data: ' . $e->getMessage());
}

// Get service configuration
$serviceKey = $report['service'] ?? 'identity-verify';
$serviceConfig = get_service_config($serviceKey);
$serviceCategory = $serviceConfig['category'] ?? 'identity';

/* ======================================================
 * INITIALIZE ALL DATA CONTAINERS (matching results-id.php)
 * ====================================================== */
$identity = [];
$fullName = '';
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
$scoreTrend = [];

/* ======================================================
 * EXTRACT DATA FROM EACH ENDPOINT RESPONSE
 * ====================================================== */
foreach ($resultData as $endpoint => $responseData) {
    if (!is_array($responseData)) continue;
    
    // === IDENTITY DATA ===
    if ($endpoint === '/identity/verify' || strpos($endpoint, 'verify') !== false) {
        if (isset($responseData['first_name']) || isset($responseData['names']) || isset($responseData['id_number'])) {
            $identity = array_merge($identity, [
                'first_name' => $responseData['first_name'] ?? $responseData['names'] ?? '',
                'other_name' => $responseData['other_name'] ?? $responseData['other_names'] ?? '',
                'surname' => $responseData['surname'] ?? $responseData['last_name'] ?? '',
                'last_name' => $responseData['last_name'] ?? $responseData['surname'] ?? '',
                'identity_number' => $responseData['identity_number'] ?? $responseData['id_number'] ?? $report['national_id'],
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
                'identity_number' => $profile['identity_number'] ?? $report['national_id'],
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

// Fill in missing identity fields
$identity['first_name'] = $identity['first_name'] ?? 'N/A';
$identity['other_name'] = $identity['other_name'] ?? '';
$identity['last_name'] = $identity['last_name'] ?? $identity['surname'] ?? 'N/A';
$identity['identity_number'] = $identity['identity_number'] ?? $report['national_id'];
$identity['dob'] = $identity['dob'] ?? $report['dob'] ?? 'N/A';
$identity['gender'] = $identity['gender'] ?? $report['gender'] ?? 'N/A';
$identity['citizenship'] = $identity['citizenship'] ?? $report['nationality'] ?? 'Kenyan';
$identity['serial_number'] = $identity['serial_number'] ?? 'N/A';

// Build full name
$fullName = trim(($identity['first_name'] ?? '') . ' ' . ($identity['other_name'] ?? '') . ' ' . ($identity['last_name'] ?? ''));
if ($fullName === '' || $fullName === 'N/A  N/A') {
    $fullName = $report['full_name'] ?? 'N/A';
}

// Extract contact from scrub data
foreach ($resultData as $endpoint => $responseData) {
    if (!is_array($responseData)) continue;
    
    if ($endpoint === '/identity/scrub' || strpos($endpoint, 'scrub') !== false) {
        $errorCodes = ['E017', 'E001', '001', 'E018'];
        $apiCode = $responseData['api_code'] ?? '';
        
        if (!in_array($apiCode, $errorCodes)) {
            if (isset($responseData['phone']) && is_array($responseData['phone'])) {
                $phones = array_merge($phones, $responseData['phone']);
            }
            if (isset($responseData['email']) && is_array($responseData['email'])) {
                $emails = array_merge($emails, $responseData['email']);
            }
            if (isset($responseData['employment']) && is_array($responseData['employment'])) {
                $employment = array_merge($employment, $responseData['employment']);
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
                        'address' => $addr['address'] ?? $addr['town'] ?? '',
                        'country' => $addr['country'] ?? 'KENYA'
                    ];
                }
            }
        }
    }
}

$phones = array_unique($phones);
$emails = array_unique($emails);

// Process accounts
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
    '001' => ['title' => 'ID Not Found', 'class' => 'danger'],
    '002' => ['title' => 'No Credit Info', 'class' => 'warning'],
    '003' => ['title' => 'CLEAR', 'class' => 'success'],
    '004' => ['title' => 'CURRENTLY DELINQUENT', 'class' => 'danger'],
    '005' => ['title' => 'HISTORICAL DELINQUENCY', 'class' => 'warning'],
];

$delStatus = $delinquencyStatus[$delinquency['code']] ?? $delinquencyStatus['003'];

// Prepare data
$nationalId = $identity['identity_number'];
$dob = $identity['dob'];
$nationality = $identity['citizenship'];
$gender = $identity['gender'];
$genderFull = ($gender === 'M' || strtolower($gender) === 'male') ? 'Male' : (($gender === 'F' || strtolower($gender) === 'female') ? 'Female' : $gender);
$serialNumber = $identity['serial_number'];

$phone = !empty($phones) ? implode(', ', array_slice($phones, 0, 2)) : ($report['phone'] ?? 'N/A');
$email = !empty($emails) ? implode(', ', array_slice($emails, 0, 2)) : ($report['email'] ?? 'N/A');

$status = ucfirst($report['status'] ?? 'unknown');
$ipAddress = $report['ip_address'] ?? 'Unknown';
$service = $serviceConfig['name'] ?? ucwords(str_replace('-', ' ', $report['service'] ?? 'Verification'));

$createdDt = DateTime::createFromFormat('Y-m-d H:i:s', $report['created_at'] ?? '');
$createdStr = $createdDt ? $createdDt->format('F j, Y - H:i') : '—';

// Create PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('Readiwork');
$pdf->SetAuthor('Readiwork Verification System');
$pdf->SetTitle($service . ' Report #' . $report['id']);
$pdf->SetSubject('Official Verification Report');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(TRUE, 20);

$pdf->AddPage();

// ═══════════════════════════════════════════════
// HEADER WITH LOGO AREA
// ═══════════════════════════════════════════════
$pdf->SetFillColor(15, 23, 42);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 24);
$pdf->Cell(0, 18, 'READIWORK', 0, 1, 'C', true);

$pdf->SetFont('helvetica', '', 11);
$pdf->SetFillColor(30, 41, 59);
$pdf->Cell(0, 10, strtoupper($service), 0, 1, 'C', true);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(51, 65, 85);
$pdf->Cell(0, 8, 'Report #' . $report['id'] . '  |  Generated: ' . date('F j, Y'), 0, 1, 'C', true);

$pdf->Ln(12);

// ═══════════════════════════════════════════════
// STATUS BADGE
// ═══════════════════════════════════════════════
if ($serviceCategory === 'crb') {
    // CRB Status based on delinquency
    if ($delStatus['class'] === 'success') {
        $pdf->SetFillColor(220, 252, 231);
        $pdf->SetTextColor(21, 128, 61);
        $statusText = 'CLEAR - No Delinquency';
    } elseif ($delStatus['class'] === 'danger') {
        $pdf->SetFillColor(254, 202, 202);
        $pdf->SetTextColor(153, 27, 27);
        $statusText = 'DELINQUENT - ' . $delinquency['summary'];
    } else {
        $pdf->SetFillColor(254, 243, 199);
        $pdf->SetTextColor(161, 98, 7);
        $statusText = 'WARNING - ' . $delinquency['summary'];
    }
} elseif ($creditScore) {
    // Score-based status
    if ($creditScore >= 700) {
        $pdf->SetFillColor(220, 252, 231);
        $pdf->SetTextColor(21, 128, 61);
        $statusText = 'EXCELLENT - Score: ' . $creditScore . '/900';
    } elseif ($creditScore >= 600) {
        $pdf->SetFillColor(254, 249, 195);
        $pdf->SetTextColor(161, 98, 7);
        $statusText = 'GOOD - Score: ' . $creditScore . '/900';
    } else {
        $pdf->SetFillColor(254, 202, 202);
        $pdf->SetTextColor(153, 27, 27);
        $statusText = 'FAIR - Score: ' . $creditScore . '/900';
    }
} else {
    // Default status
    if ($status === 'Completed' || $status === 'Paid') {
        $pdf->SetFillColor(220, 252, 231);
        $pdf->SetTextColor(21, 128, 61);
        $statusText = 'VERIFIED - ' . strtoupper($status);
    } else {
        $pdf->SetFillColor(254, 202, 202);
        $pdf->SetTextColor(153, 27, 27);
        $statusText = 'STATUS: ' . strtoupper($status);
    }
}

$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 12, $statusText, 'B', 1, 'C', true);

$pdf->Ln(8);

// ═══════════════════════════════════════════════
// PERSONAL INFORMATION SECTION
// ═══════════════════════════════════════════════
$pdf->SetFillColor(241, 245, 249);
$pdf->SetTextColor(15, 23, 42);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 10, '  PERSONAL INFORMATION', 0, 1, 'L', true);

$pdf->Ln(4);

$allFields = [
    'Full Name' => $fullName,
    'National ID' => $nationalId,
    'Date of Birth' => $dob,
    'Gender' => $genderFull,
    'Nationality' => $nationality,
];

if ($phone !== 'N/A') $allFields['Phone Number'] = $phone;
if ($email !== 'N/A') $allFields['Email Address'] = $email;
if ($serialNumber !== 'N/A') $allFields['Serial Number'] = $serialNumber;

$allFields['Verification Date'] = $createdStr;

$pdf->SetFont('helvetica', '', 10);
foreach ($allFields as $label => $value) {
    $pdf->SetTextColor(71, 85, 105);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(55, 7, $label . ':', 0, 0, 'L');
    
    $pdf->SetTextColor(15, 23, 42);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, $value, 0, 1, 'L');
}

$pdf->Ln(6);

// ═══════════════════════════════════════════════
// CREDIT SCORE (if available)
// ═══════════════════════════════════════════════
if ($creditScore) {
    $pdf->SetFillColor(240, 253, 244);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 10, '  CREDIT SCORE', 0, 1, 'L', true);
    
    $pdf->Ln(4);
    
    $pdf->SetFont('helvetica', 'B', 32);
    $pdf->SetTextColor(34, 197, 94);
    $pdf->Cell(0, 15, $creditScore . '/900', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(0, 6, $scoreCategory . ' Credit Quality', 0, 1, 'C');
    $pdf->Cell(0, 5, 'As of ' . date('M d, Y', strtotime($scoreDate)), 0, 1, 'C');
    
    $pdf->Ln(6);
}

// ═══════════════════════════════════════════════
// CRB STATUS (if applicable)
// ═══════════════════════════════════════════════
if ($serviceCategory === 'crb' || $delinquency['code'] !== '003') {
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 10, '  CRB DELINQUENCY STATUS', 0, 1, 'L', true);
    
    $pdf->Ln(4);
    
    $crbFields = [
        'Delinquency Code' => $delinquency['code'],
        'Status' => $delinquency['summary'],
    ];
    
    if ($delinquency['loan_amount'] > 0) {
        $crbFields['Loan Amount Checked'] = 'KES ' . number_format($delinquency['loan_amount']);
    }
    
    foreach ($crbFields as $label => $value) {
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(55, 7, $label . ':', 0, 0, 'L');
        
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, $value, 0, 1, 'L');
    }
    
    $pdf->Ln(6);
}

// ═══════════════════════════════════════════════
// CREDIT ACCOUNTS SUMMARY (if available)
// ═══════════════════════════════════════════════
if ($totalAccounts > 0) {
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 10, '  CREDIT ACCOUNTS SUMMARY', 0, 1, 'L', true);
    
    $pdf->Ln(4);
    
    $accountFields = [
        'Total Accounts' => $totalAccounts,
        'Active Accounts' => $activeAccounts,
        'NPA Accounts' => $npaAccounts,
        'Total Balance' => 'KES ' . number_format($totalBalance, 2),
    ];
    
    if ($totalOverdue > 0) {
        $accountFields['Overdue Amount'] = 'KES ' . number_format($totalOverdue, 2);
    }
    
    foreach ($accountFields as $label => $value) {
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(55, 7, $label . ':', 0, 0, 'L');
        
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, $value, 0, 1, 'L');
    }
    
    $pdf->Ln(4);
    
    // Account details table (top 5)
    if (count($accounts) > 0) {
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(34, 197, 94);
        $pdf->SetTextColor(255, 255, 255);
        
        $pdf->Cell(50, 6, 'Account Number', 1, 0, 'L', true);
        $pdf->Cell(30, 6, 'Status', 1, 0, 'L', true);
        $pdf->Cell(40, 6, 'Balance', 1, 0, 'R', true);
        $pdf->Cell(25, 6, 'Days Arrears', 1, 0, 'C', true);
        $pdf->Cell(25, 6, 'Del. Code', 1, 1, 'C', true);
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(15, 23, 42);
        
        foreach (array_slice($accounts, 0, 5) as $acc) {
            $pdf->Cell(50, 6, substr($acc['account_number'] ?? '-', 0, 15), 1, 0, 'L');
            $pdf->Cell(30, 6, $acc['account_status'] ?? '-', 1, 0, 'L');
            $pdf->Cell(40, 6, 'KES ' . number_format($acc['current_balance'] ?? 0, 2), 1, 0, 'R');
            $pdf->Cell(25, 6, $acc['days_in_arrears'] ?? 0, 1, 0, 'C');
            $pdf->Cell(25, 6, $acc['delinquency_code'] ?? '-', 1, 1, 'C');
        }
        
        if (count($accounts) > 5) {
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(0, 5, '... and ' . (count($accounts) - 5) . ' more account(s)', 0, 1, 'C');
        }
    }
    
    $pdf->Ln(6);
}

// ═══════════════════════════════════════════════
// CONTACT INFORMATION (if from scrub)
// ═══════════════════════════════════════════════
if (!empty($phones) || !empty($emails) || !empty($addresses)) {
    $pdf->SetFillColor(240, 249, 255);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 10, '  IDENTITY INTELLIGENCE', 0, 1, 'L', true);
    
    $pdf->Ln(4);
    
    if (!empty($phones)) {
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(55, 7, 'Phone Numbers:', 0, 0, 'L');
        
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 7, implode(', ', array_slice($phones, 0, 5)), 0, 'L');
    }
    
    if (!empty($emails)) {
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(55, 7, 'Email Addresses:', 0, 0, 'L');
        
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 7, implode(', ', array_slice($emails, 0, 3)), 0, 'L');
    }
    
    if (!empty($addresses)) {
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(55, 7, 'Addresses Found:', 0, 0, 'L');
        
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 7, count($addresses) . ' address record(s)', 0, 1, 'L');
    }
    
    if (!empty($employment)) {
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(55, 7, 'Employment Records:', 0, 0, 'L');
        
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 7, count($employment) . ' employer(s) found', 0, 1, 'L');
    }
    
    $pdf->Ln(6);
}

// ═══════════════════════════════════════════════
// TECHNICAL DETAILS BOX
// ═══════════════════════════════════════════════
$pdf->SetFillColor(249, 250, 251);
$pdf->Rect($pdf->GetX(), $pdf->GetY(), 170, 18, 'F');

$startY = $pdf->GetY() + 4;
$pdf->SetY($startY);

$pdf->SetTextColor(100, 116, 139);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(55, 5, 'IP Address:', 0, 0);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, $ipAddress, 0, 1);

$pdf->SetX(20);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(55, 5, 'Transaction ID:', 0, 0);
$pdf->SetFont('helvetica', '', 9);
$trxId = 'TRX-' . date('YmdHis', strtotime($report['created_at'])) . '-' . strtoupper(substr($nationalId, -6));
$pdf->Cell(0, 5, $trxId, 0, 1);

$pdf->Ln(10);

// ═══════════════════════════════════════════════
// FOOTER
// ═══════════════════════════════════════════════
$pdf->SetY(-35);

// Separator line
$pdf->SetDrawColor(226, 232, 240);
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->Ln(3);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(0, 6, 'READIWORK', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 5, 'Powered by Metropol CRB', 0, 1, 'C');
$pdf->Cell(0, 4, 'This report is confidential and for the intended recipient only.', 0, 1, 'C');

$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(148, 163, 184);
$pdf->Cell(0, 4, '© ' . date('Y') . ' Readiwork. All rights reserved. | Generated: ' . date('F d, Y \a\t h:i A'), 0, 1, 'C');

// ═══════════════════════════════════════════════
// OUTPUT
// ═══════════════════════════════════════════════
$filename = 'Readiwork-' . str_replace([' ', '/', '\\'], '-', $service) . '-Report-' . $report['id'] . '-' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');

exit;