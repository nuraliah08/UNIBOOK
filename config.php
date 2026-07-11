<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-inject shared footer to HTML pages
ob_start(function($buffer) {
    if (stripos($buffer, '</body>') !== false) {
        $footer_html = '
  <style>
    .app-shared-footer {
      background: #073838;
      color: #9ca3af;
      text-align: center;
      padding: 10px;
      font-size: 0.85rem;
      border-top: 1.5px solid #71a74f;
      position: relative;
      z-index: 10;
      clear: both;
    }
    .app-shared-footer a {
      color: #38bdf8;
      text-decoration: none;
    }
    .app-shared-footer a:hover {
      text-decoration: underline;
    }
    .dashboard-layout + .app-shared-footer {
      transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    body:not(.sidebar-closed) .dashboard-layout + .app-shared-footer {
      margin-left: 260px;
    }
  </style>
  <footer class="app-shared-footer">
    <p style="margin: 5px 0;">2026 © Uni.Book</p>
    <p style="margin: 5px 0;">Contact Us: <a href="mailto:unibook776@gmail.com">unibook776@gmail.com</a></p>
  </footer>';
        
        // Remove existing footer(s) if present
        $buffer = preg_replace('/<footer.*?<\/footer>/is', '', $buffer);
        // Inject footer right before </body>
        $buffer = str_ireplace('</body>', $footer_html . "\n</body>", $buffer);
    }
    return $buffer;
});

// 1. Initial State Seeding
$user_store_file = __DIR__ . '/data/users.json';
if (file_exists($user_store_file)) {
    $txt = @file_get_contents($user_store_file);
    $decoded = @json_decode($txt, true);
    if (is_array($decoded)) {
        $_SESSION['users'] = $decoded;
    }
}

if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [
        'student@unibook.edu' => [
            'id' => 'U1001',
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'student@unibook.edu',
            'password' => password_hash('pass1234', PASSWORD_DEFAULT),
            'role' => 'user'
        ],
        'staff@unibook.edu' => [
            'id' => 'U1002',
            'name' => 'Jane Smith',
            'phone' => '+1987654321',
            'email' => 'staff@unibook.edu',
            'password' => password_hash('pass1234', PASSWORD_DEFAULT),
            'role' => 'user'
        ]
    ];
    if (!is_dir(__DIR__ . '/data')) {
        @mkdir(__DIR__ . '/data', 0755, true);
    }
    @file_put_contents($user_store_file, json_encode($_SESSION['users'], JSON_PRETTY_PRINT), LOCK_EX);
}

if (!isset($_SESSION['resources'])) {
    $_SESSION['resources'] = [
        'res_1' => [
            'id' => 'res_1',
            'name' => 'Seminar Hall A',
            'category' => 'Facilities',
            'description' => 'Large air-conditioned hall with dual projectors, professional sound system, and seating capacity of 150.',
            'price' => 100.00,
            'image' => 'seminar_hall_a.jpg',
            'status' => 'Available'
        ],
        'res_2' => [
            'id' => 'res_2',
            'name' => 'Seminar Hall B',
            'category' => 'Facilities',
            'description' => 'Modern seminar hall equipped with video conferencing systems and seating capacity of 100.',
            'price' => 120.00,
            'image' => 'seminar_hall_b.jpg',
            'status' => 'Available'
        ],
        'res_3' => [
            'id' => 'res_3',
            'name' => 'Discussion Room',
            'category' => 'Facilities',
            'description' => 'Cozy meeting space with interactive smart-board and LED screen, ideal for group study of up to 8.',
            'price' => 30.00,
            'image' => 'discussion_room.jpg',
            'status' => 'Available'
        ],
        'res_4' => [
            'id' => 'res_4',
            'name' => 'Sports Hall',
            'category' => 'Facilities',
            'description' => 'Multi-purpose indoor sports hall suitable for badminton, basketball, volleyball, and campus-wide events.',
            'price' => 80.00,
            'image' => 'sports_hall.jpg',
            'status' => 'Available'
        ],
        'res_5' => [
            'id' => 'res_5',
            'name' => 'University Bus',
            'category' => 'Vehicles',
            'description' => '44-seater luxury coach with reclining seats and air conditioning, perfect for long-distance study trips.',
            'price' => 150.00,
            'image' => 'university_bus.jpg',
            'status' => 'Available'
        ],
        'res_6' => [
            'id' => 'res_6',
            'name' => 'University Van',
            'category' => 'Vehicles',
            'description' => '12-seater passenger van for local department transport and campus transfers.',
            'price' => 80.00,
            'image' => 'university_van.jpg',
            'status' => 'Available'
        ],
        'res_7' => [
            'id' => 'res_7',
            'name' => 'Photographer',
            'category' => 'Personnel',
            'description' => 'Professional event photography services, including raw image selection and post-production processing.',
            'price' => 50.00,
            'image' => 'photographer.jpg',
            'status' => 'Available'
        ],
        'res_8' => [
            'id' => 'res_8',
            'name' => 'Event Crew',
            'category' => 'Personnel',
            'description' => 'Logistical support crew of 4 student marshals to assist in venue setup, ushering, and coordination.',
            'price' => 40.00,
            'image' => 'event_crew.jpg',
            'status' => 'Available'
        ],
        'res_9' => [
            'id' => 'res_9',
            'name' => 'Technical Support',
            'category' => 'Personnel',
            'description' => 'Dedicated IT/AV technician for setup, live management, and on-site troubleshooting.',
            'price' => 60.00,
            'image' => 'technical_support.jpg',
            'status' => 'Available'
        ],
        'res_10' => [
            'id' => 'res_10',
            'name' => 'Projector',
            'category' => 'Equipment',
            'description' => 'High-brightness laser projector with HDMI inputs and screen casting capabilities.',
            'price' => 20.00,
            'image' => 'projector.jpg',
            'status' => 'Available'
        ],
        'res_11' => [
            'id' => 'res_11',
            'name' => 'Camera',
            'category' => 'Equipment',
            'description' => 'DSLR camera kit (Canon/Sony) with variable standard zoom lens, tripod, and external microphone.',
            'price' => 40.00,
            'image' => 'camera.jpg',
            'status' => 'Available'
        ],
        'res_12' => [
            'id' => 'res_12',
            'name' => 'Audio System',
            'category' => 'Equipment',
            'description' => 'Active speaker system with 2 wireless microphones, audio mixer, and Bluetooth connectivity.',
            'price' => 50.00,
            'image' => 'audio_system.jpg',
            'status' => 'Available'
        ]
    ];
}

if (!isset($_SESSION['bookings'])) {
    $_SESSION['bookings'] = [
        'UB1001' => [
            'booking_id' => 'UB1001',
            'user_id' => 'U1001',
            'user_email' => 'student@unibook.edu',
            'user_name' => 'John Doe',
            'resource_id' => 'res_1',
            'resource_name' => 'Seminar Hall A',
            'category' => 'Facilities',
            'date' => date('Y-m-d', strtotime('+2 days')),
            'slot' => '10:00 AM – 12:00 PM',
            'amount' => 100.00,
            'booking_status' => 'Pending',
            'payment_status' => 'Unpaid',
            'payment_date' => null,
            'receipt_id' => null
        ],
        'UB1002' => [
            'booking_id' => 'UB1002',
            'user_id' => 'U1001',
            'user_email' => 'student@unibook.edu',
            'user_name' => 'John Doe',
            'resource_id' => 'res_6',
            'resource_name' => 'University Van',
            'category' => 'Vehicles',
            'date' => date('Y-m-d', strtotime('+3 days')),
            'slot' => '2:00 PM – 4:00 PM',
            'amount' => 80.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Paid',
            'payment_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'receipt_id' => 'RC98471'
        ],
        'UB1003' => [
            'booking_id' => 'UB1003',
            'user_id' => 'U1002',
            'user_email' => 'staff@unibook.edu',
            'user_name' => 'Jane Smith',
            'resource_id' => 'res_7',
            'resource_name' => 'Photographer',
            'category' => 'Personnel',
            'date' => date('Y-m-d', strtotime('+1 day')),
            'slot' => '8:00 AM – 10:00 AM',
            'amount' => 50.00,
            'booking_status' => 'Completed',
            'payment_status' => 'Paid',
            'payment_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'receipt_id' => 'RC98122'
        ]
    ];
}

