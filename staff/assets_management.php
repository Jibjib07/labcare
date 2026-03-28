<?php
include '../includes/db.php';

// Dynamically update the `com_age` in the health table so the numbers always match
$auto_age_query = "
    UPDATE health h
    JOIN specs s ON h.set_id = s.set_id
    SET h.com_age = TIMESTAMPDIFF(YEAR, s.specs_purchase, CURDATE())
";
$conn->query($auto_age_query);

// Get the lab ID passed in the URL (if any)
$current_lab_id = isset($_GET['lab_id']) ? intval($_GET['lab_id']) : 0;
$current_room = 'Unknown Room';

// Fetch the lab room based on the ID
if ($current_lab_id > 0) {
    $stmt = $conn->prepare("SELECT lab_room FROM laboratories WHERE lab_id = ?");
    $stmt->bind_param("i", $current_lab_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $current_room = htmlspecialchars($row['lab_room']);
    }
    $stmt->close();
} else {
    // Fallback: If no ID, just grab the first lab from the list
    $fallback_query = "SELECT lab_id, lab_room FROM laboratories ORDER BY lab_room ASC LIMIT 1";
    $fallback_result = $conn->query($fallback_query);
    if ($fallback_result && $row = $fallback_result->fetch_assoc()) {
        $current_lab_id = $row['lab_id'];
        $current_room = htmlspecialchars($row['lab_room']);
    }
}

