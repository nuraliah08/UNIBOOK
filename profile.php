<?php
// user/profile.php
require_once '../config.php';
check_auth('user');

$user = get_user_by_email($_SESSION['email']);
$user_name = $user ? $user['name'] : $_SESSION['name'];
$user_email = $_SESSION['email'];
$user_phone = $user ? $user['phone'] : ($_SESSION['phone'] ?? '');
$user_initials = strtoupper(substr($user_name, 0, 1));

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $name = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($name) || empty($phone)) {
            $error = 'Full name and phone number cannot be empty.';
        } else {
            // Update user details
            update_user_profile($user_email, $name, $phone);
            
            // Re-assign local variables
            $user_name = $name;
            $user_phone = $phone;
            $user_initials = strtoupper(substr($user_name, 0, 1));
            
            $success = 'Profile details updated successfully!';
        }
    } elseif ($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_new_password'] ?? '';
        
        $user_data = get_user_by_email($user_email);
        
        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $error = 'All password fields are required.';
        } elseif (!$user_data || !password_verify($current_pass, $user_data['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif ($new_pass !== $confirm_pass) {
            $error = 'New passwords do not match.';
        } else {
            // Hash and store new password
            update_user_password($user_email, $new_pass);
            $success = 'Password changed successfully!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book – User Profile</title>
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
        <a href="<?php echo url('user/dashboard.php'); ?>" class="nav-link nav-item"><?php echo get_icon('dashboard'); ?> Dashboard</a>
        <a href="<?php echo url('user/add_booking.php'); ?>" class="nav-link nav-item"><?php echo get_icon('add-booking'); ?> Add New Booking</a>
        <a href="<?php echo url('user/bookingcalendar.php'); ?>" class="nav-link nav-item"><?php echo get_icon('bookings'); ?> Calendar</a>
        <a href="<?php echo url('user/my_bookings.php'); ?>" class="nav-link nav-item"><?php echo get_icon('my-bookings'); ?> My Bookings</a>
        <a href="<?php echo url('user/payments.php'); ?>" class="nav-link nav-item"><?php echo get_icon('payments'); ?> Payments &amp; Receipts</a>
        <a href="<?php echo url('user/profile.php'); ?>" class="nav-link nav-item active"><?php echo get_icon('profile'); ?> Profile</a>
        <a href="<?php echo url('logout.php'); ?>" class="nav-link nav-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;"><?php echo get_icon('logout'); ?> Logout</a>
      </nav>
      <div class="sidebar-user">
        <div class="user-avatar"><?php echo $user_initials; ?></div>
        <div class="user-meta">
          <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
          <span class="user-role">user</span>
        </div>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
      
      <!-- PAGE HEADER -->
      <div class="page-header">
        <h1 class="page-title"> 🛠 Profile Settings</h1>
        <p class="page-desc">Manage your account details and update your security credentials.</p>
      </div>

      <!-- Alerts -->
      <?php if (!empty($error)): ?>
        <div class="alert err" style="display:block;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert ok" style="display:block;"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <!-- PROFILE FORM GRID -->
      <div class="dashboard-grid-2">
        
        <!-- EDIT DETAILS PANEL -->
        <div class="dashboard-panel">
          <h2 class="panel-title">👤 Update Personal Information</h2>
          
          <form action="profile.php" method="POST">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="field">
              <label class="lbl">Registered Email Address</label>
              <input type="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled>
              <span style="font-size: 0.72rem; color: var(--muted); margin-top: 4px; display: inline-block;">
                Registered email addresses cannot be modified as they serve as your account unique identifier.
              </span>
            </div>

            <div class="field">
              <label class="lbl">Full Name *</label>
              <input type="text" name="fullname" value="<?php echo htmlspecialchars($user_name); ?>" required>
            </div>

            <div class="field">
              <label class="lbl">Phone Number *</label>
              <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_phone); ?>" required>
            </div>

            <button type="submit" class="btn-main" style="width: auto; padding: 10px 24px; font-size: 0.9rem; margin-top: 10px;">
              Save Profile Changes
            </button>
          </form>
        </div>

        <!-- CHANGE PASSWORD PANEL -->
        <div class="dashboard-panel">
          <h2 class="panel-title">🔒 Change Password</h2>
          
          <form action="profile.php" method="POST" onsubmit="return validatePasswords()">
            <input type="hidden" name="action" value="change_password">
            
            <div class="field">
              <label class="lbl">Current Password *</label>
              <input type="password" name="current_password" id="p-current" placeholder="••••••••" required>
            </div>

            <div class="field">
              <label class="lbl">New Password *</label>
              <input type="password" name="new_password" id="p-new" placeholder="Min. 6 characters" required>
            </div>

            <div class="field">
              <label class="lbl">Confirm New Password *</label>
              <input type="password" name="confirm_new_password" id="p-confirm" placeholder="Repeat new password" required>
            </div>

            <button type="submit" class="btn-main" style="width: auto; padding: 10px 24px; font-size: 0.9rem; margin-top: 10px;">
              Change Password
            </button>
          </form>
        </div>

      </div>

    </main>
  </div>

  <script>
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-closed');
    }

    function validatePasswords() {
      const newPass = document.getElementById('p-new').value;
      const confirmPass = document.getElementById('p-confirm').value;
      
      if (newPass.length < 6) {
        alert('New password must be at least 6 characters long.');
        return false;
      }
      if (newPass !== confirmPass) {
        alert('New passwords do not match. Please verify.');
        return false;
      }
      return true;
    }
  </script>


</body>
</html>


