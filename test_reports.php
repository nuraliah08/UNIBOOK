<?php
require_once '../config.php';
$bookings = get_all_bookings();
echo "BOOKINGS LIST:\n";
foreach ($bookings as $id => $bk) {
    if ($bk['payment_status'] === 'Paid') {
        echo "ID: $id | Method: " . json_encode($bk['payment_method']) . " | Status: " . $bk['payment_status'] . "\n";
    }
}
