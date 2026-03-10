<?php
$step = 1;
$notif_title = "";
$notif_msg = "";
$show_notif = false;
$notif_type = "email";

// Logic to simulate the flow based on your mockups
if (isset($_POST['send_link_btn'])) {
    $step = 2;
    $show_notif = true;
    $notif_type = "email";
    $notif_title = "Email Sent";
    $notif_msg = "If an account exists, a reset link has been sent.";
}

if (isset($_POST['reset_password_btn'])) {
    $step = 2;
    $show_notif = true;
    $notif_type = "update";
    $notif_title = "Password Updated";
    $notif_msg = "You can now log in with your new password.";
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

        <?php if ($step == 1): ?>
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
                    <button type="submit" name="send_link_btn" class="btn-login btn-dark-green">Send Reset Request</button>
                </form>
                <div class="login-footer">
                    <a href="login.php" class="return-link">Return to Login</a>
                </div>
            </div>

        <?php else: ?>
            <div class="login-box reset-card">
                <h2 class="auth-title-large">Create New Password</h2>
                <p class="auth-subtitle-gray">Please create a new password for your account.</p>

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
                <div class="login-footer">
                    <a href="login.php" class="return-link">Return to Login</a>
                </div>
            </div>
        <?php endif; ?>

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

        // Toggle Password Visibility Logic
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