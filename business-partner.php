<?php 
include 'includes/layout-top.php'; 

// Business Partner Verification - likely uses company registry search (e.g., BRS API or scraping)
// Since your DB is for individual verifications, we'll assume a custom service key or new table later
// For now, use a placeholder key or adapt - change if you add 'business' service
$serviceKey = 'business'; // Set to your actual key when implemented

try {
    // If you have business checks in verification_requests (unlikely), query here
    // Otherwise, this page might need a separate table for company searches
    $stmt = $pdo->prepare("
        SELECT id, created_at, status, result, ip_address, national_id AS business_identifier 
        FROM verification_requests 
        WHERE service = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$serviceKey]);
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats (will be 0 if no data yet)
    $totalBusiness = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey'")->fetchColumn();
    $pendingBusiness = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status = 'pending'")->fetchColumn();
    $completedBusiness = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('Business partner page DB error: ' . $e->getMessage());
    $businesses = [];
    $totalBusiness = $pendingBusiness = $completedBusiness = 0;
}

// Parse business risk level from result JSON
function getBusinessRisk($resultJSON) {
    if (empty($resultJSON)) {
        return ['risk' => 'Pending', 'badge' => 'secondary'];
    }

    $result = json_decode($resultJSON, true);
    if (!$result) {
        return ['risk' => 'Invalid', 'badge' => 'danger'];
    }

    // Adjust based on your company registry/litigation API response
    $litigation = $result['litigation'] ?? $result['cases'] ?? false;
    $flags = $result['risk_flags'] ?? $result['issues_count'] ?? 0;

    if ($litigation || $flags >= 3) {
        return ['risk' => 'High', 'badge' => 'danger'];
    } elseif ($flags >= 1) {
        return ['risk' => 'Medium', 'badge' => 'warning'];
    } else {
        return ['risk' => 'Low', 'badge' => 'success'];
    }
}
?>

<h2 class="fw-bold mb-4">Business Partner Verification</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New Check (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-business-check.php">
    <div class="col-md-4">
      <label class="form-label">Business Name <span class="text-danger">*</span></label>
      <input type="text" name="business_name" class="form-control" placeholder="e.g. ABC Supplies Ltd" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">KRA PIN (Optional)</label>
      <input type="text" name="kra_pin" class="form-control" placeholder="e.g. P051234567X">
    </div>
    <div class="col-md-3">
      <label class="form-label">Registration Number (Optional)</label>
      <input type="text" name="reg_number" class="form-control" placeholder="e.g. C.123456">
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Run Check</button>
    </div>
  </form>
  <small class="text-muted mt-2 d-block">Verify company legitimacy, directors, litigation, financial health &amp; risk flags</small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3">
    <h5>Recent Business Partner Checks</h5>
    <small class="text-muted">
      Total: <?php echo number_format($totalBusiness); ?> | 
      Pending: <?php echo number_format($pendingBusiness); ?> | 
      Completed: <?php echo number_format($completedBusiness); ?>
    </small>
  </div>
  <p class="text-muted">Latest verifications performed on potential business partners and suppliers</p>
  
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date</th>
          <th>Business Identifier</th>
          <th>IP Address</th>
          <th>Risk Level</th>
          <th>Request Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($businesses)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No business partner checks performed yet</td></tr>
        <?php else: foreach ($businesses as $business): 
          $br = getBusinessRisk($business['result']);
          $identifier = $business['business_identifier'] ?? 'Unknown';
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($business['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($identifier); ?></td>
          <td><?php echo htmlspecialchars($business['ip_address'] ?? 'Unknown'); ?></td>
          <td><span class="badge bg-<?php echo $br['badge']; ?>"><?php echo $br['risk']; ?></span></td>
          <td><span class="badge bg-<?php echo $business['status'] === 'pending' ? 'warning' : ($business['status'] === 'paid' || $business['status'] === 'completed' ? 'success' : 'danger'); ?>">
            <?php echo ucfirst($business['status']); ?>
          </span></td>
          <td>
            <a href="view-business-report.php?id=<?php echo $business['id']; ?>" class="text-info me-3" title="View Report"><i class="fa fa-eye"></i></a>
            <?php if ($business['status'] === 'completed' && $business['result']): ?>
            <a href="generate-business-pdf.php?id=<?php echo $business['id']; ?>" class="text-primary" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
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