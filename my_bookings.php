<?php
// user/my_bookings.php
require_once '../config.php';
check_auth('user');

$user = get_user_by_email($_SESSION['email']);
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);
$user_name = $user ? $user['name'] : $_SESSION['name'];
$user_email = $_SESSION['email'];
$user_initials = strtoupper(substr($user_name, 0, 1));

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $booking_id = $_POST['booking_id'] ?? '';
    $booking = get_booking_by_id($booking_id);
    
    if ($booking && ((isset($booking['user_id']) && $booking['user_id'] === $user_id) || strtolower($booking['user_email'] ?? '') === strtolower($user_email))) {
        if ($action === 'cancel') {
            if (cancel_booking($booking_id)) {
                $b_updated = get_booking_by_id($booking_id);
                if ($b_updated && $b_updated['payment_status'] === 'Paid') {
                    set_flash_message('success', 'Booking #' . htmlspecialchars($booking_id) . ' has been cancelled. You can now request a refund.');
                } else {
                    set_flash_message('success', 'Booking #' . htmlspecialchars($booking_id) . ' has been cancelled.');
                }
            } else {
                set_flash_message('error', 'This booking cannot be cancelled.');
            }
        } elseif ($action === 'request_refund') {
            if (request_refund($booking_id)) {
                set_flash_message('success', 'Refund request submitted successfully for Booking #' . htmlspecialchars($booking_id) . '.');
            } else {
                set_flash_message('error', 'Unable to request refund for this booking.');
            }
        }
    } else {
        set_flash_message('error', 'Unauthorized action.');
    }
    header("Location: my_bookings.php");
    exit();
}

// Retrieve bookings list
$bookings = get_bookings_by_user($user_id);

