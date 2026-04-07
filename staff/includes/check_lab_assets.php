<?php
require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json');

if (isset($_GET['lab_id'])) {
    $lab_id = intval($_GET['lab_id']);

    // Count active units
    $unit_query = "SELECT COUNT(*) as count FROM units WHERE lab_id = $lab_id AND set_status != 'Condemned'";
    $units = $conn->query($unit_query)->fetch_assoc()['count'];

    // Count active assets
    $asset_query = "SELECT COUNT(*) as count FROM assets WHERE lab_id = $lab_id AND asset_status != 'Condemned'";
    $assets = $conn->query($asset_query)->fetch_assoc()['count'];

    echo json_encode(['total_units' => $units, 'total_assets' => $assets]);
} else {
    echo json_encode(['error' => 'No lab ID provided']);
}
