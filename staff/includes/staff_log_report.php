<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $set_id = $_POST['set_id'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $report_affected = $_POST['report_affected'] ?? 'Entire Unit';

    // Uses 'user_name' based on your users table schema
    $actor = $_SESSION['user_name'] ?? 'Staff';

    if (empty($set_id)) {
        echo json_encode(['success' => false, 'error' => 'Missing Unit ID.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. UPDATE PORTS (Corrected table name to `ports`)
        if (isset($_POST['usb_status'])) {
            $stmt1 = $conn->prepare("UPDATE ports SET usb_status=?, wifi_status=?, mic_status=?, hdmi_status=?, headphone_status=?, display_status=?, inline_status=?, ethernet_status=? WHERE set_id=?");
            $stmt1->bind_param("sssssssss", $_POST['usb_status'], $_POST['wifi_status'], $_POST['mic_status'], $_POST['hdmi_status'], $_POST['headphone_status'], $_POST['display_status'], $_POST['inline_status'], $_POST['ethernet_status'], $set_id);
            $stmt1->execute();
            $stmt1->close();
        }

        // 2. UPDATE HEALTH (Matches your `health` table)
        if (isset($_POST['disk_health'])) {
            $stmt2 = $conn->prepare("UPDATE health SET disk_health=?, power_health=? WHERE set_id=?");
            $stmt2->bind_param("sss", $_POST['disk_health'], $_POST['power_health'], $set_id);
            $stmt2->execute();
            $stmt2->close();
        }

        // 3. UPDATE PERIPHERALS (Matches your `peripherals` table)
        if (isset($_POST['monitor_status'])) {
            $stmt3 = $conn->prepare("UPDATE peripherals SET monitor_status=?, mouse_status=?, keyboard_status=?, avr_status=? WHERE set_id=?");
            $stmt3->bind_param("sssss", $_POST['monitor_status'], $_POST['mouse_status'], $_POST['keyboard_status'], $_POST['avr_status'], $set_id);
            $stmt3->execute();
            $stmt3->close();
        }

        // 4. UPDATE PARENT UNIT STATUS
        // Since this is a "Report", we lock the parent PC status to 'For Repair'
        $stmt4 = $conn->prepare("UPDATE units SET set_status='For Repair' WHERE set_id=?");
        $stmt4->bind_param("s", $set_id);
        $stmt4->execute();
        $stmt4->close();

        // 5. INSERT HISTORY LOG (Matches your `unit_history` table)
        $action = "Report";
        $status = "For Repair";

        $stmt_hist = $conn->prepare("INSERT INTO unit_history (set_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, NOW(), ?, ?, ?, ?, ?)");
        $stmt_hist->bind_param("ssssss", $set_id, $actor, $report_affected, $action, $remarks, $status);
        $stmt_hist->execute();
        $stmt_hist->close();

        // Commit all changes safely!
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
    }
}