// Helper to generate the Working/For Repair toggles (Removed Edit Inputs for Staff Security)
function generateStatusToggle($id, $label, $hasSubInput = false, $subLabel = '', $inputId = '')
{
    $subHtml = '';
    if ($hasSubInput) {
        $subHtml = "
            <div class=\"sub-detail-row\">
                <span>$subLabel:</span>
                <div id=\"view_$inputId\" class=\"detail-box small-box view-mode\"></div>
            </div>
        ";
    }
    return "
        <div class=\"detail-group\">
            <label>$label</label>
            <div class=\"status-row\">
                <span>Status:</span>
                <div id=\"pill_{$id}_status\" class=\"status-pill green view-mode\"></div>
                <div id=\"toggle_{$id}_status\" class=\"status-toggle-group edit-mode\" style=\"display:none;\">
                    <button type=\"button\" class=\"status-btn active\" data-type=\"working\" onclick=\"toggleStatus(this)\">Working</button>
                    <button type=\"button\" class=\"status-btn\" data-type=\"repair\" onclick=\"toggleStatus(this)\">For Repair</button>
                </div>
            </div>
            $subHtml
        </div>
    ";
}
?>
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
    <?php include "includes/sidebar.php"; ?>

    <div class="main-content">

        <div class="page-header">
            <div class="breadcrumb">
                <span class="gray-link">Computer Laboratory Management</span>
                <span class="gray-text"> > </span>
                <span class="bold-text">Assets Management</span>
            </div>
            <p>View hardware specs, serial identifiers, and submit maintenance reports.</p>
        </div>

        <div id="view-computer">
            <div class="split-layout">

                <div class="panel white-panel left-panel">

                    <div class="panel-top-nav">
                        <a href="laboratory_management.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                        <div class="toggle-container">
                            <button type="button" class="toggle-link active">Computer Sets</button>
                            <button type="button" class="toggle-link" onclick="switchView('facility')">Facility Assets</button>
                        </div>
                    </div>

                    <div class="section-header-row">
                        <h3>Room <?php echo $current_room; ?> - <strong>Computer Sets</strong></h3>
                    </div>

                    <div class="search-filter-row">
                        <input type="text" id="searchInput" class="search-input" placeholder="Type a number of unit or search..." onkeyup="searchAssets()">

                        <div class="filter-dropdown-container">
                            <button class="filter-btn" onclick="toggleFilterMenu()">Filter <i class="fas fa-filter"></i></button>
                            <ul class="filter-menu" id="filterMenu">
                                <li onclick="filterAssets('All')">All</li>
                                <li onclick="filterAssets('Working')">Working</li>
                                <li onclick="filterAssets('For Repair')">For Repair</li>
                                <li onclick="filterAssets('For Condemn')">For Condemn</li>
                                <li onclick="filterAssets('No Property ID')">No Property ID</li>
                            </ul>
                        </div>
                    </div>

                    <div class="asset-list" id="assetListContainer">
                        <?php
                        $room_filter = $conn->real_escape_string($current_room);

                        $units_query = "
        SELECT u.*, s.specs_property, s.specs_purchase, p.monitor_property
        FROM units u 
        LEFT JOIN specs s ON u.set_id = s.set_id 
        LEFT JOIN peripherals p ON u.set_id = p.set_id
        WHERE u.lab_room = '$room_filter' AND (u.set_status != 'Condemned' OR u.set_status IS NULL)
        GROUP BY u.set_id 
        ORDER BY CAST(u.set_tag AS UNSIGNED) ASC
    ";

                        $units_result = $conn->query($units_query);

                        if ($units_result && $units_result->num_rows > 0):
                            $today = new DateTime();
                            while ($unit = $units_result->fetch_assoc()):
                                $display_name = "PC-" . htmlspecialchars($unit['set_tag']);
                                $db_status = !empty($unit['set_status']) ? $unit['set_status'] : 'Working';
                                $purchase_date = $unit['specs_purchase'];

                                // 1. Check for Missing IDs
                                $isMissingId = empty($unit['specs_property']) || empty($unit['monitor_property']);

                                // 2. ONLY proceed if the unit has both Property IDs
                                if (!$isMissingId):
                                    $is_for_condemn = false;
                                    if (!empty($purchase_date)) {
                                        $age = $today->diff(new DateTime($purchase_date))->y;
                                        if ($age >= 5) {
                                            $is_for_condemn = true;
                                        }
                                    }

                                    // Determine Badge HTML
                                    $display_status = $db_status;
                                    if ($display_status === 'Working') {
                                        $badge_html = "<span class='badge green'>Working</span>";
                                    } elseif ($display_status === 'For Repair') {
                                        $badge_html = "<span class='badge yellow'>For Repair</span>";
                                    } else {
                                        $badge_html = "<span class='badge gray'>$display_status</span>";
                                    }
                        ?>
                                    <div class="asset-item"
                                        data-set-id="<?= htmlspecialchars($unit['set_id']) ?>"
                                        data-db-status="<?= htmlspecialchars($db_status) ?>"
                                        data-is-condemn="<?= $is_for_condemn ? 'true' : 'false' ?>">
                                        <span class="item-name"><?= $display_name ?></span>
                                        <?= $badge_html ?>
                                    </div>
                            <?php
                                endif; // End of !$isMissingId check
                            endwhile;
                        else:
                            ?>
                            <div style="padding: 20px; color: #666; text-align: center;">
                                No units found in Room <?= htmlspecialchars($current_room) ?>.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel white-panel right-panel">
                    <div class="section-header-row details-header-mobile">
                        <button type="button" class="mobile-back-btn" onclick="closeMobileDetails()">
                            <i class="fas fa-arrow-left"></i>
                        </button>

                        <h3 style="flex: 1; margin: 0; font-size: 18px;">Select a PC</h3>

                        <div class="action-buttons" style="display: flex; gap: 8px;">
                            <button class="btn-cancel" id="btnCancelReport" onclick="cancelReportMode()" style="display: none;">Cancel</button>

                            <button class="action-btn report-btn" id="btnReport" onclick="toggleReportMode()">
                                <i class="fas fa-edit"></i> <span id="reportText">Report</span>
                            </button>
                        </div>
                    </div>

                    <div class="specs-tabs">
                        <button class="spec-tab active" onclick="switchTab('identity', this)">
                            <span class="desktop-tab-text">Identity & Specifications</span>
                            <span class="mobile-tab-text">Specs</span>
                        </button>
                        <button class="spec-tab" onclick="switchTab('external', this)">
                            <span class="desktop-tab-text">External I/O Ports</span>
                            <span class="mobile-tab-text">Ports</span>
                        </button>
                        <button class="spec-tab" onclick="switchTab('health', this)">
                            <span class="desktop-tab-text">Health & Maintenance Summary</span>
                            <span class="mobile-tab-text">Health</span>
                        </button>
                        <button class="spec-tab" onclick="switchTab('peripherals', this)">
                            Peripherals
                        </button>
                    </div>

                    <div class="specs-content-box">
                        <div id="tab-identity" class="tab-content">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Property ID</label>
                                    <div id="view_specs_property" class="detail-box view-mode"></div>
                                </div>
                                <div class="detail-group">
                                    <label>Processor (CPU)</label>
                                    <div id="view_specs_cpu" class="detail-box view-mode"></div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Brand</label>
                                    <div id="view_specs_brand" class="detail-box view-mode"></div>
                                </div>
                                <div class="detail-group">
                                    <label>Operating System</label>
                                    <div id="view_specs_os" class="detail-box view-mode"></div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Purchase Date</label>
                                    <div id="view_specs_purchase" class="detail-box view-mode"></div>
                                </div>
                                <div class="detail-group">
                                    <label>Graphics Card (GPU)</label>
                                    <div id="view_specs_gpu" class="detail-box view-mode"></div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Room Number</label>
                                    <div id="view_lab_room" class="detail-box readonly-field"></div>
                                </div>
                                <div class="detail-group">
                                    <label>RAM (Installed Memory)</label>
                                    <div id="view_specs_ram" class="detail-box view-mode"></div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Set Tag</label>
                                    <div id="view_set_tag" class="detail-box readonly-field"></div>
                                </div>
                                <div class="detail-group">
                                    <label>Storage Type</label>
                                    <div id="view_specs_storage" class="detail-box view-mode"></div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group"></div>
                                <div class="detail-group">
                                    <label>Storage Capacity</label>
                                    <div id="view_specs_capacity" class="detail-box view-mode"></div>
                                </div>
                            </div>
                        </div>

                        <div id="tab-external" class="tab-content" style="display: none;">
                            <div class="detail-grid-row">
                                <?php echo generateStatusToggle('usb', 'USB Ports', true, 'Available Ports', 'usb_ports'); ?>
                                <?php echo generateStatusToggle('wifi', 'Wi-Fi Card'); ?>
                            </div>
                            <div class="detail-grid-row">
                                <?php echo generateStatusToggle('mic', 'Microphone Jack'); ?>
                                <?php echo generateStatusToggle('hdmi', 'HDMI Port'); ?>
                            </div>
                            <div class="detail-grid-row">
                                <?php echo generateStatusToggle('headphone', 'Headphone Jack'); ?>
                                <?php echo generateStatusToggle('display', 'Display Port'); ?>
                            </div>
                            <div class="detail-grid-row">
                                <?php echo generateStatusToggle('inline', 'In-line Jack'); ?>
                                <?php echo generateStatusToggle('ethernet', 'Ethernet Port'); ?>
                            </div>
                        </div>

                        <div id="tab-health" class="tab-content" style="display: none;">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Computer Age</label>
                                    <div class="sub-detail-row" style="align-items: center;">
                                        <span>Total:</span>
                                        <div id="view_com_age" class="detail-box small-box view-mode"></div>
                                        <div id="view_condemn_badge" class="view-mode" style="margin-left: 10px;"></div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Disk Health (SMART Status)</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div id="pill_disk_health" class="status-pill green view-mode"></div>
                                        <div id="toggle_disk_health" class="status-toggle-group edit-mode" style="display:none;">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Healthy</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Poor</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Number of Repairs</label>
                                    <div class="sub-detail-row">
                                        <span>Total:</span>
                                        <div id="view_num_repair" class="detail-box small-box view-mode"></div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Power Supply Health</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div id="pill_power_health" class="status-pill green view-mode"></div>
                                        <div id="toggle_power_health" class="status-toggle-group edit-mode" style="display:none;">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="activity-section view-mode">
                                <div class="activity-header">
                                    <h4>Activity</h4>
                                </div>
                                <div id="pc_activity_log_body" style="border: 1px solid #eaeaea; border-radius: 8px; background: #fafafa; max-height: 300px; overflow-y: auto;">
                                    <div style="text-align:center; color:#888; padding: 20px;">Activity logs will appear here.</div>
                                </div>
                            </div>
                        </div>

                        <div id="tab-peripherals" class="tab-content" style="display: none;">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Monitor</label>
                                    <div class="peripheral-info">
                                        <div class="p-row">
                                            <span>Property ID:</span>
                                            <span id="view_monitor_property" class="view-mode"></span>
                                        </div>
                                        <div class="p-row">
                                            <span>Brand:</span>
                                            <span id="view_monitor_brand" class="view-mode"></span>
                                        </div>
                                        <div class="status-row">
                                            <span>Status:</span>
                                            <div id="pill_monitor_status" class="status-pill green view-mode"></div>
                                            <div id="toggle_monitor_status" class="status-toggle-group edit-mode" style="display:none;">
                                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="detail-group">
                                    <label>Mouse</label>
                                    <div class="peripheral-info">
                                        <div class="p-row">
                                            <span>Brand:</span>
                                            <span id="view_mouse_brand" class="view-mode"></span>
                                        </div>
                                        <div class="status-row">
                                            <span>Status:</span>
                                            <div id="pill_mouse_status" class="status-pill green view-mode"></div>
                                            <div id="toggle_mouse_status" class="status-toggle-group edit-mode" style="display:none;">
                                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Keyboard</label>
                                    <div class="peripheral-info">
                                        <div class="p-row">
                                            <span>Brand:</span>
                                            <span id="view_keyboard_brand" class="view-mode"></span>
                                        </div>
                                        <div class="status-row">
                                            <span>Status:</span>
                                            <div id="pill_keyboard_status" class="status-pill green view-mode"></div>
                                            <div id="toggle_keyboard_status" class="status-toggle-group edit-mode" style="display:none;">
                                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="detail-group">
                                    <label>AVR (Automatic Voltage Regulator)</label>
                                    <div class="peripheral-info">
                                        <div class="p-row">
                                            <span>Brand:</span>
                                            <span id="view_avr_brand" class="view-mode"></span>
                                        </div>
                                        <div class="status-row">
                                            <span>Status:</span>
                                            <div id="pill_avr_status" class="status-pill green view-mode"></div>
                                            <div id="toggle_avr_status" class="status-toggle-group edit-mode" style="display:none;">
                                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div id="view-facility" style="display: none;">
            <div class="split-layout">
                <div class="panel white-panel left-panel">
                    <div class="panel-top-nav">
                        <a href="laboratory_management.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                        <div class="toggle-container">
                            <button type="button" class="toggle-link" onclick="switchView('computer')">Computer Sets</button>
                            <button type="button" class="toggle-link active">Facility Assets</button>
                        </div>
                    </div>

                    <div class="section-header-row">
                        <h3>Room <?php echo $current_room; ?> - <strong>Facility Assets</strong></h3>
                    </div>

                    <div class="search-filter-row">
                        <input type="text" id="faSearchInput" class="search-input" placeholder="Type a number of asset or search..." onkeyup="searchFAAssets()">

                        <div class="filter-dropdown-container">
                            <button class="filter-btn" onclick="toggleFAFilterMenu()">Filter <i class="fas fa-filter"></i></button>

                            <ul class="filter-menu" id="faFilterMenu">
                                <li onclick="filterFAAssets('All')">All</li>
                                <li onclick="filterFAAssets('Working')">Working</li>
                                <li onclick="filterFAAssets('For Repair')">For Repair</li>
                            </ul>
                        </div>
                    </div>

                    <div class="asset-list" id="facilityListContainer">
                        <?php
                        $lab_id = $_GET['lab_id'] ?? 0;
                        $lab_id = intval($lab_id);

                        $fa_query = "SELECT * FROM assets WHERE lab_id = '$lab_id' AND asset_status != 'Condemned' ORDER BY CAST(asset_tag AS UNSIGNED) ASC";
                        $fa_result = $conn->query($fa_query);

                        if ($fa_result && $fa_result->num_rows > 0) {
                            while ($fa = $fa_result->fetch_assoc()) {
                                $tag = htmlspecialchars($fa['asset_tag']);
                                $status = htmlspecialchars($fa['asset_status']);
                                $id = $fa['asset_id'];

                                $badgeClass = 'green';
                                if ($status === 'For Repair') $badgeClass = 'yellow';
                                if ($status === 'Condemned' || $status === 'For Condemn') $badgeClass = 'red';

                                echo "
                                <div class='asset-item' data-asset-id='$id'>
                                    <div class='asset-info'>
                                        <div class='item-name'>FA-$tag</div>
                                    </div>
                                    <div class='asset-status'>
                                        <span class='badge $badgeClass'>$status</span>
                                    </div>
                                </div>";
                            }
                        } else {
                            echo "<div style='padding: 20px; text-align: center; color: #666;'>No facility assets found for this lab.</div>";
                        }
                        ?>
                    </div>
                </div>

                <div class="panel white-panel right-panel" id="view-facility-right">
                    <div id="fa-view-mode">

                        <div class="section-header-row details-header-mobile">
                            <button type="button" class="mobile-back-btn" onclick="closeMobileDetails()">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <h3 id="view_fa_header_title" style="flex: 1; margin: 0; font-size: 18px;">Select an Asset</h3>

                            <div class="action-buttons" style="display: flex; gap: 8px;">
                                <button class="btn-cancel" id="btnCancelReportFA" onclick="cancelFAReportMode()" style="display: none;">Cancel</button>
                                <button class="action-btn report-btn" id="btnReportFA" onclick="toggleFAReportMode()">
                                    <i class="fas fa-edit"></i> <span id="reportTextFA">Report</span>
                                </button>
                            </div>
                        </div>

                        <div class="detail-content">
                            <div class="detail-grid-row">
                                <div class="detail-group"><label>Property ID:</label>
                                    <div class="detail-box" id="view_fa_tag">---</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group"><label>Brand:</label>
                                    <div class="detail-box" id="view_fa_brand">---</div>
                                </div>
                            </div>
                            <div class="detail-grid-row">
                                <div class="detail-group"><label>Status:</label>
                                    <div class="detail-box view-mode-fa" id="view_fa_status_box">
                                        <span id="view_fa_status">---</span>
                                    </div>
                                    <div id="toggle_fa_status" class="status-toggle-group edit-mode-fa" style="display:none; width: 100%;">
                                        <button type="button" class="status-btn active" data-type="Working" onclick="toggleStatusFA(this)">Working</button>
                                        <button type="button" class="status-btn" data-type="For Repair" onclick="toggleStatusFA(this)">For Repair</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="activity-section">
                            <div class="activity-header">
                                <h4>Activity</h4>
                            </div>
                            <div id="fa_activity_log_body" style="border: 1px solid #eaeaea; border-radius: 8px; background: #fafafa; max-height: 300px; overflow-y: auto;">
                                <div style="text-align:center; color:#888; padding: 20px;">Activity logs will appear here.</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div id="logStatusModal" class="modal" style="display: none;">
            <div class="modal-container" style="max-width: 650px;">
                <div class="modal-header">
                    <h2><i class="fas fa-clipboard-list"></i> Submit Maintenance Report</h2>
                </div>
                <div class="modal-body" style="max-height: 65vh; overflow-y: auto; padding: 20px; background: #fdfdfd;">
                    <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
                        You are submitting a maintenance request for <strong id="logStatusUnitName">[PC-00]</strong>. Please review the details below.
                    </p>

                    <div id="logStatusChangeList"></div>

                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f9f9f9; border-top: 1px solid #eee;">
                    <button type="button" class="btn-cancel" onclick="closeModal('logStatusModal')">Cancel</button>
                    <button type="button" class="btn-confirm" onclick="confirmLogStatus()" style="background: #4caf50; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-paper-plane"></i> Submit Report
                    </button>
                </div>
            </div>
        </div>

        <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
        <script src="js/assets_management.js?v=<?php echo time(); ?>"></script>

        <div id="toast-container" class="toast-container"></div>
</body>

</html>