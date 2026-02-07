<?php 
include 'includes/layout-top.php'; 

// Transactions page - fetch from verification_requests where paid/completed (price > 0)
// Add a payments table later for M-Pesa callbacks, receipts, etc.
// For now, use verification_requests (status paid/completed = successful payment)

try {
    // Basic filters (expand with GET params later)
    $where = "WHERE status IN ('paid', 'completed')";
    $params = [];

    // Total revenue from successful payments
    $revenueStmt = $pdo->query("SELECT COALESCE(SUM(price), 0) FROM verification_requests $where");
    $totalRevenue = $revenueStmt->fetchColumn();

    // Recent transactions
    $stmt = $pdo->prepare("
        SELECT id, national_id, service, price, status, created_at, ip_address 
        FROM verification_requests 
        $where 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalTransactions = count($transactions); // Approximate
} catch (Exception $e) {
    error_log('Transactions page DB error: ' . $e->getMessage());
    $transactions = [];
    $totalRevenue = 0;
    $totalTransactions = 0;
}

// Map service to nice name
$serviceNames = [
    'crb' => 'Credit / Blacklist',
    'credit-score' => 'Credit Health Score',
    'tenant' => 'Tenant Verification',
    'job' => 'Job Verification',
    'background' => 'Dating / Background',
    'loan' => 'Loan Eligibility',
    'domestic' => 'Domestic Staff',
    'guarantor' => 'Guarantor Risk',
    'buyer' => 'Buyer Risk',
    'business' => 'Business Partner',
];
?>

<h2 class="fw-bold mb-4">Transactions</h2>

<div class="stat mb-4">
  <div class="row">
    <div class="col-md-9">
      <h5 class="mb-3">Search & Filter Transactions</h5>
      <form class="row g-3" method="GET">
        <div class="col-md-3">
          <input type="text" name="user" class="form-control" placeholder="User name or email">
        </div>
        <div class="col-md-3">
          <input type="text" name="phone" class="form-control" placeholder="Phone number">
        </div>
        <div class="col-md-3">
          <input type="text" name="receipt" class="form-control" placeholder="Receipt / Transaction ID">
        </div>
        <div class="col-md-3">
          <select name="service" class="form-select">
            <option value="">All Services</option>
            <?php foreach ($serviceNames as $key => $name): ?>
            <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <input type="date" name="from" class="form-control">
        </div>
        <div class="col-md-3">
          <input type="date" name="to" class="form-control">
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="completed">Completed</option>
            <option value="paid">Paid</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-success w-100">Apply Filters</button>
        </div>
      </form>
    </div>
    <div class="col-md-3 text-end">
      <h5 class="mb-3 opacity-0">spacer</h5>
      <div>
        <button class="btn btn-outline-light me-2" onclick="alert('Export feature coming soon!')">
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
  <h5>All Transactions</h5>
  <p class="text-muted">Payments for verification services across the platform</p>
  
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>User ID</th>
          <th>Phone / IP</th>
          <th>Service</th>
          <th>Amount (KES)</th>
          <th>Receipt No.</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No transactions yet</td></tr>
        <?php else: foreach ($transactions as $tx): 
          $serviceName = $serviceNames[$tx['service']] ?? ucwords(str_replace('-', ' ', $tx['service']));
          $receipt = 'RN' . strtoupper(substr(md5($tx['id']), 0, 10)); // Fake receipt
        ?>
        <tr>
          <td><?php echo date('Y-m-d H:i', strtotime($tx['created_at'])); ?></td>
          <td><?php echo htmlspecialchars($tx['national_id']); ?></td>
          <td><?php echo htmlspecialchars($tx['ip_address'] ?? 'Unknown'); ?></td>
          <td><?php echo $serviceName; ?></td>
          <td class="text-success fw-bold"><?php echo number_format($tx['price']); ?>.00</td>
          <td><?php echo $receipt; ?></td>
          <td><span class="badge bg-<?php echo $tx['status'] === 'pending' ? 'warning' : 'success'; ?>">
            <?php echo ucfirst($tx['status']); ?>
          </span></td>
          <td>
            <a href="view-transaction.php?id=<?php echo $tx['id']; ?>" class="text-info me-2" title="View Details"><i class="fa fa-eye"></i></a>
            <?php if ($tx['status'] === 'completed'): ?>
            <a href="refund-transaction.php?id=<?php echo $tx['id']; ?>" class="text-warning" title="Refund" onclick="return confirm('Issue refund?')"><i class="fa fa-undo"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between align-items-center mt-4">
    <p class="text-muted mb-0">Showing recent transactions</p>
    <div>
      <strong>Total Revenue (all time):</strong>
      <span class="text-success fs-5">KES <?php echo number_format($totalRevenue); ?></span>
    </div>
    <nav>
      <ul class="pagination pagination-sm">
        <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">Next</a></li>
      </ul>
    </nav>
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>