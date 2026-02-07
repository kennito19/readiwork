<?php
include "layout/header.php";

$users = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$subs  = $conn->query("SELECT COUNT(*) c FROM subscriptions WHERE expires_at > NOW()")->fetch_assoc()['c'];
$revenue = $conn->query("SELECT SUM(amount) s FROM payments WHERE status='paid'")->fetch_assoc()['s'] ?? 0;
$todayRevenue = $conn->query("
    SELECT SUM(amount) s FROM payments 
    WHERE status='paid' AND DATE(created_at)=CURDATE()
")->fetch_assoc()['s'] ?? 0;

$expired = $conn->query("
    SELECT COUNT(*) c FROM subscriptions WHERE expires_at < NOW()
")->fetch_assoc()['c'];


// ===================== PAYMENTS =====================
$totalPayments = $conn->query("
    SELECT COUNT(*) c FROM payments
")->fetch_assoc()['c'];

$paidPayments = $conn->query("
    SELECT COUNT(*) c FROM payments WHERE status='paid'
")->fetch_assoc()['c'];

$failedPayments = $conn->query("
    SELECT COUNT(*) c FROM payments WHERE status='failed'
")->fetch_assoc()['c'];

$pendingPayments = $conn->query("
    SELECT COUNT(*) c FROM payments WHERE status='pending'
")->fetch_assoc()['c'];

$arpu = $users > 0 ? round($revenue / $users, 2) : 0;

// ===================== SUBSCRIBERS =====================
$newSubsToday = $conn->query("
    SELECT COUNT(*) c
    FROM subscriptions
    WHERE DATE(expires_at)=CURDATE()
")->fetch_assoc()['c'];

$newSubsWeek = $conn->query("
    SELECT COUNT(*) c
    FROM subscriptions
    WHERE expires_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch_assoc()['c'];

$conversionRate = $users > 0 ? round(($subs / $users) * 100) : 0;





// Revenue last 7 days
$rev7 = $conn->query("
    SELECT DATE(created_at) d, SUM(amount) t
    FROM payments
    WHERE status='paid'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY d
");

// Revenue last 30 days
$rev30 = $conn->query("
    SELECT DATE(created_at) d, SUM(amount) t
    FROM payments
    WHERE status='paid'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY d
");

function chartData($q){
    $labels=[]; $data=[];
    while($r=$q->fetch_assoc()){
        $labels[]=$r['d'];
        $data[]=(int)$r['t'];
    }
    return [ $labels, $data ];
}

[$rev7_labels,$rev7_data] = chartData($rev7);
[$rev30_labels,$rev30_data] = chartData($rev30);



$wins = $conn->query("
    SELECT COUNT(*) c FROM games_schedule WHERE result='WIN'
")->fetch_assoc()['c'];

$losses = $conn->query("
    SELECT COUNT(*) c FROM games_schedule WHERE result='LOSS'
")->fetch_assoc()['c'];

$totalGames = $wins + $losses;
$winRate = $totalGames ? round(($wins/$totalGames)*100) : 0;

?>





<div class="row g-4 mt-4">
  <div class="col-md-6">
    <div class="card p-4 shadow-sm">
      <h6>Revenue (Last 7 Days)</h6>
      <canvas id="rev7"></canvas>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-4 shadow-sm">
      <h6>Revenue (Last 30 Days)</h6>
      <canvas id="rev30"></canvas>
    </div>
  </div>
</div>




<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0">📊 Admin Dashboard</h3>
        <small class="text-muted">Business performance overview</small>
    </div>
</div>

<!-- ===================== TOP STATS ===================== -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="card p-4 shadow-sm border-start border-4 border-primary">
            <small class="text-muted">Total Users</small>
            <h2><?= number_format($users) ?></h2>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 shadow-sm border-start border-4 border-success">
            <small class="text-muted">Active Subscribers</small>
            <h2><?= number_format($subs) ?></h2>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 shadow-sm border-start border-4 border-warning">
            <small class="text-muted">Total Revenue</small>
            <h2>KES <?= number_format($revenue) ?></h2>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 shadow-sm border-start border-4 border-info">
            <small class="text-muted">Today’s Revenue</small>
            <h2>KES <?= number_format($todayRevenue) ?></h2>
        </div>
    </div>

</div>

<!-- ===================== PAYMENTS ===================== -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="card p-4 shadow-sm">
            <small class="text-muted">Paid Payments</small>
            <h3 class="text-success"><?= number_format($paidPayments) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 shadow-sm">
            <small class="text-muted">Pending Payments</small>
            <h3 class="text-warning"><?= number_format($pendingPayments) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 shadow-sm">
            <small class="text-muted">Failed Payments</small>
            <h3 class="text-danger"><?= number_format($failedPayments) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 shadow-sm">
            <small class="text-muted">ARPU</small>
            <h3>KES <?= number_format($arpu, 2) ?></h3>
        </div>
    </div>

</div>

<!-- ===================== SUBSCRIPTIONS ===================== -->
<div class="row g-4">

    <div class="col-md-4">
        <div class="card p-4 shadow-sm">
            <small class="text-muted">Conversion Rate</small>
            <h2><?= $conversionRate ?>%</h2>
            <p class="text-muted mb-0">Subscribers / Users</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-4 shadow-sm">
            <small class="text-muted">Expired Subscriptions</small>
            <h2><?= number_format($expired) ?></h2>
            <p class="text-muted mb-0">Re-target candidates</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-4 shadow-sm">
            <small class="text-muted">New Subs (7 days)</small>
            <h2><?= number_format($newSubsWeek) ?></h2>
            <p class="text-muted mb-0">Growth indicator</p>
        </div>
    </div>

</div>

<script>
new Chart(document.getElementById('rev7'),{
  type:'line',
  data:{
    labels: <?= json_encode($rev7_labels) ?>,
    datasets:[{label:'KES', data:<?= json_encode($rev7_data) ?>, fill:true}]
  }
});

new Chart(document.getElementById('rev30'),{
  type:'bar',
  data:{
    labels: <?= json_encode($rev30_labels) ?>,
    datasets:[{label:'KES', data:<?= json_encode($rev30_data) ?>}]
  }
});
</script>



<?php include "layout/footer.php"; ?>
