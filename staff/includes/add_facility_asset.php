<?php
// Ensure NO whitespace or new lines exist before the <?php tag
session_start(); // CRITICAL: Added so we can grab the user's name for the log!

require_once __DIR__ . '/../../includes/db.php';

// Turn off error reporting to screen so it doesn't break JSON
error_reporting(0); 
header('Content-Type: application/json');

try {
    // Collect data
    $asset_id    = $_POST['asset_id'] ?? null;
    $asset_tag   = $_POST['asset_tag'] ?? null;
    $asset_name  = $_POST['asset_name'] ?? null;
    $asset_brand = $_POST['asset_brand'] ?? null;
    $asset_status = $_POST['asset_status'] ?? 'Working';
    $lab_id      = $_POST['lab_id'] ?? null;

    if (!$asset_id || !$lab_id) {
        throw new Exception("Missing required identification data.");
    }

    // 1. Fetch Room Name AND Lab Name for the Activity Log
    $lab_room = 'Unknown';
    $lab_full_name = 'Unknown Lab';
    
    $roomQuery = $conn->prepare("SELECT lab_name, lab_room FROM laboratories WHERE lab_id = ?");
    $roomQuery->bind_param("s", $lab_id);
    $roomQuery->execute();
    $roomResult = $roomQuery->get_result()->fetch_assoc();
    
    if ($roomResult) {
        $lab_room = $roomResult['lab_room'];
        $lab_full_name = $roomResult['lab_name'] . " (Room " . $lab_room . ")";
    }
    $roomQuery->close();

    // 2. Insert Asset - Property ID is GONE. Total 7 columns + latest_activity.
    $stmt = $conn->prepare("INSERT INTO assets (asset_id, asset_name, asset_tag, asset_brand, asset_status, lab_id, lab_room, latest_activity) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    // Total 7 "s" (strings)
    $stmt->bind_param("sssssss", $asset_id, $asset_name, $asset_tag, $asset_brand, $asset_status, $lab_id, $lab_room);

    
    if ($stmt->execute()) {
        $stmt->close();
        
        // ---------------------------------------------------------
        // --- NEW: LOG THE "ADD" ACTIVITY ---
        // ---------------------------------------------------------
        $actor = $_SESSION['user_name'] ?? 'Admin';
        $affected = 'Facility Asset';
        $action = 'Add';
        $remarks = "Facility Asset added with Asset Tag - FA-" . str_pad($asset_tag, 2, "0", STR_PAD_LEFT) . " in " . $lab_full_name;
        $status = 'Added';

        $history_stmt = $conn->prepare("INSERT INTO asset_history (asset_id, lab_id, report_date, report_actor, report_affected, report_action, report_remarks, report_status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");
        $history_stmt->bind_param("sssssss", $asset_id, $lab_id, $actor, $affected, $action, $remarks, $status);
        $history_stmt->execute();
        $history_stmt->close();
        // ---------------------------------------------------------

        echo json_encode(['success' => true]);
        exit; // Stop execution immediately after sending JSON
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}