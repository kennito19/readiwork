<?php 
include 'includes/layout-top.php'; 

// Create mpesa_logs table if you haven't (run once):
// CREATE TABLE mpesa_logs (
//   id BIGINT AUTO_INCREMENT PRIMARY KEY,
//   phone VARCHAR(20),
//   receipt_no VARCHAR(50),
//   transaction_type VARCHAR(50),
//   amount DECIMAL(10,2),
//   name VARCHAR(100),
//   description TEXT,
//   status ENUM('Success','Pending','Failed') DEFAULT 'Pending',
//   raw_json LONGTEXT,
//   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//   INDEX(receipt_no), INDEX(phone), INDEX(created_at)
// );

try {
    // Simple filters
    $where = '';
    $params = [];
    if (!empty($_GET['phone'])) {
        $where .= " AND phone LIKE ?";
        $params[] = '%' . $_GET['phone'] . '%';
    }
    if (!empty($_GET['receipt'])) {
        $where .= " AND receipt_no LIKE ?";
        $params[] = '%' . $_GET['receipt'] . '%';
    }
    if (!empty($_GET['date'])) {
        $where .= " AND DATE(created_at) = ?";
        $params[] = $_GET['date'];
    }
    if (!empty($_GET['type']) && $_GET['type'] !== 'All Types') {
        $where .= " AND transaction_type = ?";
        $params[] = $_GET['type'];
    }
    if (!empty($_GET['status']) && $_GET['status'] !== 'All Status') {
        $where .= " AND status = ?";
        $params[] = $_GET['status'];
    }

    $where = $where ? 'WHERE 1' . $where : '';

    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM mpesa_logs $where");
    $countStmt->execute($params);
    $totalLogs = $countStmt->fetchColumn();

    // Fetch logs
    $stmt = $pdo->prepare("
        SELECT * FROM mpesa_logs 
        $where 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('M-Pesa logs error: ' . $e->getMessage());
    $logs = [];
    $totalLogs = 0;
}
?>

<h2 class="fw-bold mb-4">M-Pesa Logs</h2>

<div class="stat mb-4">
  <div class="row">
    <div class="col-md-8">
      <h5 class="mb-3">Search & Filter Logs</h5>
      <form class="row g-3" method="GET">
        <div class="col-md-4">
          <input type="text" name="phone" class="form-control" placeholder="Phone number (e.g. 254712345678)" value="<?php echo htmlspecialchars($_GET['phone'] ?? ''); ?>">
        </div>
        <div class="col-md-4">
          <input type="text" name="receipt" class="form-control" placeholder="Transaction ID / Receipt No." value="<?php echo htmlspecialchars($_GET['receipt'] ?? ''); ?>">
        </div>
        <div class="col-md-4">
          <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">
        </div>
        <div class="col-md-6">
          <select name="type" class="form-select">
            <option value="">All Types</option>
            <option value="C2B Payment" <?php echo ($_GET['type'] ?? '') === 'C2B Payment' ? 'selected' : ''; ?>>C2B Payment</option>
            <option value="B2C Payout" <?php echo ($_GET['type'] ?? '') === 'B2C Payout' ? 'selected' : ''; ?>>B2C Payout</option>
            <option value="STK Push" <?php echo ($_GET['type'] ?? '') === 'STK Push' ? 'selected' : ''; ?>>STK Push</option>
            <option value="Reversal" <?php echo ($_GET['type'] ?? '') === 'Reversal' ? 'selected' : ''; ?>>Reversal</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="Success" <?php echo ($_GET['status'] ?? '') === 'Success' ? 'selected' : ''; ?>>Success</option>
            <option value="Failed" <?php echo ($_GET['status'] ?? '') === 'Failed' ? 'selected' : ''; ?>>Failed</option>
            <option value="Pending" <?php echo ($_GET['status'] ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-success w-100">Apply Filters</button>
        </div>
      </form>
    </div>
    <div class="col-md-4 text-end">
      <h5 class="mb-3 opacity-0">spacer</h5>
      <div>
        <button class="btn btn-outline-light me-2" onclick="window.location='export-mpesa-csv.php<?php echo $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>'">
          <i class="fa fa-file-export me-1"></i> Export CSV
        </button>
        <button class="btn btn-outline-light" onclick="location.reload()">
          <i class="fa fa-sync me-1"></i> Refresh
        </button>
      </div>
    </div>
  </div>
</div>

<div class="stat">
  <h5>M-Pesa Transaction Logs (<?php echo number_format($totalLogs); ?> total)</h5>
  <p class="text-muted">All incoming payments, STK pushes, payouts and reversals</p>
  
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>Phone</th>
          <th>Receipt No.</th>
          <th>Type</th>
          <th>Amount (KES)</th>
          <th>Name</th>
          <th>Description</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr>
          <td colspan="9" class="text-center text-muted py-4">
            <?php echo $totalLogs == 0 ? 'No M-Pesa transactions recorded yet.' : 'No logs match your filters.'; ?>
          </td>
        </tr>
        <?php else: foreach ($logs as $log): ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($log['phone']); ?></td>
          <td><?php echo htmlspecialchars($log['receipt_no']); ?></td>
          <td><?php echo htmlspecialchars($log['transaction_type']); ?></td>
          <td class="<?php echo $log['amount'] < 0 ? 'text-danger' : 'text-success'; ?> fw-bold">
            <?php echo number_format(abs($log['amount']), 2); ?>
          </td>
          <td><?php echo htmlspecialchars($log['name'] ?: '-'); ?></td>
          <td><?php echo htmlspecialchars($log['description'] ?: '-'); ?></td>
          <td>
            <span class="badge bg-<?php 
              echo $log['status'] === 'Success' ? 'success' : 
                   ($log['status'] === 'Pending' ? 'warning' : 'danger'); 
            ?>">
              <?php echo $log['status']; ?>
            </span>
          </td>
          <td>
            <a href="view-mpesa-log.php?id=<?php echo $log['id']; ?>" class="text-info me-2" title="View Details">
              <i class="fa fa-eye"></i>
            </a>
            <a href="#" class="text-primary" title="View Raw JSON" onclick="event.preventDefault(); showJson(<?php echo $log['id']; ?>)">
              <i class="fa fa-code"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <p class="text-muted">Showing up to 50 recent logs</p>
    <nav>
      <ul class="pagination pagination-sm">
        <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">Next</a></li>
      </ul>
    </nav>
  </div>
</div>

<!-- Modal for Raw JSON (optional but nice) -->
<div class="modal fade" id="jsonModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title">Raw M-Pesa Callback JSON</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <pre id="jsonContent" class="bg-black p-3 rounded" style="max-height: 70vh; overflow: auto;"></pre>
      </div>
    </div>
  </div>
</div>

<script>
function showJson(logId) {
  fetch('get-mpesa-json.php?id=' + logId)
    .then(response => response.json())
    .then(data => {
      document.getElementById('jsonContent').textContent = JSON.stringify(data, null, 2);
      new bootstrap.Modal(document.getElementById('jsonModal')).show();
    })
    .catch(() => {
      alert('Failed to load JSON');
    });
}
</script>

<?php include 'includes/layout-bottom.php'; ?>