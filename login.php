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
    if (strtolower($_SESSION['user_role']) === 'admin') {
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
</head>

<body class="login-body">
    <div class="glass-container">
        <div class="brand-section">
            <img src="assets/logo.png" alt="LabCare Logo" class="main-logo">
            <span class="brand-name">LAB<span class="bname2">CARE</span></span>
        </div>

        <div class="login-card">
            <h2>Welcome Back!</h2>
            <p class="subtitle">Enter your email below to login to your account</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-msg">
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