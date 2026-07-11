<?php
// user/toyyibpay_redirect.php
// Server-side ToyyibPay Sandbox Integration using cURL
require_once '../config.php';
check_auth('user');

// ToyyibPay Sandbox Credentials
define('TOYYIBPAY_CATEGORY_CODE', '9cuthhyc');
define('TOYYIBPAY_SECRET_KEY', 'zyygxm66-w0f9-i2dv-epwi-5ouy2n0pj5ll');
define('TOYYIBPAY_API_URL', 'https://dev.toyyibpay.com/index.php/api/createBill');
define('TOYYIBPAY_PAYMENT_URL', 'https://dev.toyyibpay.com');

// Get booking parameters
$booking_id = $_GET['booking_id'] ?? '';
$bank = $_GET['bank'] ?? '';

$error = '';
$booking = null;

// Validate booking and user authorization
if (!empty($booking_id)) {
    $booking = get_booking_by_id($booking_id);
    $user = get_user_by_email($_SESSION['email']);
    $user_email = $_SESSION['email'];
    $user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);

    if (!$booking) {
        $error = 'Booking not found.';
    } elseif (!((isset($booking['user_id']) && $booking['user_id'] === $user_id) || strtolower($booking['user_email']) === strtolower($user_email))) {
        $error = 'Unauthorized access to this booking.';
    } elseif ($booking['booking_status'] !== 'Approved') {
        $error = 'Booking must be approved before payment.';
    } elseif ($booking['payment_status'] !== 'Unpaid') {
        $error = 'Booking is already paid or pending verification.';
    }
}

if (empty($booking_id)) {
    $error = 'Missing booking ID.';
}

