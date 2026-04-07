<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

$lab_id = $_GET['lab_id'] ?? '0';
$current_year = date('Y');

try {
    // ==========================================================
    // 1. Generate the GLOBAL Unique Asset ID (FA-XXXX-YYYY)
    // ==========================================================
    $next_global_num = 1;

    // Look at the ENTIRE database to find the highest FA ID for this year
    $query_global = "SELECT asset_id FROM assets WHERE asset_id LIKE CONCAT('FA-%-', ?) ORDER BY asset_id DESC LIMIT 1";
    $stmt_global = $conn->prepare($query_global);
    $stmt_global->bind_param("s", $current_year);
    $stmt_global->execute();
    $result_global = $stmt_global->get_result();

    if ($row_global = $result_global->fetch_assoc()) {
        $last_id = $row_global['asset_id']; // e.g., "FA-0015-2026"
        $parts = explode('-', $last_id);
        if (count($parts) === 3) {
            $next_global_num = (int)$parts[1] + 1; // 15 + 1 = 16
        }
    }
    $stmt_global->close();

    // Format the new Global ID (e.g., FA-0016-2026)
    $full_id = sprintf("FA-%04d-%s", $next_global_num, $current_year);

    // ==========================================================
    // 2. Generate the ROOM-SPECIFIC Asset Tag (01, 02, 03...)
    // ==========================================================
    $next_room_num = 1;

    // Look ONLY at this specific lab to find the next tag number
    $query_room = "SELECT asset_tag FROM assets WHERE lab_id = ? ORDER BY CAST(asset_tag AS UNSIGNED) DESC LIMIT 1";
    $stmt_room = $conn->prepare($query_room);
    $stmt_room->bind_param("s", $lab_id);
    $stmt_room->execute();
    $result_room = $stmt_room->get_result();

    if ($row_room = $result_room->fetch_assoc()) {
        $next_room_num = (int)$row_room['asset_tag'] + 1;
    }
    $stmt_room->close();

    // Format the new Room Tag (e.g., 01, 02, 11)
    $short_tag = str_pad($next_room_num, 2, "0", STR_PAD_LEFT);

    // ==========================================================
    // 3. Return both to JavaScript
    // ==========================================================
    echo json_encode([
        'success' => true,
        'next_tag' => $short_tag,
        'next_id' => $full_id
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
