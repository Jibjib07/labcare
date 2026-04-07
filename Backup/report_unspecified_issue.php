<?php
// 1. Start the session to get the Staff Name
session_start(); 

header('Content-Type: application/json');

// 2. Connect to the database 
// Path assumes this file is in 'staff/includes/' and db.php is in 'includes/'
require_once __DIR__ . '/../../includes/db.php'; 

$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? '';
$lab_id = $_POST['lab_id'] ?? 0;
$remarks = trim($_POST['remarks'] ?? '');

if (empty($id) || empty($remarks)) {
    echo json_encode(['success' => false, 'error' => 'Missing ID or remarks']);
    exit;
}

// 3. Use 'user_name' from the staff session
$actor = $_SESSION['user_name'] ?? 'Staff Member'; 

// Format the log to match your system style
$formatted_remark = "Marked as For Repair. Notes: " . $remarks;
$log_action = "Report"; // Staff action is usually 'Report'
$affected = "Unspecified";
$db_report_status = "Issue Reported"; // This triggers the yellow pill

try {
    $conn->begin_transaction();

    if ($type === 'pc') {
        // Update overall unit status to 'For Repair'
        $stmt = $conn->prepare("UPDATE units SET set_status = 'For Repair', latest_activity = NOW() WHERE set_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();

        // Insert into PC History
        $stmt_hist = $conn->prepare("INSERT INTO unit_history (set_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");
        $stmt_hist->bind_param("sisssss", $id, $lab_id, $actor, $affected, $log_action, $formatted_remark, $db_report_status);
        $stmt_hist->execute();

    } else {
        // Update overall facility asset status
        $stmt = $conn->prepare("UPDATE assets SET asset_status = 'For Repair', latest_activity = NOW() WHERE asset_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();

        // Insert into Asset History
        $stmt_hist = $conn->prepare("INSERT INTO asset_history (asset_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");
        $stmt_hist->bind_param("sisssss", $id, $lab_id, $actor, $affected, $log_action, $formatted_remark, $db_report_status);
        $stmt_hist->execute();
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>