<?php
session_start();
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/update_unit_status.php';

    $set_id = trim($_POST['set_id'] ?? '');
    if (!$set_id) throw new Exception("No Set ID provided");

    // =========================================================
    // 1. UNIQUENESS VALIDATION (Same as before)
    // =========================================================
    $edit_specs_id = trim($_POST['specs_property'] ?? '');
    $edit_mon_id = trim($_POST['monitor_property'] ?? '');

    if ($edit_specs_id !== '') {
        $check_sys = $conn->prepare("SELECT s.set_id FROM specs s JOIN units u ON s.set_id = u.set_id WHERE s.specs_property = ? AND s.set_id != ? AND (u.set_status != 'Condemned' OR u.set_status IS NULL)");
        $check_sys->bind_param("ss", $edit_specs_id, $set_id);
        $check_sys->execute();
        if ($check_sys->get_result()->num_rows > 0) throw new Exception("System Unit Property ID '$edit_specs_id' is already in use.");
        $check_sys->close();
    }

    if ($edit_mon_id !== '') {
        $check_mon = $conn->prepare("SELECT p.set_id FROM peripherals p JOIN units u ON p.set_id = u.set_id WHERE p.monitor_property = ? AND p.set_id != ? AND (u.set_status != 'Condemned' OR u.set_status IS NULL)");
        $check_mon->bind_param("ss", $edit_mon_id, $set_id);
        $check_mon->execute();
        if ($check_mon->get_result()->num_rows > 0) throw new Exception("Monitor Property ID '$edit_mon_id' is already in use.");
        $check_mon->close();
    }

    // =========================================================
    // 2. CHECK CURRENT STATUS & FETCH LAB ID
    // =========================================================
    // FIXED: Added lab_id to the selection
    $status_check = $conn->query("SELECT set_status, lab_id FROM units WHERE set_id = '$set_id'");
    $current_status = '';
    $lab_id = 0;
    if ($status_check && $row = $status_check->fetch_assoc()) {
        $current_status = $row['set_status'];
        $lab_id = $row['lab_id'];
    }

    $usb_ports = !empty($_POST['usb_ports']) ? (int)$_POST['usb_ports'] : 0;

    // =========================================================
    // 4. UPDATE DATABASE
    // =========================================================
    if ($current_status !== 'Condemned') {

        $conn->begin_transaction();

        // Update Specs
        $stmt_specs = $conn->prepare("UPDATE specs SET specs_property=?, specs_brand=?, specs_purchase=?, specs_cpu=?, specs_os=?, specs_gpu=?, specs_ram=?, specs_storage=?, specs_capacity=? WHERE set_id=?");
        $stmt_specs->bind_param("ssssssssss", $_POST['specs_property'], $_POST['specs_brand'], $_POST['specs_purchase'], $_POST['specs_cpu'], $_POST['specs_os'], $_POST['specs_gpu'], $_POST['specs_ram'], $_POST['specs_storage'], $_POST['specs_capacity'], $set_id);
        $stmt_specs->execute();
        $stmt_specs->close();

        // Update Ports
        $stmt_ports = $conn->prepare("UPDATE ports SET usb_ports=? WHERE set_id=?");
        $stmt_ports->bind_param("is", $usb_ports, $set_id);
        $stmt_ports->execute();
        $stmt_ports->close();

        // Update Peripherals
        $stmt_peripherals = $conn->prepare("UPDATE peripherals SET monitor_property=?, monitor_brand=?, keyboard_brand=?, mouse_brand=?, avr_brand=? WHERE set_id=?");
        $stmt_peripherals->bind_param("ssssss", $_POST['monitor_property'], $_POST['monitor_brand'], $_POST['keyboard_brand'], $_POST['mouse_brand'], $_POST['avr_brand'], $set_id);
        $stmt_peripherals->execute();
        $stmt_peripherals->close();

        // FIXED: Update latest_activity on the units table
        $stmt_activity = $conn->prepare("UPDATE units SET latest_activity = NOW() WHERE set_id = ?");
        $stmt_activity->bind_param("s", $set_id);
        $stmt_activity->execute();
        $stmt_activity->close();

        updateUnitStatus($conn, $set_id);

        // =========================================================
        // 5. INSERT HISTORY LOG (Including lab_id)
        // =========================================================
        $remarks = $_POST['remarks'] ?? '';
        $report_affected = $_POST['report_affected'] ?? 'Specs/Details';
        $actor = $_SESSION['user_name'] ?? 'Admin';
        $action = "Admin Edit/Update";
        $log_status = "Updated";

        // FIXED: Added lab_id to columns and values
        $stmt_hist = $conn->prepare("INSERT INTO unit_history (set_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");

        // "sssssss" = 7 strings
        $stmt_hist->bind_param("sssssss", $set_id, $lab_id, $actor, $report_affected, $action, $remarks, $log_status);
        $stmt_hist->execute();
        $stmt_hist->close();

        $conn->commit();
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
