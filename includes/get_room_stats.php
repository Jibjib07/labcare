<?php
// File: labcare/includes/get_room_stats.php
require 'db.php';

if (isset($_GET['room'])) {
    $room = $conn->real_escape_string($_GET['room']);

    $stats = [
        'working' => 0,
        'repair' => 0,
        'condemn' => 0,
        'total_units' => 0,
        'total_assets' => 0,
        'schedule' => null
    ];

    // 1. Fetch Units
    $unit_query = "SELECT set_status, COUNT(*) as count FROM units WHERE lab_room = '$room' GROUP BY set_status";
    $unit_result = $conn->query($unit_query);
    if ($unit_result) {
        while ($row = $unit_result->fetch_assoc()) {
            $status = strtolower(trim($row['set_status']));
            if ($status === 'working') $stats['working'] = $row['count'];
            elseif ($status === 'for repair' || $status === 'repair') $stats['repair'] = $row['count'];
            elseif ($status === 'condemn' || $status === 'for condemn') $stats['condemn'] = $row['count'];
            $stats['total_units'] += $row['count'];
        }
    }

    // 2. Fetch Assets
    $asset_query = "SELECT COUNT(*) as count FROM assets WHERE lab_room = '$room'";
    $asset_result = $conn->query($asset_query);
    if ($asset_result && $row = $asset_result->fetch_assoc()) {
        $stats['total_assets'] = $row['count'];
    }

    // 3. Fetch the BLOB Image and Convert it!
    $sched_query = "SELECT lab_sched FROM laboratories WHERE lab_room = '$room'";
    $sched_result = $conn->query($sched_query);

    if ($sched_result && $row = $sched_result->fetch_assoc()) {
        if (!empty($row['lab_sched'])) {
            // Check the file type of the binary data
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($row['lab_sched']);

            // If it can't figure it out, default to jpeg
            if (!$mime) $mime = 'image/jpeg';

            // Convert binary to Base64
            $base64 = base64_encode($row['lab_sched']);

            // Format it into a Data URI that the HTML <img> tag can read
            $stats['schedule'] = 'data:' . $mime . ';base64,' . $base64;
        }
    }

    // Output JSON
    header('Content-Type: application/json');
    echo json_encode($stats);
}
