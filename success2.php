<?php
session_start();

// ✅ Security check
if (!isset($_SESSION['tracking_id'])) {
    header("Location: index.php");
    exit;
}

// ✅ Pull identity safely
$phone  = $_SESSION['phone_number'] ?? '2547XXXXXXX';
$id     = $_SESSION['id_number'] ?? 'IDXXXXXX';

// ✅ Dummy HIGH-RISK CRB DATA (until live API)
$crb = [
    "full_name"     => ["John Mwangi", "Mary Wanjiku", "Brian Otieno", "Grace Chebet"][rand(0,3)],
    "dob"           => rand(1985,2003)."-0".rand(1,9)."-".rand(10,28),
    "gender"        => rand(0,1) ? "Male" : "Female",
    "county"        => ["Nairobi","Kiambu","Kisumu","Nakuru","Machakos"][rand(0,4)],
    "credit_score" => rand(150, 380),
    "crb_status"   => "High Risk / Listed",
    "active_loans" => rand(3, 7),
    "total_debt"   => rand(120000, 450000),
    "last_updated" => date("d M Y")
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>High Risk Credit Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(120deg, #7f1d1d, #991b1b);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0,0,0,0.35);
        }
        .badge {
            font-size: 14px;
        }
        .header-status {
            background: #dc2626;
            color: white;
            font-weight: bold;
            padding: 14px;
            text-align: center;
            border-radius: 12px;
            font-size: 20px;
        }
        .risk-box{
            background:#fee2e2;
            border-left:6px solid #b91c1c;
            padding:16px;
            border-radius:10px;
            font-size:14px;
        }
    </style>
</head>

<body>
<div class="container py-5">

    <!-- ✅ STATUS -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-9">
            <div class="header-status">
                ⚠️ HIGH-RISK CREDIT PROFILE DETECTED
            </div>
        </div>
    </div>

    <!-- ✅ WARNING MESSAGE -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-9">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="fw-bold text-danger">
                        Your current credit profile indicates high financial risk.
                    </h5>
                    <p class="mt-2">
                        Most regulated financial institutions may decline loan applications under this profile.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ FULL CRB REPORT -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-dark text-white fw-bold">
                    🧾 Credit Reference Bureau (CRB) Assessment
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>Full Name</th><td><?= $crb['full_name']; ?></td></tr>
                        <tr><th>Date of Birth</th><td><?= $crb['dob']; ?></td></tr>
                        <tr><th>Gender</th><td><?= $crb['gender']; ?></td></tr>
                        <tr><th>County</th><td><?= $crb['county']; ?></td></tr>
                        <tr><th>Credit Score</th><td><?= $crb['credit_score']; ?></td></tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-danger">
                                    <?= $crb['crb_status']; ?>
                                </span>
                            </td>
                        </tr>
                        <tr><th>Active Loans</th><td><?= $crb['active_loans']; ?></td></tr>
                        <tr><th>Total Outstanding Debt</th><td>KES <?= number_format($crb['total_debt']); ?></td></tr>
                        <tr><th>Last Updated</th><td><?= $crb['last_updated']; ?></td></tr>
                    </table>

                    <!-- ✅ SAFE LEGAL WARNING -->
                    <div class="risk-box mb-4">
                        This assessment shows elevated financial risk. Loan approval is fully controlled by licensed
                        financial institutions. This platform does not issue loans, but provides credit verification
                        services only.
                    </div>

                    <!-- ✅ ACTION BUTTON -->
                    <div class="d-grid">
                        <a href="index.php" class="btn btn-dark">
                            🔁 Retry With Different Details
                        </a>
                    </div>

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
