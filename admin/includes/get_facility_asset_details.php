<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

// 1. Remove (int) because asset_id is now a string (e.g., FA-0001-2026)
$asset_id = $_GET['asset_id'] ?? '';

if (!empty($asset_id)) {
    $query = "SELECT * FROM assets WHERE asset_id = ?";
    $stmt = $conn->prepare($query);

    // 2. Change "i" to "s" for String
    $stmt->bind_param("s", $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // 3. Map the database columns to the keys your JS expects
        // (Ensure these match the 'key' in your JS populateRightPanel loop)
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Asset not found: ' . $asset_id]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'No Asset ID provided']);
}