// 2. Authentication Helper Functions
function check_auth($role = 'user') {
    if (!isset($_SESSION['role'])) {
        header("Location: " . get_base_url() . "login.php");
        exit();
    }

    $role_name = $_SESSION['role'] ?? '';

    if ($role === 'admin' && $role_name !== 'admin') {
        header("Location: " . get_base_url() . "login.php");
        exit();
    }

    if ($role === 'manager' && $role_name !== 'manager') {
        header("Location: " . get_base_url() . "login.php");
        exit();
    }

    if ($role === 'user' && !in_array($role_name, ['user', 'manager'], true)) {
        header("Location: " . get_base_url() . "login.php");
        exit();
    }
}

function get_manager_scope_by_email($email = null) {
    $email = strtolower($email ?? ($_SESSION['email'] ?? ''));
    
    $conn = db_get_connection();
    if ($conn) {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $res = mysqli_query($conn, "
            SELECT c.category_name 
            FROM users u 
            LEFT JOIN categories c ON u.category_id = c.category_id 
            WHERE LOWER(u.email) = '$email_safe' AND u.role_id = 3
        ");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            if (!empty($row['category_name'])) {
                return $row['category_name'];
            }
        }
    }

    $map = [
        'facilitiesmanager@unibook.com' => 'Facilities',
        'transportmanager@unibook.com' => 'Vehicles',
        'ictmanager@unibook.com' => 'Equipment',
        'hrmanager@unibook.com' => 'Personnel',
    ];
    return $map[$email] ?? null;
}

function get_manager_display_labels($scope = null) {
    $scope = $scope ?? ($_SESSION['manager_scope'] ?? get_manager_scope_by_email($_SESSION['email'] ?? ''));

    $email = strtolower($_SESSION['email'] ?? '');
    $db_dept = null;
    $db_cat_id = null;
    $db_cat_name = null;
    
    $conn = db_get_connection();
    if ($conn && !empty($email)) {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $res = mysqli_query($conn, "
            SELECT u.department, u.category_id, c.category_name 
            FROM users u 
            LEFT JOIN categories c ON u.category_id = c.category_id 
            WHERE LOWER(u.email) = '$email_safe'
        ");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $db_dept = $row['department'];
            $db_cat_id = $row['category_id'];
            $db_cat_name = $row['category_name'];
        }
        
        // Auto-update database for existing/pre-seeded managers if they are null
        if (empty($db_dept) || empty($db_cat_id)) {
            $map_categories = [
                'facilitiesmanager@unibook.com' => ['dept' => 'Facilities Department', 'cat_id' => 1],
                'transportmanager@unibook.com' => ['dept' => 'Transport Department', 'cat_id' => 2],
                'ictmanager@unibook.com' => ['dept' => 'ICT Department', 'cat_id' => 4],
                'hrmanager@unibook.com' => ['dept' => 'HR Department', 'cat_id' => 3],
            ];
            if (isset($map_categories[$email])) {
                $info = $map_categories[$email];
                $up_dept = mysqli_real_escape_string($conn, $info['dept']);
                $up_cat = intval($info['cat_id']);
                mysqli_query($conn, "UPDATE users SET department = '$up_dept', category_id = $up_cat WHERE LOWER(email) = '$email_safe'");
                $db_dept = $info['dept'];
                $db_cat_id = $info['cat_id'];
                
                // Fetch the category name
                $cat_res = mysqli_query($conn, "SELECT category_name FROM categories WHERE category_id = $up_cat");
                if ($cat_res && $cat_row = mysqli_fetch_assoc($cat_res)) {
                    $db_cat_name = $cat_row['category_name'];
                }
            }
        }
    }

    $map = [
        'Facilities' => ['department' => 'Facilities Department', 'role' => 'Facilities Manager'],
        'Vehicles' => ['department' => 'Transport Department', 'role' => 'Vehicle Manager'],
        'Equipment' => ['department' => 'ICT Department', 'role' => 'Equipment Manager'],
        'Personnel' => ['department' => 'HR Department', 'role' => 'Personnel Manager'],
    ];

    if ($scope && isset($map[$scope])) {
        $label = $map[$scope];
        if (!empty($db_dept)) {
            $label['department'] = $db_dept;
        }
        if (!empty($db_cat_name)) {
            $label['role'] = $db_cat_name . ' Manager';
        }
        return $label;
    }

    return [
        'department' => !empty($db_dept) ? $db_dept : ($scope ? $scope . ' Department' : 'Department'),
        'role' => !empty($db_cat_name) ? $db_cat_name . ' Manager' : ($scope ? $scope . ' Manager' : 'Manager'),
    ];
}

function set_manager_scope_from_session() {
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'manager') {
        $_SESSION['manager_scope'] = get_manager_scope_by_email($_SESSION['email'] ?? '');
    }
    return $_SESSION['manager_scope'] ?? null;
}

// Get the base url of the application dynamically
function get_base_url() {
    $script = $_SERVER['SCRIPT_NAME'];
    $normalized = str_replace('\\', '/', dirname($script));
    $path = str_replace(['/user', '/admin'], '', $normalized);
    $base = rtrim($path, '/\\') . '/';
    return $base;
}

// Get correct navigation link paths dynamically
function url($relative_path) {
    return get_base_url() . ltrim($relative_path, '/');
}

// Format numbers as currency
function format_currency($val) {
    return 'RM ' . number_format($val, 2);
}

// Alert helper functions
function set_flash_message($type, $message) {
    $_SESSION['flash_msg'] = ['type' => $type, 'text' => $message];
}

function display_flash_message() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        $class = '';
        if ($msg['type'] === 'success') $class = 'alert ok';
        if ($msg['type'] === 'error') $class = 'alert err';
        if ($msg['type'] === 'warning') $class = 'alert warn';
        return '<div class="' . $class . '" style="display:block; margin-bottom: 20px;">' . htmlspecialchars($msg['text']) . '</div>';
    }
    return '';
}

if (file_exists(__DIR__ . '/db_config.php')) {
    require_once __DIR__ . '/db_config.php';
}

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'unibook');

function db_get_connection() {
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }
    if (!function_exists('mysqli_connect')) {
        return null;
    }
    $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn || mysqli_connect_errno()) {
        $conn = null;
        return null;
    }
    mysqli_set_charset($conn, 'utf8mb4');
    mysqli_query($conn, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Schema migration: Add uploaded_by column to resources if it doesn't exist
    $cols_res = mysqli_query($conn, "SHOW COLUMNS FROM resources LIKE 'uploaded_by'");
    if ($cols_res && mysqli_num_rows($cols_res) == 0) {
        mysqli_query($conn, "ALTER TABLE resources ADD COLUMN uploaded_by INT DEFAULT NULL");
        mysqli_query($conn, "UPDATE resources SET uploaded_by = 1005 WHERE category_id = 1"); // Facilities Manager
        mysqli_query($conn, "UPDATE resources SET uploaded_by = 1006 WHERE category_id = 2"); // Transport Manager
        mysqli_query($conn, "UPDATE resources SET uploaded_by = 1008 WHERE category_id = 3"); // HR Manager
        mysqli_query($conn, "UPDATE resources SET uploaded_by = 1007 WHERE category_id = 4"); // ICT Manager
    }

    return $conn;
}

function db_query_scalar($sql, $default = 0) {
    $conn = db_get_connection();
    if (!$conn) {
        return $default;
    }
    $result = @mysqli_query($conn, $sql);
    if (!$result) {
        return $default;
    }
    $row = mysqli_fetch_row($result);
    mysqli_free_result($result);
    return $row ? $row[0] : $default;
}

function db_query_assoc_list($sql) {
    $conn = db_get_connection();
    if (!$conn) {
        return [];
    }
    $result = @mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);
    return $rows;
}

