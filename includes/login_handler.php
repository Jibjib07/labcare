<?php
session_start();

// Include the database connection (Since both files are in 'includes/', we just use 'db.php')
require 'db.php';

if (isset($_POST['login_btn'])) {
    // trim() removes accidental spaces the user might have copied/pasted
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Query the database for the user using Prepared Statements (Maximum Security)
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // SECURE CHECK: Verify the typed password against the hashed password in the DB
        if (password_verify($password, $row['user_password'])) {

            // Check if the account is active
            if (isset($row['user_status']) && strtolower($row['user_status']) !== 'active') {
                header("Location: ../login.php?error=inactive");
                exit();
            }

            // Prevent Session Fixation: Change the ID immediately upon login
            session_regenerate_id(true);            

            // Set Session Variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_name'] = $row['user_name'];
            $_SESSION['user_role'] = $row['user_role'];

            // Track Activity for Expiration Logic
            $_SESSION['last_activity'] = time();

            // Role-Based Redirection
            if (strtolower($row['user_role']) === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../staff/dashboard.php");
            }
            exit();
        } else {
            // Incorrect password
            header("Location: ../login.php?error=invalid_password");
            exit();
        }
    } else {
        // Email not found in database
        header("Location: ../login.php?error=user_not_found");
        exit();
    }
} else {
    // If accessed directly without submitting the form
    header("Location: ../login.php");
    exit();
}