// Merge shared bookings from central JSON store if not present
$shared_file = __DIR__ . '/../data/bookings.json';
if (file_exists($shared_file)) {
    $txt = @file_get_contents($shared_file);
    $decoded = @json_decode($txt, true);
    if (is_array($decoded)) {
      foreach ($decoded as $bid => $bval) {
        if ((isset($bval['user_id']) && $bval['user_id'] === $user_id) ||
          (!isset($bval['user_id']) && strtolower($bval['user_email'] ?? '') === strtolower($user_email))) {
          if (!isset($bookings[$bid])) {
            // Ensure pickup_address is present by looking up the resource
            if (empty($bval['pickup_address'])) {
              $res = get_resource_by_id($bval['resource_id'] ?? '');
              $bval['pickup_address'] = $res['pickup_address'] ?? null;
            }
            $bookings[$bid] = $bval;
          }
        }
      }
    }
}
krsort($bookings); // Show newest bookings first
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book – My Bookings</title>
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
    
    <!-- SIDEBAR -->
    <aside class="sidebar no-print" id="sidebarMenu">
            <nav class="sidebar-nav">
        <a href="<?php echo url('user/dashboard.php'); ?>" class="nav-link nav-item"><?php echo get_icon('dashboard'); ?> Dashboard</a>
        <a href="<?php echo url('user/add_booking.php'); ?>" class="nav-link nav-item"><?php echo get_icon('add-booking'); ?> Add New Booking</a>
        <a href="<?php echo url('user/bookingcalendar.php'); ?>" class="nav-link nav-item"><?php echo get_icon('bookings'); ?> Calendar</a>
        <a href="<?php echo url('user/my_bookings.php'); ?>" class="nav-link nav-item active"><?php echo get_icon('my-bookings'); ?> My Bookings</a>
        <a href="<?php echo url('user/payments.php'); ?>" class="nav-link nav-item"><?php echo get_icon('payments'); ?> Payments &amp; Receipts</a>
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
      
      <!-- PAGE HEADER -->
      <div class="page-header">
        <h1 class="page-title"> ⫶☰ My Bookings</h1>
        <p class="page-desc">Track status and manage your resource reservations.</p>
      </div>

      <!-- Flash alerts -->
      <?php echo display_flash_message(); ?>

      <!-- BOOKINGS TABLE -->
      <div class="table-container">
        <?php if (empty($bookings)): ?>
          <div style="padding: 40px; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 10px;">📖</div>
            <h3 style="font-size: 1.25rem; margin-bottom: 8px; color: var(--teal-dark);">No Bookings Found</h3>
            <p style="color: var(--muted); margin-bottom: 20px;">You haven't reserved any university resources yet.</p>
            <a href="add_booking.php" class="btn-main" style="width: auto; display: inline-flex; padding: 10px 24px;">Book a Resource Now</a>
          </div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Booking ID</th>
                <th>Resource Name</th>
                <th>Address / Pickup</th>
                <th>Category</th>
                <th>Date & Slot</th>
                <th>Amount</th>
                <th>Booking Status</th>
                <th>Payment Status</th>
                <th style="text-align: center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $id => $bk): ?>
                <tr>
                  <td style="font-weight: 700; color: var(--teal-dark);"><?php echo htmlspecialchars($id); ?></td>
                  <td style="font-weight: 600;"><?php echo htmlspecialchars($bk['resource_name']); ?></td>
                  <td style="max-width:240px; font-size:0.85rem; color:var(--muted);"><?php echo htmlspecialchars($bk['pickup_address'] ?? '—'); ?></td>
                  <td><?php echo htmlspecialchars($bk['category'] ?? ''); ?></td>
                  <td>
                    <?php echo htmlspecialchars($bk['date']); ?><br>
                    <span style="font-size: 0.75rem; color: var(--muted);"><?php echo htmlspecialchars($bk['slot']); ?></span>
                  </td>
                  <td style="font-weight: 600; color: var(--teal);"><?php echo format_currency($bk['amount']); ?></td>
                  <td>
                    <span class="badge <?php echo strtolower($bk['booking_status'] ?? ''); ?>">
                      <?php echo htmlspecialchars($bk['booking_status'] ?? ''); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?php echo strtolower($bk['payment_status'] ?? ''); ?>">
                      <?php echo htmlspecialchars($bk['payment_status'] ?? ''); ?>
                    </span>
                  </td>
                  <td>
                    <div style="display: flex; flex-direction: column; gap: 8px; align-items: center; justify-content: center;">
                      <?php if ($bk['payment_status'] === 'Unpaid' && $bk['booking_status'] === 'Approved'): ?>
                        <a href="payments.php?pay_booking_id=<?php echo urlencode($id); ?>" class="btn-main" style="padding: 6px 12px; font-size: 0.8rem; width: auto; display: inline-flex;">
                          Pay Now
                        </a>
                      <?php elseif ($bk['payment_status'] === 'Paid' || $bk['payment_status'] === 'Refund Requested' || $bk['payment_status'] === 'Refunded'): ?>
                        <a href="payments.php?receipt_booking_id=<?php echo urlencode($id); ?>" class="btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; display: inline-flex; font-family: 'Space Grotesk', sans-serif;">
                          View Receipt
                        </a>
                      <?php endif; ?>

                      <?php 
                      $show_cancel = false;
                      if ($bk['booking_status'] !== 'Cancelled' && $bk['booking_status'] !== 'Completed' && $bk['booking_status'] !== 'Declined' && $bk['booking_status'] !== 'Rejected') {
                          if ($bk['booking_status'] === 'Pending' || $bk['booking_status'] === 'Pending Review' || $bk['payment_status'] === 'Paid') {
                              $show_cancel = true;
                          }
                      }
                      ?>
                      <?php if ($show_cancel): ?>
                        <form action="my_bookings.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');" style="display: inline;">
                          <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($id); ?>">
                          <input type="hidden" name="action" value="cancel">
                          <button type="submit" class="btn-danger" style="padding: 6px 12px; font-size: 0.8rem; display: inline-flex;">
                            Cancel
                          </button>
                        </form>
                      <?php endif; ?>

                      <?php if ($bk['booking_status'] === 'Cancelled' && $bk['payment_status'] === 'Paid'): ?>
                        <form action="my_bookings.php" method="POST" style="display: inline;">
                          <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($id); ?>">
                          <input type="hidden" name="action" value="request_refund">
                          <button type="submit" class="btn-main" style="padding: 6px 12px; font-size: 0.8rem; display: inline-flex; background:#eab308; color:#fff; border-color:#eab308;">
                            Request Refund
                          </button>
                        </form>
                      <?php elseif ($bk['payment_status'] === 'Refund Requested'): ?>
                        <span class="badge pending" style="background:#eab308; color:#fff; display: inline-flex; padding: 6px 12px; align-items: center; justify-content: center; height: auto;">Requested</span>
                      <?php elseif ($bk['payment_status'] === 'Refunded'): ?>
                        <span class="badge cancelled" style="background:#ef4444; color:#fff; display: inline-flex; padding: 6px 12px; align-items: center; justify-content: center; height: auto;">Refunded</span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

    </main>
  </div>

  <script>
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-closed');
    }
  </script>

</body>
</html>