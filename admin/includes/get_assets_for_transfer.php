<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json');

$response = [
    'source_name' => 'Unknown Lab',
    'units' => [],
    'facility' => [],
    'labs' => []
];

try {
    $lab_id = isset($_GET['lab_id']) ? mysqli_real_escape_string($conn, $_GET['lab_id']) : 0;

    if (!$lab_id) {
        throw new Exception("Lab ID is missing.");
    }

    // 2. Fetch the Source Room Name (Formatted: Name (Room))
    $sourceQuery = "SELECT lab_name, lab_room FROM laboratories WHERE lab_id = '$lab_id' LIMIT 1";
    $sourceResult = $conn->query($sourceQuery);
    if ($sourceResult && $row = $sourceResult->fetch_assoc()) {
        // Formats as: Computer Lab 1 (Room 104)
        $response['source_name'] = $row['lab_name'] . " (" . $row['lab_room'] . ")";
    }

    // 3. Fetch Computer Units (Same logic as before)
    $unitQuery = "
        SELECT u.set_id, u.set_tag, u.set_status 
        FROM units u 
        LEFT JOIN specs s ON u.set_id = s.set_id 
        LEFT JOIN peripherals p ON u.set_id = p.set_id 
        WHERE u.lab_id = '$lab_id' 
        AND u.set_status != 'Condemned' 
        AND s.specs_property IS NOT NULL 
        AND s.specs_property != '' 
        AND p.monitor_property IS NOT NULL 
        AND p.monitor_property != ''
    ";
    $unitResult = $conn->query($unitQuery);
    if ($unitResult) {
        $response['units'] = $unitResult->fetch_all(MYSQLI_ASSOC);
    }

    // 4. Fetch Facility Assets (Same logic as before)
    $assetQuery = "SELECT asset_id, asset_tag, asset_status FROM assets WHERE lab_id = '$lab_id' AND asset_status != 'Condemned'";
    $assetResult = $conn->query($assetQuery);
    if ($assetResult) {
        $response['facility'] = $assetResult->fetch_all(MYSQLI_ASSOC);
    }

    // 5. Fetch Target Labs (Formatted: Name (Room))
    // Excludes current lab and archived labs
    $labQuery = "
        SELECT lab_id, lab_name, lab_room 
        FROM laboratories 
        WHERE lab_id != '$lab_id' 
        AND lab_status != 'Archived' 
        ORDER BY lab_name ASC
    ";
    $labResult = $conn->query($labQuery);
    if ($labResult) {
        $tempLabs = [];
        while ($row = $labResult->fetch_assoc()) {
            $tempLabs[] = [
                'lab_id' => $row['lab_id'],
                'full_display' => $row['lab_name'] . " (" . $row['lab_room'] . ")"
            ];
        }
        $response['labs'] = $tempLabs;
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
