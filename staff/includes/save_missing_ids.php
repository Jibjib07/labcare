<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/update_unit_status.php';

    $payload_json = $_POST['payload'] ?? '';
    $data = json_decode($payload_json, true);

    if (!empty($data) && is_array($data)) {

        // =========================================================
        // 1. UNIQUENESS VALIDATION (BULK MODAL) - IGNORING CONDEMNED
        // =========================================================
        $seen_sys_ids = [];
        $seen_mon_ids = [];

        // Prepare queries that JOIN the units table to check the status
        $stmt_check_sys = $conn->prepare("
            SELECT s.set_id 
            FROM specs s 
            JOIN units u ON s.set_id = u.set_id 
            WHERE s.specs_property = ? 
            AND s.set_id != ? 
            AND (u.set_status != 'Condemned' OR u.set_status IS NULL)
        ");

        $stmt_check_mon = $conn->prepare("
            SELECT p.set_id 
            FROM peripherals p 
            JOIN units u ON p.set_id = u.set_id 
            WHERE p.monitor_property = ? 
            AND p.set_id != ? 
            AND (u.set_status != 'Condemned' OR u.set_status IS NULL)
        ");

        foreach ($data as $unit) {
            $set_id = $unit['set_id'];
            $sys_id = trim($unit['specs_property']);
            $mon_id = trim($unit['monitor_property']);

            // A. Check for duplicates WITHIN the form they just typed
            if ($sys_id !== '') {
                if (in_array($sys_id, $seen_sys_ids)) throw new Exception("Duplicate System Unit ID '$sys_id' detected in your form.");
                $seen_sys_ids[] = $sys_id;
            }
            if ($mon_id !== '') {
                if (in_array($mon_id, $seen_mon_ids)) throw new Exception("Duplicate Monitor ID '$mon_id' detected in your form.");
                $seen_mon_ids[] = $mon_id;
            }

            // B. Check against the Database
            if ($sys_id !== '') {
                $stmt_check_sys->bind_param("ss", $sys_id, $set_id);
                $stmt_check_sys->execute();
                if ($stmt_check_sys->get_result()->num_rows > 0) {
                    throw new Exception("System Unit ID '$sys_id' is already assigned to another active unit in the database.");
                }
            }
            if ($mon_id !== '') {
                $stmt_check_mon->bind_param("ss", $mon_id, $set_id);
                $stmt_check_mon->execute();
                if ($stmt_check_mon->get_result()->num_rows > 0) {
                    throw new Exception("Monitor ID '$mon_id' is already assigned to another active monitor in the database.");
                }
            }
        }
        $stmt_check_sys->close();
        $stmt_check_mon->close();

        // =========================================================
        // 2. IF VALIDATION PASSES, SAVE EVERYTHING
        // =========================================================
        $stmt_specs = $conn->prepare("UPDATE specs SET specs_property = ? WHERE set_id = ?");
        $stmt_peripherals = $conn->prepare("UPDATE peripherals SET monitor_property = ? WHERE set_id = ?");

        foreach ($data as $unit) {
            $set_id = $unit['set_id'];
            $specs_id = trim($unit['specs_property']);
            $mon_id = trim($unit['monitor_property']);

            $stmt_specs->bind_param("ss", $specs_id, $set_id);
            $stmt_specs->execute();

            $stmt_peripherals->bind_param("ss", $mon_id, $set_id);
            $stmt_peripherals->execute();
        }

        $stmt_specs->close();
        $stmt_peripherals->close();

        // =========================================================
        // 3. UPDATE EACH UNIT'S STATUS BASED ON NEW PROPERTY IDs
        // =========================================================
        foreach ($data as $unit) {
            $set_id = $unit['set_id'];
            // Since property IDs are now filled, determine status based on repairs/age only
            updateUnitStatus($conn, $set_id);
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No data provided or invalid format.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
