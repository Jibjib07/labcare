<?php
// 1. Database Connection (Adjust the path to your db.php file)
require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // 1. Collect and Validate ID (The most important part to prevent bulk overwrites)
    $asset_id = $_POST['asset_id'] ?? null;
    if (!$asset_id || empty($asset_id)) {
        throw new Exception("Technical Error: Asset ID is missing. Update aborted to protect data.");
    }

    // 2. Collect other fields
    $asset_name = trim($_POST['asset_name'] ?? '');
    $property   = trim($_POST['asset_property'] ?? '');
    $brand      = trim($_POST['asset_brand'] ?? '');
    $status     = trim($_POST['asset_status'] ?? '');

    if (empty($asset_name) || empty($property)) {
        throw new Exception("Name and Property ID are required.");
    }

    // 3. Prepare SQL with STRICT WHERE clause
    $query = "UPDATE assets SET 
                asset_name = ?, 
                asset_property = ?, 
                asset_brand = ?, 
                asset_status = ? 
              WHERE asset_id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    // "sssss" assumes asset_id is a string/varchar. Change to "ssssi" if it's an integer.
    $stmt->bind_param("sssss", $asset_name, $property, $brand, $status, $asset_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Updated successfully.";
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;
