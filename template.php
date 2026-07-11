<?php
require_once __DIR__ . '/../config.php';
check_auth('manager');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book - Manager Template</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=2.5">
  <style>
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: #f8fafc;
      color: #0f172a;
    }

    .dashboard-layout {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 260px;
      background: linear-gradient(180deg, #0f766e 0%, #115e59 100%);
      color: white;
      padding: 24px 16px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .nav-link {
      color: white;
      text-decoration: none;
      padding: 10px 12px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
      opacity: 0.95;
    }

    .nav-link:hover,
    .nav-link.active {
      background: rgba(255,255,255,0.15);
    }

    .dashboard-main {
      flex: 1;
      padding: 24px;
    }

    .page-header {
      margin-bottom: 24px;
    }

    .page-title {
      font-size: 1.6rem;
      font-weight: 700;
      margin: 0 0 6px;
      color: #0f172a;
    }

    .page-desc {
      margin: 0;
      color: #64748b;
    }

    .dashboard-panel {
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }
  </style>
</head>
<body class="sidebar-closed">
  <header class="dashboard-header">
    <div class="header-left">
      <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle Sidebar">☰</button>
    </div>
    <div class="header-center">
      <a href="index.php" class="brand">
        <div class="brand-logo">Uni<span class="brand-dot-center"></span><span class="brand-book">Book</span></div>
        <span class="brand-sub">University Booking System</span>
      </a>
    </div>
  </header>

  <div class="dashboard-layout">
    <aside class="sidebar" id="sidebarMenu">
      <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="bookings.php" class="nav-link">Manage Requests</a>
        <a href="template.php" class="nav-link active">Template</a>
        <a href="../logout.php" class="nav-link" style="margin-top: 12px; border-top: 1px solid rgba(255,255,255,0.15); padding-top: 16px;">Logout</a>
      </nav>
      <div class="sidebar-user">
        <div class="user-avatar">MG</div>
        <div class="user-meta">
          <span class="user-name">Department Manager</span>
          <span class="user-role">Manager Portal</span>
        </div>
      </div>
    </aside>

    <main class="dashboard-main">
      <div class="page-header">
        <h1 class="page-title">Template Page</h1>
        <p class="page-desc">Use this page as a blank starter for future screens.</p>
      </div>

      <div class="dashboard-panel">
        <h2 style="margin-top:0;">Content Area</h2>
        <p style="color:#64748b;">Add your form, table, or cards here.</p>
      </div>
    </main>
  </div>

  <script>
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-closed');
      const sidebar = document.getElementById('sidebarMenu');
      if (sidebar) {
        sidebar.style.display = document.body.classList.contains('sidebar-closed') ? 'none' : 'flex';
      }
    }
  </script>
</body>
</html>
