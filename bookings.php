<?php
// admin/bookings.php
require_once '../config.php';
check_auth('manager');

$error = '';
$success = '';

// Handle Status Modifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $booking_id = $_POST['booking_id'] ?? '';
  $action = $_POST['action'];

  $booking = get_booking_by_id($booking_id);
  // If booking not found in current session, try loading from shared JSON store
  if (!$booking) {
    $shared_file_try = __DIR__ . '/../data/bookings.json';
    if (file_exists($shared_file_try)) {
      $txt_try = @file_get_contents($shared_file_try);
      $dec_try = @json_decode($txt_try, true);
      if (is_array($dec_try) && isset($dec_try[$booking_id])) {
        $booking = $dec_try[$booking_id];
      }
    }
  }
  if ($booking) {
    // Ensure session is active
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    // Ensure the booking exists in session; if not, seed it from helper
    if (!isset($_SESSION['bookings'][$booking_id])) {
      $_SESSION['bookings'][$booking_id] = $booking;
    }

    $new_status = '';
    if ($action === 'approve') {
      $new_status = 'Approved';
      $success = "Booking $booking_id has been Approved.";
    } elseif ($action === 'decline') {
      $new_status = 'Declined';
      $success = "Booking $booking_id has been Declined.";
    } elseif ($action === 'complete') {
      $new_status = 'Completed';
      $success = "Booking $booking_id has been marked as Completed.";
    } elseif ($action === 'approve_refund') {
      if (approve_refund($booking_id)) {
        $success = "Refund for Booking $booking_id has been Approved.";
      } else {
        $error = "Unable to approve refund for Booking $booking_id.";
      }
    }

    if ($new_status !== '') {
      update_booking_status($booking_id, $new_status);
    }
  } else {
    $error = 'Booking reference not found.';
  }
}

// Now iterate all aggregated bookings
// Ensure filter and data variables are initialized (avoid undefined warnings)
$filter_booking = $_GET['filter_booking'] ?? 'All';
$filter_payment = $_GET['filter_payment'] ?? 'All';
$filter_resource = $_GET['filter_resource'] ?? 'All';
$filter_date = $_GET['filter_date'] ?? '';

// Load resources and bookings from helpers (fall back to empty arrays)
$manager_scope = $_SESSION['manager_scope'] ?? get_manager_scope_by_email($_SESSION['email'] ?? '');
$manager_labels = get_manager_display_labels($manager_scope);
$current_manager_id = $_SESSION['user_id'] ?? '';
$all_resources_raw = get_all_resources() ?: [];
$scoped_resources = [];
foreach ($all_resources_raw as $res_key => $res) {
    if (($res['uploaded_by'] ?? '') === $current_manager_id) {
        $scoped_resources[$res_key] = $res;
    }
}
$resources = array_values($scoped_resources);
$all_bookings = get_all_bookings() ?: [];

// Prepare output array
$filtered_bookings = [];

