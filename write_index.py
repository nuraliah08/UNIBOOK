# -*- coding: utf-8 -*-
import codecs

content = u"""<?php
// index.php
require_once 'config.php';

// If user is already logged in, show dashboard redirect option
$dashboard_link = null;
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        $dashboard_link = url('admin/dashboard.php');
    } else {
        $dashboard_link = url('user/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book \u2013 University Resource Booking Portal</title>
  <link rel="stylesheet" href="assets/css/style.css?v=2.1">
</head>
<body>

  <!-- PUBLIC HEADER -->
  <header class="pub-header">
    <a href="<?php echo url('index.php'); ?>" class="brand">
      <div class="brand-logo">Uni<span class="brand-dot-center"></span><span class="brand-book">Book</span></div>
      <div class="brand-sub">Resource Portal</div>
    </a>
    <div class="header-actions">
      <?php if ($dashboard_link): ?>
        <a href="<?php echo $dashboard_link; ?>" class="btn-secondary">Go to Dashboard \u2192</a>
      <?php else: ?>
        <a href="<?php echo url('login.php'); ?>" class="btn-secondary" style="margin-right: 10px;">Sign In</a>
        <a href="<?php echo url('register.php'); ?>" class="btn-main" style="display: inline-flex; width: auto; padding: 10px 20px;">Get Started</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- HERO SECTION -->
  <section class="hero-public">
    <h1 class="hero-title">Book Smarter with <span class="hero-brand">Uni<span class="brand-dot-center"></span><span class="brand-book">Book</span></span></h1>
    <p class="hero-subtitle">
      A centralized, intuitive platform for reserving university facilities, official vehicles, event personnel, and high-demand equipment. Streamlined approvals, all in one place.
    </p>
    <div class="hero-btns">
      <?php if ($dashboard_link): ?>
        <a href="<?php echo $dashboard_link; ?>" class="btn-main" style="width: auto; padding: 12px 30px;">Access Your Dashboard</a>
      <?php else: ?>
        <a href="<?php echo url('register.php'); ?>" class="btn-main" style="width: auto; padding: 12px 30px;">Create Account</a>
        <a href="<?php echo url('login.php'); ?>" class="btn-secondary" style="padding: 12px 30px;">Sign In Portal</a>
      <?php endif; ?>
    </div>
  </section>

  <!-- FEATURES SHOWCASE -->
  <section class="showcase-section">
    <div class="section-hdr">
      <h2 class="section-title">Campus Resources at Your Fingertips</h2>
      <p class="section-desc">Explore categories of resources available for booking by students, staff, and faculty.</p>
    </div>

    <div class="features-grid">
      <!-- Card 1 -->
      <div class="feature-card">
        <div class="feature-icon">\U0001f3db\ufe0f</div>
        <h3 class="feature-title">Premium Facilities</h3>
        <p class="feature-body">
          Reserve Seminar Halls, discussion rooms, lecture classrooms, or indoor sports courts. Fully equipped with modern AV setups.
        </p>
      </div>

      <!-- Card 2 -->
      <div class="feature-card">
        <div class="feature-icon">\U0001f68c</div>
        <h3 class="feature-title">Official Vehicles</h3>
        <p class="feature-body">
          Book University passenger vans or institutional buses for student events, local transport, or authorized field trips.
        </p>
      </div>

      <!-- Card 3 -->
      <div class="feature-card">
        <div class="feature-icon">\U0001f91d</div>
        <h3 class="feature-title">Personnel & Services</h3>
        <p class="feature-body">
          Hire professional photographers, AV technical coordinators, or student event crew members to support your campus functions.
        </p>
      </div>

      <!-- Card 4 -->
      <div class="feature-card">
        <div class="feature-icon">\U0001f3a5</div>
        <h3 class="feature-title">Software & Equipment</h3>
        <p class="feature-body">
          Check out high-lumen projectors, professional DSLR cameras, wireless sound speaker systems, and specialized lab tools.
        </p>
      </div>
    </div>
  </section>

  <footer style="background: var(--teal-dark); color: rgba(255,255,255,0.7); text-align: center; padding: 30px 20px; border-top: 1.5px solid rgba(255,255,255,0.05);">
    <p style="font-size: 0.85rem; font-family: 'Space Grotesk', sans-serif;">\u00a9 2026 Uni.Book University Resource Booking. All rights reserved.</p>
    <p style="font-size: 0.72rem; color: rgba(255,255,255,0.4); margin-top: 6px;">Developed for administrative and academic resource management.</p>
  </footer>

</body>
</html>
"""

with codecs.open('index.php', 'w', 'utf-8') as f:
    f.write(content)
print("SUCCESS: index.php written successfully in UTF-8")
