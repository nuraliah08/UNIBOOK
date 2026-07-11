<?php
// register.php
require_once 'config.php';

$error = '';
$success = '';
$initial_tab = 'user';

// Fetch categories from DB for manager category dropdown
$categories = [];
$conn = db_get_connection();
if ($conn) {
    $cat_res = mysqli_query($conn, "SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
    if ($cat_res) {
        while ($row = mysqli_fetch_assoc($cat_res)) {
            $categories[] = $row;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $initial_tab = $_POST['registration_type'] ?? 'user';

    if ($initial_tab === 'manager') {
        $name = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $department = trim($_POST['department'] ?? '');
        $category_id = trim($_POST['category_id'] ?? '');

        // Server-side validation
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($department) || empty($category_id)) {
            $error = 'All fields except phone number are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $email_lower = strtolower($email);
            // Check for duplicate email in users
            if (get_user_by_email($email_lower)) {
                $error = 'An account with this email address already exists.';
            } else {
                if ($conn) {
                    $name_safe = mysqli_real_escape_string($conn, $name);
                    $phone_safe = !empty($phone) ? "'" . mysqli_real_escape_string($conn, $phone) . "'" : "NULL";
                    $email_safe = mysqli_real_escape_string($conn, $email_lower);
                    $pass_hash = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_DEFAULT));
                    $dept_safe = mysqli_real_escape_string($conn, $department);
                    $cat_id_safe = intval($category_id);

                    // Insert with role_id = 3
                    $sql = "INSERT INTO users (name, phone, email, password, role_id, department, category_id) 
                            VALUES ('$name_safe', $phone_safe, '$email_safe', '$pass_hash', 3, '$dept_safe', $cat_id_safe)";
                    
                    if (mysqli_query($conn, $sql) !== false) {
                        $success = 'Registration successful! Redirecting to login...';
                        header("refresh:2;url=login.php");
                    } else {
                        $error = 'An error occurred during registration. Please try again.';
                    }
                } else {
                    $error = 'Database connection failed. Please try again.';
                }
            }
        }
    } else {
        $name = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Server-side validation
        if (empty($name) || empty($phone) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $email_lower = strtolower($email);
            // Check for duplicate email in sessions
            if (get_user_by_email($email_lower)) {
                $error = 'An account with this email address already exists.';
            } else {
                // Store user in session
                if (register_user($name, $phone, $email_lower, $password)) {
                    $success = 'Registration successful! Redirecting to login...';
                    // Auto redirect to login after 2 seconds
                    header("refresh:2;url=login.php");
                } else {
                    $error = 'An error occurred. Please try again.';
                }
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
  <title>Uni.Book – Create Account</title>
  <link rel="stylesheet" href="assets/css/style.css?v=2.5">
</head>
<body>

  <!-- PUBLIC HEADER -->
  <header class="pub-header">
    <a href="<?php echo url('index.php'); ?>" class="brand">
      <div class="brand-logo">Uni<span class="brand-dot-center"></span><span class="brand-book">Book</span></div>
      <div class="brand-sub">University Booking System</div>
    </a>
    <div class="header-actions">
      <a href="<?php echo url('login.php'); ?>" class="btn-secondary">Sign In</a>
    </div>
  </header>

  <!-- SPLIT CARD CONTAINER -->
  <div class="split-container">
    <div class="split-card">
      
      <!-- Decorative left panel -->
      <div class="split-deco">
        <h2 class="deco-title">Join <em>Uni•Book</em></h2>
        <p class="deco-body">Create an account to gain instant access to seminar rooms, project equipment, transport vans, and media services.</p>
        
        <div class="deco-chips">
          <div class="deco-chip">
            <div class="deco-chip-icon">✨</div>
            <span>Fast, automated scheduling</span>
          </div>
          <div class="deco-chip">
            <div class="deco-chip-icon">🏷️</div>
            <span>Academic discounts automatically applied</span>
          </div>
          <div class="deco-chip">
            <div class="deco-chip-icon">🔔</div>
            <span>Real-time booking status notifications</span>
          </div>
        </div>
      </div>

      <!-- Registration form right panel -->
      <div class="split-form">
        <div class="form-heading">Create Account</div>
        <div class="form-sub">Sign up for your new university booking account</div>

        <!-- Inline alert messages -->
        <?php if (!empty($error)): ?>
          <div class="alert err" style="display: block;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="alert ok" style="display: block;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Tab Selector using existing styles in style.css -->
        <div class="tab-bar">
          <button type="button" class="tab-btn active" id="tab-btn-user" onclick="switchTab('user')">Register as User</button>
          <button type="button" class="tab-btn" id="tab-btn-manager" onclick="switchTab('manager')">Register as Manager</button>
        </div>

        <form action="register.php" method="POST" onsubmit="return validateForm()">
          <input type="hidden" id="registration_type" name="registration_type" value="<?php echo htmlspecialchars($initial_tab); ?>">

          <!-- User Fields Container -->
          <div id="user-fields-container">
            <div class="field">
              <label class="lbl" for="r-name">Full Name *</label>
              <input type="text" id="r-name" name="fullname" placeholder="e.g. John Doe" value="<?php echo ($initial_tab === 'user' && isset($_POST['fullname'])) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
            </div>

            <div class="two-col">
              <div class="field">
                <label class="lbl" for="r-phone">Phone Number *</label>
                <input type="tel" id="r-phone" name="phone" placeholder="e.g. +1234567890" value="<?php echo ($initial_tab === 'user' && isset($_POST['phone'])) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
              </div>
              <div class="field">
                <label class="lbl" for="r-email">Email Address *</label>
                <input type="email" id="r-email" name="email" placeholder="e.g. student@unibook.edu" value="<?php echo ($initial_tab === 'user' && isset($_POST['email'])) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
              </div>
            </div>

            <div class="two-col">
              <div class="field">
                <label class="lbl" for="r-pass">Password *</label>
                <input type="password" id="r-pass" name="password" placeholder="Min. 6 characters" oninput="updateStrength()" required>
                <div class="strength-bar">
                  <div class="strength-seg" id="seg1"></div>
                  <div class="strength-seg" id="seg2"></div>
                  <div class="strength-seg" id="seg3"></div>
                </div>
              </div>
              <div class="field">
                <label class="lbl" for="r-confirm">Confirm Password *</label>
                <input type="password" id="r-confirm" name="confirm_password" placeholder="Repeat password" required>
              </div>
            </div>
          </div>

          <!-- Manager Fields Container -->
          <div id="manager-fields-container" style="display: none;">
            <div class="field">
              <label class="lbl" for="m-name">Full Name *</label>
              <input type="text" id="m-name" name="fullname" placeholder="e.g. John Doe" value="<?php echo ($initial_tab === 'manager' && isset($_POST['fullname'])) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
            </div>

            <div class="two-col">
              <div class="field">
                <label class="lbl" for="m-phone">Phone Number</label>
                <input type="tel" id="m-phone" name="phone" placeholder="e.g. +1234567890" value="<?php echo ($initial_tab === 'manager' && isset($_POST['phone'])) ? htmlspecialchars($_POST['phone']) : ''; ?>">
              </div>
              <div class="field">
                <label class="lbl" for="m-email">Email Address *</label>
                <input type="email" id="m-email" name="email" placeholder="e.g. manager@unibook.com" value="<?php echo ($initial_tab === 'manager' && isset($_POST['email'])) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
              </div>
            </div>

            <div class="two-col">
              <div class="field">
                <label class="lbl" for="m-pass">Password *</label>
                <input type="password" id="m-pass" name="password" placeholder="Min. 6 characters" oninput="updateManagerStrength()" required>
                <div class="strength-bar">
                  <div class="strength-seg" id="m-seg1"></div>
                  <div class="strength-seg" id="m-seg2"></div>
                  <div class="strength-seg" id="m-seg3"></div>
                </div>
              </div>
              <div class="field">
                <label class="lbl" for="m-confirm">Confirm Password *</label>
                <input type="password" id="m-confirm" name="confirm_password" placeholder="Repeat password" required>
              </div>
            </div>

            <div class="two-col">
              <div class="field">
                <label class="lbl" for="m-dept">University Department/Faculty *</label>
                <input type="text" id="m-dept" name="department" placeholder="e.g. Faculty of Computer Science" value="<?php echo ($initial_tab === 'manager' && isset($_POST['department'])) ? htmlspecialchars($_POST['department']) : ''; ?>" required>
              </div>
              <div class="field">
                <label class="lbl" for="m-category">Resource Category to Manage *</label>
                <select id="m-category" name="category_id" required>
                  <option value="">-- Select Category --</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-main" style="margin-top: 10px;">
            Create Account &nbsp;→
          </button>
        </form>

        <div class="switch-link">
          Already registered? <a href="<?php echo url('login.php'); ?>">Sign in here</a>
        </div>
      </div>

    </div>
  </div>

  <script>
    function switchTab(tab) {
      const userBtn = document.getElementById('tab-btn-user');
      const managerBtn = document.getElementById('tab-btn-manager');
      const userFields = document.getElementById('user-fields-container');
      const managerFields = document.getElementById('manager-fields-container');
      const regTypeInput = document.getElementById('registration_type');

      if (tab === 'user') {
        userBtn.classList.add('active');
        managerBtn.classList.remove('active');
        userFields.style.display = 'block';
        managerFields.style.display = 'none';
        regTypeInput.value = 'user';
        
        toggleInputs(userFields, true);
        toggleInputs(managerFields, false);
      } else {
        managerBtn.classList.add('active');
        userBtn.classList.remove('active');
        managerFields.style.display = 'block';
        userFields.style.display = 'none';
        regTypeInput.value = 'manager';
        
        toggleInputs(userFields, false);
        toggleInputs(managerFields, true);
      }
    }

    function toggleInputs(container, enable) {
      const inputs = container.querySelectorAll('input, select, textarea');
      inputs.forEach(input => {
        if (enable) {
          input.removeAttribute('disabled');
          if (input.dataset.required === 'true') {
            input.setAttribute('required', 'required');
          }
        } else {
          if (input.hasAttribute('required')) {
            input.dataset.required = 'true';
            input.removeAttribute('required');
          }
          input.setAttribute('disabled', 'disabled');
        }
      });
    }

    function updateStrength() {
      const v = document.getElementById('r-pass').value;
      const segs = [
        document.getElementById('seg1'), 
        document.getElementById('seg2'), 
        document.getElementById('seg3')
      ];
      
      segs.forEach(s => s.className = 'strength-seg');
      
      if (v.length === 0) return;
      
      let score = 0;
      if (v.length >= 6) score++;
      if (v.length >= 10 && /[A-Z]/.test(v)) score++;
      if (/[0-9]/.test(v) && /[^a-zA-Z0-9]/.test(v)) score++;
      
      const cls = score === 1 ? 'weak' : score === 2 ? 'medium' : 'strong';
      for (let i = 0; i < score; i++) {
        segs[i].classList.add(cls);
      }
    }

    function updateManagerStrength() {
      const v = document.getElementById('m-pass').value;
      const segs = [
        document.getElementById('m-seg1'), 
        document.getElementById('m-seg2'), 
        document.getElementById('m-seg3')
      ];
      
      segs.forEach(s => s.className = 'strength-seg');
      
      if (v.length === 0) return;
      
      let score = 0;
      if (v.length >= 6) score++;
      if (v.length >= 10 && /[A-Z]/.test(v)) score++;
      if (/[0-9]/.test(v) && /[^a-zA-Z0-9]/.test(v)) score++;
      
      const cls = score === 1 ? 'weak' : score === 2 ? 'medium' : 'strong';
      for (let i = 0; i < score; i++) {
        segs[i].classList.add(cls);
      }
    }

    function validateForm() {
      const regType = document.getElementById('registration_type').value;
      if (regType === 'user') {
        const pass = document.getElementById('r-pass').value;
        const confirm = document.getElementById('r-confirm').value;
        
        if (pass.length < 6) {
          alert('Password must be at least 6 characters long.');
          return false;
        }
        if (pass !== confirm) {
          alert('Passwords do not match. Please verify.');
          return false;
        }
      } else {
        const pass = document.getElementById('m-pass').value;
        const confirm = document.getElementById('m-confirm').value;
        
        if (pass.length < 6) {
          alert('Password must be at least 6 characters long.');
          return false;
        }
        if (pass !== confirm) {
          alert('Passwords do not match. Please verify.');
          return false;
        }
      }
      return true;
    }

    // Initialize tab state on page load (e.g. if validation fails and postback happens)
    document.addEventListener('DOMContentLoaded', function() {
      switchTab('<?php echo htmlspecialchars($initial_tab); ?>');
    });
  </script>


</body>
</html>


