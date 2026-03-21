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
            <div class="panel white-panel user-list-panel">
                <div class="panel-header-row">
                    <h3><span id="list-status-title">Active</span> User List</h3>
                    <button id="btn-open-add-modal" class="btn-green-add"><i class="fas fa-plus-circle"></i> Add</button>
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

                            $rawRole = $row['user_role'] ? $row['user_role'] : 'Staff';
                            $roleText = (strtolower($rawRole) === 'admin') ? 'Admin' : 'Staff';

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
                    } 
                    ?>
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
        </div>

        <div id="add-user-modal" class="modal-overlay">
            <div class="modal-content modal-large">
                <h2 style="font-size: 16px; margin-bottom: 20px;">Adding New User</h2>
                <form class="user-form" id="add-user-form">
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
                        <input type="password" id="add-password" class="form-input" placeholder="Enter password">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" id="add-confirm-password" class="form-input" placeholder="Re-enter password">
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
                <h2 style="font-size: 1.3rem; color: #2c3e50; margin-bottom: 10px;">Deactivate this User</h2>
                <p style="color: #666; font-size: 0.85rem; margin-bottom: 25px; line-height: 1.5;">This user will no longer be able to log in, but their historical activity will be preserved in the audit logs.</p>
                
                <div class="form-group">
                    <label style="font-size: 0.8rem;">Full Name</label>
                    <input type="text" id="deact-name" class="form-input readonly-input" readonly>
                </div>
                <div class="form-group">
                    <label style="font-size: 0.8rem;">Email Address</label>
                    <input type="text" id="deact-email" class="form-input readonly-input" readonly>
                </div>
                <div class="form-group">
                    <label style="font-size: 0.8rem;">Role</label>
                    <input type="text" id="deact-role" class="form-input readonly-input" readonly>
                </div>

                <div class="modal-actions" style="margin-top: 25px;">
                    <button id="btn-cancel-modal" class="btn-cancel-new" style="padding: 10px 20px;">Cancel</button>
                    <button id="btn-confirm-deactivate" class="btn-red" style="padding: 10px 20px;"><i class="fas fa-user-slash"></i> Deactivate</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/user_management.js?v=<?php echo time(); ?>"></script>
</body>
</html>