<?php 
include 'includes/layout-top.php'; 

// Tenant verification service key - change if yours is different
$serviceKey = 'tenant'; // Matches your verification.html services array

try {
    $stmt = $pdo->prepare("
        SELECT id, national_id, created_at, status, result, ip_address 
        FROM verification_requests 
        WHERE service = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$serviceKey]);
    $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats for tenant verification
    $totalTenant = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey'")->fetchColumn();
    $pendingTenant = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status = 'pending'")->fetchColumn();
    $completedTenant = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = '$serviceKey' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('Tenant verification page DB error: ' . $e->getMessage());
    $tenants = [];
    $totalTenant = $pendingTenant = $completedTenant = 0;
}

// Parse tenant risk level from result JSON
function getTenantRisk($resultJSON) {
    if (empty($resultJSON)) {
        return ['risk' => 'Pending', 'badge' => 'secondary'];
    }

    $result = json_decode($resultJSON, true);
    if (!$result) {
        return ['risk' => 'Invalid', 'badge' => 'danger'];
    }

    // Adjust these keys based on your actual tenant API response
    $riskLevel = $result['risk_level'] ?? $result['risk'] ?? $result['assessment'] ?? 'unknown';
    $riskLevel = strtolower($riskLevel);

    switch ($riskLevel) {
        case 'low':
        case 'good':
        case 'safe':
            return ['risk' => 'Low', 'badge' => 'success'];
        case 'medium':
        case 'moderate':
        case 'fair':
            return ['risk' => 'Medium', 'badge' => 'warning'];
        case 'high':
        case 'bad':
        case 'risky':
            return ['risk' => 'High', 'badge' => 'danger'];
        default:
            return ['risk' => ucfirst($riskLevel), 'badge' => 'info'];
    }
}
?>

<h2 class="fw-bold mb-4">Tenant Verification</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New Tenant Check (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-tenant-check.php">
    <div class="col-md-5">
      <label class="form-label">Tenant Full Name (Optional)</label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Alice Njeri Kamau">
    </div>
    <div class="col-md-3">
      <label class="form-label">ID Number <span class="text-danger">*</span></label>
      <input type="text" name="national_id" class="form-control" placeholder="e.g. 35678901" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone Number (Optional)</label>
      <input type="text" name="phone" class="form-control" placeholder="e.g. 254756789012">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Run</button>
    </div>
  </form>
  <small class="text-muted mt-2 d-block">Verify tenant reliability: credit history, eviction records, previous landlord references, employment &amp; payment risk assessment</small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3">
    <h5>Recent Tenant Verifications</h5>
    <small class="text-muted">
      Total: <?php echo number_format($totalTenant); ?> | 
      Pending: <?php echo number_format($pendingTenant); ?> | 
      Completed: <?php echo number_format($completedTenant); ?>
    </small>
  </div>
  <p class="text-muted">Latest background checks performed on prospective tenants</p>
  
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
        <?php if (empty($tenants)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No tenant verifications performed yet</td></tr>
        <?php else: foreach ($tenants as $tenant): 
          $tr = getTenantRisk($tenant['result']);
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($tenant['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($tenant['national_id']); ?></td>
          <td><?php echo htmlspecialchars($tenant['ip_address'] ?? 'Unknown'); ?></td>
          <td><span class="badge bg-<?php echo $tr['badge']; ?>"><?php echo $tr['risk']; ?></span></td>
          <td><span class="badge bg-<?php echo $tenant['status'] === 'pending' ? 'warning' : ($tenant['status'] === 'paid' || $tenant['status'] === 'completed' ? 'success' : 'danger'); ?>">
            <?php echo ucfirst($tenant['status']); ?>
          </span></td>
          <td>
            <a href="view-tenant-report.php?id=<?php echo $tenant['id']; ?>" class="text-info me-3" title="View Report"><i class="fa fa-eye"></i></a>
            <?php if ($tenant['status'] === 'completed' && $tenant['result']): ?>
            <a href="generate-tenant-pdf.php?id=<?php echo $tenant['id']; ?>" class="text-primary" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <p class="text-muted">Showing recent checks</p>
    <!-- Add pagination later if needed -->
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>