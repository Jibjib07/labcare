<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../../includes/db.php';

    // =========================================================
    // 1. UNIQUENESS VALIDATION (ADDING) - IGNORING CONDEMNED
    // =========================================================
    $new_specs_id = trim($_POST['property_id'] ?? '');
    $new_mon_id = trim($_POST['monitor_property'] ?? '');

    // Check System Unit ID
    if ($new_specs_id !== '') {
        $check_sys = $conn->prepare("
            SELECT s.set_id 
            FROM specs s 
            JOIN units u ON s.set_id = u.set_id 
            WHERE s.specs_property = ? 
            AND (u.set_status != 'Condemned' OR u.set_status IS NULL)
        ");
        $check_sys->bind_param("s", $new_specs_id);
        $check_sys->execute();
        if ($check_sys->get_result()->num_rows > 0) {
            throw new Exception("The System Unit Property ID '$new_specs_id' is already assigned to an active unit.");
        }
        $check_sys->close();
    }

    // Check Monitor ID
    if ($new_mon_id !== '') {
        $check_mon = $conn->prepare("
            SELECT p.set_id 
            FROM peripherals p 
            JOIN units u ON p.set_id = u.set_id 
            WHERE p.monitor_property = ? 
            AND (u.set_status != 'Condemned' OR u.set_status IS NULL)
        ");
        $check_mon->bind_param("s", $new_mon_id);
        $check_mon->execute();
        if ($check_mon->get_result()->num_rows > 0) {
            throw new Exception("The Monitor Property ID '$new_mon_id' is already assigned to an active monitor.");
        }
        $check_mon->close();
    }

    // ---------------------------------------------------------
    // STEP 1: COLLECT ALL POST DATA FIRST
    // ---------------------------------------------------------

    $lab_id = intval($_POST['lab_id'] ?? 0);
    $lab_room = $_POST['lab_room'] ?? '';
    $statuses = $_POST['statuses'] ?? [];

    // Specs Data
    $prop_id = $_POST['property_id'] ?? '';
    $cpu = $_POST['cpu'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $os = $_POST['os'] ?? '';
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : NULL;
    $gpu = $_POST['gpu'] ?? '';
    $ram = $_POST['ram'] ?? '';
    $storage = $_POST['storage'] ?? '';
    $capacity = $_POST['capacity'] ?? '';

    // Ports Data
    $usb_ports = isset($_POST['usb_ports']) ? intval($_POST['usb_ports']) : 0;
    $usb_status = $_POST['usb_status'] ?? 'Working';
    $wifi_status = $_POST['wifi_status'] ?? 'Working';
    $mic_status = $_POST['mic_status'] ?? 'Working';
    $hdmi_status = $_POST['hdmi_status'] ?? 'Working';
    $headphone_status = $_POST['headphone_status'] ?? 'Working';
    $display_status = $_POST['display_status'] ?? 'Working';
    $inline_status = $_POST['inline_status'] ?? 'Working';
    $ethernet_status = $_POST['ethernet_status'] ?? 'Working';

    // Health Data
    $com_age = isset($_POST['com_age']) ? intval($_POST['com_age']) : 0;
    $num_repair = isset($_POST['num_repair']) ? intval($_POST['num_repair']) : 0;
    $disk_health = $_POST['disk_health'] ?? 'Working';
    $power_health = $_POST['power_health'] ?? 'Working';

    // Peripherals Data
    $monitor_property = $_POST['monitor_property'] ?? '';
    $monitor_brand = $_POST['monitor_brand'] ?? '';
    $monitor_status = $_POST['monitor_status'] ?? 'Working';
    $keyboard_brand = $_POST['keyboard_brand'] ?? '';
    $keyboard_status = $_POST['keyboard_status'] ?? 'Working';
    $mouse_brand = $_POST['mouse_brand'] ?? '';
    $mouse_status = $_POST['mouse_status'] ?? 'Working';
    $avr_brand = $_POST['avr_brand'] ?? '';
    $avr_status = $_POST['avr_status'] ?? 'Working';

    // ---------------------------------------------------------
    // STEP 2: GENERATE THE SET_ID AND DETERMINE STATUS
    // ---------------------------------------------------------

    // Catch the JSON array of tags
    $unit_tags = json_decode($_POST['unit_tags'] ?? '[]', true);

    // Fallback if not JSON (Single Unit Mode fallback)
    if (empty($unit_tags) && !empty($_POST['unit_no'])) {
        $unit_tags = [$_POST['unit_no']];
    }

    $set_status = in_array('repair', $statuses) ? 'For Repair' : 'Working';

    $year = date("Y");
    $id_query = "SELECT set_id FROM units WHERE set_id LIKE 'SET_%_$year' ORDER BY set_id DESC LIMIT 1";
    $id_result = $conn->query($id_query);

    $num = 1;
    if ($id_result && $id_result->num_rows > 0) {
        $row = $id_result->fetch_assoc();
        $parts = explode('_', $row['set_id']);
        if (isset($parts[1])) {
            $num = intval($parts[1]) + 1;
        }
    }

    // ---------------------------------------------------------
    // STEP 3: EXECUTE ALL DATABASE INSERTS (THE LOOP)
    // ---------------------------------------------------------
    foreach ($unit_tags as $tag) {
        $new_set_id = "SET_" . str_pad($num, 4, '0', STR_PAD_LEFT) . "_" . $year;

        // A. Insert into `units` table (FIXED: Using $tag instead of $unit_tag)
        $stmt = $conn->prepare("INSERT INTO units (set_id, set_tag, set_status, lab_id, latest_maintenance, lab_room) VALUES (?, ?, ?, ?, NULL, ?)");
        $stmt->bind_param("sssis", $new_set_id, $tag, $set_status, $lab_id, $lab_room);

        if ($stmt->execute()) {
            // B. Insert into `specs` table (FIXED: Using $tag instead of $unit_tag)
            $stmt_specs = $conn->prepare("INSERT INTO specs (specs_property, specs_brand, specs_purchase, specs_cpu, specs_os, specs_gpu, specs_ram, specs_storage, specs_capacity, lab_room, set_tag, set_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_specs->bind_param("ssssssssssss", $prop_id, $brand, $purchase_date, $cpu, $os, $gpu, $ram, $storage, $capacity, $lab_room, $tag, $new_set_id);
            $stmt_specs->execute();

            // C. Insert into `ports` table
            $stmt_ports = $conn->prepare("INSERT INTO ports (usb_status, usb_ports, mic_status, headphone_status, inline_status, wifi_status, hdmi_status, display_status, ethernet_status, set_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_ports->bind_param("sissssssss", $usb_status, $usb_ports, $mic_status, $headphone_status, $inline_status, $wifi_status, $hdmi_status, $display_status, $ethernet_status, $new_set_id);
            $stmt_ports->execute();

            // D. Insert into `health` table
            $stmt_health = $conn->prepare("INSERT INTO health (com_age, num_repair, disk_health, power_health, set_id) VALUES (?, ?, ?, ?, ?)");
            $stmt_health->bind_param("iisss", $com_age, $num_repair, $disk_health, $power_health, $new_set_id);
            $stmt_health->execute();

            // E. Insert into `peripherals` table
            $stmt_peripherals = $conn->prepare("INSERT INTO peripherals (monitor_property, monitor_brand, monitor_status, keyboard_brand, keyboard_status, mouse_brand, mouse_status, avr_brand, avr_status, set_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_peripherals->bind_param("ssssssssss", $monitor_property, $monitor_brand, $monitor_status, $keyboard_brand, $keyboard_status, $mouse_brand, $mouse_status, $avr_brand, $avr_status, $new_set_id);
            $stmt_peripherals->execute();
        }

        // FIXED: Increment the ID number for the next loop!
        $num++;
    } // <-- FIXED: Added the missing closing bracket for the foreach loop!

    // ---------------------------------------------------------
    // STEP 4: SEND SUCCESS MESSAGE BACK TO JS 
    // (FIXED: Moved outside the loop so it only sends once!)
    // ---------------------------------------------------------
    echo json_encode(['success' => true, 'message' => 'Units added successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
