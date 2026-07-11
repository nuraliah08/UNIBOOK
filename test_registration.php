<?php
require_once 'config.php';

echo "=== STARTING VERIFICATION ===\n";

// Helper function to check if a user exists
function check_user($email) {
    $conn = db_get_connection();
    if (!$conn) return false;
    $email_safe = mysqli_real_escape_string($conn, strtolower($email));
    $res = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email_safe'");
    return mysqli_fetch_assoc($res);
}

// Clean up test users first
$conn = db_get_connection();
if ($conn) {
    mysqli_query($conn, "DELETE FROM users WHERE email IN ('testuser@unibook.edu', 'testmanager@unibook.com')");
}

// Mock POST request for User registration
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'registration_type' => 'user',
    'fullname' => 'Test User',
    'phone' => '+111111111',
    'email' => 'testuser@unibook.edu',
    'password' => 'password123',
    'confirm_password' => 'password123'
];

ob_start();
include 'register.php';
ob_end_clean();

$user = check_user('testuser@unibook.edu');
if ($user && $user['role_id'] == 2) {
    echo "✔ User registration test: PASSED\n";
} else {
    echo "❌ User registration test: FAILED (User not found or incorrect role_id)\n";
    print_r($user);
}

// Mock POST request for Manager registration
$_POST = [
    'registration_type' => 'manager',
    'fullname' => 'Test Manager',
    'phone' => '+222222222',
    'email' => 'testmanager@unibook.com',
    'password' => 'password123',
    'confirm_password' => 'password123',
    'department' => 'Test Faculty',
    'category_id' => '1' // Facilities
];

ob_start();
include 'register.php';
ob_end_clean();

$manager = check_user('testmanager@unibook.com');
if ($manager && $manager['role_id'] == 3 && $manager['department'] === 'Test Faculty' && $manager['category_id'] == 1) {
    echo "✔ Manager registration test: PASSED\n";
} else {
    echo "❌ Manager registration test: FAILED\n";
    print_r($manager);
}

// Clean up
if ($conn) {
    mysqli_query($conn, "DELETE FROM users WHERE email IN ('testuser@unibook.edu', 'testmanager@unibook.com')");
}

echo "=== VERIFICATION COMPLETE ===\n";
