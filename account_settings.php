<?php include 'includes/db.php'; ?>
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
                    
                    <form>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" class="input-field" value="Juan Dela Cruz">
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" class="input-field" value="JuanDee@gmail.com">
                        </div>

                        <div class="save-btn-container">
                            <button class="btn-green-save"><i class="fas fa-save"></i> Save</button>
                        </div>
                    </form>
                </div>

                <div class="panel white-panel security-panel">
                    <h3>Security & Login</h3>
                    <div class="divider"></div>

                    <div class="security-content">
                        <p class="security-text">To update your password, we will send a secure reset link to your registered email address.</p>
                        
                        <button class="btn-purple-reset">
                            <i class="fas fa-lock"></i> Send Password Reset Link
                        </button>
                    </div>
                </div>

            </div>

            <div class="panel white-panel info-panel">
                <h3>System Information</h3>
                
                <div class="info-block">
                    <h4>CVSU Mission</h4>
                    <p>Cavite State University shall provide <strong>excellent, equitable, and relevant educational opportunities</strong> in the arts, sciences, and technology through <strong>quality instruction</strong> and <strong>responsive research</strong> and <strong>development activities</strong>. It shall <strong>produce professional, skilled, and morally upright individuals</strong> for global competitiveness.</p>
                </div>

                <div class="info-block">
                    <h4>CVSU Vision</h4>
                    <p>The <strong>premier university</strong> in historic Cavite recognized for <strong>excellence</strong> in the development of <strong>globally competitive and morally upright individuals</strong>.</p>
                </div>

                <div class="info-block">
                    <h4>CVSU Quality Policy</h4>
                    <p>We <strong>C</strong>ommit to the highest standards of education, <strong>V</strong>alue our stakeholders, <strong>S</strong>trive for continual improvement of our products and services, and <strong>U</strong>phold the University's tenets of Truth, Excellence, and Service to produce globally competitive and morally upright individuals.</p>
                </div>

                <div class="disclaimer">
                    <strong>Disclaimer:</strong> This system is for Computer Laboratory Use Only.
                </div>
            </div>

        </div>
    </div>
</body>
</html>