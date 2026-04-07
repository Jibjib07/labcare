<?php
session_start();
include '../includes/staff_auth.php';
include '../includes/db.php';

date_default_timezone_set('Asia/Manila');

// --- 1. AJAX FETCH HANDLER ---
if (isset($_GET['fetch_id'])) {
    header('Content-Type: application/json');
    $id = mysqli_real_escape_string($conn, $_GET['fetch_id']);

    $query = "SELECT * FROM supply WHERE supply_id = '$id'";
    $res = mysqli_query($conn, $query);
    $supply = mysqli_fetch_assoc($res);

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
                'quantity' => $log['supply_quantity']
            ];
        }
    }

    echo json_encode(['success' => true, 'supply' => $supply, 'history' => $history]);
    exit();
}

// --- 2. DATABASE PROCESSING LOGIC ---

// E. Handle Inventory Transaction
if (isset($_POST['submit_transaction'])) {
    $id = mysqli_real_escape_string($conn, $_POST['supply_id']);
    $trans_type = mysqli_real_escape_string($conn, $_POST['trans_type']);
    $trans_qty = intval($_POST['trans_quantity']);
    $remarks = mysqli_real_escape_string($conn, trim($_POST['trans_remarks']));
    $date = date('Y-m-d H:i:s');
    $actor = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : "System";

    if (empty($remarks)) {
        if ($trans_type === 'restock') {
            $remarks = "Stock Replenished";
        } else {
            $remarks = "Stock Released";
        }
    }

    // UPDATED: Fetch unit_type alongside supply_quantity
    $get_qty_query = "SELECT supply_quantity, unit_type FROM supply WHERE supply_id = '$id'";
    $res = mysqli_query($conn, $get_qty_query);
    
    if ($row = mysqli_fetch_assoc($res)) {
        $current_qty = intval($row['supply_quantity']);
        $unit_type = htmlspecialchars($row['unit_type']); // Extract the unit type

        if ($trans_type === 'release') {
            if ($current_qty <= 0 || $trans_qty > $current_qty) {
                header("Location: supply_inventory.php?error=insufficient_stock&id=$id");
                exit();
            }
            $new_qty = $current_qty - $trans_qty;
            // UPDATED: Added $unit_type to the string
            $activity_text = "Stock Released (-$trans_qty $unit_type)";
        } else {
            $new_qty = $current_qty + $trans_qty;
            // UPDATED: Added $unit_type to the string
            $activity_text = "Stock Replenished (+$trans_qty $unit_type)";
        }

        $new_status = ($new_qty > 0) ? "In Stock" : "Out of Stock";

        $update_query = "UPDATE supply SET supply_quantity = '$new_qty', supply_status = '$new_status', latest_activity = '$date' WHERE supply_id = '$id'";
        if (mysqli_query($conn, $update_query)) {

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
                        // UPDATED: Only fetch Current items since the Archive toggle is gone
                        $query = "SELECT supply_id, supply_name, supply_status, supply_avail FROM supply WHERE supply_avail = 'Current' ORDER BY supply_name ASC";
                        $result = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['supply_id'];
                            $badge = ($row['supply_status'] === 'In Stock') ? 'badge green' : 'badge red';

                            echo "
                            <div class='supply-row asset-item' 
                                data-id='{$id}' 
                                data-avail='{$row['supply_avail']}'> 
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

                            <div id="transaction-action-wrapper" style="display:inline-block;">
                                <button type="button" class="btn-green-edit" id="transactionTrigger">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span class="btn-text">Inventory Transaction</span>
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
            </div>
        </div>
    </div>

    <div id="transactionModal" class="modal-overlay">
        <div class="modal-content wide-modal">

            <div class="modal-header-simple">
                <h3 id="trans_modal_title">Release Stock</h3>
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
                        <textarea name="trans_remarks" id="trans_remarks" class="modal-input remarks-textarea" required placeholder="State the reason for releasing this stock."></textarea>
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

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/supply_inventory.js?v=<?php echo time(); ?>"></script>
</body>

</html>