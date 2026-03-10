
 <?php
require 'includes/db.php';
require 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$notif_title = "";
$notif_msg = "";
$show_notif = false;
$notif_type = "email";
$step = 1;

// --- Form submission: send reset link ---
if (isset($_POST['send_link_btn'])) {
    $email = trim($_POST['email']);

    // Always show generic message for security
    $notif_title = "Email Sent";
    $notif_msg = "If an account exists, a reset link has been sent.";
    $show_notif = true;

    // --- STEP 1: Look up user by email ---
    $stmt = $conn->prepare("SELECT user_id, user_name FROM users WHERE user_email = ?");
    if (!$stmt) die("Prepare failed (users): " . $conn->error);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $user_id = $user['user_id'];

        // --- STEP 2: Limit requests per hour (rate limiting) ---
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt, MIN(created_at) AS first_req FROM password_resets WHERE user_id = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)");
        if (!$stmt) die("Prepare failed: " . $conn->error);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $count = $row['cnt'];
        $first_request_time = $row['first_req'];

        // --- Max 3 requests per hour ---
        if ($count >= 3) {
            $notif_title = "Too Many Requests";
            // Calculate minutes left until the next reset is allowed
            $minutes_left = 60;
            if ($first_request_time) {
                $first_time = strtotime($first_request_time);
                $minutes_elapsed = (time() - $first_time) / 60;
                $minutes_left = max(0, ceil(60 - $minutes_elapsed));
            }

            $notif_msg = "You have reached the maximum of 3 password reset requests per hour. Please try again in {$minutes_left} minutes.";
            $show_notif = true;
            return;
        }

        // --- STEP 3: Generate token and expiration ---
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // --- STEP 4: Delete old tokens ---
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        if (!$stmt) die("Prepare failed (delete tokens): " . $conn->error);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // --- STEP 5: Insert new token ---
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())");
        if (!$stmt) die("Prepare failed (insert token): " . $conn->error);
        $stmt->bind_param("iss", $user_id, $tokenHash, $expires);
        $stmt->execute();

        // --- STEP 6: Build reset link ---
        $resetLink = "http://localhost/labcare-main/password_resets.php?token=" . $rawToken;

        // --- STEP 7: Send email via PHPMailer ---
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = 0; // 2 for debug
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your-email@gmail.com';  // Labcare Email 
            $mail->Password = 'your-16-char-code';       // Gmail App Password or Your secret 16-char code from Labcare Email
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('your-email@gmail.com', 'LabCare');
            $mail->addAddress($email, $user['user_name']);

            $mail->Subject = 'LabCare Password Reset';
            $mail->Body = "Hello {$user['user_name']},\n\nClick this link to reset your password:\n\n{$resetLink}\n\nLink expires in 1 hour.";

            $mail->send();
        } catch (Exception $e) {
            // fallback for local testing
            error_log("Password reset link for $email: $resetLink");
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

    <div id="authToast" class="auth-toast <?php echo $show_notif ? 'active' : ''; ?>">
        <div class="toast-icon-wrapper <?php echo ($notif_type == 'update') ? 'green-bg' : ''; ?>">
            <i class="fas <?php echo ($notif_type == 'update') ? 'fa-unlock-alt' : 'fa-envelope-open-text'; ?>"></i>
        </div>
        <div class="toast-content">
            <h4><?php echo $notif_title; ?></h4>
            <p><?php echo $notif_msg; ?></p>
        </div>
    </div>

    <div class="glass-container">
        <div class="brand-section">
            <img src="assets/logo.png" alt="LabCare Logo" class="main-logo">
            <span class="brand-name">LAB<span class="bname2">CARE</span></span>
        </div>

            <div class="login-box reset-card">
                <h2 class="auth-title-large">Reset Password</h2>
                <p class="auth-subtitle-gray">Enter the email address associated with your account and we'll send you a link to reset your password.</p>

                <form method="POST">
                    <div class="input-group">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <input type="email" name="email" placeholder="Ex. JohnDoe@gmail.com" required>
                            <i class="fas fa-at"></i>
                        </div>
                    </div>
                    <button type="submit" name="send_link_btn" class="btn-login btn-dark-green">Send Reset Link</button>
                </form>
                <div class="login-footer">
                    <a href="login.php" class="return-link">Return to Login</a>
                </div>
            </div>


        <p class="disclaimer"><strong>Disclaimer:</strong> For Computer Laboratory Use Only</p>
    </div>

    <script>
        // Notification Auto-hide logic
        const toast = document.getElementById('authToast');
        if (toast.classList.contains('active')) {
            setTimeout(() => {
                toast.classList.remove('active');
            }, 5000);
        }

    </script>
</body>

</html>