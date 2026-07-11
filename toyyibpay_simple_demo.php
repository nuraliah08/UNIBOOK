<?php
// toyyibpay_simple_demo.php
// Standalone ToyyibPay Sandbox Integration Example.
// Demonstrates: Form UI + API createBill request via cURL + Payment Redirect + Return handler.

// ToyyibPay Sandbox Credentials
define('TOYYIBPAY_SECRET_KEY', 'zyygxm66-w0f9-i2dv-epwi-5ouy2n0pj5ll');
define('TOYYIBPAY_CATEGORY_CODE', '9cuthhyc');
define('TOYYIBPAY_API_URL', 'https://dev.toyyibpay.com/index.php/api/createBill');
define('TOYYIBPAY_REDIRECT_URL', 'https://dev.toyyibpay.com');

$error = '';
$statusData = null;

// 1. Handle GET parameters returned from ToyyibPay redirect
if (isset($_GET['status_id'])) {
    $statusData = [
        'status_id' => $_GET['status_id'],
        'billcode' => $_GET['billcode'] ?? 'N/A',
        'transaction_id' => $_GET['transaction_id'] ?? 'N/A',
        'msg' => $_GET['msg'] ?? '',
        'order_id' => $_GET['order_id'] ?? 'N/A'
    ];
}

