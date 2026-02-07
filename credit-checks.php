<?php 
include 'includes/layout-top.php'; 

// Assuming you have admin session check here

// Fetch only 'crb' service requests (blacklist/status check)
try {
    $stmt = $pdo->prepare("
        SELECT id, national_id, created_at, status, result, ip_address 
        FROM verification_requests 
        WHERE service = 'crb' 
        ORDER BY created_at DESC 
        LIMIT 50  -- Adjust for pagination later
    ");
    $stmt->execute();
    $checks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count totals for this service
    $totalCRB = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'crb'")->fetchColumn();
    $pendingCRB = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'crb' AND status = 'pending'")->fetchColumn();
    $completedCRB = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'crb' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('CRB page DB error: ' . $e->getMessage());
    $checks = [];
    $totalCRB = $pendingCRB = $completedCRB = 0;
}

// Helper to parse result JSON for blacklist status & defaults
function getBlacklistStatus($resultJSON) {
    if (empty($resultJSON)) return ['status' => 'Pending', 'badge' => 'secondary', 'defaults' => '-'];
    
    $result = json_decode($resultJSON, true);
    if (!$result) return ['status' => 'Invalid', 'badge' => 'danger', 'defaults' => '-'];
    
    // Adjust based on your actual API result structure
    $blacklisted = $result['blacklisted'] ?? $result['listing_status'] ?? false;
    $defaults = $result['defaults_count'] ?? $result['negative_listings'] ?? 0;
    
    if ($blacklisted) {
        return ['status' => 'Blacklisted', 'badge' => 'danger', 'defaults' => $defaults];
    } elseif ($defaults > 0) {
        return ['status' => 'Watchlist', 'badge' => 'warning', 'defaults' => $defaults];
    } else {
        return ['status' => 'Clean', 'badge' => 'success', 'defaults' => 0];
    }
}
?>

<h2 class="fw-bold mb-4">Credit / Blacklist Check</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New Credit / Blacklist Check (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-crb-check.php">
    <div class="col-md-5">
      <label class="form-label">Full Name (Optional)</label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Kevin Omondi Otieno">
    </div>
    <div class="col-md-3">
      <label class="form-label">ID Number <span class="text-danger">*</span></label>
      <input type="text" name="national_id" class="form-control" placeholder="e.g. 33445566" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone Number (Optional)</label>
      <input type="text" name="phone" class="form-control" placeholder="e.g. 254798765432">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Check</button>
    </div>
  </form>
  <small class="text-muted mt-2 d-block">Instant CRB check for blacklist status, loan defaults, bounced cheques &amp; negative listings</small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3">
    <h5>Recent Credit / Blacklist Checks</h5>
    <small class="text-muted">Total: <?php echo number_format($totalCRB); ?> | Pending: <?php echo number_format($pendingCRB); ?> | Completed: <?php echo number_format($completedCRB); ?></small>
  </div>
  <p class="text-muted">Latest CRB blacklist and default verifications performed</p>
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date</th>
          <th>ID Number</th>
          <th>IP Address</th>
          <th>Blacklist Status</th>
          <th>Defaults</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($checks)): ?>
        <tr><td colspan="7" class="text-center text-muted">No CRB checks performed yet</td></tr>
        <?php else: foreach ($checks as $check): 
          $bl = getBlacklistStatus($check['result']);
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($check['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($check['national_id']); ?></td>
          <td><?php echo htmlspecialchars($check['ip_address'] ?? 'Unknown'); ?></td>
          <td><span class="badge bg-<?php echo $bl['badge']; ?>"><?php echo $bl['status']; ?></span></td>
          <td><?php echo $bl['defaults']; ?></td>
          <td><span class="badge bg-<?php echo $check['status'] === 'pending' ? 'warning' : ($check['status'] === 'paid' || $check['status'] === 'completed' ? 'success' : 'danger'); ?>">
            <?php echo ucfirst($check['status']); ?>
          </span></td>
          <td>
            <a href="view-crb-report.php?id=<?php echo $check['id']; ?>" class="text-info me-3" title="View Report"><i class="fa fa-eye"></i></a>
            <?php if ($check['status'] === 'completed' && $check['result']): ?>
            <a href="generate-crb-pdf.php?id=<?php echo $check['id']; ?>" class="text-primary" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between mt-4">
    <p class="text-muted">Showing recent checks</p>
    <!-- Add pagination links here if needed -->
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>