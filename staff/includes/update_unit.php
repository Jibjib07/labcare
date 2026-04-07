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
    // 1. FETCH CURRENT DATA 
    // =========================================================
    $status_check = $conn->prepare("SELECT set_status, lab_id FROM units WHERE set_id = ?");
    $status_check->bind_param("s", $set_id);
    $status_check->execute();
    $row = $status_check->get_result()->fetch_assoc();
    $current_status = $row['set_status'] ?? 'Working';
    $lab_id = $row['lab_id'] ?? 0;
    $status_check->close();

    // Capture manual status from UI (if sent)
    $manual_status = $_POST['set_status'] ?? $current_status;

    // =========================================================
    // 2. DEFINE FALLBACKS (Prevents NULL Database Errors)
    // =========================================================
    $usb_ports  = (int)($_POST['usb_ports'] ?? 0);
    $usb_s      = $_POST['usb_status'] ?? 'Working';
    $wifi_s     = $_POST['wifi_status'] ?? 'Working';
    $mic_s      = $_POST['mic_status'] ?? 'Working';
    $hdmi_s     = $_POST['hdmi_status'] ?? 'Working';
    $hphone_s   = $_POST['headphone_status'] ?? 'Working';
    $display_s  = $_POST['display_status'] ?? 'Working';
    $inline_s   = $_POST['inline_status'] ?? 'Working';
    $ethernet_s = $_POST['ethernet_status'] ?? 'Working';

    // (Property IDs removed from peripherals)
    $mon_b      = $_POST['monitor_brand'] ?? '';
    $mon_s      = $_POST['monitor_status'] ?? 'Working';
    $kb_b       = $_POST['keyboard_brand'] ?? '';
    $kb_s       = $_POST['keyboard_status'] ?? 'Working';
    $ms_b       = $_POST['mouse_brand'] ?? '';
    $ms_s       = $_POST['mouse_status'] ?? 'Working';
    $avr_b      = $_POST['avr_brand'] ?? '';
    $avr_s      = $_POST['avr_status'] ?? 'Working';

    $disk_h     = $_POST['disk_health'] ?? 'Healthy';
    $power_h    = $_POST['power_health'] ?? 'Working';

    // =========================================================
    // 3. UPDATE DATABASE (TRANSACTIONAL)
    // =========================================================
    if ($current_status !== 'Condemned') {
        $conn->begin_transaction();

        // A. Units Table
        $stmt_u = $conn->prepare("UPDATE units SET set_status = ?, latest_activity = NOW() WHERE set_id = ?");
        $stmt_u->bind_param("ss", $manual_status, $set_id);
        $stmt_u->execute();
        $stmt_u->close();

        // B. Specs Table (specs_property removed)
        $stmt_specs = $conn->prepare("UPDATE specs SET specs_brand=?, specs_purchase=?, specs_cpu=?, specs_os=?, specs_gpu=?, specs_ram=?, specs_storage=?, specs_capacity=? WHERE set_id=?");
        $stmt_specs->bind_param(
            "sssssssss",
            $_POST['specs_brand'],
            $_POST['specs_purchase'],
            $_POST['specs_cpu'],
            $_POST['specs_os'],
            $_POST['specs_gpu'],
            $_POST['specs_ram'],
            $_POST['specs_storage'],
            $_POST['specs_capacity'],
            $set_id
        );
        $stmt_specs->execute();
        $stmt_specs->close();

        // C. Ports Table
        $stmt_ports = $conn->prepare("UPDATE ports SET usb_ports=?, usb_status=?, wifi_status=?, mic_status=?, hdmi_status=?, headphone_status=?, display_status=?, inline_status=?, ethernet_status=? WHERE set_id=?");
        $stmt_ports->bind_param("isssssssss", $usb_ports, $usb_s, $wifi_s, $mic_s, $hdmi_s, $hphone_s, $display_s, $inline_s, $ethernet_s, $set_id);
        $stmt_ports->execute();
        $stmt_ports->close();

        // D. Peripherals Table (monitor_property removed)
        $stmt_peripherals = $conn->prepare("UPDATE peripherals SET monitor_brand=?, monitor_status=?, keyboard_brand=?, keyboard_status=?, mouse_brand=?, mouse_status=?, avr_brand=?, avr_status=? WHERE set_id=?");
        $stmt_peripherals->bind_param("sssssssss", $mon_b, $mon_s, $kb_b, $kb_s, $ms_b, $ms_s, $avr_b, $avr_s, $set_id);
        $stmt_peripherals->execute();
        $stmt_peripherals->close();

        // E. Health Table
        $stmt_health = $conn->prepare("UPDATE health SET disk_health=?, power_health=? WHERE set_id=?");
        $stmt_health->bind_param("sss", $disk_h, $power_h, $set_id);
        $stmt_health->execute();
        $stmt_health->close();

        // F. Auto-Calculator
        updateUnitStatus($conn, $set_id);

        // G. FETCH FINAL STATUS & LOG HISTORY
        $final_status_query = $conn->query("SELECT set_status FROM units WHERE set_id = '$set_id'");
        $final_status = $final_status_query->fetch_assoc()['set_status'] ?? 'Working';
        $actor = $_SESSION['user_name'] ?? 'Admin';
        $action = "Admin Edit/Update";

        $stmt_hist = $conn->prepare("INSERT INTO unit_history (set_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");

        // 1. Log General Specs changes
        $general_remarks = trim($_POST['general_remarks'] ?? '');
        $general_affected = trim($_POST['report_affected_general'] ?? '');

        if (!empty($general_remarks)) {
            // FIX: Force the status to "Updated" so it doesn't trigger the For Repair pill in the UI
            $general_status = "Updated";
            $stmt_hist->bind_param("sisssss", $set_id, $lab_id, $actor, $general_affected, $action, $general_remarks, $general_status);
            $stmt_hist->execute();
        }

        // 2. Log Individual Status Changes
        // B. Log Individual Status Changes
        $status_logs_json = $_POST['status_logs'] ?? '[]';
        $status_logs_array = json_decode($status_logs_json, true);

        if (is_array($status_logs_array) && count($status_logs_array) > 0) {
            foreach ($status_logs_array as $log) {
                $component_name = $log['component'];
                $raw_notes = trim($log['remark'] ?? '');
                $comp_status = $log['status'] ?? 'Updated';

                // Format the string for the remarks box
                $final_notes = !empty($raw_notes) ? $raw_notes : "None provided.";
                $formatted_remark = "Marked as " . $comp_status . ". Notes: " . $final_notes;

                $log_action = "Status Update";

                // --- NEW: Convert broken states to "Issue Reported" for the DB pill! ---
                $db_report_status = $comp_status;
                if (in_array($comp_status, ['Not Working', 'Not Working/Missing', 'Poor', 'For Repair'])) {
                    $db_report_status = 'Issue Reported';
                }

                // Notice we are passing $db_report_status at the very end now
                $stmt_hist->bind_param("sisssss", $set_id, $lab_id, $actor, $component_name, $log_action, $formatted_remark, $db_report_status);
                $stmt_hist->execute();
            }
        }

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