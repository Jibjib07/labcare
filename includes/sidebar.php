<?php
// Get current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="mobile-header">
    <button id="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="logo-container mobile">
        <div class="logo-icon">
            <img src="assets/logo.png" alt="LabCare Logo">
        </div>
        <h2 class="logo-text">abCare</h2>
    </div>
</div>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="sidebar" id="sidebar">
    
    <button id="mobile-menu-close" class="mobile-close-btn">
        <i class="fas fa-times"></i>
    </button>

    <div class="logo-container desktop">
        <div class="logo-icon">
            <img src="assets/logo.png" alt="LabCare Logo">
        </div>
        <h2 class="logo-text">abCare</h2>
    </div>

    <nav>
        <ul>
            <li>
                <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="laboratory_management.php" class="<?= ($current_page == 'laboratory_management.php' || $current_page == 'assets_management.php') ? 'active' : '' ?>">
                    <i class="fas fa-desktop"></i> <span>Laboratory Management</span>
                </a>
            </li>
            <li>
                <a href="maintenance_history.php" class="<?= $current_page == 'maintenance_history.php' ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i> <span>History Management</span>
                </a>
            </li>
            <li>
                <a href="report_generation.php" class="<?= $current_page == 'report_generation.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i> <span>Report Generation</span>
                </a>
            </li>
            <li>
                <a href="user_management.php" class="<?= $current_page == 'user_management.php' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i> <span>User Management</span>
                </a>
            </li>
            <li>
                <a href="troubleshooting.php" class="<?= $current_page == 'troubleshooting.php' ? 'active' : '' ?>">
                    <i class="fas fa-tools"></i> <span>Troubleshooting</span>
                </a>
            </li>
            <li>
                <a href="supply_inventory.php" class="<?= $current_page == 'supply_inventory.php' ? 'active' : '' ?>">
                    <i class="fas fa-boxes"></i> <span>Supply Inventory</span>
                </a>
            </li>
            <li>
                <a href="account_settings.php" class="<?= $current_page == 'account_settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i> <span>Account</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="logout-section">
        <a href="logout.php" class="btn-logout">Log Out</a>
    </div>
</div>