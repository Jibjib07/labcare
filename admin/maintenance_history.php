<?php include '../includes/db.php'; ?>
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
                        <h3>Maintenance Logs</h3>
                    </div>

                    <div class="toggle-container">
                        <button class="toggle-link active" onclick="switchHistoryTab('unit', this)">Unit Logs</button>
                        <button class="toggle-link" onclick="switchHistoryTab('asset', this)">Asset Logs</button>
                        <button class="toggle-link" onclick="switchHistoryTab('archives', this)">Archives</button>
                        <button class="toggle-link" onclick="switchHistoryTab('retired-units', this)">Retired Units</button>
                        <button class="toggle-link" onclick="switchHistoryTab('retired-assets', this)">Retired Assets</button>
                    </div>

                    <div class="search-filter-row" style="display: flex; gap: 10px; position: relative;">
                        <input type="text" class="search-input" id="main-search-input" placeholder="Search a set tag...." style="flex: 2;">

                        <button class="btn-filter-date" onclick="toggleDateFilter()" style="flex: 1; background: white; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; color: #666; cursor: pointer; padding: 0 10px;">
                            Date Range <i class="fas fa-filter" style="margin-left: 5px;"></i>
                        </button>

                        <div id="date-filter-popover" class="filter-popover" style="display: none;">
                            <div class="date-inputs">
                                <div class="input-group">
                                    <label>From Date:</label>
                                    <input type="date" id="filter-start-date" class="date-picker">
                                </div>
                                <div class="input-group">
                                    <label>To Date:</label>
                                    <input type="date" id="filter-end-date" class="date-picker">
                                </div>
                            </div>
                            <div class="popover-actions">
                                <button class="btn-cancel" style="padding: 6px 12px; font-size: 12px;" onclick="clearDateFilter()">Clear</button>
                                <button class="btn-green-export" style="padding: 6px 12px; font-size: 12px;" onclick="applyFilters()">Apply Filter</button>
                            </div>
                        </div>
                    </div>

                    <div id="unit-tab" class="tab-content">
                        <div class="table-container">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Room Number</th>
                                        <th>Set Tag</th>
                                        <th>Set ID</th>
                                        <th>Latest Maintenance Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_units = "SELECT lab_room, set_tag, set_id, latest_maintainance FROM units WHERE set_status != 'Condemned' OR set_status IS NULL ORDER BY latest_maintainance DESC";
                                    $result_units = $conn->query($query_units);

                                    if ($result_units && $result_units->num_rows > 0) {
                                        while ($row = $result_units->fetch_assoc()) {
                                            echo "<tr class='selectable-row' data-unit-id='" . htmlspecialchars($row['set_id']) . "' data-tag='" . htmlspecialchars($row['set_tag']) . "'>";
                                            echo "<td>" . htmlspecialchars($row['lab_room']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['set_tag']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['set_id']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['latest_maintainance']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' style='text-align:center;'>No active unit logs available.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="asset-tab" class="tab-content" style="display: none;">
                        <div class="table-container">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Room Number</th>
                                        <th>Asset Tag</th>
                                        <th>Property ID</th>
                                        <th>Latest Maintenance Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_assets = "SELECT lab_room, asset_tag, asset_id, latest_maintenance FROM assets WHERE asset_status != 'Condemned' OR asset_status IS NULL ORDER BY latest_maintenance DESC";
                                    $result_assets = $conn->query($query_assets);

                                    if ($result_assets && $result_assets->num_rows > 0) {
                                        while ($row = $result_assets->fetch_assoc()) {
                                            echo "<tr class='selectable-row' data-prop-id='" . htmlspecialchars($row['asset_id']) . "' data-tag='" . htmlspecialchars($row['asset_tag']) . "'>";
                                            echo "<td>" . htmlspecialchars($row['lab_room']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['asset_tag']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['asset_id']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['latest_maintenance']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' style='text-align:center;'>No active asset logs available.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="archives-tab" class="tab-content" style="display: none;">
                        <div class="table-container">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Room Number</th>
                                        <th>Room Name</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_archives = "SELECT lab_room, lab_name, lab_status FROM laboratories WHERE lab_status = 'Archived'";
                                    $result_archives = $conn->query($query_archives);

                                    if ($result_archives && $result_archives->num_rows > 0) {
                                        while ($row = $result_archives->fetch_assoc()) {
                                            echo "<tr class='selectable-row' data-type='archive' data-room-num='" . htmlspecialchars($row['lab_room']) . "'>";
                                            echo "<td>" . htmlspecialchars($row['lab_room']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['lab_name']) . "</td>";
                                            echo "<td><span class='status-pill'>" . htmlspecialchars($row['lab_status']) . "</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' style='text-align:center;'>No archived rooms.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="retired-units-tab" class="tab-content" style="display: none;">
                        <div class="table-container">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Set ID</th>
                                        <th>Set Tag</th>
                                        <th>Retirement Date</th>
                                        <th>Origin Lab</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_ret_units = "SELECT set_id, set_tag, latest_maintainance, lab_room, set_status FROM units WHERE set_status = 'Condemned' ORDER BY latest_maintainance DESC";
                                    $result_ret_units = $conn->query($query_ret_units);

                                    if ($result_ret_units && $result_ret_units->num_rows > 0) {
                                        while ($row = $result_ret_units->fetch_assoc()) {
                                            echo "<tr class='selectable-row' data-type='retired' data-tag='" . htmlspecialchars($row['set_tag']) . "' data-id='" . htmlspecialchars($row['set_id']) . "'>";
                                            echo "<td>" . htmlspecialchars($row['set_id']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['set_tag']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['latest_maintainance']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['lab_room']) . "</td>";
                                            echo "<td><span class='badge red'>" . htmlspecialchars($row['set_status']) . "</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' style='text-align:center;'>No retired units found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="retired-assets-tab" class="tab-content" style="display: none;">
                        <div class="table-container">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Property ID</th>
                                        <th>Asset Tag</th>
                                        <th>Retirement Date</th>
                                        <th>Origin Lab</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_ret_assets = "SELECT asset_id, asset_tag, latest_maintenance, lab_room, asset_status FROM assets WHERE asset_status = 'Condemned' ORDER BY latest_maintenance DESC";
                                    $result_ret_assets = $conn->query($query_ret_assets);

                                    if ($result_ret_assets && $result_ret_assets->num_rows > 0) {
                                        while ($row = $result_ret_assets->fetch_assoc()) {
                                            echo "<tr class='selectable-row' data-type='retired' data-tag='" . htmlspecialchars($row['asset_tag']) . "' data-prop-id='" . htmlspecialchars($row['asset_id']) . "'>";
                                            echo "<td>" . htmlspecialchars($row['asset_id']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['asset_tag']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['latest_maintenance']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['lab_room']) . "</td>";
                                            echo "<td><span class='badge red'>" . htmlspecialchars($row['asset_status']) . "</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' style='text-align:center;'>No retired assets found.</td></tr>";
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
                            <h3><span class="selected-tag-label"></span> Maintenance Timeline</h3>
                            <button class="btn-red-condemn" onclick="openCondemnModal()"><i class="fas fa-trash-alt"></i> Condemn</button>
                        </div>
                        <div class="table-container">
                            <table class="timeline-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reported by</th>
                                        <th>Affected</th>
                                        <th>Action Taken</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody class="data-body">
                                    <tr class="placeholder-row">
                                        <td colspan="6" style="text-align: center; padding: 40px; color: #757575;">
                                            <em>Click an item on the left to view its maintenance timeline.</em>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="view-retired-timeline" class="history-view" style="display: none;">
                        <div class="section-header-row">
                            <h3><span class="selected-tag-label"></span> Retirement History</h3>
                        </div>
                        <div class="table-container">
                            <table class="timeline-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reported by</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody class="data-body">
                                    <tr class="placeholder-row">
                                        <td colspan="4" style="text-align: center; padding: 40px; color: #757575;">
                                            <em>Click an item on the left to view its retirement history.</em>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="view-archives-details" class="history-view" style="display: none;">
                        <div class="section-header-row">
                            <h3><span id="archive-room-id"></span> Full Details</h3>
                            <div class="action-buttons">
                                <button class="btn-restore" onclick="handleRestore()"><i class="fas fa-circle-plus"></i> Restore</button>
                            </div>
                        </div>
                        <div class="detail-group">
                            <label>Archive Reason:</label>
                            <div class="detail-box" id="archive-reason-text" style="color: #757575; font-style: italic;">
                                Click a room on the left to view archive details.
                            </div>
                        </div>
                        <div class="detail-group">
                            <label>Archived By:</label>
                            <div class="detail-box mini" id="archived-by-name" style="color: #757575;">-</div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <div id="condemn-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h2 class="modal-title">Condemn this Unit?</h2>
            <p class="modal-desc">
                Are you sure you want to condemn <strong id="modal-tag-display">[PC-01]</strong>? This unit will be marked as permanently unusable. This action will be logged in the <strong>History Management</strong> section.
            </p>

            <form id="condemn-form">
                <div class="modal-split">
                    <div class="modal-left">
                        <div class="form-group">
                            <label>Set Tag:</label>
                            <input type="text" id="modal-set-tag" readonly class="readonly-input">
                        </div>
                        <div class="form-group">
                            <label>Set ID:</label>
                            <input type="text" id="modal-set-id" readonly class="readonly-input">
                        </div>
                    </div>

                    <div class="modal-right">
                        <label>Action Taken:</label>
                        <div class="checkbox-grid">
                            <label><input type="checkbox" name="action_taken" value="Hardware Failure"> Hardware Failure (Non-repairable)</label>
                            <label><input type="checkbox" name="action_taken" value="Physical Damage"> Significant Physical Damage</label>
                            <label><input type="checkbox" name="action_taken" value="System Obsolescence"> System Obsolescence (End of Life)</label>
                            <label><input type="checkbox" name="action_taken" value="Other"> Other (Please specify...)</label>
                        </div>

                        <div class="form-group remarks-group">
                            <label>Remarks:</label>
                            <textarea id="modal-remarks" placeholder="Provide specific details for the audit log..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeCondemnModal()">Cancel</button>
                    <button type="button" class="btn-red-condemn" onclick="submitCondemn()"><i class="fas fa-trash-alt"></i> Condemn</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/maintenance_history.js?v=<?php echo time(); ?>"></script>
</body>

</html>