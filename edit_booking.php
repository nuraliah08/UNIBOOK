<?php
// user/edit_booking.php
require_once '../config.php';
check_auth('user');

$user = get_user_by_email($_SESSION['email']);
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);
$user_email = $_SESSION['email'];
$user_name = $user ? $user['name'] : $_SESSION['name'];
$user_initials = strtoupper(substr($user_name, 0, 1));

$error = '';
$booking_id = $_GET['booking_id'] ?? '';
$booking = null;

if (!empty($booking_id)) {
    $booking = get_booking_by_id($booking_id);
    if (!$booking || !is_booking_owned_by_user($booking, $user_id, $user_email)) {
        $error = 'Booking not found or you do not have permission to edit it.';
    } elseif ($booking['booking_status'] !== 'Pending Review' && $booking['booking_status'] !== 'Pending') {
        $error = 'Booking can only be edited when status is "Waiting for admin review".';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $new_date = $_POST['date'] ?? '';
    $new_slot = $_POST['slot'] ?? '';

    if (empty($new_date) || empty($new_slot)) {
        $error = 'Please select both a date and time slot.';
    } else {
        $result = update_booking_details($booking_id, $new_date, $new_slot);
        if ($result === true) {
            set_flash_message('success', 'Booking updated successfully! Admin will review your changes.');
            header('Location: ' . url('user/my_bookings.php'));
            exit();
        } elseif ($result === 'double_booked') {
            $error = 'The selected date and time slot is not available. Please choose another time.';
        } else {
            $error = 'Failed to update booking. Please try again.';
        }
    }
    // Reload booking data if update failed
    if (!empty($error)) {
        $booking = get_booking_by_id($booking_id);
    }
}

// Get available time slots
$hourly_slots = [
    "8:00 AM – 10:00 AM",
    "10:00 AM – 12:00 PM",
    "2:00 PM – 4:00 PM",
    "4:00 PM – 6:00 PM"
];

$multi_day_slots = [
    "1 Day",
    "2 Days",
    "3 Days",
    "4 Days",
    "5 Days"
];

$is_hourly = in_array($booking['slot'] ?? '', $hourly_slots);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book – Edit Booking</title>
  <link rel="stylesheet" href="../assets/css/style.css">
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
        <h1 class="page-title">Edit Booking</h1>
        <p class="page-desc">Modify your booking details. Changes will be reviewed by the admin.</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert err" style="display:block; margin-bottom: 20px;">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php elseif ($booking): ?>
        <div class="dashboard-panel" style="max-width: 600px;">
          <h2 class="panel-title">Current Booking Details</h2>
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; padding: 16px 0; border-bottom: 1px solid var(--border);">
            <div>
              <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted);">Booking ID</span>
              <div style="font-size: 1rem; font-weight: 600; color: var(--teal-dark);"><?php echo htmlspecialchars($booking['booking_id']); ?></div>
            </div>
            <div>
              <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted);">Resource</span>
              <div style="font-size: 1rem; font-weight: 600;"><?php echo htmlspecialchars($booking['resource_name']); ?></div>
            </div>
            <div>
              <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted);">Category</span>
              <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($booking['category']); ?></div>
            </div>
            <div>
              <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted);">Pickup Address</span>
              <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($booking['pickup_address'] ?? '—'); ?></div>
            </div>
            <div>
              <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted);">Current Status</span>
              <span class="badge <?php echo strtolower($booking['booking_status']); ?>" style="display: inline-block;">
                <?php echo $booking['booking_status'] === 'Pending Review' ? 'Waiting for admin review' : htmlspecialchars($booking['booking_status']); ?>
              </span>
            </div>
          </div>

          <form action="edit_booking.php?booking_id=<?php echo urlencode($booking_id); ?>" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
              <label style="font-weight: 600; font-size: 0.9rem; color: var(--ink);">📅 New Booking Date</label>
              <input type="date" name="date" value="<?php echo htmlspecialchars($booking['date']); ?>" 
                     min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                     required
                     style="padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem;" />
              <span style="font-size: 0.75rem; color: var(--muted);">Select a new date for your booking</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
              <label style="font-weight: 600; font-size: 0.9rem; color: var(--ink);">⏰ Time Slot</label>
              <select name="slot" required style="padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem;">
                <option value="">-- Select a time slot --</option>
                <?php if ($is_hourly): ?>
                  <?php foreach ($hourly_slots as $slot): ?>
                    <option value="<?php echo htmlspecialchars($slot); ?>" <?php echo $booking['slot'] === $slot ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($slot); ?>
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <?php foreach ($multi_day_slots as $slot): ?>
                    <option value="<?php echo htmlspecialchars($slot); ?>" <?php echo $booking['slot'] === $slot ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($slot); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <span style="font-size: 0.75rem; color: var(--muted);">Select a new time slot or duration</span>
            </div>

            <div style="background: var(--surface, #f9fafb); border-left: 4px solid var(--teal); padding: 12px; border-radius: 6px; margin: 16px 0;">
              <div style="font-size: 0.85rem; color: var(--ink); margin-bottom: 8px;">
                <strong>ℹ️ Note:</strong> Once you update your booking, the admin will review the changes. Your payment will need to be made once the booking is approved.
              </div>
            </div>

            <div style="display: flex; gap: 12px;">
              <button type="submit" class="btn-main" style="flex: 1; padding: 12px;">
                💾 Save Changes
              </button>
              <a href="<?php echo url('user/my_bookings.php'); ?>" class="btn-secondary" style="flex: 1; padding: 12px; text-align: center; text-decoration: none;">
                ← Cancel
              </a>
            </div>
          </form>
        </div>
      <?php endif; ?>

    </main>
  </div>

  <script>
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-closed');
    }
  </script>


</body>
</html>


