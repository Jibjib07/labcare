<?php 
include '../includes/db.php'; 

// --- 1. AJAX FETCH HANDLER (MUST BE AT THE VERY TOP) ---
if (isset($_GET['fetch_id'])) {
    header('Content-Type: application/json');
    $id = mysqli_real_escape_string($conn, $_GET['fetch_id']);
    
    $query = "SELECT * FROM supply WHERE supply_id = '$id'";
    $res = mysqli_query($conn, $query);
    $supply = mysqli_fetch_assoc($res);
    
    $log_query = "SELECT suphisto_date, suphisto_act, suphisto_actor, suphisto_remarks 
                  FROM supply_history 
                  WHERE supply_id = '$id' 
                  ORDER BY suphisto_date DESC, suphisto_id DESC";
    $log_res = mysqli_query($conn, $log_query);
    
    $history = [];
    if ($log_res) {
        while ($log = mysqli_fetch_assoc($log_res)) {
            $history[] = [
                'date' => date('m/d/Y', strtotime($log['suphisto_date'])),
                'activity' => $log['suphisto_act'],
                'user' => $log['suphisto_actor'],
                'remarks' => $log['suphisto_remarks']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'supply' => $supply, 'history' => $history]);
    exit(); 
}

// --- 2. DATABASE PROCESSING LOGIC ---

// A. Add New Supply
if (isset($_POST['submit_supply'])) {
    $supply_name = mysqli_real_escape_string($conn, trim($_POST['supply_name']));
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (empty($supply_name)) {
        header("Location: supply_inventory.php?error=empty_name");
        exit();
    }

    $insert_query = "INSERT INTO supply (supply_name, supply_status, supply_avail) VALUES ('$supply_name', '$status', 'Current')";
    if (mysqli_query($conn, $insert_query)) {
        // Get last ID to auto-select the newly created item
        $new_id = mysqli_insert_id($conn);
        header("Location: supply_inventory.php?success=added&id=$new_id");
        exit();
    }
}

// B. Handle Conditional Update
if (isset($_POST['submit_update'])) {
    $id = mysqli_real_escape_string($conn, $_POST['supply_id']);
    $new_name = mysqli_real_escape_string($conn, trim($_POST['supply_name']));
    $new_status = mysqli_real_escape_string($conn, $_POST['stock_status']);
    $old_status = mysqli_real_escape_string($conn, $_POST['original_status']);
    $remarks = mysqli_real_escape_string($conn, trim($_POST['update_remarks']));
    
    $actor = "nats"; 
    $date = date('Y-m-d');

    $update_query = "UPDATE supply SET supply_name = '$new_name', supply_status = '$new_status' WHERE supply_id = '$id'";
    
    if (mysqli_query($conn, $update_query)) {
        if ($new_status !== $old_status) {
            $activity = ($new_status === "In Stock") ? "Marked In Stock" : "Marked Out of Stock";
            $hist_query = "INSERT INTO supply_history (suphisto_date, suphisto_act, suphisto_actor, suphisto_remarks, supply_id) 
                           VALUES ('$date', '$activity', '$actor', '$remarks', '$id')";
            mysqli_query($conn, $hist_query);
        }
        // MODIFIED: Pass ID for auto-selection after reload
        header("Location: supply_inventory.php?success=updated&id=$id");
        exit();
    } else {
        header("Location: supply_inventory.php?error=sql_error");
        exit();
    }
}

// C. Handle Archival Logic (Silent Mode)
if (isset($_POST['submit_archive'])) {
    $id = mysqli_real_escape_string($conn, $_POST['supply_id']);
    $archive_query = "UPDATE supply SET supply_avail = 'Archived', supply_status = 'Out of Stock' WHERE supply_id = '$id'";
    
    if (mysqli_query($conn, $archive_query)) {
        // FIXED: Removed tab=Archived so it stays on Current Laboratory by default
        header("Location: supply_inventory.php?success=archived");
        exit();
    }
    
}

// D. Handle Restore Logic
if (isset($_POST['submit_restore'])) {
    $id = mysqli_real_escape_string($conn, $_POST['supply_id']);
    // Restore the item to 'Current' status
    $restore_query = "UPDATE supply SET supply_avail = 'Current' WHERE supply_id = '$id'";
    
    if (mysqli_query($conn, $restore_query)) {
        // REDIRECT: Returns to the default tab (Current) but passes the ID for selection
        header("Location: supply_inventory.php?success=restored&id=$id");
        exit();
    }
}


?>
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

    <div id="toast-container" class="toast-container"></div>

    <div class="main-content">
        <div class="page-header">
            <h1>Supply Inventory</h1>
            <p>Monitor laboratory deployment, resource counts, and room archival states.</p>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <input type="hidden" id="php_success" value="<?php echo $_GET['success']; ?>">
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
            <input type="hidden" id="php_error" value="<?php echo $_GET['error']; ?>">
        <?php endif; ?>

        <div class="supply-layout">
            <div class="panel white-panel left-list-panel">
                <div class="panel-header-row">
                    <h3>Existing Supply List</h3>
                    <button type="button" class="btn-green-add" id="openModalBtn"><i class="fas fa-plus-circle"></i> Add</button>
                </div>
                
                <div class="switch-filter-container">
                    <div class="switch-filter">
                        <button type="button" class="switch-btn active" data-value="Current">Current Inventory</button>
                        <button type="button" class="switch-btn" data-value="Archived">Archived</button>
                    </div>
                </div>

                <div class="search-filter-row">
                    <input type="text" class="search-input" id="tableSearch" placeholder="Search a supply">
                    <select class="filter-dropdown" id="statusFilter">
                        <option value="all">All</option>
                        <option value="in_stock">In Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>

                <div class="table-container">
                    <table class="supply-table">
                        <tbody>
                            <?php
                            $query = "SELECT supply_id, supply_name, supply_status, supply_avail FROM supply ORDER BY supply_name ASC";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $id = $row['supply_id'];
                                $badge = ($row['supply_status'] === 'In Stock') ? 'badge green' : 'badge red';
                                // FIXED: Wrapped name and badge in distinct spans for better CSS control
                                echo "<tr class='supply-row' data-id='{$id}' data-avail='{$row['supply_avail']}' style='cursor: pointer;'>
                                        <td>
                                            <span class='supply-name-cell'><strong>".htmlspecialchars($row['supply_name'])."</strong></span>
                                            <span class='supply-status-cell'><span class='{$badge}'>{$row['supply_status']}</span></span>
                                        </td>
                                      </tr>";
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
                            <div id="edit-action-wrapper" style="display:inline-block;">
                                <button type="button" class="btn-green-edit" id="editTrigger"><i class="fas fa-pencil-alt"></i> Edit</button>
                            </div>
                            
                            <div id="archive-action-wrapper" style="display:inline-block;">
                                <form action="supply_inventory.php" method="POST" id="archiveForm" style="display:inline;">
                                    <input type="hidden" name="supply_id" id="archive_supply_id">
                                    <button type="button" class="btn-orange-archive" id="archiveTrigger"><i class="fas fa-box-archive"></i> Archive</button>
                                    <input type="submit" name="submit_archive" id="hiddenArchiveSubmit" style="display:none;">
                                </form>
                            </div>
                            
                            <div id="restore-action-wrapper" style="display:none;">
                                <button type="button" class="btn-green-edit" id="restoreTrigger"><i class="fas fa-rotate-left"></i> Restore</button>
                            </div>
                        </div>
                    </div>
                    <div class="detail-grid">
                        <div class="detail-group"><label>Supply Name:</label><div class="detail-input" id="view_supply_name">Select an item</div></div>
                        <div class="detail-group"><label>Current Status:</label><div class="detail-input" id="view_supply_status">-</div></div>
                    </div>
                    <h4 class="activity-title">Recent Stock Activity:</h4>
                    <div class="activity-table-container">
                        <table class="activity-table">
                            <thead>
                                <tr><th>Date</th><th>Activity</th><th>Updated by</th><th>Remarks</th></tr>
                            </thead>
                            <tbody><tr><td colspan="4" style="text-align:center; padding:20px;">Select a supply to view history.</td></tr></tbody>
                        </table>
                    </div>
                </div>

                <div id="edit-mode" style="display: none;">
                    <form action="supply_inventory.php" method="POST" id="updateForm">
                        <input type="hidden" name="supply_id" id="edit_supply_id">
                        <input type="hidden" name="original_status" id="original_status">
                        
                        <div class="panel-header-row">
                            <h3>Supply Details</h3>
                            <div class="header-actions">
                                <button type="button" class="btn-modal-cancel" id="cancelEdit">Cancel</button>
                                <button type="submit" name="submit_update" class="btn-green-edit"><i class="fas fa-check-circle"></i> Save Update</button>
                            </div>
                        </div>
                        <div class="detail-grid">
                            <div class="detail-group">
                                <label>Supply Name:</label>
                                <input type="text" name="supply_name" id="edit_supply_name" class="detail-input">
                            </div>
                            <div class="detail-group">
                                <label>Current Status:</label>
                                <div class="status-toggle-buttons">
                                    <input type="hidden" name="stock_status" id="stock_status_value">
                                    <button type="button" class="status-option-btn" data-value="In Stock">In Stock</button>
                                    <button type="button" class="status-option-btn" data-value="Out of Stock">Out of Stock</button>
                                </div>
                            </div>
                        </div>
                        <div class="remarks-container" style="margin-top: 20px;">
                            <label class="modal-label">Update Remarks (Required for status change):</label>
                            <textarea name="update_remarks" id="update_remarks" class="modal-input remarks-textarea" style="height: 100px; resize: none;"></textarea>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="archiveConfirmModal" class="modal-overlay">
        <div class="modal-content wide-modal">
            <div class="modal-header-simple">
                <h3>Archive this Supply?</h3>
                <p>This supply will no longer be visible in current inventory and will automatically marked as Out of Stock. Can be restored later.</p>
            </div>
            <div class="modal-body-grid">
                <div class="full-width">
                    <label class="modal-label">Supply Name</label>
                    <div class="detail-input uneditable-confirm" id="confirm_supply_name">-</div>
                </div>
            </div>
            <div class="modal-footer-styled">
                <button type="button" class="btn-modal-cancel" id="cancelArchiveConfirm">Cancel</button>
                <button type="button" class="btn-orange-archive" id="finalArchiveBtn" style="padding: 10px 25px;"><i class="fas fa-box-archive"></i> Archive</button>
            </div>
        </div>
    </div>

    <div id="restoreConfirmModal" class="modal-overlay">
        <div class="modal-content wide-modal">
            <div class="modal-header-simple">
                <h3>Restore this Supply?</h3>
                <p>This supply will be moved back to the active list. You may need to update its stock status manually.</p>
            </div>
            <div class="modal-body-grid">
                <div class="full-width">
                    <label class="modal-label">Supply Name</label>
                    <div class="detail-input uneditable-confirm" id="restore_confirm_supply_name">-</div>
                </div>
            </div>
            <div class="modal-footer-styled">
                <button type="button" class="btn-modal-cancel" id="cancelRestoreConfirm">Cancel</button>
                <form action="supply_inventory.php" method="POST" style="display:inline;">
                    <input type="hidden" name="supply_id" id="restore_supply_id">
                    <button type="submit" name="submit_restore" class="btn-green-edit" style="padding: 10px 25px;"><i class="fas fa-rotate-left"></i> Restore</button>
                </form>
            </div>
        </div>
    </div>

    <div id="addSupplyModal" class="modal-overlay">
        <div class="modal-content wide-modal">
            <div class="modal-header-simple"><h3>Add New Supply</h3></div>
            <form action="supply_inventory.php" method="POST">
                <div class="modal-body-grid">
                    <div class="full-width"><label class="modal-label">Supply Name:</label><input type="text" name="supply_name" class="modal-input" required></div>
                    <div class="full-width"><label class="modal-label">Initial Status:</label>
                        <select name="status" class="modal-input"><option value="In Stock">In Stock</option><option value="Out of Stock">Out of Stock</option></select>
                    </div>
                </div>
                <div class="modal-footer-styled">
                    <button type="button" class="btn-modal-cancel close-modal">Cancel</button>
                    <button type="submit" name="submit_supply" class="btn-modal-create">Create</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/supply_inventory.js?v=<?php echo time(); ?>"></script>
</body>
</html>