function db_table_columns($table) {
    static $cache = [];
    $table_key = strtolower($table);
    if (isset($cache[$table_key])) return $cache[$table_key];

    $conn = db_get_connection();
    if (!$conn) return [];

    $db_safe = mysqli_real_escape_string($conn, DB_NAME);
    $table_safe = mysqli_real_escape_string($conn, $table);
    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$db_safe' AND TABLE_NAME = '$table_safe'";
    $rows = db_query_assoc_list($sql);
    $cols = [];
    foreach ($rows as $r) {
        if (!empty($r['COLUMN_NAME'])) $cols[] = $r['COLUMN_NAME'];
    }
    $cache[$table_key] = $cols;
    return $cols;
}

function get_mysql_total_users_count() {
    return (int) db_query_scalar("SELECT COUNT(*) FROM users");
}

function get_mysql_total_resources_count() {
    return (int) db_query_scalar("SELECT COUNT(*) FROM resources");
}

function get_mysql_total_bookings_count() {
    return (int) db_query_scalar("SELECT COUNT(*) FROM bookings");
}

function get_mysql_booking_counts_by_status($status) {
    $conn = db_get_connection();
    if (!$conn) {
        return 0;
    }
    $status_safe = mysqli_real_escape_string($conn, $status);
    return (int) db_query_scalar("SELECT COUNT(*) FROM bookings WHERE booking_status = '$status_safe'");
}

function get_mysql_total_revenue() {
    return (float) db_query_scalar("SELECT IFNULL(SUM(amount), 0) FROM bookings WHERE payment_status = 'Paid'");
}

function get_mysql_booking_category_counts() {
    $counts = [
        'Facilities' => 0,
        'Vehicles' => 0,
        'Personnel' => 0,
        'Equipment' => 0
    ];
    $rows = db_query_assoc_list("SELECT category, COUNT(*) AS total FROM bookings GROUP BY category");
    foreach ($rows as $row) {
        if (!empty($row['category']) && isset($counts[$row['category']])) {
            $counts[$row['category']] = intval($row['total']);
        }
    }
    return $counts;
}

function get_mysql_monthly_bookings($months = 6) {
    $conn = db_get_connection();
    if (!$conn) {
        return [];
    }
    $months = max(1, intval($months));
    $rows = db_query_assoc_list("SELECT DATE_FORMAT(date, '%b %Y') AS month_label, COUNT(*) AS total FROM bookings WHERE date >= DATE_SUB(CURDATE(), INTERVAL $months MONTH) GROUP BY YEAR(date), MONTH(date) ORDER BY YEAR(date), MONTH(date)");
    $buckets = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $dt = new DateTime("first day of -{$i} months");
        $buckets[$dt->format('M Y')] = 0;
    }
    foreach ($rows as $row) {
        $label = $row['month_label'] ?? '';
        if ($label !== '') {
            $buckets[$label] = intval($row['total']);
        }
    }
    return $buckets;
}

function get_mysql_pending_bookings($limit = 5) {
    $conn = db_get_connection();
    if (!$conn) {
        return [];
    }
    $limit = max(1, intval($limit));
    $rows = db_query_assoc_list("SELECT booking_id, user_name, user_email, resource_name, date, slot, amount FROM bookings WHERE booking_status = 'Pending' ORDER BY date ASC, booking_id ASC LIMIT $limit");
    $bookings = [];
    foreach ($rows as $row) {
        if (!empty($row['booking_id'])) {
            $bookings[$row['booking_id']] = [
                'booking_id' => $row['booking_id'],
                'user_name' => $row['user_name'],
                'user_email' => $row['user_email'],
                'resource_name' => $row['resource_name'],
                'date' => $row['date'],
                'slot' => $row['slot'],
                'amount' => floatval($row['amount'])
            ];
        }
    }
    return $bookings;
}

function get_pending_bookings_list($limit = 5) {
    if (db_get_connection()) {
        return get_mysql_pending_bookings($limit);
    }
    $pending_bookings_list = [];
    foreach (get_all_bookings() as $booking_id => $booking) {
        if ($booking['booking_status'] === 'Pending') {
            $pending_bookings_list[$booking_id] = $booking;
        }
    }
    ksort($pending_bookings_list);
    return array_slice($pending_bookings_list, 0, $limit, true);
}

function get_monthly_bookings_chart($months = 6) {
    if (db_get_connection()) {
        return get_mysql_monthly_bookings($months);
    }
    $months = max(1, intval($months));
    $buckets = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $dt = new DateTime("first day of -{$i} months");
        $buckets[$dt->format('M Y')] = 0;
    }
    foreach (get_all_bookings() as $booking) {
        if (empty($booking['date'])) {
            continue;
        }
        $timestamp = strtotime($booking['date']);
        if ($timestamp === false) {
            continue;
        }
        $label = date('M Y', $timestamp);
        if (isset($buckets[$label])) {
            $buckets[$label]++;
        }
    }
    return $buckets;
}

function get_booking_category_counts_fallback() {
    return get_booking_category_counts();
}

function get_total_users_count_fallback() {
    return get_total_users_count();
}

function get_total_bookings_count_fallback() {
    return get_total_bookings_count();
}

function get_total_resources_count_fallback() {
    return count(get_all_resources());
}

function get_total_revenue_fallback() {
    return get_total_revenue();
}

function get_booking_counts_by_status_fallback($status) {
    return get_booking_counts_by_status($status);
}

function get_booking_category_counts_current() {
    if (db_get_connection()) {
        return get_mysql_booking_category_counts();
    }
    return get_booking_category_counts();
}

function get_total_users_count_current() {
    if (db_get_connection()) {
        return get_mysql_total_users_count();
    }
    return get_total_users_count_fallback();
}

function get_total_resources_count_current() {
    if (db_get_connection()) {
        return get_mysql_total_resources_count();
    }
    return get_total_resources_count_fallback();
}

function get_total_bookings_count_current() {
    if (db_get_connection()) {
        return get_mysql_total_bookings_count();
    }
    return get_total_bookings_count_fallback();
}

function get_total_revenue_current() {
    if (db_get_connection()) {
        return get_mysql_total_revenue();
    }
    return get_total_revenue_fallback();
}

function get_booking_counts_by_status_current($status) {
    if (db_get_connection()) {
        return get_mysql_booking_counts_by_status($status);
    }
    return get_booking_counts_by_status_fallback($status);
}

function get_monthly_bookings_current($months = 6) {
    return get_monthly_bookings_chart($months);
}

function get_recent_pending_bookings($limit = 5) {
    return get_pending_bookings_list($limit);
}

function get_booking_category_counts_present() {
    return get_booking_category_counts_current();
}

function get_resource_count_present() {
    return get_total_resources_count_current();
}

function get_user_count_present() {
    return get_total_users_count_current();
}

function get_booking_count_present() {
    return get_total_bookings_count_current();
}

function get_revenue_present() {
    return get_total_revenue_current();
}

function get_pending_count_present() {
    return get_booking_counts_by_status_current('Pending');
}

