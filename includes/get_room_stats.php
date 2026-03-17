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
    'condemn' => 0, // This is now an age-based warning metric
    'total_units' => 0,
    'total_assets' => 0,
    'schedule' => null
];

// 2. Fetch Units (Filtering out items that are already permanently disposed/Condemned)
$unit_query = "
    SELECT u.set_status, s.specs_purchase 
    FROM units u
    LEFT JOIN specs s ON u.set_id = s.set_id
    WHERE u.lab_room = '$room' AND u.set_status != 'Condemned'
";
$unit_result = $conn->query($unit_query);

if ($unit_result) {
    $today = new DateTime(); // Get current date

    while ($row = $unit_result->fetch_assoc()) {
        $status = strtolower(trim($row['set_status']));
        $purchase_date = $row['specs_purchase']; // Now grabbing from the joined specs table

        // --- A. AGE CHECK (For Condemn Warning) ---
        if (!empty($purchase_date)) {
            $purchase_time = new DateTime($purchase_date);
            $age = $today->diff($purchase_time)->y;

            if ($age >= 5) {
                $stats['condemn']++;
            }
        }

        // --- B. PHYSICAL STATUS CHECK (The Donut Chart Slices) ---
        if ($status === 'working') {
            $stats['working']++;
        } elseif ($status === 'for repair') {
            $stats['repair']++;
        }

        // --- C. TOTAL COUNT ---
        $stats['total_units']++;
    }
}

// 3. Fetch Assets (Excluding permanently Condemned assets)
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
