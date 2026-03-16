<?php
// Start the session to access user login data
session_start();

// 1. Check if the user is logged in at all
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect them back to the login page
    header("Location: ../index.php"); // Adjust this path if your login page is named differently (e.g., login.php)
    exit();
}

// 2. ROLE VERIFICATION (Highly Recommended)
// Assuming you store the user's role in the session when they log in (e.g., $_SESSION['role'] = 'Staff')
// If your database uses a different name for the role (like 'staff', 'Admin', 'user'), adjust the text below!

if (isset($_SESSION['role']) && $_SESSION['role'] !== 'Staff') {

    // If an Admin tries to access the Staff dashboard, redirect them back to the Admin dashboard
    if ($_SESSION['role'] === 'Admin') {
        header("Location: ../admin/dashboard.php"); // Adjust this path to your actual admin dashboard
        exit();
    }

    // If they have any other unknown role, kick them back to login
    header("Location: ../index.php");
    exit();
}