function get_revenue_by_resource_present() {
    $conn = db_get_connection();
    $current_month = date('m');
    $current_year = date('Y');
    
    if ($conn) {
        $rows = db_query_assoc_list("
            SELECT c.category_name AS name, SUM(b.amount) AS revenue 
            FROM bookings_data b 
            INNER JOIN resources r ON b.resource_id = r.resource_id 
            INNER JOIN categories c ON r.category_id = c.category_id 
            WHERE b.payment_status = 'Paid' 
              AND MONTH(b.booking_date) = $current_month
              AND YEAR(b.booking_date) = $current_year
            GROUP BY c.category_id 
            ORDER BY revenue DESC
        ");
        
        // Initialize all 4 categories to 0
        $categories_res = mysqli_query($conn, "SELECT category_name FROM categories");
        $revenue = [];
        if ($categories_res) {
            while ($row = mysqli_fetch_row($categories_res)) {
                $revenue[$row[0]] = 0.0;
            }
        } else {
            $revenue = ['Facilities' => 0.0, 'Vehicles' => 0.0, 'Personnel' => 0.0, 'Equipment' => 0.0];
        }
        
        foreach ($rows as $row) {
            $revenue[$row['name']] = floatval($row['revenue']);
        }
        
        arsort($revenue);
        return $revenue;
    }
    
    // Fallback using session bookings
    $revenue = [
        'Facilities' => 0.0,
        'Vehicles' => 0.0,
        'Personnel' => 0.0,
        'Equipment' => 0.0
    ];
    
    foreach (get_all_bookings() as $bk) {
        if ($bk['payment_status'] === 'Paid' && !empty($bk['date'])) {
            $bk_month = date('m', strtotime($bk['date']));
            $bk_year = date('Y', strtotime($bk['date']));
            
            if ($bk_month === $current_month && $bk_year === $current_year) {
                $cat = $bk['category'];
                if (isset($revenue[$cat])) {
                    $revenue[$cat] += floatval($bk['amount']);
                }
            }
        }
    }
    
    arsort($revenue);
    return $revenue;
}

// --- STATISTICS & REPORTS FUNCTIONS ---

function get_user_store_file() {
    return __DIR__ . '/data/users.json';
}

function get_user_store() {
    $file = get_user_store_file();
    if (!file_exists($file)) {
        return [];
    }
    $txt = @file_get_contents($file);
    $decoded = @json_decode($txt, true);
    return is_array($decoded) ? $decoded : [];
}

function save_user_store(array $users) {
    $file = get_user_store_file();
    if (!is_dir(dirname($file))) {
        @mkdir(dirname($file), 0755, true);
    }
    $payload = json_encode($users, JSON_PRETTY_PRINT);
    if ($payload === false) {
        return false;
    }
    return @file_put_contents($file, $payload, LOCK_EX) !== false;
}

// --- USER FUNCTIONS ---
function db_get_role_id_by_name($conn, $role_name) {
    $role_name_safe = mysqli_real_escape_string($conn, $role_name);
    $res = mysqli_query($conn, "SELECT role_id FROM roles WHERE role_name = '$role_name_safe'");
    if ($res && $row = mysqli_fetch_row($res)) {
        return intval($row[0]);
    }
    return 2; // Default to 'user' role
}

function get_total_users_count() {
    return count($_SESSION['users'] ?? []);
}

function get_all_users() {
    $conn = db_get_connection();
    if ($conn) {
        $rows = db_query_assoc_list(" 
            SELECT u.user_id, u.name, u.phone, u.email, r.role_name AS role
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            ORDER BY u.name ASC
        ");

        if (!empty($rows)) {
            return array_map(function ($row) {
                return [
                    'id' => intval($row['user_id']),
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'role' => $row['role']
                ];
            }, $rows);
        }
    }

    $users = [];
    foreach ($_SESSION['users'] ?? [] as $user) {
        $users[] = [
            'id' => $user['id'] ?? '',
            'name' => $user['name'] ?? '',
            'phone' => $user['phone'] ?? '',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'user'
        ];
    }

    usort($users, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
    });

    return $users;
}

function delete_user_by_id($user_id) {
    $conn = db_get_connection();
    if ($conn) {
        $user_id_safe = intval($user_id);
        return mysqli_query($conn, "DELETE FROM users WHERE user_id = $user_id_safe") !== false;
    }

    $email = null;
    foreach ($_SESSION['users'] ?? [] as $key => $user) {
        if (($user['id'] ?? '') == $user_id) {
            $email = $key;
            break;
        }
    }

    if ($email !== null) {
        unset($_SESSION['users'][$email]);
        save_user_store($_SESSION['users']);
        return true;
    }

    return false;
}

function get_user_by_email($email) {
    $email = strtolower($email);
    $conn = db_get_connection();
    if ($conn) {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $rows = db_query_assoc_list("
            SELECT u.user_id, u.name, u.phone, u.email, u.password, r.role_name AS role 
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE LOWER(u.email) = '$email_safe'
        ");
        if (!empty($rows)) {
            $row = $rows[0];
            return [
                'id' => 'U' . $row['user_id'],
                'name' => $row['name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'password' => $row['password'],
                'role' => $row['role']
            ];
        }
        return null;
    }
    return $_SESSION['users'][$email] ?? null;
}

function get_user_by_id($id) {
    $conn = db_get_connection();
    if ($conn) {
        $num_id = intval(str_replace('U', '', $id));
        $rows = db_query_assoc_list("
            SELECT u.user_id, u.name, u.phone, u.email, u.password, r.role_name AS role 
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = $num_id
        ");
        if (!empty($rows)) {
            $row = $rows[0];
            return [
                'id' => 'U' . $row['user_id'],
                'name' => $row['name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'password' => $row['password'],
                'role' => $row['role']
            ];
        }
        return null;
    }
    
    if (empty($_SESSION['users'])) {
        return null;
    }
    foreach ($_SESSION['users'] as $user) {
        if (isset($user['id']) && $user['id'] === $id) {
            return $user;
        }
    }
    return null;
}

function register_user($name, $phone, $email, $password) {
    $email = strtolower($email);
    
    $conn = db_get_connection();
    if ($conn) {
        if (get_user_by_email($email)) {
            return false;
        }
        
        $role_id = db_get_role_id_by_name($conn, 'user');
        $name_safe = mysqli_real_escape_string($conn, $name);
        $phone_safe = !empty($phone) ? "'" . mysqli_real_escape_string($conn, $phone) . "'" : "NULL";
        $email_safe = mysqli_real_escape_string($conn, $email);
        $pass_hash = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_DEFAULT));
        
        $sql = "INSERT INTO users (name, phone, email, password, role_id) 
                VALUES ('$name_safe', $phone_safe, '$email_safe', '$pass_hash', $role_id)";
        return mysqli_query($conn, $sql) !== false;
    }
    
    if (isset($_SESSION['users'][$email])) {
        return false;
    }

    $next_id_num = 1001;
    if (!empty($_SESSION['users'])) {
        $existing_ids = array_map(function ($user) {
            return intval(substr($user['id'] ?? 'U1000', 1));
        }, $_SESSION['users']);
        $next_id_num = max($existing_ids) + 1;
    }

    $_SESSION['users'][$email] = [
        'id' => 'U' . $next_id_num,
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user'
    ];

    save_user_store($_SESSION['users']);
    return true;
}

function update_user_profile($email, $name, $phone) {
    $email = strtolower($email);
    $conn = db_get_connection();
    if ($conn) {
        $name_safe = mysqli_real_escape_string($conn, $name);
        $phone_safe = !empty($phone) ? "'" . mysqli_real_escape_string($conn, $phone) . "'" : "NULL";
        $email_safe = mysqli_real_escape_string($conn, $email);
        
        $sql = "UPDATE users SET name = '$name_safe', phone = $phone_safe WHERE LOWER(email) = '$email_safe'";
        return mysqli_query($conn, $sql) !== false;
    }
    if (isset($_SESSION['users'][$email])) {
        $_SESSION['users'][$email]['name'] = $name;
        $_SESSION['users'][$email]['phone'] = $phone;
        save_user_store($_SESSION['users']);
        return true;
    }
    return false;
}

function update_user_password($email, $new_password) {
    $email = strtolower($email);
    $conn = db_get_connection();
    if ($conn) {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $pass_hash = mysqli_real_escape_string($conn, password_hash($new_password, PASSWORD_DEFAULT));
        
        $sql = "UPDATE users SET password = '$pass_hash' WHERE LOWER(email) = '$email_safe'";
        return mysqli_query($conn, $sql) !== false;
    }
    if (isset($_SESSION['users'][$email])) {
        $_SESSION['users'][$email]['password'] = password_hash($new_password, PASSWORD_DEFAULT);
        save_user_store($_SESSION['users']);
        return true;
    }
    return false;
}

// --- RESOURCE FUNCTIONS ---
function db_get_category_id_by_name($conn, $category_name) {
    $category_name_safe = mysqli_real_escape_string($conn, $category_name);
    $res = mysqli_query($conn, "SELECT category_id FROM categories WHERE category_name = '$category_name_safe'");
    if ($res && $row = mysqli_fetch_row($res)) {
        return intval($row[0]);
    }
    mysqli_query($conn, "INSERT INTO categories (category_name) VALUES ('$category_name_safe')");
    return mysqli_insert_id($conn);
}

function get_all_resources() {
    $conn = db_get_connection();
    if ($conn) {
        $rows = db_query_assoc_list("
            SELECT r.resource_id, r.name, c.category_name AS category, r.description, r.price, r.image, r.status, r.pickup_address, r.uploaded_by 
            FROM resources r
            INNER JOIN categories c ON r.category_id = c.category_id
            ORDER BY r.resource_id ASC
        ");
        $resources = [];
        foreach ($rows as $row) {
            $key = 'res_' . $row['resource_id'];
            $resources[$key] = [
                'id' => $key,
                'name' => $row['name'],
                'category' => $row['category'],
                'description' => $row['description'],
                'price' => floatval($row['price']),
                'image' => $row['image'],
                'status' => $row['status'],
                'pickup_address' => $row['pickup_address'],
                'uploaded_by' => $row['uploaded_by'] !== null ? 'U' . $row['uploaded_by'] : null
            ];
        }
        return $resources;
    }
    return $_SESSION['resources'] ?? [];
}

function get_resource_by_id($id) {
    $conn = db_get_connection();
    if ($conn) {
        $num_id = intval(str_replace('res_', '', $id));
        $rows = db_query_assoc_list("
            SELECT r.resource_id, r.name, c.category_name AS category, r.description, r.price, r.image, r.status, r.pickup_address, r.uploaded_by 
            FROM resources r
            INNER JOIN categories c ON r.category_id = c.category_id
            WHERE r.resource_id = $num_id
        ");
        if (!empty($rows)) {
            $row = $rows[0];
            return [
                'id' => 'res_' . $row['resource_id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'description' => $row['description'],
                'price' => floatval($row['price']),
                'image' => $row['image'],
                'status' => $row['status'],
                'pickup_address' => $row['pickup_address'],
                'uploaded_by' => $row['uploaded_by'] !== null ? 'U' . $row['uploaded_by'] : null
            ];
        }
        return null;
    }
    return $_SESSION['resources'][$id] ?? null;
}

function add_resource($name, $category, $description, $price, $image, $pickup_address, $uploaded_by = null) {
    $conn = db_get_connection();
    if ($conn) {
        $category_id = db_get_category_id_by_name($conn, $category);
        $name_safe = mysqli_real_escape_string($conn, $name);
        $desc_safe = mysqli_real_escape_string($conn, $description);
        $price_safe = floatval($price);
        $image_safe = mysqli_real_escape_string($conn, $image);
        $pickup_safe = mysqli_real_escape_string($conn, $pickup_address);
        
        $uploaded_by_val = "NULL";
        if ($uploaded_by !== null) {
            $uploaded_by_val = intval(str_replace('U', '', $uploaded_by));
        } elseif (isset($_SESSION['user_id'])) {
            $uploaded_by_val = intval(str_replace('U', '', $_SESSION['user_id']));
        }
        
        $sql = "INSERT INTO resources (category_id, name, description, price, image, status, pickup_address, uploaded_by) 
                VALUES ($category_id, '$name_safe', '$desc_safe', $price_safe, '$image_safe', 'Available', '$pickup_safe', $uploaded_by_val)";
        if (mysqli_query($conn, $sql)) {
            $new_id = mysqli_insert_id($conn);
            return 'res_' . $new_id;
        }
        return false;
    }
    
    $next_res_num = 1;
    if (!empty($_SESSION['resources'])) {
        $ids = array_map(function($key) {
            return intval(substr($key, 4));
        }, array_keys($_SESSION['resources']));
        $next_res_num = max($ids) + 1;
    }
    $new_key = 'res_' . $next_res_num;
    $_SESSION['resources'][$new_key] = [
        'id' => $new_key,
        'name' => $name,
        'category' => $category,
        'description' => $description,
        'price' => floatval($price),
        'image' => $image,
        'status' => 'Available',
        'pickup_address' => $pickup_address
    ];
    return $new_key;
}

function edit_resource($id, $name, $category, $description, $price, $status, $pickup_address) {
    $conn = db_get_connection();
    if ($conn) {
        $num_id = intval(str_replace('res_', '', $id));
        $category_id = db_get_category_id_by_name($conn, $category);
        $name_safe = mysqli_real_escape_string($conn, $name);
        $desc_safe = mysqli_real_escape_string($conn, $description);
        $price_safe = floatval($price);
        $status_safe = mysqli_real_escape_string($conn, $status);
        $pickup_safe = mysqli_real_escape_string($conn, $pickup_address);
        
        $sql = "UPDATE resources 
                SET category_id = $category_id, name = '$name_safe', description = '$desc_safe', price = $price_safe, status = '$status_safe', pickup_address = '$pickup_safe' 
                WHERE resource_id = $num_id";
        return mysqli_query($conn, $sql);
    }
    if (isset($_SESSION['resources'][$id])) {
        $_SESSION['resources'][$id]['name'] = $name;
        $_SESSION['resources'][$id]['category'] = $category;
        $_SESSION['resources'][$id]['description'] = $description;
        $_SESSION['resources'][$id]['price'] = floatval($price);
        $_SESSION['resources'][$id]['status'] = $status;
        $_SESSION['resources'][$id]['pickup_address'] = $pickup_address;
        return true;
    }
    return false;
}

function delete_resource($id) {
    $conn = db_get_connection();
    if ($conn) {
        $num_id = intval(str_replace('res_', '', $id));
        $sql = "DELETE FROM resources WHERE resource_id = $num_id";
        return mysqli_query($conn, $sql);
    }
    if (isset($_SESSION['resources'][$id])) {
        unset($_SESSION['resources'][$id]);
        return true;
    }
    return false;
}

// --- BOOKING FUNCTIONS ---
function get_shared_bookings() {
    $shared_file = __DIR__ . '/data/bookings.json';
    $shared = [];
    if (file_exists($shared_file)) {
        $txt = @file_get_contents($shared_file);
        $decoded = @json_decode($txt, true);
        if (is_array($decoded)) {
            $shared = $decoded;
        }
    }
    return $shared;
}

function save_shared_bookings(array $bookings) {
    $shared_file = __DIR__ . '/data/bookings.json';
    $payload = json_encode($bookings, JSON_PRETTY_PRINT);
    if ($payload === false) {
        return false;
    }
    return @file_put_contents($shared_file, $payload, LOCK_EX) !== false;
}

function get_all_bookings() {
    $conn = db_get_connection();
    if ($conn) {
        $rows = db_query_assoc_list("SELECT * FROM bookings ORDER BY booking_id ASC");
        $bookings = [];
        foreach ($rows as $row) {
            $bookings[$row['booking_id']] = [
                'booking_id' => $row['booking_id'],
                'user_id' => $row['user_id'],
                'user_email' => $row['user_email'],
                'user_name' => $row['user_name'],
                'resource_id' => $row['resource_id'],
                'resource_name' => $row['resource_name'],
                'category' => $row['category'],
                'date' => $row['date'],
                'slot' => $row['slot'],
                'booking_purpose' => isset($row['booking_purpose']) ? $row['booking_purpose'] : null,
                'amount' => floatval($row['amount']),
                'booking_status' => $row['booking_status'],
                'payment_method' => isset($row['payment_method']) ? $row['payment_method'] : null,
                'payment_bank' => isset($row['payment_bank']) ? $row['payment_bank'] : null,
                'transaction_reference' => isset($row['transaction_reference']) ? $row['transaction_reference'] : null,
                'payment_status' => $row['payment_status'],
                'payment_date' => $row['payment_date'],
                'receipt_id' => $row['receipt_id'],
                'pickup_address' => isset($row['pickup_address']) ? $row['pickup_address'] : null
            ];
        }
        // If pickup_address is missing from the view (nullable seed data), fill from resource record
        foreach ($bookings as $bid => $bk) {
            if (empty($bk['pickup_address'])) {
                $res = get_resource_by_id($bk['resource_id'] ?? '');
                $bookings[$bid]['pickup_address'] = $res['pickup_address'] ?? null;
            }
        }
        return $bookings;
    }
    
    $shared = get_shared_bookings();
    $session_bookings = $_SESSION['bookings'] ?? [];
    // Enrich session-backed bookings with pickup_address from related resource when missing
    if (!empty($session_bookings)) {
        foreach ($session_bookings as $bid => $sbk) {
            if (!isset($sbk['pickup_address']) || $sbk['pickup_address'] === null) {
                $res = get_resource_by_id($sbk['resource_id'] ?? '');
                $session_bookings[$bid]['pickup_address'] = $res['pickup_address'] ?? null;
            }
        }
    }
    // Enrich shared bookings loaded from JSON with pickup_address when missing
    if (!empty($shared)) {
        foreach ($shared as $bid => $sbk) {
            if (!isset($sbk['pickup_address']) || $sbk['pickup_address'] === null) {
                $res = get_resource_by_id($sbk['resource_id'] ?? '');
                $shared[$bid]['pickup_address'] = $res['pickup_address'] ?? null;
            }
        }
    }
    return array_replace($shared, $session_bookings);
}

function get_bookings_by_user($user_id) {
    $conn = db_get_connection();
    if ($conn) {
        $user_id_safe = mysqli_real_escape_string($conn, $user_id);
        $num_id = intval(str_replace('U', '', $user_id_safe));
        $rows = db_query_assoc_list("SELECT * FROM bookings WHERE user_id = 'U$num_id' ORDER BY booking_id ASC");
        $bookings = [];
        foreach ($rows as $row) {
            $bookings[$row['booking_id']] = [
                'booking_id' => $row['booking_id'],
                'user_id' => $row['user_id'],
                'user_email' => $row['user_email'],
                'user_name' => $row['user_name'],
                'resource_id' => $row['resource_id'],
                'resource_name' => $row['resource_name'],
                'category' => $row['category'],
                'date' => $row['date'],
                'slot' => $row['slot'],
                'booking_purpose' => isset($row['booking_purpose']) ? $row['booking_purpose'] : null,
                'amount' => floatval($row['amount']),
                'booking_status' => $row['booking_status'],
                'payment_method' => isset($row['payment_method']) ? $row['payment_method'] : null,
                'payment_bank' => isset($row['payment_bank']) ? $row['payment_bank'] : null,
                'transaction_reference' => isset($row['transaction_reference']) ? $row['transaction_reference'] : null,
                'payment_status' => $row['payment_status'],
                'payment_date' => $row['payment_date'],
                'receipt_id' => $row['receipt_id'],
                'pickup_address' => isset($row['pickup_address']) ? $row['pickup_address'] : null
            ];
        }
        // Fill missing pickup_address from resource for DB-backed user bookings
        foreach ($bookings as $bid => $bk) {
            if (empty($bk['pickup_address'])) {
                $res = get_resource_by_id($bk['resource_id'] ?? '');
                $bookings[$bid]['pickup_address'] = $res['pickup_address'] ?? null;
            }
        }
        return $bookings;
    }
    
    $user_email = '';
    $user = get_user_by_id($user_id);
    if ($user) {
        $user_email = strtolower($user['email']);
    }
    $user_bookings = [];
    foreach (get_all_bookings() as $booking_id => $booking) {
        if (isset($booking['user_id']) && $booking['user_id'] === $user_id) {
            $user_bookings[$booking_id] = $booking;
        } elseif (!isset($booking['user_id']) && $user_email !== '' && strtolower($booking['user_email'] ?? '') === $user_email) {
            $user_bookings[$booking_id] = $booking;
        }
    }
    return $user_bookings;
}

function get_booking_by_id($id) {
    $conn = db_get_connection();
    if ($conn) {
        $id_safe = mysqli_real_escape_string($conn, $id);
        $rows = db_query_assoc_list("SELECT * FROM bookings WHERE booking_id = '$id_safe'");
        if (!empty($rows)) {
            $row = $rows[0];
            return [
                'booking_id' => $row['booking_id'],
                'user_id' => $row['user_id'],
                'user_email' => $row['user_email'],
                'user_name' => $row['user_name'],
                'resource_id' => $row['resource_id'],
                'resource_name' => $row['resource_name'],
                'category' => $row['category'],
                'date' => $row['date'],
                'slot' => $row['slot'],
                'booking_purpose' => isset($row['booking_purpose']) ? $row['booking_purpose'] : null,
                'amount' => floatval($row['amount']),
                'booking_status' => $row['booking_status'],
                'payment_method' => isset($row['payment_method']) ? $row['payment_method'] : null,
                'payment_bank' => isset($row['payment_bank']) ? $row['payment_bank'] : null,
                'transaction_reference' => isset($row['transaction_reference']) ? $row['transaction_reference'] : null,
                'payment_status' => $row['payment_status'],
                'payment_date' => $row['payment_date'],
                'receipt_id' => $row['receipt_id'],
                'pickup_address' => isset($row['pickup_address']) ? $row['pickup_address'] : null
            ];
        }
        return null;
    }
    $bookings = get_all_bookings();
    return $bookings[$id] ?? null;
}

function get_occupied_dates_for_slot($start_date, $slot) {
    $days = 1;
    if ($slot === '2 Days') $days = 2;
    elseif ($slot === '3 Days') $days = 3;
    elseif ($slot === '4 Days') $days = 4;
    elseif ($slot === '5 Days') $days = 5;
    
    $dates = [];
    for ($i = 0; $i < $days; $i++) {
        $dates[] = date('Y-m-d', strtotime("$start_date +$i days"));
    }
    return $dates;
}

function check_booking_conflict($resource_id, $new_date, $new_slot) {
    $new_dates = get_occupied_dates_for_slot($new_date, $new_slot);
    
    $hourly_slots = [
        "8:00 AM – 10:00 AM",
        "10:00 AM – 12:00 PM",
        "2:00 PM – 4:00 PM",
        "4:00 PM – 6:00 PM"
    ];
    $is_new_hourly = in_array($new_slot, $hourly_slots);
    
    $conn = db_get_connection();
    if ($conn) {
        $res_num_id = intval(str_replace('res_', '', $resource_id));
        $sql = "SELECT booking_date, booking_slot FROM bookings_data 
                WHERE resource_id = $res_num_id 
                  AND booking_status NOT IN ('Rejected', 'Declined')";
        $existing_bookings = db_query_assoc_list($sql);
        foreach ($existing_bookings as $bk) {
            $ex_date = $bk['booking_date'];
            $ex_slot = $bk['booking_slot'];
            $is_ex_hourly = in_array($ex_slot, $hourly_slots);
            
            if ($is_new_hourly && $is_ex_hourly) {
                if ($ex_date === $new_date && $ex_slot === $new_slot) {
                    return true;
                }
            } else {
                $ex_dates = get_occupied_dates_for_slot($ex_date, $ex_slot);
                $overlap = array_intersect($new_dates, $ex_dates);
                if (!empty($overlap)) {
                    return true;
                }
            }
        }
    } else {
        foreach (get_all_bookings() as $existing) {
            if ($existing['resource_id'] === $resource_id && 
                $existing['booking_status'] !== 'Rejected' &&
                $existing['booking_status'] !== 'Declined') {
                
                $ex_date = $existing['date'];
                $ex_slot = $existing['slot'];
                $is_ex_hourly = in_array($ex_slot, $hourly_slots);
                
                if ($is_new_hourly && $is_ex_hourly) {
                    if ($ex_date === $new_date && $ex_slot === $new_slot) {
                        return true;
                    }
                } else {
                    $ex_dates = get_occupied_dates_for_slot($ex_date, $ex_slot);
                    $overlap = array_intersect($new_dates, $ex_dates);
                    if (!empty($overlap)) {
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

function create_booking($user_id, $user_name, $resource_id, $date, $slot, $purpose = '') {
    $resource = get_resource_by_id($resource_id);
    if (!$resource) {
        return false;
    }

    $user = get_user_by_id($user_id);
    if (!$user) {
        return false;
    }

    if (check_booking_conflict($resource_id, $date, $slot)) {
        return 'double_booked';
    }

    $days = 1;
    if ($slot === '2 Days') $days = 2;
    elseif ($slot === '3 Days') $days = 3;
    elseif ($slot === '4 Days') $days = 4;
    elseif ($slot === '5 Days') $days = 5;
    $amount = floatval($resource['price']) * $days;

    $conn = db_get_connection();
    if ($conn) {
        $res_num_id = intval(str_replace('res_', '', $resource_id));
        $date_safe = mysqli_real_escape_string($conn, $date);
        $slot_safe = mysqli_real_escape_string($conn, $slot);
        $purpose_safe = mysqli_real_escape_string($conn, $purpose);
        $user_num_id = intval(str_replace('U', '', $user_id));
        
        $sql = "INSERT INTO bookings_data (user_id, resource_id, booking_date, booking_slot, booking_purpose, amount, booking_status, payment_status) 
                VALUES ($user_num_id, $res_num_id, '$date_safe', '$slot_safe', '$purpose_safe', $amount, 'Pending', 'Unpaid')";
        
        if (mysqli_query($conn, $sql)) {
            $new_id = mysqli_insert_id($conn);
            return 'UB' . $new_id;
        }
        return false;
    }
    
    // Generate Booking ID
    $next_id_num = 1001;
    $bookings = get_all_bookings();
    if (!empty($bookings)) {
        $ids = array_map(function($key) {
            return intval(substr($key, 2));
        }, array_keys($bookings));
        $next_id_num = max($ids) + 1;
    }
    $booking_id = 'UB' . $next_id_num;

    $_SESSION['bookings'][$booking_id] = [
        'booking_id' => $booking_id,
        'user_id' => $user_id,
        'user_email' => strtolower($user['email']),
        'user_name' => $user_name ?: $user['name'],
        'resource_id' => $resource_id,
        'resource_name' => $resource['name'],
        'category' => $resource['category'],
        'date' => $date,
        'slot' => $slot,
        'booking_purpose' => $purpose,
        'amount' => $amount,
        'booking_status' => 'Pending',
        'payment_method' => null,
        'payment_bank' => null,
        'transaction_reference' => null,
        'payment_status' => 'Unpaid',
        'payment_date' => null,
        'receipt_id' => null
    ];
    // Ensure pickup_address is stored for session-backed bookings
    $_SESSION['bookings'][$booking_id]['pickup_address'] = $resource['pickup_address'] ?? null;

    return $booking_id;
}

function update_booking_status($id, $status) {
    $db_updated = false;
    
    // Try to update database first
    $conn = db_get_connection();
    if ($conn) {
        // Extract numeric ID from 'UB####' format
        $num_id = intval(str_replace('UB', '', $id));
        $status_safe = mysqli_real_escape_string($conn, $status);
        $sql = "UPDATE bookings_data SET booking_status = '$status_safe' WHERE booking_id = $num_id";
        $result = @mysqli_query($conn, $sql);
        $db_updated = ($result !== false && mysqli_affected_rows($conn) > 0);
    }
    
    // Always update session storage as fallback or mirror
    $session_updated = false;
    
    // Ensure the booking exists in session storage
    if (!isset($_SESSION['bookings'][$id])) {
        $shared = get_shared_bookings();
        if (isset($shared[$id])) {
            $_SESSION['bookings'][$id] = $shared[$id];
        }
    }

    if (isset($_SESSION['bookings'][$id])) {
        $_SESSION['bookings'][$id]['booking_status'] = $status;
        $session_updated = true;
    }

    if ($session_updated) {
        $shared = get_shared_bookings();
        $shared[$id] = $_SESSION['bookings'][$id];
        save_shared_bookings($shared);
    }

    return $db_updated || $session_updated;
}

function save_booking_payment($id, array $data) {
    if (empty($id)) {
        return false;
    }

    $booking = get_booking_by_id($id);
    if (!$booking) {
        return false;
    }

    $current_status = strtolower(trim($booking['payment_status'] ?? ''));
    $new_status = isset($data['payment_status']) ? strtolower(trim($data['payment_status'])) : $current_status;
    if ($current_status === 'paid' && $new_status === 'paid' && isset($data['payment_method']) && strtolower($booking['payment_method'] ?? '') === strtolower($data['payment_method'])) {
        return false; // prevent duplicate paid updates
    }

    $fields = [];
    $params = [];
    foreach (['payment_method', 'payment_status', 'payment_bank', 'transaction_reference', 'payment_date', 'receipt_id'] as $key) {
        if (array_key_exists($key, $data)) {
            $fields[$key] = $data[$key] === null ? null : $data[$key];
        }
    }

    $db_updated = false;
    $conn = db_get_connection();
    if ($conn && !empty($fields)) {
        $num_id = intval(str_replace('UB', '', $id));

        // Only attempt to update columns that actually exist in the DB table.
        $available = db_table_columns('bookings_data');
        if (!empty($available)) {
            $allowed = array_flip($available);
            $fields = array_intersect_key($fields, $allowed);
        }

        if (!empty($fields)) {
            $sets = [];
            foreach ($fields as $column => $value) {
                if ($value === null) {
                    $sets[] = "$column = NULL";
                } else {
                    $escaped = mysqli_real_escape_string($conn, $value);
                    $sets[] = "$column = '$escaped'";
                }
            }
            $sql = 'UPDATE bookings_data SET ' . implode(', ', $sets) . " WHERE booking_id = $num_id";
            $result = @mysqli_query($conn, $sql);
            $db_updated = ($result !== false && mysqli_affected_rows($conn) >= 0);
        }
    }

    $session_updated = false;
    if (!isset($_SESSION['bookings'][$id])) {
        $shared = get_shared_bookings();
        if (isset($shared[$id])) {
            $_SESSION['bookings'][$id] = $shared[$id];
        }
    }

    if (isset($_SESSION['bookings'][$id])) {
        foreach ($fields as $column => $value) {
            $_SESSION['bookings'][$id][$column] = $value;
        }
        $session_updated = true;
    }

    if ($session_updated) {
        $shared = get_shared_bookings();
        $shared[$id] = $_SESSION['bookings'][$id];
        save_shared_bookings($shared);
    }

    return $db_updated || $session_updated;
}

function pay_booking($id, $method = 'Card', $payment_bank = null, $transaction_reference = null) {
    $booking = get_booking_by_id($id);
    if (!$booking) {
        return false;
    }

    if (strtolower(trim($booking['payment_status'] ?? '')) === 'paid') {
        return false;
    }

    $pay_date = date('Y-m-d H:i:s');
    $receipt_id = 'RC' . rand(10000, 99999);
    return save_booking_payment($id, [
        'payment_method' => $method,
        'payment_status' => 'Paid',
        'payment_bank' => $payment_bank,
        'transaction_reference' => $transaction_reference,
        'payment_date' => $pay_date,
        'receipt_id' => $receipt_id
    ]);
}

// Cash payment feature removed. Related helpers were removed to disable cash flows.

function cancel_booking($id) {
    $booking = get_booking_by_id($id);
    if (!$booking) {
        return false;
    }

    // Allow cancellation if Pending/Pending Review, or if Paid (and not already Cancelled/Completed)
    $can_cancel = false;
    if ($booking['booking_status'] === 'Pending' || $booking['booking_status'] === 'Pending Review') {
        $can_cancel = true;
    } elseif ($booking['payment_status'] === 'Paid' && $booking['booking_status'] !== 'Cancelled' && $booking['booking_status'] !== 'Completed') {
        $can_cancel = true;
    }

    if (!$can_cancel) {
        return false;
    }

    return update_booking_status($id, 'Cancelled');
}

function request_refund($id) {
    $booking = get_booking_by_id($id);
    if (!$booking) {
        return false;
    }
    // Only allow requesting refund if booking is Cancelled and payment is Paid
    if ($booking['booking_status'] === 'Cancelled' && $booking['payment_status'] === 'Paid') {
        return save_booking_payment($id, ['payment_status' => 'Refund Requested']);
    }
    return false;
}

function approve_refund($id) {
    $booking = get_booking_by_id($id);
    if (!$booking) {
        return false;
    }
    // Allow approving refund if payment_status is 'Refund Requested', or if booking is cancelled and paid
    if ($booking['payment_status'] === 'Refund Requested' || ($booking['booking_status'] === 'Cancelled' && $booking['payment_status'] === 'Paid')) {
        return save_booking_payment($id, ['payment_status' => 'Refunded']);
    }
    return false;
}


function update_booking_details($id, $date, $slot) {
    $booking = get_booking_by_id($id);
    if (!$booking) {
        return false;
    }

    // Only allow edits if status is Pending Review or Pending
    if ($booking['booking_status'] !== 'Pending Review' && $booking['booking_status'] !== 'Pending') {
        return false;
    }

    // Check for conflicts with the new date/slot
    if (check_booking_conflict($booking['resource_id'], $date, $slot)) {
        return 'double_booked';
    }

    // Calculate new amount based on slot duration
    $resource = get_resource_by_id($booking['resource_id']);
    if (!$resource) {
        return false;
    }

    $days = 1;
    if ($slot === '2 Days') $days = 2;
    elseif ($slot === '3 Days') $days = 3;
    elseif ($slot === '4 Days') $days = 4;
    elseif ($slot === '5 Days') $days = 5;
    $new_amount = floatval($resource['price']) * $days;

    // Update session first
    if (!isset($_SESSION['bookings'][$id])) {
        $shared = get_shared_bookings();
        if (isset($shared[$id])) {
            $_SESSION['bookings'][$id] = $shared[$id];
        }
    }

    if (isset($_SESSION['bookings'][$id])) {
        $_SESSION['bookings'][$id]['date'] = $date;
        $_SESSION['bookings'][$id]['slot'] = $slot;
        $_SESSION['bookings'][$id]['amount'] = $new_amount;
    }

    // Update database if available
    $conn = db_get_connection();
    if ($conn) {
        $num_id = intval(str_replace('UB', '', $id));
        $date_safe = mysqli_real_escape_string($conn, $date);
        $slot_safe = mysqli_real_escape_string($conn, $slot);
        $sql = "UPDATE bookings_data SET booking_date = '$date_safe', booking_slot = '$slot_safe', amount = $new_amount WHERE booking_id = $num_id";
        @mysqli_query($conn, $sql);
    }

    // Save to shared JSON
    $shared = get_shared_bookings();
    if (isset($_SESSION['bookings'][$id])) {
        $shared[$id] = $_SESSION['bookings'][$id];
        save_shared_bookings($shared);
    }

    return true;
}

function is_booking_owned_by_user($booking, $user_id, $user_email) {
    if (!$booking) {
        return false;
    }
    return (isset($booking['user_id']) && $booking['user_id'] === $user_id) || strtolower($booking['user_email'] ?? '') === strtolower($user_email);
}

// --- STATISTICS & REPORTS FUNCTIONS ---
function get_total_bookings_count() {
    return count(get_all_bookings());
}

function get_booking_counts_by_status($status) {
    $count = 0;
    foreach (get_all_bookings() as $bk) {
        if ($bk['booking_status'] === $status) {
            $count++;
        }
    }
    return $count;
}

function get_paid_bookings_count() {
    $count = 0;
    foreach (get_all_bookings() as $bk) {
        if ($bk['payment_status'] === 'Paid') {
            $count++;
        }
    }
    return $count;
}

function get_unpaid_bookings_count() {
    $count = 0;
    foreach (get_all_bookings() as $bk) {
        if ($bk['payment_status'] === 'Unpaid') {
            $count++;
        }
    }
    return $count;
}

function get_total_revenue() {
    $revenue = 0.0;
    foreach (get_all_bookings() as $bk) {
        if ($bk['payment_status'] === 'Paid') {
            $revenue += floatval($bk['amount']);
        }
    }
    return $revenue;
}

function get_booking_category_counts() {
    $cat_counts = ['Facilities' => 0, 'Vehicles' => 0, 'Personnel' => 0, 'Equipment' => 0];
    foreach (get_all_bookings() as $bk) {
        $cat = $bk['category'];
        if (isset($cat_counts[$cat])) {
            $cat_counts[$cat]++;
        }
    }
    return $cat_counts;
}

function get_most_booked_resource_by_category($category) {
    $usage = [];
    foreach (get_all_bookings() as $bk) {
        if ($bk['category'] === $category) {
            $name = $bk['resource_name'];
            if (!isset($usage[$name])) {
                $usage[$name] = 0;
            }
            $usage[$name]++;
        }
    }

    if (empty($usage)) {
        return ['name' => 'None', 'count' => 0];
    }

    arsort($usage);
    $top_name = array_key_first($usage);
    return ['name' => $top_name, 'count' => $usage[$top_name]];
}

function get_icon($name) {
    $icons = [
        'dashboard' => '
            <svg viewBox="0 0 24 24">
              <rect x="3" y="3" width="7" height="7" rx="1"></rect>
              <rect x="14" y="3" width="7" height="7" rx="1"></rect>
              <rect x="3" y="14" width="7" height="7" rx="1"></rect>
              <rect x="14" y="14" width="7" height="7" rx="1"></rect>
            </svg>',
        'add-booking' => '
            <svg viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="16"></line>
              <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>',
        'my-bookings' => '
            <svg viewBox="0 0 24 24">
              <line x1="8" y1="6" x2="21" y2="6"></line>
              <line x1="8" y1="12" x2="21" y2="12"></line>
              <line x1="8" y1="18" x2="21" y2="18"></line>
              <line x1="3" y1="6" x2="3.01" y2="6"></line>
              <line x1="3" y1="12" x2="3.01" y2="12"></line>
              <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>',
        'bookings' => '
            <svg viewBox="0 0 24 24">
              <rect x="3" y="4" width="18" height="18" rx="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>',
        'resources' => '
            <svg viewBox="0 0 24 24">
              <path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"></path>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            </svg>',
        'payments' => '
            <svg viewBox="0 0 24 24">
              <rect x="2" y="5" width="20" height="14" rx="2"></rect>
              <line x1="2" y1="10" x2="22" y2="10"></line>
              <line x1="6" y1="15" x2="10" y2="15"></line>
            </svg>',
        'reports' => '
            <svg viewBox="0 0 24 24">
              <line x1="4" y1="20" x2="20" y2="20"></line>
              <rect x="5" y="11" width="3" height="9" rx="1"></rect>
              <rect x="11" y="7" width="3" height="13" rx="1"></rect>
              <rect x="17" y="3" width="3" height="17" rx="1"></rect>
            </svg>',
        'users' => '
            <svg viewBox="0 0 24 24">
              <circle cx="9" cy="8" r="3"></circle>
              <circle cx="17" cy="9" r="2"></circle>
              <path d="M4 19c0-3 2-5 5-5s5 2 5 5"></path>
              <path d="M15 19c0-2 1.5-3.5 4-3.5"></path>
            </svg>',
        'settings' => '
            <svg viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.2a1.6 1.6 0 0 0-1-1.5 1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.2a1.6 1.6 0 0 0 1.5-1 1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3 1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.2a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8 1.6 1.6 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.2a1.6 1.6 0 0 0-1.4 1z"></path>
            </svg>',
        'profile' => '
            <svg viewBox="0 0 24 24">
              <circle cx="12" cy="8" r="4"></circle>
              <path d="M4 20c0-4 3-7 8-7s8 3 8 7"></path>
            </svg>',
        'logout' => '
            <svg viewBox="0 0 24 24">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <line x1="16" y1="17" x2="21" y2="12"></line>
              <line x1="21" y1="12" x2="16" y2="7"></line>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>'
    ];
    return $icons[$name] ?? '';
}
?>
