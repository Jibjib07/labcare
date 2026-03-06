<?php
session_start();

// Include the database connection (Since both files are in 'includes/', we just use 'db.php')
require 'db.php'; 

if (isset($_POST['login_btn'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prevent SQL Injection
    $email = $conn->real_escape_string($email);
    $password = $conn->real_escape_string($password);

    // Query the database for the user
    $query = "SELECT * FROM users WHERE user_email = '$email'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verify password (plain text for now based on your DB insert)
        if (password_verify($password, $row['user_password'])) {
            
            // Check if the account is active
            if (strtolower($row['user_status']) !== 'active') {
                header("Location: ../login.php?error=inactive");
                exit();
            }

            // Set Session Variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_name'] = $row['user_name'];
            $_SESSION['user_role'] = $row['user_role'];

            // Role-Based Redirection
            if (strtolower($row['user_role']) === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../user/dashboard.php");
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
?>