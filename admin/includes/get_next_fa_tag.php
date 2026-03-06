<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

$lab_id = $conn->real_escape_string($_GET['lab_id'] ?? '0');
$next_number = 1;
$current_year = date('Y');

// Scan the table for the highest existing number in this room
$query = "SELECT asset_tag FROM assets WHERE lab_id = '$lab_id' ORDER BY CAST(asset_tag AS UNSIGNED) DESC LIMIT 1";
$result = $conn->query($query);

if ($result && $row = $result->fetch_assoc()) {
    $next_number = (int)$row['asset_tag'] + 1;
}

// 01, 02, 03...
$short_tag = str_pad($next_number, 2, "0", STR_PAD_LEFT);
// FA-0001-2026
$full_id = "FA-" . str_pad($next_number, 4, "0", STR_PAD_LEFT) . "-" . $current_year;

echo json_encode([
    'success' => true,
    'next_tag' => $short_tag,
    'next_id' => $full_id
]);
