<?php
session_start();

// ✅ Security check
if (!isset($_SESSION['tracking_id'])) {
    header("Location: index.php");
    exit;
}

// ✅ Pull original input identity
$phone  = $_SESSION['phone_number'] ?? '2547XXXXXXX';
$id     = $_SESSION['id_number'] ?? 'IDXXXXXX';
$amount = $_SESSION['loan_amount'] ?? rand(10000, 100000);

// ✅ Dummy FULL CRB Report (until live API)
$crb = [
    "full_name"     => ["John Mwangi", "Mary Wanjiku", "Brian Otieno", "Grace Chebet"][rand(0,3)],
    "dob"           => rand(1985,2003)."-0".rand(1,9)."-".rand(10,28),
    "gender"        => rand(0,1) ? "Male" : "Female",
    "county"        => ["Nairobi","Kiambu","Kisumu","Nakuru","Machakos"][rand(0,4)],
    "credit_score" => rand(320, 760),
    "crb_status"   => rand(0,1) ? "Good Standing" : "Listed",
    "active_loans" => rand(0, 3),
    "total_debt"   => rand(0, 250000),
    "last_updated" => date("d M Y")
];

// ✅ Determine qualification message safely
$qualification_message = $crb['crb_status'] === "Good Standing"
    ? "✅ Your credit profile is in good standing. You may qualify for loan offers from our partner financial institutions."
    : "⚠️ Your credit profile shows high risk. You may experience difficulty obtaining loans from most financial institutions.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verification Complete & CRB Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(120deg, #0d6efd, #20c997);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0,0,0,0.25);
        }
        .badge {
            font-size: 14px;
        }
        .header-status {
            background: #198754;
            color: white;
            font-weight: bold;
            padding: 14px;
            text-align: center;
            border-radius: 12px;
            font-size: 20px;
        }
        .warning-box{
            background:#fff3cd;
            border-left:6px solid #ffc107;
            padding:14px;
            border-radius:10px;
            font-size:14px;
        }
    </style>
</head>

<body>
<div class="container py-5">

    <!-- ✅ статус -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-9">
            <div class="header-status">
                ✅ VERIFICATION SUCCESSFUL — CREDIT PROFILE READY
            </div>
        </div>
    </div>

    <!-- ✅ QUALIFICATION MESSAGE -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-9">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="fw-bold"><?= htmlspecialchars($qualification_message); ?></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ LOAN ESTIMATE (SAFE DISPLAY) -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-9">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="fw-bold text-success">Up to KES <?= number_format($amount); ?></h3>
                    <p>Estimated eligibility based on your credit profile.</p>
                    <h6><?= htmlspecialchars($phone); ?></h6>
                    <span class="badge bg-primary">Typical Tenure: 12 Months</span>
                    <span class="badge bg-success">Depends on Lender</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ FULL CRB REPORT -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-dark text-white fw-bold">
                    🧾 Credit Reference Bureau (CRB) Full Report
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>Full Name</th><td><?= $crb['full_name']; ?></td></tr>
                        <tr><th>Date of Birth</th><td><?= $crb['dob']; ?></td></tr>
                        <tr><th>Gender</th><td><?= $crb['gender']; ?></td></tr>
                        <tr><th>County</th><td><?= $crb['county']; ?></td></tr>
                        <tr><th>Credit Score</th><td><?= $crb['credit_score']; ?></td></tr>
                        <tr>
                            <th>CRB Status</th>
                            <td>
                                <span class="badge bg-<?= $crb['crb_status']=="Good Standing"?"success":"danger"; ?>">
                                    <?= $crb['crb_status']; ?>
                                </span>
                            </td>
                        </tr>
                        <tr><th>Active Loans</th><td><?= $crb['active_loans']; ?></td></tr>
                        <tr><th>Total Outstanding Debt</th><td>KES <?= number_format($crb['total_debt']); ?></td></tr>
                        <tr><th>Last Updated</th><td><?= $crb['last_updated']; ?></td></tr>
                    </table>

                    <!-- ✅ SAFE DISCLAIMER -->
                    <div class="warning-box mb-4">
                        This CRB report is provided for informational purposes only. Loan approval, limits, and
                        disbursement are determined solely by independent licensed financial institutions.
                    </div>

                    <!-- ✅ DOWNLOAD PDF -->
                    <form action="download_crb_pdf.php" method="POST">
                        <input type="hidden" name="tracking_id" value="<?= $_SESSION['tracking_id']; ?>">
                        <button type="submit" class="btn btn-danger w-100">
                            📄 Download Full CRB Report (PDF)
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- ✅ FOOTER -->
    <div class="row">
        <div class="col-12 text-center text-white">
            <p>© <?= date("Y"); ?> ReadyLoan™. All Rights Reserved.</p>
        </div>
    </div>

</div>
</body>
</html>
