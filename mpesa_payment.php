<?php
session_start();

// ✅ Get payment details ONLY from session (amount is LOCKED)
$tracking_id  = $_SESSION['tracking_id'] ?? '';
$amount       = $_SESSION['verification_fee'] ?? '';
$phone_number = $_SESSION['phone_number'] ?? '';

// ✅ Block direct access if session is missing
if (!$tracking_id || !$amount) {
    header("Location: eligibility_check.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure M-PESA Verification Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(120deg, #0d6efd, #198754);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }
        .card {
            border-radius: 18px;
            box-shadow: 0 0 25px rgba(0,0,0,0.25);
        }
        .secure-badge {
            background: #198754;
            color: #fff;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 13px;
            display: inline-block;
        }
        .disclaimer {
            font-size: 13px;
            color: #6c757d;
        }
    </style>
</head>

<body>
<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card text-center">
                <div class="card-body py-4">

                    <span class="secure-badge mb-2">🔐 Secure Checkout</span>

                    <h4 class="mb-2 mt-2">CRB & Identity Verification</h4>
                    <p class="text-muted">
                        Reference ID: <strong><?= htmlspecialchars($tracking_id); ?></strong>
                    </p>

                    <!-- ✅ FIXED AMOUNT (NOT EDITABLE) -->
                    <div class="alert alert-danger fw-bold fs-4">
                        KES <?= number_format($amount); ?>
                    </div>

                    <p class="mb-3">
                        This payment covers your official **CRB & identity verification**.  
                        After successful verification, your full credit report and eligibility result will be processed.
                    </p>

                    <p class="disclaimer mb-4">
                        ⚠️ This fee is for verification only and does <strong>not</strong> guarantee loan approval or disbursement.
                    </p>

                    <!-- ✅ ONLY PHONE NUMBER IS EDITABLE -->
                    <form method="POST" action="/stk">

                        <!-- ✅ Locked values -->
                        <input type="hidden" name="tracking_id" value="<?= htmlspecialchars($tracking_id); ?>">
                        <input type="hidden" name="amount" value="<?= htmlspecialchars($amount); ?>">

                        <!-- ✅ Phone input -->
                        <div class="mb-3 text-start">
                            <label class="form-label">M-PESA Phone Number</label>
                            <input 
                                type="text" 
                                name="phone_number" 
                                class="form-control"
                                placeholder="07XXXXXXXX"
                                value="<?= htmlspecialchars($phone_number); ?>"
                                required
                            >
                        </div>

                        <!-- ✅ MUST ACCEPT TERMS AGAIN -->
                        <div class="form-check mb-3 text-start">
                            <input class="form-check-input" type="checkbox" required id="confirmCheck">
                            <label class="form-check-label" for="confirmCheck">
                                I understand that this payment is for **CRB & identity verification only** and does not
                                guarantee loan approval. I agree to the 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#terms">Terms</a> & 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#policy">Privacy Policy</a>.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-lg btn-success w-100">
                            📲 Send Secure M-PESA Prompt
                        </button>

                    </form>

                    <p class="text-muted mt-3" style="font-size: 13px;">
                        You will receive a secure M-PESA popup on your phone. Please enter your M-PESA PIN to complete verification.
                        Do not refresh this page.
                    </p>

                </div>
            </div>

        </div>
    </div>

</div>

<!-- ✅ TERMS MODAL -->
<div class="modal fade" id="terms" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Terms & Conditions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        This platform provides CRB and identity verification services. The verification fee is non-refundable and is charged
        strictly for background checks. Loan approval, interest rates, limits and disbursement are determined only by
        independent licensed financial institutions.
      </div>
    </div>
  </div>
</div>

<!-- ✅ POLICY MODAL -->
<div class="modal fade" id="policy" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Privacy Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Your personal data and payment details are encrypted, securely stored and used strictly for CRB & identity
        verification and eligibility assessment. We do not sell your data to third parties.
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
