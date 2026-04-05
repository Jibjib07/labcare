<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../../includes/db.php';

    // 1. Include the status updater!
    require_once __DIR__ . '/update_unit_status.php';

    // ---------------------------------------------------------
    // STEP 1: COLLECT ALL POST DATA FIRST
    // ---------------------------------------------------------

    $lab_id = intval($_POST['lab_id'] ?? 0);
    $lab_room = $_POST['lab_room'] ?? '';
    $statuses = $_POST['statuses'] ?? [];

    // Specs Data (Property ID removed)
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
    $power_health = $_POST['power_health'] ?? 'Working';

    // Force the correct naming convention for Disk Health!
    $disk_health = $_POST['disk_health'] ?? 'Healthy';
    if ($disk_health === 'Working') $disk_health = 'Healthy';
    if ($disk_health === 'For Repair') $disk_health = 'Poor';

    // Peripherals Data (Monitor Property ID removed)
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

    // Initial status guess (will be overwritten if wrong!)
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

        // A. Insert into `units` table
        $stmt = $conn->prepare("INSERT INTO units (set_id, set_tag, set_status, lab_id, latest_activity, lab_room) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("sssis", $new_set_id, $tag, $set_status, $lab_id, $lab_room);

        if ($stmt->execute()) {
            // B. Insert into `specs` table (specs_property removed)
            $stmt_specs = $conn->prepare("INSERT INTO specs (specs_brand, specs_purchase, specs_cpu, specs_os, specs_gpu, specs_ram, specs_storage, specs_capacity, lab_room, set_tag, set_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_specs->bind_param("sssssssssss", $brand, $purchase_date, $cpu, $os, $gpu, $ram, $storage, $capacity, $lab_room, $tag, $new_set_id);
            $stmt_specs->execute();

            // C. Insert into `ports` table
            $stmt_ports = $conn->prepare("INSERT INTO ports (usb_status, usb_ports, mic_status, headphone_status, inline_status, wifi_status, hdmi_status, display_status, ethernet_status, set_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_ports->bind_param("sissssssss", $usb_status, $usb_ports, $mic_status, $headphone_status, $inline_status, $wifi_status, $hdmi_status, $display_status, $ethernet_status, $new_set_id);
            $stmt_ports->execute();

            // D. Insert into `health` table
            $stmt_health = $conn->prepare("INSERT INTO health (com_age, num_repair, disk_health, power_health, set_id) VALUES (?, ?, ?, ?, ?)");
            $stmt_health->bind_param("iisss", $com_age, $num_repair, $disk_health, $power_health, $new_set_id);
            $stmt_health->execute();

            // E. Insert into `peripherals` table (monitor_property removed)
            $stmt_peripherals = $conn->prepare("INSERT INTO peripherals (monitor_brand, monitor_status, keyboard_brand, keyboard_status, mouse_brand, mouse_status, avr_brand, avr_status, set_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_peripherals->bind_param("sssssssss", $monitor_brand, $monitor_status, $keyboard_brand, $keyboard_status, $mouse_brand, $mouse_status, $avr_brand, $avr_status, $new_set_id);
            $stmt_peripherals->execute();

            // Force the system to scan the new PC for broken parts instantly!
            updateUnitStatus($conn, $new_set_id);
        }

        $num++;
    }

    // ---------------------------------------------------------
    // STEP 4: SEND SUCCESS MESSAGE BACK TO JS 
    // ---------------------------------------------------------
    echo json_encode(['success' => true, 'message' => 'Units added successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}