<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../../includes/admin_auth.php';
    require_once __DIR__ . '/../../includes/db.php';

    $target_lab_id = $_POST['target_lab_id'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');
    $actions_json = $_POST['actions'] ?? '[]';
    $units = json_decode($_POST['units'] ?? '[]', true);
    $assets = json_decode($_POST['assets'] ?? '[]', true);

    if (!$target_lab_id) throw new Exception("Target laboratory not selected.");

    // --- NEW LOGIC: Fetch the new Room Name for the target Lab ID ---
    $roomStmt = $conn->prepare("SELECT lab_room FROM laboratories WHERE lab_id = ? LIMIT 1");
    $roomStmt->bind_param("s", $target_lab_id);
    $roomStmt->execute();
    $roomResult = $roomStmt->get_result();
    $roomRow = $roomResult->fetch_assoc();

    if (!$roomRow) throw new Exception("Target laboratory room not found.");
    $new_room = $roomRow['lab_room'];
    // ---------------------------------------------------------------

    $actions_array = json_decode($actions_json, true);
    $reason_summary = !empty($actions_array) ? implode(", ", $actions_array) : 'General Transfer';
    $actor = $_SESSION['user_name'] ?? 'Admin';

    $conn->begin_transaction();

    // 1. Process Computer Units
    if (!empty($units)) {
        // UPDATED: Now updates both lab_id AND lab_room
        $stmt_u = $conn->prepare("UPDATE units SET lab_id = ?, lab_room = ? WHERE set_id = ?");
        $stmt_u_h = $conn->prepare("INSERT INTO unit_history (set_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, NOW(), ?, 'Entire Unit', 'Transfer', ?, 'Transferred')");

        foreach ($units as $id) {
            $stmt_u->bind_param("sss", $target_lab_id, $new_room, $id);
            $stmt_u->execute();

            $msg = "Transferred to $new_room (ID: $target_lab_id). Reason: $reason_summary. Notes: $remarks";
            $stmt_u_h->bind_param("sss", $id, $actor, $msg);
            $stmt_u_h->execute();
        }
    }

    // 2. Process Facility Assets
    if (!empty($assets)) {
        // UPDATED: Now updates both lab_id AND lab_room
        $stmt_a = $conn->prepare("UPDATE assets SET lab_id = ?, lab_room = ? WHERE asset_id = ?");
        $stmt_a_h = $conn->prepare("INSERT INTO asset_history (asset_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, NOW(), ?, 'Facility Asset', 'Transfer', ?, 'Transferred')");

        foreach ($assets as $id) {
            $stmt_a->bind_param("sss", $target_lab_id, $new_room, $id);
            $stmt_a->execute();

            $msg = "Transferred to $new_room (ID: $target_lab_id). Reason: $reason_summary. Notes: $remarks";
            $stmt_a_h->bind_param("sss", $id, $actor, $msg);
            $stmt_a_h->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
