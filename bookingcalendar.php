<?php
require_once '../config.php';
check_auth('user');

$user = get_user_by_email($_SESSION['email']);
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);
$user_name = $user ? $user['name'] : $_SESSION['name'];
$user_email = $_SESSION['email'];
$user_initials = strtoupper(substr($user_name, 0, 1));

$month = isset($_GET['m']) ? intval($_GET['m']) : date('n');
$year  = isset($_GET['y']) ? intval($_GET['y']) : date('Y');
if ($month < 1)  $month = 1;
if ($month > 12) $month = 12;

/* ── Category → resource mapping ───────────────────────────────────
   Resources are read from the DB; we group them by their 'category'
   field (facilities / vehicles / equipment / personnel).
   If your DB uses different category names, adjust $categoryMap keys.
   ----------------------------------------------------------------- */
$resources = get_all_resources();   // returns [ id => ['name'=>..,'category'=>..], ... ]

$categoryMap = [
    'facilities'  => ['label' => 'Facilities',  'icon' => 'building'],
    'vehicles'    => ['label' => 'Vehicles',     'icon' => 'car'],
    'equipment'   => ['label' => 'Equipment',    'icon' => 'tool'],
    'personnel'   => ['label' => 'Personnel',    'icon' => 'users'],
];

// Group resources by category
$resourcesByCategory = ['all' => []];
foreach ($categoryMap as $catKey => $_) {
    $resourcesByCategory[$catKey] = [];
}
foreach ($resources as $rid => $res) {
    $cat = strtolower(trim($res['category'] ?? ''));
    if (isset($resourcesByCategory[$cat])) {
        $resourcesByCategory[$cat][$rid] = $res;
    }
    $resourcesByCategory['all'][$rid] = $res;
}

$selected_category = $_GET['category'] ?? 'all';
if (!array_key_exists($selected_category, $categoryMap) && $selected_category !== 'all') {
    $selected_category = 'all';
}

$selected_resource = $_GET['resource'] ?? 'all';

// Determine display names
$selected_category_label = $selected_category === 'all' ? 'All Categories' : ($categoryMap[$selected_category]['label'] ?? 'All Categories');
$selected_resource_name  = 'All Resources';
if ($selected_resource !== 'all') {
    $pool = $selected_category === 'all' ? $resources : ($resourcesByCategory[$selected_category] ?? []);
    if (isset($pool[$selected_resource])) {
        $selected_resource_name = $pool[$selected_resource]['name'];
    } else {
        $selected_resource = 'all';
    }
}

/* ── Filter bookings ──────────────────────────────────────────────── */
$all_bookings = get_all_bookings();
$bookings = [];
foreach ($all_bookings as $booking) {
    if (!empty($booking['date'])) {
        $rid = $booking['resource_id'];
        // Category filter
        if ($selected_category !== 'all') {
            $cat = strtolower(trim($resources[$rid]['category'] ?? ''));
            if ($cat !== $selected_category) continue;
        }
        // Resource filter
        if ($selected_resource !== 'all' && $rid !== $selected_resource) continue;

        // Skip cancelled bookings
        $status = strtolower($booking['booking_status'] ?? $booking['status'] ?? '');
        if ($status === 'cancelled' || $status === 'canceled') continue;
        
        // Month filter
        $b_month = (int)date('n', strtotime($booking['date']));
        $b_year  = (int)date('Y', strtotime($booking['date']));
        if ($b_month !== $month || $b_year !== $year) continue;

        $bookings[$booking['booking_id']] = $booking;
    }
}

$bookings_by_date = [];
foreach ($bookings as $booking) {
    $bookings_by_date[$booking['date']][] = $booking;
}

/* ── Calendar math ────────────────────────────────────────────────── */
$calendar_start = mktime(0, 0, 0, $month, 1, $year);
$days_in_month  = intval(date('t', $calendar_start));
$first_weekday  = intval(date('w', $calendar_start));
$month_name     = date('F', $calendar_start);

$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1)  { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1;  $next_year++; }

$today       = date('Y-m-d');
$booked_count = count($bookings_by_date);
$upcoming     = array_filter($bookings, fn($b) => isset($b['date']) && $b['date'] >= $today);

