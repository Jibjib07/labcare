<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    session_start();
    require_once __DIR__ . '/../../includes/admin_auth.php';
    require_once __DIR__ . '/../../includes/db.php';

    $target_lab_id = $_POST['target_lab_id'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');
    $actions_json = $_POST['actions'] ?? '[]';
    $units = json_decode($_POST['units'] ?? '[]', true);
    $assets = json_decode($_POST['assets'] ?? '[]', true);

    if (!$target_lab_id) throw new Exception("Target laboratory not selected.");

    // Fetch the new Room Name
    $roomStmt = $conn->prepare("SELECT lab_room FROM laboratories WHERE lab_id = ? LIMIT 1");
    $roomStmt->bind_param("s", $target_lab_id);
    $roomStmt->execute();
    $roomRow = $roomStmt->get_result()->fetch_assoc();

    if (!$roomRow) throw new Exception("Target laboratory room not found.");
    $new_room = $roomRow['lab_room'];

    $actions_array = json_decode($actions_json, true);
    $reason_summary = !empty($actions_array) ? implode(", ", $actions_array) : 'General Transfer';
    $actor = $_SESSION['user_name'] ?? 'Admin';

    $conn->begin_transaction();

    // --- 1. Process Computer Units ---
    if (!empty($units)) {
        // Prepare the update (Now includes set_tag)
        $stmt_u = $conn->prepare("UPDATE units SET lab_id = ?, lab_room = ?, set_tag = ?, latest_activity = NOW() WHERE set_id = ?");
        $stmt_u_h = $conn->prepare("INSERT INTO unit_history (set_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, 'Entire Set', 'Transfer', ?, 'Transferred')");

        foreach ($units as $id) {
            // GET OLD ROOM NAME BEFORE OVERWRITING
            $old_room_res = $conn->query("SELECT lab_room FROM units WHERE set_id = '$id'");
            $old_room = ($old_room_res && $row = $old_room_res->fetch_assoc()) ? $row['lab_room'] : 'Unknown';

            // FIND NEXT FREE TAG FOR COMPUTER UNITS
            $tag_res = $conn->query("SELECT set_tag FROM units WHERE lab_id = '$target_lab_id' AND (set_status != 'Condemned' OR set_status IS NULL) ORDER BY CAST(set_tag AS UNSIGNED) ASC");
            $taken = [];
            while ($t = $tag_res->fetch_assoc()) $taken[] = (int)$t['set_tag'];

            $next_f = 1;
            while (in_array($next_f, $taken)) $next_f++;
            $new_tag = str_pad($next_f, 2, "0", STR_PAD_LEFT);

            // Execute Update
            $stmt_u->bind_param("ssss", $target_lab_id, $new_room, $new_tag, $id);
            $stmt_u->execute();

            // FORMATTED MESSAGE WITH OLD AND NEW ROOM
            $msg = "Transferred From $old_room to $new_room. New Tag: PC-$new_tag. Reason: $reason_summary. Remarks: " . (!empty($remarks) ? $remarks : "None provided.");
            $stmt_u_h->bind_param("ssss", $id, $target_lab_id, $actor, $msg);
            $stmt_u_h->execute();
        }
    }

    // --- 2. Process Facility Assets ---
    if (!empty($assets)) {
        // Prepare the update (Now includes asset_tag)
        $stmt_a = $conn->prepare("UPDATE assets SET lab_id = ?, lab_room = ?, asset_tag = ?, latest_activity = NOW() WHERE asset_id = ?");
        $stmt_a_h = $conn->prepare("INSERT INTO asset_history (asset_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, 'Facility Asset', 'Transfer', ?, 'Transferred')");

        foreach ($assets as $id) {
            // GET OLD ROOM NAME BEFORE OVERWRITING
            $old_room_res_a = $conn->query("SELECT lab_room FROM assets WHERE asset_id = '$id'");
            $old_room_a = ($old_room_res_a && $row_a = $old_room_res_a->fetch_assoc()) ? $row_a['lab_room'] : 'Unknown';

            // FIND NEXT FREE TAG FOR FACILITY ASSETS
            $tag_res_a = $conn->query("SELECT asset_tag FROM assets WHERE lab_id = '$target_lab_id' ORDER BY CAST(asset_tag AS UNSIGNED) ASC");
            $taken_a = [];
            while ($t_a = $tag_res_a->fetch_assoc()) $taken_a[] = (int)$t_a['asset_tag'];

            $next_f_a = 1;
            while (in_array($next_f_a, $taken_a)) $next_f_a++;
            $new_tag_a = str_pad($next_f_a, 2, "0", STR_PAD_LEFT);

            // Execute Update
            $stmt_a->bind_param("ssss", $target_lab_id, $new_room, $new_tag_a, $id);
            $stmt_a->execute();

            // FORMATTED MESSAGE WITH OLD AND NEW ROOM
            $msg = "Transferred From $old_room_a to $new_room. New Tag: FA-$new_tag_a. Reason: $reason_summary. Remarks: $remarks";
            $stmt_a_h->bind_param("ssss", $id, $target_lab_id, $actor, $msg);
            $stmt_a_h->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}