<?php
// login.php
require_once 'config.php';

$error = '';
$active_tab = 'user'; // default tab to show

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? 'user';
    $active_tab = $login_type;

    if ($login_type === 'admin') {
        $username = trim($_POST['admin_username'] ?? '');
        $password = $_POST['admin_password'] ?? '';

        if ($username === 'admin' && $password === 'pass1234') {
            $_SESSION['role'] = 'admin';
            $_SESSION['email'] = 'admin@unibook.edu';
            $_SESSION['name'] = 'Administrator';

            header("Location: admin/dashboard.php");
            exit();
        } else {
            $error = 'Invalid admin credentials. Access denied.';
        }
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email and password.';
        } else {
            $email_lower = strtolower($email);
            $user = get_user_by_email($email_lower);
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['role'] = strtolower($user['role'] ?? 'user');
                    $_SESSION['user_id'] = $user['id'] ?? null;
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['phone'] = $user['phone'];

                    if ($_SESSION['role'] === 'manager') {
                        set_manager_scope_from_session();
                    }

                    if ($_SESSION['role'] === 'manager') {
                        header("Location: manager/dashboard.php");
                    } else {
                        header("Location: user/dashboard.php");
                    }
                    exit();
                } else {
                    $error = 'Incorrect email or password. Please try again.';
                }
            } else {
                $error = 'Incorrect email or password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book Access Portal</title>
  <link rel="stylesheet" href="assets/css/style.css?v=2.5">
</head>
<body>

  <!-- PUBLIC HEADER -->
  <header class="pub-header">
    <a href="<?php echo url('index.php'); ?>" class="brand">
      <div class="brand-logo">Uni<span class="brand-dot-center"></span><span class="brand-book">Book</span></div>
      <div class="brand-sub">Resource Portal</div>
    </a>
    <div class="header-actions">
      <a href="<?php echo url('register.php'); ?>" class="btn-main" style="display: inline-flex; width: auto; padding: 10px 20px;">Create Account</a>
    </div>
  </header>

  <!-- SPLIT CARD CONTAINER -->
  <div class="split-container">
    <div class="split-card">
      
      <!-- Decorative left panel -->
      <div class="split-deco">
        <h2 class="deco-title">Welcome Back to Uni•Book</h2>
        <p class="deco-body">Enter the centralized portal to view status notifications, manage bookings, and generate print-ready receipts.</p>
        
        <div class="deco-chips">
          <div class="deco-chip">
            <div class="deco-chip-icon">🔐</div>
            <span>Secure role-based login</span>
          </div>
          <div class="deco-chip">
            <div class="deco-chip-icon">🖥️</div>
            <span>Multi-role dashboards</span>
          </div>
          <div class="deco-chip">
            <div class="deco-chip-icon">📝</div>
            <span>Personalised activity logs</span>
          </div>
        </div>
      </div>

      <!-- Form right panel -->
      <div class="split-form">
        <div class="tab-bar">
          <button class="tab-btn <?php echo $active_tab === 'user' ? 'active' : ''; ?>" id="tab-user" onclick="switchTab('user')">👤 User Sign In</button>
          <button class="tab-btn <?php echo $active_tab === 'admin' ? 'active' : ''; ?>" id="tab-admin" onclick="switchTab('admin')">⚙️ Admin Control</button>
        </div>

        <!-- Inline alert messages -->
        <?php if (!empty($error)): ?>
          <div class="alert err" style="display: block;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php echo display_flash_message(); ?>

        <!-- USER PANEL -->
        <div class="form-panel <?php echo $active_tab === 'user' ? 'active' : ''; ?>" id="panel-user">
          <div class="form-heading">Welcome Back</div>
          <div class="form-sub">Sign in with your registered account</div>

          <form action="login.php" method="POST">
            <input type="hidden" name="login_type" value="user">
            
            <div class="field">
              <label class="lbl" for="u-email">Email Address</label>
              <input type="email" id="u-email" name="email" placeholder="you@unibook.edu" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="field">
              <label class="lbl" for="u-pass">Password</label>
              <input type="password" id="u-pass" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn-main" style="margin-top: 10px;">
              Sign In &nbsp;→
            </button>
          </form>

          <div class="switch-link">
            No account yet? <a href="<?php echo url('register.php'); ?>">Create one free</a>
          </div>
        </div>

        <!-- ADMIN PANEL -->
        <div class="form-panel <?php echo $active_tab === 'admin' ? 'active' : ''; ?>" id="panel-admin">
          <div class="alert warn" style="display: block; padding: 8px 12px; margin-bottom: 16px; font-size: 0.78rem;">
            ⚙️ Restricted access – administrators only.
          </div>
          <div class="form-heading">Admin Console</div>
          <div class="form-sub">Enter your administrator credentials</div>

          <form action="login.php" method="POST">
            <input type="hidden" name="login_type" value="admin">
            
            <div class="field">
              <label class="lbl" for="a-user">Admin Username</label>
              <input type="text" id="a-user" name="admin_username" placeholder="admin" required autocomplete="username">
            </div>

            <div class="field">
              <label class="lbl" for="a-pass">Admin Password</label>
              <input type="password" id="a-pass" name="admin_password" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-main" style="margin-top: 10px;">
              Access Dashboard &nbsp;→
            </button>
          </form>
        </div>

      </div><!-- /split-form -->
    </div><!-- /split-card -->
  </div><!-- /split-container -->

  <script>
    function switchTab(tab) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
      
      document.getElementById('tab-' + tab).classList.add('active');
      document.getElementById('panel-' + tab).classList.add('active');
      
      // Clear error display if any
      const alertEl = document.querySelector('.alert.err');
      if (alertEl) {
        alertEl.style.display = 'none';
      }
    }
  </script>


</body>
</html>


