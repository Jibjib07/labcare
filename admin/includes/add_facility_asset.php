<?php
require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json');

try {
    $asset_id = $_POST['asset_id'];
    $asset_tag = $_POST['asset_tag'];
    $asset_name = $_POST['asset_name'];
    $asset_property = $_POST['asset_property']; // NEW
    $asset_brand = $_POST['asset_brand'];
    $asset_status = $_POST['asset_status'];
    $lab_id = $_POST['lab_id'];

    // 1. Fetch Room Name to keep data in sync
    $roomQuery = $conn->prepare("SELECT lab_room FROM laboratories WHERE lab_id = ?");
    $roomQuery->bind_param("s", $lab_id);
    $roomQuery->execute();
    $roomResult = $roomQuery->get_result()->fetch_assoc();
    $lab_room = $roomResult ? $roomResult['lab_room'] : 'Unknown';

    // 2. Insert Asset (Including asset_property and lab_room)
    $stmt = $conn->prepare("INSERT INTO assets (asset_id, asset_name, asset_tag, asset_property, asset_brand, asset_status, lab_id, lab_room) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $asset_id, $asset_name, $asset_tag, $asset_property, $asset_brand, $asset_status, $lab_id, $lab_room);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
