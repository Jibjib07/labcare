<?php include '../includes/db.php'; ?>
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
            <div class="panel white-panel left-form-panel">
                <div class="panel-header-row">
                    <h3>Adding New User</h3>
                    <button id="btn-add-user" class="btn-green-add"><i class="fas fa-plus-circle"></i> Add</button>
                </div>

                <form class="user-form" id="add-user-form">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="add-name" class="form-input" placeholder="Ex. Juan Dela Cruz">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="add-email" class="form-input" placeholder="Ex. JuanDee@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="add-role" value="Staff" checked> Staff
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="add-role" value="Admin"> Admin
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-input" placeholder="Enter password">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" class="form-input" placeholder="Re-enter password">
                    </div>
                    <div class="form-group">
                        <label>User Status</label>
                        <select id="add-status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Deactivated">Deactivated</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="right-column-wrapper">
                
                <div class="panel white-panel user-list-panel">
                    <h3>Existing User List</h3>
                    <div class="search-filter-row">
                        <input type="text" class="search-input" placeholder="Type a name...">
                        <button class="filter-btn">Filter <i class="fas fa-filter"></i></button>
                    </div>

                    <div class="table-container" id="scrollable-table-container">
                        <table class="user-table" id="user-table">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th style="display:none;">Email</th> </tr>
                            </thead>
                            <tbody id="user-table-body">
                                <tr style="cursor: pointer;" data-id="999">
                                    <td>Jane Doe</td>
                                    <td>Admin</td>
                                    <td>
                                        <span class="badge active" style="background-color:#4CAF50; padding:4px 12px; border-radius:12px; color:white; font-size:11px; font-weight:700; display:inline-block;">
                                            Active
                                        </span>
                                    </td>
                                    <td style="display:none;">janedoe@gmail.com</td>
                                </tr>
                                <?php
                                $query = "SELECT * FROM users ORDER BY user_id DESC";
                                $result = $conn->query($query);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $statusText = $row['user_status'] ? $row['user_status'] : 'Active';
                                        $statusClass = ($statusText === 'Active') ? 'active' : 'deactivated';
                                        $bgColor = ($statusText === 'Active') ? '#4CAF50' : '#9E9E9E';
                                        ?>
                                        <tr style="cursor: pointer;" data-id="<?php echo $row['user_id']; ?>">
                                            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['user_role']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $statusClass; ?>" 
                                                    style="background-color:<?php echo $bgColor; ?>; padding:4px 12px; border-radius:12px; color:white; font-size:11px; font-weight:700; display:inline-block;">
                                                    <?php echo htmlspecialchars($statusText); ?>
                                                </span>
                                            </td>
                                            <td style="display:none;"><?php echo htmlspecialchars($row['user_email']); ?></td>
                                        </tr>
                                        <?php
                                    }
                                } 
                                ?>
                            </tbody>
                        </table>
                    </div>
                    </div>

                <div class="panel white-panel user-info-panel" id="user-info-panel">
                    <div class="panel-header-row">
                        <h3>User Information</h3>
                        <div class="action-buttons" id="info-action-buttons">
                            <button id="btn-edit" class="btn-edit"><i class="fas fa-pen"></i> Edit</button>
                            <button id="btn-deactivate-trigger" class="btn-deactivate"><i class="fas fa-user-slash"></i> Deactivate</button>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Full Name</label>
                            <div class="info-value" id="info-name"></div> </div>
                        <div class="info-item">
                            <label>User Status</label>
                            <div class="info-value status-bg" id="info-status"></div> </div>
                        <div class="info-item full-width">
                            <label>Email Address</label>
                            <div class="info-value" id="info-email"></div> </div>
                        <div class="info-item full-width">
                            <label>Role</label>
                            <div class="info-value role-text" id="info-role"></div> </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="deactivate-modal" class="modal-overlay">
            <div class="modal-content">
                <h2>Deactivate this User</h2>
                <p>This user will no longer be able to log in, but their historical activity will be preserved in the audit logs.</p>
                
                <div class="modal-inputs">
                    <label>Full Name</label>
                    <input type="text" id="modal-name" readonly class="form-input readonly-input">
                    <label>Email Address</label>
                    <input type="text" id="modal-email" readonly class="form-input readonly-input">
                    <label>Role</label>
                    <input type="text" id="modal-role" readonly class="form-input readonly-input">
                </div>

                <div class="modal-actions">
                    <button id="btn-cancel-modal" class="btn-deactivate">Cancel</button>
                    <button id="btn-confirm-deactivate" class="btn-red"><i class="fas fa-user-slash"></i> Deactivate</button>
                </div>
            </div>
        </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/user_management.js?v=<?php echo time(); ?>"></script>

</body>
</html>