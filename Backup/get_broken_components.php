<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if (empty($type) || empty($id)) {
    echo json_encode(['success' => false, 'error' => 'Missing ID or Type']);
    exit;
}

$broken_components = [];

try {
    if ($type === 'pc') {
        // 1. Check for specific hardware table issues (Standard Logic)
        $query = "SELECT p.*, h.*, per.* FROM units u 
                  LEFT JOIN ports p ON u.set_id = p.set_id 
                  LEFT JOIN health h ON u.set_id = h.set_id 
                  LEFT JOIN peripherals per ON u.set_id = per.set_id 
                  WHERE u.set_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        $map = [
            'usb_status' => 'USB Ports', 'wifi_status' => 'Display Ports (HDMI/VGA)', 
            'mic_status' => 'RAM', 'hdmi_status' => 'Network', 
            'headphone_status' => 'Storage', 'display_status' => 'Operating System', 
            'inline_status' => 'Audio Ports', 'ethernet_status' => 'Drivers',
            'disk_health' => 'Disk Health', 'power_health' => 'System Performance',
            'monitor_status' => 'Monitor', 'mouse_status' => 'Mouse', 
            'keyboard_status' => 'Keyboard', 'avr_status' => 'Power (PSU/AVR)'
        ];

        foreach ($map as $col => $niceName) {
            if (isset($res[$col]) && in_array($res[$col], ['For Repair', 'Not Working', 'Poor', 'Not Working/Missing', 'Missing'])) {
                // Get the last reporter remarks for this specific part
                $rem_stmt = $conn->prepare("SELECT report_remarks FROM unit_history WHERE set_id = ? AND report_affected = ? ORDER BY report_id DESC LIMIT 1");
                $rem_stmt->bind_param("ss", $id, $niceName);
                $rem_stmt->execute();
                $rem_res = $rem_stmt->get_result()->fetch_assoc();
                
                $broken_components[] = [
                    'db_column' => $col,
                    'name' => $niceName,
                    'reporter_remarks' => $rem_res['report_remarks'] ?? 'No notes provided.'
                ];
            }
        }

        // --- THE FIX: Check for Unspecified Master Status ---
        $master_stmt = $conn->prepare("SELECT set_status FROM units WHERE set_id = ?");
        $master_stmt->bind_param("s", $id);
        $master_stmt->execute();
        $master_status = $master_stmt->get_result()->fetch_assoc()['set_status'];

        if ($master_status === 'For Repair') {
            // --- THE FIX: Fetch ALL unresolved Unspecified issues ---
            $unspec_stmt = $conn->prepare("SELECT report_id, report_remarks FROM unit_history 
                                        WHERE set_id = ? AND report_affected = 'Unspecified' 
                                        AND report_status = 'Issue Reported' 
                                        ORDER BY report_id ASC");
            $unspec_stmt->bind_param("s", $id);
            $unspec_stmt->execute();
            $unspec_res = $unspec_stmt->get_result();
            
            while ($rem = $unspec_res->fetch_assoc()) {
                $broken_components[] = [
                    // We use 'unspec_' + the actual ID so the resolve script knows exactly which one to fix
                    'db_column' => 'unspec_' . $rem['report_id'], 
                    'name' => 'Unspecified Issue',
                    'reporter_remarks' => $rem['report_remarks'] ?? 'General repair request.'
                ];
            }
            $unspec_stmt->close();
        }

    } else {
        // Facility Asset Logic
        $stmt = $conn->prepare("SELECT asset_status, asset_name FROM assets WHERE asset_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res['asset_status'] === 'For Repair') {
            $rem_stmt = $conn->prepare("SELECT report_remarks FROM asset_history WHERE asset_id = ? ORDER BY report_id DESC LIMIT 1");
            $rem_stmt->bind_param("s", $id);
            $rem_stmt->execute();
            $rem_res = $rem_stmt->get_result()->fetch_assoc();

            $broken_components[] = [
                'db_column' => 'asset_status',
                'name' => $res['asset_name'],
                'reporter_remarks' => $rem_res['report_remarks'] ?? 'No notes provided.'
            ];
        }
    }

    echo json_encode(['success' => true, 'components' => $broken_components]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}