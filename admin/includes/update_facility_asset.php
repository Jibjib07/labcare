<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // 1. Collect and Validate ID
    $asset_id = $_POST['asset_id'] ?? null;
    if (!$asset_id || empty($asset_id)) {
        throw new Exception("Technical Error: Asset ID is missing.");
    }

    // 2. Collect other fields
    $asset_name = trim($_POST['asset_name'] ?? '');
    $property   = trim($_POST['asset_property'] ?? '');
    $brand      = trim($_POST['asset_brand'] ?? '');
    $status     = trim($_POST['asset_status'] ?? '');

    if (empty($asset_name) || empty($property)) {
        throw new Exception("Name and Property ID are required.");
    }

    // =========================================================
    // START SAFE TRANSACTION
    // =========================================================
    $conn->begin_transaction();

    // --- NEW: FETCH LAB ID BEFORE UPDATING ---
    // We need this for the history log
    $id_check = $conn->prepare("SELECT lab_id FROM assets WHERE asset_id = ?");
    $id_check->bind_param("s", $asset_id);
    $id_check->execute();
    $res = $id_check->get_result();
    $lab_id = ($row = $res->fetch_assoc()) ? $row['lab_id'] : 0;
    $id_check->close();

    // 3. Update the Assets table
    $query = "UPDATE assets SET 
                asset_name = ?, 
                asset_property = ?, 
                asset_brand = ?,
                asset_status = ?,
                latest_activity = NOW()
              WHERE asset_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $asset_name, $property, $brand, $status, $asset_id);
    $stmt->execute();
    $stmt->close();

    // =========================================================
    // 4. INSERT HISTORY LOG (Updated to include lab_id)
    // =========================================================
    $remarks = $_POST['remarks'] ?? '';
    $report_affected = $_POST['report_affected'] ?? 'Facility Asset';
    $actor = $_SESSION['user_name'] ?? 'System';
    $action = "Admin Edit/Update";
    $report_status = "Updated";

    // FIXED: Added lab_id column and placeholder
    $stmt_hist = $conn->prepare("INSERT INTO asset_history (asset_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");

    // 7 Strings: asset_id, lab_id, actor, affected, action, remarks, status
    $stmt_hist->bind_param("sssssss", $asset_id, $lab_id, $actor, $report_affected, $action, $remarks, $report_status);
    $stmt_hist->execute();
    $stmt_hist->close();

    $conn->commit();
    $response['success'] = true;
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;
