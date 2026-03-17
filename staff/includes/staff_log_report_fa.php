<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id = $_POST['asset_id'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $report_status = $_POST['report_status'] ?? 'For Repair';

    $actor = $_SESSION['user_name'] ?? 'Staff';

    if (empty($asset_id)) {
        echo json_encode(['success' => false, 'error' => 'Missing Asset ID.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. UPDATE ASSET STATUS
        $stmt1 = $conn->prepare("UPDATE assets SET asset_status=? WHERE asset_id=?");
        $stmt1->bind_param("ss", $report_status, $asset_id);
        $stmt1->execute();
        $stmt1->close();

        // 2. INSERT INTO HISTORY LOG
        $action = "Report";
        $affected = "Facility Asset";

        $stmt2 = $conn->prepare("INSERT INTO asset_history (asset_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, NOW(), ?, ?, ?, ?, ?)");
        $stmt2->bind_param("ssssss", $asset_id, $actor, $affected, $action, $remarks, $report_status);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
    }
}
