<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/update_unit_status.php';

    $set_id = trim($_POST['set_id'] ?? '');
    if (!$set_id) throw new Exception("No Set ID provided");

    // =========================================================
    // 1. UNIQUENESS VALIDATION (EDITING) - IGNORING CONDEMNED
    // =========================================================
    $edit_specs_id = trim($_POST['specs_property'] ?? '');
    $edit_mon_id = trim($_POST['monitor_property'] ?? '');

    // Check System Unit ID
    if ($edit_specs_id !== '') {
        $check_sys = $conn->prepare("
            SELECT s.set_id 
            FROM specs s 
            JOIN units u ON s.set_id = u.set_id 
            WHERE s.specs_property = ? 
            AND s.set_id != ? 
            AND (u.set_status != 'Condemned' OR u.set_status IS NULL)
        ");
        $check_sys->bind_param("ss", $edit_specs_id, $set_id);
        $check_sys->execute();
        if ($check_sys->get_result()->num_rows > 0) {
            throw new Exception("The System Unit Property ID '$edit_specs_id' is already in use by another active unit.");
        }
        $check_sys->close();
    }

    // Check Monitor ID
    if ($edit_mon_id !== '') {
        $check_mon = $conn->prepare("
            SELECT p.set_id 
            FROM peripherals p 
            JOIN units u ON p.set_id = u.set_id 
            WHERE p.monitor_property = ? 
            AND p.set_id != ? 
            AND (u.set_status != 'Condemned' OR u.set_status IS NULL)
        ");
        $check_mon->bind_param("ss", $edit_mon_id, $set_id);
        $check_mon->execute();
        if ($check_mon->get_result()->num_rows > 0) {
            throw new Exception("The Monitor Property ID '$edit_mon_id' is already in use by another active monitor.");
        }
        $check_mon->close();
    }

    // =========================================================
    // 2. CHECK CURRENT STATUS
    // =========================================================
    $status_check = $conn->query("SELECT set_status FROM units WHERE set_id = '$set_id'");
    $current_status = '';
    if ($status_check && $status_check->num_rows > 0) {
        $current_status = $status_check->fetch_assoc()['set_status'];
    }

    // =========================================================
    // 3. SAFELY PARSE NUMBERS
    // =========================================================
    $com_age = !empty($_POST['com_age']) ? (int)$_POST['com_age'] : 0;
    $num_repair = !empty($_POST['num_repair']) ? (int)$_POST['num_repair'] : 0;
    $usb_ports = !empty($_POST['usb_ports']) ? (int)$_POST['usb_ports'] : 0;

    // =========================================================
    // 4. UPDATE MAIN UNIT TABLE (Only if NOT Condemned)
    // =========================================================
    if ($current_status !== 'Condemned') {
        // Update all other tables first, then let updateUnitStatus() determine the final status
        $stmt_specs = $conn->prepare("UPDATE specs SET specs_property=?, specs_brand=?, specs_purchase=?, specs_cpu=?, specs_os=?, specs_gpu=?, specs_ram=?, specs_storage=?, specs_capacity=? WHERE set_id=?");
        $stmt_specs->bind_param("ssssssssss", $_POST['specs_property'], $_POST['specs_brand'], $_POST['specs_purchase'], $_POST['specs_cpu'], $_POST['specs_os'], $_POST['specs_gpu'], $_POST['specs_ram'], $_POST['specs_storage'], $_POST['specs_capacity'], $set_id);
        $stmt_specs->execute();
        $stmt_specs->close();

        $stmt_ports = $conn->prepare("UPDATE ports SET usb_status=?, usb_ports=?, mic_status=?, headphone_status=?, inline_status=?, wifi_status=?, hdmi_status=?, display_status=?, ethernet_status=? WHERE set_id=?");
        $stmt_ports->bind_param("sissssssss", $_POST['usb_status'], $usb_ports, $_POST['mic_status'], $_POST['headphone_status'], $_POST['inline_status'], $_POST['wifi_status'], $_POST['hdmi_status'], $_POST['display_status'], $_POST['ethernet_status'], $set_id);
        $stmt_ports->execute();
        $stmt_ports->close();

        $stmt_health = $conn->prepare("UPDATE health SET com_age=?, num_repair=?, disk_health=?, power_health=? WHERE set_id=?");
        $stmt_health->bind_param("iisss", $com_age, $num_repair, $_POST['disk_health'], $_POST['power_health'], $set_id);
        $stmt_health->execute();
        $stmt_health->close();

        $stmt_peripherals = $conn->prepare("UPDATE peripherals SET monitor_property=?, monitor_brand=?, monitor_status=?, keyboard_brand=?, keyboard_status=?, mouse_brand=?, mouse_status=?, avr_brand=?, avr_status=? WHERE set_id=?");
        $stmt_peripherals->bind_param("ssssssssss", $_POST['monitor_property'], $_POST['monitor_brand'], $_POST['monitor_status'], $_POST['keyboard_brand'], $_POST['keyboard_status'], $_POST['mouse_brand'], $_POST['mouse_status'], $_POST['avr_brand'], $_POST['avr_status'], $set_id);
        $stmt_peripherals->execute();
        $stmt_peripherals->close();

        // NOW call the status update function to determine correct status
        updateUnitStatus($conn, $set_id);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
