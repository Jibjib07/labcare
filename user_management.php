<?php include 'includes/db.php'; ?>
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
                    <button class="btn-green-add"><i class="fas fa-plus-circle"></i> Add</button>
                </div>

                <form class="user-form">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-input" placeholder="Ex. Juan Dela Cruz">
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-input" placeholder="Ex. JuanDee@gmail.com">
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="role" value="Staff" checked> Staff
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="role" value="Admin"> Admin
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
                        <select class="form-select">
                            <option>Active</option>
                            <option>Deactivated</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="right-column-wrapper">
                
                <div class="panel white-panel user-list-panel">
                    <h3>Existing User List</h3>
                    
                    <div class="search-filter-row">
                        <input type="text" class="search-input" placeholder="Type a number of unit or search...">
                        <button class="filter-btn">Filter <i class="fas fa-filter"></i></button>
                    </div>

                    <div class="table-container">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="active-row">
                                    <td>Dexter Morgan</td>
                                    <td>Staff</td>
                                    <td><span class="badge active">Active</span></td>
                                </tr>
                                <tr>
                                    <td>John D. Joe</td>
                                    <td>Staff</td>
                                    <td><span class="badge deactivated">Deactivated</span></td>
                                </tr>
                                <tr>
                                    <td>Juan D. Bone</td>
                                    <td>Staff</td>
                                    <td><span class="badge active">Active</span></td>
                                </tr>
                                <tr>
                                    <td>Nia G. Rah</td>
                                    <td>Staff</td>
                                    <td><span class="badge active">Active</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <span class="page-nav">< Previous</span>
                        <span class="page-num active">1</span>
                        <span class="page-num">2</span>
                        <span class="page-num">3</span>
                        <span class="page-nav">Next ></span>
                    </div>
                </div>

                <div class="panel white-panel user-info-panel">
                    <div class="panel-header-row">
                        <h3>User Information</h3>
                        <div class="action-buttons">
                            <button class="btn-edit"><i class="fas fa-pen"></i> Edit</button>
                            <button class="btn-deactivate"><i class="fas fa-user-slash"></i> Deactivate</button>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Full Name</label>
                            <div class="info-value">Dexter Morgan</div>
                        </div>
                        <div class="info-item">
                            <label>User Status</label>
                            <div class="info-value status-bg">Active</div>
                        </div>
                        <div class="info-item full-width">
                            <label>Email Address</label>
                            <div class="info-value">DexterBHB@gmail.com</div>
                        </div>
                        <div class="info-item full-width">
                            <label>Role</label>
                            <div class="info-value role-text">Staff</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
</body>
</html>