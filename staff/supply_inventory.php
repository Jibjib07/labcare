<?php
session_start();
include '../includes/db.php';

date_default_timezone_set('Asia/Manila');

// --- 1. AJAX FETCH HANDLER (READ-ONLY) ---
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
                'date' => date('m/d/Y h:i A', strtotime($log['suphisto_date'])),
                'activity' => $log['suphisto_act'],
                'user' => $log['suphisto_actor'],
                'remarks' => $log['suphisto_remarks']
            ];
        }
    }

    echo json_encode(['success' => true, 'supply' => $supply, 'history' => $history]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supply Inventory (Staff View) - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/supply_inventory.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Supply Inventory</h1>
            <p>View current laboratory deployment and stock status.</p>
        </div>

        <div class="supply-layout">
            <div class="panel white-panel left-list-panel">
                <div class="panel-header-row" style="margin-bottom: 20px;">
                    <h3 style="font-size: 16px;">Available Supply List</h3>
                </div>

                <div class="search-filter-row">
                    <div class="search-box-container" style="flex: 1; position: relative;">
                        <input type="text" class="search-input" id="tableSearch" placeholder="Search supplies..." style="width: 100%;">
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
                        // Filtered to only show "Current" items for staff
                        $query = "SELECT supply_id, supply_name, supply_status FROM supply WHERE supply_avail = 'Current' ORDER BY supply_name ASC";
                        $result = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['supply_id'];
                            $badge = ($row['supply_status'] === 'In Stock') ? 'badge green' : 'badge red';
                            
                            echo "
                            <div class='supply-row asset-item' data-id='{$id}' data-avail='Current'> 
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
                    </div>

                    <div class="detail-group">
                        <label>Supply Name:</label>
                        <div class="detail-box" id="view_supply_name">Select an item from the list to view details</div>
                    </div>

                    <h4 class="activity-title" style="margin-top: 15px; margin-bottom: 15px; font-weight: 700; font-size: 14px;">Supply History:</h4>
                    <div class="activity-feed-container" id="activityFeed" style="flex: 1; overflow-y: auto; border: 1px solid #f0f0f0; border-radius: 8px; background: #fff;">
                        </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/supply_inventory.js?v=<?php echo time(); ?>"></script>
</body>
</html>