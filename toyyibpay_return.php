<?php
// user/toyyibpay_return.php
// Simple simulated ToyyibPay FPX return handler for local testing.
require_once __DIR__ . '/../config.php';

// Accept either GET redirect or POST callback simulation
// Accept parameters from ToyyibPay redirect or local simulator form
$booking_id = $_REQUEST['booking_id'] ?? '';
$bank = $_REQUEST['bank'] ?? ($_REQUEST['fpx_bank'] ?? '');
$status_id = $_GET['status_id'] ?? null;
$transaction_id = $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? '';

// 1. Handle Real ToyyibPay Sandbox Redirect Return
if ($status_id !== null) {
    if (empty($booking_id)) {
        set_flash_message('error', 'Missing booking reference.');
        header('Location: payments.php');
        exit();
    }

    if ($status_id === '1') {
        // Successful payment
        $txref = !empty($transaction_id) ? $transaction_id : 'TOYYIB-' . rand(100000, 999999);
        $ok = pay_booking($booking_id, 'ToyyibPay', $bank ?: 'FPX', $txref);
        if ($ok) {
            set_flash_message('success', 'ToyyibPay payment recorded successfully.');
            header('Location: payments.php?receipt_booking_id=' . urlencode($booking_id));
        } else {
            set_flash_message('error', 'Unable to record ToyyibPay payment.');
            header('Location: payments.php');
        }
    } elseif ($status_id === '2') {
        // Pending payment
        set_flash_message('warning', 'ToyyibPay payment is pending verification.');
        header('Location: payments.php');
    } else {
        // Failed/Cancelled payment (status_id = 3)
        $msg = $_GET['msg'] ?? 'Transaction failed or was cancelled.';
        set_flash_message('error', 'ToyyibPay Payment Failed: ' . htmlspecialchars($msg));
        header('Location: payments.php');
    }
    exit();
}

// 2. Handle local simulated confirmation form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['confirm'])) {
    if (empty($booking_id)) {
        set_flash_message('error', 'Missing booking reference.');
        header('Location: payments.php');
        exit();
    }

    // Simulate transaction reference from gateway
    $txref = !empty($transaction_id) ? $transaction_id : 'TOYYIB-' . rand(100000, 999999);

    // Mark booking as paid via ToyyibPay
    $ok = pay_booking($booking_id, 'ToyyibPay', $bank ?: 'FPX', $txref);
    if ($ok) {
        set_flash_message('success', 'ToyyibPay payment recorded successfully (Simulated).');
        header('Location: payments.php?receipt_booking_id=' . urlencode($booking_id));
        exit();
    } else {
        set_flash_message('error', 'Unable to record ToyyibPay payment.');
        header('Location: payments.php');
        exit();
    }
}

// Show a tiny confirmation page for local testing
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ToyyibPay Simulator</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=2.5">
</head>
<body class="sidebar-closed">
  <main style="max-width:720px;margin:40px auto;padding:24px;">
    <h1>ToyyibPay FPX Simulator</h1>
    <p>This page simulates the FPX return flow for local testing.</p>
    <dl>
      <dt>Booking:</dt>
      <dd><?php echo htmlspecialchars($booking_id); ?></dd>
      <dt>Bank:</dt>
      <dd><?php echo htmlspecialchars($bank ?: 'â€”'); ?></dd>
    </dl>

    <form method="POST" style="margin-top:20px;">
      <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
      <input type="hidden" name="fpx_bank" value="<?php echo htmlspecialchars($bank); ?>">
      <button type="submit" class="btn-main">Simulate Successful FPX Payment</button>
      <a href="payments.php" class="btn-secondary" style="margin-left:10px;">Cancel</a>
    </form>

    <p style="margin-top:18px;color:#6b7280;font-size:0.9rem;">Tip: access this via the Payments page flow or directly with <code>?booking_id=UB1008&amp;bank=maybank</code>.</p>
  </main>

</body>
</html>


