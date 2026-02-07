<?php 
include 'includes/layout-top.php'; 

// Guarantor Risk Check - we'll use 'crb' as base since it's credit-focused, or a custom key
// Change to your actual service key if different (e.g., 'guarantor' or 'loan')
$serviceKey = 'crb'; // Most likely based on credit/blacklist for guarantor risk

try {
    $stmt = $pdo->prepare("
        SELECT id, national_id, created_at, status, result, ip_address 
        FROM verification_requests 
        WHERE service = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$serviceKey]);
    $guarantors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $totalGuarantor = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey'")->fetchColumn();
    $pendingGuarantor = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status = 'pending'")->fetchColumn();
    $completedGuarantor = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('Guarantor risk page DB error: ' . $e->getMessage());
    $guarantors = [];
    $totalGuarantor = $pendingGuarantor = $completedGuarantor = 0;
}

// Parse guarantor risk level from result JSON
function getGuarantorRisk($resultJSON) {
    if (empty($resultJSON)) {
        return ['risk' => 'Pending', 'badge' => 'secondary'];
    }

    $result = json_decode($resultJSON, true);
    if (!$result) {
        return ['risk' => 'Invalid', 'badge' => 'danger'];
    }

    // Common keys from CRB/guarantor APIs
    $blacklisted = $result['blacklisted'] ?? $result['listing_status'] ?? false;
    $defaults = $result['defaults_count'] ?? $result['negative_listings'] ?? 0;
    $score = $result['score'] ?? null;

    if ($blacklisted || $defaults >= 3) {
        return ['risk' => 'High', 'badge' => 'danger'];
    } elseif ($defaults >= 1 || ($score && $score < 600)) {
        return ['risk' => 'Medium', 'badge' => 'warning'];
    } else {
        return ['risk' => 'Low', 'badge' => 'success'];
    }
}
?>

<h2 class="fw-bold mb-4">Guarantor Risk Check</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New Guarantor Check (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-guarantor-check.php">
    <div class="col-md-5">
      <label class="form-label">Guarantor Full Name (Optional)</label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Joseph Kamau Njoroge">
    </div>
    <div class="col-md-3">
      <label class="form-label">ID Number <span class="text-danger">*</span></label>
      <input type="text" name="national_id" class="form-control" placeholder="e.g. 23456789" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone Number (Optional)</label>
      <input type="text" name="phone" class="form-control" placeholder="e.g. 254723456789">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Run</button>
    </div>
  </form>
  <small class="text-muted mt-2 d-block">Evaluate guarantor's credit history, blacklist status, income stability &amp; ability to cover loan/default risk</small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3">
    <h5>Recent Guarantor Checks</h5>
    <small class="text-muted">
      Total: <?php echo number_format($totalGuarantor); ?> | 
      Pending: <?php echo number_format($pendingGuarantor); ?> | 
      Completed: <?php echo number_format($completedGuarantor); ?>
    </small>
  </div>
  <p class="text-muted">Latest risk assessments performed on loan guarantors</p>
  
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date</th>
          <th>ID Number</th>
          <th>IP Address</th>
          <th>Risk Level</th>
          <th>Request Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($guarantors)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No guarantor checks performed yet</td></tr>
        <?php else: foreach ($guarantors as $guarantor): 
          $gr = getGuarantorRisk($guarantor['result']);
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($guarantor['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($guarantor['national_id']); ?></td>
          <td><?php echo htmlspecialchars($guarantor['ip_address'] ?? 'Unknown'); ?></td>
          <td><span class="badge bg-<?php echo $gr['badge']; ?>"><?php echo $gr['risk']; ?></span></td>
          <td><span class="badge bg-<?php echo $guarantor['status'] === 'pending' ? 'warning' : ($guarantor['status'] === 'paid' || $guarantor['status'] === 'completed' ? 'success' : 'danger'); ?>">
            <?php echo ucfirst($guarantor['status']); ?>
          </span></td>
          <td>
            <a href="view-guarantor-report.php?id=<?php echo $guarantor['id']; ?>" class="text-info me-3" title="View Report"><i class="fa fa-eye"></i></a>
            <?php if ($guarantor['status'] === 'completed' && $guarantor['result']): ?>
            <a href="generate-guarantor-pdf.php?id=<?php echo $guarantor['id']; ?>" class="text-primary" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <p class="text-muted">Showing recent checks</p>
    <!-- Pagination can be added later -->
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>