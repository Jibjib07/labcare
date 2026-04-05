<?php
session_start();
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../../includes/db.php';

    $asset_id = trim($_POST['asset_id'] ?? '');
    if (!$asset_id) throw new Exception("No Asset ID provided");

    // The JS script strips "edit_" from the IDs, leaving us with these keys:
    $name = trim($_POST['fa_name'] ?? '');
    // $property has been removed
    $brand = trim($_POST['fa_brand'] ?? '');
    $status = trim($_POST['fa_status'] ?? 'Working');

    // =========================================================
    // 1. FETCH CURRENT DATA FOR HISTORY LOGGING
    // =========================================================
    $status_check = $conn->prepare("SELECT asset_status, lab_id FROM assets WHERE asset_id = ?");
    $status_check->bind_param("s", $asset_id);
    $status_check->execute();
    $row = $status_check->get_result()->fetch_assoc();
    $lab_id = (int)($row['lab_id'] ?? 0);
    $status_check->close();

    // =========================================================
    // 2. UPDATE ASSET (asset_property removed from query and values)
    // =========================================================
    $conn->begin_transaction();

    $stmt = $conn->prepare("UPDATE assets SET asset_name=?, asset_brand=?, asset_status=?, latest_activity=NOW() WHERE asset_id=?");
    
    // Bind 4 strings ("ssss") instead of 5
    $stmt->bind_param("ssss", $name, $brand, $status, $asset_id);
    $stmt->execute();
    $stmt->close();

    // =========================================================
    // 3. INSERT HISTORY LOG (Matching the new JSON loop format)
    // =========================================================
    $actor = $_SESSION['user_name'] ?? 'Admin';
    $action = "Admin Edit/Update";

    $stmt_hist = $conn->prepare("INSERT INTO asset_history (asset_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");

    // A. Log General Specs changes
    $general_remarks = trim($_POST['general_remarks'] ?? '');
    $general_affected = trim($_POST['report_affected_general'] ?? '');

    if (!empty($general_remarks)) {
        $general_status = "Updated"; // Keeps it from showing a yellow pill
        $stmt_hist->bind_param("sisssss", $asset_id, $lab_id, $actor, $general_affected, $action, $general_remarks, $general_status);
        $stmt_hist->execute();
    }

    // B. Log Individual Status Changes
    $status_logs_json = $_POST['status_logs'] ?? '[]';
    $status_logs_array = json_decode($status_logs_json, true);

    if (is_array($status_logs_array) && count($status_logs_array) > 0) {
        foreach ($status_logs_array as $log) {
            $component_name = $log['component'];
            $component_remark = $log['remark'];
            $log_action = "Status Update";

            $stmt_hist->bind_param("sisssss", $asset_id, $lab_id, $actor, $component_name, $log_action, $component_remark, $status);
            $stmt_hist->execute();
        }
    }

    $stmt_hist->close();
    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}