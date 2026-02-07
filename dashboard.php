<?php
require_once 'config.php';

// Fetch real stats
try {
    $totalVerifs = $pdo->query("SELECT COUNT(*) FROM verification_requests")->fetchColumn();
    $pending = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'")->fetchColumn();
    $thisMonth = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
  
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(price), 0) FROM verification_requests WHERE status IN ('paid', 'completed')")->fetchColumn();
    $monthRevenue = $pdo->query("SELECT COALESCE(SUM(price), 0) FROM verification_requests WHERE status IN ('paid', 'completed') AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
  
    $completed = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE status IN ('paid','completed')")->fetchColumn();
  
    // Query includes new columns
    $recentStmt = $pdo->prepare("
        SELECT 
            id, created_at, service, national_id, full_name,
            dob, nationality, gender, status 
        FROM verification_requests 
        ORDER BY created_at DESC 
        LIMIT 8
    ");
    $recentStmt->execute();
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
  
    // Service breakdown (top 6)
    $topServices = $pdo->query("SELECT service, COUNT(*) as cnt FROM verification_requests GROUP BY service ORDER BY cnt DESC LIMIT 6")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    error_log($e->getMessage());
    $totalVerifs = $pending = $thisMonth = $totalRevenue = $monthRevenue = $completed = 0;
    $recent = [];
    $topServices = [];
}
?>
<?php include 'includes/layout-top.php'; ?>
</style>


<div class="content">
  <!-- Header -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-5">
    <div>
      <h2 class="fw-bold mb-1">Welcome back, Admin!</h2>
      <p style="color: #94a3b8;">
        Here's what's happening with Readiwork today — <?= date('l, F j, Y') ?>
      </p>
    </div>
    <div class="text-end">
      <span class="badge bg-success fs-6 px-4 py-2 rounded-pill">
        <i class="fa fa-circle me-2" style="font-size:10px;"></i> System Online
      </span>
    </div>
  </div>

  <!-- Stats Cards -->
  <div class="row g-4 mb-5">
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card border-0 shadow-lg h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px;">
        <div class="card-body text-white p-4">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="mb-1 opacity-75">Total Revenue</p>
              <h3 class="fw-bold mb-0">KES <?= number_format($totalRevenue) ?></h3>
              <small class="opacity-75">+ KES <?= number_format($monthRevenue) ?> this month</small>
            </div>
            <i class="fa fa-sack-dollar fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card border-0 shadow-lg h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 20px;">
        <div class="card-body text-white p-4">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="mb-1 opacity-75">Total Verifications</p>
              <h3 class="fw-bold mb-0"><?= number_format($totalVerifs) ?></h3>
              <small class="opacity-75">+ <?= number_format($thisMonth) ?> this month</small>
            </div>
            <i class="fa fa-check-circle fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card border-0 shadow-lg h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 20px;">
        <div class="card-body text-white p-4">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="mb-1 opacity-75">Pending Checks</p>
              <h3 class="fw-bold mb-0"><?= number_format($pending) ?></h3>
              <small class="opacity-75">Requires attention</small>
            </div>
            <i class="fa fa-clock fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card border-0 shadow-lg h-100" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); border-radius: 20px;">
        <div class="card-body text-white p-4">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="mb-1 opacity-75">Completed</p>
              <h3 class="fw-bold mb-0"><?= number_format($completed) ?></h3>
              <small class="opacity-75">Successful verifications</small>
            </div>
            <i class="fa fa-thumbs-up fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Activity + Top Services -->
  <div class="row g-4">
    <!-- Recent Verifications -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-lg" style="background:#111827; border-radius:20px;">
        <div class="card-body p-4">
          <h4 class="mb-4 text-white">Recent Activity</h4>
          <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 recent-table">
              <thead class="text-muted small">
                <tr>
                  <th>Time</th>
                  <th>Name</th>
                  <th>Service</th>
                  <th>ID Number</th>
                  <th>DOB</th>
                  <th>Nationality</th>
                  <th>Gender</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recent)): ?>
                  <tr><td colspan="9" class="text-center py-4 text-muted">No recent activity</td></tr>
                <?php else: foreach ($recent as $r): ?>
                  <tr>
                    <!-- Time -->
                    <td class="time-cell small">
                      <?php
                      $dt = DateTime::createFromFormat('Y-m-d H:i:s', $r['created_at'] ?? '');
                      echo $dt ? $dt->format('H:i') : '—';
                      ?>
                    </td>

                    <!-- Name -->
                    <td class="text-white">
                      <?php
                      $name = trim($r['full_name'] ?? '');
                      echo $name ? htmlspecialchars($name) : '<span class="text-muted fst-italic">Not available</span>';
                      ?>
                    </td>

                    <!-- Service -->
                    <td class="text-white">
                      <?php echo ucwords(str_replace('-', ' ', $r['service'] ?? '—')); ?>
                    </td>

                    <!-- ID Number -->
                    <td class="text-white font-monospace">
                      <?php echo htmlspecialchars($r['national_id'] ?? '—'); ?>
                    </td>

                    
                    <!-- DOB -->
<td class="small text-white">
  <?php 
  $dob = trim($r['dob'] ?? '');
  echo $dob ? htmlspecialchars($dob) : '<span class="text-muted fst-italic">—</span>'; 
  ?>
</td>

                    <!-- Nationality -->
                    <td class="small text-white">
                      <?php 
                      $nat = trim($r['nationality'] ?? '');
                      echo $nat ? htmlspecialchars(ucfirst($nat)) : '<span class="text-muted fst-italic">—</span>'; 
                      ?>
                    </td>

                    <!-- Gender -->
                    <td class="small text-white text-center">
                      <?php 
                      $gen = trim($r['gender'] ?? '');
                      echo $gen ? htmlspecialchars(ucfirst($gen)) : '<span class="text-muted fst-italic">—</span>'; 
                      ?>
                    </td>

                    <!-- Status -->
                    <td>
                      <?php if ($r['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                      <?php elseif ($r['status'] === 'paid'): ?>
                        <span class="badge bg-info">Paid</span>
                      <?php else: ?>
                        <span class="badge bg-success">Completed</span>
                      <?php endif; ?>
                    </td>

                    <!-- Action -->
                    <td>
                      <a href="results.php?rid=<?= $r['id'] ?>" class="text-info" title="View Details">
                        <i class="fa fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Services -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-lg" style="background:#111827; border-radius:20px;">
        <div class="card-body p-4">
          <h4 class="mb-4 text-white">Top Services This Month</h4>
          <?php if (empty($topServices)): ?>
            <p class="text-muted text-center py-5">No data yet</p>
          <?php else: 
            $colors = ['#667eea', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#a8edea'];
            $i = 0;
            $maxCount = max($topServices) ?: 1; // avoid division by zero
            foreach ($topServices as $svc => $cnt): 
              $name = ucwords(str_replace('-', ' ', $svc));
          ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="d-flex align-items-center">
                <div class="rounded-circle me-3" style="width:12px; height:12px; background:<?= $colors[$i % count($colors)] ?>;"></div>
                <span class="text-white"><?= $name ?></span>
              </div>
              <strong class="text-success"><?= number_format($cnt) ?></strong>
            </div>
            <div class="progress mb-3" style="height:6px;">
              <div class="progress-bar" style="width:<?= ($cnt / $maxCount) * 100 ?>%; background:<?= $colors[$i % count($colors)] ?>;"></div>
            </div>
          <?php $i++; endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>