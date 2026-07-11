<?php
// user/add_booking.php
require_once '../config.php';
check_auth('user');

$user = get_user_by_email($_SESSION['email']);
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);
$user_name = $user ? $user['name'] : $_SESSION['name'];
$user_email = $_SESSION['email'];
$user_phone = $user ? $user['phone'] : ($_SESSION['phone'] ?? '');
$user_initials = strtoupper(substr($user_name, 0, 1));

$error = '';
$success = '';

// Handle resource categories filter via query param
$pre_selected_category = $_GET['category'] ?? 'All';

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resource_id = $_POST['resource_id'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';
    $booking_slot = $_POST['booking_slot'] ?? '';
    $booking_purpose = trim($_POST['booking_purpose'] ?? '');
    $action = $_POST['action'] ?? 'save'; // 'save' or 'pay'
    $terms = isset($_POST['terms_agree']) ? true : false;

    if (empty($booking_date) || empty($booking_slot)) {
        $error = 'Please select both a date and a time slot.';
    } elseif (!$terms) {
        $error = 'You must agree to the Terms and Conditions to proceed.';
    } elseif ($booking_date < date('Y-m-d')) {
        $error = 'You cannot book a resource for a past date.';
    } else {
        $booking_id = create_booking($user_id, $user_name, $resource_id, $booking_date, $booking_slot, $booking_purpose);
        if ($booking_id === false) {
            $error = 'Selected resource does not exist.';
        } elseif ($booking_id === 'double_booked') {
            $error = 'Sorry, this time slot is already booked for this resource. Please select a different date or slot.';
        } else {
          // Always set initial booking status to Pending Review and disallow immediate payment
          update_booking_status($booking_id, 'Pending Review');

          // Also persist the booking to a shared JSON store so admin sessions can see it
          $shared_file = __DIR__ . '/../data/bookings.json';
          $new_booking = get_booking_by_id($booking_id);
          if ($new_booking) {
            $shared = [];
            if (file_exists($shared_file)) {
              $txt = @file_get_contents($shared_file);
              $decoded = @json_decode($txt, true);
              if (is_array($decoded)) $shared = $decoded;
            }
            $shared[$booking_id] = $new_booking;
            // write with exclusive lock and ensure session data is saved
            @file_put_contents($shared_file, json_encode($shared, JSON_PRETTY_PRINT), LOCK_EX);
            if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
          }

          // Redirect back to My Bookings and trigger a popup there
          header("Location: my_bookings.php?booked=1");
          exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book - Add New Booking</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=2.5">
  <style>
    /* ── Category pills (visual indicator row) ── */
    .cal-cat-pills {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    .cal-cat-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 999px;
      font-size: 0.82rem;
      font-weight: 600;
      border: 1.5px solid transparent;
      cursor: pointer;
      transition: all 0.15s;
      background: #f1f5f9;
      color: #475569;
      text-decoration: none;
    }
    .cal-cat-pill svg { flex-shrink: 0; }
    .cal-cat-pill:hover { background: #e2e8f0; color: #0f172a; }
    .cal-cat-pill.active {
      background: #f0fdf9;
      color: #0f766e;
      border-color: #6ee7b7;
    }
  </style>
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
        <a href="<?php echo url('user/add_booking.php'); ?>" class="nav-link nav-item active"><?php echo get_icon('add-booking'); ?> Add New Booking</a>
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
        <h1 class="page-title"> ✚ Add New Booking</h1>
        <p class="page-desc">Select a university resource, verify availability, and request a slot booking.</p>
      </div>

      <!-- Error display -->
      <?php if (!empty($error)): ?>
        <div class="alert err" style="display:block;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <!-- CATEGORY FILTER CHIPS -->
      <div class="cal-cat-pills" style="margin-bottom:14px;">
        <a href="javascript:void(0)" onclick="filterCategory('All')" class="cal-cat-pill <?php echo $pre_selected_category === 'All' ? 'active' : ''; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4l3 3"></path></svg>
          All
        </a>
        <a href="javascript:void(0)" onclick="filterCategory('Facilities')" class="cal-cat-pill <?php echo $pre_selected_category === 'Facilities' ? 'active' : ''; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21h18M9 21V7l6-4v18M9 11h6"></path></svg>
          Facilities
        </a>
        <a href="javascript:void(0)" onclick="filterCategory('Vehicles')" class="cal-cat-pill <?php echo $pre_selected_category === 'Vehicles' ? 'active' : ''; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="1" y="9" width="22" height="9" rx="2"></rect><path d="M16 18v2M8 18v2M5 9l2-5h10l2 5"></path><circle cx="7" cy="15" r="1.5"></circle><circle cx="17" cy="15" r="1.5"></circle></svg>
          Vehicles
        </a>
        <a href="javascript:void(0)" onclick="filterCategory('Equipment')" class="cal-cat-pill <?php echo $pre_selected_category === 'Equipment' ? 'active' : ''; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
          Equipment
        </a>
        <a href="javascript:void(0)" onclick="filterCategory('Personnel')" class="cal-cat-pill <?php echo $pre_selected_category === 'Personnel' ? 'active' : ''; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          Personnel
        </a>
      </div>

      <!-- SIMPLE SEARCH -->
      <div style="margin:12px 0;">
        <input type="search" id="resource-search" placeholder="Search resources by name or description..." style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--border);" oninput="applyFilters()">
      </div>

      <!-- RESOURCE CARDS GRID -->
      <div class="resource-grid">
        <?php foreach (get_all_resources() as $res_id => $res): ?>
          <div class="resource-card" data-category="<?php echo htmlspecialchars($res['category']); ?>">
            
            <!-- Styled image container -->
            <?php 
              $image_path = '';
              if (!empty($res['image']) && $res['image'] !== 'facility.jpg' && file_exists(__DIR__ . '/../uploads/' . $res['image'])) {
                  $image_path = '../uploads/' . $res['image'];
              }
            ?>
            <?php if ($image_path !== ''): ?>
              <div class="resource-img-placeholder" style="padding:0; overflow:hidden;">
                <img src="<?php echo htmlspecialchars($image_path); ?>" style="width:100%; height:100%; object-fit:cover;" alt="<?php echo htmlspecialchars($res['name']); ?>">
              </div>
            <?php else: ?>
              <div class="resource-img-placeholder">
                <?php 
                  $icon = '🖥️';
                  if ($res['category'] === 'Facilities') $icon = '🏛️';
                  if ($res['category'] === 'Vehicles') $icon = '🚗';
                  if ($res['category'] === 'Personnel') $icon = '👥';
                ?>
                <i style="font-style: normal; font-size: 2.5rem;"><?php echo $icon; ?></i>
                <span style="font-size: 0.8rem; opacity: 0.8;"><?php echo htmlspecialchars($res['category']); ?></span>
              </div>
            <?php endif; ?>

            <div class="resource-card-body">
              <span class="resource-tag"><?php echo htmlspecialchars($res['category']); ?></span>
              <h3 class="resource-name"><?php echo htmlspecialchars($res['name']); ?></h3>
              <p class="resource-desc"><?php echo htmlspecialchars($res['description']); ?></p>
              
              <div class="resource-footer">
                <div class="resource-price"><?php echo format_currency($res['price']); ?><span> / slot</span></div>
                
                <?php if ($res['status'] === 'Available'): ?>
                  <button type="button" class="btn-secondary" style="padding: 6px 12px; font-size: 0.82rem;" 
                          onclick="openBookingModal('<?php echo $res_id; ?>', '<?php echo htmlspecialchars($res['name'], ENT_QUOTES); ?>', '<?php echo $res['price']; ?>', '<?php echo htmlspecialchars($res['category'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($res['pickup_address'] ?? '', ENT_QUOTES); ?>')">
                    Book Now
                  </button>
                <?php else: ?>
                  <button type="button" class="btn-secondary" disabled style="padding: 6px 12px; font-size: 0.82rem; border-color: #ccc; color: #aaa;">
                    Unavailable
                  </button>
                <?php endif; ?>
              </div>
            </div>

          </div>
        <?php endforeach; ?>
      </div>

    </main>
  </div>

  <!-- BOOKING MODAL -->
  <div class="modal-overlay" id="bookingModal">
    <div class="modal-box" style="max-width: 550px;">
      <h2 class="modal-title">Book Resource</h2>
      <p style="color: var(--muted); font-size: 0.82rem; margin-bottom: 20px;">Please select date, time slot, and agree to our booking terms.</p>

      <form action="add_booking.php" method="POST" id="bookingForm">
        <!-- Selected resource dynamic info -->
        <input type="hidden" name="resource_id" id="form-resource-id">
        
        <div class="field">
          <label class="lbl">Resource Name</label>
          <input type="text" id="form-resource-name" disabled>
        </div>

        <div class="field">
          <label class="lbl">Pickup Address</label>
          <input type="text" id="form-resource-pickup" disabled>
        </div>

        <div class="two-col">
          <div class="field">
            <label class="lbl">Booking Date *</label>
            <input type="date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <div class="field">
            <label class="lbl">Time Slot *</label>
            <select name="booking_slot" required>
              <option value="">-- Choose Slot --</option>
              <option value="8:00 AM – 10:00 AM">8:00 AM – 10:00 AM</option>
              <option value="10:00 AM – 12:00 PM">10:00 AM – 12:00 PM</option>
              <option value="2:00 PM – 4:00 PM">2:00 PM – 4:00 PM</option>
              <option value="4:00 PM – 6:00 PM">4:00 PM – 6:00 PM</option>
            </select>
          </div>
        </div>

        <div class="field">
          <label class="lbl">Purpose of use:</label>
          <textarea name="booking_purpose" rows="3" placeholder="Describe what you will use the resource/book an appointment for..." style="resize: vertical;"></textarea>
        </div>

        <!-- Auto-filled user information -->
        <div style="background: var(--mist); border: 1.5px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 16px;">
          <div style="font-size: 0.8rem; font-weight: 700; color: var(--teal-dark); margin-bottom: 8px;">Auto-Filled Account Details</div>
          
          <div class="two-col">
            <div class="field" style="margin-bottom:8px;">
              <label class="lbl" style="font-size:0.75rem;">Full Name</label>
              <input type="text" value="<?php echo htmlspecialchars($user_name); ?>" disabled style="padding: 7px 10px; font-size: 0.85rem;">
            </div>
            <div class="field" style="margin-bottom:8px;">
              <label class="lbl" style="font-size:0.75rem;">Phone Number</label>
              <input type="text" value="<?php echo htmlspecialchars($user_phone); ?>" disabled style="padding: 7px 10px; font-size: 0.85rem;">
            </div>
          </div>
          <div class="field" style="margin-bottom:0;">
            <label class="lbl" style="font-size:0.75rem;">Email Address</label>
            <input type="text" value="<?php echo htmlspecialchars($user_email); ?>" disabled style="padding: 7px 10px; font-size: 0.85rem;">
          </div>
          <p style="font-size: 0.7rem; color: var(--muted); margin-top: 8px; font-style: italic;">
            Note: User details are automatically retrieved from your registered account.
          </p>
        </div>

        <!-- Rules & Agreements -->
        <div class="field" style="margin-bottom: 20px;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
            <button type="button" class="btn-secondary" style="padding: 4px 10px; font-size: 0.78rem; border-color: var(--teal-mid);" onclick="openRulesModal()">
              📖 View Booking Rules
            </button>
          </div>
          
          <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; font-size: 0.82rem; user-select: none;">
            <input type="checkbox" name="terms_agree" id="terms-agree" style="margin-top: 3px;" required>
            <span>I Agree to the Terms & Conditions and booking policies.</span>
          </label>
        </div>

        <!-- Actions -->
        <div class="modal-footer" style="padding-top:16px;">
          <button type="button" class="btn-secondary" onclick="closeBookingModal()">Cancel</button>
          <button type="submit" name="action" value="save" class="btn-secondary" style="background:var(--white); border-color:var(--teal); color:var(--teal);">Save Booking</button>
        </div>
      </form>
    </div>
  </div>

  <!-- BOOKING RULES MODAL -->
  <div class="modal-overlay" id="rulesModal">
    <div class="modal-box" style="max-width: 440px;">
      <h2 class="modal-title">Booking Rules & Conditions</h2>
      <div class="modal-body">
        <ul>
          <li><strong>Payment Required:</strong> Bookings must be paid within 24 hours of booking creation.</li>
          <li><strong>No Refunds:</strong> Cancellations or refunds are not permitted once a booking request has been approved by the Administrator.</li>
          <li><strong>Damages & Liabilities:</strong> The reserving user is fully responsible for any damages caused to equipment, vehicles, or physical venues.</li>
          <li><strong>Regulations:</strong> Users must comply with standard university regulations and code of conduct during the booked slot.</li>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-main" style="width: auto; padding: 8px 16px;" onclick="closeRulesModal()">I Understand</button>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-closed');
    }

    // Client side category filtering
    function filterCategory(cat) {
      // Active chip styling
      document.querySelectorAll('.cal-cat-pill').forEach(chip => {
        chip.classList.remove('active');
        if (chip.textContent.includes(cat) || (cat === 'All' && chip.textContent.includes('All'))) {
          chip.classList.add('active');
        }
      });

      // Filter cards
      document.querySelectorAll('.resource-card').forEach(card => {
        const cardCat = card.getAttribute('data-category');
        if (cat === 'All' || cardCat === cat) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }

    // Modal Operations
    function openBookingModal(id, name, price, category, pickup) {
      document.getElementById('form-resource-id').value = id;
      document.getElementById('form-resource-name').value = name;
      document.getElementById('form-resource-pickup').value = pickup || '—';
      document.getElementById('terms-agree').checked = false;

      const slotSelect = document.querySelector('select[name="booking_slot"]');
      if (slotSelect) {
        slotSelect.innerHTML = '<option value="">-- Choose Slot --</option>';
        if (category === 'Vehicles' || category === 'Equipment') {
          slotSelect.innerHTML += `
            <option value="Half Day">Half Day</option>
            <option value="All Day">All Day</option>
            <option value="1 Day">1 Day</option>
            <option value="2 Days">2 Days</option>
            <option value="3 Days">3 Days</option>
            <option value="4 Days">4 Days</option>
            <option value="5 Days">5 Days</option>
          `;
        } else {
          slotSelect.innerHTML += `
            <option value="8:00 AM – 10:00 AM">8:00 AM – 10:00 AM</option>
            <option value="10:00 AM – 12:00 PM">10:00 AM – 12:00 PM</option>
            <option value="2:00 PM – 4:00 PM">2:00 PM – 4:00 PM</option>
            <option value="4:00 PM – 6:00 PM">4:00 PM – 6:00 PM</option>
          `;
        }
      }

      document.getElementById('bookingModal').classList.add('open');
    }

    function closeBookingModal() {
      document.getElementById('bookingModal').classList.remove('open');
    }

    function openRulesModal() {
      document.getElementById('rulesModal').classList.add('open');
    }

    function closeRulesModal() {
      document.getElementById('rulesModal').classList.remove('open');
    }

    // Run filter automatically if pre-selected via GET
    window.addEventListener('DOMContentLoaded', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const cat = urlParams.get('category');
      if (cat) {
        filterCategory(cat);
      }
    });

    // Apply combined category + search filtering
    function applyFilters() {
      const search = (document.getElementById('resource-search')?.value || '').trim().toLowerCase();
      document.querySelectorAll('.resource-card').forEach(card => {
        const cardCat = card.getAttribute('data-category');
        const name = (card.querySelector('.resource-name')?.textContent || '').toLowerCase();
        const desc = (card.querySelector('.resource-desc')?.textContent || '').toLowerCase();

        // category visibility check (respect currently active chip)
        const activeChip = document.querySelector('.cal-cat-pill.active');
        const activeCat = activeChip ? activeChip.textContent.trim() : 'All Categories';
        const catMatch = (activeCat.includes('All') || cardCat === activeCat.replace(/[^A-Za-z]/g, ''));

        const searchMatch = search === '' || name.includes(search) || desc.includes(search);

        card.style.display = (catMatch && searchMatch) ? 'flex' : 'none';
      });
    }
  </script>


</body>
</html>


