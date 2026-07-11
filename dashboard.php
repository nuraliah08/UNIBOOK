<?php
// user/dashboard.php
require_once '../config.php';
check_auth('user');

$user = get_user_by_email($_SESSION['email']);
$user_name = $user ? $user['name'] : $_SESSION['name'];
$user_initials = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book – User Dashboard</title>
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
        <a href="<?php echo url('user/dashboard.php'); ?>" class="nav-link nav-item active"><?php echo get_icon('dashboard'); ?> Dashboard</a>
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
        <h1 class="page-title"> 🏠︎ Dashboard</h1>
        <p class="page-desc">Overview of your bookings and resource navigation.</p>
      </div>

      <!-- HERO SECTION -->
      <div class="dashboard-hero">
        <div class="hero-welcome">Welcome Back, <?php echo htmlspecialchars($user_name); ?></div>
        <h2 class="hero-headline">Book Smarter with Uni•Book</h2>
        <p class="hero-description">
          A centralized, premium university booking platform for scheduling resources, booking event support staff, planning study venues, and renting technical audio-visual gear.
        </p>
        <a href="<?php echo url('user/add_booking.php'); ?>" class="btn-main" style="width: auto; display: inline-flex; padding: 12px 28px; background: var(--teal-pale); color: var(--teal-dark);">
          Browse Resources 🔍︎
        </a>
      </div>

      <!-- RESOURCE SHOWCASE SECTIONS -->
      <div style="margin-bottom: 30px;">
        <h2 class="panel-title" style="font-size: 1.5rem; margin-bottom: 20px;">Explore Resource Categories</h2>
        
        <div class="showcase-grid">
          
          <!-- Category 1: Facilities -->
          <div class="showcase-card">
            <div class="img-container facilities" style="padding:0;">
              <img src="../facility.jpg" alt="Facilities" style="width:100%; height:180px; object-fit:cover; display:block; border-radius:12px;" />
            </div>
            <div class="showcase-card-body">
              <h3 class="showcase-title">University Venues</h3>
              <p class="showcase-desc">
                Book discussion rooms for research study groups, major lecture halls, or the campus indoor sports complex.
              </p>
              <a href="<?php echo url('user/add_booking.php?category=Facilities'); ?>" class="btn-secondary" style="margin-top: auto;">
                View Resources
              </a>
            </div>
          </div>

          <!-- Category 2: Vehicles -->
          <div class="showcase-card">
            <div class="img-container vehicles" style="padding:0;">
              <img src="../vehicles.jpg" alt="Vehicles" style="width:100%; height:180px; object-fit:cover; display:block; border-radius:12px;" />
            </div>
            <div class="showcase-card-body">
              <h3 class="showcase-title">Official Vehicles</h3>
              <p class="showcase-desc">
                Book a department van or the 44-seater luxury institutional bus for study tours, club trips, or campus events.
              </p>
              <a href="<?php echo url('user/add_booking.php?category=Vehicles'); ?>" class="btn-secondary" style="margin-top: auto;">
                Browse Vehicles
              </a>
            </div>
          </div>

          <!-- Category 3: Personnel -->
          <div class="showcase-card">
            <div class="img-container personnel" style="padding:0;">
              <img src="../personnel.png" alt="Personnel" style="width:100%; height:180px; object-fit:cover; display:block; border-radius:12px;" />
            </div>
            <div class="showcase-card-body">
              <h3 class="showcase-title">Services & Crews</h3>
              <p class="showcase-desc">
                Hire professional photographer coverage, technical AV equipment operators, or logistics volunteer event crews.
              </p>
              <a href="<?php echo url('user/add_booking.php?category=Personnel'); ?>" class="btn-secondary" style="margin-top: auto;">
                View Personnel
              </a>
            </div>
          </div>

          <!-- Category 4: Equipment -->
          <div class="showcase-card">
            <div class="img-container equipment" style="padding:0;">
              <img src="../equipment.jpg" alt="Equipment" style="width:100%; height:180px; object-fit:cover; display:block; border-radius:12px;" />
            </div>
            <div class="showcase-card-body">
              <h3 class="showcase-title">Media & Hardware</h3>
              <p class="showcase-desc">
                Check out active sound speaker systems, HD digital video cameras, laser projectors, or lab equipment.
              </p>
              <a href="<?php echo url('user/add_booking.php?category=Equipment'); ?>" class="btn-secondary" style="margin-top: auto;">
                Explore Equipment
              </a>
            </div>
          </div>

        </div>
      </div>

      <!-- CALL TO ACTION BANNER -->
      <div class="cta-banner">
        <div class="cta-text">
          <h3>Ready to Start Booking?</h3>
          <p>Click here to view calendar</p>
        </div>
        <div class="cta-action">
          <a href="<?php echo url('user/bookingcalendar.php'); ?>" class="btn-main" style="width: auto; padding: 12px 30px;">
            Calendar &nbsp;→
          </a>
        </div>
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


