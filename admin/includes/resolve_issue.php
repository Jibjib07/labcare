<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once 'update_unit_status.php'; // Required to recalculate the parent PC status!
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id = $_POST['id'] ?? '';
    $lab_id = $_POST['lab_id'] ?? 0; // Receive the lab_id

    // Decode the payload of fixed components
    $resolutions = json_decode($_POST['resolutions'] ?? '[]', true);
    $actor = $_SESSION['user_name'] ?? 'Admin';

    if (empty($id) || empty($resolutions)) {
        echo json_encode(['success' => false, 'error' => 'Missing resolution data.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $num_repairs_added = 0; // Tracks the mathematical +1 logic

        if ($type === 'pc') {
            $port_cols = ['usb_status', 'wifi_status', 'mic_status', 'hdmi_status', 'headphone_status', 'display_status', 'inline_status', 'ethernet_status'];
            $periph_cols = ['monitor_status', 'mouse_status', 'keyboard_status', 'avr_status'];

            // Loop through every single part the Admin switched to "Working"
            foreach ($resolutions as $res) {
                $col = $res['column'];
                $name = $res['componentName'];
                $remarks = !empty($res['adminRemarks']) ? $res['adminRemarks'] : 'Resolved without specific remarks.';

                // 1. UPDATE THE SPECIFIC TABLE
                if ($col === 'general_system') {
                    $conn->query("UPDATE units SET set_status='Working' WHERE set_id='$id'");
                } else if ($col === 'disk_health') {
                    // FIX: Explicitly save as "Healthy" instead of "Working"
                    $conn->query("UPDATE health SET disk_health='Healthy' WHERE set_id='$id'");
                    $num_repairs_added++;
                } else if ($col === 'power_health') {
                    $conn->query("UPDATE health SET power_health='Working' WHERE set_id='$id'");
                    $num_repairs_added++;
                } else if (in_array($col, $port_cols)) {
                    $conn->query("UPDATE ports SET $col='Working' WHERE set_id='$id'");
                    $num_repairs_added++;
                } else if (in_array($col, $periph_cols)) {
                    $conn->query("UPDATE peripherals SET $col='Working' WHERE set_id='$id'");
                }

                // 2. INSERT INDIVIDUAL HISTORY LOG WITH LAB_ID
                $action = "Fixed";
                // Notice the new lab_id column and the "s" bind param updated
                $stmt = $conn->prepare("INSERT INTO unit_history (set_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, 'Resolved')");
                $stmt->bind_param("sissss", $id, $lab_id, $actor, $name, $action, $remarks);
                $stmt->execute();
                $stmt->close();
            }

            // 3. INCREMENT NUMBER OF REPAIRS
            if ($num_repairs_added > 0) {
                $conn->query("UPDATE health SET num_repair = num_repair + $num_repairs_added WHERE set_id='$id'");
            }

            // 4. CHECK IF UNIT IS FULLY REPAIRED
            updateUnitStatus($conn, $id);

            // 5. NEW: STAMP THE LATEST ACTIVITY ON THE PARENT UNIT
            // This ensures the main PC is marked as recently updated regardless of which component was fixed.
            $conn->query("UPDATE units SET latest_activity = NOW() WHERE set_id='$id'");
        } else if ($type === 'fa') {
            foreach ($resolutions as $res) {
                $remarks = !empty($res['adminRemarks']) ? $res['adminRemarks'] : 'Resolved without specific remarks.';

                // FIXED: Added latest_activity = NOW() to the FA update
                $conn->query("UPDATE assets SET asset_status='Working', latest_activity = NOW() WHERE asset_id='$id'");

                $action = "Fixed";
                $affected = "Facility Asset";

                // Add lab_id to the FA history insert
                $stmt = $conn->prepare("INSERT INTO asset_history (asset_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, 'Resolved')");
                $stmt->bind_param("sissss", $id, $lab_id, $actor, $affected, $action, $remarks);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
