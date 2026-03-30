<?php
// 1. DATABASE CONNECTION
include '../includes/admin_auth.php';
include '../includes/db.php';

// --- HELPER FUNCTION: STRICT BADGE COLOR RULES ---
if (!function_exists('getBadgeColor')) {
    function getBadgeColor($text)
    {
        $t = strtolower(trim($text));

        // Green badges (Matches if the word is anywhere in the string)
        if (strpos($t, 'resolved') !== false || strpos($t, 'replenished') !== false || strpos($t, 'working') !== false || strpos($t, 'in stock') !== false) {
            return 'green';
        }

        // Orange badges
        if (strpos($t, 'for repair') !== false) {
            return 'orange';
        }

        // Red badges
        if (strpos($t, 'condemned') !== false || strpos($t, 'out of stock') !== false || strpos($t, 'released') !== false) {
            return 'red';
        }

        // Default gray for Update, Added, Restored, etc.
        return 'gray';
    }
}

// 2. DATA FETCHING LOGIC (For AJAX Requests)
if (isset($_GET['id']) && isset($_GET['type'])) {
    $id = $_GET['id'];
    $type = $_GET['type'];

    if ($type === 'archive') {
        header('Content-Type: application/json');
        
        // FIX: Using lab_id to fetch specific archive details based on table schema
        $stmt = $conn->prepare("SELECT lab_name, lab_room, reason, archived_by, archived_date FROM lab_history WHERE lab_id = ? ORDER BY archived_date DESC LIMIT 1");
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
                "date" => date('M d, Y', strtotime($row['archived_date']))
            ]);
        } else {
            echo json_encode(["status" => "error", "reason" => "No archive history found.", "admin" => "-"]);
        }
        exit;
    }

    $foundData = false;
    if ($type === 'inventory') {
        $stmt_supply = $conn->prepare("SELECT suphisto_date, suphisto_act, suphisto_actor, suphisto_remarks, suphisto_stat FROM supply_history WHERE supply_id = ? ORDER BY suphisto_id DESC");
        $stmt_supply->bind_param("i", $id);
        $stmt_supply->execute();
        $res_supply = $stmt_supply->get_result();

        if ($res_supply->num_rows > 0) {
            $foundData = true;
            while ($row = $res_supply->fetch_assoc()) {
                $action = htmlspecialchars($row['suphisto_act']);
                $status = htmlspecialchars($row['suphisto_stat'] ?? '-');

                $badgeClass = getBadgeColor($action);

                $date = htmlspecialchars(date('M d, Y', strtotime($row['suphisto_date'])));
                $actor = htmlspecialchars($row['suphisto_actor']);
                $remarks = htmlspecialchars($row['suphisto_remarks']);

                echo "<div class='timeline-card'>";
                echo "  <div class='timeline-card-header'>";
                echo "      <div class='user-info'><i class='fas fa-user-circle'></i> <strong>{$actor}</strong> <span class='card-date'>&bull; {$date}</span></div>";
                echo "      <span class='status-pill badge {$badgeClass}'>{$action}</span>";
                echo "  </div>";
                echo "  <div class='timeline-card-subheader'>";
                echo "      <span class='info-item'><strong>Status:</strong> {$status}</span>";
                echo "  </div>";
                echo "  <div class='timeline-card-body'>";
                echo "      <div class='remarks-box'>\"{$remarks}\"</div>";
                echo "  </div>";
                echo "</div>";
            }
        }
    } else {
        $stmt_unit = $conn->prepare("SELECT report_date, report_actor, report_affected, report_action, report_remarks, report_status FROM unit_history WHERE set_id = ? ORDER BY report_id DESC");
        $stmt_unit->bind_param("s", $id);
        $stmt_unit->execute();
        $res_unit = $stmt_unit->get_result();

        if ($res_unit->num_rows > 0) {
            $foundData = true;
            while ($row = $res_unit->fetch_assoc()) {
                $status = htmlspecialchars($row['report_status']);
                $badgeClass = getBadgeColor($status);
                $date = htmlspecialchars(date('M d, Y', strtotime($row['report_date'])));
                $actor = htmlspecialchars($row['report_actor']);
                $affected = htmlspecialchars($row['report_affected'] ?? '-');
                $action = htmlspecialchars($row['report_action'] ?? '-');
                $remarks = htmlspecialchars($row['report_remarks']);

                echo "<div class='timeline-card'>";
                echo "  <div class='timeline-card-header'>";
                echo "      <div class='user-info'><i class='fas fa-user-circle'></i> <strong>{$actor}</strong> <span class='card-date'>&bull; {$date}</span></div>";
                echo "      <span class='status-pill badge {$badgeClass}'>{$status}</span>";
                echo "  </div>";
                echo "  <div class='timeline-card-subheader'>";
                echo "      <span class='info-item'><strong>Action:</strong> {$action}</span>";
                echo "      <span class='info-item'><strong>Affected:</strong> {$affected}</span>";
                echo "  </div>";
                echo "  <div class='timeline-card-body'>";
                echo "      <div class='remarks-box'>\"{$remarks}\"</div>";
                echo "  </div>";
                echo "</div>";
            }
        } else {
            $stmt_asset = $conn->prepare("SELECT report_date, report_actor, report_affected, report_action, report_remarks, report_status FROM asset_history WHERE asset_id = ? ORDER BY report_id DESC");
            $stmt_asset->bind_param("s", $id);
            $stmt_asset->execute();
            $res_asset = $stmt_asset->get_result();

            if ($res_asset->num_rows > 0) {
                $foundData = true;
                while ($row = $res_asset->fetch_assoc()) {
                    $status = htmlspecialchars($row['report_status']);
                    $badgeClass = getBadgeColor($status);
                    $date = htmlspecialchars(date('M d, Y', strtotime($row['report_date'])));
                    $actor = htmlspecialchars($row['report_actor']);
                    $affected = htmlspecialchars($row['report_affected'] ?? '-');
                    $action = htmlspecialchars($row['report_action'] ?? '-');
                    $remarks = htmlspecialchars($row['report_remarks']);

                    echo "<div class='timeline-card'>";
                    echo "  <div class='timeline-card-header'>";
                    echo "      <div class='user-info'><i class='fas fa-user-circle'></i> <strong>{$actor}</strong> <span class='card-date'>&bull; {$date}</span></div>";
                    echo "      <span class='status-pill badge {$badgeClass}'>{$status}</span>";
                    echo "  </div>";
                    echo "  <div class='timeline-card-subheader'>";
                    echo "      <span class='info-item'><strong>Action:</strong> {$action}</span>";
                    echo "      <span class='info-item'><strong>Affected:</strong> {$affected}</span>";
                    echo "  </div>";
                    echo "  <div class='timeline-card-body'>";
                    echo "      <div class='remarks-box'>\"{$remarks}\"</div>";
                    echo "  </div>";
                    echo "</div>";
                }
            }
        }
    }

    if (!$foundData) {
        echo "<div style='text-align: center; padding: 40px; color: #757575;'>No activity history found for this item.</div>";
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_room_id'])) {
    header('Content-Type: application/json');
    $update = $conn->prepare("UPDATE laboratories SET lab_status = 'Available' WHERE lab_room = ?");
    $update->bind_param("s", $_POST['restore_room_id']);
    echo json_encode(["success" => $update->execute()]);
    exit;
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
                        <button class="btn-toggle-view" onclick="toggleNavView(this)">View Retirement</button>
                    </div>

                    <div class="nav-hierarchy">
                        <div id="log-nav-container" class="pill-container">
                            <button class="main-nav-btn active" onclick="switchHistoryTab('unit', this)">Unit Logs</button>
                            <button class="main-nav-btn" onclick="switchHistoryTab('asset', this)">Asset Logs</button>
                            <button class="main-nav-btn" onclick="switchHistoryTab('inventory', this)">Inventory</button>
                        </div>

                        <div id="retirement-nav-container" class="pill-container" style="display: none;">
                            <button class="main-nav-btn" onclick="switchHistoryTab('retired-units', this)">Units</button>
                            <button class="main-nav-btn" onclick="switchHistoryTab('retired-assets', this)">Assets</button>
                            <button class="main-nav-btn" onclick="switchHistoryTab('archives', this)">Lab</button>
                        </div>
                    </div>

                    <div class="search-filter-row" style="position: relative;">
                        <input type="text" class="search-input" id="main-search-input" placeholder="Search record..." style="flex: 2;">

                        <div class="date-filter-container" style="flex: 1;">
                            <button class="btn-filter-date" onclick="toggleDateDropdown(event)" style="width: 100%; height: 100%; position: relative; z-index: 101;">
                                Date Range <i class="fas fa-filter"></i>
                            </button>

                            <div class="dropdown-backdrop" id="dropdown-backdrop" onclick="closeDateDropdown()"></div>

                            <div class="date-dropdown" id="date-dropdown">
                                <div class="date-input-group">
                                    <label>Start Date</label>
                                    <input type="date" id="filter-start-date" onchange="applyFilters()">
                                </div>
                                <div class="date-input-group">
                                    <label>End Date</label>
                                    <input type="date" id="filter-end-date" onchange="applyFilters()">
                                </div>
                                <div class="date-actions">
                                    <button onclick="clearDateFilter(event)" class="btn-clear-date">Clear</button>
                                    <button onclick="applyFilters(); closeDateDropdown();" class="btn-apply-date">Done</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="unit-tab" class="tab-content">
                        <div class="table-container">
                            <table class="history-table no-header">
                                <tbody>
                                    <?php
                                    $res = $conn->query("SELECT set_id, set_tag, lab_room, latest_activity, set_status FROM units WHERE set_status IN ('Working', 'For Repair') ORDER BY latest_activity DESC");
                                    if ($res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            $statusClass = getBadgeColor($row['set_status']);
                                            $formattedDate = date('m/d/Y', strtotime($row['latest_activity']));
                                            echo "<tr class='selectable-row' data-type='unit' data-id='{$row['set_id']}' data-tag='PC-{$row['set_tag']}'>";
                                            echo "<td><div class='tag-info'><strong>PC-{$row['set_tag']}</strong><span class='separator'> | </span><span class='room-text'>Room {$row['lab_room']}</span></div></td>";
                                            echo "<td><div class='activity-info'><strong>Latest Activity <span class='separator'>|</span> </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                            echo "<td class='text-right'><span class='status-pill badge {$statusClass}'>{$row['set_status']}</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr class='no-data-row'><td colspan='3'>No active units found.</td></tr>";
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
                                    if ($res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            $statusClass = getBadgeColor($row['asset_status']);
                                            $formattedDate = date('m/d/Y', strtotime($row['latest_activity']));
                                            echo "<tr class='selectable-row' data-type='asset' data-id='{$row['asset_id']}' data-tag='FA-{$row['asset_tag']}'>";
                                            echo "<td><div class='tag-info'><strong>FA-{$row['asset_tag']}</strong><span class='separator'> | </span><span class='room-text'>Room {$row['lab_room']}</span></div></td>";
                                            echo "<td><div class='activity-info'><strong>Latest Activity <span class='separator'>|</span> </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                            echo "<td class='text-right'><span class='status-pill badge {$statusClass}'>{$row['asset_status']}</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr class='no-data-row'><td colspan='3'>No active assets found.</td></tr>";
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
                                    if ($res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            $statusClass = getBadgeColor($row['supply_status']);
                                            $formattedDate = date('m/d/Y', strtotime($row['latest_activity']));
                                            echo "<tr class='selectable-row' data-type='inventory' data-id='{$row['supply_id']}' data-tag='{$row['supply_name']}'>";
                                            echo "<td><div class='tag-info'><strong>{$row['supply_name']}</strong></div></td>";
                                            echo "<td><div class='activity-info'><strong>Latest Activity <span class='separator'>|</span> </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                            echo "<td class='text-right'><span class='status-pill badge {$statusClass}'>{$row['supply_status']}</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr class='no-data-row'><td colspan='3'>No inventory records found.</td></tr>";
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
                                    if ($res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            $formattedDate = date('m/d/Y', strtotime($row['latest_activity']));
                                            echo "<tr class='selectable-row' data-type='retired' data-id='{$row['set_id']}' data-tag='PC-{$row['set_tag']}'>";
                                            echo "<td><div class='tag-info'><strong>PC-{$row['set_tag']}</strong><span class='separator'> | </span><span class='room-text'>Room {$row['lab_room']}</span></div></td>";
                                            echo "<td><div class='activity-info'><strong>Condemned On <span class='separator'>|</span> </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                            echo "<td class='text-right'><span class='status-pill badge red'>Condemned</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr class='no-data-row'><td colspan='3'>No condemned units found.</td></tr>";
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
                                    if ($res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            $formattedDate = date('m/d/Y', strtotime($row['latest_activity']));
                                            echo "<tr class='selectable-row' data-type='retired' data-id='{$row['asset_id']}' data-tag='FA-{$row['asset_tag']}'>";
                                            echo "<td><div class='tag-info'><strong>FA-{$row['asset_tag']}</strong><span class='separator'> | </span><span class='room-text'>Room {$row['lab_room']}</span></div></td>";
                                            echo "<td><div class='activity-info'><strong>Condemned On <span class='separator'>|</span> </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                            echo "<td class='text-right'><span class='status-pill badge red'>Condemned</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr class='no-data-row'><td colspan='3'>No condemned assets found.</td></tr>";
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
                                    // FIX: Selected and grouped by lab_id to pass correctly to Javascript fetch
                                    $res = $conn->query("SELECT l.lab_id, l.lab_room, l.lab_name, l.lab_status, MAX(h.archived_date) as archived_date 
                                                         FROM laboratories l 
                                                         INNER JOIN lab_history h ON l.lab_room = h.lab_room 
                                                         WHERE l.lab_status = 'Archived' 
                                                         GROUP BY l.lab_id, l.lab_room");
                                    if ($res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            $formattedDate = date('m/d/Y', strtotime($row['archived_date']));
                                            // Pass lab_id dynamically instead of lab_room
                                            echo "<tr class='selectable-row' data-type='archive' data-id='{$row['lab_id']}' data-tag='Room {$row['lab_room']}'>";
                                            echo "<td><div class='tag-info'><strong>Room {$row['lab_room']}</strong><span class='separator'> | </span><span class='room-text'>({$row['lab_name']})</span></div></td>";
                                            echo "<td><div class='activity-info'><strong>Archived On <span class='separator'>|</span> </strong><span class='date-text'>{$formattedDate}</span></div></td>";
                                            echo "<td class='text-right'><span class='status-pill badge-archived'>Archived</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr class='no-data-row'><td colspan='3'>No archived rooms found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="panel white-panel right-panel" id="right-panel">
                    <div class="mobile-detail-header">
                        <button class="mobile-back-btn" onclick="closeMobileDetails()">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <h3 id="mobile-detail-title">Details</h3>
                    </div>

                    <div id="view-full-timeline" class="history-view">
                        <div class="section-header-row">
                            <h3 id="timeline-title">Activity Timeline</h3>
                            <button id="btn-export-active" class="btn-export" style="display: none;" onclick="exportTimeline()">
                                <i class="fas fa-file-export"></i> Export
                            </button>
                        </div>
                        <div class="table-container">
                            <div class="timeline-feed data-body">
                                <div style="text-align: center; padding: 40px; color: #757575;"><em>Select an item on the left.</em></div>
                            </div>
                        </div>
                    </div>

                    <div id="view-retired-timeline" class="history-view" style="display: none;">
                        <div class="section-header-row">
                            <h3 id="retired-title">Retirement Record</h3>
                            <button id="btn-export-retired" class="btn-export" style="display: none;" onclick="exportTimeline()">
                                <i class="fas fa-file-export"></i> Export
                            </button>
                        </div>
                        <div class="table-container">
                            <div class="timeline-feed data-body">
                                <div style="text-align: center; padding: 40px; color: #757575;"><em>Select a retired item.</em></div>
                            </div>
                        </div>
                    </div>

                    <div id="view-archives-details" class="history-view" style="display: none;">
                        <div class="section-header-row">
                            <h3 id="archive-title">Archive Details</h3>
                        </div>
                        <div class="timeline-card archive-summary-card">
                            <div class="timeline-card-subheader archive-meta">
                                <div class="user-info">
                                    <i class="fas fa-user-circle"></i>
                                    <span><strong>Archived By:</strong> <span id="archived-by-name">-</span></span>
                                </div>
                                <div class="date-info" style="font-size: 13px; color: #666; display: flex; align-items: center; gap: 5px;">
                                    <span><strong>Date:</strong> <span id="archive-date-text">-</span></span>
                                </div>
                            </div>
                            <div class="timeline-card-body">
                                <strong style="display: block; margin-bottom: 8px; font-size: 13px; color: #222;">Reason for Archiving:</strong>
                                <div class="remarks-box" id="archive-reason-text">Waiting for selection...</div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <div id="toast-container" class="toast-container"></div>
    
    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/maintenance_history.js?v=<?php echo time(); ?>"></script>
</body>

</html>