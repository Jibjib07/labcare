<?php
// ==========================================
// 1. DYNAMIC SYSTEM CONFIGURATION
// ==========================================

// Ask the system for the computer's name
$hostName = gethostname();

// Ask the network for the IPv4 address assigned to that name
$localIP = gethostbyname($hostName);

// Define the base URL for the entire application automatically
define('BASE_URL', 'http://' . $localIP . '/labcare/');


// ==========================================
// 2. DATABASE CONNECTION
// ==========================================

$db_host = "localhost"; // Keep this as localhost! The DB is on the same machine.
$db_username = "root";  // Default XAMPP username
$db_password = "";      // Default XAMPP password is usually empty
$db_name = "labcare_db";

// Create connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
