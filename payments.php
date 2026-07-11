<?php
// user/payments.php
require_once '../config.php';
check_auth('user');

$user = get_user_by_email($_SESSION['email']);
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);
$user_name = $user ? $user['name'] : $_SESSION['name'];
$user_email = $_SESSION['email'];
$user_initials = strtoupper(substr($user_name, 0, 1));

$error = '';
$success = '';
$show_checkout = false;
$show_receipt = false;
$active_booking = null;

// Handle POST complete payment simulation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_payment') {
    $booking_id = $_POST['booking_id'] ?? '';
    $payment_method = strtolower(trim($_POST['payment_method'] ?? 'card'));
    $fpx_bank = trim($_POST['fpx_bank'] ?? '');
    $booking = get_booking_by_id($booking_id);

    if ($booking && ((isset($booking['user_id']) && $booking['user_id'] === $user_id) || strtolower($booking['user_email']) === strtolower($user_email))) {
      if ($booking['booking_status'] !== 'Approved') {
        $error = 'Payment not allowed. Booking is not approved by admin.';
      } elseif ($booking['payment_status'] !== 'Unpaid') {
        $error = 'This booking has already been paid or is pending verification.';
      } else {
        if ($payment_method === 'toyyibpay') {
          // Redirect to ToyyibPay dev gateway via redirect handler
          header('Location: toyyibpay_redirect.php?booking_id=' . urlencode($booking_id) . '&bank=' . urlencode($fpx_bank));
          exit();
        } elseif ($payment_method === 'cash') {
          $error = 'Cash payments have been disabled.';
        } else {
          $transaction_reference = 'CARD-' . rand(100000, 999999);
          if (pay_booking($booking_id, 'Card', 'Card', $transaction_reference)) {
            $success = 'Payment completed successfully by card!';
            header("refresh:2;url=payments.php?receipt_booking_id=" . $booking_id);
            exit();
          }
          $error = 'Unable to process card payment right now.';
        }
      }
    } else {
        $error = 'Invalid booking transaction.';
    }
}

// Check if displaying checkout form
if (isset($_GET['pay_booking_id'])) {
    $pay_booking_id = $_GET['pay_booking_id'];
    $active_booking = get_booking_by_id($pay_booking_id);
    if ($active_booking && ((isset($active_booking['user_id']) && $active_booking['user_id'] === $user_id) || strtolower($active_booking['user_email']) === strtolower($user_email))) {
      if ($active_booking['booking_status'] !== 'Approved') {
        $error = 'Cannot proceed to payment. Booking is not approved.';
      } elseif ($active_booking['payment_status'] === 'Unpaid') {
        $show_checkout = true;
      } else {
        // Already paid, redirect to receipt
        header("Location: payments.php?receipt_booking_id=" . $pay_booking_id);
        exit();
      }
    } else {
        $error = 'Booking request not found or unauthorized.';
    }
}

// Check if displaying receipt page
if (isset($_GET['receipt_booking_id'])) {
    $receipt_booking_id = $_GET['receipt_booking_id'];
    $active_booking = get_booking_by_id($receipt_booking_id);
    if ($active_booking) {
        $conn = db_get_connection();
        if ($conn) {
            $raw_id = intval(str_replace('UB', '', $receipt_booking_id));
            $pm_val = db_query_scalar("SELECT payment_method FROM bookings_data WHERE booking_id = $raw_id", null);
            if ($pm_val !== null) {
                if (stripos($pm_val, 'toyyib') !== false) {
                    $active_booking['payment_method'] = 'Toyyib';
                } else {
                    $active_booking['payment_method'] = 'Card';
                }
            }
        }
    }
    if ($active_booking && ((isset($active_booking['user_id']) && $active_booking['user_id'] === $user_id) || strtolower($active_booking['user_email']) === strtolower($user_email))) {
        if ($active_booking['payment_status'] === 'Paid') {
            $show_receipt = true;
        } else {
            $error = 'This booking has not been paid yet.';
        }
    } else {
        $error = 'Receipt query not found or unauthorized.';
    }
}

