<?php 
include 'includes/layout-top.php'; 

// Fetch only 'credit-score' service requests
try {
    $stmt = $pdo->prepare("
        SELECT id, national_id, created_at, status, result, ip_address 
        FROM verification_requests 
        WHERE service = 'credit-score' 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute();
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats for credit health only
    $totalScore = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'credit-score'")->fetchColumn();
    $pendingScore = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'credit-score' AND status = 'pending'")->fetchColumn();
    $completedScore = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'credit-score' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('Credit Health page DB error: ' . $e->getMessage());
    $scores = [];
    $totalScore = $pendingScore = $completedScore = 0;
}

// Helper to parse credit score result
function getCreditHealthData($resultJSON) {
    if (empty($resultJSON)) {
        return ['score' => '-', 'rating' => 'Pending', 'rating_badge' => 'secondary'];
    }

    $result = json_decode($resultJSON, true);
    if (!$result || !isset($result['score'])) {
        return ['score' => 'N/A', 'rating' => 'Invalid', 'rating_badge' => 'danger'];
    }

    $score = (int)$result['score'];

    // Standard credit score ranges (adjust if your API uses different scale, e.g. Metropol/TransUnion ~300-900)
    if ($score >= 750) {
        $rating = 'Excellent';
        $badge = 'success';
        $color = 'text-success';
    } elseif ($score >= 700) {
        $rating = 'Good';
        $badge = 'success';
        $color = 'text-success';
    } elseif ($score >= 600) {
        $rating = 'Fair';
        $badge = 'warning';
        $color = 'text-warning';
    } else {
        $rating = 'Poor';
        $badge = 'danger';
        $color = 'text-danger';
    }

    return [
        'score' => $score,
        'score_color' => $color,
        'rating' => $rating,
        'rating_badge' => $badge
    ];
}
?>

<h2 class="fw-bold mb-4">Credit Health Score</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New Credit Health Check (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-credit-score.php">
    <div class="col-md-5">
      <label class="form-label">Full Name (Optional)</label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Naomi Chelangat">
    </div>
    <div class="col-md-3">
      <label class="form-label">ID Number <span class="text-danger">*</span></label>
      <input type="text" name="national_id" class="form-control" placeholder="e.g. 37890123" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone Number (Optional)</label>
      <input type="text" name="phone" class="form-control" placeholder="e.g. 254712345678">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Check</button>
    </div>
  </form>
  <small class="text-muted mt-2 d-block">Retrieve detailed credit score, repayment history, active loans, defaults &amp; overall credit health rating from CRB</small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3">
    <h5>Recent Credit Health Checks</h5>
    <small class="text-muted">
      Total: <?php echo number_format($totalScore); ?> | 
      Pending: <?php echo number_format($pendingScore); ?> | 
      Completed: <?php echo number_format($completedScore); ?>
    </small>
  </div>
  <p class="text-muted">Latest credit score and health reports generated</p>
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date</th>
          <th>ID Number</th>
          <th>IP Address</th>
          <th>Credit Score</th>
          <th>Health Rating</th>
          <th>Request Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($scores)): ?>
        <tr><td colspan="7" class="text-center text-muted">No credit health checks performed yet</td></tr>
        <?php else: foreach ($scores as $check): 
          $ch = getCreditHealthData($check['result']);
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($check['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($check['national_id']); ?></td>
          <td><?php echo htmlspecialchars($check['ip_address'] ?? 'Unknown'); ?></td>
          <td class="fw-bold <?php echo $ch['score_color']; ?>"><?php echo $ch['score']; ?></td>
          <td><span class="badge bg-<?php echo $ch['rating_badge']; ?>"><?php echo $ch['rating']; ?></span></td>
          <td><span class="badge bg-<?php echo $check['status'] === 'pending' ? 'warning' : ($check['status'] === 'paid' || $check['status'] === 'completed' ? 'success' : 'danger'); ?>">
            <?php echo ucfirst($check['status']); ?>
          </span></td>
          <td>
            <a href="view-credit-score-report.php?id=<?php echo $check['id']; ?>" class="text-info me-3" title="View Report"><i class="fa fa-eye"></i></a>
            <?php if ($check['status'] === 'completed' && $check['result']): ?>
            <a href="generate-credit-score-pdf.php?id=<?php echo $check['id']; ?>" class="text-primary" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between mt-4">
    <p class="text-muted">Showing recent checks</p>
    <!-- Add pagination if needed -->
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>