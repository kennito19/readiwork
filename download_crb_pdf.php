<?php
session_start();

// ✅ Security check
if (!isset($_SESSION['tracking_id'], $_SESSION['kyc'], $_SESSION['loan_amount'])) {
    die("Unauthorized access.");
}

// ✅ Load TCPDF
require_once __DIR__ . '/tcpdf/tcpdf.php';

// ✅ Fetch data
$tracking_id = $_SESSION['tracking_id'];
$phone       = $_SESSION['phone_number'] ?? '2547XXXXXXX';
$id_number   = $_SESSION['id_number'] ?? 'Not Provided';
$loan_amount = $_SESSION['loan_amount'];

$crb = $_SESSION['kyc'];

$credit_score = rand(320, 760);
$active_loans = rand(0, 3);
$total_debt   = rand(0, 250000);
$last_updated = date("d M Y");

// ✅ Create PDF
$pdf = new TCPDF();
$pdf->SetCreator('ReadyLoan');
$pdf->SetAuthor('ReadyLoan™ Verification');
$pdf->SetTitle('CRB Verification Report');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// ✅ Logo (optional)
if (file_exists('logo.png')) {
    $pdf->Image('logo.png', 15, 10, 30);
    $pdf->Ln(25);
}

// ✅ Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'CREDIT REFERENCE BUREAU (CRB) REPORT', 0, 1, 'C');
$pdf->Ln(4);

// ✅ Sub Info
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, "Tracking ID: $tracking_id", 0, 1);
$pdf->Cell(0, 8, "Phone Number: $phone", 0, 1);
$pdf->Cell(0, 8, "ID Number: $id_number", 0, 1);
$pdf->Cell(0, 8, "Report Date: $last_updated", 0, 1);
$pdf->Ln(6);

// ✅ Table Style
$html = "
<style>
table { width:100%; border-collapse:collapse; }
th { background:#f1f5f9; padding:8px; border:1px solid #ccc; text-align:left; }
td { padding:8px; border:1px solid #ccc; }
</style>

<table>
<tr><th>Full Name</th><td>{$crb['full_name']}</td></tr>
<tr><th>Date of Birth</th><td>{$crb['dob']}</td></tr>
<tr><th>Gender</th><td>{$crb['gender']}</td></tr>
<tr><th>County</th><td>{$crb['county']}</td></tr>
<tr><th>Credit Score</th><td>$credit_score</td></tr>
<tr><th>CRB Status</th><td>{$crb['crb_status']}</td></tr>
<tr><th>Active Loans</th><td>$active_loans</td></tr>
<tr><th>Total Outstanding Debt</th><td>KES " . number_format($total_debt) . "</td></tr>
<tr><th>Eligibility Amount</th><td>KES " . number_format($loan_amount) . "</td></tr>
</table>
";

$pdf->writeHTML($html, true, false, true, false, '');

// ✅ Decision Section
$pdf->Ln(8);
$pdf->SetFont('helvetica', 'B', 12);

if ($crb['crb_status'] === "Good Standing") {
    $pdf->SetTextColor(0, 128, 0);
    $pdf->MultiCell(0, 8, "✅ CREDIT ASSESSMENT RESULT: GOOD STANDING\n\nYou meet the minimum credit requirements used by lending institutions. You may qualify for loan consideration subject to lender approval.", 0, 'L');
} else {
    $pdf->SetTextColor(180, 0, 0);
    $pdf->MultiCell(0, 8, "⚠️ CREDIT ASSESSMENT RESULT: HIGH RISK\n\nYour credit profile shows elevated risk. Loan approval is unlikely at this time until outstanding obligations are resolved.", 0, 'L');
}
$pdf->SetTextColor(0, 0, 0);

// ✅ Legal Disclaimer
$pdf->Ln(8);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 8,
"DISCLAIMER:
This report is generated for identity and credit assessment purposes only. ReadyLoan™ is a financial verification and loan-matching platform and does not issue loans directly. Loan approval and disbursement depend solely on third-party licensed lenders.",
0, 'L');

// ✅ Output PDF
$pdf->Output("CRB_Report_$tracking_id.pdf", "D");
exit;
