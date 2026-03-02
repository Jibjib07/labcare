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
                    <button type="button" class="btn-green-add" id="openModalBtn"><i class="fas fa-plus-circle"></i> Add</button>
                </div>

                <div class="search-filter-row">
                <input type="text" class="search-input" id="tableSearch" placeholder="Search a supply">
                <select class="filter-btn" id="categoryFilter">
                    <option value="all">All Categories</option>
                    <option value="Connectivity & Cables">Connectivity & Cables</option>
                    <option value="Peripherals">Peripherals</option>
                    <option value="Internal Components">Internal Components</option>
                    <option value="Networking Tools">Networking Tools</option>
                    <option value="Networking Consumables">Networking Consumables</option> <option value="Maintenance & Cleaning">Maintenance & Cleaning</option> <option value="Power Management">Power Management</option>
                    <option value="Facility Supplies">Facility Supplies</option> 
                </select>
            </div>

                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="stock_status" value="all" checked> All
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="stock_status" value="in_stock"> In Stock
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
                            <?php
                            $query = "SELECT supply_id, supply_name, supply_category, supply_status FROM supply ORDER BY supply_name ASC";
                            $result = mysqli_query($conn, $query);

                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $id = htmlspecialchars($row['supply_id']);
                                    $name = htmlspecialchars($row['supply_name']);
                                    $category = htmlspecialchars($row['supply_category']);
                                    $status = htmlspecialchars($row['supply_status']);

                                    $badgeClass = ($status === 'In Stock') ? 'badge green' : 'badge red';

                                    echo "<tr data-id='{$id}' data-category='{$category}'>";
                                    echo "    <td>{$name}</td>";
                                    echo "    <td>{$category}</td>";
                                    echo "    <td><span class='{$badgeClass}'>{$status}</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align: center; padding: 20px;'>No supplies found in inventory.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel white-panel right-detail-panel">
                <div id="view-mode">
                    <div class="panel-header-row">
                        <h3>Supply Details</h3>
                        <div class="header-actions">
                            <button type="button" class="btn-edit-outline" id="editTrigger">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn-mark-stock">
                                <i class="fas fa-sign-in-alt"></i> Mark as In Stock
                            </button>
                        </div>
                    </div>

                    <div class="detail-grid">
                        <div class="detail-group">
                            <label>Supply Name:</label>
                            <div class="detail-input" id="view_supply_name">Select an item</div>
                        </div>
                        <div class="detail-group">
                            <label>Current Status:</label>
                            <div class="detail-input" id="view_supply_status">-</div>
                        </div>
                        <div class="detail-group">
                            <label>Category:</label>
                            <div class="detail-input" id="view_supply_category">-</div>
                        </div>
                    </div>

                    <h4 class="activity-title">Recent Stock Activity:</h4>
                    <div class="activity-table-container">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th>User</th>
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

                <div id="edit-mode" style="display: none;">
                    <form action="handlers/update_supply.php" method="POST">
                        <input type="hidden" name="supply_id" id="edit_supply_id">
                        <div class="panel-header-row">
                            <h3>Supply Details</h3>
                            <div class="header-actions">
                                <button type="button" class="filter-btn" id="cancelEdit">Cancel</button>
                                <button type="submit" name="submit_update" class="btn-green-add" id="saveUpdate">
                                    <i class="fas fa-check-circle"></i> Save Update
                                </button>
                            </div>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-group">
                                <label>Supply Name:</label>
                                <input type="text" name="supply_name" id="edit_supply_name" class="modal-input" value="" placeholder="Select an item first..." readonly>
                            </div>
                            <div class="detail-group">
                                <label>Current Status:</label>
                                <div class="status-toggle-container">
                                    <label class="custom-radio">
                                        <input type="radio" name="stock_status" id="edit_out_stock" value="Out of Stock">
                                        <span class="checkmark"></span> Out of Stock
                                    </label>
                                    <label class="custom-radio">
                                        <input type="radio" name="stock_status" id="edit_in_stock" value="In Stock">
                                        <span class="checkmark"></span> In Stock
                                    </label>
                                </div>
                            </div>
                            <div class="detail-group">
                                <label>Category:</label>
                                <select name="supply_category" id="edit_supply_category" class="modal-input">
                                    <option value="" disabled selected>Select a category...</option>
                                    <option value="Connectivity & Cables">Connectivity & Cables</option>
                                    <option value="Peripherals">Peripherals</option>
                                    <option value="Internal Components">Internal Components</option>
                                    <option value="Networking Tools">Networking Tools</option>
                                    <option value="Networking Consumables">Networking Consumables</option>
                                    <option value="Maintenance & Cleaning">Maintenance & Cleaning</option>
                                    <option value="Power Management">Power Management</option>
                                    <option value="Facility Supplies">Facility Supplies</option> 
                                </select>
                            </div>
                        </div>

                        <div class="remarks-container" style="margin-top: 20px;">
                            <label class="modal-label">Update Remarks (Required):</label>
                            <textarea name="update_remarks" class="modal-input remarks-textarea" placeholder="Provide a reason for this stock update..." required></textarea>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <div id="addSupplyModal" class="modal-overlay">
        <div class="modal-content wide-modal">
            <div class="modal-header-simple">
                <h3>Add New Supply</h3>
            </div>
            <form action="handlers/add_supply.php" method="POST">
                <div class="modal-body-grid">
                    <div class="form-section">
                        <label class="modal-label">Supply Name:</label>
                        <input type="text" name="supply_name" class="modal-input" placeholder="Ex. HDMI Cable" required>
                    </div>

                    <div class="form-section">
                        <label class="modal-label">Category:</label>
                        <div class="category-grid">
                            <label><input type="checkbox" name="category[]" value="Connectivity & Cables"> Connectivity & Cables</label>
                            <label><input type="checkbox" name="category[]" value="Internal"> Internal Components</label>
                            <label><input type="checkbox" name="category[]" value="Peripherals"> Peripherals</label>
                            <label><input type="checkbox" name="category[]" value="Networking"> Networking Tools</label>
                            <label><input type="checkbox" name="category[]" value="Consumables"> Networking Consumables</label>
                            <label><input type="checkbox" name="category[]" value="Maintenance"> Maintenance & Cleaning</label>
                            <label><input type="checkbox" name="category[]" value="Power"> Power Management</label>
                            <label><input type="checkbox" name="category[]" value="Facility"> Facility Supplies</label>
                        </div>
                    </div>

                    <div class="form-section full-width">
                        <label class="modal-label">Initial Status:</label>
                        <select name="status" class="modal-input">
                            <option value="In Stock">In Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-styled">
                    <button type="button" class="btn-modal-cancel close-modal">Cancel</button>
                    <button type="submit" name="submit_supply" class="btn-modal-create"><i class="fas fa-plus-circle"></i> Create</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/supply_inventory.js?v=<?php echo time(); ?>"></script>

</body>
</html>