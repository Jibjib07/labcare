<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🛡️ 1. CHECK IF LOGGED IN
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 🛡️ 2. ROLE VERIFICATION (Authorization)
// Note: Changed $_SESSION['role'] to $_SESSION['user_role'] to match your login_action.php
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'Staff') {
    
    // If an Admin tries to sneak into staff pages, send them back to Admin dashboard
    if ($_SESSION['user_role'] === 'Admin') {
        header("Location: ../admin/dashboard.php");
        exit();
    }

    // Unknown role? Kick to login
    header("Location: ../login.php");
    exit();
}

// 🛡️ 3. CHECK SESSION TIMEOUT (Activity Tracking)
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['last_activity'])) {
    $elapsed_time = time() - $_SESSION['last_activity'];

    if ($elapsed_time > $timeout_duration) {
        // Clear and destroy session
        session_unset();
        session_destroy();

        // ⚡ Handle AJAX (for any staff-side fetch requests)
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || (isset($_POST['action']))) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'session_expired' => true, 'message' => 'Session timed out.']);
            exit;
        }

        header("Location: ../login.php?error=timeout");
        exit;
    }
}

// SUCCESS: User is active and authorized. Update the clock.
$_SESSION['last_activity'] = time();
?>