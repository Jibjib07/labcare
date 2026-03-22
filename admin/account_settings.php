<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("User is not logged in.");
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

/* SAVE PROFILE */
if (isset($_POST['save_profile'])) {
    $user_name = trim($_POST['full_name']);
    $user_email = trim($_POST['email']);

    if (empty($user_name) || empty($user_email)) {
        $error = "Full Name and Email Address are required.";
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE user_email = ? AND user_id != ?");
        $check->bind_param("si", $user_email, $user_id);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $error = "That email address is already being used by another account.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET user_name = ?, user_email = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $user_name, $user_email, $user_id);

            if ($stmt->execute()) {
                $success = "saved";

                $_SESSION['user_name'] = $user_name;
                $_SESSION['user_email'] = $user_email;
            } else {
                $error = "Failed to update profile.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

/* LOAD CURRENT USER DATA */
$stmt = $conn->prepare("SELECT user_name, user_email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Account Settings - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/account_settings.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- PHP success trigger for profile save -->
    <?php if (!empty($success)) : ?>
        <input type="hidden" id="php_success" value="<?php echo htmlspecialchars($success); ?>">
    <?php endif; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Account Settings</h1>
            <p>Update your profile details, manage login security, and view system policies.</p>
        </div>

        <div class="account-grid">

            <div class="left-stack">

                <div class="panel white-panel">
                    <h3>Profile Details</h3>
                    <div class="divider"></div>

                    <?php if (!empty($error)) : ?>
                        <p style="color: red; margin-bottom: 15px;">
                            <?php echo htmlspecialchars($error); ?>
                        </p>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input
                                type="text"
                                name="full_name"
                                class="input-field"
                                value="<?php echo htmlspecialchars($user['user_name']); ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input
                                type="email"
                                name="email"
                                class="input-field"
                                value="<?php echo htmlspecialchars($user['user_email']); ?>"
                                required>
                        </div>

                        <div class="save-btn-container">
                            <button type="submit" name="save_profile" class="btn-green-save">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </form>
                </div>

                <div class="panel white-panel security-panel">
                    <h3>Security & Login</h3>
                    <div class="divider"></div>

                    <div class="security-content">
                        <p class="security-text">
                            To update your password, we will send a secure reset link to your registered email address.
                        </p>

                        <button type="button" id="resetBtn" class="btn-purple-reset">
                            <i class="fas fa-lock"></i> Send Password Reset Link
                        </button>
                    </div>
                </div>

            </div>

            <div class="panel white-panel info-panel">
                <h3>System Information</h3>

                <div class="info-block">
                    <h4>CVSU Mission</h4>
                    <p>
                        Cavite State University shall provide
                        <strong>excellent, equitable, and relevant educational opportunities</strong>
                        in the arts, sciences, and technology through
                        <strong>quality instruction</strong> and
                        <strong>responsive research</strong> and
                        <strong>development activities</strong>. It shall
                        <strong>produce professional, skilled, and morally upright individuals</strong>
                        for global competitiveness.
                    </p>
                </div>

                <div class="info-block">
                    <h4>CVSU Vision</h4>
                    <p>
                        The <strong>premier university</strong> in historic Cavite recognized for
                        <strong>excellence</strong> in the development of
                        <strong>globally competitive and morally upright individuals</strong>.
                    </p>
                </div>

                <div class="info-block">
                    <h4>CVSU Quality Policy</h4>
                    <p>
                        We <strong>C</strong>ommit to the highest standards of education,
                        <strong>V</strong>alue our stakeholders,
                        <strong>S</strong>trive for continual improvement of our products and services, and
                        <strong>U</strong>phold the University's tenets of Truth, Excellence, and Service to produce globally competitive and morally upright individuals.
                    </p>
                </div>

                <div class="disclaimer">
                    <strong>Disclaimer:</strong> This system is for Computer Laboratory Use Only.
                </div>
            </div>

        </div>
    </div>

    <script src="js/account_settings.js?v=<?php echo time(); ?>"></script>
</body>

</html>