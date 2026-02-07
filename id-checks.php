<?php
include 'includes/layout-top.php';
// Assuming admin session check is already in layout-top.php or here

// Fetch only 'id-verification' service requests
try {
    $stmt = $pdo->prepare("
        SELECT id, national_id, full_name, dob, gender, nationality, created_at, status, result, ip_address
        FROM verification_requests
        WHERE service = 'id-verification'
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $checks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalID     = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'id-verification'")->fetchColumn();
    $pendingID   = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'id-verification' AND status = 'pending'")->fetchColumn();
    $completedID = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE service = 'id-verification' AND status IN ('paid', 'completed')")->fetchColumn();
} catch (Exception $e) {
    error_log('ID Verification page DB error: ' . $e->getMessage());
    $checks = [];
    $totalID = $pendingID = $completedID = 0;
}

// Helper: ID verification result badge - Updated for Metropol API format
function getVerificationResultBadge($resultJSON, $status) {
    if ($status === 'pending' || $status === 'pending_payment') {
        return ['text' => 'Pending', 'bg' => 'warning'];
    }
    if (empty($resultJSON) || $resultJSON === '{}') {
        return ['text' => 'No Result', 'bg' => 'secondary'];
    }

    $result = json_decode($resultJSON, true);
    if (!$result || !is_array($result)) {
        return ['text' => 'Error', 'bg' => 'danger'];
    }

    // Search through all endpoints for verification result
    foreach ($result as $endpoint => $data) {
        if (!is_array($data)) continue;
        
        // Check for Metropol API success indicators
        if (isset($data['has_error'])) {
            if ($data['has_error'] === false) {
                return ['text' => 'Valid ✓', 'bg' => 'success'];
            } else {
                return ['text' => 'Invalid', 'bg' => 'danger'];
            }
        }
        
        // Check for api_code
        if (isset($data['api_code'])) {
            $code = (int)$data['api_code'];
            if ($code === 200 || $code === 201) {
                return ['text' => 'Valid ✓', 'bg' => 'success'];
            } elseif ($code >= 400 && $code < 500) {
                return ['text' => 'Invalid', 'bg' => 'danger'];
            }
        }
        
        // Check for success field
        if (isset($data['success'])) {
            if ($data['success'] === true) {
                return ['text' => 'Valid ✓', 'bg' => 'success'];
            } else {
                return ['text' => 'Invalid', 'bg' => 'danger'];
            }
        }
        
        // Check for identity_number presence (indicates valid response)
        if (isset($data['identity_number']) || isset($data['id_number'])) {
            return ['text' => 'Valid ✓', 'bg' => 'success'];
        }
    }

    return ['text' => 'Unknown', 'bg' => 'secondary'];
}
?>

<!-- Force columns to show + better visibility -->
<style>
  .table-responsive {
    overflow-x: auto !important;
  }
  
  .table th,
  .table td {
    min-width: 100px !important;
    padding: 0.75rem 1rem !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
    display: table-cell !important;
  }
  
  /* Date column – make sure it's visible */
  .table th:first-child,
  .table td:first-child {
    min-width: 160px !important;
    background-color: rgba(30, 41, 59, 0.5) !important;
  }
  
  /* Date text – lighter & clearer */
  .table td.date-cell {
    color: #cbd5e1 !important;
    font-weight: 500 !important;
  }
  
  /* ID Number & Name – give them breathing room */
  .table th:nth-child(2),
  .table td:nth-child(2) { min-width: 140px !important; }
  .table th:nth-child(3),
  .table td:nth-child(3) { min-width: 200px !important; }
  
  /* General muted text improvement */
  .table .text-muted {
    color: #94a3b8 !important;
  }
  
  /* Name cells */
  .name-cell {
    color: #e2e8f0 !important;
    font-weight: 500 !important;
  }
  
  /* Better badge visibility */
  .badge {
    font-size: 0.8rem !important;
    padding: 0.35em 0.65em !important;
  }
</style>

<h2 class="fw-bold mb-4">ID Verification Checks</h2>

<div class="stat mb-4">
  <h5 class="mb-3">Run New ID Verification (Admin Manual)</h5>
  <form class="row g-3" method="POST" action="manual-id-check.php">
    <div class="col-md-5">
      <label class="form-label">Full Name (Optional)</label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Peter Marangi">
    </div>
    <div class="col-md-3">
      <label class="form-label">ID Number <span class="text-danger">*</span></label>
      <input type="text" name="national_id" class="form-control" placeholder="e.g. 880000088" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone Number (Optional)</label>
      <input type="text" name="phone" class="form-control" placeholder="e.g. 254712345678">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100">Verify</button>
    </div>
  </form>
  <small class="text-light mt-2 d-block" style="opacity: 0.85;">
    Instant ID verification – checks validity and basic authenticity against official records
  </small>
</div>

<div class="stat">
  <div class="d-flex justify-content-between mb-3 align-items-center">
    <h5 class="mb-0">Recent ID Verifications</h5>
    <small class="text-light" style="opacity: 0.85;">
      Total: <?= number_format($totalID) ?> 
      | Pending: <?= number_format($pendingID) ?> 
      | Completed: <?= number_format($completedID) ?>
    </small>
  </div>
  
  <p class="text-light mb-4" style="opacity: 0.85;">Latest ID number verifications performed</p>
  
  <div class="table-responsive mt-3">
    <table class="table table-dark table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>ID Number</th>
          <th>Full Name</th>
          <th>Gender</th>
          <th>DOB</th>
          <th>Verification Result</th>
          <th>Status</th>
          <th>IP Address</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($checks)): ?>
          <tr>
            <td colspan="9" class="text-center text-muted py-5">
              No ID verifications found yet
              <br><small>Service: "id-verification"</small>
            </td>
          </tr>
        <?php else: foreach ($checks as $check):
            $res = getVerificationResultBadge($check['result'], $check['status']);
        ?>
          <tr>
            <td class="date-cell small">
              <?php
              $dt = DateTime::createFromFormat('Y-m-d H:i:s', $check['created_at'] ?? '');
              echo $dt ? $dt->format('Y-m-d H:i') : '—';
              ?>
            </td>
            
            <td class="font-monospace">
              <?= htmlspecialchars($check['national_id'] ?? '—') ?>
            </td>
            
            <td class="name-cell">
              <?php
              $name = trim($check['full_name'] ?? '');
              if ($name) {
                  echo htmlspecialchars($name);
              } else {
                  echo '<span class="text-muted fst-italic">Not available</span>';
              }
              ?>
            </td>
            
            <td>
              <?php if ($check['gender']): ?>
                <span class="badge bg-<?= $check['gender'] === 'M' ? 'primary' : 'info' ?>">
                  <?= $check['gender'] === 'M' ? 'Male' : ($check['gender'] === 'F' ? 'Female' : htmlspecialchars($check['gender'])) ?>
                </span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            
            <td class="small">
              <?php 
              if ($check['dob']) {
                  $dobDt = DateTime::createFromFormat('Y-m-d', $check['dob']);
                  echo $dobDt ? $dobDt->format('Y-m-d') : htmlspecialchars($check['dob']);
              } else {
                  echo '<span class="text-muted">—</span>';
              }
              ?>
            </td>
            
            <td>
              <span class="badge bg-<?= $res['bg'] ?>">
                <?= $res['text'] ?>
              </span>
            </td>
            
            <td>
              <span class="badge bg-<?= 
                $check['status'] === 'pending' || $check['status'] === 'pending_payment' ? 'warning' : 
                ($check['status'] === 'paid' || $check['status'] === 'completed' ? 'success' : 'danger')
              ?>">
                <?= ucfirst(str_replace('_', ' ', $check['status'] ?? 'unknown')) ?>
              </span>
            </td>
            
            <td class="small text-muted">
              <?= htmlspecialchars($check['ip_address'] ?? 'Unknown') ?>
            </td>
            
            <td>
              <a href="results.php?rid=<?= $check['id'] ?>" class="text-info me-3" title="View Details">
                <i class="fa fa-eye"></i>
              </a>
              <?php if (in_array($check['status'] ?? '', ['paid','completed']) && !empty($check['result']) && $check['result'] !== '{}'): ?>
              <a href="generate-id-pdf.php?rid=<?= $check['id'] ?>" class="text-primary" title="Download PDF">
                <i class="fa fa-file-pdf"></i>
              </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between mt-4 text-light small" style="opacity: 0.8;">
    <div>Showing recent verifications (last 50)</div>
    <!-- Add pagination here later if needed -->
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>