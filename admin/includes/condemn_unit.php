<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../../includes/db.php';

    $set_id = trim($_POST['set_id'] ?? '');
    $reasons_json = $_POST['reasons'] ?? '[]';
    $remarks = trim($_POST['remarks'] ?? '');

    if (!$set_id) throw new Exception("No Set ID provided.");

    // Parse the array of checkboxes into a readable string (e.g., "Hardware Failure, System Obsolescence")
    $reasons_array = json_decode($reasons_json, true);
    $action_taken = !empty($reasons_array) ? implode(", ", $reasons_array) : 'No specific reason checked';

    // 1. Update the Main Unit Status to permanently "Condemned"
    $stmt = $conn->prepare("UPDATE units SET set_status = 'Condemned' WHERE set_id = ?");
    $stmt->bind_param("s", $set_id);
    $stmt->execute();

    // 2. Insert the record into the unit_history table
    $actor = 'Admin'; // You can change this to $_SESSION['username'] later if you have login sessions
    $affected = 'Entire Unit';
    $report_status = 'Condemned';

    $stmt_history = $conn->prepare("
        INSERT INTO unit_history (set_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) 
        VALUES (?, NOW(), ?, ?, ?, ?, ?)
    ");
    $stmt_history->bind_param("ssssss", $set_id, $actor, $affected, $action_taken, $remarks, $report_status);
    $stmt_history->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
