<?php
session_start();

// Correct path: goes up from admin/includes -> admin -> labcare -> includes/db.php
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'error' => 'No ID provided.']);
    exit;
}

$components = [];
$reporter_remarks = 'No remarks provided.'; // Default message

try {
    // 1. GET THE REPORTER'S LATEST REMARKS (If it exists)
    if ($type === 'pc') {
        $stmt = $conn->prepare("SELECT report_remarks FROM unit_history WHERE set_id=? AND report_action='Report' ORDER BY report_date DESC LIMIT 1");
    } else {
        $stmt = $conn->prepare("SELECT report_remarks FROM asset_history WHERE asset_id=? AND report_action='Report' ORDER BY report_date DESC LIMIT 1");
    }

    if ($stmt) {
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            // Only overwrite if the reporter actually typed something
            if (!empty(trim($row['report_remarks']))) {
                $reporter_remarks = $row['report_remarks'];
            }
        }
        $stmt->close();
    }

    // 2. FIND ALL CURRENTLY BROKEN COMPONENTS
    if ($type === 'pc') {
        // Scan Ports
        $res = $conn->query("SELECT * FROM ports WHERE set_id='$id'");
        if ($res && $row = $res->fetch_assoc()) {
            $port_map = [
                'usb_status' => 'USB Ports',
                'wifi_status' => 'Wi-Fi Card',
                'mic_status' => 'Microphone Jack',
                'hdmi_status' => 'HDMI Port',
                'headphone_status' => 'Headphone Jack',
                'display_status' => 'Display Port',
                'inline_status' => 'In-Line Jack',
                'ethernet_status' => 'Ethernet Port'
            ];
            foreach ($port_map as $col => $name) {
                if (isset($row[$col]) && $row[$col] === 'For Repair') {
                    $components[] = ['db_column' => $col, 'name' => $name, 'reporter_remarks' => $reporter_remarks];
                }
            }
        }

        // Scan Health
        $res = $conn->query("SELECT * FROM health WHERE set_id='$id'");
        if ($res && $row = $res->fetch_assoc()) {
            // FIX: Support both 'Poor' (new format) and 'For Repair' (legacy format)
            if (isset($row['disk_health']) && ($row['disk_health'] === 'Poor' || $row['disk_health'] === 'For Repair')) {
                $components[] = ['db_column' => 'disk_health', 'name' => 'Disk Health (SMART)', 'reporter_remarks' => $reporter_remarks];
            }
            if (isset($row['power_health']) && $row['power_health'] === 'For Repair') {
                $components[] = ['db_column' => 'power_health', 'name' => 'Power Supply', 'reporter_remarks' => $reporter_remarks];
            }
        }

        // Scan Peripherals
        $res = $conn->query("SELECT * FROM peripherals WHERE set_id='$id'");
        if ($res && $row = $res->fetch_assoc()) {
            if (isset($row['monitor_status']) && $row['monitor_status'] === 'For Repair') $components[] = ['db_column' => 'monitor_status', 'name' => 'Monitor', 'reporter_remarks' => $reporter_remarks];
            if (isset($row['mouse_status']) && $row['mouse_status'] === 'For Repair') $components[] = ['db_column' => 'mouse_status', 'name' => 'Mouse', 'reporter_remarks' => $reporter_remarks];
            if (isset($row['keyboard_status']) && $row['keyboard_status'] === 'For Repair') $components[] = ['db_column' => 'keyboard_status', 'name' => 'Keyboard', 'reporter_remarks' => $reporter_remarks];
            if (isset($row['avr_status']) && $row['avr_status'] === 'For Repair') $components[] = ['db_column' => 'avr_status', 'name' => 'AVR', 'reporter_remarks' => $reporter_remarks];
        }

        // --- FALLBACK: If NO components are broken, but the parent unit is still For Repair ---
        if (empty($components)) {
            $res = $conn->query("SELECT set_status FROM units WHERE set_id='$id'");
            if ($res && $row = $res->fetch_assoc()) {
                if (isset($row['set_status']) && $row['set_status'] === 'For Repair') {
                    $components[] = ['db_column' => 'general_system', 'name' => 'Entire Unit (Unspecified)', 'reporter_remarks' => $reporter_remarks];
                }
            }
        }
    } else {
        // Facility Asset Logic
        $stmt = $conn->prepare("SELECT asset_status, asset_name FROM assets WHERE asset_id=?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $row = $res->fetch_assoc()) {
            // Check for 'For Repair' or 'Missing Parts'
            if ($row['asset_status'] !== 'Working' && $row['asset_status'] !== 'Condemned') {
                $components[] = [
                    'db_column' => 'asset_status',
                    'name' => $row['asset_name'],
                    'reporter_remarks' => $reporter_remarks // Now correctly passed!
                ];
            }
        }
        $stmt->close();
    }

    echo json_encode(['success' => true, 'components' => $components]);
} catch (Exception $e) {
    // This catches fatal DB errors and turns them into clean JSON so the frontend doesn't crash!
    echo json_encode(['success' => false, 'error' => 'Backend Error: ' . $e->getMessage()]);
}
