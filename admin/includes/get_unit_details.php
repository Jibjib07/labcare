<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Adjust this path if your db.php is located somewhere else!
    require_once __DIR__ . '/../../includes/db.php';

    $set_id = $_GET['set_id'] ?? '';

    if (empty($set_id)) {
        echo json_encode(['success' => false, 'error' => 'No ID provided']);
        exit;
    }

    // Join all 5 tables based on the set_id
    $query = "
        SELECT u.set_id, u.set_tag, u.set_status, u.lab_room,
               s.specs_property, s.specs_brand, s.specs_purchase, s.specs_cpu, s.specs_os, s.specs_gpu, s.specs_ram, s.specs_storage, s.specs_capacity,
               p.usb_status, p.usb_ports, p.mic_status, p.headphone_status, p.inline_status, p.wifi_status, p.hdmi_status, p.display_status, p.ethernet_status,
               h.com_age, h.num_repair, h.disk_health, h.power_health,
               per.monitor_property, per.monitor_brand, per.monitor_status, per.keyboard_brand, per.keyboard_status, per.mouse_brand, per.mouse_status, per.avr_brand, per.avr_status
        FROM units u
        LEFT JOIN specs s ON u.set_id = s.set_id
        LEFT JOIN ports p ON u.set_id = p.set_id
        LEFT JOIN health h ON u.set_id = h.set_id
        LEFT JOIN peripherals per ON u.set_id = per.set_id
        WHERE u.set_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $set_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unit not found']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
