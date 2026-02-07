<?php 
include 'includes/layout-top.php'; 

// Correct service key for loan eligibility
$serviceKey = 'loan-eligibility'; // Updated from 'loan'

try {
    $stmt = $pdo->prepare("
        SELECT id, national_id, full_name, dob, gender, created_at, status, result, ip_address 
        FROM verification_requests 
        WHERE service = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$serviceKey]);
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $totalLoan = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey'")->fetchColumn();
    $pendingLoan = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status = 'pending'")->fetchColumn();
    $completedLoan = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('Loan eligibility page DB error: ' . $e->getMessage());
    $loans = [];
    $totalLoan = $pendingLoan = $completedLoan = 0;
}

// Parse loan eligibility from result JSON
function getLoanEligibility($resultJSON) {
    if (empty($resultJSON)) {
        return ['eligibility' => 'Pending', 'badge' => 'secondary', 'amount' => 0, 'amount_class' => '', 'formatted_amount' => '0'];
    }

    $result = json_decode($resultJSON, true);
    if (!$result || !is_array($result)) {
        return ['eligibility' => 'Invalid', 'badge' => 'danger', 'amount' => 0, 'amount_class' => '', 'formatted_amount' => '0'];
    }

    // Search through all endpoints for eligibility data
    $eligible = false;
    $amount = 0;
    $riskScore = null;
    $creditScore = null;
    
    foreach ($result as $endpoint => $data) {
        if (!is_array($data)) continue;
        
        // Check for eligibility indicators
        if (isset($data['eligible'])) {
            $eligible = $data['eligible'];
        }
        if (isset($data['eligibility_status'])) {
            $eligible = ($data['eligibility_status'] === 'eligible' || $data['eligibility_status'] === true);
        }
        if (isset($data['is_eligible'])) {
            $eligible = $data['is_eligible'];
        }
        
        // Check for amount
        if (isset($data['max_amount'])) {
            $amount = max($amount, (int)$data['max_amount']);
        }
        if (isset($data['recommended_amount'])) {
            $amount = max($amount, (int)$data['recommended_amount']);
        }
        if (isset($data['eligible_amount'])) {
            $amount = max($amount, (int)$data['eligible_amount']);
        }
        if (isset($data['maximum_loan_amount'])) {
            $amount = max($amount, (int)$data['maximum_loan_amount']);
        }
        
        // Check for credit score (as alternative eligibility indicator)
        if (isset($data['credit_score'])) {
            $creditScore = (int)$data['credit_score'];
        }
        if (isset($data['metro_score'])) {
            $creditScore = (int)$data['metro_score'];
        }
        
        // Check for risk score
        if (isset($data['risk_score'])) {
            $riskScore = $data['risk_score'];
        }
    }
    
    // Determine eligibility based on collected data
    if ($eligible === true || $eligible === 'yes' || $amount > 0) {
        if ($amount >= 100000) {
            $elig = 'High Eligible';
            $badge = 'success';
            $class = 'text-success fw-bold';
        } elseif ($amount >= 50000) {
            $elig = 'Eligible';
            $badge = 'success';
            $class = 'text-success fw-bold';
        } elseif ($amount > 0) {
            $elig = 'Limited';
            $badge = 'warning';
            $class = 'text-warning fw-bold';
        } else {
            $elig = 'Eligible';
            $badge = 'success';
            $class = 'text-success fw-bold';
        }
    } elseif ($creditScore && $creditScore >= 600) {
        // Use credit score as fallback indicator
        $elig = 'Likely Eligible';
        $badge = 'info';
        $class = 'text-info';
        $amount = 0; // Amount not specified
    } else {
        $elig = 'Not Eligible';
        $badge = 'danger';
        $class = 'text-danger';
        $amount = 0;
    }

    return [
        'eligibility' => $elig,
        'badge' => $badge,
        'amount' => $amount,
        'amount_class' => $class,
        'formatted_amount' => number_format($amount),
        'credit_score' => $creditScore,
        'risk_score' => $riskScore
    ];
}
?>

