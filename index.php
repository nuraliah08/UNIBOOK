<?php
// index.php
require_once 'config.php';

// If user is already logged in, show dashboard redirect option
$dashboard_link = null;
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        $dashboard_link = url('admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'manager') {
        $dashboard_link = url('manager/dashboard.php');
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
  <title>Uni.Book – University Resource Booking Portal</title>
  <link rel="stylesheet" href="assets/css/style.css?v=2.3">
  <style>
    /* ── Hero Public ── */


    body .hero-public {
  position: relative !important;
  padding: 160px 40px 180px !important;
  text-align: center !important;
  overflow: hidden !important;
  isolation: isolate !important;
  background: linear-gradient(160deg, #031f1f 0%, #063232 35%, #0a4a4a 65%, #0d5e5e 100%) !important;
  border-bottom: none !important;
  display: block !important;
}

body .hero-title {
  color: #ffffff !important;
  text-shadow: 0 2px 24px rgba(0, 0, 0, 0.35) !important;
}

body .hero-subtitle {
  color: rgba(255, 255, 255, 0.68) !important;
}



    .hero-public {
      position: relative;
      padding: 100px 40px 110px;
      text-align: center;
      overflow: hidden;
      isolation: isolate;
      background: linear-gradient(160deg, #031f1f 0%, #063232 35%, #0a4a4a 65%, #0d5e5e 100%);
    }

    .hero-public::before {
      content: '';
      position: absolute;
      inset: 0;
      z-index: 0;
      background-image:
        linear-gradient(rgba(125, 232, 232, 0.07) 1px, transparent 1px),
        linear-gradient(90deg, rgba(125, 232, 232, 0.07) 1px, transparent 1px);
      background-size: 48px 48px;
      mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
    }

    .hero-public::after {
      content: '';
      position: absolute;
      top: -120px;
      left: -120px;
      width: 480px;
      height: 480px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(13, 180, 150, 0.22) 0%, transparent 70%);
      z-index: 0;
      pointer-events: none;
    }

    .hero-public .hero-orb-br {
      position: absolute;
      bottom: -100px;
      right: -80px;
      width: 400px;
      height: 400px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(26, 144, 144, 0.18) 0%, transparent 70%);
      z-index: 0;
      pointer-events: none;
    }

    .hero-public .hero-ring {
      position: absolute;
      border-radius: 50%;
      pointer-events: none;
      z-index: 0;
    }
    .hero-public .hero-ring-1 {
      width: 340px; height: 340px;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%) rotateX(68deg);
      border: 1px solid rgba(125, 232, 232, 0.10);
    }
    .hero-public .hero-ring-2 {
      width: 540px; height: 540px;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%) rotateX(68deg);
      border: 1px solid rgba(125, 232, 232, 0.07);
    }
    .hero-public .hero-ring-3 {
      width: 740px; height: 740px;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%) rotateX(68deg);
      border: 1px solid rgba(125, 232, 232, 0.04);
    }

    .hero-public > *:not(.hero-orb-br):not(.hero-ring) {
      position: relative;
      z-index: 1;
    }

    .hero-title {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 3.2rem;
      font-weight: 700;
      color: #ffffff;
      line-height: 1.15;
      margin-bottom: 20px;
      letter-spacing: -1px;
      text-shadow: 0 2px 24px rgba(0, 0, 0, 0.35);
    }

    .hero-subtitle {
      font-size: 1.15rem;
      color: rgba(255, 255, 255, 0.68);
      max-width: 660px;
      margin: 0 auto 36px auto;
      font-weight: 400;
      line-height: 1.7;
    }

    .hero-public .btn-main {
      background: #0d9e8a;
      color: #ffffff;
      box-shadow: 0 4px 20px rgba(13, 158, 138, 0.35), inset 0 1px 0 rgba(255,255,255,0.15);
    }
    .hero-public .btn-main:hover {
      background: #0fbfa6;
      box-shadow: 0 6px 28px rgba(13, 158, 138, 0.45);
    }
    .hero-public .btn-secondary {
      border-color: rgba(125, 232, 232, 0.5);
      color: #7de8e8;
      background: rgba(125, 232, 232, 0.07);
    }
    .hero-public .btn-secondary:hover {
      background: rgba(125, 232, 232, 0.14);
      color: #ffffff;
      border-color: rgba(125, 232, 232, 0.8);
    }

    /* ── Feature Cards Section ── */
    .feature-cards-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 24px;
      max-width: 1200px;
      margin: 48px auto 0;
      padding: 0 24px 64px;
    }

    .feature-card {
      background: #0a3d30;
      border: 1px solid rgba(94, 231, 196, 0.15);
      border-radius: 18px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      opacity: 0;
      transform: translateY(52px);
      transition: opacity 0.6s cubic-bezier(0.22, 1, 0.36, 1),
                  transform 0.6s cubic-bezier(0.22, 1, 0.36, 1),
                  background 0.25s ease,
                  border-color 0.25s ease,
                  box-shadow 0.25s ease;
    }

    .feature-card.card-delay-1 { transition-delay: 0s; }
    .feature-card.card-delay-2 { transition-delay: 0.12s; }
    .feature-card.card-delay-3 { transition-delay: 0.24s; }
    .feature-card.card-delay-4 { transition-delay: 0.36s; }

    .feature-card.is-visible {
      opacity: 1;
      transform: translateY(0);
    }

    .feature-card:hover {
      background: #0f4f3e;
      border-color: rgba(94, 231, 196, 0.35);
      box-shadow: 0 8px 36px rgba(0, 0, 0, 0.3);
      transform: translateY(-5px);
    }

    .feature-card.is-visible:hover {
      transform: translateY(-5px);
    }

