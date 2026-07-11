<?php
require_once '../config.php';
$conn = db_get_connection();
if ($conn) {
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM bookings LIMIT 1"));
    echo "VIEW ROW:\n";
    print_r($row);
    
    echo "\nSHOW CREATE VIEW bookings:\n";
    $view_info = mysqli_fetch_assoc(mysqli_query($conn, "SHOW CREATE VIEW bookings"));
    print_r($view_info);
}