foreach ($all_bookings as $id => $bk) {
  $res_id = $bk['resource_id'];
  if (!isset($scoped_resources[$res_id])) {
    continue;
  }

  // Apply booking status filter (normalize and treat similar statuses as equivalent)
  if ($filter_booking !== 'All') {
    $bk_status_norm = strtolower(trim($bk['booking_status'] ?? ''));
    $filter_norm = strtolower(trim($filter_booking));

    $matches = false;
    if ($filter_norm === 'all') {
      $matches = true;
    } elseif ($filter_norm === 'pending review' || $filter_norm === 'pending') {
      // treat any pending-like status as pending review
      if ($bk_status_norm === '' || strpos($bk_status_norm, 'pend') !== false) $matches = true;
    } elseif ($filter_norm === 'approved') {
      if ($bk_status_norm === 'approved' || $bk_status_norm === 'confirm' || $bk_status_norm === 'confirmed') $matches = true;
    } elseif ($filter_norm === 'declined' || $filter_norm === 'rejected') {
      if ($bk_status_norm === 'declined' || $bk_status_norm === 'rejected') $matches = true;
    } elseif ($filter_norm === 'cancelled') {
      if ($bk_status_norm === 'cancelled') $matches = true;
    } elseif ($filter_norm === 'completed') {
      if ($bk_status_norm === 'completed') $matches = true;
    } else {
      // direct compare as fallback
      if ($bk_status_norm === $filter_norm) $matches = true;
    }

    if (!$matches) continue;
  }
    // Apply payment status filter
    if ($filter_payment !== 'All' && $bk['payment_status'] !== $filter_payment) {
        continue;
    }
    if ($filter_resource !== 'All' && $bk['resource_id'] !== $filter_resource) {
        continue;
    }
    if ($filter_date !== '' && $bk['date'] !== $filter_date) {
        continue;
    }
    
    // Retrieve phone from user helper
    $user_email_lower = strtolower($bk['user_email']);
    $user_details = get_user_by_email($user_email_lower);
    $phone = $user_details ? $user_details['phone'] : 'â€”';
    
    $bk['user_phone'] = $phone;
    $filtered_bookings[$id] = $bk;
}

