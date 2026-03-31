<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id = $_POST['asset_id'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    // Log status could be "Updated" or "For Repair"
    $report_status = $_POST['report_status'] ?? 'Updated';
    // Actual table status is strictly "Working" or "For Repair"
    $overall_status = $_POST['overall_status'] ?? 'Working';

    $actor = $_SESSION['user_name'] ?? 'Staff';

    if (empty($asset_id)) {
        echo json_encode(['success' => false, 'error' => 'Missing Asset ID.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // --- 1. FETCH ASSET NAME AND LAB ID ---
        $info_stmt = $conn->prepare("SELECT asset_name, lab_id FROM assets WHERE asset_id = ?");
        $info_stmt->bind_param("s", $asset_id);
        $info_stmt->execute();
        $info_result = $info_stmt->get_result();

        $asset_name = "Facility Asset";
        $lab_id = 0; // Fallback

        if ($row = $info_result->fetch_assoc()) {
            $asset_name = !empty($row['asset_name']) ? $row['asset_name'] : "Facility Asset";
            $lab_id = (int)$row['lab_id']; // Grab the lab_id securely from the DB
        }
        $info_stmt->close();

        // --- 2. UPDATE ASSET STATUS ---
        // Added latest_activity=NOW() to keep tracking accurate
        $stmt1 = $conn->prepare("UPDATE assets SET asset_status=?, latest_activity=NOW() WHERE asset_id=?");
        $stmt1->bind_param("ss", $overall_status, $asset_id);
        $stmt1->execute();
        $stmt1->close();

        // --- 3. INSERT INTO HISTORY LOG (NOW INCLUDES LAB ID) ---
        $action = "Report";
        $stmt2 = $conn->prepare("INSERT INTO asset_history (asset_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");

        // "sisssss" translates to: String, Integer, String, String, String, String, String
        $stmt2->bind_param("sisssss", $asset_id, $lab_id, $actor, $asset_name, $action, $remarks, $report_status);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
    }
}
