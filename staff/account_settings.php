<?php
include '../includes/db.php';
require '../includes/staff_auth.php'; // This already handles role checking

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    die("User is not logged in.");
}

$user_id = $_SESSION['user_id'];

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

        // Uses the BASE_URL defined in includes/db.php
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
        echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo, 'csrf_token' => $_SESSION['csrf_token']]);
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

    <link href="https://cdn.jsdelivr.net/npm/geist-font@latest/dist/geist-sans/style.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/account_settings.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div id="authToast" class="toast">
        <div id="toast-icon"></div>
        <div>
            <div id="toast-title"></div>
            <div id="toast-message"></div>
        </div>
    </div>

    <div id="toast-container" class="toast-container"></div>

    <div class="main-content">
        <div class="page-header">
            <h1>Account Settings</h1>
            <p>View your profile details, manage login security, and view system policies.</p>
        </div>

        <div class="account-grid">

            <div class="left-stack">

                <div class="panel white-panel">
                    <h3>Profile Details</h3>
                    <div class="divider"></div>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input
                            type="text"
                            class="input-field readonly-input"
                            value="<?php echo htmlspecialchars($user['user_name']); ?>"
                            style="background-color: #f9f9f9; color: #888; cursor: not-allowed;"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input
                            type="email"
                            class="input-field readonly-input"
                            value="<?php echo htmlspecialchars($user['user_email']); ?>"
                            style="background-color: #f9f9f9; color: #888; cursor: not-allowed;"
                            readonly>
                    </div>
                </div>

                <div class="panel white-panel security-panel">
                    <h3>Security & Login</h3>
                    <div class="divider"></div>

                    <div class="security-content">
                        <p class="security-text">
                            To update your password, we will send a secure reset link to your registered email address.
                        </p>

                        <button type="button" id="resetBtn" class="btn-purple-reset">
                            <i class="fas fa-paper-plane"></i> Send Reset Link
                        </button>
                    </div>
                </div>

            </div>

            <div class="panel white-panel info-panel">
                <div class="info-block">
                    <h4>CVSU Mission</h4>
                    <p>
                        Cavite State University shall provide excellent, equitable and relevant educational opportunities in the arts, sciences and technology through quality instruction and responsive research and development activities. It shall produce professional, skilled and morally upright individuals for global competitiveness.
                    </p>
                </div>

                <div class="info-block">
                    <h4>CVSU Vision</h4>
                    <p>
                        The premier university in historic Cavite globally recognized for excellence in character development, academics, research, innovation and sustainable community engagement.
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
            <h2 style="color:#7B1FA2;">Send Password Reset</h2>

            <p style="color: #666; font-size: 0.85rem; margin-bottom: 20px;">
                A secure reset link will be sent to your registered email.
            </p>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="reset-name" class="input-field readonly-input" style="background-color: #f9f9f9; color: #888; cursor: not-allowed;" readonly>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="text" id="reset-email" class="input-field readonly-input" style="background-color: #f9f9f9; color: #888; cursor: not-allowed;" readonly>
            </div>

            <div class="modal-actions">
                <button id="btn-cancel-reset" class="btn-cancel-new">Cancel</button>
                <button id="btn-confirm-reset" class="btn-purple-reset">
                    <i class="fas fa-paper-plane"></i> Send Link
                </button>
            </div>
        </div>
    </div>

    <script>
        window.csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";
    </script>
    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/account_settings.js?v=<?php echo time(); ?>"></script>
</body>

</html>