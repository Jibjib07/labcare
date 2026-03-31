<?php
// 1. THE SESSION KILLER - Log them out immediately when they click the link
session_start();
session_unset();
session_destroy();
session_start(); // Start a fresh, empty session for security/form handling

require 'includes/db.php';

$MAX_ATTEMPTS = 3;                  // Max allowed attempts before the token is locked
$notif_title = $notif_msg = '';     // Notifications for UI feedback
$show_form = true;                  // Flag to control UI visibility
$token = $_GET['token'] ?? null;    // Get the token from URL query

// No token provided → block form
if (!$token) {
    $notif_title = "Error";
    $notif_msg = "No reset token provided. Please use the link sent to your email.";
    $show_form = false;
} else {
    // STEP 1: Hash token for secure DB lookup
    $tokenHash = hash('sha256', $token);

    // STEP 2: Fetch the password reset record for this token
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token_hash = ?");
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    // Token doesn't exist (invalid/fake/deleted) → hide form
    if (!$row) {
        $notif_title = "Error";
        $notif_msg = "Invalid or expired token. Please request a new password reset.";
        $show_form = false;
    } else {
        $user_id = (int)$row['user_id'];
        $attempts = (int)$row['attempts'];
        $expires_at = $row['expires_at'];

        // STEP 3: Lock token if attempts exceeded
        if ($attempts >= $MAX_ATTEMPTS) {
            // "Burn" the token from the DB so it can't be guessed further
            $delStmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $delStmt->bind_param("i", $user_id);
            $delStmt->execute();

            $notif_title = "Error";
            $notif_msg = "This link is locked due to too many failed attempts. Please request a new one.";
            $show_form = false;
        }

        // STEP 4: Check if token expired → clean DB and hide form
        elseif (strtotime($expires_at) < time()) {
            // Delete expired record to keep the table clean
            $delStmt = $conn->prepare("DELETE FROM password_resets WHERE token_hash = ?");
            $delStmt->bind_param("s", $tokenHash);
            $delStmt->execute();

            $notif_title = "Error";
            $notif_msg = "This password reset link has expired.";
            $show_form = false;
        }
    }
}

// STEP 5: Handle form submission
if ($show_form && isset($_POST['reset_password_btn'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Empty password → error
    if (empty($new_password)) {
        $notif_title = "Error";
        $notif_msg = "Please enter a new password.";
    }
    // Passwords mismatch → show error, don't lock token
    elseif ($new_password !== $confirm_password) {
        $notif_title = "Error";
        $notif_msg = "Passwords do not match. Please try again.";
    } else {
        // Valid password → hash and update user record
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET user_password = ? WHERE user_id = ?");
        $updateStmt->bind_param("si", $hashed, $user_id);

        if ($updateStmt->execute()) {
            // Delete token after successful reset
            $delStmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $delStmt->bind_param("i", $user_id);
            $delStmt->execute();

            // Log it in the audit trail
            $logStmt = $conn->prepare("INSERT INTO user_audit_trail (admin_id, user_id, action_type, created_at) VALUES (?, ?, 'Password Reset Completed', NOW())");
            $logStmt->bind_param("ii", $user_id, $user_id);
            $logStmt->execute();
            $logStmt->close();

            // Redirect to login with success message
            header("Location: login.php?reset=success");
            exit;
        } else {
            // DB update failed
            $notif_title = "Error";
            $notif_msg = "Something went wrong. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Recovery - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://api.fontshare.com/v2/css?f[]=geist@1,2&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
</head>

<body class="login-body">

    <div class="glass-container">
        <div class="brand-section">
            <img src="assets/logo.png" alt="LabCare Logo" class="main-logo">
            <span class="brand-name">LAB<span class="bname2">CARE</span></span>
        </div>

        <div class="login-box reset-card">
            <h2 class="auth-title-large">Create New Password</h2>

            <?php if ($show_form): ?>
                <p class="auth-subtitle-gray">Please create a new password for your account.</p>
            <?php endif; ?>

            <?php if ($notif_msg): ?>
                <div class="notif <?php echo strtolower($notif_title); ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; background: rgba(255,0,0,0.1); color: #ff4d4d; border: 1px solid rgba(255,0,0,0.2);">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $notif_msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($show_form): ?>
                <form method="POST">
                    <div class="input-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="new_password" class="pass-input" placeholder="Enter your password" required>
                            <i class="fas fa-eye-slash toggle-pass"></i>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="confirm_password" class="pass-input" placeholder="Re-enter your password" required>
                            <i class="fas fa-eye-slash toggle-pass"></i>
                        </div>
                    </div>

                    <button type="submit" name="reset_password_btn" class="btn-login btn-dark-green">Reset Password</button>
                </form>
            <?php else: ?>
                <a href="forgot_password.php" class="btn-login btn-dark-green" style="text-decoration: none; display: block; text-align: center;">Request New Link</a>
            <?php endif; ?>

            <div class="login-footer">
                <a href="login.php" class="return-link">Return to Login</a>
            </div>
        </div>
    </div>

    <script>
        // Send a cross-tab signal to log out all other active LabCare windows
        localStorage.setItem('labcare_force_logout', Date.now());

        document.querySelectorAll('.toggle-pass').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>