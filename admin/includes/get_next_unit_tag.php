<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

$lab_id = $_GET['lab_id'] ?? '';

if (!$lab_id) {
    echo json_encode(['success' => false, 'error' => 'No Lab ID']);
    exit;
}

// Find all taken tags in this lab
$query = "SELECT set_tag FROM units WHERE lab_id = ? AND set_status != 'Condemned' ORDER BY CAST(set_tag AS UNSIGNED) ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $lab_id);
$stmt->execute();
$result = $stmt->get_result();

$taken_tags = [];
while ($row = $result->fetch_assoc()) {
    $taken_tags[] = (int)$row['set_tag'];
}

// Find the first hole in the sequence (1, 2, 4... returns 3)
$next_tag = 1;
while (in_array($next_tag, $taken_tags)) {
    $next_tag++;
}

echo json_encode([
    'success' => true,
    'next_tag' => str_pad($next_tag, 2, "0", STR_PAD_LEFT)
]);
