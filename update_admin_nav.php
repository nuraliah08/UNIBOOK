<?php
$files = glob(__DIR__ . '/*.php');
foreach ($files as $file) {
    if (in_array(basename($file), ['update_admin_nav.php'])) continue;

    $content = file_get_contents($file);
    if (strpos($content, '<nav class="sidebar-nav">') === false) continue;

    $basename = basename($file);
    
    $nav = '      <nav class="sidebar-nav">
        <a href="<?php echo url(\'admin/dashboard.php\'); ?>" class="nav-link nav-item'.($basename=='dashboard.php'?' active':'').'"><?php echo get_icon(\'dashboard\'); ?> Dashboard</a>
        <a href="<?php echo url(\'admin/resources.php\'); ?>" class="nav-link nav-item'.($basename=='resources.php'?' active':'').'"><?php echo get_icon(\'resources\'); ?> Manage Resources</a>
        <a href="<?php echo url(\'admin/bookingcalender.php\'); ?>" class="nav-link nav-item'.($basename=='bookingcalender.php'?' active':'').'"><?php echo get_icon(\'bookings\'); ?> Calender</a>
        <a href="<?php echo url(\'admin/bookings.php\'); ?>" class="nav-link nav-item'.($basename=='bookings.php'?' active':'').'"><?php echo get_icon(\'bookings\'); ?> View Bookings</a>
        <a href="<?php echo url(\'admin/users.php\'); ?>" class="nav-link nav-item'.($basename=='users.php'?' active':'').'"><?php echo get_icon(\'profile\'); ?> Manage Users</a>
        <a href="<?php echo url(\'admin/reports.php\'); ?>" class="nav-link nav-item'.($basename=='reports.php'?' active':'').'"><?php echo get_icon(\'reports\'); ?> Reports</a>
        <a href="<?php echo url(\'logout.php\'); ?>" class="nav-link nav-item" style="margin-top:20px; border-top:1px solid rgba(255,255,255,0.1); padding-top:20px;"><?php echo get_icon(\'logout\'); ?> Logout</a>
      </nav>
      <div class="sidebar-user">
        <div class="user-avatar">AD</div>
        <div class="user-meta">
          <span class="user-name">Administrator</span>
          <span class="user-role">System Admin</span>
        </div>
      </div>';

    // We want to replace everything from <nav class="sidebar-nav"> to the closing </aside> tag (not including </aside>)
    $content = preg_replace('/<nav class="sidebar-nav">.*?(?=<\/aside>)/is', $nav . "\n    ", $content);
    file_put_contents($file, $content);
    echo "Updated $basename\n";
}
