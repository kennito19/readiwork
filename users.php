<?php 
include 'includes/layout-top.php'; 

// Basic search functionality
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE national_id LIKE :search 
               OR full_name LIKE :search
               OR ip_address LIKE :search 
               OR user_agent LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

// Fetch distinct users (based on national_id, since your platform uses ID as identifier)
try {
    $countSql = "SELECT COUNT(DISTINCT national_id) FROM verification_requests " . $where;
    
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();

    // Get unique users with latest activity
$stmt = $pdo->prepare("
    SELECT 
        national_id,
        MAX(full_name) as full_name,
        MIN(created_at) as first_seen,
        MAX(created_at) as last_seen,
        COUNT(*) as total_checks,
        ip_address,
        user_agent
    FROM verification_requests
    $where
    GROUP BY national_id
    ORDER BY last_seen DESC
    LIMIT 50
");
    
    
    
    
    
    
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Users page DB error: ' . $e->getMessage());
    $users = [];
    $totalUsers = 0;
}

// Simple status logic (you can expand this later with a real users table)
function getUserStatus($user) {
    $daysSinceLast = (time() - strtotime($user['last_seen'])) / (60*60*24);
    if ($daysSinceLast <= 7) {
        return ['text' => 'Active', 'badge' => 'success'];
    } elseif ($daysSinceLast <= 30) {
        return ['text' => 'Recent', 'badge' => 'warning'];
    } else {
        return ['text' => 'Inactive', 'badge' => 'secondary'];
    }
}
?>

<h2 class="fw-bold mb-4">Manage Users</h2>

<div class="stat mb-4">
  <div class="row">
    <div class="col-md-6">
      <h5 class="mb-3">Search Users</h5>
      <form class="row g-3" method="GET">
        <div class="col-md-8">
        
          <input type="text" name="q" class="form-control" placeholder="Search by ID, Name, IP or device" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-outline-light w-100">Search</button>
        </div>
      </form>
    </div>
    <div class="col-md-6 text-end">
      <h5 class="mb-3 opacity-0">placeholder</h5>
      <a href="#" class="btn btn-success">
        <i class="fa fa-plus me-2"></i> Export Users
      </a>
    </div>
  </div>
</div>

<div class="stat">
  <h5>All Users (<?php echo number_format($totalUsers); ?> unique)</h5>
  <p >List of individuals who have used Readiwork verification services</p>
  
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
    
    
    <thead>
  <tr>
    <th>#</th>
    <th>Full Name</th>
    <th>ID Number</th>
    <th>First Seen</th>
    <th>Last Active</th>
    <th>Total Checks</th>
    <th>IP Address</th>
    <th>Status</th>
    <th>Actions</th>
  </tr>
</thead>
      <tbody>
        <?php if (empty($users)): ?>
        <tr>
           
              <td colspan="9" class="text-center text-muted py-4">
            <?php echo $search ? 'No users found matching your search.' : 'No users yet.'; ?>
          </td>
        </tr>
        <?php else: foreach ($users as $index => $user): 
          $status = getUserStatus($user);
        ?>
        
        
        
        
        
        
        
        <tr>
  <td><?php echo $index + 1; ?></td>
  <td>
    <strong><?php echo htmlspecialchars($user['full_name'] ?: 'N/A'); ?></strong>
    <?php if (!$user['full_name']): ?>
      <small class="text-muted d-block">Name not available</small>
    <?php endif; ?>
  </td>
  <td><?php echo htmlspecialchars($user['national_id']); ?></td>
  <td><?php echo date('Y-m-d', strtotime($user['first_seen'])); ?></td>
  <td><?php echo date('Y-m-d H:i', strtotime($user['last_seen'])); ?></td>
  <td><?php echo number_format($user['total_checks']); ?></td>
  <td><?php echo htmlspecialchars($user['ip_address'] ?? 'Unknown'); ?></td>
  <td>
    <span class="badge bg-<?php echo $status['badge']; ?>">
      <?php echo $status['text']; ?>
    </span>
  </td>
  
     
          
          
          
          
          <td>
            <a href="view-user.php?id=<?php echo urlencode($user['national_id']); ?>" class="text-info me-3" title="View Profile & History">
              <i class="fa fa-eye"></i>
            </a>
            <a href="user-checks.php?id=<?php echo urlencode($user['national_id']); ?>" class="text-primary me-3" title="View All Checks">
              <i class="fa fa-list"></i>
            </a>
            <!-- Suspend/Delete would require a proper users table with status -->
            <a href="#" class="text-danger" title="Block User (coming soon)">
              <i class="fa fa-ban"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <p >
      Showing <?php echo count($users); ?> of <?php echo number_format($totalUsers); ?> unique users
    </p>
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

<?php include 'includes/layout-bottom.php'; ?>