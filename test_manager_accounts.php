<?php
require_once __DIR__ . '/../config.php';

$conn = db_get_connection();
if (!$conn) {
    fwrite(STDERR, "Database connection failed\n");
    exit(1);
}

$roles = [];
$result = mysqli_query($conn, "SELECT role_name FROM roles WHERE role_name IN ('manager')");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $roles[] = $row['role_name'];
    }
}

$emails = [];
$result = mysqli_query($conn, "SELECT email FROM users WHERE email IN ('facilitiesmanager@unibook.com','transportmanager@unibook.com','ictmanager@unibook.com','hrmanager@unibook.com')");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $emails[] = $row['email'];
    }
}

if (!in_array('manager', $roles, true) || count($emails) < 4) {
    fwrite(STDERR, "Manager role/accounts are missing\n");
    exit(1);
}

echo "Manager role and accounts are present\n";
