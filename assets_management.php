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
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div class="breadcrumb">
                <a href="laboratory_management.php" class="gray-link">Computer Laboratory Management</a> 
                <span class="gray-text"> > </span> 
                <span class="bold-text">Assets Management</span>
            </div>
            <p>Manage hardware specs, serial identifiers, and individual maintenance logs.</p>
        </div>

        <div class="assets-layout">
            
            <div class="panel white-panel left-list-panel">
                
                <div class="top-controls">
                    <a href="laboratory_management.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                    
                    <div class="toggle-switch">
                        <button class="toggle-btn active">Computer Unit</button>
                        <button class="toggle-btn">Facility Assets</button>
                    </div>
                </div>

                <div class="list-header-row">
                    <h3>Room 104 - <strong>Computer Units</strong></h3>
                    <button class="btn-green-add"><i class="fas fa-plus-circle"></i> Add</button>
                </div>

                <div class="filter-row">
                    <input type="text" class="search-input" placeholder="Type a number of unit or search...">
                    <button class="filter-btn">Filter <i class="fas fa-filter"></i></button>
                </div>

                <div class="assets-list">
                    <div class="asset-item active">
                        <span class="asset-name">PC-01</span>
                        <span class="status-badge working">Working</span>
                    </div>
                    
                    <div class="asset-item">
                        <span class="asset-name">PC-02</span>
                        <span class="status-badge repair">For Repair</span>
                    </div>
                    <div class="asset-item">
                        <span class="asset-name">PC-03</span>
                        <span class="status-badge working">Working</span>
                    </div>
                    <div class="asset-item">
                        <span class="asset-name">PC-04</span>
                        <span class="status-badge working">Working</span>
                    </div>
                    <div class="asset-item">
                        <span class="asset-name">PC-05</span>
                        <span class="status-badge working">Working</span>
                    </div>
                    <div class="asset-item">
                        <span class="asset-name">PC-06</span>
                        <span class="status-badge repair">For Repair</span>
                    </div>
                    <div class="asset-item">
                        <span class="asset-name">PC-09</span>
                        <span class="status-badge pending">Pending</span>
                    </div>
                    <div class="asset-item">
                        <span class="asset-name">PC-10</span>
                        <span class="status-badge pending">Pending</span>
                    </div>
                </div>

                <div class="pagination">
                    <span>< Previous</span> <span class="active-page">1</span> <span>2</span> <span>3</span> <span>Next ></span>
                </div>
            </div>

            <div class="panel white-panel right-details-panel">
                <div class="details-header">
                    <h3>PC-01 Details</h3>
                    <div class="details-actions">
                        <button class="btn-edit"><i class="fas fa-pen"></i> Edit</button>
                        <button class="btn-condemn"><i class="fas fa-trash-alt"></i> Condemn</button>
                    </div>
                </div>

                <div class="details-tabs">
                    <button class="tab-btn active">Identity & Specifications</button>
                    <button class="tab-btn">External I/O Ports</button>
                    <button class="tab-btn">Health & Maintenance Summary</button>
                    <button class="tab-btn">Peripherals</button>
                </div>

                <div class="specs-grid-container">
                    <div class="spec-group">
                        <label>Serial Number</label>
                        <div class="spec-value">1025478521</div>
                    </div>
                    <div class="spec-group">
                        <label>Processor (CPU)</label>
                        <div class="spec-value">Intel Core i5-12400</div>
                    </div>

                    <div class="spec-group">
                        <label>Brand</label>
                        <div class="spec-value">Asus</div>
                    </div>
                    <div class="spec-group">
                        <label>Operating System</label>
                        <div class="spec-value">Microsoft Windows 11 Pro</div>
                    </div>

                    <div class="spec-group">
                        <label>Purchase Date</label>
                        <div class="spec-value">11/20/2025</div>
                    </div>
                    <div class="spec-group">
                        <label>Graphics Card (GPU)</label>
                        <div class="spec-value">Integrated Intel UHD Graphics 730</div>
                    </div>

                    <div class="spec-group">
                        <label>Room Number</label>
                        <div class="spec-value">104</div>
                    </div>
                    <div class="spec-group">
                        <label>RAM (Installed Memory)</label>
                        <div class="spec-value">16 GB</div>
                    </div>

                    <div class="spec-group">
                        <label>Unit Number</label>
                        <div class="spec-value">01</div>
                    </div>
                    <div class="spec-group">
                        <label>Storage Type</label>
                        <div class="spec-value">SSD (M.2 NVMe)</div>
                    </div>

                    <div class="spec-group"></div> <div class="spec-group">
                        <label>Storage Capacity</label>
                        <div class="spec-value">512 GB</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
</body>
</html>