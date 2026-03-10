<?php
// File: labcare/includes/get_room_stats.php
require 'db.php';

$room = null;

// 1. UNIVERSAL INPUT HANDLER: Tap into laboratories table if we get an ID
if (isset($_GET['lab_id'])) {
    $lab_id = intval($_GET['lab_id']);
    // Look up the room name using the ID
    $lab_query = $conn->query("SELECT lab_room FROM laboratories WHERE lab_id = '$lab_id'");
    if ($lab_query && $row = $lab_query->fetch_assoc()) {
        $room = $conn->real_escape_string($row['lab_room']);
    }
} elseif (isset($_GET['room'])) {
    // Fallback for laboratory_management.php which sends ?room=104
    $room = $conn->real_escape_string($_GET['room']);
}

// If we couldn't resolve a room, stop here and return empty stats
if (!$room) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Room not found']);
    exit;
}

$stats = [
    'working' => 0,
    'repair' => 0,
    'condemn' => 0, // This will ONLY track "For Condemn"
    'total_units' => 0,
    'total_assets' => 0,
    'schedule' => null
];

// 2. Fetch Units
$unit_query = "SELECT set_status, COUNT(*) as count FROM units WHERE lab_room = '$room' GROUP BY set_status";
$unit_result = $conn->query($unit_query);

if ($unit_result) {
    while ($row = $unit_result->fetch_assoc()) {
        // Standardize string to lowercase for safe matching
        $status = strtolower(trim($row['set_status']));

        // Strictly separate the statuses
        if ($status === 'working') {
            $stats['working'] = $row['count'];
        } elseif ($status === 'for repair') {
            $stats['repair'] = $row['count'];
        } elseif ($status === 'for condemn') {
            $stats['condemn'] = $row['count'];
        }

        // Total units calculation: Count everything EXCEPT "Condemned"
        if ($status !== 'condemned') {
            $stats['total_units'] += $row['count'];
        }
    }
}

// 3. Fetch Assets
$asset_query = "SELECT COUNT(*) as count FROM assets WHERE lab_room = '$room' AND asset_status != 'Condemned'";
$asset_result = $conn->query($asset_query);
if ($asset_result && $row = $asset_result->fetch_assoc()) {
    $stats['total_assets'] = $row['count'];
}

// 4. Fetch the BLOB Image and Convert it
$sched_query = "SELECT lab_sched FROM laboratories WHERE lab_room = '$room'";
$sched_result = $conn->query($sched_query);

if ($sched_result && $row = $sched_result->fetch_assoc()) {
    if (!empty($row['lab_sched'])) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($row['lab_sched']);
        if (!$mime) $mime = 'image/jpeg';

        $base64 = base64_encode($row['lab_sched']);
        $stats['schedule'] = 'data:' . $mime . ';base64,' . $base64;
    }
}

// 5. Return JSON Output
header('Content-Type: application/json');
echo json_encode($stats);
