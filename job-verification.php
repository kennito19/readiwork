<?php 
include 'includes/layout-top.php'; 

// Fetch only 'job' service requests
try {
    $stmt = $pdo->prepare("
        SELECT id, national_id, created_at, status, result, ip_address 
        FROM verification_requests 
        WHERE service = 'job' 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats for job verification only
    $totalJob = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'job'")->fetchColumn();
    $pendingJob = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'job' AND status = 'pending'")->fetchColumn();
    $completedJob = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'job' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('Job verification page DB error: ' . $e->getMessage());
    $jobs = [];
    $totalJob = $pendingJob = $completedJob = 0;
}

// Helper to parse job verification result
function getJobVerificationStatus($resultJSON) {
    if (empty($resultJSON)) {
        return ['emp_status' => 'Pending', 'emp_badge' => 'secondary', 'verified' => '-', 'verified_badge' => ''];
    }

    $result = json_decode($resultJSON, true);
    if (!$result) {
        return ['emp_status' => 'Invalid Data', 'emp_badge' => 'danger', 'verified' => 'No', 'verified_badge' => 'danger'];
    }

    // Adjust keys based on your actual API response for job verification
    $employmentStatus = $result['employment_status'] ?? $result['status'] ?? 'Unknown';
    $verified = $result['verified'] ?? $result['confirmed'] ?? false;

    // Map employment status
    $statusMap = [
        'active' => ['text' => 'Active', 'badge' => 'success'],
        'employed' => ['text' => 'Active', 'badge' => 'success'],
        'terminated' => ['text' => 'Terminated', 'badge' => 'danger'],
        'resigned' => ['text' => 'Terminated', 'badge' => 'danger'],
        'on leave' => ['text' => 'On Leave', 'badge' => 'warning'],
        'suspended' => ['text' => 'Suspended', 'badge' => 'warning'],
        'pending' => ['text' => 'Pending', 'badge' => 'secondary'],
    ];

    $emp = $statusMap[strtolower($employmentStatus)] ?? ['text' => ucfirst($employmentStatus), 'badge' => 'info'];

    $ver = $verified ? 
        ['text' => 'Yes', 'badge' => 'success'] : 
        ['text' => 'No', 'badge' => 'danger'];

    return [
        'emp_status' => $emp['text'],
        'emp_badge' => $emp['badge'],
        'verified' => $ver['text'],
        'verified_badge' => $ver['badge']
    ];
}
?>

<h2 class="fw-bold mb-4">Job Verification</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New Job Verification (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-job-verify.php">
    <div class="col-md-5">
      <label class="form-label">Employee Full Name <span class="text-danger">*</span></label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Peter Kimani Mwangi" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">ID Number <span class="text-danger">*</span></label>
      <input type="text" name="national_id" class="form-control" placeholder="e.g. 28901234" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone Number (Optional)</label>
      <input type="text" name="phone" class="form-control" placeholder="e.g. 254734567890">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Verify</button>
    </div>
  </form>
  <small class="text-muted mt-2 d-block">Verify employment status, job title, salary, employer details, employment duration &amp; reference confirmation</small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3">
    <h5>Recent Job Verifications</h5>
    <small class="text-muted">
      Total: <?php echo number_format($totalJob); ?> | 
      Pending: <?php echo number_format($pendingJob); ?> | 
      Completed: <?php echo number_format($completedJob); ?>
    </small>
  </div>
  <p class="text-muted">Latest employment verification requests processed</p>
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date</th>
          <th>ID Number</th>
          <th>IP Address</th>
          <th>Employment Status</th>
          <th>Verified</th>
          <th>Request Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($jobs)): ?>
        <tr><td colspan="7" class="text-center text-muted">No job verifications performed yet</td></tr>
        <?php else: foreach ($jobs as $job): 
          $jv = getJobVerificationStatus($job['result']);
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($job['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($job['national_id']); ?></td>
          <td><?php echo htmlspecialchars($job['ip_address'] ?? 'Unknown'); ?></td>
          <td><span class="badge bg-<?php echo $jv['emp_badge']; ?>"><?php echo $jv['emp_status']; ?></span></td>
          <td><span class="badge bg-<?php echo $jv['verified_badge']; ?>"><?php echo $jv['verified']; ?></span></td>
          <td><span class="badge bg-<?php echo $job['status'] === 'pending' ? 'warning' : ($job['status'] === 'paid' || $job['status'] === 'completed' ? 'success' : 'danger'); ?>">
            <?php echo ucfirst($job['status']); ?>
          </span></td>
          <td>
            <a href="view-job-report.php?id=<?php echo $job['id']; ?>" class="text-info me-3" title="View Report"><i class="fa fa-eye"></i></a>
            <?php if ($job['status'] === 'completed' && $job['result']): ?>
            <a href="generate-job-pdf.php?id=<?php echo $job['id']; ?>" class="text-primary" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between mt-4">
    <p class="text-muted">Showing recent verifications</p>
    <!-- Pagination can be added later -->
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>