// Sort filtered bookings descending (newest first)
krsort($filtered_bookings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book - Manager Bookings</title>
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
        <a href="dashboard.php" class="nav-link nav-item"><?php echo get_icon('dashboard'); ?> Dashboard</a>
        <a href="resources.php" class="nav-link nav-item"><?php echo get_icon('resources'); ?> Manage Resources</a>
        <a href="bookings.php" class="nav-link nav-item active"><?php echo get_icon('my-bookings'); ?> View Bookings</a>
        <a href="../logout.php" class="nav-link nav-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;"><?php echo get_icon('logout'); ?> Logout</a>
      </nav>
      <div class="sidebar-user">
        <div class="user-avatar">MG</div>
        <div class="user-meta">
          <span class="user-name"><?php echo htmlspecialchars($manager_labels['department']); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($manager_labels['role']); ?></span>
        </div>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
      
      <!-- PAGE HEADER -->
      <div class="page-header">
        <h1 class="page-title"> ⫶☰ Manage Bookings</h1>
        <p class="page-desc">Review and approve booking reservations made across the campus.</p>
      </div>

      <!-- Alerts -->
      <?php if (!empty($error)): ?>
        <div class="alert err" style="display:block;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert ok" style="display:block;"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      <?php echo display_flash_message(); ?>

      <!-- FILTER CONTROLS BAR -->
      <div class="dashboard-panel" style="padding: 16px 24px; margin-bottom: 24px;">
        <form action="bookings.php" method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
          <div style="display:flex; flex-direction:column; gap:4px;">
            <span style="font-size:0.75rem; font-weight:700; color:var(--teal-dark);">Booking Status</span>
              <select name="filter_booking" onchange="this.form.submit()" style="padding: 6px 12px; font-size:0.85rem; width:160px; background:var(--white);">
              <option value="All" <?php echo $filter_booking === 'All' ? 'selected' : ''; ?>>All Statuses</option>
              <option value="Pending Review" <?php echo $filter_booking === 'Pending Review' ? 'selected' : ''; ?>>Pending Review</option>
              <option value="Approved" <?php echo $filter_booking === 'Approved' ? 'selected' : ''; ?>>Approved</option>
              <option value="Declined" <?php echo $filter_booking === 'Declined' ? 'selected' : ''; ?>>Declined</option>
              <option value="Cancelled" <?php echo $filter_booking === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
              <option value="Completed" <?php echo $filter_booking === 'Completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
          </div>
          <div style="display:flex; flex-direction:column; gap:4px;">
            <span style="font-size:0.75rem; font-weight:700; color:var(--teal-dark);">Payment Status</span>
            <select name="filter_payment" onchange="this.form.submit()" style="padding: 6px 12px; font-size:0.85rem; width:180px; background:var(--white);">
              <option value="All" <?php echo $filter_payment === 'All' ? 'selected' : ''; ?>>All Payments</option>
              <option value="Unpaid" <?php echo $filter_payment === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
              <option value="Paid" <?php echo $filter_payment === 'Paid' ? 'selected' : ''; ?>>Paid</option>
            </select>
          </div>
       

          <div style="display:flex; flex-direction:column; gap:4px;">
            <span style="font-size:0.75rem; font-weight:700; color:var(--teal-dark);">Resource</span>
            <select name="filter_resource" onchange="this.form.submit()" style="padding: 6px 12px; font-size:0.85rem; width:160px; background:var(--white);">
              <?php if (!$manager_scope): ?>
                <option value="All" <?php echo $filter_resource === 'All' ? 'selected' : ''; ?>>All Resources</option>
              <?php endif; ?>
              <?php foreach ($resources as $res_index => $res_info): ?>
                <?php $res_id = $res_info['id'] ?? $res_index; ?>
                <option value="<?php echo htmlspecialchars($res_id); ?>" <?php echo $filter_resource === $res_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($res_info['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="display:flex; flex-direction:column; gap:4px;">
            <span style="font-size:0.75rem; font-weight:700; color:var(--teal-dark);">Booking Date</span>
            <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>" onchange="this.form.submit()" style="padding: 6px 12px; font-size:0.85rem; width:160px; background:var(--white); border:1px solid #d1d5db; border-radius:6px;" />
          </div>

          <div style="margin-left:auto; align-self:flex-end; padding-bottom: 2px;">
            <a href="bookings.php" style="font-size:0.82rem; font-weight:600; color:var(--muted);">Clear Filters</a>
          </div>
        </form>
      </div>

      <!-- MASTER BOOKINGS TABLE -->
      <?php if (empty($filtered_bookings)): ?>
        <div class="table-container" style="padding: 40px;">
          <div class="empty-state">
            <div class="empty-state-icon">✔️</div>
            <h3 class="empty-state-title">No Bookings Match Filters</h3>
            <p class="empty-state-desc">Try resetting your filters to view other booking entries.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Booking ID</th>
                <th>User / Account</th>
                <th>Resource Name</th>
                <th>Address / Pickup</th>
                <th>Category</th>
                <th>Schedule</th>
                <th>Amount</th>
                <th>Booking Status</th>
                <th>Payment Status</th>
                <th style="text-align:center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filtered_bookings as $booking_id => $bk): ?>
                <tr>
                  <td data-label="Booking ID" style="font-weight: 700; color: var(--teal-dark);"><?php echo $booking_id; ?></td>
                  <td data-label="User / Account">
                    <div style="display:flex; flex-direction:column; gap:4px;">
                      <span style="font-weight:600;"><?php echo htmlspecialchars($bk['user_name']); ?></span>
                      <span style="font-size:0.75rem; color:var(--muted);"><?php echo htmlspecialchars($bk['user_email']); ?></span>
                    </div>
                  </td>
                  <td data-label="Resource Name" style="font-weight: 600;"><?php echo htmlspecialchars($bk['resource_name']); ?></td>
                  <td data-label="Pickup Address" style="max-width:240px; font-size:0.85rem; color:var(--muted);"><?php echo htmlspecialchars($bk['pickup_address'] ?? '—'); ?></td>
                  <td data-label="Category"><?php echo htmlspecialchars($bk['category']); ?></td>
                  <td data-label="Schedule"><?php echo htmlspecialchars(date('M d, Y', strtotime($bk['date']))); ?><br><span style="font-size:0.72rem; color:var(--muted);"><?php echo htmlspecialchars($bk['slot']); ?></span></td>
                  <td data-label="Amount" style="font-weight: 600; color: var(--teal);"><?php echo format_currency($bk['amount']); ?></td>
                  <td data-label="Booking Status">
                    <span class="badge <?php echo strtolower($bk['booking_status']); ?>">
                      <?php echo htmlspecialchars($bk['booking_status']); ?>
                    </span>
                  </td>
                  <td data-label="Payment Status">
                    <span class="badge <?php echo strtolower($bk['payment_status']); ?>">
                      <?php echo htmlspecialchars($bk['payment_status']); ?>
                    </span>
                  </td>
                  <td>
<div style="display:flex; flex-direction:column; gap:6px; align-items:center;">                      <!-- Details -->
                      <button class="btn-secondary" style="padding:4px 8px; font-size:0.72rem;"
                              onclick="openDetailsModal(
                                '<?php echo $booking_id; ?>',
                                '<?php echo htmlspecialchars($bk['user_name'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($bk['user_email'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($bk['user_phone'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($bk['resource_name'], ENT_QUOTES); ?>',
                                '<?php echo $bk['category']; ?>',
                                '<?php echo htmlspecialchars($bk['pickup_address'] ?? ''); ?>',
                                '<?php echo $bk['date']; ?>',
                                '<?php echo htmlspecialchars($bk['slot'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($bk['booking_purpose'] ?? ''); ?>',
                                '<?php echo $bk['amount']; ?>',
                                '<?php echo $bk['booking_status']; ?>',
                                '<?php echo $bk['payment_status']; ?>'
                              )">
                        Details
                      </button>

                      <!-- Quick Actions for bookings that have not yet been reviewed -->
                      <?php 
                        $status_norm = strtolower(trim($bk['booking_status'] ?? ''));
                        $is_unreviewed = ($status_norm === '') || (strpos($status_norm, 'pend') !== false) || ($status_norm === 'new');
                      ?>
                       <?php if ($is_unreviewed): ?>
                        <form action="bookings.php" method="POST" style="margin: 0; display: flex; flex-direction: column;">
                          <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                          <input type="hidden" name="action" value="approve">
                          <button type="submit" style="padding: 6px 12px; font-size: 0.8rem; width: 100%; display: inline-flex; justify-content: center; background:#16a34a; color:#fff; border-radius:6px; border:1px solid rgba(0,0,0,0.06);" title="Approve Booking">Approve</button>
                        </form>
                        <form action="bookings.php" method="POST" style="margin: 0; display: flex; flex-direction: column;">
                          <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                          <input type="hidden" name="action" value="decline">
                          <button type="submit" style="padding: 6px 12px; font-size: 0.8rem; width: 100%; display: inline-flex; justify-content: center; background:#ef4444; color:#fff; border-radius:6px; border:1px solid rgba(0,0,0,0.06);" title="Decline Booking">Decline</button>
                        </form>
                      <?php elseif ($status_norm === 'approved'): ?>
                        <form action="bookings.php" method="POST" style="margin: 0; display: flex; flex-direction: column;">
                          <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                          <input type="hidden" name="action" value="complete">
                          <button type="submit" class="btn-main" style="padding: 6px 12px; font-size: 0.8rem; width: 100%; display: inline-flex; justify-content: center; background:#3b82f6;" title="Mark Completed">Done</button>
                        </form>
                      <?php elseif ($bk['payment_status'] === 'Refund Requested' || ($status_norm === 'cancelled' && $bk['payment_status'] === 'Paid')): ?>
                        <form action="bookings.php" method="POST" style="margin: 0; display: flex; flex-direction: column;">
                          <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                          <input type="hidden" name="action" value="approve_refund">
                          <button type="submit" class="btn-main" style="padding: 6px 12px; font-size: 0.8rem; width: 100%; display: inline-flex; justify-content: center; background:#f59e0b;" title="Process Refund">Refund</button>
                        </form>
                      <?php elseif ($status_norm === 'pending cash verification'): ?>
                        <!-- Cash verification removed -->
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </main>
  </div>

  <!-- BOOKING DETAILS MODAL -->
  <div class="modal-overlay" id="detailsModal">
    <div class="modal-box" style="max-width: 500px;">
      <h2 class="modal-title">Booking Specifications</h2>
      <p style="color:var(--muted); font-size:0.8rem; margin-bottom:20px;">Detailed inspection parameters of the reservation request.</p>
      
      <div style="background:var(--mist); border:1.5px solid var(--border); border-radius:8px; padding:16px; display:flex; flex-direction:column; gap:10px;">
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Booking ID:</span>
          <strong id="det-id" style="color:var(--teal-dark);">UB1000</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>User Name:</span>
          <strong id="det-user">John Doe</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Email Address:</span>
          <strong id="det-email">john@example.com</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Phone Number:</span>
          <strong id="det-phone">+1234567890</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Resource Name:</span>
          <strong id="det-res">Seminar Room</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Resource Category:</span>
          <strong id="det-cat">Facilities</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Pickup Address:</span>
          <strong id="det-pickup">Main Courtyard Entrance</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Scheduled Date:</span>
          <strong id="det-date">2026-06-18</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Time Slot:</span>
          <strong id="det-slot">10:00 AM - 12:00 PM</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Purpose of Use:</span>
          <strong id="det-purpose">Event planning / rehearsal</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Transaction Cost:</span>
          <strong id="det-amount">$100.00</strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border); padding-bottom:8px;">
          <span>Booking Status:</span>
          <span id="det-bstatus" class="badge">Pending</span>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; padding-bottom:2px;">
          <span>Payment Status:</span>
          <span id="det-pstatus" class="badge">Unpaid</span>
        </div>
      </div>

      <div class="modal-footer" style="margin-top:24px; padding-top:16px; display:flex; justify-content:space-between; align-items:center; gap: 10px; flex-wrap: wrap;">
        <form id="modal-refund-form" action="bookings.php" method="POST" style="display:none; margin:0;">
          <input type="hidden" name="booking_id" id="modal-refund-booking-id" value="">
          <input type="hidden" name="action" value="approve_refund">
          <button type="submit" class="btn-main" style="width:auto; padding:8px 20px; background:#eab308; color:#fff; border-color:#eab308; cursor:pointer;">Approve Refund</button>
        </form>
        <button type="button" class="btn-secondary" style="width:auto; padding:8px 20px;" onclick="printBookingInvoice()">Print Invoice</button>
        <button type="button" class="btn-main" style="width:auto; padding:8px 20px;" onclick="closeDetailsModal()">Close Details</button>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-closed');
    }

    // Modal Operations
    function openDetailsModal(id, user, email, phone, res, cat, pickup, date, slot, purpose, amount, bStatus, pStatus) {
      document.getElementById('det-id').textContent = id;
      document.getElementById('det-user').textContent = user;
      document.getElementById('det-email').textContent = email;
      document.getElementById('det-phone').textContent = phone;
      document.getElementById('det-res').textContent = res;
      document.getElementById('det-cat').textContent = cat;
      document.getElementById('det-pickup').textContent = pickup || '—';
      document.getElementById('det-date').textContent = date;
      document.getElementById('det-slot').textContent = slot;
      document.getElementById('det-purpose').textContent = purpose || '—';
      document.getElementById('det-amount').textContent = "$" + parseFloat(amount).toFixed(2);
      
      const bStatusEl = document.getElementById('det-bstatus');
      bStatusEl.textContent = bStatus;
      bStatusEl.className = 'badge ' + bStatus.toLowerCase();

      const pStatusEl = document.getElementById('det-pstatus');
      pStatusEl.textContent = pStatus;
      pStatusEl.className = 'badge ' + pStatus.toLowerCase();
      
      // Toggle Refund button visibility inside modal
      const isRefundable = (pStatus.toLowerCase() === 'refund requested') || (bStatus.toLowerCase() === 'cancelled' && pStatus.toLowerCase() === 'paid');
      if (isRefundable) {
        document.getElementById('modal-refund-booking-id').value = id;
        document.getElementById('modal-refund-form').style.display = 'block';
      } else {
        document.getElementById('modal-refund-form').style.display = 'none';
      }

      document.getElementById('detailsModal').classList.add('open');
    }

    function closeDetailsModal() {
      document.getElementById('detailsModal').classList.remove('open');
    }

    function printBookingInvoice() {
      const invoice = {
        id: document.getElementById('det-id').textContent.trim(),
        user: document.getElementById('det-user').textContent.trim(),
        email: document.getElementById('det-email').textContent.trim(),
        phone: document.getElementById('det-phone').textContent.trim(),
        resource: document.getElementById('det-res').textContent.trim(),
        category: document.getElementById('det-cat').textContent.trim(),
        pickup: document.getElementById('det-pickup').textContent.trim(),
        date: document.getElementById('det-date').textContent.trim(),
        slot: document.getElementById('det-slot').textContent.trim(),
        purpose: document.getElementById('det-purpose').textContent.trim(),
        amount: document.getElementById('det-amount').textContent.trim(),
        bStatus: document.getElementById('det-bstatus').textContent.trim(),
        pStatus: document.getElementById('det-pstatus').textContent.trim()
      };

      const printWindow = window.open('', '_blank', 'width=800,height=900');
      if (!printWindow) {
        alert('Please allow pop-ups to print the invoice.');
        return;
      }

      printWindow.document.write(`<!DOCTYPE html>
        <html>
          <head>
            <meta charset="UTF-8">
            <title>Booking Invoice</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 0; padding: 24px; color: #111; }
              .invoice { max-width: 700px; margin: 0 auto; border: 1px solid #ddd; padding: 24px; }
              .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f766e; padding-bottom: 12px; margin-bottom: 18px; }
              .brand { font-size: 22px; font-weight: 700; color: #0f766e; }
              .meta { font-size: 13px; color: #555; }
              .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
              .label { color: #666; }
              .value { font-weight: 600; text-align: right; }
              .total { margin-top: 16px; padding-top: 12px; border-top: 2px solid #0f766e; font-size: 16px; font-weight: 700; }
              @page { size: auto; margin: 10mm; }
            </style>
          </head>
          <body>
            <div class="invoice">
              <div class="header">
                <div>
                  <div class="brand">Uni.Book</div>
                  <div class="meta">University Booking System</div>
                </div>
                <div class="meta">Booking Invoice</div>
              </div>
              <div class="row"><span class="label">Booking ID</span><span class="value">${invoice.id}</span></div>
              <div class="row"><span class="label">User Name</span><span class="value">${invoice.user}</span></div>
              <div class="row"><span class="label">Email</span><span class="value">${invoice.email}</span></div>
              <div class="row"><span class="label">Phone</span><span class="value">${invoice.phone}</span></div>
              <div class="row"><span class="label">Resource</span><span class="value">${invoice.resource}</span></div>
              <div class="row"><span class="label">Category</span><span class="value">${invoice.category}</span></div>
              <div class="row"><span class="label">Pickup Address</span><span class="value">${invoice.pickup}</span></div>
              <div class="row"><span class="label">Date</span><span class="value">${invoice.date}</span></div>
              <div class="row"><span class="label">Time Slot</span><span class="value">${invoice.slot}</span></div>
              <div class="row"><span class="label">Purpose of Use</span><span class="value">${invoice.purpose}</span></div>
              <div class="row"><span class="label">Amount</span><span class="value">${invoice.amount}</span></div>
              <div class="row"><span class="label">Booking Status</span><span class="value">${invoice.bStatus}</span></div>
              <div class="row"><span class="label">Payment Status</span><span class="value">${invoice.pStatus}</span></div>
              <div class="total">Total: ${invoice.amount}</div>
            </div>
          </body>
        </html>`);
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => {
        printWindow.print();
        setTimeout(() => printWindow.close(), 500);
      }, 300);
    }
  </script>


</body>
</html>


