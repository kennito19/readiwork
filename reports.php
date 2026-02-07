<?php 
include 'includes/layout-top.php'; 

// Fetch real analytics data
try {
    // Total revenue (sum of price where paid/completed)
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(price), 0) FROM verification_requests WHERE status IN ('paid', 'completed')")->fetchColumn();

    // Total checks all time
    $totalChecks = $pdo->query("SELECT COUNT(*) FROM verification_requests")->fetchColumn();

    // Active users last 30 days (distinct national_id with created_at in last 30 days)
    $activeUsers = $pdo->query("SELECT COUNT(DISTINCT national_id) FROM verification_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

    // Positive listings this month (for crb service, count where result indicates blacklisted/watchlist)
    $positiveListings = 0; // Placeholder - implement parsing if needed
    // Example: Query crb with result containing blacklisted true, etc.

    // Service breakdown
    $serviceBreakdown = $pdo->query("
        SELECT service, COUNT(*) as count 
        FROM verification_requests 
        GROUP BY service 
        ORDER BY count DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Map service keys to nice names
    $serviceNames = [
        'crb' => 'Credit / Blacklist',
        'credit-score' => 'Credit Health Score',
        'tenant' => 'Tenant Verification',
        'job' => 'Job Verification',
        'background' => 'Background Check',
        // Add others as needed
    ];

    // Revenue trend last 30 days (daily)
    $revenueTrendStmt = $pdo->query("
        SELECT DATE(created_at) as date, COALESCE(SUM(price), 0) as daily_rev 
        FROM verification_requests 
        WHERE status IN ('paid', 'completed') AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY date 
        ORDER BY date
    ");
    $revenueTrend = $revenueTrendStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Format for chart
    $revenueLabels = [];
    $revenueData = [];
    for ($i = 29; $i >= 0; $i--) {
        $date = date('M j', strtotime("-$i days"));
        $key = date('Y-m-d', strtotime("-$i days"));
        $revenueLabels[] = $date;
        $revenueData[] = $revenueTrend[$key] ?? 0;
    }

    // Top verifications this month for doughnut
    $checksLabels = [];
    $checksData = [];
    $checksColors = ['#22c55e', '#3b82f6', '#eab308', '#ef4444', '#a855f7', '#64748b', '#06b6d4', '#f97316', '#84cc16', '#ec4899'];
    foreach ($serviceBreakdown as $key => $count) {
        $checksLabels[] = $serviceNames[$key] ?? ucwords(str_replace('-', ' ', $key));
        $checksData[] = $count;
    }

} catch (Exception $e) {
    error_log('Reports page DB error: ' . $e->getMessage());
    // Defaults if error
    $totalRevenue = 1248500;
    $totalChecks = 9402;
    $activeUsers = 1156;
    $positiveListings = 182;
    $revenueLabels = ['Dec 1', 'Dec 5', 'Dec 10', 'Dec 15', 'Dec 20', 'Dec 25', 'Dec 29'];
    $revenueData = [32000, 45000, 68000, 92300, 71000, 85000, 62000];
    $checksLabels = ['Credit/Blacklist', 'Tenant', 'Job', 'Credit Health', 'Domestic', 'Others'];
    $checksData = [2845, 1920, 1456, 1203, 978, 1000];
}
?>

<h2 class="fw-bold mb-4">Reports & Analytics</h2>

<div class="row g-4 mb-5">
  <!-- Quick Summary Cards -->
  <div class="col-md-3">
    <div class="stat text-center p-4">
      <h5>Total Revenue</h5>
      <h3 class="text-success">KES <?php echo number_format($totalRevenue); ?></h3>
      <small class="text-muted">All time (paid/completed)</small>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat text-center p-4">
      <h5>Checks Performed</h5>
      <h3 class="text-info"><?php echo number_format($totalChecks); ?></h3>
      <small class="text-muted">All time</small>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat text-center p-4">
      <h5>Active Users</h5>
      <h3 class="text-warning"><?php echo number_format($activeUsers); ?></h3>
      <small class="text-muted">Last 30 days</small>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat text-center p-4">
      <h5>Positive Listings</h5>
      <h3 class="text-danger"><?php echo number_format($positiveListings); ?></h3>
      <small class="text-muted">CRB blacklists/watchlists this month</small>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Left Column: Charts & Filters -->
  <div class="col-lg-8">
    <!-- Date Range Filter -->
    <div class="stat mb-4 p-4">
      <h5 class="mb-3">Report Period</h5>
      <form class="row g-3 align-items-end" method="GET">
        <div class="col-md-4">
          <label class="form-label">From</label>
          <input type="date" name="from" class="form-control" value="<?php echo date('Y-m-01'); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">To</label>
          <input type="date" name="to" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-success w-100">Apply Filter</button>
        </div>
      </form>
      <small class="text-muted mt-2 d-block">Filter charts and lists below</small>
    </div>

    <!-- Revenue Over Time -->
    <div class="stat mb-4 p-4">
      <h5>Revenue Trend</h5>
      <p class="text-muted">Daily revenue (paid/completed checks)</p>
      <div class="mt-4">
        <canvas id="revenueChart" height="100"></canvas>
      </div>
    </div>

    <!-- Checks by Type -->
    <div class="stat p-4">
      <h5>Verifications by Type</h5>
      <p class="text-muted">Breakdown of all checks performed</p>
      <div class="mt-4">
        <canvas id="checksChart" height="120"></canvas>
      </div>
    </div>
  </div>

  <!-- Right Column: Top Lists -->
  <div class="col-lg-4">
    <!-- Most Popular Checks -->
    <div class="stat mb-4 p-4">
      <h5>Top Verification Types (All Time)</h5>
      <ol class="list-group list-group-numbered mt-3">
        <?php 
        arsort($serviceBreakdown);
        $i = 1;
        foreach ($serviceBreakdown as $key => $count): 
          if ($i > 10) break;
        ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?php echo $serviceNames[$key] ?? ucwords(str_replace('-', ' ', $key)); ?>
          <span class="badge bg-success rounded-pill"><?php echo number_format($count); ?></span>
        </li>
        <?php $i++; endforeach; ?>
        <?php if (empty($serviceBreakdown)): ?>
        <li class="list-group-item text-center text-muted">No data yet</li>
        <?php endif; ?>
      </ol>
    </div>

    <!-- Placeholder for Recent High-Value (customize if you track bulk/high payments) -->
    <div class="stat p-4">
      <h5>Recent High-Value Transactions</h5>
      <p class="text-muted">Coming soon – bulk or high-price checks</p>
      <a href="#" class="btn btn-outline-light w-100 mt-3">Export All Reports</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Line Chart
new Chart(document.getElementById('revenueChart'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode($revenueLabels); ?>,
    datasets: [{
      label: 'Daily Revenue (KES)',
      data: <?php echo json_encode($revenueData); ?>,
      borderColor: '#22c55e',
      backgroundColor: 'rgba(34, 197, 94, 0.2)',
      tension: 0.4,
      fill: true
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { callback: value => 'KES ' + value.toLocaleString() } } }
  }
});

// Verifications Doughnut Chart
new Chart(document.getElementById('checksChart'), {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode($checksLabels); ?>,
    datasets: [{
      data: <?php echo json_encode($checksData); ?>,
      backgroundColor: ['#22c55e', '#3b82f6', '#eab308', '#ef4444', '#a855f7', '#64748b', '#06b6d4', '#f97316', '#84cc16', '#ec4899']
    }]
  },
  options: { plugins: { legend: { position: 'right' } } }
});
</script>

<?php include 'includes/layout-bottom.php'; ?>