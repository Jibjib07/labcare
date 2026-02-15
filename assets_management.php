<?php include 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets Management - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/assets_management.css?v=<?php echo time(); ?>">
    
    <style>
        .view-section { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Animation for Tab Switching */
        .tab-content { animation: fadeIn 0.3s ease-in-out; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div class="breadcrumb">
                <span class="gray-link">Computer Laboratory Management</span> 
                <span class="gray-text"> > </span> 
                <span class="bold-text">Assets Management</span>
            </div>
            <p>Manage hardware specs, serial identifiers, and individual maintenance logs.</p>
        </div>

        <div id="view-computer" class="view-section">
            <div class="split-layout">
                
                <div class="panel white-panel left-panel">
                    
                    <div class="panel-top-nav">
                        <a href="laboratory_management.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                        <div class="toggle-container">
                            <button type="button" class="toggle-link active">Computer Unit</button>
                            <button type="button" class="toggle-link" onclick="switchView('facility')">Facility Assets</button>
                        </div>
                    </div>

                    <div class="section-header-row">
                        <h3>Room 104 - <strong>Computer Units</strong></h3>
                        <button class="btn-green-add"><i class="fas fa-plus-circle"></i> Add</button>
                    </div>

                    <div class="search-filter-row">
                        <input type="text" class="search-input" placeholder="Type a number of unit or search...">
                        <button class="filter-btn">Filter <i class="fas fa-filter"></i></button>
                    </div>

                    <div class="asset-list">
                        <div class="asset-item active">
                            <span class="item-name">PC-01</span>
                            <span class="badge green">Working</span>
                        </div>
                        <div class="asset-item">
                            <span class="item-name">PC-02</span>
                            <span class="badge orange">For Repair</span>
                        </div>
                        <div class="asset-item">
                            <span class="item-name">PC-03</span>
                            <span class="badge green">Working</span>
                        </div>
                        <div class="asset-item">
                            <span class="item-name">PC-04</span>
                            <span class="badge green">Working</span>
                        </div>
                        <div class="asset-item">
                            <span class="item-name">PC-05</span>
                            <span class="badge green">Working</span>
                        </div>
                        <div class="asset-item">
                            <span class="item-name">PC-06</span>
                            <span class="badge orange">For Repair</span>
                        </div>
                        <div class="asset-item">
                            <span class="item-name">PC-09</span>
                            <span class="badge purple">Pending</span>
                        </div>
                    </div>

                    <div class="pagination-row">
                        <span class="page-nav">< Previous</span>
                        <span class="page-num active">1</span>
                        <span class="page-num">2</span>
                        <span class="page-num">3</span>
                        <span class="page-nav">Next ></span>
                    </div>
                </div>

                <div class="panel white-panel right-panel">
                    <div class="section-header-row">
                        <h3>PC-01 Details</h3>
                        <div class="action-buttons">
                            <button class="btn-edit"><i class="fas fa-pen"></i> Edit</button>
                            <button class="btn-condemn"><i class="fas fa-trash-alt"></i> Condemn</button>
                        </div>
                    </div>

                    <div class="specs-tabs">
                        <button class="spec-tab active" onclick="switchTab('identity', this)">Identity & Specifications</button>
                        <button class="spec-tab" onclick="switchTab('external', this)">External I/O Ports</button>
                        <button class="spec-tab" onclick="switchTab('health', this)">Health & Maintenance Summary</button>
                        <button class="spec-tab" onclick="switchTab('peripherals', this)">Peripherals</button>
                    </div>

                    <div class="specs-content-box">
                        
                        <div id="tab-identity" class="tab-content">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Property ID</label>
                                    <div class="detail-box">1025478521</div>
                                </div>
                                <div class="detail-group">
                                    <label>Processor (CPU)</label>
                                    <div class="detail-box">Intel Core i5-12400</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Brand</label>
                                    <div class="detail-box">Asus</div>
                                </div>
                                <div class="detail-group">
                                    <label>Operating System</label>
                                    <div class="detail-box">Windows 11 Pro</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Purchase Date</label>
                                    <div class="detail-box">11/20/2025</div>
                                </div>
                                <div class="detail-group">
                                    <label>Graphics Card (GPU)</label>
                                    <div class="detail-box">Integrated Intel UHD Graphics 730</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Room Number</label>
                                    <div class="detail-box">104</div>
                                </div>
                                <div class="detail-group">
                                    <label>RAM (Installed Memory)</label>
                                    <div class="detail-box">16 GB</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Unit Number</label>
                                    <div class="detail-box">01</div>
                                </div>
                                <div class="detail-group">
                                    <label>Storage Type</label>
                                    <div class="detail-box">SSD (M.2 NVMe)</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group"></div>
                                <div class="detail-group">
                                    <label>Storage Capacity</label>
                                    <div class="detail-box">512 GB</div>
                                </div>
                            </div>
                        </div>

                        <div id="tab-external" class="tab-content" style="display: none;">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>USB Ports</label>
                                    <div class="status-row">
                                        <span>Status:</span> <div class="status-pill green">Working</div>
                                    </div>
                                    <div class="sub-detail-row">
                                        <span>Available Ports:</span> <div class="detail-box small-box">8</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Wi-Fi Card</label>
                                    <div class="status-row">
                                        <span>Status:</span> <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Microphone Jack</label>
                                    <div class="status-row">
                                        <span>Status:</span> <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>HDMI Port</label>
                                    <div class="status-row">
                                        <span>Status:</span> <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Headphone Jack</label>
                                    <div class="status-row">
                                        <span>Status:</span> <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Display Port</label>
                                    <div class="status-row">
                                        <span>Status:</span> <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>In-line Jack</label>
                                    <div class="status-row">
                                        <span>Status:</span> <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Ethernet Port</label>
                                    <div class="status-row">
                                        <span>Status:</span> <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="tab-health" class="tab-content" style="display: none;">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Computer Age</label>
                                    <div class="sub-detail-row">
                                        <span>Total:</span> <div class="detail-box small-box">7 Years</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Disk Health (SMART Status)</label>
                                    <div class="status-row">
                                        <span>Status:</span> <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Number of Repairs</label>
                                    <div class="sub-detail-row">
                                        <span>Total:</span> <div class="detail-box small-box">8</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Power Supply Health</label>
                                    <div class="status-row">
                                        <span>Status:</span> <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="activity-section">
                                <div class="activity-header">
                                    <h4>Recent Activity</h4>
                                    <a href="#" class="view-history-link">View Full Maintenance History</a>
                                </div>
                                <table class="activity-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Reported by</th>
                                            <th>Affected</th>
                                            <th>Remarks</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>11/19/2025</td>
                                            <td>Juan Dela Cruz</td>
                                            <td>
                                                <ul class="table-list">
                                                    <li>Monitor</li>
                                                    <li>System Unit</li>
                                                </ul>
                                            </td>
                                            <td>System Grounded, Monitor Dead Pixels</td>
                                            <td><span class="badge green">Resolved</span></td>
                                        </tr>
                                        <tr>
                                            <td>11/18/2025</td>
                                            <td>Juan Dela Cruz</td>
                                            <td>Monitor</td>
                                            <td>Monitor Dead Pixels</td>
                                            <td><span class="badge green">Resolved</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="tab-peripherals" class="tab-content" style="display: none;">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Monitor</label>
                                    <div class="peripheral-info">
                                        <div class="p-row"><span>Property ID:</span> 1025482128</div>
                                        <div class="p-row"><span>Brand:</span> Acer</div>
                                        <div class="status-row"><span>Status:</span> <div class="status-pill green">Working</div></div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Mouse</label>
                                    <div class="peripheral-info">
                                        <div class="p-row"><span>Brand:</span> Acer</div>
                                        <div class="status-row"><span>Status:</span> <div class="status-pill green">Working</div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Keyboard</label>
                                    <div class="peripheral-info">
                                        <div class="p-row"><span>Brand:</span> Acer</div>
                                        <div class="status-row"><span>Status:</span> <div class="status-pill green">Working</div></div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>AVR (Automatic Voltage Regulator)</label>
                                    <div class="peripheral-info">
                                        <div class="p-row"><span>Brand:</span> Acer</div>
                                        <div class="status-row"><span>Status:</span> <div class="status-pill green">Working</div></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div id="view-facility" class="view-section" style="display: none;">
            <div class="split-layout">
                <div class="panel white-panel left-panel">
                    <div class="panel-top-nav">
                        <a href="laboratory_management.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                        <div class="toggle-container">
                            <button type="button" class="toggle-link" onclick="switchView('computer')">Computer Unit</button>
                            <button type="button" class="toggle-link active">Facility Assets</button>
                        </div>
                    </div>
                    <div class="section-header-row">
                        <h3>Room 104 - <strong>Facility Assets</strong></h3>
                        <button class="btn-green-add"><i class="fas fa-plus-circle"></i> Add</button>
                    </div>
                    <div class="search-filter-row">
                        <input type="text" class="search-input" placeholder="Type a device or search...">
                        <button class="filter-btn">Filter <i class="fas fa-filter"></i></button>
                    </div>
                    <div class="asset-list">
                        <div class="asset-item active">
                            <span class="item-name">FA-01</span>
                            <span class="badge green">Working</span>
                        </div>
                        <div class="asset-item">
                            <span class="item-name">FA-02</span>
                            <span class="badge green">Working</span>
                        </div>
                    </div>
                </div>

                <div class="panel white-panel right-panel">
                    <div class="section-header-row">
                        <h3>Television</h3>
                        <div class="action-buttons">
                            <button class="btn-edit"><i class="fas fa-pen"></i> Edit</button>
                            <button class="btn-condemn"><i class="fas fa-trash-alt"></i> Condemn</button>
                        </div>
                    </div>
                    <div class="detail-content">
                        <div class="detail-grid-row">
                            <div class="detail-group">
                                <label>Property ID:</label>
                                <div class="detail-box">10284521</div>
                            </div>
                        </div>
                        <div class="detail-grid-row">
                            <div class="detail-group">
                                <label>Brand:</label>
                                <div class="detail-box">Acer</div>
                            </div>
                        </div>
                        <div class="detail-grid-row">
                            <div class="detail-group">
                                <label>Status:</label>
                                <div class="detail-box status-box-green">Working</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/assets_management.js?v=<?php echo time(); ?>"></script>
</body>
</html>