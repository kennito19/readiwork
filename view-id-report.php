<?php
// generate-id-pdf.php

// ────────────────────────────────────────────────
// Security & basic checks
// ────────────────────────────────────────────────
session_start();

// Optional: add your admin login check here
// if (!isset($_SESSION['admin_logged_in'])) { die('Access denied'); }

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid request');
}

$id = (int)$_GET['id'];

// ────────────────────────────────────────────────
// Include TCPDF
// ────────────────────────────────────────────────
require_once 'vendor/tcpdf/tcpdf.php';

// ────────────────────────────────────────────────
// Database connection
// ────────────────────────────────────────────────
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
        die('Record not found');
    }

    // Parse JSON result for additional data
    $resultData = json_decode($report['result'] ?? '{}', true);

} catch (Exception $e) {
    error_log('PDF generation error: ' . $e->getMessage());
    die('Error loading report data');
}

// ────────────────────────────────────────────────
// Extract data with fallbacks
// ────────────────────────────────────────────────
$fullName = $report['full_name'] ?? 'N/A';
$nationalId = $report['national_id'] ?? 'N/A';
$dob = $report['dob'] ?? $resultData['dob'] ?? $resultData['date_of_birth'] ?? 'N/A';
$nationality = $report['nationality'] ?? $resultData['citizenship'] ?? 'Kenyan';
$gender = $report['gender'] ?? $resultData['gender'] ?? 'N/A';
$genderFull = ($gender === 'M') ? 'Male' : (($gender === 'F') ? 'Female' : $gender);
$phone = $report['phone'] ?? $resultData['phone'] ?? 'N/A';
$email = $report['email'] ?? $resultData['email'] ?? 'N/A';
$status = ucfirst($report['status'] ?? 'unknown');
$ipAddress = $report['ip_address'] ?? 'Unknown';
$service = ucwords(str_replace('-', ' ', $report['service'] ?? 'Verification'));

// Format dates
$createdDt = DateTime::createFromFormat('Y-m-d H:i:s', $report['created_at'] ?? '');
$createdStr = $createdDt ? $createdDt->format('F j, Y - H:i') : '—';

// Additional fields from JSON
$occupation = $resultData['occupation'] ?? 'N/A';
$placeOfBirth = $resultData['place_of_birth'] ?? 'N/A';
$serialNumber = $resultData['serial_number'] ?? 'N/A';

// ────────────────────────────────────────────────
// Create PDF document
// ────────────────────────────────────────────────
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Readiwork');
$pdf->SetAuthor('Readiwork Verification System');
$pdf->SetTitle($service . ' Report #' . $report['id']);
$pdf->SetSubject('Official Verification Report');
$pdf->SetKeywords('Verification, Readiwork, KYC, Identity');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// ────────────────────────────────────────────────
// HEADER SECTION
// ────────────────────────────────────────────────
$pdf->SetFillColor(17, 24, 39); // Dark background
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 20, 'READIWORK', 0, 1, 'C', true);

// Service name
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetFillColor(34, 197, 94); // Green
$pdf->Cell(0, 12, strtoupper($service) . ' REPORT', 0, 1, 'C', true);

// Report ID and date
$pdf->SetFont('helvetica', '', 10);
$pdf->SetFillColor(30, 41, 59);
$pdf->Cell(0, 8, 'Report #' . $report['id'] . '  •  Generated: ' . date('F j, Y - H:i'), 0, 1, 'C', true);

$pdf->Ln(10);

