<?php 
include 'includes/layout-top.php'; 

// Domestic staff verification - assuming a custom service key (not in your original list)
// We'll use 'background' as it's closest (safety screening), or change to your actual key e.g. 'domestic'
$serviceKey = 'background'; // Change if you have a specific key like 'domestic' or 'staff'

try {
    $stmt = $pdo->prepare("
        SELECT id, national_id, created_at, status, result, ip_address 
        FROM verification_requests 
        WHERE service = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$serviceKey]);
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $totalStaff = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey'")->fetchColumn();
    $pendingStaff = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status = 'pending'")->fetchColumn();
    $completedStaff = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('Domestic staff page DB error: ' . $e->getMessage());
    $staff = [];
    $totalStaff = $pendingStaff = $completedStaff = 0;
}

// Parse risk flags from result JSON
function getRiskFlags($resultJSON) {
    if (empty($resultJSON)) {
        return ['flags' => 'Pending', 'badge' => 'secondary'];
    }

    $result = json_decode($resultJSON, true);
    if (!$result) {
        return ['flags' => 'Invalid', 'badge' => 'danger'];
    }

    // Adjust keys based on your actual domestic/background API response
    $flagsCount = $result['flags_count'] ?? $result['risk_flags'] ?? $result['issues'] ?? 0;
    $critical = $result['critical'] ?? $result['criminal'] ?? false;

    if ($critical || $flagsCount >= 3) {
        return ['flags' => 'Critical', 'badge' => 'danger'];
    } elseif ($flagsCount >= 1) {
        return ['flags' => "$flagsCount Minor", 'badge' => 'warning'];
    } else {
        return ['flags' => 'None', 'badge' => 'success'];
    }
}
?>

<h2 class="fw-bold mb-4">Domestic Staff Verification</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New Domestic Staff Check (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-domestic-check.php">
    <div class="col-md-5">
      <label class="form-label">Full Name (Optional)</label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Mercy Akinyi Omondi">
    </div>
    <div class="col-md-3">
      <label class="form-label">ID Number <span class="text-danger">*</span></label>
      <input type="text" name="national_id" class="form-control" placeholder="e.g. 36789012" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone Number (Optional)</label>
      <input type="text" name="phone" class="form-control" placeholder="e.g. 254745678901">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Run</button>
    </div>
  </form>
  <small class="text-muted mt-2 d-block">Background check for domestic workers: criminal records, references, previous employment, identity verification &amp; safety risk assessment</small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3">
    <h5>Recent Domestic Staff Checks</h5>
    <small class="text-muted">
      Total: <?php echo number_format($totalStaff); ?> | 
      Pending: <?php echo number_format($pendingStaff); ?> | 
      Completed: <?php echo number_format($completedStaff); ?>
    </small>
  </div>
  <p class="text-muted">Latest verifications performed for househelps, nannies, gardeners &amp; drivers</p>
  
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date</th>
          <th>ID Number</th>
          <th>IP Address</th>
          <th>Risk Flags</th>
          <th>Request Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($staff)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No domestic staff checks performed yet</td></tr>
        <?php else: foreach ($staff as $check): 
          $rf = getRiskFlags($check['result']);
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($check['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($check['national_id']); ?></td>
          <td><?php echo htmlspecialchars($check['ip_address'] ?? 'Unknown'); ?></td>
          <td><span class="badge bg-<?php echo $rf['badge']; ?>"><?php echo $rf['flags']; ?></span></td>
          <td><span class="badge bg-<?php echo $check['status'] === 'pending' ? 'warning' : ($check['status'] === 'paid' || $check['status'] === 'completed' ? 'success' : 'danger'); ?>">
            <?php echo ucfirst($check['status']); ?>
          </span></td>
          <td>
            <a href="view-domestic-report.php?id=<?php echo $check['id']; ?>" class="text-info me-3" title="View Report"><i class="fa fa-eye"></i></a>
            <?php if ($check['status'] === 'completed' && $check['result']): ?>
            <a href="generate-domestic-pdf.php?id=<?php echo $check['id']; ?>" class="text-primary" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
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