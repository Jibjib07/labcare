<?php

/**
 * UPDATE UNIT STATUS UTILITY
 * Determines and updates unit status based on:
 * 1. Not Working/Poor components -> "For Repair"
 * 2. Otherwise -> "Working"
 */

function updateUnitStatus($conn, $set_id)
{
    try {
        // =========================================================
        // 1. CHECK FOR ANY BROKEN STATUS IN KEY TABLES
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
                // FIX: Look for 'Not Working' (and keep 'For Repair' for legacy DB data fallback)
                if ($value === 'Not Working' || $value === 'For Repair') {
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
                    // FIX: Look for 'Not Working' here too
                    if ($value === 'Not Working' || $value === 'For Repair') {
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
                // FIX: Both disk and power health use 'Poor'
                if ($health_row['disk_health'] === 'Poor' || $health_row['power_health'] === 'Poor') {
                    $has_repair = true;
                }
            }
            $health_check->close();
        }

        // If ANY component is broken, mark the whole unit as "For Repair"
        if ($has_repair) {
            $new_status = "For Repair";
            $stmt = $conn->prepare("UPDATE units SET set_status = ? WHERE set_id = ?");
            $stmt->bind_param("ss", $new_status, $set_id);
            $stmt->execute();
            $stmt->close();
            return $new_status;
        }

        // =========================================================
        // 2. ELSE, STATUS IS "Working"
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