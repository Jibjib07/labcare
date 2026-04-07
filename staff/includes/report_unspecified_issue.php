<?php
// 1. Start the session at the very top!
session_start(); 

header('Content-Type: application/json');

// 2. Connect to the database
require_once __DIR__ . '/../../includes/db.php'; 

$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? '';
$lab_id = $_POST['lab_id'] ?? 0;
$remarks = trim($_POST['remarks'] ?? '');

if (empty($id) || empty($remarks)) {
    echo json_encode(['success' => false, 'error' => 'Missing ID or remarks']);
    exit;
}

// 3. THIS IS THE FIX: We are using 'user_name' to match your login.php perfectly!
$actor = $_SESSION['user_name'] ?? 'System'; 

$formatted_remark = "Marked as For Repair. Notes: " . $remarks;
$log_action = "Status Update";
$affected = "Unspecified";
$db_report_status = "Issue Reported";

try {
    if ($type === 'pc') {
        // Update overall unit status
        $stmt = $conn->prepare("UPDATE units SET set_status = 'For Repair' WHERE set_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();

        // Safe INSERT for PC History
        $stmt_hist = $conn->prepare("INSERT INTO unit_history (set_id, lab_id, report_actor, report_affected, report_action, report_remarks, report_status, report_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt_hist->bind_param("sisssss", $id, $lab_id, $actor, $affected, $log_action, $formatted_remark, $db_report_status);
        $stmt_hist->execute();

    } else {
        // Update overall asset status
        $stmt = $conn->prepare("UPDATE assets SET asset_status = 'For Repair' WHERE asset_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();

        // Safe INSERT for Asset History
        $stmt_hist = $conn->prepare("INSERT INTO asset_history (asset_id, lab_id, report_actor, report_affected, report_action, report_remarks, report_status, report_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt_hist->bind_param("sisssss", $id, $lab_id, $actor, $affected, $log_action, $formatted_remark, $db_report_status);
        $stmt_hist->execute();
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>