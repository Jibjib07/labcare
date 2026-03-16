<?php
session_start(); // Required to get the Admin's name for the history log
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
        throw new Exception("Technical Error: Asset ID is missing. Update aborted to protect data.");
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

    // 3. Update the Assets table (Excluded asset_status)
    $query = "UPDATE assets SET 
                asset_name = ?, 
                asset_property = ?, 
                asset_brand = ?
              WHERE asset_id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    $stmt->bind_param("ssss", $asset_name, $property, $brand, $asset_id);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $stmt->close();

    // =========================================================
    // 4. INSERT HISTORY LOG
    // =========================================================
    $remarks = $_POST['remarks'] ?? '';
    $report_affected = $_POST['report_affected'] ?? 'Facility Asset';
    $actor = $_SESSION['user_name'] ?? 'Admin';
    $action = "Admin Edit/Update";

    // Status locked to Updated
    $report_status = "Updated";

    $stmt_hist = $conn->prepare("INSERT INTO asset_history (asset_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, NOW(), ?, ?, ?, ?, ?)");
    $stmt_hist->bind_param("ssssss", $asset_id, $actor, $report_affected, $action, $remarks, $report_status);

    if (!$stmt_hist->execute()) {
        throw new Exception("History Log failed: " . $stmt_hist->error);
    }
    $stmt_hist->close();

    // =========================================================
    // COMMIT TRANSACTION (Save permanently)
    // =========================================================
    $conn->commit();

    $response['success'] = true;
    $response['message'] = "Updated successfully.";
} catch (Exception $e) {
    // If ANY step fails, rollback all changes
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;
