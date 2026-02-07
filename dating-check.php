<?php 
include 'includes/layout-top.php'; 

// Dating / Background Check - using 'background' as the service key (from your original services)
$serviceKey = 'background';

try {
    $stmt = $pdo->prepare("
        SELECT id, national_id, created_at, status, result, ip_address 
        FROM verification_requests 
        WHERE service = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$serviceKey]);
    $dating = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $totalDating = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey'")->fetchColumn();
    $pendingDating = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status = 'pending'")->fetchColumn();
    $completedDating = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('Dating background page DB error: ' . $e->getMessage());
    $dating = [];
    $totalDating = $pendingDating = $completedDating = 0;
}

// Parse risk flags from result JSON
function getDatingRiskFlags($resultJSON) {
    if (empty($resultJSON)) {
        return ['flags' => 'Pending', 'badge' => 'secondary'];
    }

    $result = json_decode($resultJSON, true);
    if (!$result) {
        return ['flags' => 'Invalid', 'badge' => 'danger'];
    }

    // Adjust keys based on your actual dating/background API response
    $flagsCount = $result['flags_count'] ?? $result['risk_flags'] ?? $result['issues'] ?? 0;
    $critical = $result['critical_flag'] ?? $result['criminal'] ?? $result['high_risk'] ?? false;

    if ($critical) {
        return ['flags' => 'Critical', 'badge' => 'danger'];
    } elseif ($flagsCount >= 2) {
        return ['flags' => "$flagsCount Minor", 'badge' => 'warning'];
    } elseif ($flagsCount == 1) {
        return ['flags' => '1 Minor', 'badge' => 'warning'];
    } else {
        return ['flags' => 'None', 'badge' => 'success'];
    }
}
?>

<h2 class="fw-bold mb-4">Dating / Background Check</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New Dating Background Check (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-dating-check.php">
    <div class="col-md-5">
      <label class="form-label">Full Name (Optional)</label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Brian Otieno Mwangi">
    </div>
    <div class="col-md-3">
      <label class="form-label">ID Number (Optional)</label>
      <input type="text" name="national_id" class="form-control" placeholder="e.g. 37890123">
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone Number <span class="text-danger">*</span></label>
      <input type="text" name="phone" class="form-control" placeholder="e.g. 254756789012" required>
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Run</button>
    </div>
  </form>
  <small class="text-muted mt-2 d-block">Comprehensive background check including criminal records, marriage status, social media flags, known aliases &amp; safety risk indicators</small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3">
    <h5>Recent Dating / Background Checks</h5>
    <small class="text-muted">
      Total: <?php echo number_format($totalDating); ?> | 
      Pending: <?php echo number_format($pendingDating); ?> | 
      Completed: <?php echo number_format($completedDating); ?>
    </small>
  </div>
  <p class="text-muted">Latest personal background verifications performed</p>
  
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date</th>
          <th>ID Number</th>
          <th>Phone / IP</th>
          <th>Risk Flags</th>
          <th>Request Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($dating)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No dating background checks performed yet</td></tr>
        <?php else: foreach ($dating as $check): 
          $rf = getDatingRiskFlags($check['result']);
          $displayId = $check['national_id'] ?: '-';
          $displayContact = $check['national_id'] ? htmlspecialchars($check['national_id']) : (htmlspecialchars($check['ip_address'] ?? 'Unknown'));
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($check['created_at'])); ?></td>
          <td><?php echo $displayId; ?></td>
          <td><?php echo $displayContact; ?></td>
          <td><span class="badge bg-<?php echo $rf['badge']; ?>"><?php echo $rf['flags']; ?></span></td>
          <td><span class="badge bg-<?php echo $check['status'] === 'pending' ? 'warning' : ($check['status'] === 'paid' || $check['status'] === 'completed' ? 'success' : 'danger'); ?>">
            <?php echo ucfirst($check['status']); ?>
          </span></td>
          <td>
            <a href="view-dating-report.php?id=<?php echo $check['id']; ?>" class="text-info me-3" title="View Report"><i class="fa fa-eye"></i></a>
            <?php if ($check['status'] === 'completed' && $check['result']): ?>
            <a href="generate-dating-pdf.php?id=<?php echo $check['id']; ?>" class="text-primary" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
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