.feature-card-img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  object-position: center;
  display: block;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

    .feature-card-body {
      padding: 22px 20px 24px;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .feature-card-tag {
      display: inline-block;
      font-size: 0.68rem;
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--teal-light, #5ee7c4);
      background: rgba(94, 231, 196, 0.12);
      border: 1px solid rgba(94, 231, 196, 0.25);
      border-radius: 20px;
      padding: 3px 10px;
      width: fit-content;
    }

    .feature-card-title {
      font-size: 1.05rem;
      font-weight: 700;
      font-family: 'Space Grotesk', sans-serif;
      color: #ffffff;
      margin: 0;
      line-height: 1.35;
    }

    .feature-card-desc {
      font-size: 0.82rem;
      color: rgba(255, 255, 255, 0.62);
      line-height: 1.65;
      margin: 0;
      flex: 1;
    }

    .feature-card-link {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.8rem;
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 600;
      color: var(--teal-light, #5ee7c4);
      text-decoration: none;
      margin-top: 4px;
      transition: gap 0.2s ease;
    }

    .feature-card-link:hover { gap: 9px; }
    .feature-card-link-arrow { font-size: 1rem; line-height: 1; }

    /* responsive */
    @media (max-width: 960px) {
      .feature-cards-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    @media (max-width: 560px) {
      .feature-cards-grid {
        grid-template-columns: 1fr;
      }
      .hero-public {
        padding: 70px 24px 80px;
      }
      .hero-title {
        font-size: 2.2rem;
      }
    }
  </style>
</head>
<body>

  <!-- PUBLIC HEADER -->
  <header class="pub-header">
    <a href="<?php echo url('index.php'); ?>" class="brand">
      <div class="brand-logo">Uni<span class="brand-dot-center"></span><span class="brand-book">Book</span></div>
      <div class="brand-sub">University Booking System</div>
    </a>
    <div class="header-actions">
      <?php if ($dashboard_link): ?>
        <a href="<?php echo $dashboard_link; ?>" class="btn-secondary">Go to Dashboard →</a>
      <?php else: ?>
        <a href="<?php echo url('login.php'); ?>" class="btn-secondary" style="margin-right: 10px;">Sign In</a>
        <a href="<?php echo url('register.php'); ?>" class="btn-main" style="display: inline-flex; width: auto; padding: 10px 20px;">Get Started</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- HERO SECTION -->
  <section class="hero-public">
    <div class="hero-orb-br"></div>
    <div class="hero-ring hero-ring-1"></div>
    <div class="hero-ring hero-ring-2"></div>
    <div class="hero-ring hero-ring-3"></div>
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

    <div class="feature-cards-grid">

      <!-- Card 1: Facilities -->
      <div class="feature-card card-delay-1">
        <img
          src="facility.jpg"
          alt="Facilities booking"
          class="feature-card-img"
        >
        <div class="feature-card-body">
          <span class="feature-card-tag">Facilities</span>
          <h3 class="feature-card-title">Reserve Spaces &amp; Venues</h3>
          <p class="feature-card-desc">Book lecture halls, seminar rooms, labs, sports courts, and auditoriums with real-time availability and instant confirmation.</p>
          <a href="<?php echo url('login.php'); ?>" class="feature-card-link">Book a space <span class="feature-card-link-arrow">→</span></a>
        </div>
      </div>

      <!-- Card 2: Vehicles -->
      <div class="feature-card card-delay-2">
        <img
          src="vehicles.jpg"
          alt="Vehicle booking"
          class="feature-card-img"
        >
        <div class="feature-card-body">
          <span class="feature-card-tag">Vehicles</span>
          <h3 class="feature-card-title">Official University Fleet</h3>
          <p class="feature-card-desc">Request university cars, vans, and buses for field trips, official travel, and campus logistics — all with driver scheduling built in.</p>
          <a href="<?php echo url('login.php'); ?>" class="feature-card-link">Request a vehicle <span class="feature-card-link-arrow">→</span></a>
        </div>
      </div>

      <!-- Card 3: Personnel & Moderators -->
      <div class="feature-card card-delay-3">
        <img
          src="personnel.png"
          alt="Personnel and moderator booking"
          class="feature-card-img"
        >
        <div class="feature-card-body">
          <span class="feature-card-tag">Personnel</span>
          <h3 class="feature-card-title">Staff &amp; Event Moderators</h3>
          <p class="feature-card-desc">Assign technicians, event moderators, security, and support staff to your events. Managed availability ensures no double-booking.</p>
          <a href="<?php echo url('login.php'); ?>" class="feature-card-link">Assign personnel <span class="feature-card-link-arrow">→</span></a>
        </div>
      </div>

      <!-- Card 4: Equipment -->
      <div class="feature-card card-delay-4">
        <img
          src="equipment.jpg"
          alt="Equipment booking"
          class="feature-card-img"
        >
        <div class="feature-card-body">
          <span class="feature-card-tag">Equipment</span>
          <h3 class="feature-card-title">High-Demand Gear &amp; Tech</h3>
          <p class="feature-card-desc">Reserve projectors, PA systems, cameras, scientific instruments, and computing hardware. Track loan periods and return reminders automatically.</p>
          <a href="<?php echo url('login.php'); ?>" class="feature-card-link">Browse equipment <span class="feature-card-link-arrow">→</span></a>
        </div>
      </div>

    </div>
  </section>

  <footer style="background: var(--teal-dark); color: rgba(255,255,255,0.7); text-align: center; padding: 30px 20px; border-top: 1.5px solid rgba(255,255,255,0.05);">
    <p style="font-size: 0.85rem; font-family: 'Space Grotesk', sans-serif;">© 2026 Uni.Book University Resource Booking. All rights reserved.</p>
    <p style="font-size: 0.72rem; color: rgba(255,255,255,0.4); margin-top: 6px;">Developed for administrative and academic resource management.</p>
  </footer>

  <script>
    (function () {
      var cards = document.querySelectorAll('.feature-card');
      if (!cards.length) return;

      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.15 });

      cards.forEach(function (card) {
        observer.observe(card);
      });
    })();
  </script>

</body>
</html>