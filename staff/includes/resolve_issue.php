<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once 'update_unit_status.php'; // Required to recalculate the parent PC status!
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id = $_POST['id'] ?? '';
    $lab_id = $_POST['lab_id'] ?? 0; 

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

            // Loop through every single part/issue the Admin switched to "Working"
            foreach ($resolutions as $res) {
                $col = $res['column'];
                $name = $res['componentName'];
                $raw_remarks = !empty($res['adminRemarks']) ? $res['adminRemarks'] : 'None provided.';
                
                // Determine the correct "fixed" status word for formatting
                $fixed_status = ($col === 'disk_health') ? 'Healthy' : 'Working';
                $remarks = "Marked as " . $fixed_status . ". Remarks: " . $raw_remarks;
                $history_affected = $name;

                // 1. UPDATE THE SPECIFIC TABLES OR HISTORY ROWS
                
                // --- CASE A: Handle Multiple Unspecified Reports (Virtual Columns) ---
                if (strpos($col, 'unspec_') === 0) {
                    $report_id_to_fix = str_replace('unspec_', '', $col);
                    
                    // Mark the ORIGINAL report row as Resolved so it leaves the "Broken" list
                    $stmt_upd = $conn->prepare("UPDATE unit_history SET report_status = 'Resolved' WHERE report_id = ?");
                    $stmt_upd->bind_param("i", $report_id_to_fix);
                    $stmt_upd->execute();
                    $stmt_upd->close();

                    $history_affected = "Unspecified Issue";
                } 
                // --- CASE B: General Unit Status ---
                else if ($col === 'general_system') {
                    $conn->query("UPDATE units SET set_status='Working' WHERE set_id='$id'");
                    $history_affected = "Unspecified";
                } 
                // --- CASE C: Disk Health ---
                else if ($col === 'disk_health') {
                    $conn->query("UPDATE health SET disk_health='Healthy' WHERE set_id='$id'");
                    $num_repairs_added++;
                } 
                // --- CASE D: System Performance / Power ---
                else if ($col === 'power_health') {
                    $conn->query("UPDATE health SET power_health='Working' WHERE set_id='$id'");
                    $num_repairs_added++;
                } 
                // --- CASE E: Hardware Ports ---
                else if (in_array($col, $port_cols)) {
                    $conn->query("UPDATE ports SET $col='Working' WHERE set_id='$id'");
                    $num_repairs_added++;
                } 
                // --- CASE F: Peripherals ---
                else if (in_array($col, $periph_cols)) {
                    $conn->query("UPDATE peripherals SET $col='Working' WHERE set_id='$id'");
                    $num_repairs_added++;
                }

                // 2. INSERT INDIVIDUAL HISTORY LOG (The record of the resolution action)
                $action = "Fixed";
                $stmt = $conn->prepare("INSERT INTO unit_history (set_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, 'Resolved')");
                $stmt->bind_param("sissss", $id, $lab_id, $actor, $history_affected, $action, $remarks);
                $stmt->execute();
                $stmt->close();
            }

            // 3. INCREMENT NUMBER OF REPAIRS (Only if actual hardware parts were fixed)
            if ($num_repairs_added > 0) {
                $conn->query("UPDATE health SET num_repair = num_repair + $num_repairs_added WHERE set_id='$id'");
            }

            // 4. CHECK IF UNIT IS FULLY REPAIRED
            // This function checks history for active unspecified issues and hardware tables.
            updateUnitStatus($conn, $id);

            // 5. STAMP LATEST ACTIVITY
            $conn->query("UPDATE units SET latest_activity = NOW() WHERE set_id='$id'");

        } else if ($type === 'fa') {
            // Facility Asset Logic
            foreach ($resolutions as $res) {
                $raw_remarks = !empty($res['adminRemarks']) ? $res['adminRemarks'] : 'None provided.';
                $remarks = "Marked as Working. Remarks: " . $raw_remarks;

                $conn->query("UPDATE assets SET asset_status='Working', latest_activity = NOW() WHERE asset_id='$id'");

                $action = "Fixed";
                $affected = "Facility Asset";

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
        echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
    }
}
?>