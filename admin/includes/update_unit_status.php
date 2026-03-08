<?php

/**
 * UPDATE UNIT STATUS UTILITY
 * 
 * Determines and updates unit status based on:
 * 1. For Repair components -> "For Repair"
 * 2. Age >= 5 years -> "For Condemn"
 * 3. Otherwise -> "Working"
 * 
 * Note: "No Property ID" status is determined by frontend based on missing property IDs
 */

function updateUnitStatus($conn, $set_id)
{
    try {
        // =========================================================
        // 1. CHECK FOR ANY "For Repair" STATUS IN KEY TABLES
        // =========================================================
        $has_repair = false;

        // Check ports table
        $ports_check = $conn->prepare("
            SELECT usb_status, wifi_status, mic_status, hdmi_status,
                   headphone_status, display_status, inline_status, ethernet_status
            FROM ports WHERE set_id = ?
        ");
        $ports_check->bind_param("s", $set_id);
        $ports_check->execute();
        $ports_result = $ports_check->get_result();
        if ($ports_row = $ports_result->fetch_assoc()) {
            foreach ($ports_row as $value) {
                if ($value === 'For Repair') {
                    $has_repair = true;
                    break;
                }
            }
        }
        $ports_check->close();

        // Check peripherals table
        if (!$has_repair) {
            $peripherals_status_check = $conn->prepare("
                SELECT monitor_status, keyboard_status, mouse_status, avr_status
                FROM peripherals WHERE set_id = ?
            ");
            $peripherals_status_check->bind_param("s", $set_id);
            $peripherals_status_check->execute();
            $periph_result = $peripherals_status_check->get_result();
            if ($periph_row = $periph_result->fetch_assoc()) {
                foreach ($periph_row as $value) {
                    if ($value === 'For Repair') {
                        $has_repair = true;
                        break;
                    }
                }
            }
            $peripherals_status_check->close();
        }

        // Check health table
        if (!$has_repair) {
            $health_check = $conn->prepare("SELECT disk_health, power_health FROM health WHERE set_id = ?");
            $health_check->bind_param("s", $set_id);
            $health_check->execute();
            $health_result = $health_check->get_result();
            if ($health_row = $health_result->fetch_assoc()) {
                if ($health_row['disk_health'] === 'For Repair' || $health_row['power_health'] === 'For Repair') {
                    $has_repair = true;
                }
            }
            $health_check->close();
        }

        if ($has_repair) {
            $new_status = "For Repair";
            $stmt = $conn->prepare("UPDATE units SET set_status = ? WHERE set_id = ?");
            $stmt->bind_param("ss", $new_status, $set_id);
            $stmt->execute();
            $stmt->close();
            return $new_status;
        }

        // =========================================================
        // 2. CHECK IF UNIT AGE >= 5 YEARS
        // =========================================================
        $age_check = $conn->prepare("
            SELECT s.specs_purchase
            FROM specs s
            WHERE s.set_id = ?
        ");
        $age_check->bind_param("s", $set_id);
        $age_check->execute();
        $age_result = $age_check->get_result();
        if ($age_row = $age_result->fetch_assoc()) {
            $purchase_date = new DateTime($age_row['specs_purchase']);
            $today = new DateTime();
            $age_years = $today->diff($purchase_date)->y;

            if ($age_years >= 5) {
                $new_status = "For Condemn";
                $stmt = $conn->prepare("UPDATE units SET set_status = ? WHERE set_id = ?");
                $stmt->bind_param("ss", $new_status, $set_id);
                $stmt->execute();
                $stmt->close();
                $age_check->close();
                return $new_status;
            }
        }
        $age_check->close();

        // =========================================================
        // 3. ELSE, STATUS IS "Working"
        // =========================================================
        $new_status = "Working";
        $stmt = $conn->prepare("UPDATE units SET set_status = ? WHERE set_id = ?");
        $stmt->bind_param("ss", $new_status, $set_id);
        $stmt->execute();
        $stmt->close();
        return $new_status;
    } catch (Exception $e) {
        error_log("Error updating unit status: " . $e->getMessage());
        return null;
    }
}
