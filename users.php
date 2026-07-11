<?php
// admin/users.php
require_once '../config.php';
check_auth('admin');

$error = '';
$success = '';

$filter_role = $_GET['filter_role'] ?? 'All';
$filter_search = trim($_GET['search'] ?? '');

$conn = db_get_connection();
if ($conn) {
    // Handle user deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $delete_id = intval($_POST['user_id'] ?? 0);
        if ($delete_id > 0) {
            // Set uploaded_by = NULL for resources uploaded by this manager
            mysqli_query($conn, "UPDATE resources SET uploaded_by = NULL WHERE uploaded_by = $delete_id");
            
            // Delete user
            if (mysqli_query($conn, "DELETE FROM users WHERE user_id = $delete_id")) {
                $success = "User deleted successfully!";
            } else {
                $error = "Error deleting user: " . mysqli_error($conn);
            }
        }
    }

    // Retrieve all users & managers
    $res = mysqli_query($conn, "
        SELECT u.user_id, u.name, u.phone, u.email, r.role_name, u.department, c.category_name 
        FROM users u
        INNER JOIN roles r ON u.role_id = r.role_id
        LEFT JOIN categories c ON u.category_id = c.category_id
        WHERE u.role_id IN (2, 3)
        ORDER BY r.role_name ASC, u.user_id DESC
    ");
    $user_list = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            // Apply role filter
            if ($filter_role !== 'All' && strtolower($row['role_name']) !== strtolower($filter_role)) {
                continue;
            }
            // Apply search filter
            if ($filter_search !== '') {
                $search_lower = strtolower($filter_search);
                $match = (
                    stripos($row['name'], $search_lower) !== false ||
                    stripos($row['email'], $search_lower) !== false ||
                    stripos($row['phone'] ?? '', $search_lower) !== false ||
                    stripos($row['department'] ?? '', $search_lower) !== false ||
                    stripos($row['category_name'] ?? '', $search_lower) !== false
                );
                if (!$match) {
                    continue;
                }
            }
            $user_list[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book - Manage Users</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=2.5">
</head>
<body class="sidebar-closed">

  <!-- FIXED TOP HEADER -->
  <header class="dashboard-header">
    <div class="header-left">
      <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle Sidebar">☰</button>
    </div>
    <div class="header-center">
      <a href="<?php echo url('index.php'); ?>" class="brand">
        <div class="brand-logo">Uni<span class="brand-dot-center"></span><span class="brand-book">Book</span></div>
        <span class="brand-sub">University Booking System</span>
      </a>
    </div>
  </header>

  <div class="dashboard-layout">
    
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebarMenu">
            <nav class="sidebar-nav">
        <a href="<?php echo url('admin/dashboard.php'); ?>" class="nav-link nav-item"><?php echo get_icon('dashboard'); ?> Dashboard</a>
        <a href="<?php echo url('admin/resources.php'); ?>" class="nav-link nav-item"><?php echo get_icon('resources'); ?> Manage Resources</a>
        <a href="<?php echo url('admin/bookingcalender.php'); ?>" class="nav-link nav-item"><?php echo get_icon('bookings'); ?> Calender</a>
        <a href="<?php echo url('admin/bookings.php'); ?>" class="nav-link nav-item"><?php echo get_icon('bookings'); ?> View Bookings</a>
        <a href="<?php echo url('admin/users.php'); ?>" class="nav-link nav-item active"><?php echo get_icon('profile'); ?> Manage Users</a>
        <a href="<?php echo url('admin/reports.php'); ?>" class="nav-link nav-item"><?php echo get_icon('reports'); ?> Reports</a>
        <a href="<?php echo url('logout.php'); ?>" class="nav-link nav-item" style="margin-top:20px; border-top:1px solid rgba(255,255,255,0.1); padding-top:20px;"><?php echo get_icon('logout'); ?> Logout</a>
      </nav>
      <div class="sidebar-user">
        <div class="user-avatar">AD</div>
        <div class="user-meta">
          <span class="user-name">Administrator</span>
          <span class="user-role">System Admin</span>
        </div>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">

      <!-- PAGE HEADER -->
      <div class="page-header">
        <h1 class="page-title"> 👤 Manage Users</h1>
        <p class="page-desc">Retrieve, inspect, or delete user and manager accounts.</p>
      </div>

      <!-- FILTER BAR -->
      <div class="dashboard-panel" style="padding:16px 24px; margin-bottom:24px; display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
        <form action="users.php" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; width:100%;">
          <div style="display:flex; flex-direction:column; gap:4px; min-width:180px;">
            <span style="font-size:0.75rem; font-weight:700; color:var(--teal-dark);">Filter by Role</span>
            <select name="filter_role" onchange="this.form.submit()" style="padding:8px 12px; background:#fff; border:1px solid var(--border); border-radius:8px; min-width:180px; font-family:inherit; color:var(--ink); outline:none;">
              <option value="All" <?php echo $filter_role === 'All' ? 'selected' : ''; ?>>All Roles</option>
              <option value="user" <?php echo $filter_role === 'user' ? 'selected' : ''; ?>>User</option>
              <option value="manager" <?php echo $filter_role === 'manager' ? 'selected' : ''; ?>>Manager</option>
            </select>
          </div>

          <div style="display:flex; flex-direction:column; gap:4px; flex:1; min-width:220px;">
            <span style="font-size:0.75rem; font-weight:700; color:var(--teal-dark);">Search Users</span>
            <input type="search" name="search" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Search by name, email, department, phone..." style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; width:100%; font-family:inherit; color:var(--ink); outline:none;">
          </div>

          <div style="display:flex; gap:12px; align-items:center; margin-left:auto;">
            <button type="submit" class="btn-secondary" style="padding:10px 18px;">Apply</button>
            <a href="users.php" class="btn-main" style="padding:10px 18px;">Clear</a>
          </div>
        </form>
      </div>

      <!-- Alerts -->
      <?php if (!empty($error)): ?>
        <div class="alert err" style="display:block;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert ok" style="display:block;"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <!-- USERS TABLE -->
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Role</th>
              <th>Department / Category Scope</th>
              <th style="text-align:center;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($user_list)): ?>
              <?php foreach ($user_list as $u): ?>
                <tr>
                  <td style="font-weight:700; color:var(--teal-dark);">U<?php echo htmlspecialchars($u['user_id']); ?></td>
                  <td style="font-weight:600;"><?php echo htmlspecialchars($u['name']); ?></td>
                  <td><?php echo htmlspecialchars($u['email']); ?></td>
                  <td><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
                  <td>
                    <span class="badge <?php echo $u['role_name'] === 'manager' ? 'confirmed' : 'info'; ?>">
                      <?php echo htmlspecialchars(ucfirst($u['role_name'])); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($u['role_name'] === 'manager'): ?>
                      <div style="font-size:0.85rem; font-weight:500;">Dept: <?php echo htmlspecialchars($u['department'] ?? '—'); ?></div>
                      <div style="font-size:0.75rem; color:var(--muted);">Category: <?php echo htmlspecialchars($u['category_name'] ?? '—'); ?></div>
                    <?php else: ?>
                      <span style="color:var(--muted); font-size:0.85rem;">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display:flex; gap:8px; justify-content:center;">
                      <form action="users.php" method="POST" onsubmit="return confirm('⚠️ Are you sure you want to delete user \'<?php echo htmlspecialchars($u['name'], ENT_QUOTES); ?>\'? All their booking records will be cascadingly deleted.')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                        <button type="submit" class="btn-danger" style="padding:6px 12px; font-size:0.8rem; border-radius:6px;">
                          Delete
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" style="text-align:center; color:var(--muted);">No users or managers registered.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
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
