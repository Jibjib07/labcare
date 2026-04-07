    <?php
    include '../includes/admin_auth.php';
    include '../includes/db.php';


    // Also, dynamically update the `com_age` in the health table so the numbers always match
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

    // --- ADD NEW UNIT LOGIC ---
    function getNextAvailableUnit($conn, $current_room)
    {
        // 1. Fetch all existing tags for this room, ordered numerically
        $sql = "SELECT set_tag FROM units WHERE lab_room = ? ORDER BY CAST(set_tag AS UNSIGNED) ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $current_room);
        $stmt->execute();
        $result = $stmt->get_result();

        $existing_numbers = [];
        while ($row = $result->fetch_assoc()) {
            // Cast to int to remove "PC-" prefix if it exists, or just handle raw number
            $existing_numbers[] = (int)filter_var($row['set_tag'], FILTER_SANITIZE_NUMBER_INT);
        }

        // 2. Find the first gap (e.g., if we have 1, 2, 4, the gap is 3)
        $next = 1;
        while (in_array($next, $existing_numbers)) {
            $next++;
        }

        // 3. Return formatted with leading zero
        return sprintf("%02d", $next);
    }

    function generateStatusToggle($id, $label, $hasSubInput = false, $subLabel = '', $inputId = '', $inputValue = '')
    {
        $subHtml = '';
        if ($hasSubInput) {
            $subHtml = "
                <div class=\"sub-detail-row\">
                    <span>$subLabel:</span>
                    <div id=\"view_$inputId\" class=\"detail-box small-box view-mode\"></div>
                    <input type=\"number\" id=\"edit_$inputId\" class=\"edit-mode edit-input small-edit-box\" min=\"0\" max=\"20\" style=\"display:none;\">
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
                        <button type=\"button\" class=\"status-btn\" data-type=\"repair\" onclick=\"toggleStatus(this)\">Not Working</button>
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
                    <span class="gray-link">Room Management</span>
                    <span class="gray-text"> > </span>
                    <span class="bold-text">Assets Management</span>
                </div>
                <p>Manage hardware specs, serial identifiers, and individual maintenance logs.</p>
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
                            <h3><?php echo $current_room; ?> - <strong>Computer Sets</strong></h3>

                            <div class="header-actions">
                                <button class="btn-transfer" onclick="openModal('transferModal', <?= $current_lab_id ?>)">
                                    <i class="fas fa-exchange-alt"></i> <span class="btn-text">Transfer</span>
                                </button>
                                <button class="btn-green-add" onclick="openModal('addComputerModal', <?= $current_lab_id ?>)">
                                    <i class="fas fa-plus-circle"></i> <span class="btn-text">Add</span>
                                </button>
                            </div>
                        </div>

                        <div class="search-filter-row">
                            <input type="text" id="searchInput" class="search-input" placeholder="Type a number of unit or search..." onkeyup="searchAssets()">

                            <div class="filter-dropdown-container">
                                <button class="filter-btn" onclick="toggleFilterMenu()">Filter <i class="fas fa-filter"></i></button>
                                <ul class="filter-menu" id="filterMenu">
                                    <li onclick="filterAssets('All')">All</li>
                                    <li onclick="filterAssets('Working')">Working</li>
                                    <li onclick="filterAssets('For Repair')">For Repair</li>
                                </ul>
                            </div>
                        </div>

                        <div class="asset-list" id="assetListContainer">
                            <?php
                            $room_filter = $conn->real_escape_string($current_room);

                            // Removed s.specs_property and p.monitor_property
                            $units_query = "
        SELECT u.*, s.specs_purchase
        FROM units u 
        LEFT JOIN specs s ON u.set_id = s.set_id 
        WHERE u.lab_room = '$room_filter' AND (u.set_status != 'Condemned' OR u.set_status IS NULL)
        GROUP BY u.set_id 
        ORDER BY CAST(u.set_tag AS UNSIGNED) ASC
    ";

                            $units_result = $conn->query($units_query);

                            if ($units_result && $units_result->num_rows > 0):
                                $today = new DateTime(); // Initialize date checker
                                while ($unit = $units_result->fetch_assoc()):
                                    $display_name = "PC-" . htmlspecialchars($unit['set_tag']);
                                    $db_status = !empty($unit['set_status']) ? $unit['set_status'] : 'Working';
                                    $purchase_date = $unit['specs_purchase'];

                                    // --- 1. AGE CHECK LOGIC ---
                                    $is_for_condemn = false;
                                    if (!empty($purchase_date)) {
                                        $age = $today->diff(new DateTime($purchase_date))->y;
                                        if ($age >= 5) {
                                            $is_for_condemn = true;
                                        }
                                    }

                                    $display_status = $db_status;
                                    $set_id = htmlspecialchars($unit['set_id']);
                                    $row_class = 'asset-item';

                                    if ($display_status === 'Working') {
                                        $badge_html = "<span class='badge green'>Working</span>";
                                    } elseif ($display_status === 'For Repair') {
                                        $badge_html = "<span class='badge yellow'>For Repair</span>";
                                    } else {
                                        $badge_html = "<span class='badge gray'>$display_status</span>";
                                    }
                            ?>
                                    <div class="<?= $row_class ?>" data-set-id="<?= htmlspecialchars($unit['set_id']) ?>" data-db-status="<?= htmlspecialchars($db_status) ?>" data-is-condemn="<?= $is_for_condemn ? 'true' : 'false' ?>">
                                        <span class="item-name" style="width: 60px;"><?= $display_name ?></span>
                                        
                                        <span style="flex-grow: 1; text-align: center; font-weight: normal; color: #888; font-size: 13px; padding: 0 10px;">
                                            <?= htmlspecialchars($unit['set_id']) ?>
                                        </span>
                                        
                                        <?= $badge_html ?>
                                    </div>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <div style="padding: 20px; color: #666; text-align: center;">
                                    No sets found in <?= htmlspecialchars($current_room) ?>.
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
                                <button class="btn-cancel" id="btnCancelEdit" onclick="cancelEditMode()" style="display: none;">
                                    <i class="fas fa-times"></i> <span class="btn-text">Cancel</span>
                                </button>

                                <button class="action-button edit-btn" id="editToggleButton" onclick="toggleEditMode()">
                                    <i class="fas fa-pen"></i> <span class="btn-text" id="editText">Edit</span>
                                </button>

                                <button class="btn-resolve" id="btnResolve" style="display: none;" onclick="openResolveModal('pc')">
                                    <i class="fas fa-tools"></i> <span class="btn-text">Resolve</span>
                                </button>

                                <button class="btn-delete" id="btnCondemn" onclick="openCondemnModal()">
                                    <i class="fas fa-archive"></i>
                                </button>
                            </div>
                        </div>

                        <div class="specs-tabs">
                            <button class="spec-tab active" onclick="switchTab('identity', this)">
                                <span class="desktop-tab-text">Identity & Specifications</span>
                                <span class="mobile-tab-text">Specs</span>
                            </button>
                            <button class="spec-tab" onclick="switchTab('external', this)">
                                <span class="desktop-tab-text">Software & Hardware</span>
                                <span class="mobile-tab-text">Core</span>
                            </button>
                            <button class="spec-tab" onclick="switchTab('health', this)">
                                <span class="desktop-tab-text">Health & Recent Activities</span>
                                <span class="mobile-tab-text">Health</span>
                            </button>
                            <button class="spec-tab" onclick="switchTab('peripherals', this)">
                                <span class="desktop-tab-text">Peripherals & Power</span>
                                <span class="mobile-tab-text">Peripherals</span>
                            </button>
                        </div>

                        <div class="specs-content-box">

                            <div id="tab-identity" class="tab-content">
                                <div class="detail-grid-row">
                                    <div class="detail-group">
                                        <label>Set ID</label>
                                        <div id="view_set_id" class="detail-box readonly-field"></div>
                                    </div>
                                    <div class="detail-group">
                                        <label>Processor (CPU)</label>
                                        <div id="view_specs_cpu" class="detail-box view-mode"></div>
                                        <input type="text" id="edit_specs_cpu" class="edit-mode edit-input">
                                    </div>
                                </div>

                                <div class="detail-grid-row">
                                    <div class="detail-group">
                                        <label>Brand</label>
                                        <div id="view_specs_brand" class="detail-box view-mode"></div>
                                        <input type="text" id="edit_specs_brand" class="edit-mode edit-input">
                                    </div>
                                    <div class="detail-group">
                                        <label>Operating System</label>
                                        <div id="view_specs_os" class="detail-box view-mode"></div>
                                        <input type="text" id="edit_specs_os" class="edit-mode edit-input">
                                    </div>
                                </div>

                                <div class="detail-grid-row">
                                    <div class="detail-group">
                                        <label>Acquisition Date</label>
                                        <div id="view_specs_purchase" class="detail-box view-mode"></div>
                                        <input type="date" id="edit_specs_purchase" class="edit-mode edit-input" style="display:none;" onchange="calculateEditComputerAge()">
                                    </div>
                                    <div class="detail-group">
                                        <label>Graphics Card (GPU)</label>
                                        <div id="view_specs_gpu" class="detail-box view-mode"></div>
                                        <input type="text" id="edit_specs_gpu" class="edit-mode edit-input">
                                    </div>
                                </div>

                                <div class="detail-grid-row">
                                    <div class="detail-group">
                                        <label>Set Tag</label>
                                        <div id="view_set_tag" class="detail-box readonly-field"></div>
                                    </div>
                                    <div class="detail-group">
                                        <label>RAM (Installed Memory)</label>
                                        <div id="view_specs_ram" class="detail-box view-mode"></div>
                                        <input type="text" id="edit_specs_ram" class="edit-mode edit-input">
                                    </div>
                                </div>

                                <div class="detail-grid-row">
                                    <div class="detail-group">
                                        <label>Room Number</label>
                                        <div id="view_lab_room" class="detail-box readonly-field"></div>
                                    </div>
                                    <div class="detail-group">
                                        <label>Storage Type</label>
                                        <div id="view_specs_storage" class="detail-box view-mode"></div>
                                        <input type="text" id="edit_specs_storage" class="edit-mode edit-input">
                                    </div>
                                </div>

                                <div class="detail-grid-row">
                                    <div class="detail-group"></div> <div class="detail-group">
                                        <label>Storage Capacity</label>
                                        <div id="view_specs_capacity" class="detail-box view-mode"></div>
                                        <input type="text" id="edit_specs_capacity" class="edit-mode edit-input">
                                    </div>
                                </div>
                            </div>

                            <div id="tab-external" class="tab-content" style="display: none;">
                                <div class="detail-grid-row">
                                    <?php echo generateStatusToggle('usb', 'USB Ports', true, 'Available Ports', 'usb_ports'); ?>
                                    <?php echo generateStatusToggle('wifi', 'Display Ports (HDMI/VGA)'); ?>
                                </div>

                                <div class="detail-grid-row">
                                    <?php echo generateStatusToggle('mic', 'RAM'); ?>
                                    <?php echo generateStatusToggle('hdmi', 'Network'); ?>
                                </div>

                                <div class="detail-grid-row">
                                    <?php echo generateStatusToggle('headphone', 'Storage'); ?>
                                    <?php echo generateStatusToggle('display', 'Operating System'); ?>
                                </div>

                                <div class="detail-grid-row">
                                    <?php echo generateStatusToggle('inline', 'Audio Ports'); ?>
                                    <?php echo generateStatusToggle('ethernet', 'Drivers'); ?>
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

                                            <div class="edit-mode" style="display:none; align-items: center; gap: 5px;">
                                                <input type="number" id="edit_com_age" class="edit-input small-edit-box">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <label>Disk Health</label>
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
                                            <input type="number" id="edit_num_repair" class="edit-mode edit-input small-edit-box" style="display:none;">
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <label>System Performance</label>
                                        <div class="status-row">
                                            <span>Status:</span>
                                            <div id="pill_power_health" class="status-pill green view-mode"></div>
                                            <div id="toggle_power_health" class="status-toggle-group edit-mode" style="display:none;">
                                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Healthy</button>
                                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Poor</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="activity-section view-mode">
                                    <div class="activity-header">
                                        <h4>Activity</h4>
                                        <a href="#" class="view-history-link">View Full Maintenance History</a>
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
                                                <span>Brand:</span>
                                                <span id="view_monitor_brand" class="view-mode"></span>
                                                <input type="text" id="edit_monitor_brand" class="edit-mode edit-input" style="display:none;">
                                            </div>
                                            <div class="status-row">
                                                <span>Status:</span>
                                                <div id="pill_monitor_status" class="status-pill green view-mode"></div>
                                                <div id="toggle_monitor_status" class="status-toggle-group edit-mode" style="display:none;">
                                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
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
                                                <input type="text" id="edit_mouse_brand" class="edit-mode edit-input" style="display:none;">
                                            </div>
                                            <div class="status-row">
                                                <span>Status:</span>
                                                <div id="pill_mouse_status" class="status-pill green view-mode"></div>
                                                <div id="toggle_mouse_status" class="status-toggle-group edit-mode" style="display:none;">
                                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)" style="flex: 1; white-space: nowrap; font-size: 11.5px;">Not Working/Missing</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="detail-grid-row">
                                    <div class="detail-group">
                                        <label>Power (PSU/AVR)</label>
                                        <div class="peripheral-info">
                                            <div class="p-row">
                                                <span>Brand:</span>
                                                <span id="view_avr_brand" class="view-mode"></span>
                                                <input type="text" id="edit_avr_brand" class="edit-mode edit-input" style="display:none;">
                                            </div>
                                            <div class="status-row">
                                                <span>Status:</span>
                                                <div id="pill_avr_status" class="status-pill green view-mode"></div>
                                                <div id="toggle_avr_status" class="status-toggle-group edit-mode" style="display:none;">
                                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="detail-group">
                                        <label>Keyboard</label>
                                        <div class="peripheral-info">
                                            <div class="p-row">
                                                <span>Brand:</span>
                                                <span id="view_keyboard_brand" class="view-mode"></span>
                                                <input type="text" id="edit_keyboard_brand" class="edit-mode edit-input" style="display:none;">
                                            </div>
                                            <div class="status-row">
                                                <span>Status:</span>
                                                <div id="pill_keyboard_status" class="status-pill green view-mode"></div>
                                                <div id="toggle_keyboard_status" class="status-toggle-group edit-mode" style="display:none;">
                                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)" style="flex: 1; white-space: nowrap; font-size: 11.5px;">Not Working/Missing</button>
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
                            <h3><?php echo $current_room; ?> - <strong>Facility Assets</strong></h3>

                            <div class="header-actions">
                                <button class="btn-transfer" onclick="openModal('transferModal', <?= $current_lab_id ?>)">
                                    <i class="fas fa-exchange-alt"></i> <span class="btn-text">Transfer</button>
                                <button class="btn-green-add" onclick="openFacilityAssetModal()">
                                    <i class="fas fa-plus-circle"></i> <span class="btn-text">Add</button>
                            </div>
                        </div>

                        <div class="search-filter-row">
                            <input type="text" id="faSearchInput" class="search-input" placeholder="Type a number of asset or search..." onkeyup="searchFAAssets()">

                            <div class="filter-dropdown-container">
                                <button class="filter-btn" onclick="toggleFAFilterMenu()">Filter <i class="fas fa-filter"></i></button>

                                <ul class="filter-menu" id="faFilterMenu">
                                    <li onclick="filterFAAssets('All')">All</li>
                                    <li onclick="filterFAAssets('Working')">Working</li>
                                    <li onclick="filterFAAssets('Not Working')">Not Working</li>
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
                                    // Catch ALL broken states so they turn yellow/orange
                                    if (in_array($status, ['Not Working', 'For Repair', 'Missing Parts'])) {
                                        $badgeClass = 'yellow'; 
                                    }
                                    if (in_array($status, ['Condemned', 'For Condemn'])) {
                                        $badgeClass = 'red';
                                    }

                                    echo "
                                    <div class='asset-item' data-asset-id='$id'>
                                        <span class='item-name' style='width: 60px;'>FA-$tag</span>
                                        
                                        <span style='flex-grow: 1; text-align: center; font-weight: normal; color: #888; font-size: 13px; padding: 0 10px;'>
                                            " . htmlspecialchars($id) . "
                                        </span>
                                        
                                        <span class='badge $badgeClass'>$status</span>
                                    </div>";
                                }
                            } else {
                                echo "<div style='padding: 20px; text-align: center; color: #666;'>No facility assets found for this lab.</div>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="panel white-panel right-panel" id="view-facility-right">
                        <div class="section-header-row details-header-mobile">
                            <button type="button" class="mobile-back-btn" onclick="closeMobileDetails()">
                                <i class="fas fa-arrow-left"></i>
                            </button>

                            <h3 id="view_fa_header_title" style="flex: 1; margin: 0; font-size: 18px;">Select an Asset</h3>

                            <input type="hidden" id="original_fa_status" value="">

                            <div class="action-buttons" style="display: flex; gap: 8px;">
                                <button class="btn-cancel" id="btnCancelEditFA" onclick="cancelEditMode()" style="display: none;">
                                    <i class="fas fa-times"></i> <span class="btn-text">Cancel</span>
                                </button>
                                <button class="action-button edit-btn" id="editToggleButtonFA" onclick="toggleEditMode()">
                                    <i class="fas fa-pen"></i> <span class="btn-text" id="editTextFA">Edit</span>
                                </button>
                                <button class="btn-resolve" id="btnResolveFA" style="display: none;" onclick="openResolveModal('fa')">
                                    <i class="fas fa-tools"></i> <span class="btn-text">Resolve</span>
                                </button>
                                <button class="btn-delete" id="btnCondemnFA" onclick="openCondemnModal()">
                                    <i class="fas fa-archive"></i>
                                </button>
                            </div>
                        </div>

                        <div class="specs-content-box" style="padding: 20px;">
                            
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Asset ID</label>
                                    <div id="view_fa_id" class="detail-box readonly-field">---</div>
                                </div>
                                <div class="detail-group">
                                    <label>Device Name</label>
                                    <div id="view_fa_name" class="detail-box view-mode">---</div>
                                    <input type="text" id="edit_fa_name" class="edit-mode edit-input" style="display:none;">
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Brand</label>
                                    <div id="view_fa_brand" class="detail-box view-mode">---</div>
                                    <input type="text" id="edit_fa_brand" class="edit-mode edit-input" style="display:none;">
                                </div>
                                <div class="detail-group" style="display: flex; flex-direction: column; justify-content: flex-end; padding-bottom: 5px;">
                                    <div class="status-row">
                                        <span style="font-size: 13px; font-weight: 600; color: #333; margin-right: 15px;">Status:</span>
                                        <div id="pill_fa_status" class="status-pill green view-mode">---</div>
                                        <div id="toggle_fa_status" class="status-toggle-group edit-mode" style="display:none;">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="activity-section view-mode">
                                <div class="activity-header">
                                    <h4>Activity Logs</h4>
                                    <a href="maintenance_history.php" class="view-history-link">View Full Maintenance History</a>
                                </div>
                                <div id="fa_activity_log_body" class="recent-reports-feed" style="max-height: 300px; overflow-y: auto; border: 1px solid #eaeaea; border-radius: 8px; background: #fafafa;">
                                    <div style="text-align:center; color:#888; padding: 25px;">
                                        <i class="fas fa-history" style="display:block; font-size:20px; margin-bottom:10px;"></i>
                                        Select an item to view activity logs.
                                    </div>
                                </div>
                            </div>

                        </div> 
                        </div>
                </div>
            </div>

        </div>

        <div id="addComputerModal" class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h3>Add New Computer Unit</h3>
                </div>

                <div class="modal-body">
                    <div class="unit-type-toggle" style="display: flex; align-items: center; gap: 15px;">
                        <label class="radio-option active" onclick="toggleAddMode('single')">
                            <span class="radio-circle checked" id="circle_single"></span> Single Unit
                        </label>
                        <label class="radio-option" onclick="toggleAddMode('multiple')">
                            <span class="radio-circle" id="circle_multiple"></span> Multiple Unit
                        </label>

                        <div id="bulk_input_container" style="display: none; align-items: center; gap: 10px; margin-left: 10px;">
                            <label style="font-size: 13px; font-weight: 600; color: #555;">Quantity:</label>
                            <input type="number" id="bulk_count" class="modal-input small-input" value="2" min="2" max="50" style="width: 70px; padding: 6px;" oninput="updateBulkUnitNumbers()">
                        </div>
                    </div>

                    <div class="specs-tabs modal-tabs-nav">
                        <button class="spec-tab active" onclick="switchModalTab('m-identity', this)">Identity & Specifications</button>
                        <button class="spec-tab" onclick="switchModalTab('m-external', this)">Software & Hardware</button>
                        <button class="spec-tab" onclick="switchModalTab('m-health', this)">Health & Recent Activities</button>
                        <button class="spec-tab" onclick="switchModalTab('m-peripherals', this)">Peripherals & Power</button>
                    </div>

                    <div id="m-identity" class="modal-tab-content">
                        <div class="modal-form-grid">
                            <div class="form-group"><label>Brand</label><input type="text" id="spec_brand" class="modal-input" placeholder="e.g., Dell, HP, Lenovo"></div>
                            <div class="form-group"><label>Processor (CPU)</label><input type="text" id="spec_cpu" class="modal-input" placeholder="e.g., Intel Core i5-12400"></div>

                            <div class="form-group">
                                <label>Acquisition Date</label>
                                <input type="date" id="purchase_date_input" class="modal-input" value="<?= date('Y-m-d'); ?>" onchange="calculateComputerAge()">
                            </div>
                            <div class="form-group"><label>Operating System</label><input type="text" id="spec_os" class="modal-input" placeholder="e.g., Windows 11 Pro"></div>

                            <div class="form-group">
                                <label>Set Tag</label>
                                <input type="text" id="smart_unit_no" class="modal-input" value="<?= getNextAvailableUnit($conn, $current_room) ?>" readonly style="background: #f4f4f4; font-weight: bold; color: #333; cursor: not-allowed;">
                            </div>
                            <div class="form-group"><label>Graphics Card (GPU)</label><input type="text" id="spec_gpu" class="modal-input" placeholder="e.g., Integrated Intel UHD"></div>

                            <div class="form-group">
                                <label>Room Number</label>
                                <input type="text" id="room_number_input" class="modal-input" value="<?= htmlspecialchars($current_room); ?>" readonly style="background: #f4f4f4; cursor: not-allowed;">
                            </div>
                            <div class="form-group"><label>RAM (Installed Memory)</label><input type="text" id="spec_ram" class="modal-input" placeholder="e.g., 16 GB"></div>

                            <div class="form-group"><label>Storage Type</label><input type="text" id="spec_storage" class="modal-input" placeholder="e.g., SSD (M.2 NVMe)"></div>
                            <div class="form-group"><label>Storage Capacity</label><input type="text" id="spec_capacity" class="modal-input" placeholder="e.g., 512 GB"></div>
                        </div>
                    </div>

                    <div id="m-external" class="modal-tab-content" style="display: none;">
                        <div class="modal-form-grid">
                            <div class="form-group">
                                <label>USB Ports</label>
                                <div class="status-toggle-group" id="usb_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                </div>
                                <div class="sub-input-row">
                                    <label>Available Ports:</label>
                                    <input type="number" id="usb_ports_count" class="modal-input small-input" placeholder="e.g., 4">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Display Ports (HDMI/VGA)</label>
                                <div class="status-toggle-group" id="wifi_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>RAM</label>
                                <div class="status-toggle-group" id="mic_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Network</label>
                                <div class="status-toggle-group" id="hdmi_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Storage</label>
                                <div class="status-toggle-group" id="headphone_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Operating System</label>
                                <div class="status-toggle-group" id="display_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Audio Ports</label>
                                <div class="status-toggle-group" id="inline_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Drivers</label>
                                <div class="status-toggle-group" id="ethernet_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="m-health" class="modal-tab-content" style="display: none;">
                        <div class="modal-form-grid">
                            <div class="form-group">
                                <label>Computer Age</label>
                                <div class="sub-input-row">
                                    <label>Total:</label>
                                    <input type="text" id="computer_age_display" class="modal-input" placeholder="Calculated automatically" readonly style="background:#f4f4f4; color: #555;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Disk Health</label>
                                <div class="status-toggle-group" id="disk_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Healthy</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Poor</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Number of Repairs</label>
                                <div class="sub-input-row">
                                    <label>Total:</label>
                                    <input type="number" id="num_repair_input" class="modal-input" value="0" disabled style="background:#e9e9e9;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>System Performance</label>
                                <div class="status-toggle-group" id="power_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Healthy</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Poor</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="m-peripherals" class="modal-tab-content" style="display: none;">
                        <div class="modal-form-grid">

                            <div class="form-group peripheral-group">
                                <label class="group-title">Monitor</label>
                                <div class="sub-field">
                                    <label>Brand:</label>
                                    <input type="text" id="monitor_brand_input" class="modal-input" placeholder="Acer">
                                </div>
                                <div class="status-toggle-group" id="monitor_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                </div>
                            </div>

                            <div class="form-group peripheral-group">
                                <label class="group-title">Mouse</label>
                                <div class="sub-field">
                                    <label>Brand:</label>
                                    <input type="text" id="mouse_brand_input" class="modal-input" placeholder="Acer">
                                </div>
                                <div class="status-toggle-group" id="mouse_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working/Missing</button>
                                </div>
                            </div>

                            <div class="form-group peripheral-group">
                                <label class="group-title">Power (PSU/AVR)</label>
                                <div class="sub-field">
                                    <label>Brand:</label>
                                    <input type="text" id="avr_brand_input" class="modal-input" placeholder="Acer">
                                </div>
                                <div class="status-toggle-group" id="avr_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working</button>
                                </div>
                            </div>

                            <div class="form-group peripheral-group">
                                <label class="group-title">Keyboard</label>
                                <div class="sub-field">
                                    <label>Brand:</label>
                                    <input type="text" id="keyboard_brand_input" class="modal-input" placeholder="Acer">
                                </div>
                                <div class="status-toggle-group" id="keyboard_toggle">
                                    <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                    <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">Not Working/Missing</button>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn-cancel" onclick="closeModal('addComputerModal')">Cancel</button>
                    <button class="btn-create" onclick="submitNewUnit()">
                        <i class="fas fa-plus-circle"></i> Create
                    </button>
                </div>
            </div>
        </div>
        <div id="condemnModal" class="modal-overlay">
            <div class="modal-container condemn-modal">
                <div class="modal-header">
                    <h3 id="condemn_modal_title">Condemned this Unit?</h3>
                </div>

                <div class="modal-body">
                    <p class="condemn-warning">
                        Are you sure you want to condemn <strong id="condemn_display_name">[PC-01]</strong>? This unit will be marked as permanently unusable. This action will be logged in the <strong>History Management</strong> section.
                    </p>

                    <div class="condemn-grid">
                        <div class="condemn-info">
                            <div class="form-group">
                                <label id="condemn_tag_label">Set Tag:</label>
                                <input type="text" id="condemn_set_tag" class="modal-input readonly-input" readonly>
                            </div>
                            <div class="form-group">
                                <label id="condemn_id_label">Set ID:</label>
                                <input type="text" id="condemn_set_id" class="modal-input readonly-input" readonly>
                            </div>
                        </div>

                        <div class="condemn-action">
                            <div class="form-group">
                                <label>Reason:</label>
                                <div class="checkbox-grid">
                                    <label class="check-container"><input type="checkbox" name="condemn_reason" value="Hardware Failure (Non-repairable)"> <span>Hardware Failure (Non-repairable)</span></label>
                                    <label class="check-container"><input type="checkbox" name="condemn_reason" value="Significant Physical Damage"> <span>Significant Physical Damage</span></label>
                                    <label class="check-container"><input type="checkbox" name="condemn_reason" value="System Obsolescence (End of Life)"> <span>System Obsolescence (End of Life)</span></label>
                                    <label class="check-container"><input type="checkbox" name="condemn_reason" value="Other"> <span>Other (Please specify...)</span></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Remarks:</label>
                                <textarea id="condemn_remarks" class="modal-textarea" placeholder="Provide specific details for the audit log..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn-cancel" onclick="closeModal('condemnModal')">Cancel</button>
                    <button class="btn-confirm-condemn" onclick="submitCondemnAction()"><i class="fas fa-archive"></i> Condemn</button>
                </div>
            </div>
        </div>

        <div id="transferModal" class="modal-overlay">
            <div class="modal-container transfer-modal">
                <div class="modal-header">
                    <h3>Transfer Asset to Active Labs</h3>
                </div>

                <div class="modal-body">
                    <div class="transfer-grid">
                        <div class="transfer-list-card">
                            <h4>Computer Unit List</h4>
                            <input type="text" class="modal-input search-sm" placeholder="Search" onkeyup="filterTransferList('transferUnitsTableBody', this.value)">
                            <div class="select-all-row">
                                <label class="check-container select-all-text">
                                    <input type="checkbox" id="selectAllUnits" onclick="toggleTransferSelection('unit')"> <span>Select All</span>
                                </label>
                            </div>
                            <div class="transfer-table-container">
                                <table class="transfer-table">
                                    <thead>
                                        <tr>
                                            <th>Set Tag</th>
                                            <th>Set ID</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transferUnitsTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="transfer-list-card">
                            <h4>Facility Asset List</h4>
                            <input type="text" class="modal-input search-sm" placeholder="Search" onkeyup="filterTransferList('transferAssetsTableBody', this.value)">
                            <div class="select-all-row">
                                <label class="check-container select-all-text">
                                    <input type="checkbox" id="selectAllAssets" onclick="toggleTransferSelection('asset')"> <span>Select All</span>
                                </label>
                            </div>
                            <div class="transfer-table-container">
                                <table class="transfer-table">
                                    <thead>
                                        <tr>
                                            <th>Set Tag</th>
                                            <th>Asset ID</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transferAssetsTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="transfer-form-col">
                            <div class="form-group">
                                <label>Source Room:</label>
                                <input type="text" id="transfer_source_room" class="modal-input readonly-input" readonly>
                            </div>
                            <div class="form-group">
                                <label>Target Room:</label>
                                <div class="select-wrapper">
                                    <select id="transfer_target_lab" class="modal-input custom-select">
                                        <option value="">Room</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Reason:</label>
                                <div class="checkbox-grid" id="transfer_actions">
                                    <label class="check-container"><input type="checkbox" value="Resource Reallocation"> <span>Resource Reallocation</span></label>
                                    <label class="check-container"><input type="checkbox" value="Temporary Relocation"> <span>Temporary Relocation</span></label>
                                    <label class="check-container"><input type="checkbox" value="Facility Maintenance"> <span>Facility Maintenance</span></label>
                                    <label class="check-container"><input type="checkbox" value="Other"> <span>Other</span></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Remarks:</label>
                                <textarea id="transfer_remarks" class="modal-textarea" placeholder="Provide specific details..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn-cancel" onclick="closeModal('transferModal')">Cancel</button>
                    <button class="btn-confirm-transfer" onclick="processTransfer()"><i class="fas fa-check-circle"></i> Confirm</button>
                </div>
            </div>
        </div>

        <div id="addFacilityAssetModal" class="modal-overlay" style="display: none;">
            <div class="modal-container" style="max-width: 450px;">
                <div class="modal-header">
                    <h3>Add New Asset</h3>
                </div>

                <div class="modal-body" style="padding: 20px;">
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600; color: #333;">Device Name</label>
                        <input type="text" id="fa_asset_name" class="modal-input" placeholder="e.g. Printer, Air Conditioner, Projector" style="width: 100%; box-sizing: border-box;">
                    </div>

                    <div style="display: flex; gap: 15px; margin: 15px 0;">
                        <div class="form-group" style="flex: 1;">
                            <label style="font-size: 13px; font-weight: 600; color: #333;">Set Tag</label>
                            <input type="text" id="fa_set_tag" class="modal-input readonly-input" value="(Automatic)" readonly disabled style="width: 100%; box-sizing: border-box; background-color: #f5f5f5; color: #888; text-align: center;">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label style="font-size: 13px; font-weight: 600; color: #333;">Brand</label>
                            <input type="text" id="fa_brand" class="modal-input" placeholder="e.g. Acer, Samsung" style="width: 100%; box-sizing: border-box;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600; color: #333;">Status</label>
                        <select id="fa_status" class="modal-input" style="width: 100%; box-sizing: border-box; background-color: #e8f5e9; color: #2e7d32; font-weight: bold; border: 1px solid #c8e6c9;" onchange="updateFAStatusColor(this)">
                            <option value="Working" style="background: #fff; color: #333;">Working</option>
                            <option value="Not Working" style="background: #fff; color: #333;">Not Working</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer" style="padding: 15px 20px;">
                    <button class="btn-cancel" onclick="closeModal('addFacilityAssetModal')">Cancel</button>
                    <button class="btn-finalize" onclick="submitFacilityAsset()" style="background-color: #4caf50; padding: 8px 25px;"><i class="fas fa-plus-circle"></i> Create</button>
                </div>
            </div>
        </div>

        <div id="logStatusModal" class="modal-overlay" style="display: none;">
            <div class="modal-container" style="max-width: 650px;">
                <div class="modal-header">
                    <h2 id="logStatusModalTitle"><i class="fas fa-clipboard-list"></i> Finalize Updates</h2>
                </div>
                <div class="modal-body" style="max-height: 65vh; overflow-y: auto; padding: 20px; background: #fdfdfd;">
                    <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
                        You are updating <strong id="logStatusUnitName">[PC-00]</strong>. Please provide context for the audit trail.
                    </p>

                    <div id="logStatusDynamicContent"></div>

                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f9f9f9; border-top: 1px solid #eee;">
                    <button type="button" class="btn-cancel" onclick="closeModal('logStatusModal')">Cancel</button>
                    <button type="button" class="btn-confirm" onclick="confirmLogStatus()" style="background: #4caf50; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-check-circle"></i> Submit
                    </button>
                </div>
            </div>
        </div>

        <div id="resolveModal" class="modal-overlay" style="display: none;">
            <div class="modal-container assignment-modal" style="max-width: 900px;">
                <div class="modal-header">
                    <h2>Resolve Issue</h2>
                    <p style="font-size: 13px; color: #666; margin-top: 5px;">Resolving issues for <strong id="resolveUnitName">[PC-00]</strong>.</p>
                </div>

                <div class="modal-body" style="padding: 20px; overflow-y: auto; max-height: 50vh; background: #fdfdfd;">
                    <div id="resolveTableBody">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('resolveModal')">Cancel</button>
                    <button type="button" class="btn-confirm" onclick="submitResolve()">
                        <i class="fas fa-check-circle"></i> Submit
                    </button>
                </div>
            </div>
        </div>


        <div id="reportModal" class="modal-overlay" style="display: none;">
            <div class="modal-container" style="max-width: 650px;">
                <div class="modal-header">
                    <h2 id="reportModalTitle"><i class="fas fa-flag"></i> Report Unspecified Issue</h2>
                </div>
                <div class="modal-body" style="padding: 20px; background: #fdfdfd;">
                    <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
                        You are reporting a general issue for <strong id="reportUnitName">[PC-00]</strong>. This will update the overall status of the unit to "For Repair".
                    </p>

                    <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                            <h4 style="margin: 0; font-size: 14px; color: #f57f17;">
                                <i class="fas fa-exclamation-circle"></i> Status Update
                            </h4>
                            <span style="background-color: #fff3e0; color: #e65100; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 700;">
                                For Repair
                            </span>
                        </div>
                        <div style="display: flex; gap: 20px;">
                            <div style="flex: 1; font-size: 13px; color: #555; background: #f4f4f4; padding: 10px; border-radius: 6px;">
                                <strong>Affected:</strong><br>
                                <span style="display: inline-block; margin-top: 5px;">Others (Unspecified)</span>
                            </div>
                            <div style="flex: 2; display: flex; flex-direction: column; gap: 5px;">
                                <label style="font-size: 13px; font-weight: 600; color: #333;">Remarks:</label>
                                <textarea id="report_remarks" placeholder="Required: What problem did you encounter?..." style="width: 100%; height: 60px; padding: 10px; border-radius: 6px; border: 1px solid #ddd; resize: none; font-size: 13px; box-sizing: border-box;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f9f9f9; border-top: 1px solid #eee;">
                    <button type="button" class="btn-cancel" onclick="closeModal('reportModal')">Cancel</button>
                    <button type="button" class="btn-confirm" onclick="submitReportIssue()" style="background: #4caf50; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-check-circle"></i> Submit
                    </button>
                </div>
            </div>
        </div>

        <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
        <script src="js/assets_management.js?v=<?php echo time(); ?>"></script>

        <div id="toast-container" class="toast-container"></div>

    </body>

    </html>