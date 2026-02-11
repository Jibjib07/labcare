<?php include 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supply Inventory - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/supply_inventory.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Supply Inventory</h1>
            <p>Monitor laboratory deployment, resource counts, and room archival states.</p>
        </div>

        <div class="supply-layout">
            
            <div class="panel white-panel left-list-panel">
                <div class="panel-header-row">
                    <h3>Existing Supply List</h3>
                    <button class="btn-green-add"><i class="fas fa-plus-circle"></i> Add</button>
                </div>

                <div class="search-filter-row">
                    <input type="text" class="search-input" placeholder="Search a supply">
                    <button class="filter-btn">Filter <i class="fas fa-filter"></i></button>
                </div>

                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="stock_status" value="in_stock" checked> In Stock
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="stock_status" value="out_of_stock"> Out of Stock
                    </label>
                </div>

                <div class="table-container">
                    <table class="supply-table">
                        <thead>
                            <tr>
                                <th>Supply Name</th>
                                <th>Category</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="active-row">
                                <td>HDMI Cables</td>
                                <td>Connectivity & Cables</td>
                                <td><span class="badge red">Out of Stock</span></td>
                            </tr>
                            <tr>
                                <td>Keyboards</td>
                                <td>Peripherals</td>
                                <td><span class="badge green">In Stock</span></td>
                            </tr>
                            <tr>
                                <td>RAM Sticks (8GB/16GB)</td>
                                <td>Internal Components</td>
                                <td><span class="badge green">In Stock</span></td>
                            </tr>
                            <tr>
                                <td>Crimping Tool</td>
                                <td>Networking Tools</td>
                                <td><span class="badge green">In Stock</span></td>
                            </tr>
                            <tr>
                                <td>RJ45 Connectors</td>
                                <td>Networking Consumables</td>
                                <td><span class="badge green">In Stock</span></td>
                            </tr>
                            <tr>
                                <td>Thermal Paste</td>
                                <td>Maintenance & Cleaning</td>
                                <td><span class="badge green">In Stock</span></td>
                            </tr>
                            <tr>
                                <td>AVR Fuses</td>
                                <td>Power Management</td>
                                <td><span class="badge green">In Stock</span></td>
                            </tr>
                            <tr>
                                <td>Printer Toner/Ink</td>
                                <td>Facility Supplies</td>
                                <td><span class="badge green">In Stock</span></td>
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

            <div class="panel white-panel right-detail-panel">
                <div class="panel-header-row">
                    <h3>Supply Details</h3>
                    <button class="btn-mark-stock"><i class="fas fa-arrow-right"></i> Mark as In Stock</button>
                </div>

                <div class="detail-grid">
                    <div class="detail-group">
                        <label>Supply Name:</label>
                        <div class="detail-input">HDMI Cables</div>
                    </div>
                    <div class="detail-group">
                        <label>Current Status:</label>
                        <div class="detail-input">Out of Stock</div>
                    </div>
                </div>

                <h4 class="activity-title">Recent Stock Activity:</h4>
                <div class="activity-table-container">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activity</th>
                                <th>Admin/Staff</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>11/20/2025</td>
                                <td>Marked <strong>Out of Stock</strong></td>
                                <td>Juan Dela Cruz</td>
                                <td>Used for Room 104 projector repair.</td>
                            </tr>
                            <tr>
                                <td>11/20/2025</td>
                                <td>Marked <strong>In Stock</strong></td>
                                <td>Juan Dela Cruz</td>
                                <td>Received new shipment of 20 cables.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
</body>
</html>