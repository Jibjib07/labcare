<?php
// 1. DATABASE CONNECTION
include '../includes/db.php'; 

// 2. DATA FETCHING LOGIC (For AJAX Requests)
if (isset($_GET['id']) && isset($_GET['type'])) {
    $id = $_GET['id'];
    $type = $_GET['type'];

    // CASE 1: LAB ARCHIVES (JSON RESPONSE FOR RIGHT PANEL)
    if ($type === 'archive') {
        header('Content-Type: application/json');
        
        $stmt = $conn->prepare("SELECT lab_name, lab_room, reason, archived_by, archived_date 
                                FROM lab_history 
                                WHERE lab_room = ? 
                                ORDER BY archived_date DESC LIMIT 1");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            echo json_encode([
                "status" => "success",
                "reason" => $row['reason'], 
                "admin" => $row['archived_by'],    
                "lab_name" => $row['lab_name'],
                "lab_room" => $row['lab_room'],
                "date" => date('m/d/Y', strtotime($row['archived_date']))
            ]);
        } else {
            echo json_encode(["status" => "error", "reason" => "No archive history found.", "admin" => "-"]);
        }
        exit;
    }

    // CASE 2: MAINTENANCE / ASSET / INVENTORY / RETIRED (HTML TABLE RESPONSE)
    $foundData = false;
    
    if ($type === 'inventory') {
        $stmt_supply = $conn->prepare("SELECT suphisto_date, suphisto_act, suphisto_actor, suphisto_remarks FROM supply_history WHERE supply_id = ? ORDER BY suphisto_date DESC");
        $stmt_supply->bind_param("i", $id);
        $stmt_supply->execute();
        $res_supply = $stmt_supply->get_result();

        if ($res_supply->num_rows > 0) {
            $foundData = true;
            while ($row = $res_supply->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars(date('m/d/Y', strtotime($row['suphisto_date']))) . "</td>";
                echo "<td>" . htmlspecialchars($row['suphisto_act']) . "</td>";
                echo "<td>" . htmlspecialchars($row['suphisto_actor']) . "</td>";
                echo "<td>" . htmlspecialchars($row['suphisto_remarks']) . "</td>";
                echo "</tr>";
            }
        }
    } else {
        $stmt_unit = $conn->prepare("SELECT report_date, report_actor, report_affected, report_action, report_remarks, report_status FROM unit_history WHERE set_id = ? ORDER BY report_date DESC");
        $stmt_unit->bind_param("s", $id);
        $stmt_unit->execute();
        $res_unit = $stmt_unit->get_result();

        if ($res_unit->num_rows > 0) {
            $foundData = true;
            while ($row = $res_unit->fetch_assoc()) {
                $status = $row['report_status'];
                $badgeClass = ($status == 'Resolved' || $status == 'Working') ? 'green' : (($status == 'Condemned') ? 'red' : 'orange');
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars(date('m/d/Y', strtotime($row['report_date']))) . "</td>";
                echo "<td>" . htmlspecialchars($row['report_actor']) . "</td>";
                if ($type !== 'retired') {
                    echo "<td>" . htmlspecialchars($row['report_affected'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['report_action'] ?? '-') . "</td>";
                }
                echo "<td>" . htmlspecialchars($row['report_remarks']) . "</td>";
                echo "<td><span class='badge {$badgeClass}'>" . htmlspecialchars($status) . "</span></td>";
                echo "</tr>";
            }
        } else {
            $stmt_asset = $conn->prepare("SELECT report_date, report_actor, report_remarks, report_status FROM asset_history WHERE asset_id = ? ORDER BY report_date DESC");
            $stmt_asset->bind_param("s", $id);
            $stmt_asset->execute();
            $res_asset = $stmt_asset->get_result();

            if ($res_asset->num_rows > 0) {
                $foundData = true;
                while ($row = $res_asset->fetch_assoc()) {
                    $status = $row['report_status'];
                    $badgeClass = ($status == 'Resolved' || $status == 'Working') ? 'green' : (($status == 'Condemned') ? 'red' : 'orange');
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars(date('m/d/Y', strtotime($row['report_date']))) . "</td>";
                    echo "<td>" . htmlspecialchars($row['report_actor']) . "</td>";
                    if ($type !== 'retired') {
                        echo "<td>-</td><td>-</td>";
                    }
                    echo "<td>" . htmlspecialchars($row['report_remarks']) . "</td>";
                    echo "<td><span class='badge {$badgeClass}'>" . htmlspecialchars($status) . "</span></td>";
                    echo "</tr>";
                }
            }
        }
    }

    if (!$foundData) {
        $colSpan = ($type === 'inventory' || $type === 'retired') ? 4 : 6;
        echo "<tr><td colspan='{$colSpan}' style='text-align:center; padding: 20px; color: #757575;'>No activity history found for this item.</td></tr>";
    }
    exit;
}

