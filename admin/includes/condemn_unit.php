<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../../includes/admin_auth.php';
    require_once __DIR__ . '/../../includes/db.php';

    $set_id = trim($_POST['set_id'] ?? '');
    $reasons_json = $_POST['reasons'] ?? '[]';
    $remarks = trim($_POST['remarks'] ?? '');

    if (!$set_id) throw new Exception("No Set ID provided.");

    $reasons_array = json_decode($reasons_json, true);
    $action_taken = !empty($reasons_array) ? implode(", ", $reasons_array) : 'Condemned';

    $conn->begin_transaction();

    // 1. Update unit status
    $stmt = $conn->prepare("UPDATE units SET set_status = 'Condemned' WHERE set_id = ?");
    $stmt->bind_param("s", $set_id);
    $stmt->execute();

    $actor = $_SESSION['user_name'] ?? 'Admin';
    $affected = 'Entire Unit';
    $report_status = 'Condemned';

    // 2. Insert into unit_history
    $stmt_history = $conn->prepare("
        INSERT INTO unit_history (set_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) 
        VALUES (?, NOW(), ?, ?, ?, ?, ?)
    ");

    $stmt_history->bind_param("ssssss", $set_id, $actor, $affected, $action_taken, $remarks, $report_status);
    $stmt_history->execute();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