/* ── Build JS resource map for cascading dropdown ─────────────────── */
$js_resource_map = [];
foreach ($categoryMap as $catKey => $_) {
    $js_resource_map[$catKey] = [];
    foreach ($resourcesByCategory[$catKey] as $rid => $res) {
        $js_resource_map[$catKey][] = ['id' => $rid, 'name' => $res['name']];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book - Booking Calendar</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=2.5">
  <style>
    .cal-page { display: flex; flex-direction: column; gap: 28px; }

    /* ── Page title ── */
    .cal-title-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
    }
    .cal-title-row h1 {
      margin: 0 0 6px;
      font-size: 1.75rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: #111827;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .cal-title-row h1 svg { color: #000000ff; flex-shrink: 0; }
    .cal-title-row p { margin: 0; color: #6b7280; font-size: 0.93rem; line-height: 1.6; max-width: 460px; }

    /* ── Filter bar ── */
    .cal-filterbar {
      background: #fff;
      border: 1px solid rgba(15,23,42,0.08);
      border-radius: 20px;
      padding: 20px 24px;
      box-shadow: 0 4px 16px rgba(15,23,42,0.04);
      display: flex;
      align-items: flex-end;
      gap: 16px;
      flex-wrap: wrap;
    }
    .cal-filter-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
      flex: 1;
      min-width: 180px;
    }
    .cal-filter-group label {
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      color: #6b7280;
    }
    .cal-filter-group select {
      border: 1px solid rgba(15,23,42,0.15);
      border-radius: 12px;
      padding: 10px 14px;
      background: #f8fafc;
      color: #0f172a;
      font-size: 0.92rem;
      cursor: pointer;
      width: 100%;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 36px;
      transition: border-color 0.15s;
    }
    .cal-filter-group select:focus { outline: none; border-color: #0f766e; }
    .cal-filter-group select:disabled { opacity: 0.45; cursor: not-allowed; }
    .cal-filter-divider {
      width: 1px;
      height: 42px;
      background: rgba(15,23,42,0.08);
      flex-shrink: 0;
      align-self: flex-end;
    }
    .cal-filter-actions {
      display: flex;
      gap: 8px;
      align-self: flex-end;
      flex-shrink: 0;
    }
    .cal-filter-actions button {
      border: 1px solid rgba(15,23,42,0.15);
      border-radius: 12px;
      padding: 10px 20px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.15s;
    }
    .btn-apply  { background: #0f766e; color: #fff; border-color: #0f766e; }
    .btn-apply:hover  { background: #0d6560; }
    .btn-reset  { background: #f8fafc; color: #374151; }
    .btn-reset:hover  { background: #e2e8f0; }

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

    /* ── Summary strip ── */
    .cal-summary {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
    }
    .cal-stat {
      background: #fff;
      border: 1px solid rgba(15,23,42,0.07);
      border-radius: 18px;
      padding: 20px 22px;
      box-shadow: 0 4px 16px rgba(15,23,42,0.04);
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .cal-stat-icon {
      width: 42px; height: 42px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .cal-stat-icon.teal  { background: #f0fdf9; color: #0f766e; }
    .cal-stat-icon.blue  { background: #eff6ff; color: #1d4ed8; }
    .cal-stat-icon.amber { background: #fffbeb; color: #b45309; }
    .cal-stat-body strong {
      display: block;
      font-size: 0.78rem;
      font-weight: 700;
      color: #9ca3af;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      margin-bottom: 4px;
    }
    .cal-stat-body span {
      font-size: 1.55rem;
      font-weight: 700;
      color: #111827;
      line-height: 1;
    }
    .cal-stat-body span.text-val {
      font-size: 0.95rem;
      font-weight: 600;
      color: #0f766e;
    }

    /* ── Two-column panel ── */
    .cal-panel {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 24px;
      align-items: start;
    }

    /* ── Calendar card ── */
    .cal-card {
      background: #fff;
      border-radius: 24px;
      border: 1px solid rgba(15,23,42,0.08);
      padding: 28px;
      box-shadow: 0 4px 24px rgba(15,23,42,0.04);
    }
    .cal-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 22px;
    }
    .cal-nav-label { font-size: 1.05rem; font-weight: 700; color: #111827; }
    .cal-nav-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border: 1px solid rgba(15,23,42,0.13);
      background: #f8fafc;
      color: #374151;
      padding: 8px 14px;
      border-radius: 10px;
      text-decoration: none;
      font-size: 0.88rem;
      font-weight: 600;
      transition: background 0.15s;
    }
    .cal-nav-btn:hover { background: #e2e8f0; }

    .cal-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 8px;
    }
    .cal-weekday {
      text-align: center;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #94a3b8;
      padding-bottom: 8px;
    }
    .cal-day {
      min-height: 96px;
      padding: 12px 10px;
      border-radius: 14px;
      background: #f8fafc;
      border: 1px solid transparent;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.18s, border-color 0.18s, background 0.18s;
    }
    .cal-day:hover { transform: translateY(-2px); border-color: rgba(15,23,42,0.14); background: #fff; }
    .cal-day.today   { background: #eff6ff; border-color: #93c5fd; }
    .cal-day.booked  { background: #f0fdf9; border-color: #6ee7b7; }
    .cal-day.selected { border-color: #0f766e !important; box-shadow: 0 0 0 2px rgba(15,118,110,0.15); }
    .cal-day-num { font-size: 0.9rem; font-weight: 700; color: #0f172a; }
    .cal-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      height: 22px;
      border-radius: 999px;
      padding: 0 8px;
      font-size: 0.7rem;
      font-weight: 700;
      color: #065f46;
      background: #d1fae5;
    }
    .cal-avail { font-size: 0.72rem; color: #94a3b8; font-weight: 500; }

    /* ── Detail sidebar ── */
    .cal-detail {
      background: #0f172a;
      border-radius: 24px;
      padding: 26px;
      color: #f8fafc;
      min-height: 500px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .cal-detail-header { display: flex; align-items: center; gap: 10px; }
    .cal-detail-header svg { color: #38bdf8; flex-shrink: 0; }
    .cal-detail-header h2 { margin: 0; font-size: 1rem; font-weight: 700; color: #f8fafc; }
    .cal-detail-hint { margin: 0; font-size: 0.86rem; color: #94a3b8; line-height: 1.65; }
    .cal-detail-box {
      border-radius: 14px;
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.08);
      padding: 16px 18px;
      flex: 1;
    }
    .cal-detail-box h3 { margin: 0 0 10px; font-size: 0.9rem; font-weight: 700; color: #e2e8f0; }
    .cal-detail-box ul { margin: 0; padding: 0; list-style: none; }
    .cal-detail-box ul li {
      font-size: 0.86rem;
      color: #cbd5e1;
      line-height: 1.7;
      padding: 8px 0;
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .cal-detail-box ul li:last-child { border-bottom: none; }
    .cal-detail-box ul li strong { color: #f1f5f9; }
    .cal-booking-status {
      display: inline-block;
      margin-top: 4px;
      font-size: 0.72rem;
      font-weight: 700;
      color: #10b981;
      background: rgba(16,185,129,0.15);
      border-radius: 999px;
      padding: 2px 10px;
    }

    /* ── Responsive ── */
    @media (max-width: 1100px) {
      .cal-panel { grid-template-columns: 1fr; }
      .cal-detail { min-height: 260px; }
    }
    @media (max-width: 760px) {
      .cal-summary { grid-template-columns: 1fr 1fr; }
      .cal-filterbar { flex-direction: column; align-items: stretch; }
      .cal-filter-divider { display: none; }
      .cal-filter-actions { justify-content: flex-end; }
      .cal-title-row { flex-direction: column; }
    }
    @media (max-width: 480px) {
      .cal-summary { grid-template-columns: 1fr; }
      .cal-grid { gap: 4px; }
      .cal-day { min-height: 68px; }
    }
  </style>
</head>
<body class="sidebar-closed">

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
    <aside class="sidebar no-print" id="sidebarMenu">
            <nav class="sidebar-nav">
        <a href="<?php echo url('user/dashboard.php'); ?>" class="nav-link nav-item"><?php echo get_icon('dashboard'); ?> Dashboard</a>
        <a href="<?php echo url('user/add_booking.php'); ?>" class="nav-link nav-item"><?php echo get_icon('add-booking'); ?> Add New Booking</a>
        <a href="<?php echo url('user/bookingcalendar.php'); ?>" class="nav-link nav-item active"><?php echo get_icon('bookings'); ?> Calendar</a>
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

    <main class="dashboard-main">
      <?php echo display_flash_message(); ?>

      <div class="cal-page">

        <!-- Page title -->
        <div class="cal-title-row">
          <div>
            <h1>
              <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              Booking Calendar
            </h1>
            <p>Review reserved dates and view booking details for any resource across the university.</p>
          </div>
        </div>

        <!-- Filter bar -->
        <form method="GET" id="filter-form">
          <input type="hidden" name="m" value="<?php echo intval($month); ?>">
          <input type="hidden" name="y" value="<?php echo intval($year); ?>">

          <!-- Category pills -->
          <div class="cal-cat-pills" style="margin-bottom:14px;">
            <a href="?m=<?php echo $month; ?>&y=<?php echo $year; ?>&category=all&resource=all"
               class="cal-cat-pill<?php echo $selected_category === 'all' ? ' active' : ''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
              All
            </a>
            <a href="?m=<?php echo $month; ?>&y=<?php echo $year; ?>&category=facilities&resource=all"
               class="cal-cat-pill<?php echo $selected_category === 'facilities' ? ' active' : ''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21h18M9 21V7l6-4v18M9 11h6"/></svg>
              Facilities
            </a>
            <a href="?m=<?php echo $month; ?>&y=<?php echo $year; ?>&category=vehicles&resource=all"
               class="cal-cat-pill<?php echo $selected_category === 'vehicles' ? ' active' : ''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="1" y="9" width="22" height="9" rx="2"/><path d="M16 18v2M8 18v2M5 9l2-5h10l2 5"/><circle cx="7" cy="15" r="1.5"/><circle cx="17" cy="15" r="1.5"/></svg>
              Vehicles
            </a>
            <a href="?m=<?php echo $month; ?>&y=<?php echo $year; ?>&category=equipment&resource=all"
               class="cal-cat-pill<?php echo $selected_category === 'equipment' ? ' active' : ''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              Equipment
            </a>
            <a href="?m=<?php echo $month; ?>&y=<?php echo $year; ?>&category=personnel&resource=all"
               class="cal-cat-pill<?php echo $selected_category === 'personnel' ? ' active' : ''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Personnel
            </a>
          </div>

          <!-- Two dropdowns + apply -->
          <div class="cal-filterbar">
            <div class="cal-filter-group">
              <label for="sel-category">Category</label>
              <select id="sel-category" name="category" onchange="cascadeResources()">
                <option value="all"<?php echo $selected_category === 'all' ? ' selected' : ''; ?>>All Categories</option>
                <?php foreach ($categoryMap as $catKey => $catData): ?>
                  <option value="<?php echo $catKey; ?>"<?php echo $selected_category === $catKey ? ' selected' : ''; ?>>
                    <?php echo htmlspecialchars($catData['label']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="cal-filter-divider"></div>

            <div class="cal-filter-group">
              <label for="sel-resource">Resource</label>
              <select id="sel-resource" name="resource">
                <option value="all">All Resources</option>
                <?php
                  // Render all resources; JS will show/hide by category
                  $pool = $selected_category === 'all' ? $resources : ($resourcesByCategory[$selected_category] ?? []);
                  foreach ($pool as $rid => $res):
                ?>
                  <option value="<?php echo htmlspecialchars($rid); ?>"
                          data-cat="<?php echo htmlspecialchars(strtolower(trim($res['category'] ?? ''))); ?>"
                          <?php echo $selected_resource === $rid ? ' selected' : ''; ?>>
                    <?php echo htmlspecialchars($res['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="cal-filter-actions">
              <a href="?m=<?php echo $month; ?>&y=<?php echo $year; ?>&category=all&resource=all" class="cal-filter-actions button btn-reset" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;border:1px solid rgba(15,23,42,0.15);border-radius:12px;padding:10px 16px;font-size:0.9rem;font-weight:600;background:#f8fafc;color:#374151;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                Reset
              </a>
              <button type="submit" class="btn-apply" style="display:inline-flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                Apply
              </button>
            </div>
          </div>
        </form>

        <!-- Summary strip -->
        <div class="cal-summary">
          <div class="cal-stat">
            <div class="cal-stat-icon teal">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <div class="cal-stat-body">
              <strong>Viewing</strong>
              <span class="text-val">
                <?php echo $selected_resource !== 'all' ? htmlspecialchars($selected_resource_name) : htmlspecialchars($selected_category_label); ?>
              </span>
            </div>
          </div>
          <div class="cal-stat">
            <div class="cal-stat-icon blue">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg>
            </div>
            <div class="cal-stat-body">
              <strong>Reserved days</strong>
              <span><?php echo intval($booked_count); ?></span>
            </div>
          </div>
          <div class="cal-stat">
            <div class="cal-stat-icon amber">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            </div>
            <div class="cal-stat-body">
              <strong>Upcoming</strong>
              <span><?php echo intval(count($upcoming)); ?></span>
            </div>
          </div>
        </div>

        <!-- Calendar + detail -->
        <div class="cal-panel">

          <div class="cal-card">
            <div class="cal-nav">
              <a class="cal-nav-btn" href="?m=<?php echo $prev_month; ?>&y=<?php echo $prev_year; ?>&category=<?php echo urlencode($selected_category); ?>&resource=<?php echo urlencode($selected_resource); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                Previous
              </a>
              <div class="cal-nav-label"><?php echo htmlspecialchars($month_name . ' ' . $year); ?></div>
              <a class="cal-nav-btn" href="?m=<?php echo $next_month; ?>&y=<?php echo $next_year; ?>&category=<?php echo urlencode($selected_category); ?>&resource=<?php echo urlencode($selected_resource); ?>">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
              </a>
            </div>

            <div class="cal-grid">
              <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $wd): ?>
                <div class="cal-weekday"><?php echo $wd; ?></div>
              <?php endforeach; ?>

              <?php for ($b = 1; $b < $first_weekday; $b++): ?>
                <div></div>
              <?php endfor; ?>

              <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                <?php $date = sprintf('%04d-%02d-%02d', $year, $month, $day); ?>
                <?php $is_today = ($date === $today); ?>
                <?php $has_booking = isset($bookings_by_date[$date]); ?>
                <div class="cal-day<?php echo $is_today ? ' today' : ''; ?><?php echo $has_booking ? ' booked' : ''; ?>" data-date="<?php echo $date; ?>">
                  <div class="cal-day-num"><?php echo $day; ?></div>
                  <div>
                    <?php if ($has_booking): ?>
                      <span class="cal-pill">
                        <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="10"/></svg>
                        <?php echo count($bookings_by_date[$date]); ?>
                      </span>
                    <?php else: ?>
                      <span class="cal-avail">Free</span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endfor; ?>
            </div>
          </div>

          <aside class="cal-detail">
            <div class="cal-detail-header">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              <h2>Date details</h2>
            </div>
            <p class="cal-detail-hint">Select any date to view reservations. Booked dates are highlighted in green.</p>
            <div class="cal-detail-box" id="calendar-detail">
              <h3>No date selected</h3>
              <ul>
                <li>Click a day to see bookings.</li>
                <li>Use the arrows to browse months.</li>
                <li>Reserved days show a green indicator.</li>
              </ul>
            </div>
          </aside>

        </div>
      </div><!-- /.cal-page -->
    </main>
  </div>

  <script>
    /* ── Resource map for cascading dropdown ── */
    const resourceMap = <?php echo json_encode($js_resource_map, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const allResources = <?php echo json_encode(array_map(fn($id, $r) => ['id' => $id, 'name' => $r['name']], array_keys($resources), array_values($resources)), JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    const currentResource = <?php echo json_encode($selected_resource); ?>;

    function cascadeResources() {
      const cat    = document.getElementById('sel-category').value;
      const sel    = document.getElementById('sel-resource');
      const pool   = cat === 'all' ? allResources : (resourceMap[cat] || []);

      sel.innerHTML = '<option value="all">All Resources</option>';
      pool.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.id;
        opt.textContent = r.name;
        if (r.id === currentResource) opt.selected = true;
        sel.appendChild(opt);
      });

      sel.disabled = pool.length === 0;
    }

    // Init on load
    cascadeResources();

    /* ── Calendar day click ── */
    const bookingsByDate = <?php echo json_encode($bookings_by_date, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const detailPanel = document.getElementById('calendar-detail');

    function esc(v) {
      return String(v)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function renderDetail(date) {
      const items = bookingsByDate[date] || [];
      if (!items.length) {
        detailPanel.innerHTML = '<h3>' + date + ' — no bookings</h3><ul><li>No reservations scheduled for this day.</li></ul>';
        return;
      }
      let html = '<h3>' + date + '</h3><ul>';
      items.forEach(b => {
        const status = b.booking_status || 'Reserved';
        html += '<li><strong>' + esc(b.resource_name || 'Resource') + '</strong> &middot; ' + esc(b.slot || 'No slot');
        if (b.category) html += '<br><span style="color:#94a3b8;font-size:0.8rem;">' + esc(b.category) + '</span>';
        html += '<br><span class="cal-booking-status">' + esc(status) + '</span></li>';
      });
      html += '</ul>';
      detailPanel.innerHTML = html;
    }

    document.querySelectorAll('.cal-day').forEach(cell => {
      cell.addEventListener('click', () => {
        document.querySelectorAll('.cal-day.selected').forEach(el => el.classList.remove('selected'));
        cell.classList.add('selected');
        renderDetail(cell.dataset.date);
      });
    });

    function toggleSidebar() {
      document.body.classList.toggle('sidebar-closed');
    }
  </script>

</body>
</html>