// 3. POST LOGIC (For Restoration)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (isset($_POST['restore_room_id'])) {
        $room_id = $_POST['restore_room_id'];
        $update = $conn->prepare("UPDATE laboratories SET lab_status = 'Available' WHERE lab_room = ?");
        $update->bind_param("s", $room_id);
        if ($update->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Management - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/maintenance_history.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">History Management</h1>
            <p>Centralized audit trail for maintenance logs, room archives, and retired assets.</p>
        </div>

        <div class="view-section">
            <div class="split-layout">
                
                <div class="panel white-panel left-panel">
                    <div class="section-header-row">
                        <h3 id="nav-title">Activity Logs</h3>
                        <button class="btn-toggle-view" onclick="toggleNavView(this)">
                            View Retirement <i></i>
                        </button>
                    </div>

                    <div class="nav-hierarchy">
                        <div id="log-nav-container" class="pill-container">
                            <button class="main-nav-btn active" onclick="switchHistoryTab('unit', this)">Unit Logs</button>
                            <button class="main-nav-btn" onclick="switchHistoryTab('asset', this)">Asset Logs</button>
                            <button class="main-nav-btn" onclick="switchHistoryTab('inventory', this)">Inventory</button>
                        </div>

                        <div id="retirement-nav-container" class="pill-container" style="display: none;">
                            <button class="main-nav-btn" onclick="switchHistoryTab('retired-units', this)">Condemned Units</button>
                            <button class="main-nav-btn" onclick="switchHistoryTab('retired-assets', this)">Condemned Assets</button>
                            <button class="main-nav-btn" onclick="switchHistoryTab('archives', this)">Lab Archives</button>
                        </div>
                    </div>

                    <div class="search-filter-row" style="display: flex; gap: 10px; position: relative;">
                        <input type="text" class="search-input" id="main-search-input" placeholder="Search record..." style="flex: 2;">
                        <button class="btn-filter-date" onclick="toggleDateFilter()" style="flex: 1;">
                            Date Range <i class="fas fa-filter"></i>
                        </button>
                    </div>

                    <div id="unit-tab" class="tab-content">
                        <div class="table-container">
                            <table class="history-table no-header">
                                <tbody>
                                    <?php
                                    $res = $conn->query("SELECT set_id, set_tag, lab_room, latest_activity, set_status FROM units WHERE set_status IN ('Working', 'For Repair') ORDER BY latest_activity DESC");
                                    while ($row = $res->fetch_assoc()) {
                                        $statusClass = ($row['set_status'] == 'Working') ? 'badge green' : 'badge orange';
                                        $formattedDate = date('m/d/Y', strtotime($row['latest_activity']));
                                        echo "<tr class='selectable-row' data-type='unit' data-id='{$row['set_id']}' data-tag='{$row['set_tag']}'>";
                                        echo "<td>
                                                <div class='tag-info'>
                                                    <strong>PC-" . htmlspecialchars($row['set_tag']) . "</strong>
                                                    <span class='separator'> | </span>
                                                    <span class='room-text'>Room " . htmlspecialchars($row['lab_room']) . "</span>
                                                </div>
                                              </td>";
                                        echo "<td>
                                                <div class='activity-info'>
                                                    <strong>Latest Activity | </strong><span class='date-text'>{$formattedDate}</span>
                                                </div>
                                              </td>";
                                        echo "<td class='text-right'><span class='status-pill {$statusClass}'>" . htmlspecialchars($row['set_status']) . "</span></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="asset-tab" class="tab-content" style="display: none;">
                        <div class="table-container">
                            <table class="history-table no-header">
                                <tbody>
                                    <?php
                                    $res = $conn->query("SELECT asset_id, asset_tag, lab_room, latest_activity, asset_status FROM assets WHERE asset_status IN ('Working', 'For Repair') ORDER BY latest_activity DESC");
                                    while ($row = $res->fetch_assoc()) {
                                        $statusClass = ($row['asset_status'] == 'Working') ? 'badge green' : 'badge orange';
                                        $formattedDate = date('m/d/Y', strtotime($row['latest_activity']));
                                        echo "<tr class='selectable-row' data-type='asset' data-id='{$row['asset_id']}' data-tag='{$row['asset_tag']}'>";
                                        echo "<td><div class='tag-info'><strong>FA-" . htmlspecialchars($row['asset_tag']) . "</strong><span class='separator'> | </span><span class='room-text'>Room " . htmlspecialchars($row['lab_room']) . "</span></div></td>";
                                        echo "<td><div class='activity-info'><strong>Latest Activity | </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                        echo "<td class='text-right'><span class='status-pill {$statusClass}'>" . htmlspecialchars($row['asset_status']) . "</span></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="inventory-tab" class="tab-content" style="display: none;">
                        <div class="table-container">
                            <table class="history-table no-header">
                                <tbody>
                                    <?php
                                    $res = $conn->query("SELECT supply_id, supply_name, latest_activity, supply_status FROM supply ORDER BY latest_activity DESC");
                                    while ($row = $res->fetch_assoc()) {
                                        $status = $row['supply_status'];
                                        $statusClass = ($status == 'In Stock') ? 'badge green' : (($status == 'Low Stock') ? 'badge orange' : 'badge red');
                                        $formattedDate = date('m/d/Y', strtotime($row['latest_activity']));
                                        echo "<tr class='selectable-row' data-type='inventory' data-id='{$row['supply_id']}' data-tag='{$row['supply_name']}'>";
                                        echo "<td><div class='tag-info'><strong>" . htmlspecialchars($row['supply_name']) . "</strong></div></td>";
                                        echo "<td><div class='activity-info'><strong>Latest Activity | </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                        echo "<td class='text-right'><span class='status-pill {$statusClass}'>" . htmlspecialchars($status) . "</span></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="retired-units-tab" class="tab-content" style="display: none;">
                        <div class="table-container">
                            <table class="history-table no-header">
                                <tbody>
                                    <?php
                                    $res = $conn->query("SELECT set_id, set_tag, latest_activity, lab_room FROM units WHERE set_status = 'Condemned' ORDER BY latest_activity DESC");
                                    while ($row = $res->fetch_assoc()) {
                                        $formattedDate = date('m/d/Y', strtotime($row['latest_activity']));
                                        echo "<tr class='selectable-row' data-type='retired' data-id='{$row['set_id']}' data-tag='{$row['set_tag']}'>";
                                        echo "<td><div class='tag-info'><strong>PC-" . htmlspecialchars($row['set_tag']) . "</strong><span class='separator'> | </span><span class='room-text'>Room " . htmlspecialchars($row['lab_room']) . "</span></div></td>";
                                        echo "<td><div class='activity-info'><strong>Condemned On | </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                        echo "<td class='text-right'><span class='status-pill badge red'>Condemned</span></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="retired-assets-tab" class="tab-content" style="display: none;">
                        <div class="table-container">
                            <table class="history-table no-header">
                                <tbody>
                                    <?php
                                    $res = $conn->query("SELECT asset_id, asset_tag, lab_room, latest_activity FROM assets WHERE asset_status = 'Condemned' ORDER BY latest_activity DESC");
                                    while ($row = $res->fetch_assoc()) {
                                        $formattedDate = date('m/d/Y', strtotime($row['latest_activity']));
                                        echo "<tr class='selectable-row' data-type='retired' data-id='{$row['asset_id']}' data-tag='{$row['asset_tag']}'>";
                                        echo "<td><div class='tag-info'><strong>FA-" . htmlspecialchars($row['asset_tag']) . "</strong><span class='separator'> | </span><span class='room-text'>Room " . htmlspecialchars($row['lab_room']) . "</span></div></td>";
                                        echo "<td><div class='activity-info'><strong>Condemned On | </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                        echo "<td class='text-right'><span class='status-pill badge red'>Condemned</span></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="archives-tab" class="tab-content" style="display: none;">
                        <div class="table-container">
                            <table class="history-table no-header">
                                <tbody>
                                    <?php
                                    $res = $conn->query("SELECT l.lab_room, l.lab_name, l.lab_status, MAX(h.archived_date) as archived_date 
                                                         FROM laboratories l 
                                                         INNER JOIN lab_history h ON l.lab_room = h.lab_room 
                                                         WHERE l.lab_status = 'Archived' 
                                                         GROUP BY l.lab_room");
                                    while ($row = $res->fetch_assoc()) {
                                        $formattedDate = date('m/d/Y', strtotime($row['archived_date']));
                                        echo "<tr class='selectable-row' data-type='archive' data-id='{$row['lab_room']}'>";
                                        echo "<td><div class='tag-info'><strong>Room " . htmlspecialchars($row['lab_room']) . "</strong><span class='separator'> | </span><span class='room-text'>(" . htmlspecialchars($row['lab_name']) . ")</span></div></td>";
                                        echo "<td><div class='activity-info'><strong>Archived On | </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                        echo "<td class='text-right'><span class='status-pill badge-archived'>Archived</span></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="panel white-panel right-panel">
                    <div id="view-full-timeline" class="history-view">
                        <div class="section-header-row">
                            <h3 id="timeline-title">Activity Timeline</h3>
                        </div>
                        <div class="table-container">
                            <table class="timeline-table">
                                <thead id="timeline-thead">
                                    <tr><th>Date</th><th>By</th><th>Affected</th><th>Action</th><th>Remarks</th><th>Status</th></tr>
                                </thead>
                                <tbody class="data-body">
                                    <tr class="placeholder-row"><td colspan="6" style="text-align: center; padding: 40px; color: #757575;"><em>Select an item on the left.</em></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="view-retired-timeline" class="history-view" style="display: none;">
                        <div class="section-header-row"><h3>Retirement Record</h3></div>
                        <div class="table-container">
                            <table class="timeline-table">
                                <thead><tr><th>Date</th><th>Reported by</th><th>Remarks</th><th>Status</th></tr></thead>
                                <tbody class="data-body">
                                    <tr class="placeholder-row"><td colspan="4" style="text-align: center; padding: 40px; color: #757575;"><em>Select a retired item.</em></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="view-archives-details" class="history-view" style="display: none;">
                        <div class="section-header-row">
                            <h3>Archive Details</h3>
                            <span id="archive-room-id" style="display: none;"></span>
                            <div class="action-buttons">
                                <button class="btn-restore" onclick="handleRestore()"><i class="fas fa-undo"></i> Restore Room</button>
                            </div>
                        </div>
                        <div class="detail-group">
                            <label>Archive Reason:</label>
                            <div class="detail-box" id="archive-reason-text">Select a room to view details.</div>
                        </div>
                        <div class="detail-group">
                            <label>Archived By:</label>
                            <div class="detail-box mini" id="archived-by-name">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/maintenance_history.js?v=<?php echo time(); ?>"></script>
</body>
</html>