<h2 class="fw-bold mb-4">Loan Eligibility Check</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New Loan Eligibility Check (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-loan-eligibility.php">
    <div class="col-md-5">
      <label class="form-label">Applicant Full Name (Optional)</label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Thomas Kipchoge Rotich">
    </div>
    <div class="col-md-3">
      <label class="form-label">ID Number <span class="text-danger">*</span></label>
      <input type="text" name="national_id" class="form-control" placeholder="e.g. 31234567" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone Number (Optional)</label>
      <input type="text" name="phone" class="form-control" placeholder="e.g. 254723456789">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Check</button>
    </div>
  </form>
  <small class="text-light opacity-75 mt-2 d-block">Determine loan eligibility, recommended amount, interest rate tier &amp; risk score based on CRB data, income, defaults &amp; credit history</small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3">
    <h5>Recent Loan Eligibility Checks</h5>
    <small class="text-light opacity-75">
      Total: <?php echo number_format($totalLoan); ?> | 
      Pending: <?php echo number_format($pendingLoan); ?> | 
      Completed: <?php echo number_format($completedLoan); ?>
    </small>
  </div>
  <p class="text-light opacity-75">Latest eligibility assessments performed for loan applicants</p>
  
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date</th>
          <th>Full Name</th>
          <th>ID Number</th>
          <th>Gender</th>
          <th>IP Address</th>
          <th>Eligibility</th>
          <th>Max Amount (KES)</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($loans)): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">
          No loan eligibility checks found. 
          <br><small>Service key: "<?php echo htmlspecialchars($serviceKey); ?>"</small>
        </td></tr>
        <?php else: foreach ($loans as $loan): 
          $le = getLoanEligibility($loan['result']);
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($loan['created_at'])); ?></td>
          <td>
            <strong><?php echo htmlspecialchars($loan['full_name'] ?: 'N/A'); ?></strong>
            <?php if (!$loan['full_name']): ?>
              <small class="text-muted d-block">Name not available</small>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($loan['national_id']); ?></td>
          <td>
            <?php if ($loan['gender']): ?>
              <span class="badge bg-<?php echo $loan['gender'] === 'M' ? 'primary' : 'info'; ?>">
                <?php echo $loan['gender'] === 'M' ? 'Male' : ($loan['gender'] === 'F' ? 'Female' : htmlspecialchars($loan['gender'])); ?>
              </span>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($loan['ip_address'] ?? 'Unknown'); ?></td>
          <td>
            <span class="badge bg-<?php echo $le['badge']; ?>"><?php echo $le['eligibility']; ?></span>
            <?php if ($le['credit_score']): ?>
              <br><small class="text-muted">Score: <?php echo $le['credit_score']; ?>/900</small>
            <?php endif; ?>
          </td>
          <td class="<?php echo $le['amount_class']; ?>">
            <?php echo $le['amount'] > 0 ? 'KES ' . $le['formatted_amount'] : '-'; ?>
          </td>
          <td>
            <span class="badge bg-<?php 
              echo $loan['status'] === 'pending' ? 'warning' : 
                   ($loan['status'] === 'paid' || $loan['status'] === 'completed' ? 'success' : 'danger'); 
            ?>">
              <?php echo ucfirst($loan['status']); ?>
            </span>
          </td>
          <td>
            <a href="results.php?rid=<?php echo $loan['id']; ?>" class="text-info me-3" title="View Report">
              <i class="fa fa-eye"></i>
            </a>
            <?php if ($loan['status'] === 'completed' && $loan['result']): ?>
            <a href="generate-id-pdf.php?rid=<?php echo $loan['id']; ?>" class="text-primary" title="Download PDF">
              <i class="fa fa-file-pdf"></i>
            </a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <p class="text-light opacity-75">Showing recent checks (last 50)</p>
    <!-- Pagination can be added later -->
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>