<?php 
include 'includes/layout-top.php'; 

// Fetch all admins from the 'admins' table
try {
    $stmt = $pdo->prepare("
        SELECT id, email, role, last_login, created_at 
        FROM admins 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalAdmins = count($admins);
} catch (Exception $e) {
    error_log('Admins page DB error: ' . $e->getMessage());
    $admins = [];
    $totalAdmins = 0;
}

// Simple role mapping for display
$roleBadges = [
    'super' => ['text' => 'Super Admin', 'badge' => 'danger'],
    'admin' => ['text' => 'Administrator', 'badge' => 'primary'],
    // Add more custom roles if you expand the enum later
];

// Fake names for demo (in real app, store name separately or extract from email)
$fakeNames = [
    1 => 'Super Admin',
    2 => 'James Njoroge',
    3 => 'Grace Muthoni',
    4 => 'Peter Omondi',
    5 => 'Sarah Wangui',
    // Add more as needed
];

// Fake phones (same idea)
$fakePhones = [
    1 => '+254 700 000 001',
    2 => '+254 712 345 678',
    3 => '+254 723 456 789',
    4 => '+254 734 567 890',
    5 => '+254 745 678 901',
];
?>

<h2 class="fw-bold mb-4">Admin Accounts</h2>

<div class="stat mb-4">
  <div class="row align-items-end">
    <div class="col-md-8">
      <h5 class="mb-3">Search Admins</h5>
      <form class="row g-3" method="GET">
        <div class="col-md-9">
          <input type="text" name="q" class="form-control" placeholder="Search by name, email or phone" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-outline-light w-100">Search</button>
        </div>
      </form>
    </div>
    <div class="col-md-4 text-end">
      <h5 class="mb-3 opacity-0">spacer</h5>
      <a href="add-admin.php" class="btn btn-success">
        <i class="fa fa-plus me-2"></i> Add New Admin
      </a>
    </div>
  </div>
</div>

<div class="stat">
  <h5>All Administrators (<?php echo $totalAdmins; ?>)</h5>
  <p class="text-muted">Manage system administrators and their access levels</p>
  
  <div class="table-responsive mt-4">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Last Login</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($admins)): ?>
        <tr>
          <td colspan="8" class="text-center text-muted py-4">No administrators found</td>
        </tr>
        <?php else: foreach ($admins as $index => $admin): 
          $roleInfo = $roleBadges[strtolower($admin['role'])] ?? ['text' => ucwords($admin['role']), 'badge' => 'secondary'];
          $isActive = $admin['last_login'] && strtotime($admin['last_login']) > strtotime('-30 days');
        ?>
        <tr>
          <td><?php echo $index + 1; ?></td>
          <td><?php echo htmlspecialchars($fakeNames[$admin['id']] ?? 'Admin User'); ?></td>
          <td><?php echo htmlspecialchars($admin['email']); ?></td>
          <td><?php echo htmlspecialchars($fakePhones[$admin['id']] ?? 'Not set'); ?></td>
          <td><span class="badge bg-<?php echo $roleInfo['badge']; ?>"><?php echo $roleInfo['text']; ?></span></td>
          <td>
            <?php if ($admin['last_login']): ?>
              <?php echo date('Y-m-d H:i', strtotime($admin['last_login'])); ?>
            <?php else: ?>
              <em class="text-muted">Never</em>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge bg-<?php echo $isActive ? 'success' : 'warning'; ?>">
              <?php echo $isActive ? 'Active' : 'Inactive'; ?>
            </span>
          </td>
          <td>
            <a href="view-admin.php?id=<?php echo $admin['id']; ?>" class="text-info me-3" title="View">
              <i class="fa fa-eye"></i>
            </a>
            <a href="edit-admin.php?id=<?php echo $admin['id']; ?>" class="text-warning me-3" title="Edit">
              <i class="fa fa-edit"></i>
            </a>
            <?php if ($admin['role'] !== 'super'): // Prevent deleting super admin ?>
            <a href="delete-admin.php?id=<?php echo $admin['id']; ?>" class="text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this admin?')">
              <i class="fa fa-trash"></i>
            </a>
            <?php else: ?>
            <span class="text-muted" title="Cannot delete Super Admin"><i class="fa fa-lock"></i></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalAdmins > 10): // Simple pagination placeholder ?>
  <div class="d-flex justify-content-between mt-4">
    <p class="text-muted">Showing 1 to <?php echo min(10, $totalAdmins); ?> of <?php echo $totalAdmins; ?> administrators</p>
    <nav>
      <ul class="pagination pagination-sm">
        <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">Next</a></li>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/layout-bottom.php'; ?>