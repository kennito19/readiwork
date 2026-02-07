<?php
include "layout/header.php";

$payments = $conn->query("
    SELECT phone, amount, type, status, created_at
    FROM payments
    ORDER BY id DESC
");
?>

<h3 class="mb-4">💳 Payments</h3>

<div class="card shadow-sm">
<table class="table table-striped mb-0">
    <thead class="table-light">
        <tr>
            <th>Phone</th>
            <th>Plan</th>
            <th>Amount (KES)</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
    <?php while($p = $payments->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($p['phone']) ?></td>
            <td><?= htmlspecialchars($p['type']) ?></td>
            <td><?= number_format($p['amount']) ?></td>
            <td>
                <?php if($p['status'] === 'paid'): ?>
                    <span class="badge bg-success">Paid</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                <?php endif; ?>
            </td>
            <td><?= date("d M Y H:i", strtotime($p['created_at'])) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<?php include "layout/footer.php"; ?>
