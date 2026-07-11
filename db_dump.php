<?php
require_once '../config.php';
$conn = db_get_connection();
if ($conn) {
    $rows = db_query_assoc_list("SELECT booking_id, payment_method, payment_status FROM bookings_data");
    echo "DATABASE BOOKINGS:\n";
    print_r($rows);
} else {
    echo "NO DB CONNECTION\n";
}
echo "\nSESSION BOOKINGS:\n";
print_r($_SESSION['bookings'] ?? []);