// If validation passed, create bill via ToyyibPay API
if (empty($error) && $booking) {
    // Prepare API request data
    $billName = 'Booking ' . $booking['booking_id'];
    $billDescription = 'Payment for ' . $booking['resource_name'] . ' on ' . $booking['date'];
    
    // ToyyibPay expects billAmount in cents/sen (e.g. RM 10.00 is sent as 1000)
    $billAmountCents = (int)round(floatval($booking['amount']) * 100);
    
    // Debug: log the amount being sent
    error_log("ToyyibPay: Creating bill for {$booking['booking_id']}, amount={$booking['amount']}, cents={$billAmountCents}");
    
    // Build absolute return URL so ToyyibPay redirects back to your localhost, not dev.toyyibpay
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $billReturnUrl = $protocol . '://' . $host . url('user/toyyibpay_return.php') . '?booking_id=' . urlencode($booking_id);

    // Get payor details from user profile or fall back to defaults
    $payorName = !empty($user['name']) ? $user['name'] : 'Guest Customer';
    $payorEmail = !empty($user['email']) ? $user['email'] : 'guest@example.com';
    
    // Validate and format phone number for ToyyibPay (must be valid Malaysia format or similar)
    $payorPhone = '0123456789'; // Default fallback
    if (!empty($user['phone'])) {
        // Remove any spaces, dashes, or special characters; keep only digits
        $cleanPhone = preg_replace('/[^0-9+]/', '', $user['phone']);
        // If it starts with +60, convert to 0-prefix for Malaysia
        if (strpos($cleanPhone, '+60') === 0) {
            $cleanPhone = '0' . substr($cleanPhone, 3);
        }
        // Only use if it's a reasonable length (10-15 digits)
        if (strlen($cleanPhone) >= 10 && strlen($cleanPhone) <= 15) {
            $payorPhone = $cleanPhone;
        }
    }

    // Create bill via ToyyibPay API using cURL
    $postData = [
        'userSecretKey' => TOYYIBPAY_SECRET_KEY,
        'categoryCode' => TOYYIBPAY_CATEGORY_CODE,
        'billName' => $billName,
        'billDescription' => $billDescription,
        'billPriceSetting' => 0, // 0 = Fixed price amount, 1 = Open amount
        'billPayorInfo' => 1,    // 1 = Show/require payor info, 0 = Hide/optional
        'billAmount' => $billAmountCents,
        'billReturnUrl' => $billReturnUrl,
        'billTo' => $payorName,
        'billEmail' => $payorEmail,
        'billPhone' => $payorPhone
    ];

    // Optional: add bank selection if provided
    if (!empty($bank)) {
        $postData['fpxBank'] = $bank;
    }

    // Call ToyyibPay createBill API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, TOYYIBPAY_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // OK for sandbox testing
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Debug: log API response
    error_log("ToyyibPay API Response: HTTP=$httpCode, Error=$curlError, Response=" . substr($response, 0, 200));

    // Parse API response
    if ($curlError) {
        $error = 'Connection error: ' . htmlspecialchars($curlError);
    } elseif ($httpCode !== 200) {
        $error = 'API returned HTTP ' . intval($httpCode) . '. Response: ' . htmlspecialchars(substr($response, 0, 500));
    } else {
        // Decode JSON response into associative array and object
        $result = @json_decode($response, true);
        $resultObj = @json_decode($response);

        // Extract bill code from known response shapes
        $billCode = null;
        if (is_array($result) && !empty($result[0]['BillCode'])) {
            $billCode = $result[0]['BillCode'];
        } elseif (is_array($result) && !empty($result[0]['billCode'])) {
            $billCode = $result[0]['billCode'];
        } elseif (is_array($result) && !empty($result['BillCode'])) {
            $billCode = $result['BillCode'];
        } elseif (is_array($result) && !empty($result['billCode'])) {
            $billCode = $result['billCode'];
        } elseif ($resultObj && isset($resultObj->BillCode)) {
            $billCode = $resultObj->BillCode;
        } elseif ($resultObj && isset($resultObj->billCode)) {
            $billCode = $resultObj->billCode;
        }

        if (!empty($billCode)) {
            // Normalize the payment URL and redirect to ToyyibPay sandbox page
            $redirectUrl = rtrim(TOYYIBPAY_PAYMENT_URL, '/') . '/' . ltrim($billCode, '/');
            header('Location: ' . $redirectUrl);
            exit();
        }

        // Show detailed error for debugging
        if (is_array($result) && isset($result['msg'])) {
            $error = 'ToyyibPay Error: ' . htmlspecialchars($result['msg']);
        } elseif (is_array($result) && isset($result['message'])) {
            $error = 'Payment gateway error: ' . htmlspecialchars($result['message']);
        } elseif (!empty($response)) {
            $error = 'Failed to create payment bill. Response: ' . htmlspecialchars(substr($response, 0, 500));
        } else {
            $error = 'Failed to create payment bill. Please check your Category Code and Secret Key.';
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ToyyibPay Redirect – Uni.Book</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=2.5">
</head>
<body class="sidebar-closed">
  <header class="dashboard-header">
    <div class="header-left">
      <button class="toggle-btn" onclick="window.history.back();" aria-label="Go Back">← Back</button>
    </div>
    <div class="header-center">
      <a href="<?php echo url('index.php'); ?>" class="brand">
        <div class="brand-logo"><span class="uni">Uni</span><span class="dot"></span><span class="book">Book</span></div>
      </a>
    </div>
  </header>

  <div class="dashboard-layout">
    <main class="dashboard-main" style="padding: 40px;">
      <?php if (!empty($error)): ?>
        <div class="dashboard-panel" style="max-width: 600px; margin: 0 auto;">
          <h2 class="panel-title">⚠️ Payment Error</h2>
          <div class="alert err" style="display: block; margin-bottom: 20px; word-wrap: break-word; white-space: pre-wrap; font-family: monospace; font-size: 0.85rem;">
            <?php echo htmlspecialchars($error); ?>
          </div>
          <p style="margin-bottom: 16px; color: var(--muted);">
            Debug: Please check the error details above and contact support if the issue persists.
          </p>
          <a href="<?php echo url('user/payments.php'); ?>" class="btn-main">← Return to Payments</a>
        </div>
      <?php else: ?>
        <div style="text-align: center; padding: 40px;">
          <div style="font-size: 24px; margin-bottom: 12px;">⏳</div>
          <h2>Redirecting to ToyyibPay...</h2>
          <p style="color: var(--muted); margin-top: 12px;">Please wait while we prepare your payment. If you are not redirected within 5 seconds, <a href="<?php echo url('user/payments.php'); ?>" style="color: var(--teal);">click here</a>.</p>
        </div>
      <?php endif; ?>
    </main>
  </div>

</body>
</html>


