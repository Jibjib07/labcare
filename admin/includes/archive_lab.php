<?php
// File: includes/archive_lab.php

// 1. Safely start the session to avoid "<br><b>Notice</b>" errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';

// 2. Prevent standard PHP HTML errors from ruining our JSON response
error_reporting(0);
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    $lab_id = intval($_POST['lab_id'] ?? 0);
    $reasons_json = $_POST['reasons'] ?? '[]';
    $reasons = json_decode($reasons_json, true) ?: [];
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');

    // Format the reason string
    $reason_str = implode(', ', $reasons);
    if (!empty($remarks)) {
        $reason_str .= empty($reason_str) ? "Remarks: " . $remarks : " (Remarks: " . $remarks . ")";
    }
    if (empty($reason_str)) {
        $reason_str = "No reason provided";
    }

    $archived_by = $_SESSION['user_name'] ?? 'Admin';

    // 3. Fetch current lab details
    $lab_query = $conn->query("SELECT lab_name, lab_room, lab_sched FROM laboratories WHERE lab_id = $lab_id");
    if (!$lab_query || $lab_query->num_rows === 0) {
        throw new Exception("Lab not found in database. Received Lab ID: " . $lab_id);
    }
    $lab_row = $lab_query->fetch_assoc();

    // 4. Insert into lab_history
    $stmt = $conn->prepare("INSERT INTO lab_history (lab_id, lab_name, lab_room, lab_sched, lab_status, archived_date, reason, archived_by) VALUES (?, ?, ?, ?, 'Archived', NOW(), ?, ?)");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isssss", $lab_id, $lab_row['lab_name'], $lab_row['lab_room'], $lab_row['lab_sched'], $reason_str, $archived_by);

    if (!$stmt->execute()) {
        throw new Exception("Insert History failed: " . $stmt->error);
    }
    $stmt->close();

    // 5. Update the laboratories table
    $update = $conn->query("UPDATE laboratories SET lab_status = 'Archived' WHERE lab_id = $lab_id");
    if (!$update) {
        throw new Exception("Failed to update lab status.");
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // 6. If anything fails, return a clean JSON error instead of crashing!
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
