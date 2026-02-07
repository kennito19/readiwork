<?php require "../config.php"; require "auth.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Daily Odds Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background:#f4f6f9;
        }

        /* Sidebar */
        .sidebar {
            background:#111827;
            min-height:100vh;
            width:240px;
            position:fixed;
            top:0;
            left:0;
            transition:transform .3s ease;
            z-index:1040;
        }

        .sidebar a {
            color:#cbd5e1;
            text-decoration:none;
            display:block;
            padding:12px 18px;
            font-size:15px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background:#1f2937;
            color:#fff;
        }

        .sidebar-brand {
            font-weight:600;
            font-size:18px;
            padding:16px;
            text-align:center;
            color:#fff;
            border-bottom:1px solid #1f2937;
        }

        /* Content */
        .main-content {
            margin-left:240px;
            padding:24px;
            transition:margin-left .3s ease;
        }

        /* Mobile */
        @media (max-width: 768px) {
            .sidebar {
                transform:translateX(-100%);
            }
            .sidebar.show {
                transform:translateX(0);
            }
            .main-content {
                margin-left:0;
                padding:16px;
            }
        }

        .card {
            border:none;
            border-radius:14px;
        }

        /* Topbar */
        .topbar {
            background:#fff;
            border-bottom:1px solid #e5e7eb;
            padding:12px 16px;
            position:sticky;
            top:0;
            z-index:1030;
        }
    </style>
</head>
<body>

<!-- MOBILE TOPBAR -->
<div class="topbar d-md-none d-flex justify-content-between align-items-center">
    <button class="btn btn-outline-secondary btn-sm" onclick="toggleSidebar()">
        ☰
    </button>
    <strong>Daily Odds Admin</strong>
</div>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar">
    <div class="sidebar-brand">⚽ Daily Odds</div>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="users.php">👥 Users</a>
    <a href="payments.php">💳 Payments</a>
    <a href="games.php">⚽ Games</a>
    <a href="broadcast.php">📢 Broadcast</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
}
</script>
