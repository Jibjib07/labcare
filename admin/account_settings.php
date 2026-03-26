<?php
include '../includes/db.php';
require '../includes/admin_auth.php'; // This already handles role checking

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    die("User is not logged in.");
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

/* SAVE PROFILE */
if (isset($_POST['save_profile'])) {

    // 🔹 Trim inputs
    $user_name = trim($_POST['full_name']);
    $user_email = trim($_POST['email']);

    // 🔹 Frontend validation fallback (redundant server-side)
    if (empty($user_name) || empty($user_email)) {
        $error = "Full Name and Email Address are required.";
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        // 🔹 Fetch current user data to capture old values for audit
        $fetchStmt = $conn->prepare("SELECT user_name, user_email FROM users WHERE user_id = ?");
        $fetchStmt->bind_param("i", $user_id);
        $fetchStmt->execute();
        $oldUser = $fetchStmt->get_result()->fetch_assoc();
        $fetchStmt->close();

        if (!$oldUser) {
            $error = "User not found.";
        } else {

            // 🔹 Check for duplicate emails
            $check = $conn->prepare("SELECT user_id FROM users WHERE user_email = ? AND user_id != ?");
            $check->bind_param("si", $user_email, $user_id);
            $check->execute();
            $check_result = $check->get_result();

            if ($check_result->num_rows > 0) {
                $error = "That email address is already being used by another account.";
            } else {

                // 🔹 Update user profile
                $stmt = $conn->prepare("UPDATE users SET user_name = ?, user_email = ? WHERE user_id = ?");
                $stmt->bind_param("ssi", $user_name, $user_email, $user_id);

                if ($stmt->execute()) {

                    $success = "saved";

                    // 🔹 Update session values
                    $_SESSION['user_name'] = $user_name;
                    $_SESSION['user_email'] = $user_email;

                    // 🔹 Prepare audit trail
                    $oldData = [
                        'old_name' => $oldUser['user_name'],
                        'old_email' => $oldUser['user_email']
                    ];
                    $newData = [
                        'new_name' => $user_name,
                        'new_email' => $user_email
                    ];

                    $logStmt = $conn->prepare("
                        INSERT INTO user_audit_trail 
                        (admin_id, user_id, action_type, old_data, new_data, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $actionType = 'Update';
                    $oldDataJson = json_encode($oldData);
                    $newDataJson = json_encode($newData);

                    $logStmt->bind_param("iisss", $user_id, $user_id, $actionType, $oldDataJson, $newDataJson);
                    $logStmt->execute();
                    $logStmt->close();

                } else {
                    $error = "Failed to update profile.";
                }

                $stmt->close();
            }

            $check->close();
        }
    }
}

/* --- SEND PASSWORD RESET LINK (AJAX) --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'self_send_reset') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // RATE LIMIT
    $_SESSION['last_reset'] = $_SESSION['last_reset'] ?? 0;
    if (time() - $_SESSION['last_reset'] < 15) {
        echo json_encode(['status' => 'error', 'message' => 'Please wait before retrying.']);
        exit;
    }
    $_SESSION['last_reset'] = time();

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF']);
        exit;
    }

    // GET USER
    $stmt = $conn->prepare("SELECT user_name, user_email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // Clean old tokens
    $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $del->bind_param("i", $user_id);
    $del->execute();
    $del->close();

    // Insert new token
    $ins = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())");
    $ins->bind_param("iss", $user_id, $tokenHash, $expiry);
    if (!$ins->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to generate reset token']);
        exit;
    }
    $ins->close();

    // SEND EMAIL
    require '../vendor/autoload.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cvsuccclabcare26@gmail.com';
        $mail->Password = 'ftla jdqz yifw jejl';  // <-- Don't forget to generate a new app password and put it here!
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('cvsuccclabcare26@gmail.com', 'LabCare');
        $mail->addAddress($user['user_email'], $user['user_name']);

        // Uses the BASE_URL defined in includes/db.php!
        $resetLink = BASE_URL . "password_resets.php?token=" . $rawToken;

        $mail->Subject = 'LabCare Password Reset';
        $mail->Body = "Hello {$user['user_name']},\n\nClick the link below to reset your password:\n\n{$resetLink}\n\nThis link expires in 1 hour.";

        $mail->send();

        // ✅ LOG AUDIT TRAIL
        $logStmt = $conn->prepare("
            INSERT INTO user_audit_trail (admin_id, user_id, action_type, new_data, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $adminId = $user_id; // if this is a self-action by user
        $actionType = 'Send Password Reset Link';
        $logData = json_encode([
            'email' => $user['user_email'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'message' => "Reset link sent to {$user['user_email']} by admin ID {$adminId}"
        ]);
        $logStmt->bind_param("iiss", $adminId, $user_id, $actionType, $logData);
        $logStmt->execute();
        $logStmt->close();

        echo json_encode(['status' => 'success', 'message' => 'Password reset email sent', 'csrf_token' => $_SESSION['csrf_token']]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Mailer Error: '.$mail->ErrorInfo, 'csrf_token' => $_SESSION['csrf_token']]);
        exit;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/account_settings.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div id="authToast" class="toast">
        <div id="toast-icon"></div>
        <div class="toast-text">
            <div id="toast-title"></div>
            <div id="toast-message"></div>
        </div>
    </div>

    <!-- PHP success trigger for profile save -->
    <?php if (!empty($success)) : ?>
        <input type="hidden" id="php_success" value="<?php echo htmlspecialchars($success); ?>">
    <?php endif; ?>

    <?php if (!empty($error)) : ?>
        <input type="hidden" id="php_error" value="<?php echo htmlspecialchars($error); ?>">
    <?php endif; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Account Settings</h1>
            <p>Update your profile details, manage login security, and view system policies.</p>
        </div>

        <div class="account-grid">
            <div class="left-stack">

                <div class="panel white-panel">
                    <div class="panel-header-row">
                        <h3>Profile Details</h3>
                    </div>
                    <div class="divider"></div>

                    <form method="POST" action="" id="profileForm">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input
                                type="text"
                                name="full_name"
                                id="full_name"
                                class="input-field"
                                value="<?php echo htmlspecialchars($user['user_name'] ?? ''); ?>"
                                readonly
                            >
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="input-field"
                                value="<?php echo htmlspecialchars($user['user_email'] ?? ''); ?>"
                                readonly
                            >
                        </div>

                        <div class="save-btn-container">
                            <button type="button" id="editSaveBtn" class="btn-green-save">
                                <i class="fas fa-edit"></i>
                                <span>Edit</span>
                            </button>

                            <button type="submit" name="save_profile" id="hiddenSubmit" style="display: none;"></button>
                        </div>
                    </form>
                </div>

                <div class="panel white-panel security-panel">
                    <div class="panel-header-row">
                        <h3>Security & Login</h3>
                    </div>
                    <div class="divider"></div>

                    <div class="security-content">
                        <p class="security-text">
                            To update your password, we will send a secure reset link to your registered email address.
                        </p>

                        <button type="button" id="resetBtn" class="btn-purple-reset">
                            <i class="fas fa-paper-plane"></i>
                            <span>Send Password Reset Link</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="panel white-panel info-panel">
                <div class="panel-header-row">
                </div>

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

    <div id="reset-modal" class="modal-overlay">
        <div class="modal-content">
            <h2>Send Password Reset</h2>

            <p class="modal-subtext">
                A secure reset link will be sent to your registered email.
            </p>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="reset-name" class="input-field readonly-input" readonly>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="text" id="reset-email" class="input-field readonly-input" readonly>
            </div>

            <div class="modal-actions">
                <button type="button" id="btn-cancel-reset" class="btn-cancel-new">Cancel</button>
                <button type="button" id="btn-confirm-reset" class="btn-purple-reset">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send Link</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        window.csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const editSaveBtn = document.getElementById("editSaveBtn");
            const hiddenSubmit = document.getElementById("hiddenSubmit");
            const fullNameInput = document.getElementById("full_name");
            const emailInput = document.getElementById("email");

            if (editSaveBtn && hiddenSubmit && fullNameInput && emailInput) {
                let isEditing = false;

                editSaveBtn.addEventListener("click", function () {
                    if (!isEditing) {
                        fullNameInput.removeAttribute("readonly");
                        emailInput.removeAttribute("readonly");

                        editSaveBtn.innerHTML = `
                            <i class="fas fa-save"></i>
                            <span>Save</span>
                        `;

                        isEditing = true;
                    } else {
                        hiddenSubmit.click();
                    }
                });
            }
        });
    </script>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/account_settings.js?v=<?php echo time(); ?>"></script>
</body>
</html>