// ────────────────────────────────────────────────
// STATUS BADGE
// ────────────────────────────────────────────────
$statusColor = ($status === 'Completed' || $status === 'Paid') ? [34, 197, 94] : [239, 68, 68];
$pdf->SetFillColor($statusColor[0], $statusColor[1], $statusColor[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 12, 'STATUS: ' . strtoupper($status), 0, 1, 'C', true);

$pdf->Ln(8);

// ────────────────────────────────────────────────
// PERSONAL INFORMATION SECTION
// ────────────────────────────────────────────────
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(240, 253, 244); // Light green background
$pdf->Cell(0, 10, '  PERSONAL INFORMATION', 0, 1, 'L', true);

$pdf->Ln(5);

// Single column layout to avoid overlap
$allFields = [
    'Full Name' => $fullName,
    'National ID' => $nationalId,
    'Date of Birth' => $dob,
    'Gender' => $genderFull,
    'Nationality' => $nationality,
    'Phone Number' => $phone,
    'Email Address' => $email,
    'Verification Date' => $createdStr,
];

foreach ($allFields as $label => $value) {
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(50, 6, $label . ':', 0, 0);
    
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(0, 6, $value, 0, 1);
}

$pdf->Ln(8);

// ────────────────────────────────────────────────
// ADDITIONAL DETAILS (if available)
// ────────────────────────────────────────────────
if ($occupation !== 'N/A' || $placeOfBirth !== 'N/A' || $serialNumber !== 'N/A') {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(239, 246, 255); // Light blue
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, '  ADDITIONAL DETAILS', 0, 1, 'L', true);
    
    $pdf->Ln(5);
    
    $additionalFields = [
        'Occupation' => $occupation,
        'Place of Birth' => $placeOfBirth,
        'Serial Number' => $serialNumber,
    ];
    
    foreach ($additionalFields as $label => $value) {
        if ($value !== 'N/A') {
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(50, 6, $label . ':', 0, 0);
            
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(0, 6, $value, 0, 1);
        }
    }
    
    $pdf->Ln(8);
}

// ────────────────────────────────────────────────
// VERIFICATION RESULT
// ────────────────────────────────────────────────
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(220, 252, 231); // Light green
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, '  VERIFICATION RESULT', 0, 1, 'L', true);

$pdf->Ln(5);

$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(21, 128, 61); // Dark green

$apiCode = $resultData['api_code'] ?? 'N/A';

if ($apiCode === '200' || $apiCode === 200) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(10, 8, chr(0x25CF), 0, 0); // Bullet point
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 8, 'Verification Successful - Identity Confirmed', 0, 1);
    
    $pdf->Cell(10, 8, chr(0x25CF), 0, 0);
    $pdf->Cell(0, 8, 'All provided details match official records', 0, 1);
    
    $pdf->Cell(10, 8, chr(0x25CF), 0, 0);
    $pdf->Cell(0, 8, 'Data retrieved from Metropol CRB database', 0, 1);
} else {
    $pdf->Cell(0, 8, 'Status: Verification Completed', 0, 1);
    if ($apiCode !== 'N/A') {
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 6, 'Response Code: ' . $apiCode, 0, 1);
    }
}

$pdf->Ln(8);

// ────────────────────────────────────────────────
// TECHNICAL DETAILS
// ────────────────────────────────────────────────
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 8, 'Technical Details', 0, 1);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 6, 'IP Address: ' . $ipAddress, 0, 1);
$pdf->Cell(0, 6, 'Transaction ID: TRX-' . date('YmdHis', strtotime($report['created_at'])) . '-' . strtoupper(substr($nationalId, -6)), 0, 1);

// ────────────────────────────────────────────────
// FOOTER
// ────────────────────────────────────────────────
$pdf->SetY(-30);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 6, 'READIWORK', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 5, 'Powered by Metropol CRB', 0, 1, 'C');
$pdf->Cell(0, 5, 'This report is confidential and for the intended recipient only.', 0, 1, 'C');
$pdf->SetFont('helvetica', 'I', 7);
$pdf->Cell(0, 5, '© ' . date('Y') . ' Readiwork. All rights reserved. | Generated: ' . date('F d, Y \a\t h:i A'), 0, 1, 'C');

// ────────────────────────────────────────────────
// Output PDF
// ────────────────────────────────────────────────
$filename = 'Readiwork-' . str_replace(' ', '-', $service) . '-Report-' . $report['id'] . '-' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');
// 'D' = force download
// 'I' = display in browser

exit;