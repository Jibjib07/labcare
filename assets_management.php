<?php include "includes/db.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets Management - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/assets_management.css?v=<?php echo time(); ?>">

</head>

<body>
    <?php include "includes/sidebar.php"; ?>

    <div class="main-content">

        <div class="page-header">
            <div class="breadcrumb">
                <span class="gray-link">Computer Laboratory Management</span>
                <span class="gray-text"> > </span>
                <span class="bold-text">Assets Management</span>
            </div>
            <p>Manage hardware specs, serial identifiers, and individual maintenance logs.</p>
        </div>

        <div id="view-computer">
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
                        <button class="btn-green-add" onclick="openModal('addComputerModal')"><i class="fas fa-plus-circle"></i> Add</button>
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
                        <div class="asset-item">
                            <span class="item-name">PC-10</span>
                            <span class="badge purple">Pending</span>
                        </div>
                    </div>

                    <div class="pagination-row">
                        <span class="page-nav">
                            < Previous</span>
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
                                <div class="detail-group"><label>Property ID</label>
                                    <div class="detail-box">1025478521</div>
                                </div>
                                <div class="detail-group"><label>Processor (CPU)</label>
                                    <div class="detail-box">Intel Core i5-12400</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group"><label>Brand</label>
                                    <div class="detail-box">Asus</div>
                                </div>
                                <div class="detail-group"><label>Operating System</label>
                                    <div class="detail-box">Windows 11 Pro</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group"><label>Purchase Date</label>
                                    <div class="detail-box">11/20/2025</div>
                                </div>
                                <div class="detail-group"><label>Graphics Card (GPU)</label>
                                    <div class="detail-box">Integrated Intel UHD Graphics 730</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group"><label>Room Number</label>
                                    <div class="detail-box">104</div>
                                </div>
                                <div class="detail-group"><label>RAM (Installed Memory)</label>
                                    <div class="detail-box">16 GB</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group"><label>Unit Number</label>
                                    <div class="detail-box">01</div>
                                </div>
                                <div class="detail-group"><label>Storage Type</label>
                                    <div class="detail-box">SSD (M.2 NVMe)</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group"></div>
                                <div class="detail-group"><label>Storage Capacity</label>
                                    <div class="detail-box">512 GB</div>
                                </div>
                            </div>
                        </div>

                        <div id="tab-external" class="tab-content" style="display: none;">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>USB Ports</label>
                                    <div class="status-row"><span>Status:</span>
                                        <div class="status-pill green">Working</div>
                                    </div>
                                    <div class="sub-detail-row"><span>Available Ports:</span>
                                        <div class="detail-box small-box">8</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Wi-Fi Card</label>
                                    <div class="status-row"><span>Status:</span>
                                        <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Microphone Jack</label>
                                    <div class="status-row"><span>Status:</span>
                                        <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>HDMI Port</label>
                                    <div class="status-row"><span>Status:</span>
                                        <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Headphone Jack</label>
                                    <div class="status-row"><span>Status:</span>
                                        <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Display Port</label>
                                    <div class="status-row"><span>Status:</span>
                                        <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>In-line Jack</label>
                                    <div class="status-row"><span>Status:</span>
                                        <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Ethernet Port</label>
                                    <div class="status-row"><span>Status:</span>
                                        <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="tab-health" class="tab-content" style="display: none;">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Computer Age</label>
                                    <div class="sub-detail-row"><span>Total:</span>
                                        <div class="detail-box small-box">7 Years</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Disk Health (SMART Status)</label>
                                    <div class="status-row"><span>Status:</span>
                                        <div class="status-pill green">Working</div>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Number of Repairs</label>
                                    <div class="sub-detail-row"><span>Total:</span>
                                        <div class="detail-box small-box">8</div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Power Supply Health</label>
                                    <div class="status-row"><span>Status:</span>
                                        <div class="status-pill green">Working</div>
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
                                        <div class="status-row"><span>Status:</span>
                                            <div class="status-pill green">Working</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Mouse</label>
                                    <div class="peripheral-info">
                                        <div class="p-row"><span>Brand:</span> Acer</div>
                                        <div class="status-row"><span>Status:</span>
                                            <div class="status-pill green">Working</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Keyboard</label>
                                    <div class="peripheral-info">
                                        <div class="p-row"><span>Brand:</span> Acer</div>
                                        <div class="status-row"><span>Status:</span>
                                            <div class="status-pill green">Working</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>AVR (Automatic Voltage Regulator)</label>
                                    <div class="peripheral-info">
                                        <div class="p-row"><span>Brand:</span> Acer</div>
                                        <div class="status-row"><span>Status:</span>
                                            <div class="status-pill green">Working</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div id="view-facility" style="display: none;">
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
                            <div class="detail-group"><label>Property ID:</label>
                                <div class="detail-box">10284521</div>
                            </div>
                        </div>
                        <div class="detail-grid-row">
                            <div class="detail-group"><label>Brand:</label>
                                <div class="detail-box">Acer</div>
                            </div>
                        </div>
                        <div class="detail-grid-row">
                            <div class="detail-group"><label>Status:</label>
                                <div class="detail-box status-box-green">Working</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div id="addComputerModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Add New Computer Unit</h3>
            </div>

            <div class="modal-body">
                <div class="unit-type-toggle">
                    <label class="radio-option active">
                        <span class="radio-circle checked"></span> Single Unit
                    </label>
                    <label class="radio-option">
                        <span class="radio-circle"></span> Multiple Unit
                    </label>
                </div>

                <div class="specs-tabs modal-tabs-nav">
                    <button class="spec-tab active" onclick="switchModalTab('m-identity', this)">Identity & Specifications</button>
                    <button class="spec-tab" onclick="switchModalTab('m-external', this)">External I/O Ports</button>
                    <button class="spec-tab" onclick="switchModalTab('m-health', this)">Health & Maintenance Summary</button>
                    <button class="spec-tab" onclick="switchModalTab('m-peripherals', this)">Peripherals</button>
                </div>

                <div id="m-identity" class="modal-tab-content">
                    <div class="modal-form-grid">
                        <div class="form-group"><label>Property ID</label><input type="text" class="modal-input" placeholder="Enter unique property number"></div>
                        <div class="form-group"><label>Processor (CPU)</label><input type="text" class="modal-input" placeholder="e.g., Intel Core i5-12400"></div>
                        <div class="form-group"><label>Brand</label><input type="text" class="modal-input" placeholder="e.g., Dell, HP, Lenovo"></div>
                        <div class="form-group"><label>Operating System</label><input type="text" class="modal-input" placeholder="e.g., Windows 11 Pro"></div>
                        <div class="form-group"><label>Purchase Date</label><input type="text" class="modal-input" placeholder="MM/DD/YYYY"></div>
                        <div class="form-group"><label>Graphics Card (GPU)</label><input type="text" class="modal-input" placeholder="e.g., Integrated Intel UHD"></div>
                        <div class="form-group"><label>Room Number</label><input type="text" class="modal-input" value="104"></div>
                        <div class="form-group"><label>RAM (Installed Memory)</label><input type="text" class="modal-input" placeholder="e.g., 16 GB"></div>
                        <div class="form-group"><label>Unit Number</label><input type="text" class="modal-input" value="01"></div>
                        <div class="form-group"><label>Storage Type</label><input type="text" class="modal-input" placeholder="e.g., SSD (M.2 NVMe)"></div>
                        <div class="form-group"></div>
                        <div class="form-group"><label>Storage Capacity</label><input type="text" class="modal-input" placeholder="e.g., 512 GB"></div>
                    </div>
                </div>

                <div id="m-external" class="modal-tab-content" style="display: none;">
                    <div class="modal-form-grid">
                        <div class="form-group">
                            <label>USB Ports</label>

                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>

                            <div class="sub-input-row">
                                <label>Available Ports:</label>
                                <input type="number" class="modal-input small-input" value="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Wi-fi Card</label>

                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>

                        </div>
                        <div class="form-group">
                            <label>Microhpone Jack</label>

                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>

                        </div>
                        <div class="form-group">
                            <label>HDMI Port</label>

                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>

                        </div>
                        <div class="form-group">
                            <label>Headphone Jack</label>

                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Display Port</label>

                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>In-Line Jack</label>

                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Ethernet Port</label>

                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="m-health" class="modal-tab-content" style="display: none;">
                    <div class="modal-form-grid">
                        <div class="form-group">
                            <label>Computer Age</label>
                            <div class="sub-input-row">
                                <label>Total:</label>
                                <input type="text" class="modal-input" placeholder="e.g., 7 Years">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Disk Health (SMART Status)</label>

                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Number of Repairs</label>
                            <div class="sub-input-row">
                                <label>Total:</label>
                                <input type="text" class="modal-input" placeholder="Automatic" disabled style="background:#e9e9e9;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Power Supply</label>

                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="m-peripherals" class="modal-tab-content" style="display: none;">
                    <div class="modal-form-grid">

                        <div class="form-group peripheral-group">
                            <label class="group-title">Monitor</label>
                            <div class="sub-field">
                                <label>Property ID:</label>
                                <input type="text" class="modal-input" placeholder="1025482128">
                            </div>
                            <div class="sub-field">
                                <label>Brand:</label>
                                <input type="text" class="modal-input" placeholder="Acer">
                            </div>
                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>

                        <div class="form-group peripheral-group">
                            <label class="group-title">Mouse</label>
                            <div class="sub-field">
                                <label>Brand:</label>
                                <input type="text" class="modal-input" placeholder="Acer">
                            </div>
                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>

                        <div class="form-group peripheral-group">
                            <label class="group-title">Keyboard</label>
                            <div class="sub-field">
                                <label>Brand:</label>
                                <input type="text" class="modal-input" placeholder="Acer">
                            </div>
                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>

                        <div class="form-group peripheral-group">
                            <label class="group-title">AVR (Automatic Voltage Regulator)</label>
                            <div class="sub-field">
                                <label>Brand:</label>
                                <input type="text" class="modal-input" placeholder="Acer">
                            </div>
                            <div class="status-toggle-group">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('addComputerModal')">Cancel</button>
                <button class="btn-create"><i class="fas fa-plus-circle"></i> Create</button>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/assets_management.js?v=<?php echo time(); ?>"></script>
</body>

</html>