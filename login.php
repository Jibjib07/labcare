<?php
session_start();

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Role-based redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://api.fontshare.com/v2/css?f[]=geist@1,2&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">

    <style>
        .btn-back {
            position: fixed;
            /* Keeps it in the top corner even if the screen is small/scrolling */
            top: 30px;
            left: 30px;
            color: #ffffff;
            /* White to contrast with the green background */
            background: rgba(255, 255, 255, 0.15);
            /* Slightly frosted glass effect */
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 18px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: translateX(-5px);
            /* Subtle slide left on hover */
            color: #1b4d3e;
            /* Turns dark green on hover */
            border-color: #ffffff;
        }
    </style>
</head>

<body class="login-body">

    <a href="landing.php" class="btn-back" title="Back to Landing Page">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="glass-container">
        <div class="brand-section">
            <img src="assets/logo.png" alt="LabCare Logo" class="main-logo">
            <span class="brand-name">LAB<span class="bname2">CARE</span></span>
        </div>

        <div class="login-card">
            <h2>Welcome Back!</h2>
            <p class="subtitle">Enter your email below to login to your account</p>

            <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
                <div class="success-msg" style="color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                    <i class="fas fa-check-circle"></i> Password updated successfully! Please login.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-msg" style="color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                    if ($_GET['error'] == 'invalid_password') echo "Incorrect password.";
                    if ($_GET['error'] == 'user_not_found') echo "Account not found.";
                    if ($_GET['error'] == 'inactive') echo "Account is disabled.";
                    ?>
                </div>
            <?php endif; ?>

            <form action="includes/login_handler.php" method="POST">
                <div class="input-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" placeholder="Ex. JohnDoe@gmail.com" required>
                        <i class="fas fa-at"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye-slash" id="togglePassword"></i>
                    </div>
                </div>

                <button type="submit" name="login_btn" class="btn-login">Login</button>
            </form>

            <div class="login-footer">
                <a href="forgot_password.php">Forgot password?</a>
            </div>
        </div>

        <p class="disclaimer"><strong>Disclaimer:</strong> For Computer Laboratory Use Only</p>
    </div>

    <script>
        const togglePassword = document.querySelector('.fa-eye-slash');
        const passwordInput = document.querySelector('input[name="password"]');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>