<?php
/**
 * admin/includes/clear_schedule.php
 */

// Disable HTML error display for this script so it doesn't break JSON
ini_set('display_errors', 0); 
error_reporting(E_ALL);

header('Content-Type: application/json');

// 1. PATH FIX: If db.php is in the ROOT includes folder, 
// and this file is in admin/includes/, we need to go up two levels.
if (file_exists('db.php')) {
    include 'db.php';
} elseif (file_exists('../../includes/db.php')) {
    include '../../includes/db.php';
} else {
    echo json_encode(['success' => false, 'error' => 'Database connection file not found.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Variable Check: Check if your connection is named $conn or $db
    if (!isset($conn)) {
        echo json_encode(['success' => false, 'error' => 'Database connection variable ($conn) is not defined. Check db.php.']);
        exit;
    }

    $room = isset($_POST['room_number']) ? $_POST['room_number'] : '';

    if (!empty($room)) {
        $room = $conn->real_escape_string($room);
        $query = "UPDATE laboratories SET lab_sched = NULL WHERE lab_room = '$room'";
        
        if ($conn->query($query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Room number is empty.']);
    }
}
?>