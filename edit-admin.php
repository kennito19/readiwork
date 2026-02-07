<?php 
include 'includes/layout-top.php'; 

// Get admin ID from URL
$admin_id = $_GET['id'] ?? null;

if (!$admin_id || !is_numeric($admin_id)) {
    echo '<div class="alert alert-danger">Invalid admin ID.</div>';
    include 'includes/layout-bottom.php';
    exit;
}

// Fetch current admin data
try {
    $stmt = $pdo->prepare("SELECT id, email, role FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo '<div class="alert alert-danger">Admin not found.</div>';
        include 'includes/layout-bottom.php';
        exit;
    }
} catch (Exception $e) {
    error_log('Edit admin DB error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">Error loading admin data.</div>';
    include 'includes/layout-bottom.php';
    exit;
}

// Handle form submission
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'admin';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($email !== $admin['email']) {
        // Check if new email already exists
        $checkStmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $admin_id]);
        if ($checkStmt->rowCount() > 0) {
            $error = 'This email is already used by another admin.';
        }
    }

    // Password change (optional)
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        }
    }

    if (empty($error)) {
        try {
            $updates = ['email' => $email, 'role' => $role];
            $sql = "UPDATE admins SET email = :email, role = :role";
            $params = [':email' => $email, ':role' => $role, ':id' => $admin_id];

            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $sql .= ", password = :password";
                $params[':password'] = $hashed;
            }

            $sql .= " WHERE id = :id";

            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute($params);

            $success = 'Admin updated successfully!';
            // Refresh data
            $admin['email'] = $email;
            $admin['role'] = $role;
        } catch (Exception $e) {
            error_log('Update admin error: ' . $e->getMessage());
            $error = 'Failed to update admin. Please try again.';
        }
    }
}

// Role options
$availableRoles = ['admin' => 'Administrator', 'super' => 'Super Admin'];
// Add more if you expand roles later
?>

<h2 class="fw-bold mb-4">Edit Admin Account</h2>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="stat p-4">
  <div class="row">
    <div class="col-lg-8">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
              <?php foreach ($availableRoles as $value => $label): ?>
              <option value="<?php echo $value; ?>" <?php echo $admin['role'] === $value ? 'selected' : ''; ?>>
                <?php echo $label; ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <hr class="my-4">

        <h6 class="mb-3">Change Password (Optional)</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password">
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-success me-3">
            <i class="fa fa-save me-2"></i> Save Changes
          </button>
          <a href="admins.php" class="btn btn-outline-light">
            Cancel
          </a>
        </div>
      </form>
    </div>

    <div class="col-lg-4">
      <div class="stat bg-transparent border-0 shadow-none">
        <h6>Admin Info</h6>
        <ul class="list-unstyled text-muted small">
          <li><strong>ID:</strong> #<?php echo $admin['id']; ?></li>
          <li><strong>Current Role:</strong> 
            <span class="badge bg-<?php echo $admin['role'] === 'super' ? 'danger' : 'primary'; ?>">
              <?php echo $admin['role'] === 'super' ? 'Super Admin' : 'Administrator'; ?>
            </span>
          </li>
          <li><strong>Note:</strong> Super Admins have full access and cannot be deleted.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>