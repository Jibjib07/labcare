<?php
// Ensure NO whitespace or new lines exist before the <?php tag
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

    // 1. Fetch Room Name
    $roomQuery = $conn->prepare("SELECT lab_room FROM laboratories WHERE lab_id = ?");
    $roomQuery->bind_param("s", $lab_id);
    $roomQuery->execute();
    $roomResult = $roomQuery->get_result()->fetch_assoc();
    $lab_room = $roomResult ? $roomResult['lab_room'] : 'Unknown';
    $roomQuery->close();

    // 2. Insert Asset - Property ID is GONE. Total 7 columns + latest_activity.
    $stmt = $conn->prepare("INSERT INTO assets (asset_id, asset_name, asset_tag, asset_brand, asset_status, lab_id, lab_room, latest_activity) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    // Total 7 "s" (strings)
    $stmt->bind_param("sssssss", $asset_id, $asset_name, $asset_tag, $asset_brand, $asset_status, $lab_id, $lab_room);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
        exit; // Stop execution immediately after sending JSON
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}