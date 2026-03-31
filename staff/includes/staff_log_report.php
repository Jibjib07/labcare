<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $set_id = $_POST['set_id'] ?? '';
    $overall_status = $_POST['overall_status'] ?? 'Working';
    $component_logs_json = $_POST['component_logs'] ?? '[]'; // Grab the JSON

    $actor = $_SESSION['user_name'] ?? 'Staff';

    if (empty($set_id)) {
        echo json_encode(['success' => false, 'error' => 'Missing Unit ID.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // --- 0. FETCH LAB ID ---
        $info_stmt = $conn->prepare("SELECT lab_id FROM units WHERE set_id = ?");
        $info_stmt->bind_param("s", $set_id);
        $info_stmt->execute();
        $info_result = $info_stmt->get_result();

        $lab_id = 0; // Fallback
        if ($row = $info_result->fetch_assoc()) {
            $lab_id = (int)$row['lab_id']; // Grab the lab_id securely from the DB
        }
        $info_stmt->close();

        // 1. UPDATE PORTS
        if (isset($_POST['usb_status'])) {
            $stmt1 = $conn->prepare("UPDATE ports SET usb_status=?, wifi_status=?, mic_status=?, hdmi_status=?, headphone_status=?, display_status=?, inline_status=?, ethernet_status=? WHERE set_id=?");
            $stmt1->bind_param("sssssssss", $_POST['usb_status'], $_POST['wifi_status'], $_POST['mic_status'], $_POST['hdmi_status'], $_POST['headphone_status'], $_POST['display_status'], $_POST['inline_status'], $_POST['ethernet_status'], $set_id);
            $stmt1->execute();
            $stmt1->close();
        }

        // 2. UPDATE HEALTH
        if (isset($_POST['disk_health'])) {
            $stmt2 = $conn->prepare("UPDATE health SET disk_health=?, power_health=? WHERE set_id=?");
            $stmt2->bind_param("sss", $_POST['disk_health'], $_POST['power_health'], $set_id);
            $stmt2->execute();
            $stmt2->close();
        }

        // 3. UPDATE PERIPHERALS
        if (isset($_POST['monitor_status'])) {
            $stmt3 = $conn->prepare("UPDATE peripherals SET monitor_status=?, mouse_status=?, keyboard_status=?, avr_status=? WHERE set_id=?");
            $stmt3->bind_param("sssss", $_POST['monitor_status'], $_POST['mouse_status'], $_POST['keyboard_status'], $_POST['avr_status'], $set_id);
            $stmt3->execute();
            $stmt3->close();
        }

        // 4. UPDATE PARENT UNIT STATUS
        $stmt4 = $conn->prepare("UPDATE units SET set_status=?, latest_activity=NOW() WHERE set_id=?");
        $stmt4->bind_param("ss", $overall_status, $set_id);
        $stmt4->execute();
        $stmt4->close();

        // 5. INDIVIDUAL HISTORY LOGS (Now includes lab_id)
        $logs = json_decode($component_logs_json, true);

        if (!empty($logs) && is_array($logs)) {
            $action = "Report";
            // Added lab_id to the INSERT statement
            $stmt_hist = $conn->prepare("INSERT INTO unit_history (set_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");

            foreach ($logs as $log) {
                $affected = $log['affected'];
                $remark = $log['remark'];
                $status = $log['status']; // Will safely insert 'For Repair' or 'Updated'

                // "sisssss" translates to: String, Integer, String, String, String, String, String
                $stmt_hist->bind_param("sisssss", $set_id, $lab_id, $actor, $affected, $action, $remark, $status);
                $stmt_hist->execute();
            }
            $stmt_hist->close();
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
    }
}
