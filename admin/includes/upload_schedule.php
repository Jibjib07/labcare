<?php
// File: labcare/includes/upload_schedule.php
require '../../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['schedule_image']) && isset($_POST['room_number'])) {

    $room = $_POST['room_number'];
    $file = $_FILES['schedule_image'];

    // 1. Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF allowed.']);
        exit;
    }

    // 2. Read the image file into raw binary data
    $imgData = file_get_contents($file['tmp_name']);

    // 3. Prepare the SQL Statement
    // We use a prepared statement to safely insert binary data
    $stmt = $conn->prepare("UPDATE laboratories SET lab_sched = ? WHERE lab_room = ?");

    // In mysqli, 'b' stands for BLOB and 's' stands for String (room number)
    // We have to pass a NULL placeholder first, then send the binary data separately
    $null = NULL;
    $stmt->bind_param("bs", $null, $room);
    $stmt->send_long_data(0, $imgData); // Send the binary data to the first '?' (index 0)

    // 4. Execute and Respond
    if ($stmt->execute()) {
        // Since we are not using a file path anymore, we instantly convert 
        // the uploaded image into a Base64 string so the JavaScript can display it right away!
        $base64 = base64_encode($imgData);
        $mime = $file['type'];
        $src = 'data:' . $mime . ';base64,' . $base64;

        echo json_encode(['success' => true, 'file_path' => $src]); // We still call it file_path so JS doesn't break
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request. Missing image or room number.']);
}
