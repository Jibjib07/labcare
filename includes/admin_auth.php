<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// If the role variable is missing OR it is not exactly 'admin'
if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
    header("Location: ../staff/dashboard.php");
    exit();
}

$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['last_activity'])) {
    $elapsed_time = time() - $_SESSION['last_activity'];

    if ($elapsed_time > $timeout_duration) {
        session_unset();
        session_destroy();

        // UPGRADE: Check if it's an AJAX request via POST, GET, or Server Headers
        $is_ajax = isset($_POST['action']) || isset($_GET['action']) ||
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'session_expired' => true, 'message' => 'Session timed out. Please log in again.']);
            exit;
        }

        header("Location: ../login.php?error=timeout");
        exit;
    }
}

// Everything is good, update the clock
$_SESSION['last_activity'] = time();
