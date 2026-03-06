<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

// Catch all POST data
$asset_id     = $_POST['asset_id'] ?? '';
$asset_tag    = $_POST['asset_tag'] ?? '';
$asset_name   = $_POST['asset_name'] ?? '';
$asset_brand  = $_POST['asset_brand'] ?? '';
$asset_status = $_POST['asset_status'] ?? '';
$lab_id       = $_POST['lab_id'] ?? '';

// Basic Validation
if (empty($asset_id) || empty($asset_name) || empty($lab_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields (ID, Name, or Lab)']);
    exit;
}

// SQL Query - Note: No AUTO_INCREMENT used here because asset_id is our custom string
$query = "INSERT INTO assets (asset_id, asset_tag, asset_name, asset_brand, asset_status, lab_id) 
          VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);

if ($stmt) {
    // "ssssss" means 6 strings
    $stmt->bind_param("ssssss", $asset_id, $asset_tag, $asset_name, $asset_brand, $asset_status, $lab_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database Insert Failed: ' . $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'SQL Prepare Failed: ' . $conn->error]);
}