// 2. Handle POST form submission to create a bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['status_id'])) {
    $name = trim($_POST['billTo'] ?? '');
    $email = trim($_POST['billEmail'] ?? '');
    $phone = trim($_POST['billPhone'] ?? '');
    $amount = trim($_POST['billAmount'] ?? '');
    $billName = trim($_POST['billName'] ?? 'Uni.Book Demo Payment');
    $billDesc = trim($_POST['billDescription'] ?? 'Payment Integration Demo');

    // Basic Validation
    if (empty($name) || empty($email) || empty($phone) || empty($amount)) {
        $error = 'Please fill in all required fields (Name, Email, Phone, Amount).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!is_numeric($amount) || floatval($amount) <= 0) {
        $error = 'Please enter a valid positive payment amount.';
    } else {
        // A. Convert RM to cents/sen (e.g. RM 10.00 is sent as 1000)
        $amountInCents = (int)round(floatval($amount) * 100);

        // B. Generate dynamic return URL pointing back to this script
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $returnUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

        // C. Prepare ToyyibPay API Payload
        $postData = [
            'userSecretKey' => TOYYIBPAY_SECRET_KEY,
            'categoryCode' => TOYYIBPAY_CATEGORY_CODE,
            'billName' => $billName,
            'billDescription' => $billDesc,
            'billPriceSetting' => 0, // 0 = Fixed Amount, 1 = Open Amount (payer enters value)
            'billPayorInfo' => 1,    // 1 = Show/require payor info, 0 = Optional
            'billAmount' => $amountInCents,
            'billReturnUrl' => $returnUrl,
            'billTo' => $name,
            'billEmail' => $email,
            'billPhone' => $phone
        ];

        // D. Send POST request via cURL to Sandbox API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, TOYYIBPAY_API_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Recommended only for development environments
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $error = 'cURL connection error: ' . htmlspecialchars($curlError);
        } elseif ($httpCode !== 200) {
            $error = 'API server returned HTTP ' . intval($httpCode) . '. Response: ' . htmlspecialchars($response);
        } else {
            // E. Decode and verify API JSON response
            $result = @json_decode($response, true);

            // ToyyibPay returns a JSON array: [{"BillCode": "xxx"}]
            if (is_array($result) && !empty($result[0]['BillCode'])) {
                $billCode = $result[0]['BillCode'];

                // F. Redirect user to Sandbox payment page
                $gatewayUrl = TOYYIBPAY_REDIRECT_URL . '/' . $billCode;
                header('Location: ' . $gatewayUrl);
                exit();
            } else {
                if (is_array($result) && isset($result['msg'])) {
                    $error = 'ToyyibPay Error: ' . htmlspecialchars($result['msg']);
                } elseif (is_array($result) && isset($result['message'])) {
                    $error = 'Gateway Message: ' . htmlspecialchars($result['message']);
                } elseif (!empty($response)) {
                    $error = 'Failed to create bill. Response: ' . htmlspecialchars(substr($response, 0, 500));
                } else {
                    $error = 'Failed to create bill. Please check your Secret Key and Category Code.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ToyyibPay Sandbox Payment Demo</title>
  
  <!-- Modern Typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg-gradient-start: #0f172a;
      --bg-gradient-end: #1e1b4b;
      --card-bg: rgba(30, 41, 59, 0.7);
      --card-border: rgba(255, 255, 255, 0.08);
      --accent: #6366f1;
      --accent-hover: #4f46e5;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --success: #10b981;
      --warning: #f59e0b;
      --error: #ef4444;
      --font-stack: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: var(--font-stack);
      background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 24px;
    }

    .container {
      width: 100%;
      max-width: 580px;
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      backdrop-filter: blur(16px);
      border-radius: 24px;
      padding: 32px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    header {
      text-align: center;
      margin-bottom: 28px;
    }

    header h1 {
      font-size: 2.2rem;
      font-weight: 700;
      background: linear-gradient(to right, #a5b4fc, #818cf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 8px;
    }

    header p {
      font-size: 0.95rem;
      color: var(--text-muted);
    }

    .alert {
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-size: 0.95rem;
      line-height: 1.5;
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }

    .alert-danger {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #fca5a5;
    }

    .alert-success {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #a7f3d0;
    }

    .alert-warning {
      background: rgba(245, 158, 11, 0.15);
      border: 1px solid rgba(245, 158, 11, 0.3);
      color: #fde68a;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    label {
      display: block;
      font-size: 0.85rem;
      font-weight: 500;
      color: var(--text-muted);
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    input, textarea {
      width: 100%;
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 12px 16px;
      font-family: var(--font-stack);
      font-size: 0.95rem;
      color: var(--text-main);
      outline: none;
      transition: all 0.2s ease-in-out;
    }

    input:focus, textarea:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
      background: rgba(15, 23, 42, 0.8);
    }

    textarea {
      resize: vertical;
      min-height: 80px;
    }

    .btn {
      display: inline-flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 14px 24px;
      font-family: var(--font-stack);
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
      margin-top: 10px;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .btn:hover {
      background: var(--accent-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    }

    .btn:active {
      transform: translateY(1px);
    }

    .status-card {
      text-align: center;
      padding: 12px 0;
    }

    .status-icon {
      font-size: 3rem;
      margin-bottom: 16px;
    }

    .status-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .details-table {
      width: 100%;
      margin: 24px 0;
      border-collapse: collapse;
      font-size: 0.9rem;
      text-align: left;
      background: rgba(15, 23, 42, 0.4);
      border-radius: 12px;
      overflow: hidden;
    }

    .details-table th, .details-table td {
      padding: 12px 16px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .details-table th {
      color: var(--text-muted);
      font-weight: 500;
      width: 40%;
    }

    .details-table td {
      color: var(--text-main);
      font-family: monospace;
      font-size: 0.95rem;
    }

    .details-table tr:last-child th, .details-table tr:last-child td {
      border-bottom: none;
    }

    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .badge-success {
      background: rgba(16, 185, 129, 0.2);
      color: #34d399;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .badge-warning {
      background: rgba(245, 158, 11, 0.2);
      color: #fbbf24;
      border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .badge-danger {
      background: rgba(239, 68, 68, 0.2);
      color: #fca5a5;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .footer-note {
      text-align: center;
      margin-top: 24px;
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    .footer-note a {
      color: var(--accent);
      text-decoration: none;
    }

    .footer-note a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="container">
  <?php if ($statusData !== null): ?>
    <!-- Display return status from ToyyibPay sandbox redirect -->
    <div class="status-card">
      <?php if ($statusData['status_id'] === '1'): ?>
        <div class="status-icon">âœ…</div>
        <div class="status-title" style="color: var(--success);">Payment Successful</div>
        <p style="color: var(--text-muted);">Thank you! Your Sandbox transaction has been completed.</p>
      <?php elseif ($statusData['status_id'] === '2'): ?>
        <div class="status-icon">â³</div>
        <div class="status-title" style="color: var(--warning);">Payment Pending</div>
        <p style="color: var(--text-muted);">Your transaction is currently pending verification.</p>
      <?php else: ?>
        <div class="status-icon">âŒ</div>
        <div class="status-title" style="color: var(--error);">Payment Failed / Cancelled</div>
        <p style="color: var(--text-muted);">The transaction was unsuccessful or cancelled by the user.</p>
      <?php endif; ?>

      <table class="details-table">
        <tr>
          <th>Status ID</th>
          <td>
            <?php if ($statusData['status_id'] === '1'): ?>
              <span class="badge badge-success">1 (Success)</span>
            <?php elseif ($statusData['status_id'] === '2'): ?>
              <span class="badge badge-warning">2 (Pending)</span>
            <?php else: ?>
              <span class="badge badge-danger"><?php echo htmlspecialchars($statusData['status_id']); ?> (Failed/Cancelled)</span>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th>Transaction ID</th>
          <td><?php echo htmlspecialchars($statusData['transaction_id']); ?></td>
        </tr>
        <tr>
          <th>Bill Code</th>
          <td><?php echo htmlspecialchars($statusData['billcode']); ?></td>
        </tr>
        <tr>
          <th>Message</th>
          <td><?php echo htmlspecialchars($statusData['msg'] ?: 'N/A'); ?></td>
        </tr>
      </table>

      <a href="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" class="btn">Try Another Payment</a>
    </div>

  <?php else: ?>
    <!-- Standard Billing Form -->
    <header>
      <h1>ToyyibPay Sandbox</h1>
      <p>Simple PHP cURL Integration Example</p>
    </header>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <strong>Error:</strong> <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
      <div class="form-group">
        <label for="billTo">Full Name</label>
        <input type="text" id="billTo" name="billTo" value="John Doe" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="billEmail">Email Address</label>
          <input type="email" id="billEmail" name="billEmail" value="john@example.com" required>
        </div>
        <div class="form-group">
          <label for="billPhone">Phone Number</label>
          <input type="text" id="billPhone" name="billPhone" value="0123456789" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="billAmount">Amount (RM)</label>
          <input type="number" id="billAmount" name="billAmount" step="0.01" value="10.00" required>
        </div>
        <div class="form-group">
          <label for="billName">Bill Name</label>
          <input type="text" id="billName" name="billName" value="Uni.Book Demo Payment" required>
        </div>
      </div>

      <div class="form-group">
        <label for="billDescription">Bill Description</label>
        <textarea id="billDescription" name="billDescription" required>Payment integration test in sandbox mode.</textarea>
      </div>

      <button type="submit" class="btn">Pay with ToyyibPay Sandbox</button>
    </form>
  <?php endif; ?>

  <div class="footer-note">
    <p>Using Sandbox Category Code: <code>9cuthhyc</code></p>
    <p>Powered by <a href="https://dev.toyyibpay.com" target="_blank" rel="noopener">ToyyibPay Developer Staging</a></p>
  </div>
</div>


</body>
</html>


