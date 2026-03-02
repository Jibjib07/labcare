<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if (strtolower($_SESSION['user_role']) !== 'admin') {
    header("Location: ../user/dashboard.php");
    exit();
}
?>