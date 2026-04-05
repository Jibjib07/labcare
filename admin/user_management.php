<?php
include '../includes/db.php';
require '../includes/admin_auth.php';

// CSRF TOKEN GENERATION
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ===============================
//  HANDLE AJAX REQUESTS
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // 🛡️ SESSION TIMEOUT CHECK
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired', 'session_expired' => true]);
        exit;
    }

    // 🛡️ SMART RATE LIMITING (PER ACTION)
    $now = time();
    $action = $_POST['action'] ?? 'unknown';

    $_SESSION['rate_limits'] = $_SESSION['rate_limits'] ?? [];

    // Define only critical limits
    $limits = [
        'admin_send_reset' => 10,
        'default' => 0
    ];

    $wait = $limits[$action] ?? $limits['default'];
    $last = $_SESSION['rate_limits'][$action] ?? 0;

    if ($wait > 0 && ($now - $last) < $wait) {
        $remaining = $wait - ($now - $last);
        echo json_encode([
            'status' => 'error',
            'message' => "Please wait {$remaining}s before retrying."
        ]);
        exit;
    }

    // Update timestamp only if limited
    if ($wait > 0) {
        $_SESSION['rate_limits'][$action] = $now;
    }

    // 🛡️ CSRF VERIFICATION
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $adminId = $_SESSION['user_id'] ?? 0;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token', 'csrf_token' => $_SESSION['csrf_token']]);
        exit;
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $new_csrf = $_SESSION['csrf_token'];
    $action = $_POST['action'];
    $adminId = $_SESSION['user_id'];

    try {
        // --- 1. ADD USER ---
        if ($action === 'add_user') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $allowedRoles = ['Admin', 'Staff'];
            $role = $_POST['role'] ?? '';

            if (!in_array($role, $allowedRoles)) {
                throw new Exception("Invalid role provided.");
            }
            if (!$name || !$email || !$password) throw new Exception("Missing required fields");
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Invalid email format");

            // 🛡️ PASSWORD COMPLEXITY ENFORCEMENT
            $uppercase = preg_match('@[A-Z]@', $password);
            $lowercase = preg_match('@[a-z]@', $password);
            $number    = preg_match('@[0-9]@', $password);
            $specialChars = preg_match('@[^\w]@', $password);

            if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
                throw new Exception("Password does not meet security requirements.");
            }

            $check = $conn->prepare("SELECT user_id FROM users WHERE user_email = ?");
            $check->bind_param("s", $email);
            $check->execute();

            if ($check->get_result()->num_rows > 0) throw new Exception("Email already exists");

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (user_name, user_email, user_password, user_role, user_status) VALUES (?, ?, ?, ?, 'Active')");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $newUserId = $stmt->insert_id;

                // 🛡️ LOG AUDIT: ADD (Safely wrapped to prevent crashes)
                try {
                    $audit = $conn->prepare("INSERT INTO user_audit_trail (admin_id, user_id, action_type, new_data) VALUES (?, ?, 'Add', ?)");
                    if ($audit) {
                        $logData = json_encode(['name' => $name, 'email' => $email, 'role' => $role]);
                        $audit->bind_param("iis", $adminId, $newUserId, $logData);
                        $audit->execute();
                    }
                } catch (Exception $logError) {
                    error_log("Audit Log Failed: " . $logError->getMessage());
                }

                echo json_encode(['status' => 'success', 'id' => $newUserId, 'csrf_token' => $new_csrf]);
                exit;
            }
        }

        // --- 2. UPDATE USER ---
        if ($action === 'update_user') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);

            // Check target user's current role before allowing edit
            $checkRole = $conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
            $checkRole->bind_param("i", $id);
            $checkRole->execute();
            $targetUser = $checkRole->get_result()->fetch_assoc();

            if (!$targetUser) throw new Exception("User not found.");

            // RESTRICTION: If target is an Admin and NOT the person logged in, block the edit.
            if (strtolower($targetUser['user_role']) === 'admin' && $id !== (int)$adminId) {
                throw new Exception("Security Restriction: Administrators cannot edit details of other Administrators.");
            }

            if (!$name || !$email) throw new Exception("Name and Email are required");
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Invalid email format");

            // Perform the update (Removed role from update to prevent role-changing)
            $stmt = $conn->prepare("UPDATE users SET user_name=?, user_email=? WHERE user_id=?");
            $stmt->bind_param("ssi", $name, $email, $id);
            if ($stmt->execute()) {
                // 🛡️ LOG AUDIT: UPDATE (Safely wrapped to prevent crashes)
                try {
                    $audit = $conn->prepare("INSERT INTO user_audit_trail (admin_id, user_id, action_type, old_data, new_data) VALUES (?, ?, 'Update', ?, ?)");
                    if ($audit) {
                        $newData = json_encode(['name' => $name, 'email' => $email, 'role' => $role]);
                        $oldJson = json_encode($oldData);
                        $audit->bind_param("iiss", $adminId, $id, $oldJson, $newData);
                        $audit->execute();
                    }
                } catch (Exception $logError) {
                    error_log("Audit Log Failed: " . $logError->getMessage());
                }

                echo json_encode(['status' => 'success', 'csrf_token' => $new_csrf]);
                exit;
            }
        }

        // --- 3. UPDATE STATUS ---
        if ($action === 'update_status') {
            $id = (int)$_POST['id'];
            $status = $_POST['status'] ?? '';
            $allowedStatus = ['Active', 'Deactivated'];
            if (!in_array($status, $allowedStatus)) {
                throw new Exception("Invalid status.");
            }

            if ($id === (int)$adminId) throw new Exception("You cannot deactivate your own account.");

            $oldStmt = $conn->prepare("SELECT user_status FROM users WHERE user_id=?");
            $oldStmt->bind_param("i", $id);
            $oldStmt->execute();
            $oldData = $oldStmt->get_result()->fetch_assoc();

            $stmt = $conn->prepare("UPDATE users SET user_status=? WHERE user_id=?");
            $stmt->bind_param("si", $status, $id);

            if ($stmt->execute()) {
                // 🛡️ LOG AUDIT: STATUS CHANGE (Safely wrapped to prevent crashes)
                try {
                    $audit = $conn->prepare("INSERT INTO user_audit_trail (admin_id, user_id, action_type, old_data, new_data) VALUES (?, ?, 'Status Change', ?, ?)");
                    if ($audit) {
                        $oldJson = json_encode($oldData);
                        $newJson = json_encode(['user_status' => $status]);
                        $audit->bind_param("iiss", $adminId, $id, $oldJson, $newJson);
                        $audit->execute();
                    }
                } catch (Exception $logError) {
                    error_log("Audit Log Failed: " . $logError->getMessage());
                }

                echo json_encode(['status' => 'success', 'csrf_token' => $new_csrf]);
                exit;
            }
        }

        // --- 4. ADMIN SEND RESET LINK ---
        if ($action === 'admin_send_reset') {
            $id = (int)$_POST['id'];

            $stmt = $conn->prepare("SELECT user_name, user_email FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) throw new Exception("User not found.");

            // Rate Limit
            $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM password_resets WHERE user_id = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()['cnt'] >= 3) {
                throw new Exception("Reset limit reached (3/hr) for this user.");
            }

            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Clear old tokens
            $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $del->bind_param("i", $id);
            $del->execute();

            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $id, $tokenHash, $expires);

            if ($stmt->execute()) {
                // EXPLICITLY REQUIRE AND LOAD PHPMAILER ONLY HERE
                require '../vendor/autoload.php';
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'cvsuccclabcare26@gmail.com';
                    $mail->Password = 'ftla jdqz yifw jejl';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('cvsuccclabcare26@gmail.com', 'LabCare');
                    $mail->addAddress($user['user_email'], $user['user_name']);
                    $mail->Subject = 'LabCare - Password Reset Initiated by Admin';

                    $resetLink = BASE_URL . "password_resets.php?token=" . $rawToken;

                    $mail->Body = "Hello {$user['user_name']},\n\nAn administrator has initiated a password reset for your account. Please click the link below to set a new password:\n\n{$resetLink}\n\nThis link expires in 1 hour.";

                    if ($mail->send()) {
                        // LOG AUDIT TRAIL
                        try {
                            $actionType = 'Send Password Reset Link';
                            $logData = json_encode([
                                'message' => "Reset link sent to {$user['user_email']} by admin ID {$adminId}",
                                'ip' => $_SERVER['REMOTE_ADDR'],
                                'user_agent' => $_SERVER['HTTP_USER_AGENT']
                            ]);

                            $audit = $conn->prepare("INSERT INTO user_audit_trail (admin_id, user_id, action_type, new_data) VALUES (?, ?, ?, ?)");
                            if ($audit) {
                                $audit->bind_param("iiss", $adminId, $id, $actionType, $logData);
                                $audit->execute();
                            }
                        } catch (Exception $logError) {
                            error_log("Audit log failed: " . $logError->getMessage());
                        }

                        echo json_encode(['status' => 'success', 'csrf_token' => $new_csrf]);
                        exit;
                    }
                } catch (\Exception $e) { // Catch any mailer-specific exceptions locally
                    throw new Exception("Mailer Error: " . $mail->ErrorInfo);
                }
            }
        }

        throw new Exception("Invalid action provided.");
    } catch (Exception $e) {
        // Global catch block for all standard PHP exceptions
        echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'csrf_token' => $new_csrf]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/user_management.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>User Management</h1>
            <p>Manage system access, update user roles, and control account permissions.</p>
        </div>

        <div class="user-layout">
            <div class="panel white-panel user-list-panel">
                <div class="panel-header-row">
                    <h3><span id="list-status-title">Active</span> User List</h3>
                    <button id="btn-open-add-modal" class="btn-green-add">
                        <span class="desktop-text"><i class="fas fa-plus-circle"></i> Add</span>
                        <span class="mobile-icon"><i class="fas fa-plus"></i></span>
                    </button>
                </div>

                <div class="search-filter-row">
                    <input type="text" id="search-input" class="search-input" placeholder="Type a name or search...">
                    <select id="status-filter" class="filter-btn">
                        <option value="All">All</option>
                        <option value="Active">Active</option>
                        <option value="Deactivated">Deactivated</option>
                    </select>
                </div>

                <div class="list-container" id="user-list-container">
                    <?php
                    $query = "SELECT * FROM users ORDER BY user_id DESC";
                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                        $firstRow = true;
                        while ($row = $result->fetch_assoc()) {
                            $rawStatus = $row['user_status'] ? $row['user_status'] : 'Active';
                            $statusText = ucfirst(strtolower($rawStatus));
                            $statusClass = ($statusText === 'Active') ? 'active' : 'deactivated';
                            $roleText = (strtolower($row['user_role']) === 'admin') ? 'Admin' : 'Staff';
                            $selectedClass = $firstRow ? 'selected' : '';
                    ?>
                            <div class="user-list-item <?php echo $selectedClass; ?>"
                                data-id="<?php echo $row['user_id']; ?>"
                                data-name="<?php echo htmlspecialchars($row['user_name']); ?>"
                                data-role="<?php echo htmlspecialchars($roleText); ?>"
                                data-email="<?php echo htmlspecialchars($row['user_email']); ?>"
                                data-status="<?php echo htmlspecialchars($statusText); ?>">
                                <div class="user-list-info">
                                    <span class="fw-bold"><?php echo htmlspecialchars($row['user_name']); ?></span>
                                    <span class="text-gray">| <?php echo htmlspecialchars($roleText); ?></span>
                                </div>
                                <div class="user-list-status">
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                                </div>
                            </div>
                    <?php
                            $firstRow = false;
                        }
                    } else {
                        echo '<div class="no-users-msg">No users found.</div>';
                    }
                    ?>
                </div>
            </div>

            <div class="info-column-right">

                <div class="mobile-detail-header" id="mobile-detail-header">
                    <button id="mobile-back-btn" class="mobile-back-btn">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h2 id="mobile-detail-title">User Details</h2>
                </div>
                <div class="panel white-panel user-info-panel" id="user-info-panel">
                    <div class="panel-header-row">
                        <h3>User Information</h3>
                        <div class="action-buttons" id="info-action-buttons">
                            <button id="btn-edit" class="btn-edit">
                                <span class="desktop-text"><i class="fas fa-pen"></i> Edit</span>
                                <span class="mobile-icon"><i class="fas fa-pen"></i></span>
                            </button>
                            <button id="btn-deactivate-trigger" class="btn-deactivate">
                                <span class="desktop-text"><i class="fas fa-user-slash"></i> Deactivate</span>
                                <span class="mobile-icon"><i class="fas fa-user-slash"></i></span>
                            </button>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Full Name</label>
                            <div class="info-value" id="info-name">-</div>
                        </div>
                        <div class="info-item">
                            <label>Email Address</label>
                            <div class="info-value" id="info-email">-</div>
                        </div>
                        <div class="info-item">
                            <label>Role</label>
                            <div class="info-value role-text" id="info-role">-</div>
                        </div>
                        <div class="info-item">
                            <label>User Status</label>
                            <div class="info-value status-bg" id="info-status">-</div>
                        </div>
                    </div>
                </div>
                <div class="divider"></div>

                <div class="panel white-panel security-panel">
                    <h3>Account Recovery</h3>
                    <div class="security-content">
                        <p class="security-text">
                            Initiate a secure password reset for this user. A unique recovery link <br> will be sent to their registered email address.
                        </p>
                        <button type="button" id="resetBtn" class="btn-purple-reset">
                            <i class="fas fa-lock"></i> Send Password Reset Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="add-user-modal" class="modal-overlay">
        <div class="modal-content modal-large">
            <h2 style="font-size: 16px; margin-bottom: 20px;">Adding New User</h2>
            <form class="user-form" id="add-user-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" id="logged-in-admin-id" value="<?php echo $_SESSION['user_id']; ?>">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="add-name" class="form-input" placeholder="Ex. Juan Dela Cruz">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="add-email" class="form-input" placeholder="Ex. JuanDee@gmail.com">
                </div>
                <div class="form-group inline-role-group">
                    <label style="margin-right: 15px;">Role:</label>
                    <div class="role-toggle">
                        <button type="button" class="role-btn" data-val="Admin">Admin</button>
                        <button type="button" class="role-btn active" data-val="Staff">Staff</button>
                        <input type="hidden" id="add-role" value="Staff">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="add-password" class="form-input" placeholder="Enter password">
                        <i class="fas fa-eye toggle-password" data-target="add-password"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="add-confirm-password" class="form-input" placeholder="Re-enter password">
                        <i class="fas fa-eye toggle-password" data-target="add-confirm-password"></i>
                    </div>
                </div>
                <div class="modal-actions" style="margin-top: 30px;">
                    <button type="button" id="btn-cancel-add" class="btn-cancel-new">Cancel</button>
                    <button type="button" id="btn-confirm-add" class="btn-green-add"><i class="fas fa-plus-circle"></i> Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deactivate-modal" class="modal-overlay">
        <div class="modal-content">
            <h2>Deactivate this User</h2>
            <p style="color: #666; font-size: 0.85rem; margin-bottom: 25px; line-height: 1.5;">
                This user will no longer be able to log in, but their historical activity will be preserved in the audit logs.
            </p>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="deact-name" class="form-input readonly-input" readonly>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="text" id="deact-email" class="form-input readonly-input" readonly>
            </div>
            <div class="form-group">
                <label>Role</label>
                <input type="text" id="deact-role" class="form-input readonly-input" readonly>
            </div>
            <div class="modal-actions" style="margin-top: 25px;">
                <button id="btn-cancel-modal" class="btn-cancel-new">Cancel</button>
                <button id="btn-confirm-deactivate" class="btn-red">
                    <i class="fas fa-user-slash"></i> Deactivate
                </button>
            </div>
        </div>
    </div>

    <div id="reset-modal" class="modal-overlay">
        <div class="modal-content">
            <h2>Send Password Reset</h2>
            <p style="color: #666; font-size: 0.85rem; margin-bottom: 25px; line-height: 1.5;">
                A secure reset link will be sent to this user's email.
            </p>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="reset-name" class="form-input readonly-input" readonly>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="text" id="reset-email" class="form-input readonly-input" readonly>
            </div>

            <div class="modal-actions">
                <button id="btn-cancel-reset" class="btn-cancel-new">Cancel</button>
                <button id="btn-confirm-reset" class="btn-purple-reset">
                    <i class="fas fa-lock"></i> Send Link
                </button>
            </div>
        </div>
    </div>
    </div>

    <div id="authToast" class="toast">
        <div id="toast-icon"></div>
        <div>
            <div id="toast-title"></div>
            <div id="toast-message"></div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/user_management.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const listContainer = document.getElementById("user-list-container");
            const searchInput = document.getElementById("search-input");
            const statusFilter = document.getElementById("status-filter");

            function applyFilters() {
                const term = searchInput.value.toLowerCase();
                const status = statusFilter.value;
                let visibleCount = 0;

                document.querySelectorAll(".user-list-item").forEach((item) => {
                    const name = item.dataset.name.toLowerCase();
                    const itemStatus = item.dataset.status;
                    const show = name.includes(term) && (status === "All" || itemStatus === status);
                    item.style.display = show ? "flex" : "none";
                    if (show) visibleCount++;
                });

                let msg = listContainer.querySelector('.no-users-msg');
                if (visibleCount === 0) {
                    if (!msg) {
                        msg = document.createElement('div');
                        msg.className = 'no-users-msg';
                        msg.innerText = 'No users found matching your criteria.';
                        listContainer.appendChild(msg);
                    }
                } else if (msg) msg.remove();
            }

            searchInput.addEventListener("input", applyFilters);
            statusFilter.addEventListener("change", applyFilters);
        });
    </script>
</body>

</html>