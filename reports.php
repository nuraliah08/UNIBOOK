<?php
// admin/reports.php
require_once '../config.php';
check_auth('admin');

$admin_name = $_SESSION['name'];

// Get all bookings from standard helper
$all_bookings = get_all_bookings();

// Fetch actual payment_method from bookings_data table directly to bypass incomplete view definition
$conn = db_get_connection();
if ($conn) {
    $pm_rows = db_query_assoc_list("SELECT booking_id, payment_method FROM bookings_data");
    $pm_map = [];
    foreach ($pm_rows as $pm_row) {
        $pm_map['UB' . $pm_row['booking_id']] = $pm_row['payment_method'];
    }
    foreach ($all_bookings as $booking_id => &$bk) {
        if (isset($pm_map[$booking_id])) {
            $bk['payment_method'] = $pm_map[$booking_id];
        }
    }
    unset($bk);
}


// Build available months list for dropdown
$available_months = [];
foreach ($all_bookings as $bk) {
    if (!empty($bk['date'])) {
        $val = date('Y-m', strtotime($bk['date']));
        $label = date('F Y', strtotime($bk['date']));
        $available_months[$val] = $label;
    }
}
uksort($available_months, function($a, $b) {
    return strcmp($b, $a); // Show newest months first
});

// Filter by selected month
$selected_month = isset($_GET['month']) ? $_GET['month'] : '';

$filtered_bookings = [];
foreach ($all_bookings as $booking_id => $bk) {
    if (!empty($selected_month)) {
        $bk_month = date('Y-m', strtotime($bk['date']));
        if ($bk_month !== $selected_month) {
            continue;
        }
    }
    $filtered_bookings[$booking_id] = $bk;
}

// 1. Calculate Booking Statuses & Financial values based on filtered list
$total_bookings = count($filtered_bookings);
$confirmed_bookings = 0;
$rejected_bookings = 0;
$pending_bookings = 0;
$completed_bookings = 0;
$cancelled_bookings = 0;
$refunded_bookings = 0;

$total_revenue = 0;
$total_paid_bookings = 0;
$total_refunded = 0;
$total_refunded_bookings = 0;

$paid_bookings_detail = [];
$refunded_bookings_detail = [];

foreach ($filtered_bookings as $booking_id => $bk) {
    $status = $bk['booking_status'] ?? '';
    $pstatus = $bk['payment_status'] ?? '';

    // Count statistics
    if ($status === 'Confirmed' || $status === 'Approved') {
        $confirmed_bookings++;
    } elseif ($status === 'Rejected' || $status === 'Declined') {
        $rejected_bookings++;
    } elseif ($status === 'Pending' || $status === 'Pending Review') {
        $pending_bookings++;
    } elseif ($status === 'Completed') {
        $completed_bookings++;
    } elseif ($status === 'Cancelled') {
        $cancelled_bookings++;
    }

    if ($pstatus === 'Refunded') {
        $refunded_bookings++;
    }

    // Revenue / Refund details
    if ($pstatus === 'Paid') {
        $total_paid_bookings++;
        $total_revenue += floatval($bk['amount']);
        $method = $bk['payment_method'] ?? 'Card';
        if (stripos($method, 'toyyib') !== false) {
            $method = 'Toyyib';
        } else {
            $method = 'Card';
        }
        $paid_bookings_detail[] = [
            'email' => $bk['user_email'] ?? '',
            'booking_ref' => $booking_id,
            'payment_method' => $method,
            'amount' => floatval($bk['amount'])
        ];
    } elseif ($pstatus === 'Refunded') {
        $total_refunded_bookings++;
        $total_refunded += floatval($bk['amount']);
        $method = $bk['payment_method'] ?? 'Card';
        if (stripos($method, 'toyyib') !== false) {
            $method = 'Toyyib';
        } else {
            $method = 'Card';
        }
        $refunded_bookings_detail[] = [
            'email' => $bk['user_email'] ?? '',
            'booking_ref' => $booking_id,
            'payment_method' => $method,
            'amount' => floatval($bk['amount'])
        ];
    }
}

