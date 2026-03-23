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
        $history_data = [];
        $hist_stmt = $conn->prepare("SELECT report_date, report_actor, report_remarks, report_status FROM asset_history WHERE asset_id = ? ORDER BY report_date DESC");
        $hist_stmt->bind_param("s", $asset_id);
        $hist_stmt->execute();
        $hist_res = $hist_stmt->get_result();

        while ($h_row = $hist_res->fetch_assoc()) {
            $date = new DateTime($h_row['report_date']);
            $h_row['formatted_date'] = $date->format('M d, Y');
            $history_data[] = $h_row;
        }
        $hist_stmt->close();

        // Send BOTH the FA data and the History data back to JavaScript
        echo json_encode(['success' => true, 'data' => $row, 'history' => $history_data]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Asset not found']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'No Asset ID provided']);
}
