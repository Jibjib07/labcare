<?php
/**
 * RE-CALCULATE UNIT STATUS
 * Checks all components and the history for Unspecified issues.
 */
function updateUnitStatus($conn, $set_id) {
    // 1. Check Hardware Tables (Ports, Health, Peripherals)
    $query = "SELECT p.*, h.*, per.* FROM units u 
              LEFT JOIN ports p ON u.set_id = p.set_id 
              LEFT JOIN health h ON u.set_id = h.set_id 
              LEFT JOIN peripherals per ON u.set_id = per.set_id 
              WHERE u.set_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $set_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    $is_broken = false;

    // List of columns that indicate a hardware failure
    $status_cols = [
        'usb_status', 'wifi_status', 'mic_status', 'hdmi_status', 
        'headphone_status', 'display_status', 'inline_status', 'ethernet_status',
        'disk_health', 'power_health', 'monitor_status', 
        'mouse_status', 'keyboard_status', 'avr_status'
    ];

    foreach ($status_cols as $col) {
        if (isset($res[$col])) {
            $val = $res[$col];
            if (in_array($val, ['For Repair', 'Not Working', 'Poor', 'Not Working/Missing', 'Missing'])) {
                $is_broken = true;
                break;
            }
        }
    }

    // --- THE FIX: Check if there's an unresolved Unspecified issue ---
    if (!$is_broken) {
        $unspec_query = "SELECT report_status FROM unit_history 
                         WHERE set_id = ? AND report_affected = 'Unspecified' 
                         ORDER BY report_id DESC LIMIT 1";
        
        $unspec_stmt = $conn->prepare($unspec_query);
        $unspec_stmt->bind_param("s", $set_id);
        $unspec_stmt->execute();
        $unspec_res = $unspec_stmt->get_result();

        if ($unspec_row = $unspec_res->fetch_assoc()) {
            // If the latest Unspecified report is still an "Issue Reported", the PC is still broken
            if ($unspec_row['report_status'] === 'Issue Reported') {
                $is_broken = true;
            }
        }
        $unspec_stmt->close();
    }

    // 2. Update the master Units table
    $final_status = $is_broken ? 'For Repair' : 'Working';
    $update = $conn->prepare("UPDATE units SET set_status = ? WHERE set_id = ?");
    $update->bind_param("ss", $final_status, $set_id);
    $update->execute();
    $update->close();
}