<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    session_start(); // Added this just in case admin_auth doesn't start the session automatically
    require_once __DIR__ . '/../../includes/admin_auth.php';
    require_once __DIR__ . '/../../includes/db.php';

    $asset_id = trim($_POST['asset_id'] ?? '');
    $reasons_json = $_POST['reasons'] ?? '[]';
    $remarks = trim($_POST['remarks'] ?? '');

    if (!$asset_id) throw new Exception("No Asset ID provided.");

    $reasons_array = json_decode($reasons_json, true);
    $action_taken = !empty($reasons_array) ? implode(", ", $reasons_array) : 'Condemned';

    $conn->begin_transaction();

    // 1. Update status to Condemned AND set the latest_activity timestamp!
    $stmt = $conn->prepare("UPDATE assets SET asset_status = 'Condemned', latest_activity = NOW() WHERE asset_id = ?");
    $stmt->bind_param("s", $asset_id);
    $stmt->execute();

    // 2. Insert into asset_history
    $actor = $_SESSION['user_name'] ?? 'Admin';
    $affected = 'Facility Asset';
    $report_status = 'Condemned';

    // FIX: 7 columns, 7 values (asset_id, NOW(), actor, affected, action, remarks, status)
    $stmt_history = $conn->prepare("
        INSERT INTO asset_history (asset_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) 
        VALUES (?, NOW(), ?, ?, ?, ?, ?)
    ");

    $stmt_history->bind_param("ssssss", $asset_id, $actor, $affected, $action_taken, $remarks, $report_status);
    $stmt_history->execute();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