// 2. Calculate Resource Booking Counts
$resource_counts = [];
foreach ($filtered_bookings as $bk) {
    $res_name = $bk['resource_name'] ?? '';
    $cat = $bk['category'] ?? '';
    if (!empty($res_name)) {
        if (!isset($resource_counts[$res_name])) {
            $resource_counts[$res_name] = [
                'name' => $res_name,
                'category' => $cat,
                'count' => 0
            ];
        }
        $resource_counts[$res_name]['count']++;
    }
}

// Sort from highest booking count to lowest
uasort($resource_counts, function($a, $b) {
    return $b['count'] - $a['count'];
});

// 3. Print layout check
$print_mode = isset($_GET['print']) ? $_GET['print'] : '';
$is_print = !empty($print_mode);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book – Reports Panel</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=2.5">
  <?php if ($is_print): ?>
  <style>
    @media print { .no-print { display: none !important; } body { padding: 0 !important; } }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Arial', sans-serif; font-size: 13px; color: #111; background: #fff; }
    .page { max-width: 860px; margin: 0 auto; padding: 40px 48px; }

    .doc-header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 16px; border-bottom: 2px solid #0F6E56; margin-bottom: 28px; }
    .doc-brand { font-size: 22px; font-weight: 700; color: #0F6E56; }
    .doc-brand span { color: #111; }
    .doc-subtitle { font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 1px; margin-top: 3px; }
    .doc-meta { text-align: right; font-size: 11px; color: #555; line-height: 1.8; }
    .doc-meta strong { display: block; font-size: 12px; color: #111; }

    .section { margin-bottom: 32px; }
    .section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: #0F6E56; margin-bottom: 6px; }
    .section-title { font-size: 15px; font-weight: 700; border-bottom: 1px solid #ddd; padding-bottom: 6px; margin-bottom: 12px; }

    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    thead th { background: #f4f4f4; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; color: #444; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; font-weight: 700; }
    tbody td { padding: 8px 10px; border-bottom: 1px solid #eee; color: #222; vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    .r { text-align: right; } .c { text-align: center; }
    tfoot td { padding: 9px 10px; border-top: 2px solid #ccc; background: #f9f9f9; font-weight: 700; }
    tfoot td.r { color: #0F6E56; font-size: 13px; }

    .method-pill { display: inline-block; font-size: 10px; padding: 2px 8px; border-radius: 99px; font-weight: 700; text-transform: none; }
    .m-online    { background: #E1F5EE; color: #0F6E56; }
    .m-card      { background: #E6F1FB; color: #185FA5; }
    .m-toyyibpay { background: #F3E5F5; color: #7B1FA2; }

    .stat-list { display: flex; flex-direction: column; gap: 8px; max-width: 400px; margin-bottom: 20px; }
    .stat-item { display: flex; justify-content: space-between; padding: 8px 12px; border: 1px solid #eee; border-radius: 6px; font-size: 13px; }
    .stat-item strong { color: #0F6E56; }

    .asset-cat { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #888; display: block; }
    .count-badge { display: inline-block; background: #E1F5EE; color: #0F6E56; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 99px; }

    .doc-footer { margin-top: 36px; padding-top: 12px; border-top: 1px dashed #ccc; display: flex; justify-content: space-between; font-size: 10px; color: #999; }
    .btn-row { text-align: center; margin-top: 24px; display: flex; gap: 10px; justify-content: center; }
    .btn-p { background: #0F6E56; color: #fff; border: none; padding: 9px 22px; font-size: 13px; border-radius: 6px; cursor: pointer; }
    .btn-b { background: #fff; color: #555; border: 1px solid #ccc; padding: 8px 18px; font-size: 13px; border-radius: 6px; cursor: pointer; text-decoration: none; }
  </style>
  <?php endif; ?>
  <style>
    /* Styling overrides for reports web view */
    .kpi-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 24px;
    }
    .kpi-card {
      border-radius: 12px;
      padding: 24px;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 20px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    .kpi-card.green-box {
      background: linear-gradient(135deg, #10b981 0%, #047857 100%);
      border: 1px solid #059669;
    }
    .kpi-card.red-box {
      background: linear-gradient(135deg, #f87171 0%, #b91c1c 100%);
      border: 1px solid #dc2626;
    }
    .kpi-card-icon {
      font-size: 2.8rem;
      line-height: 1;
    }
    .kpi-card-val {
      font-size: 2rem;
      font-weight: 700;
      font-family: 'Space Grotesk', sans-serif;
      line-height: 1.1;
    }
    .kpi-card-lbl {
      font-size: 0.9rem;
      opacity: 0.9;
      font-weight: 500;
      margin-top: 4px;
    }
    .button-group-row {
      display: flex;
      gap: 16px;
      margin-top: 24px;
      margin-bottom: 12px;
          justify-content: center;

    }
    .btn-export {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      font-family: 'Inter', sans-serif;
      border: 1.5px solid transparent;
    }
    .btn-export-financial {
      background: #0d6e6e;
      color: #fff;
    }
    .btn-export-financial:hover {
      background: #059669;
    }
    .btn-export-booking {
      background: #0d6e6e;
      color: #fff;
    }
    .btn-export-booking:hover {
      background: #059669;
    }
  </style>
</head>
<body class="sidebar-closed">

  <?php if ($is_print): ?>
<div class="page">

  <div class="doc-header">
    <div>
      <div class="doc-brand">Uni.<span>Book</span></div>
      <div class="doc-subtitle">University Resource Booking System</div>
    </div>
    <div class="doc-meta">
      <strong>
        <?php echo $print_mode === 'financial' ? 'Financial Revenue & Refund Report' : 'Booking Distribution & Statistics Report'; ?>
      </strong>
      Generated: <?php echo date('F d, Y — h:i A'); ?><br>
      <?php if (!empty($selected_month)): ?>
        Report Period: <?php echo date('F Y', strtotime($selected_month . '-01')); ?><br>
      <?php else: ?>
        Report Period: All Time<br>
      <?php endif; ?>
      Prepared by: Administrator
    </div>
  </div>

  <?php if ($print_mode === 'financial'): ?>
    <!-- FINANCIAL REPORT PRINT MODE -->
    <div class="section">
      <div class="section-label">Section 1</div>
      <div class="section-title">Paid Invoices & Revenue Summary</div>
      <table>
        <thead>
          <tr>
            <th style="width:5%">#</th>
            <th style="width:45%">User Email</th>
            <th style="width:18%">Booking Ref.</th>
            <th class="c" style="width:16%">Payment Method</th>
            <th class="r" style="width:16%">Amount (MYR)</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          foreach ($paid_bookings_detail as $row):
          ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['booking_ref']); ?></td>
            <td class="c"><span class="method-pill <?php echo strtolower($row['payment_method']) === 'toyyib' ? 'm-toyyibpay' : 'm-card'; ?>"><?php echo htmlspecialchars($row['payment_method']); ?></span></td>
            <td class="r"><?php echo format_currency($row['amount']); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($paid_bookings_detail)): ?>
          <tr>
            <td colspan="5" class="c" style="color: #666; padding: 20px;">No paid transactions found for this period.</td>
          </tr>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3"></td>
            <td style="font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#444;">Total Revenue</td>
            <td class="r"><?php echo format_currency($total_revenue); ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="section">
      <div class="section-label">Section 2</div>
      <div class="section-title" style="color:#dc2626;">Refunded Bookings Summary</div>
      <table>
        <thead>
          <tr>
            <th style="width:5%; color:#dc2626;">#</th>
            <th style="width:45%; color:#dc2626;">User Email</th>
            <th style="width:18%; color:#dc2626;">Booking Ref.</th>
            <th class="c" style="width:16%; color:#dc2626;">Payment Method</th>
            <th class="r" style="width:16%; color:#dc2626;">Refund Amount (MYR)</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          foreach ($refunded_bookings_detail as $row):
          ?>
          <tr style="color:#b91c1c;">
            <td style="color:#b91c1c;"><?php echo $i++; ?></td>
            <td style="color:#b91c1c;"><?php echo htmlspecialchars($row['email']); ?></td>
            <td style="color:#b91c1c;"><?php echo htmlspecialchars($row['booking_ref']); ?></td>
            <td class="c" style="color:#b91c1c;"><span class="method-pill" style="background:#fef2f2; color:#b91c1c;"><?php echo htmlspecialchars($row['payment_method']); ?></span></td>
            <td class="r" style="color:#b91c1c;">-<?php echo format_currency($row['amount']); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($refunded_bookings_detail)): ?>
          <tr>
            <td colspan="5" class="c" style="color: #666; padding: 20px;">No refunded bookings found for this period.</td>
          </tr>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr style="color:#b91c1c; background:#fef2f2;">
            <td colspan="3"></td>
            <td style="font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#b91c1c;">Total Refunded</td>
            <td class="r" style="color:#b91c1c;">-<?php echo format_currency($total_refunded); ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

  <?php elseif ($print_mode === 'booking'): ?>
    <!-- BOOKING STATISTICS PRINT MODE -->
    <div class="section">
      <div class="section-label">Section 1</div>
      <div class="section-title">Booking Statistics & Status Distribution</div>
      <div class="stat-list">
        <div class="stat-item"><span>Total Reservations:</span><strong><?php echo $total_bookings; ?></strong></div>
        <div class="stat-item"><span>Confirmed Bookings:</span><strong><?php echo $confirmed_bookings; ?></strong></div>
        <div class="stat-item"><span>Pending Approval:</span><strong><?php echo $pending_bookings; ?></strong></div>
        <div class="stat-item"><span>Completed Bookings:</span><strong><?php echo $completed_bookings; ?></strong></div>
        <div class="stat-item"><span>Rejected Requests:</span><strong><?php echo $rejected_bookings; ?></strong></div>
        <div class="stat-item"><span>Cancelled Bookings:</span><strong><?php echo $cancelled_bookings; ?></strong></div>
        <div class="stat-item"><span>Refunded Bookings:</span><strong><?php echo $refunded_bookings; ?></strong></div>
      </div>
    </div>

    <div class="section">
      <div class="section-label">Section 2</div>
      <div class="section-title">Most Booked Resources</div>
      <table>
        <thead>
          <tr>
            <th style="width:10%">Rank</th>
            <th style="width:45%">Resource Name</th>
            <th style="width:25%">Category</th>
            <th class="c" style="width:20%">Booking count</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $rank = 1;
          foreach ($resource_counts as $res_name => $data): 
          ?>
          <tr>
            <td style="font-weight:700;">#<?php echo $rank++; ?></td>
            <td style="font-weight:600;"><?php echo htmlspecialchars($res_name); ?></td>
            <td><span class="asset-cat"><?php echo htmlspecialchars($data['category']); ?></span></td>
            <td class="c"><span class="count-badge"><?php echo $data['count']; ?> bookings</span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($resource_counts)): ?>
          <tr>
            <td colspan="4" class="c" style="color: #666; padding: 20px;">No resource bookings recorded.</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="doc-footer">
    <span>Uni.Book — Confidential. System Generated Report.</span>
    <span>Page 1 of 1</span>
  </div>

  <div class="btn-row no-print">
    <a href="reports.php" class="btn-b">Back to reports</a>
    <button class="btn-p" onclick="window.print()">Print / Save PDF</button>
  </div>

</div>

<script>
  window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => { window.print(); }, 600);
  });
</script>

  <?php else: ?>

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
        <a href="<?php echo url('admin/dashboard.php'); ?>" class="nav-link nav-item"><?php echo get_icon('dashboard'); ?> Dashboard</a>
        <a href="<?php echo url('admin/resources.php'); ?>" class="nav-link nav-item"><?php echo get_icon('resources'); ?> Manage Resources</a>
        <a href="<?php echo url('admin/bookingcalender.php'); ?>" class="nav-link nav-item"><?php echo get_icon('bookings'); ?> Calender</a>
        <a href="<?php echo url('admin/bookings.php'); ?>" class="nav-link nav-item"><?php echo get_icon('bookings'); ?> View Bookings</a>
        <a href="<?php echo url('admin/users.php'); ?>" class="nav-link nav-item"><?php echo get_icon('profile'); ?> Manage Users</a>
        <a href="<?php echo url('admin/reports.php'); ?>" class="nav-link nav-item active"><?php echo get_icon('reports'); ?> Reports</a>
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
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 15px;">
          <div>
            <h1 class="page-title"> 🗁 System Reports</h1>
            <p class="page-desc">Check out financial stats, booking distributions, and top-performing items.</p>
          </div>
          <div style="display: flex; gap: 10px; align-items: center;" class="no-print">
            <select id="monthSelect" style="padding: 10px; border-radius: 8px; border: 1.5px solid var(--border); font-family: 'Inter', sans-serif; font-size: 0.9rem; background: var(--white); outline: none;" onchange="filterMonth()">
              <option value="">All Months</option>
              <?php foreach ($available_months as $val => $label): ?>
                <option value="<?php echo $val; ?>" <?php echo $selected_month === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <script>
          function filterMonth() {
            const m = document.getElementById('monthSelect').value;
            window.location.href = 'reports.php' + (m ? '?month=' + encodeURIComponent(m) : '');
          }
        </script>

        <!-- KPI ROW (GREEN AND RED BOXES) -->
        <div class="kpi-row">
          <!-- REVENUE SUMMARY CARD (GREEN) -->
          <div class="kpi-card green-box">
            <div class="kpi-card-icon">
              <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width: 38px; height: 38px; color: #fff;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
              <div class="kpi-card-val"><?php echo format_currency($total_revenue); ?></div>
              <div class="kpi-card-lbl">Revenue Summary (<?php echo $total_paid_bookings; ?> Paid Bookings)</div>
            </div>
          </div>

          <!-- REFUNDED SUMMARY CARD (RED) -->
          <div class="kpi-card red-box">
            <div class="kpi-card-icon">↺</div>
            <div>
              <div class="kpi-card-val"><?php echo format_currency($total_refunded); ?></div>
              <div class="kpi-card-lbl">Refunded Amount (<?php echo $total_refunded_bookings; ?> Bookings Refunded)</div>
            </div>
          </div>
        </div>

        <div class="dashboard-grid-2" style="gap: 20px; margin-bottom: 24px;">
          
          <!-- BOOKING SUMMARY PANEL -->
          <div class="dashboard-panel" style="min-height: 380px;">
            <h2 class="panel-title">
              <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width: 18px; height: 18px; color: #0d6e6e; vertical-align: middle; margin-right: 6px; display: inline-block;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"></path></svg>Booking Statistics
            </h2>
            <p style="color:var(--muted); font-size:0.8rem; margin-top:-14px; margin-bottom:24px;">Breakdown of status totals for the selected period.</p>
            
            <div style="display:flex; flex-direction:column; gap:10px; font-size:0.9rem;">
              <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border);">
                <span>Total Reservations:</span>
                <strong style="color:var(--ink);"><?php echo $total_bookings; ?></strong>
              </div>
              <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border);">
                <span>Confirmed Bookings:</span>
                <span style="color:var(--success); font-weight:700;"><?php echo $confirmed_bookings; ?></span>
              </div>
              <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border);">
                <span>Pending Approval:</span>
                <span style="color:var(--warning); font-weight:700;"><?php echo $pending_bookings; ?></span>
              </div>
              <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border);">
                <span>Completed Reservations:</span>
                <span style="color:var(--info); font-weight:700;"><?php echo $completed_bookings; ?></span>
              </div>
              <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border);">
                <span>Rejected Requests:</span>
                <span style="color:var(--error); font-weight:700;"><?php echo $rejected_bookings; ?></span>
              </div>
              <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border);">
                <span>Cancelled Bookings:</span>
                <span style="color:#6b7280; font-weight:700;"><?php echo $cancelled_bookings; ?></span>
              </div>
              <div style="display:flex; justify-content:space-between; padding-bottom:2px;">
                <span>Refunded Bookings:</span>
                <span style="color:#b91c1c; font-weight:700;"><?php echo $refunded_bookings; ?></span>
              </div>
            </div>
          </div>

          <!-- MOST BOOKED RESOURCES (RIGHT COLUMN) -->
          <div class="dashboard-panel" style="min-height: 380px;">
            <h2 class="panel-title">
              <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width: 18px; height: 18px; color: #0d6e6e; vertical-align: middle; margin-right: 6px; display: inline-block;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>Most Booked Resources
            </h2>
            <p style="color:var(--muted); font-size:0.8rem; margin-top:-14px; margin-bottom:24px;">Ranked list of resources from most booked to least (minimum 1 booking).</p>

            <div style="display:flex; flex-direction:column; gap:12px; max-height:280px; overflow-y:auto; padding-right:4px;">
              <?php 
              $rank = 1;
              foreach ($resource_counts as $res_name => $data): 
                $category = $data['category'];
                $icon_svg = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width: 16px; height: 16px; color:#fff;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>'; // Equipment/default
                if ($category === 'Facilities') {
                    $icon_svg = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width: 16px; height: 16px; color:#fff;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>';
                } elseif ($category === 'Vehicles') {
                    $icon_svg = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width: 16px; height: 16px; color:#fff;"><rect x="2" y="9" width="20" height="8" rx="2"></rect><circle cx="6" cy="17" r="2"></circle><circle cx="18" cy="17" r="2"></circle><path d="M18 9l-3-4H9L6 9"></path></svg>';
                } elseif ($category === 'Personnel') {
                    $icon_svg = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width: 16px; height: 16px; color:#fff;"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>';
                }
              ?>
                <div style="display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:4px;">
                  <div style="display:flex; align-items:center; gap:14px;">
                    <span style="font-weight:700; font-size:0.95rem; color:var(--teal); min-width:20px;">#<?php echo $rank++; ?></span>
                    <div class="metric-icon teal" style="width:36px; height:36px; border-radius:6px; background:#0d6e6e; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><?php echo $icon_svg; ?></div>
                    <div>
                      <div style="font-size:0.95rem; font-weight:600; color:var(--ink);"><?php echo htmlspecialchars($res_name); ?></div>
                      <div style="font-size:0.75rem; color:var(--muted);"><?php echo htmlspecialchars($category); ?></div>
                    </div>
                  </div>
                  
                  <div style="text-align:right;">
                    <div style="font-size:1rem; font-weight:700; color:var(--teal); font-family:'Space Grotesk';"><?php echo $data['count']; ?></div>
                    <div style="font-size:0.7rem; color:var(--muted); font-weight:500;">Bookings</div>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if (empty($resource_counts)): ?>
                <div style="padding:20px; text-align:center; color:var(--muted);">No resource bookings recorded.</div>
              <?php endif; ?>
            </div>
          </div>

        </div>

        <!-- BUTTONS GROUP FOR REPORTS -->
        <div class="button-group-row no-print">
          <a href="reports.php?print=financial<?php echo !empty($selected_month) ? '&month=' . urlencode($selected_month) : ''; ?>" target="_blank" class="btn-export btn-export-financial">
            🗁 Generate Financial Report
          </a>
          <a href="reports.php?print=booking<?php echo !empty($selected_month) ? '&month=' . urlencode($selected_month) : ''; ?>" target="_blank" class="btn-export btn-export-booking">
            🗁 Generate Booking Report
          </a>
        </div>

      </main>
    </div>

  <?php endif; ?>

  <script>
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-closed');
    }
  </script>

</body>
</html>
