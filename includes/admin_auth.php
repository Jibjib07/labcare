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

        // If AJAX request, return JSON so JS can show the toast
        if (isset($_POST['action'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'session_expired' => true, 'message' => 'Session timed out.']);
            exit;
        }

        header("Location: ../login.php?error=timeout");
        exit;
    }
}

// Everything is good, update the clock
$_SESSION['last_activity'] = time();
?>
