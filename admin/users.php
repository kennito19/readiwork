<?php
include "layout/header.php";

/*
  Robust query:
  - Works even if users table has NO id column
  - Shows users without subscriptions
  - Detects active subscription correctly
*/
$users = $conn->query("
    SELECT 
        u.phone,
        u.referral_code,
        MAX(CASE WHEN s.expires_at > NOW() THEN 1 ELSE 0 END) AS active
    FROM users u
    LEFT JOIN subscriptions s ON s.phone = u.phone
    GROUP BY u.phone, u.referral_code
    ORDER BY u.phone DESC
");
?>

<h3 class="mb-4">👥 Users</h3>

<div class="card shadow-sm">
<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr>
            <th>Phone</th>
            <th>Referral Code</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($users && $users->num_rows > 0): ?>
        <?php while($u = $users->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($u['phone']) ?></td>
                <td><?= htmlspecialchars($u['referral_code'] ?? '-') ?></td>
                <td>
                    <?php if($u['active']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="3" class="text-center text-muted p-4">
                No users found
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php include "layout/footer.php"; ?>
