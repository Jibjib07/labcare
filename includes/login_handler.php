<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

if (isset($_POST['login_btn'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Updated query to match your schema
    $query = "SELECT * FROM users WHERE user_email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Match the SHA256 encryption used in your SQL insert
        $hashed_input = hash('sha256', $password);

        if ($hashed_input === $user['user_password']) {
            // Set session variables using exact column names from your SQL
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['user_name'];
            $_SESSION['user_role'] = $user['user_role'];

            header("Location: ../troubleshooting.php");
            exit();
        } else {
            header("Location: ../login.php?error=invalid_password");
            exit();
        }
    } else {
        header("Location: ../login.php?error=user_not_found");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
