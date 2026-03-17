<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../../includes/db.php';

    $room = $conn->real_escape_string($_GET['room'] ?? '');

    $query = "
        SELECT u.set_id, u.set_tag, IFNULL(u.set_status, 'Working') as status, 
               s.specs_property, p.monitor_property
        FROM units u
        LEFT JOIN specs s ON u.set_id = s.set_id
        LEFT JOIN peripherals p ON u.set_id = p.set_id
        WHERE u.lab_room = '$room' AND (u.set_status != 'Condemned' OR u.set_status IS NULL)
        AND (s.specs_property IS NULL OR s.specs_property = '' OR p.monitor_property IS NULL OR p.monitor_property = '')
        ORDER BY CAST(u.set_tag AS UNSIGNED) ASC
    ";

    $result = $conn->query($query);
    $units = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $units[] = $row;
        }
    }

    echo json_encode(['success' => true, 'units' => $units]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
