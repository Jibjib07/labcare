<?php
session_start();
include '../includes/admin_auth.php';
include '../includes/db.php';

date_default_timezone_set('Asia/Manila');

// --- 1. AJAX FETCH HANDLER ---
if (isset($_GET['fetch_id'])) {
    header('Content-Type: application/json');
    $id = mysqli_real_escape_string($conn, $_GET['fetch_id']);

    $query = "SELECT * FROM supply WHERE supply_id = '$id'";
    $res = mysqli_query($conn, $query);
    $supply = mysqli_fetch_assoc($res);

    // UPDATED: Added supply_quantity to the SELECT statement
    $log_query = "SELECT suphisto_date, suphisto_act, suphisto_actor, suphisto_remarks, supply_quantity 
                  FROM supply_history 
                  WHERE supply_id = '$id' 
                  ORDER BY suphisto_date DESC, suphisto_id DESC";
    $log_res = mysqli_query($conn, $log_query);

    $history = [];
    if ($log_res) {
        while ($log = mysqli_fetch_assoc($log_res)) {
            $history[] = [
                'date' => date('m/d/Y h:i A', strtotime($log['suphisto_date'])),
                'activity' => $log['suphisto_act'],
                'user' => $log['suphisto_actor'],
                'remarks' => $log['suphisto_remarks'],
                'quantity' => $log['supply_quantity'] // Passed to JS just in case you need it later
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
    $quantity = isset($_POST['supply_quantity']) ? intval($_POST['supply_quantity']) : 0;
    if ($quantity < 0) {
        $quantity = 0;
    }
    $status = ($quantity > 0) ? "In Stock" : "Out of Stock";

    if (empty($supply_name)) {
        header("Location: supply_inventory.php?error=empty_name");
        exit();
    }

    $date = date('Y-m-d H:i:s');
    $insert_query = "INSERT INTO supply (supply_name, supply_status, supply_avail, latest_activity, supply_quantity) 
                     VALUES ('$supply_name', '$status', 'Current', '$date', '$quantity')";

    if (mysqli_query($conn, $insert_query)) {
        $new_id = mysqli_insert_id($conn);
        $actor = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : "System";

        // UPDATED: Included supply_quantity
        $hist_query = "INSERT INTO supply_history (suphisto_date, suphisto_act, suphisto_stat, suphisto_actor, suphisto_remarks, supply_id, supply_quantity) 
                       VALUES ('$date', 'Added', '$status', '$actor', 'Added to inventory with $quantity units', '$new_id', '$quantity')";
        mysqli_query($conn, $hist_query);
        header("Location: supply_inventory.php?success=added&id=$new_id");
        exit();
    }
}

// B. Handle Update (Name only)
if (isset($_POST['submit_update'])) {
    $id = mysqli_real_escape_string($conn, $_POST['supply_id']);
    $new_name = mysqli_real_escape_string($conn, trim($_POST['supply_name']));
    $old_status = mysqli_real_escape_string($conn, $_POST['original_status']);
    $actor = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : "System";
    $date = date('Y-m-d H:i:s');

    // Fetch current quantity to log it accurately
    $q_res = mysqli_query($conn, "SELECT supply_quantity FROM supply WHERE supply_id = '$id'");
    $curr_qty = ($q_row = mysqli_fetch_assoc($q_res)) ? intval($q_row['supply_quantity']) : 0;

    $update_query = "UPDATE supply SET supply_name = '$new_name', latest_activity = '$date' WHERE supply_id = '$id'";
    if (mysqli_query($conn, $update_query)) {

        // UPDATED: Included supply_quantity
        $hist_query = "INSERT INTO supply_history (suphisto_date, suphisto_act, suphisto_stat, suphisto_actor, suphisto_remarks, supply_id, supply_quantity) 
                       VALUES ('$date', 'Details Updated', '$old_status', '$actor', 'Supply name updated', '$id', '$curr_qty')";
        mysqli_query($conn, $hist_query);
        header("Location: supply_inventory.php?success=updated&id=$id");
        exit();
    }
}

// C. Handle Archival Logic
if (isset($_POST['submit_archive'])) {
    $id = mysqli_real_escape_string($conn, $_POST['supply_id']);
    $date = date('Y-m-d H:i:s');

    // Fetch current quantity to log it accurately before archiving
    $q_res = mysqli_query($conn, "SELECT supply_quantity FROM supply WHERE supply_id = '$id'");
    $curr_qty = ($q_row = mysqli_fetch_assoc($q_res)) ? intval($q_row['supply_quantity']) : 0;

    $archive_query = "UPDATE supply SET supply_avail = 'Archived', supply_status = 'Out of Stock', latest_activity = '$date' WHERE supply_id = '$id'";
    if (mysqli_query($conn, $archive_query)) {
        $actor = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : "System";

        // UPDATED: Included supply_quantity
        $hist_query = "INSERT INTO supply_history (suphisto_date, suphisto_act, suphisto_stat, suphisto_actor, suphisto_remarks, supply_id, supply_quantity) 
                       VALUES ('$date', 'Archived', 'Out of Stock', '$actor', 'Item moved to archive', '$id', '$curr_qty')";
        mysqli_query($conn, $hist_query);
        header("Location: supply_inventory.php?success=archived");
        exit();
    }
}

// D. Handle Restore Logic
if (isset($_POST['submit_restore'])) {
    $id = mysqli_real_escape_string($conn, $_POST['supply_id']);
    $date = date('Y-m-d H:i:s');

    // Fetch current quantity to log it accurately
    $q_res = mysqli_query($conn, "SELECT supply_quantity FROM supply WHERE supply_id = '$id'");
    $curr_qty = ($q_row = mysqli_fetch_assoc($q_res)) ? intval($q_row['supply_quantity']) : 0;

    $restore_query = "UPDATE supply SET supply_avail = 'Current', latest_activity = '$date' WHERE supply_id = '$id'";
    if (mysqli_query($conn, $restore_query)) {
        $actor = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : "System";

        // UPDATED: Included supply_quantity
        $hist_query = "INSERT INTO supply_history (suphisto_date, suphisto_act, suphisto_stat, suphisto_actor, suphisto_remarks, supply_id, supply_quantity) 
                       VALUES ('$date', 'Restored', 'Out of Stock', '$actor', 'Item restored from archive', '$id', '$curr_qty')";
        mysqli_query($conn, $hist_query);
        header("Location: supply_inventory.php?success=restored&id=$id");
        exit();
    }
}

// E. Handle Inventory Transaction
if (isset($_POST['submit_transaction'])) {
    $id = mysqli_real_escape_string($conn, $_POST['supply_id']);
    $trans_type = mysqli_real_escape_string($conn, $_POST['trans_type']); // 'release' or 'restock'
    $trans_qty = intval($_POST['trans_quantity']);
    $remarks = mysqli_real_escape_string($conn, trim($_POST['trans_remarks']));
    $date = date('Y-m-d H:i:s');
    $actor = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : "System";

    // Auto-generate remarks if empty
    if (empty($remarks)) {
        if ($trans_type === 'restock') {
            $remarks = "Stock Replenished";
        } else {
            $remarks = "Stock Released";
        }
    }

    // Get current quantity
    $get_qty_query = "SELECT supply_quantity FROM supply WHERE supply_id = '$id'";
    $res = mysqli_query($conn, $get_qty_query);
    if ($row = mysqli_fetch_assoc($res)) {
        $current_qty = intval($row['supply_quantity']);

        if ($trans_type === 'release') {
            // Backend Error Trapping: Prevent dropping below 0
            if ($current_qty <= 0 || $trans_qty > $current_qty) {
                header("Location: supply_inventory.php?error=insufficient_stock&id=$id");
                exit();
            }
            $new_qty = $current_qty - $trans_qty;
            $activity_text = "Stock Released (-$trans_qty)";
        } else {
            // Restock
            $new_qty = $current_qty + $trans_qty;
            $activity_text = "Stock Replenished (+$trans_qty)";
        }

        // Auto-update status
        $new_status = ($new_qty > 0) ? "In Stock" : "Out of Stock";

        $update_query = "UPDATE supply SET supply_quantity = '$new_qty', supply_status = '$new_status', latest_activity = '$date' WHERE supply_id = '$id'";
        if (mysqli_query($conn, $update_query)) {

            // UPDATED: Included supply_quantity (logging the new resulting quantity)
            $hist_query = "INSERT INTO supply_history (suphisto_date, suphisto_act, suphisto_stat, suphisto_actor, suphisto_remarks, supply_id, supply_quantity) 
                           VALUES ('$date', '$activity_text', '$new_status', '$actor', '$remarks', '$id', '$new_qty')";
            mysqli_query($conn, $hist_query);
            header("Location: supply_inventory.php?success=transaction&id=$id");
            exit();
        }
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

        <div class="supply-layout">

            <?php if (isset($_GET['success'])): ?>
                <input type="hidden" id="php_success" value="<?php echo htmlspecialchars($_GET['success']); ?>">
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <input type="hidden" id="php_error" value="<?php echo htmlspecialchars($_GET['error']); ?>">
            <?php endif; ?>

            <div class="panel white-panel left-list-panel">
                <div class="panel-header-row" style="margin-bottom: 20px;">
                    <h3 style="font-size: 16px;">Existing Supply List</h3>
                    <button type="button" class="btn-green-add" id="openModalBtn">
                        <i class="fas fa-plus-circle"></i>
                        <span class="btn-text">Add</span>
                    </button>
                </div>
                <div class="switch-filter-container" style="margin-bottom: 20px;">
                    <div class="switch-filter">
                        <button type="button" class="switch-btn active" data-value="Current">Current Inventory</button>
                        <button type="button" class="switch-btn" data-value="Archived">Archived</button>
                    </div>
                </div>
                <div class="search-filter-row">
                    <div class="search-box-container" style="flex: 1; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                        <input type="text" class="search-input" id="tableSearch" placeholder="Search a supply..." style="padding-left: 35px; width: 100%;">
                    </div>
                    <select class="filter-dropdown" id="statusFilter" style="width: 140px;">
                        <option value="all">All Status</option>
                        <option value="in_stock">In Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
                <div class="list-scroll-container">
                    <div id="supplyListContainer">
                        <?php
                        $query = "SELECT supply_id, supply_name, supply_status, supply_avail FROM supply ORDER BY supply_name ASC";
                        $result = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['supply_id'];
                            $badge = ($row['supply_status'] === 'In Stock') ? 'badge green' : 'badge red';
                            $displayClass = ($row['supply_avail'] === 'Archived') ? 'style="display:none;"' : '';

                            echo "
                            <div class='supply-row asset-item' 
                                data-id='{$id}' 
                                data-avail='{$row['supply_avail']}' 
                                {$displayClass}> 
                                <div class='item-info'>
                                    <span class='supply-name-cell item-name'>" . htmlspecialchars($row['supply_name']) . "</span>
                                </div>
                                <div class='supply-status-cell'>
                                    <span class='{$badge}'>{$row['supply_status']}</span>
                                </div>
                            </div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="panel white-panel right-detail-panel">
                <div id="view-mode">
                    <div class="mobile-back-row">
                        <button type="button" class="mobile-back-btn" onclick="closeMobileDetails()">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <h3 style="margin:0; font-size:16px;">Back to List</h3>
                    </div>
                    <div class="panel-header-row">
                        <h3>Supply Details</h3>
                        <div class="header-actions">
                            <div id="edit-action-wrapper" style="display:inline-block;">
                                <button type="button" class="btn-green-edit" id="editTrigger">
                                    <i class="fas fa-pencil-alt"></i>
                                    <span class="btn-text">Edit</span>
                                </button>
                            </div>

                            <div id="archive-action-wrapper" style="display:inline-block;">
                                <form action="supply_inventory.php" method="POST" id="archiveForm" style="display:inline;">
                                    <input type="hidden" name="supply_id" id="archive_supply_id">
                                    <button type="button" class="btn-orange-archive" id="archiveTrigger">
                                        <i class="fas fa-box-archive"></i>
                                        <span class="btn-text">Archive</span>
                                    </button>
                                    <input type="submit" name="submit_archive" id="hiddenArchiveSubmit" style="display:none;">
                                </form>
                            </div>

                            <div id="transaction-action-wrapper" style="display:inline-block;">
                                <button type="button" class="btn-green-edit" id="transactionTrigger">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span class="btn-text">Inventory Transaction</span>
                                </button>
                            </div>

                            <div id="restore-action-wrapper" style="display:none;">
                                <button type="button" class="btn-green-edit" id="restoreTrigger">
                                    <i class="fas fa-rotate-left"></i>
                                    <span class="btn-text">Restore</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="detail-group" style="margin-bottom: 20px;">
                        <label>Supply Name:</label>
                        <div class="detail-box" style="background: white; border: 1px solid #eaeaea; justify-content: flex-start; padding: 12px 15px;" id="view_supply_name">Select an item</div>
                    </div>

                    <div class="detail-grid" style="margin-bottom: 25px; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="detail-group">
                            <label>Current Status:</label>
                            <div class="detail-box" style="background: white; border: 1px solid #eaeaea; justify-content: flex-start; padding: 12px 15px;" id="view_supply_status">
                            </div>
                        </div>
                        <div class="detail-group">
                            <label>Quantity:</label>
                            <div class="detail-box" style="background: white; border: 1px solid #eaeaea; color: #111; justify-content: flex-start; padding: 12px 15px;" id="view_supply_quantity">
                            </div>
                        </div>
                    </div>

                    <h4 class="activity-title" style="margin-bottom: 15px;">Recent Stock Activity:</h4>
                    <div class="activity-feed-container" id="activityFeed" style="flex: 1; overflow-y: auto;">
                    </div>
                </div>

                <div id="edit-mode" style="display: none;">
                    <form action="supply_inventory.php" method="POST" id="updateForm">
                        <input type="hidden" name="supply_id" id="edit_supply_id">
                        <input type="hidden" name="original_status" id="original_status">

                        <div class="panel-header-row">
                            <h3>Edit Supply</h3>
                            <div class="header-actions">
                                <button type="button" class="btn-cancel" id="cancelEdit">
                                    <i class="fas fa-times"></i> <span class="btn-text">Cancel</span>
                                </button>
                                <button type="submit" name="submit_update" class="btn-green-edit">
                                    <i class="fas fa-check-circle"></i> <span class="btn-text">Save Update</span>
                                </button>
                            </div>
                        </div>

                        <div class="detail-grid" style="display: block;">
                            <div class="detail-group">
                                <label>Supply Name:</label>
                                <input type="text" name="supply_name" id="edit_supply_name" class="modal-input" required>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="transactionModal" class="modal-overlay">
        <div class="modal-content wide-modal">
            <div class="transaction-tabs">
                <button type="button" class="trans-tab active" data-type="release">Release (Stock Out)</button>
                <button type="button" class="trans-tab" data-type="restock">Restock (Stock In)</button>
            </div>
            <div class="modal-header-simple">
                <h3 id="trans_modal_title">Update Stock</h3>
                <p id="trans_modal_desc">Removing items for use in a laboratory.</p>
            </div>
            <form action="supply_inventory.php" method="POST" id="transactionForm">
                <input type="hidden" name="supply_id" id="trans_supply_id">
                <input type="hidden" name="trans_type" id="trans_type" value="release">

                <div class="modal-body-grid">
                    <div class="full-width">
                        <label class="modal-label">Quantity:</label>
                        <input type="number" name="trans_quantity" id="trans_quantity" class="modal-input" min="1" step="1" required value="1">
                    </div>
                    <div class="full-width">
                        <label class="modal-label">Reason (Remarks):</label>
                        <textarea name="trans_remarks" id="trans_remarks" class="modal-input remarks-textarea" required placeholder="State the reason for stock update."></textarea>
                    </div>
                </div>

                <div class="modal-footer-styled" style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" class="btn-modal-cancel close-modal" style="flex: 1; justify-content: center;">
                        <i class="fas fa-times"></i> <span class="btn-text">Cancel</span>
                    </button>
                    <button type="submit" name="submit_transaction" class="btn-green-edit" style="flex: 1; justify-content: center;">
                        <i class="fas fa-check"></i> <span class="btn-text">Confirm</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="restoreConfirmModal" class="modal-overlay">
        <div class="modal-content wide-modal">
            <div class="modal-header-simple">
                <h3>Restore this Supply?</h3>
                <p>This supply will be moved back to the active list.</p>
            </div>
            <div class="detail-input uneditable-confirm" id="restore_confirm_supply_name">-</div>
            <div class="modal-footer-styled">
                <button type="button" class="btn-modal-cancel" id="cancelRestoreConfirm">
                    <i class="fas fa-times"></i> <span class="btn-text">Cancel</span>
                </button>
                <form action="supply_inventory.php" method="POST" style="display:inline;">
                    <input type="hidden" name="supply_id" id="restore_supply_id">
                    <button type="submit" name="submit_restore" class="btn-green-edit">
                        <i class="fas fa-rotate-left"></i> <span class="btn-text">Restore</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="archiveConfirmModal" class="modal-overlay">
        <div class="modal-content wide-modal">
            <div class="modal-header-simple">
                <h3>Archive this Supply?</h3>
                <p>This supply will no longer be visible in current inventory.</p>
            </div>
            <div class="detail-input uneditable-confirm" id="confirm_supply_name">-</div>
            <div class="modal-footer-styled">
                <button type="button" class="btn-modal-cancel" id="cancelArchiveConfirm">
                    <i class="fas fa-times"></i> <span class="btn-text">Cancel</span>
                </button>
                <button type="button" class="btn-orange-archive" id="finalArchiveBtn">
                    <i class="fas fa-box-archive"></i> <span class="btn-text">Archive</span>
                </button>
            </div>
        </div>
    </div>

    <div id="addSupplyModal" class="modal-overlay">
        <div class="modal-content wide-modal">
            <div class="modal-header-simple">
                <h3>Add New Supply</h3>
            </div>
            <form action="supply_inventory.php" method="POST" id="addSupplyForm">
                <div class="modal-body-grid">
                    <div class="full-width">
                        <label class="modal-label">Supply Name:</label>
                        <input type="text" name="supply_name" class="modal-input" required placeholder="Input supply name.">
                    </div>
                    <div class="full-width">
                        <label class="modal-label">Quantity:</label>
                        <input type="number" name="supply_quantity" id="add_supply_quantity" class="modal-input" min="0" step="1" required placeholder="Enter quantity">
                    </div>
                </div>
                <div class="modal-footer-styled">
                    <button type="button" class="btn-modal-cancel close-modal">
                        <i class="fas fa-times"></i> <span class="btn-text">Cancel</span>
                    </button>
                    <button type="submit" name="submit_supply" class="btn-green-edit">
                        <i class="fas fa-check"></i> <span class="btn-text">Create</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/supply_inventory.js?v=<?php echo time(); ?>"></script>
</body>

</html>