// Retrieve bookings list (merge session bookings with shared JSON store)
$unpaid_bookings = [];
$paid_bookings = [];
$user_bookings = get_bookings_by_user($user_id);

// Merge shared bookings from central JSON store
$shared_file = __DIR__ . '/../data/bookings.json';
if (file_exists($shared_file)) {
    $txt = @file_get_contents($shared_file);
    $decoded = @json_decode($txt, true);
    if (is_array($decoded)) {
        foreach ($decoded as $bid => $bval) {
            if ((isset($bval['user_id']) && $bval['user_id'] === $user_id) ||
                (!isset($bval['user_id']) && strtolower($bval['user_email'] ?? '') === strtolower($user_email))) {
                if (!isset($user_bookings[$bid])) $user_bookings[$bid] = $bval;
            }
        }
    }
}

foreach ($user_bookings as $booking_id => $booking) {
  if ($booking['payment_status'] === 'Unpaid' && $booking['booking_status'] === 'Approved') {
    $unpaid_bookings[$booking_id] = $booking;
  } elseif (in_array($booking['payment_status'], ['Paid', 'Refund Requested', 'Refunded'])) {
    $paid_bookings[$booking_id] = $booking;
  }
}
krsort($unpaid_bookings);
krsort($paid_bookings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book – Payments & Receipts</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=2.5">
</head>
<body class="sidebar-closed">

  <!-- FIXED TOP HEADER -->
  <header class="dashboard-header no-print">
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
    
    <!-- SIDEBAR (HIDDEN ON PRINT) -->
    <aside class="sidebar no-print" id="sidebarMenu">
            <nav class="sidebar-nav">
        <a href="<?php echo url('user/dashboard.php'); ?>" class="nav-link nav-item"><?php echo get_icon('dashboard'); ?> Dashboard</a>
        <a href="<?php echo url('user/add_booking.php'); ?>" class="nav-link nav-item"><?php echo get_icon('add-booking'); ?> Add New Booking</a>
        <a href="<?php echo url('user/bookingcalendar.php'); ?>" class="nav-link nav-item"><?php echo get_icon('bookings'); ?> Calendar</a>
        <a href="<?php echo url('user/my_bookings.php'); ?>" class="nav-link nav-item"><?php echo get_icon('my-bookings'); ?> My Bookings</a>
        <a href="<?php echo url('user/payments.php'); ?>" class="nav-link nav-item active"><?php echo get_icon('payments'); ?> Payments &amp; Receipts</a>
        <a href="<?php echo url('user/profile.php'); ?>" class="nav-link nav-item"><?php echo get_icon('profile'); ?> Profile</a>
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
      
      <!-- PAGE HEADER (HIDDEN ON PRINT) -->
      <div class="page-header no-print">
        <h1 class="page-title"> ⚠︎ Pending Payments</h1>
        <p class="page-desc">Complete payments for pending bookings and view printable receipts.</p>
      </div>

      <!-- Flash alerts -->
      <?php if (!empty($error)): ?>
        <div class="alert err no-print" style="display:block;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert ok no-print" style="display:block;"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      <?php echo display_flash_message(); ?>

      <?php if ($show_checkout): ?>
        
        <!-- SIMULATED CHECKOUT PROCESS -->
        <div class="no-print" style="max-width: 600px; margin: 0 auto;">
          <div class="dashboard-panel">
            <h2 class="panel-title">💳 Complete Payment</h2>
            
            <div style="background: var(--teal-pale); border-radius: 8px; padding: 16px; margin-bottom: 24px; border: 1.5px solid var(--border);">
              <div style="font-size: 0.8rem; font-weight: 700; color: var(--teal-dark); margin-bottom: 6px; text-transform: uppercase;">Transaction Details</div>
              <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 8px;">
                <span>Resource:</span>
                <strong><?php echo htmlspecialchars($active_booking['resource_name']); ?></strong>
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 8px;">
                <span>Booking ID:</span>
                <strong><?php echo htmlspecialchars($active_booking['booking_id']); ?></strong>
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 8px;">
                <span>Date & Time Slot:</span>
                <strong><?php echo htmlspecialchars($active_booking['date']) . ' (' . htmlspecialchars($active_booking['slot']) . ')'; ?></strong>
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 1.1rem; border-top: 1px solid var(--border); padding-top: 10px; margin-top: 6px;">
                <span style="font-weight: 700; color: var(--teal-dark);">Amount Due:</span>
                <strong style="color: var(--teal); font-family: 'Space Grotesk', sans-serif;"><?php echo format_currency($active_booking['amount']); ?></strong>
              </div>
            </div>

            <!-- Fake Credit Card Form -->
            <form action="payments.php" method="POST">
              <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($active_booking['booking_id']); ?>">
              <input type="hidden" name="action" value="complete_payment">

              <div class="field">
                <label class="lbl">Select Payment Method</label>
                <div style="display: flex; gap: 12px; margin-bottom: 14px; flex-wrap: wrap;">
                  <label class="payment-option active" style="flex: 1;" onclick="selectPaymentMethod('card')">
                    <input type="radio" name="payment_method" value="card" checked>
                    <span>Credit / Debit Card</span>
                  </label>
                  <!-- Cash payments removed -->
                  <label class="payment-option" style="flex: 1;" onclick="selectPaymentMethod('toyyibpay')">
                    <input type="radio" name="payment_method" value="toyyibpay">
                    <span>ToyyibPay FPX</span>
                  </label>
                </div>
              </div>

              <div id="card-section">
                <div class="field">
                  <label class="lbl">Cardholder Name</label>
                  <input type="text" name="cardholder_name" placeholder="John Doe" required>
                </div>

                <div class="field">
                  <label class="lbl">Card Number</label>
                  <input type="text" name="card_number" placeholder="4111 2222 3333 4444" minlength="16" maxlength="19" required>
                </div>

                <div class="two-col">
                  <div class="field">
                    <label class="lbl">Expiration Date</label>
                    <input type="text" name="card_expiry" placeholder="MM/YY" maxlength="5" required>
                  </div>
                  <div class="field">
                    <label class="lbl">CVV / Security Code</label>
                    <input type="password" name="card_cvv" placeholder="123" maxlength="3" required>
                  </div>
                </div>
              </div>

              <!-- Cash payment section removed -->

              <div id="toyyibpay-section" style="display:none; background: #f8fafc; border: 1px solid #d1d5db; border-radius: 12px; padding: 16px; margin-top: 16px;">
                <p style="margin-bottom: 12px; font-size:0.95rem;">Proceed with ToyyibPay FPX payment.</p>
              </div>

              <div style="font-size: 0.75rem; color: var(--muted); margin-bottom: 20px; line-height: 1.4;">
                ⚠️ This checkout screen connects to ToyyibPay sandbox for payment processing.
              </div>

              <div class="action-row">
                <a href="<?php echo url('user/payments.php'); ?>" class="btn-secondary" style="flex: 1;">Cancel Payment</a>
                <button type="submit" class="btn-main" style="flex: 1; font-size: 0.9rem;">Complete Payment</button>
              </div>
            </form>
          </div>
        </div>

      <?php elseif ($show_receipt): ?>
        
        <!-- PRINTABLE RECEIPT TEMPLATE -->
        <div class="receipt-wrapper" style="margin-top: 20px;">
          <div class="receipt-header">
            <div class="receipt-logo">Uni.<span class="brand-book">Book</span><span class="brand-dot"></span></div>
            <div class="receipt-title">Payment Receipt</div>
            <div style="font-size: 0.8rem; color: var(--muted); margin-top: 4px;">University Booking Portal</div>
          </div>

          <div class="receipt-row">
            <span class="receipt-lbl">Receipt Number:</span>
            <span class="receipt-val" style="font-weight: 700; color: var(--teal-dark);"><?php echo htmlspecialchars($active_booking['receipt_id']); ?></span>
          </div>

          <div class="receipt-row">
            <span class="receipt-lbl">Booking ID:</span>
            <span class="receipt-val" style="font-weight: 600;"><?php echo htmlspecialchars($active_booking['booking_id']); ?></span>
          </div>

          <div class="receipt-row">
            <span class="receipt-lbl">Paid By:</span>
            <span class="receipt-val"><?php echo htmlspecialchars($user_name); ?><br><span style="font-weight: normal; font-size: 0.78rem; color: var(--muted);"><?php echo htmlspecialchars($user_email); ?></span></span>
          </div>

          <div class="receipt-row">
            <span class="receipt-lbl">Resource:</span>
            <span class="receipt-val"><?php echo htmlspecialchars($active_booking['resource_name']); ?><br><span style="font-weight: normal; font-size: 0.78rem; color: var(--muted);"><?php echo htmlspecialchars($active_booking['category']); ?></span></span>
          </div>

          <div class="receipt-row">
            <span class="receipt-lbl">Booking Date:</span>
            <span class="receipt-val"><?php echo htmlspecialchars(date('M d, Y', strtotime($active_booking['date']))); ?></span>
          </div>

          <div class="receipt-row">
            <span class="receipt-lbl">Time Slot:</span>
            <span class="receipt-val"><?php echo htmlspecialchars($active_booking['slot']); ?></span>
          </div>

          <div class="receipt-row">
            <span class="receipt-lbl">Purpose of Use:</span>
            <span class="receipt-val"><?php echo htmlspecialchars($active_booking['booking_purpose'] ?? '—'); ?></span>
          </div>

          <div class="receipt-row">
            <span class="receipt-lbl">Payment Method:</span>
            <span class="receipt-val"><?php echo htmlspecialchars($active_booking['payment_method'] ?? 'Unknown'); ?></span>
          </div>

          <div class="receipt-row">
            <span class="receipt-lbl">Payment Status:</span>
            <span class="receipt-val" style="color: var(--success); font-weight: 700; text-transform: uppercase;">PAID</span>
          </div>

          <div class="receipt-row">
            <span class="receipt-lbl">Transaction Date:</span>
            <span class="receipt-val"><?php echo htmlspecialchars(date('M d, Y H:i A', strtotime($active_booking['payment_date']))); ?></span>
          </div>

          <div class="receipt-total">
            <span class="receipt-lbl">Total Paid:</span>
            <span class="receipt-val"><?php echo format_currency($active_booking['amount']); ?></span>
          </div>

          <div class="receipt-footer">
            <p style="font-weight: 600; margin-bottom: 4px;">Thank you for booking with Uni.Book!</p>
            <p>Please present this receipt at the venue or to personnel if required.</p>
          </div>
        </div>

        <div style="text-align: center; margin-top: 30px;" class="no-print">
          <button onclick="window.print()" class="btn-main" style="width: auto; display: inline-flex; padding: 12px 30px; font-size: 0.9rem;">
            🖨️ Print Receipt
          </button>
          <a href="<?php echo url('user/my_bookings.php'); ?>" class="btn-secondary" style="margin-left: 10px; padding: 11px 24px; font-size: 0.9rem;">
            Back to Bookings
          </a>
        </div>

      <?php else: ?>
        
        <!-- UNPAID INVOICES LIST -->
        <?php if (empty($unpaid_bookings)): ?>
          <div class="table-container" style="padding: 40px;">
            <div class="empty-state">
              <div class="empty-state-icon" style="color:var(--success);">✓</div>
              <h3 class="empty-state-title">No Unpaid Invoices</h3>
              <p class="empty-state-desc">
                All of your resource bookings have been paid in full. Thank you for your prompt payments.
              </p>
              <a href="<?php echo url('user/my_bookings.php'); ?>" class="btn-secondary" style="margin-top: 20px;">
                View My Bookings
              </a>
            </div>
          </div>
        <?php else: ?>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Booking ID</th>
                  <th>Resource Name</th>
                  <th>Date & Time Slot</th>
                  <th>Amount</th>
                  <th>Booking Status</th>
                  <th>Payment Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($unpaid_bookings as $booking_id => $bk): ?>
                  <tr>
                    <td style="font-weight: 700; color: var(--teal-dark);"><?php echo htmlspecialchars($booking_id); ?></td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($bk['resource_name']); ?></td>
                    <td><?php echo htmlspecialchars($bk['date']) . '<br><span style="font-size:0.75rem; color:var(--muted);">' . htmlspecialchars($bk['slot']) . '</span>'; ?></td>
                    <td style="font-weight: 600; color: var(--teal);"><?php echo format_currency($bk['amount']); ?></td>
                    <td>
                      <span class="badge <?php echo strtolower($bk['booking_status']); ?>">
                        <?php echo htmlspecialchars($bk['booking_status']); ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge unpaid">
                        <?php echo htmlspecialchars($bk['payment_status']); ?>
                      </span>
                    </td>
                    <td>
                      <a href="payments.php?pay_booking_id=<?php echo $booking_id; ?>" class="btn-main" style="padding: 6px 12px; font-size: 0.8rem; width: auto; display: inline-flex;">
                        Pay Now
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <!-- PAID BOOKINGS LIST -->
        <div style="margin-top: 40px;" class="no-print">
          <h2 class="page-title" style="margin-bottom: 16px;"> ☑ Paid Bookings & Receipts</h2>
          <?php if (empty($paid_bookings)): ?>
            <div class="table-container" style="padding: 40px; text-align: center;">
              <p style="color: var(--muted);">No paid bookings found.</p>
            </div>
          <?php else: ?>
            <div class="table-container">
              <table>
                <thead>
                  <tr>
                    <th>Booking ID</th>
                    <th>Resource Name</th>
                    <th>Date & Time Slot</th>
                    <th>Amount</th>
                    <th>Booking Status</th>
                    <th>Payment Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($paid_bookings as $booking_id => $bk): ?>
                    <tr>
                      <td style="font-weight: 700; color: var(--teal-dark);"><?php echo htmlspecialchars($booking_id); ?></td>
                      <td style="font-weight: 600;"><?php echo htmlspecialchars($bk['resource_name']); ?></td>
                      <td><?php echo htmlspecialchars($bk['date']) . '<br><span style="font-size:0.75rem; color:var(--muted);">' . htmlspecialchars($bk['slot']) . '</span>'; ?></td>
                      <td style="font-weight: 600; color: var(--teal);"><?php echo format_currency($bk['amount']); ?></td>
                      <td>
                        <span class="badge <?php echo strtolower($bk['booking_status']); ?>">
                          <?php echo htmlspecialchars($bk['booking_status']); ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge <?php echo strtolower($bk['payment_status']); ?>">
                          <?php echo htmlspecialchars($bk['payment_status']); ?>
                        </span>
                      </td>
                      <td>
                        <a href="payments.php?receipt_booking_id=<?php echo urlencode($booking_id); ?>" class="btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; width: auto; display: inline-flex; font-family: 'Space Grotesk', sans-serif;">
                          View Receipt
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      <?php endif; ?>

    </main>
  </div>

  <script>
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-closed');
    }

    function selectPaymentMethod(method) {
      const cardSection = document.getElementById('card-section');
      const toyyibSection = document.getElementById('toyyibpay-section');
      const options = document.querySelectorAll('.payment-option');
      options.forEach(opt => opt.classList.remove('active'));
      const selectedOption = Array.from(options).find(opt => opt.querySelector('input[name="payment_method"]').value === method);
      if (selectedOption) selectedOption.classList.add('active');

      document.querySelectorAll('input[name="payment_method"]').forEach(input => {
        input.checked = input.value === method;
      });

      cardSection.style.display = method === 'card' ? 'block' : 'none';
      toyyibSection.style.display = method === 'toyyibpay' ? 'block' : 'none';

      document.querySelectorAll('#card-section input').forEach(input => {
        input.required = method === 'card';
      });
    }
  </script>


</body>
</html>


