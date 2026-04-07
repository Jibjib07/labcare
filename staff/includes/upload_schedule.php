<?php
// File: labcare/includes/upload_schedule.php
require '../../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['schedule_image']) && isset($_POST['room_number'])) {

    $room = $_POST['room_number'];
    $file = $_FILES['schedule_image'];

    // 1. CATCH SERVER-SIDE UPLOAD ERRORS FIRST (Crucial for large files)
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'The file is too large. It exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        $errorMsg = isset($uploadErrors[$file['error']]) ? $uploadErrors[$file['error']] : 'Unknown upload error.';
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        exit;
    }

    // 2. Validate File Size (e.g., 5MB Limit = 5242880 bytes)
    if ($file['size'] > 5242880) {
        echo json_encode(['success' => false, 'error' => 'File is too large. Maximum size is 5MB.']);
        exit;
    }

    // 3. Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF allowed.']);
        exit;
    }

    // 4. Read the image file into raw binary data
    $imgData = file_get_contents($file['tmp_name']);

    // 5. Prepare the SQL Statement
    $stmt = $conn->prepare("UPDATE laboratories SET lab_sched = ? WHERE lab_room = ?");

    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database preparation failed: ' . $conn->error]);
        exit;
    }

    // In mysqli, 'b' stands for BLOB and 's' stands for String
    $null = NULL;
    $stmt->bind_param("bs", $null, $room);

    // Send the binary data in packets
    $stmt->send_long_data(0, $imgData);

    // 6. Execute and Respond
    if ($stmt->execute()) {
        // Convert to Base64 for instant frontend rendering
        $base64 = base64_encode($imgData);
        $mime = $file['type'];
        $src = 'data:' . $mime . ';base64,' . $base64;

        echo json_encode(['success' => true, 'file_path' => $src]);
    } else {
        // If MySQL rejects the BLOB size, this will catch the "max_allowed_packet" error
        echo json_encode(['success' => false, 'error' => 'Database update failed: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request. Missing image or room number.']);
}
