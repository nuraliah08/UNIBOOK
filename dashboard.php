<?php
// dashboard.php
require_once 'config.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
} elseif ($_SESSION['role'] === 'manager') {
    header("Location: manager/dashboard.php");
    exit();
} else {
    header("Location: user/dashboard.php");
    exit();
}
