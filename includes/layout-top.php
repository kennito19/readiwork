<?php require_once __DIR__ . '/../config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — Readiwork</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #020617;
      --sidebar-bg: #0d1117;
      --card-bg: #111827;
      --text: #f1f5f9;
      --muted: #94a3b8;
      --border: #1f2937;
      --accent: #22c55e;
      --accent-hover: #16a34a;
      --accent-light: rgba(34,197,94,.1);
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', sans-serif;
      margin: 0;
    }

    .wrapper {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 270px;
      background: var(--sidebar-bg);
      border-right: 1px solid var(--border);
      padding: 30px 20px;
      position: sticky;
      top: 0;
      height: 100vh;
      overflow-y: auto;
      box-shadow: 8px 0 30px rgba(0,0,0,.3);
    }

    .sidebar .logo {
      font-weight: 900;
      font-size: 32px;
      color: var(--accent);
      text-align: center;
      margin-bottom: 60px;
      letter-spacing: -1px;
    }

    .sidebar .section {
      margin: 35px 0 15px;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: var(--muted);
      padding: 0 12px;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      padding: 14px 18px;
      color: var(--muted);
      border-radius: 14px;
      margin-bottom: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: all .3s ease;
    }

    .sidebar a i {
      width: 30px;
      font-size: 1.2rem;
      text-align: center;
      margin-right: 14px;
    }

    .sidebar a:hover {
      background: var(--accent-light);
      color: var(--accent);
      padding-left: 28px;
      transform: translateX(5px);
    }

    .sidebar a.active {
      background: var(--accent-light);
      color: var(--accent);
      font-weight: 700;
      box-shadow: 0 0 20px rgba(34,197,94,.2);
    }

    .content {
      flex: 1;
      padding: 50px;
      background: linear-gradient(to bottom right, #0b1120, var(--bg));
    }

    @media (max-width: 992px) {
      .sidebar {
        position: fixed;
        left: -270px;
        top: 0;
        height: 100%;
        z-index: 1000;
        transition: left .4s ease;
      }
      .sidebar.show { left: 0; }
      .content { padding: 30px; }
      .mobile-toggle { display: flex !important; }
    }

    .mobile-toggle {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: var(--sidebar-bg);
      padding: 18px 25px;
      justify-content: space-between;
      align-items: center;
      z-index: 999;
      border-bottom: 1px solid var(--border);
      box-shadow: 0 4px 20px rgba(0,0,0,.3);
    }

    .mobile-toggle .logo {
      font-size: 26px;
      font-weight: 900;
      color: var(--accent);
    }
  </style>
</head>
<body>

<div class="mobile-toggle">
  <div class="logo">READIWORK</div>
  <button class="btn btn-outline-success btn-lg" onclick="document.querySelector('.sidebar').classList.toggle('show')">
    <i class="fa fa-bars"></i>
  </button>
</div>

<div class="wrapper">
  <?php include __DIR__ . '/sidebar.php'; ?>

  <div class="content">