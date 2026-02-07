<?php 
include 'includes/layout-top.php'; 

// Buyer Risk Assessment - typically credit-based like guarantor/loan
// We'll use 'crb' as the service key (common for buyer credit checks)
// Change to your actual key if different (e.g., 'buyer' or 'credit-score')
$serviceKey = 'crb';

try {
    $stmt = $pdo->prepare("
        SELECT id, national_id, created_at, status, result, ip_address 
        FROM verification_requests 
        WHERE service = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$serviceKey]);
    $buyers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $totalBuyer = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey'")->fetchColumn();
    $pendingBuyer = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status = 'pending'")->fetchColumn();
    $completedBuyer = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('Buyer risk page DB error: ' . $e->getMessage());
    $buyers = [];
    $totalBuyer = $pendingBuyer = $completedBuyer = 0;
}

// Parse buyer risk level from result JSON
function getBuyerRisk($resultJSON) {
    if (empty($resultJSON)) {
        return ['risk' => 'Pending', 'badge' => 'secondary'];
    }

    $result = json_decode($resultJSON, true);
    if (!$result) {
        return ['risk' => 'Invalid', 'badge' => 'danger'];
    }

    // Standard CRB indicators for buyer risk
    $blacklisted = $result['blacklisted'] ?? $result['listing_status'] ?? false;
    $defaults = $result['defaults_count'] ?? $result['negative_listings'] ?? 0;
    $score = $result['score'] ?? null;

    if ($blacklisted || $defaults >= 3 || ($score && $score < 500)) {
        return ['risk' => 'High', 'badge' => 'danger'];
    } elseif ($defaults >= 1 || ($score && $score < 650)) {
        return ['risk' => 'Medium', 'badge' => 'warning'];
    } else {
        return ['risk' => 'Low', 'badge' => 'success'];
    }
}
?>

<h2 class="fw-bold mb-4">Buyer Risk Assessment</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New Buyer Risk Check (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-buyer-check.php">
    <div class="col-md-5">
      <label class="form-label">Buyer Full Name (Optional)</label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Jane Wambui Mwangi">
    </div>
    <div class="col-md-3">
      <label class="form-label">ID Number <span class="text-danger">*</span></label>
      <input type="text" name="national_id" class="form-control" placeholder="e.g. 34567890" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone Number (Optional)</label>
      <input type="text" name="phone" class="form-control" placeholder="e.g. 254712345678">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Run</button>
    </div>
  </form>
  <small class="text-muted mt-2 d-block">Assess buyer creditworthiness, blacklist status, payment history &amp; risk of default for asset financing, hire purchase or large sales</small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3">
    <h5>Recent Buyer Risk Checks</h5>
    <small class="text-muted">
      Total: <?php echo number_format($totalBuyer); ?> | 
      Pending: <?php echo number_format($pendingBuyer); ?> | 
      Completed: <?php echo number_format($completedBuyer); ?>
    </small>
  </div>
  <p class="text-muted">Latest assessments performed on potential buyers</p>
  
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
        <?php if (empty($buyers)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No buyer risk checks performed yet</td></tr>
        <?php else: foreach ($buyers as $buyer): 
          $br = getBuyerRisk($buyer['result']);
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($buyer['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($buyer['national_id']); ?></td>
          <td><?php echo htmlspecialchars($buyer['ip_address'] ?? 'Unknown'); ?></td>
          <td><span class="badge bg-<?php echo $br['badge']; ?>"><?php echo $br['risk']; ?></span></td>
          <td><span class="badge bg-<?php echo $buyer['status'] === 'pending' ? 'warning' : ($buyer['status'] === 'paid' || $buyer['status'] === 'completed' ? 'success' : 'danger'); ?>">
            <?php echo ucfirst($buyer['status']); ?>
          </span></td>
          <td>
            <a href="view-buyer-report.php?id=<?php echo $buyer['id']; ?>" class="text-info me-3" title="View Report"><i class="fa fa-eye"></i></a>
            <?php if ($buyer['status'] === 'completed' && $buyer['result']): ?>
            <a href="generate-buyer-pdf.php?id=<?php echo $buyer['id']; ?>" class="text-primary" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
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