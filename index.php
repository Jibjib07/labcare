<?php include 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LabCare</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Dashboard</h1>
            <p class="desktop-only-text">Real-time management of computer laboratory units and supplies, optimized for seamless admin and staff coordination across all devices.</p>
            <p class="mobile-only-text">Overview of daily tasks and laboratory status.</p>
        </div>

        <div class="mobile-dashboard-layout">
            <div class="mobile-stats-grid">
                <div class="m-stat-card dark-green">
                    <div class="m-card-content"><h2>902</h2><span>Total Computer Units</span></div>
                    <div class="m-card-icon"><i class="fas fa-desktop"></i></div>
                </div>
                <div class="m-stat-card light-green">
                    <div class="m-card-content"><h2>452</h2><span>Total Working Units</span></div>
                    <div class="m-card-icon"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="m-stat-card gray">
                    <div class="m-card-content"><h2>45</h2><span>Total Facility Assets</span></div>
                    <div class="m-card-icon"><i class="fas fa-print"></i></div>
                </div>
                <div class="m-stat-card yellow">
                    <div class="m-card-content"><h2>452</h2><span>Total For Repair Units</span></div>
                    <div class="m-card-icon"><i class="fas fa-wrench"></i></div>
                </div>
            </div>
            
            <div class="mobile-section">
                <h3 class="section-title">Quick Access</h3>
                <div class="quick-access-grid">
                    <a href="#" class="qa-btn"><div class="qa-icon"><i class="fas fa-qrcode"></i></div><span>Scan Room</span></a>
                    <a href="report_generation.php" class="qa-btn"><div class="qa-icon"><i class="fas fa-file-alt"></i></div><span>Make Report</span></a>
                    <a href="troubleshooting.php" class="qa-btn"><div class="qa-icon"><i class="fas fa-book"></i></div><span>Guide</span></a>
                </div>
            </div>

            <div class="mobile-section">
                <h3 class="section-title">Computer Laboratory Overview</h3>
                <div class="mobile-room-list">
                    <div class="m-room-item">
                        <div class="m-room-header"><h4>Com Lab 1</h4><span class="working-units">Working Units: <strong>25</strong></span></div>
                        <div class="m-room-details"><span class="room-badge">Room 104</span><a href="laboratory_management.php" class="room-link-btn"><i class="fas fa-chevron-right"></i></a></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="desktop-dashboard-layout">
            <div class="dashboard-top-grid">
                
                <div class="panel transparent-panel status-col">
                    <h3 class="section-title">Unit Status Summary</h3>
                    <div class="status-stack">
                        <div class="status-card green">
                            <div class="icon-circle-bg"><i class="fas fa-check-circle"></i></div>
                            <div class="card-info-right">
                                <h2>400</h2><span>Total Working Units</span>
                            </div>
                        </div>
                        <div class="status-card yellow">
                            <div class="icon-circle-bg"><i class="fas fa-wrench"></i></div>
                            <div class="card-info-right">
                                <h2>248</h2><span>Total For Repair Units</span>
                            </div>
                        </div>
                        <div class="status-card red">
                            <div class="icon-circle-bg"><i class="fas fa-trash-alt"></i></div>
                            <div class="card-info-right">
                                <h2>400</h2><span>Total Condemned Units</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel white-panel">
                    <h3 class="section-title">Resource Summary</h3>
                    <hr class="divider">
                    <div class="resource-grid">
                        <div class="res-item">
                            <i class="fas fa-desktop"></i>
                            <h2>658</h2><span>Total Computer Units</span>
                        </div>
                        <div class="res-item">
                            <i class="fas fa-print"></i>
                            <h2>10</h2><span>Total Facility Assets</span>
                        </div>
                        <div class="res-item">
                            <i class="fas fa-warehouse"></i>
                            <h2>08</h2><span>Total Computer Labs</span>
                        </div>
                        <div class="res-item">
                            <i class="fas fa-users"></i>
                            <h2>10</h2><span>Total Active Users</span>
                        </div>
                    </div>
                </div>

                <div class="panel white-panel supply-panel">
                    <h3 class="section-title">Supply Inventory Summary</h3>
                    <div class="supply-list-container">
                        <div class="supply-item out-stock">
                            <div class="supply-accent"></div>
                            <div class="supply-info">
                                <h4>Power Cord</h4>
                                <span>Out of Stock</span>
                            </div>
                        </div>
                        <div class="supply-item out-stock">
                            <div class="supply-accent"></div>
                            <div class="supply-info">
                                <h4>HDMI Cable</h4>
                                <span>Out of Stock</span>
                            </div>
                        </div>
                        <div class="supply-item in-stock">
                            <div class="supply-accent"></div>
                            <div class="supply-info">
                                <h4>Mouse</h4>
                                <span class="green-text">In Stock</span>
                            </div>
                        </div>
                        <div class="supply-item in-stock">
                            <div class="supply-accent"></div>
                            <div class="supply-info">
                                <h4>Keyboard</h4>
                                <span class="green-text">In Stock</span>
                            </div>
                        </div>
                        <div class="supply-item in-stock">
                            <div class="supply-accent"></div>
                            <div class="supply-info">
                                <h4>Ram Stick</h4>
                                <span class="green-text">In Stock</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-bottom-grid">
                <div class="panel white-panel table-panel">
                    <div class="panel-header-row">
                        <h3 class="section-title">Recent Maintenance Report</h3>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Serial Number</th>
                                <th>Latest Maintenance Date</th>
                                <th>Room Number</th>
                                <th>Device Number</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i=0; $i<6; $i++): ?>
                            <tr>
                                <td>1258786</td>
                                <td>11/20/2025</td>
                                <td>104</td>
                                <td>PC-0<?= $i+1 ?></td>
                                <td><span class="badge purple">Pending</span></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <div class="panel white-panel">
                    <h3 class="section-title">Computer Lab Overview</h3>
                    <div class="chart-controls">
                        <select class="room-select">
                            <option>Room 104</option>
                            <option>Room 105</option>
                        </select>
                    </div>
                    
                    <div class="chart-container">
                        <div class="donut-chart-large"></div>
                    </div>
                    
                    <div class="legend-row">
                        <span class="legend-pill green"><span class="dot"></span> Working 15</span>
                        <span class="legend-pill yellow"><span class="dot"></span> For Repair 10</span>
                        <span class="legend-pill red"><span class="dot"></span> Condemned 5</span>
                    </div>
                    
                    <div class="view-details-link">
                        <a href="laboratory_management.php">View Details <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>