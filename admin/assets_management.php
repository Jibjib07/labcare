<?php
include '../includes/db.php';
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
            <p>Manage hardware specs, serial identifiers, and individual maintenance logs.</p>
        </div>

        <div id="view-computer">
            <div class="split-layout">

                <div class="panel white-panel left-panel">

                    <div class="panel-top-nav">
                        <a href="laboratory_management.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                        <div class="toggle-container">
                            <button type="button" class="toggle-link active">Computer Unit</button>
                            <button type="button" class="toggle-link" onclick="switchView('facility')">Facility Assets</button>
                        </div>
                    </div>

                    <div class="section-header-row">
                        <h3>Room <?php echo $current_room; ?> - <strong>Computer Units</strong></h3>

                        <div class="header-actions">
                            <button class="btn-transfer" onclick="openModal('transferModal', <?= $current_lab_id ?>)">
                                <i class="fas fa-exchange-alt"></i> Transfer
                            </button>

                            <button class="btn-green-add" onclick="openModal('addComputerModal', <?= $current_lab_id ?>)">
                                <i class="fas fa-plus-circle"></i> Add
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
                                <li onclick="filterAssets('For Condemn')">For Condemn</li>
                                <li onclick="filterAssets('No Property ID')">No Property ID</li>
                            </ul>
                        </div>
                    </div>

                    <div class="asset-list" id="assetListContainer">
                        <?php
                        $room_filter = $conn->real_escape_string($current_room);

                        $units_query = "
        SELECT u.*, s.specs_property 
        FROM units u 
        LEFT JOIN specs s ON u.set_id = s.set_id 
        WHERE u.lab_room = '$room_filter' 
        ORDER BY CAST(u.set_tag AS UNSIGNED) ASC
    ";

                        $units_result = $conn->query($units_query);

                        if ($units_result && $units_result->num_rows > 0):
                            while ($unit = $units_result->fetch_assoc()):
                                $display_name = "PC-" . htmlspecialchars($unit['set_tag']);

                                // 1. Get the base status first
                                $status = !empty($unit['set_status']) ? $unit['set_status'] : 'Unknown';

                                // 2. OVERRIDE it if the Property ID is missing
                                if (empty($unit['specs_property'])) {
                                    $status = 'No Property ID';
                                }

                                $set_id = htmlspecialchars($unit['set_id']);

                                $status_map = [
                                    'Working'        => 'badge green',
                                    'For Condemn'    => 'badge red',
                                    'For Repair'     => 'badge yellow',
                                    'No Property ID' => 'badge purple'
                                ];

                                $badge_class = isset($status_map[$status]) ? $status_map[$status] : 'badge gray';
                                $row_class   = ($status === 'No Property ID') ? 'asset-item gray-row' : 'asset-item';
                        ?>
                                <div class="<?= $row_class ?>" onclick="selectUnit(this, '<?= $set_id ?>')">
                                    <span class="item-name"><?= $display_name ?></span>
                                    <span class="<?= $badge_class ?>"><?= htmlspecialchars($status) ?></span>
                                </div>
                            <?php
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
                    <div class="section-header-row">
                        <h3>PC-01 Details</h3>
                        <div class="action-buttons">
                            <button class="btn-edit" id="editToggleButton" onclick="toggleEditMode()">
                                <i class="fas fa-pen"></i> <span id="editText">Edit</span>
                            </button>

                            <button class="btn-resolve" id="btnResolve"><i class="fas fa-history"></i> Resolve</button>
                            <button class="btn-condemn" id="btnCondemn" onclick="openModal('condemnModal')">
                                <i class="fas fa-trash-alt"></i> Condemn
                            </button>

                            <button class="btn-cancel-edit" id="btnCancelEdit" onclick="cancelEditMode()" style="display: none;">Cancel</button>
                        </div>
                    </div>

                    <div class="specs-tabs">
                        <button class="spec-tab active" onclick="switchTab('identity', this)">Identity & Specifications</button>
                        <button class="spec-tab" onclick="switchTab('external', this)">External I/O Ports</button>
                        <button class="spec-tab" onclick="switchTab('health', this)">Health & Maintenance Summary</button>
                        <button class="spec-tab" onclick="switchTab('peripherals', this)">Peripherals</button>
                    </div>

                    <div class="specs-content-box">

                        <div id="tab-identity" class="tab-content">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Property ID</label>
                                    <div class="detail-box view-mode">1025478521</div>
                                    <input type="text" class="edit-mode edit-input" value="1025478521">
                                </div>
                                <div class="detail-group">
                                    <label>Processor (CPU)</label>
                                    <div class="detail-box view-mode">Intel Core i5-12400</div>
                                    <input type="text" class="edit-mode edit-input" value="Intel Core i5-12400">
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Brand</label>
                                    <div class="detail-box view-mode">Asus</div>
                                    <input type="text" class="edit-mode edit-input" value="Asus">
                                </div>
                                <div class="detail-group">
                                    <label>Operating System</label>
                                    <div class="detail-box view-mode">Windows 11 Pro</div>
                                    <input type="text" class="edit-mode edit-input" value="Windows 11 Pro">
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Purchase Date</label>
                                    <div class="detail-box view-mode">11/20/2025</div>
                                    <input type="date" class="edit-mode edit-input" value="2025-11-20">
                                </div>
                                <div class="detail-group">
                                    <label>Graphics Card (GPU)</label>
                                    <div class="detail-box view-mode">Integrated Intel UHD Graphics 730</div>
                                    <input type="text" class="edit-mode edit-input" value="Integrated Intel UHD Graphics 730">
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Room Number</label>
                                    <div class="detail-box readonly-field">104</div>
                                </div>
                                <div class="detail-group">
                                    <label>RAM (Installed Memory)</label>
                                    <div class="detail-box view-mode">16 GB</div>
                                    <input type="text" class="edit-mode edit-input" value="16 GB">
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Set Tag</label>
                                    <div class="detail-box readonly-field">01</div>
                                </div>
                                <div class="detail-group">
                                    <label>Storage Type</label>
                                    <div class="detail-box view-mode">SSD (M.2 NVMe)</div>
                                    <input type="text" class="edit-mode edit-input" value="SSD (M.2 NVMe)">
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group"></div>
                                <div class="detail-group">
                                    <label>Storage Capacity</label>
                                    <div class="detail-box view-mode">512 GB</div>
                                    <input type="text" class="edit-mode edit-input" value="512 GB">
                                </div>
                            </div>
                        </div>

                        <div id="tab-external" class="tab-content" style="display: none;">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>USB Ports</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div class="status-pill green view-mode">Working</div>
                                        <div class="status-toggle-group edit-mode">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                    <div class="sub-detail-row">
                                        <span>Available Ports:</span>
                                        <div class="detail-box small-box view-mode">8</div>
                                        <input type="number" class="edit-mode edit-input small-edit-box" value="8" min="0" max="20">
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Wi-Fi Card</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div class="status-pill green view-mode">Working</div>
                                        <div class="status-toggle-group edit-mode">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Microphone Jack</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div class="status-pill green view-mode">Working</div>
                                        <div class="status-toggle-group edit-mode">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>HDMI Port</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div class="status-pill green view-mode">Working</div>
                                        <div class="status-toggle-group edit-mode">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Headphone Jack</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div class="status-pill green view-mode">Working</div>
                                        <div class="status-toggle-group edit-mode">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Display Port</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div class="status-pill green view-mode">Working</div>
                                        <div class="status-toggle-group edit-mode">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>In-line Jack</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div class="status-pill green view-mode">Working</div>
                                        <div class="status-toggle-group edit-mode">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Ethernet Port</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div class="status-pill green view-mode">Working</div>
                                        <div class="status-toggle-group edit-mode">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="tab-health" class="tab-content" style="display: none;">
                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Computer Age</label>
                                    <div class="sub-detail-row">
                                        <span>Total:</span>
                                        <div class="detail-box small-box view-mode">7</div>
                                        <div class="edit-mode" style="display: flex; align-items: center; gap: 5px;">
                                            <input type="number" class="edit-input small-edit-box edit-mode" value="7">
                                        </div>
                                        <span style="font-size: 12px; color: #666;">Year/s</span>
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Disk Health (SMART Status)</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div class="status-pill green view-mode">Working</div>
                                        <div class="status-toggle-group edit-mode">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Number of Repairs</label>
                                    <div class="sub-detail-row">
                                        <span>Total:</span>
                                        <div class="detail-box small-box view-mode">8</div>
                                        <input type="number" class="edit-input small-edit-box edit-mode" value="8">
                                    </div>
                                </div>
                                <div class="detail-group">
                                    <label>Power Supply Health</label>
                                    <div class="status-row">
                                        <span>Status:</span>
                                        <div class="status-pill green view-mode">Working</div>
                                        <div class="status-toggle-group edit-mode">
                                            <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                            <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="activity-section view-mode">
                                <div class="activity-header">
                                    <h4>Recent Activity</h4>
                                    <a href="#" class="view-history-link">View Full Maintenance History</a>
                                </div>
                                <table class="activity-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Reported by</th>
                                            <th>Affected</th>
                                            <th>Remarks</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>11/19/2025</td>
                                            <td>Juan Dela Cruz</td>
                                            <td>
                                                <ul class="table-list">
                                                    <li>Monitor</li>
                                                    <li>System Unit</li>
                                                </ul>
                                            </td>
                                            <td>System Grounded, Monitor Dead Pixels</td>
                                            <td><span class="badge green">Resolved</span></td>
                                        </tr>
                                        <tr>
                                            <td>11/18/2025</td>
                                            <td>Juan Dela Cruz</td>
                                            <td>Monitor</td>
                                            <td>Monitor Dead Pixels</td>
                                            <td><span class="badge green">Resolved</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="tab-peripherals" class="tab-content" style="display: none;">

                            <div class="detail-grid-row">
                                <div class="detail-group">
                                    <label>Monitor</label>
                                    <div class="peripheral-info">
                                        <div class="p-row">
                                            <span>Property ID:</span>
                                            <span class="view-mode">1025482128</span>
                                            <input type="text" class="edit-mode edit-input" value="1025482128">
                                        </div>
                                        <div class="p-row">
                                            <span>Brand:</span>
                                            <span class="view-mode">Acer</span>
                                            <input type="text" class="edit-mode edit-input" value="Acer">
                                        </div>
                                        <div class="status-row">
                                            <span>Status:</span>
                                            <div class="status-pill green view-mode">Working</div>
                                            <div class="status-toggle-group edit-mode">
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
                                            <span>Property ID:</span>
                                            <span class="view-mode">1025482129</span>
                                            <input type="text" class="edit-mode edit-input" value="1025482129">
                                        </div>
                                        <div class="p-row">
                                            <span>Brand:</span>
                                            <span class="view-mode">Acer</span>
                                            <input type="text" class="edit-mode edit-input" value="Acer">
                                        </div>
                                        <div class="status-row">
                                            <span>Status:</span>
                                            <div class="status-pill green view-mode">Working</div>
                                            <div class="status-toggle-group edit-mode">
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
                                            <span>Property ID:</span>
                                            <span class="view-mode">1025482130</span>
                                            <input type="text" class="edit-mode edit-input" value="1025482130">
                                        </div>
                                        <div class="p-row">
                                            <span>Brand:</span>
                                            <span class="view-mode">Acer</span>
                                            <input type="text" class="edit-mode edit-input" value="Acer">
                                        </div>
                                        <div class="status-row">
                                            <span>Status:</span>
                                            <div class="status-pill green view-mode">Working</div>
                                            <div class="status-toggle-group edit-mode">
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
                                            <span>Property ID:</span>
                                            <span class="view-mode">1025482131</span>
                                            <input type="text" class="edit-mode edit-input" value="1025482131">
                                        </div>
                                        <div class="p-row">
                                            <span>Brand:</span>
                                            <span class="view-mode">Acer</span>
                                            <input type="text" class="edit-mode edit-input" value="Acer">
                                        </div>
                                        <div class="status-row">
                                            <span>Status:</span>
                                            <div class="status-pill green view-mode">Working</div>
                                            <div class="status-toggle-group edit-mode">
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
                            <button type="button" class="toggle-link" onclick="switchView('computer')">Computer Unit</button>
                            <button type="button" class="toggle-link active">Facility Assets</button>
                        </div>
                    </div>

                    <div class="section-header-row">
                        <h3>Room 104 - <strong>Facility Assets</strong></h3>
                        <button class="btn-green-add"><i class="fas fa-plus-circle"></i> Add</button>
                    </div>

                    <div class="search-filter-row">
                        <input type="text" class="search-input" placeholder="Type a device or search...">
                        <button class="filter-btn">Filter <i class="fas fa-filter"></i></button>
                    </div>

                    <div class="asset-list">
                        <div class="asset-item active">
                            <span class="item-name">FA-01</span>
                            <span class="badge green">Working</span>
                        </div>
                        <div class="asset-item">
                            <span class="item-name">FA-02</span>
                            <span class="badge green">Working</span>
                        </div>
                    </div>
                </div>

                <div class="panel white-panel right-panel">
                    <div class="section-header-row">
                        <h3>Television</h3>
                        <div class="action-buttons">
                            <button class="btn-edit"><i class="fas fa-pen"></i> Edit</button>
                            <button class="btn-condemn"><i class="fas fa-trash-alt"></i> Condemn</button>
                        </div>
                    </div>
                    <div class="detail-content">
                        <div class="detail-grid-row">
                            <div class="detail-group"><label>Property ID:</label>
                                <div class="detail-box">10284521</div>
                            </div>
                        </div>
                        <div class="detail-grid-row">
                            <div class="detail-group"><label>Brand:</label>
                                <div class="detail-box">Acer</div>
                            </div>
                        </div>
                        <div class="detail-grid-row">
                            <div class="detail-group"><label>Status:</label>
                                <div class="detail-box status-box-green">Working</div>
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
                    <button class="spec-tab" onclick="switchModalTab('m-external', this)">External I/O Ports</button>
                    <button class="spec-tab" onclick="switchModalTab('m-health', this)">Health & Maintenance Summary</button>
                    <button class="spec-tab" onclick="switchModalTab('m-peripherals', this)">Peripherals</button>
                </div>

                <div id="m-identity" class="modal-tab-content">
                    <div class="modal-form-grid">
                        <div class="form-group"><label>Property ID</label><input type="text" id="spec_property" class="modal-input" placeholder="Enter unique property number"></div>
                        <div class="form-group"><label>Processor (CPU)</label><input type="text" id="spec_cpu" class="modal-input" placeholder="e.g., Intel Core i5-12400"></div>
                        <div class="form-group"><label>Brand</label><input type="text" id="spec_brand" class="modal-input" placeholder="e.g., Dell, HP, Lenovo"></div>
                        <div class="form-group"><label>Operating System</label><input type="text" id="spec_os" class="modal-input" placeholder="e.g., Windows 11 Pro"></div>

                        <div class="form-group">
                            <label>Purchase Date</label>
                            <input type="date" id="purchase_date_input" class="modal-input" value="<?= date('Y-m-d'); ?>" onchange="calculateComputerAge()">
                        </div>

                        <div class="form-group"><label>Graphics Card (GPU)</label><input type="text" id="spec_gpu" class="modal-input" placeholder="e.g., Integrated Intel UHD"></div>

                        <div class="form-group">
                            <label>Room Number</label>
                            <input type="text" id="room_number_input" class="modal-input" value="<?= htmlspecialchars($current_room); ?>" readonly style="background: #f4f4f4; cursor: not-allowed;">
                        </div>

                        <div class="form-group"><label>RAM (Installed Memory)</label><input type="text" id="spec_ram" class="modal-input" placeholder="e.g., 16 GB"></div>

                        <div class="form-group">
                            <label>Unit Number</label>
                            <input type="text" id="smart_unit_no" class="modal-input" value="<?= getNextAvailableUnit($conn, $current_room) ?>" readonly style="background: #f4f4f4; font-weight: bold; color: #333;">
                        </div>

                        <div class="form-group"><label>Storage Type</label><input type="text" id="spec_storage" class="modal-input" placeholder="e.g., SSD (M.2 NVMe)"></div>
                        <div class="form-group"></div>
                        <div class="form-group"><label>Storage Capacity</label><input type="text" id="spec_capacity" class="modal-input" placeholder="e.g., 512 GB"></div>
                    </div>
                </div>

                <div id="m-external" class="modal-tab-content" style="display: none;">
                    <div class="modal-form-grid">
                        <div class="form-group">
                            <label>USB Ports</label>
                            <div class="status-toggle-group" id="usb_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                            <div class="sub-input-row">
                                <label>Available Ports:</label>
                                <input type="number" id="usb_ports_count" class="modal-input small-input" value="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Wi-fi Card</label>
                            <div class="status-toggle-group" id="wifi_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Microphone Jack</label>
                            <div class="status-toggle-group" id="mic_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>HDMI Port</label>
                            <div class="status-toggle-group" id="hdmi_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Headphone Jack</label>
                            <div class="status-toggle-group" id="headphone_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Display Port</label>
                            <div class="status-toggle-group" id="display_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>In-Line Jack</label>
                            <div class="status-toggle-group" id="inline_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Ethernet Port</label>
                            <div class="status-toggle-group" id="ethernet_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
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
                            <label>Disk Health (SMART Status)</label>
                            <div class="status-toggle-group" id="disk_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
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
                            <label>Power Supply</label>
                            <div class="status-toggle-group" id="power_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="m-peripherals" class="modal-tab-content" style="display: none;">
                    <div class="modal-form-grid">

                        <div class="form-group peripheral-group">
                            <label class="group-title">Monitor</label>
                            <div class="sub-field">
                                <label>Property ID:</label>
                                <input type="text" id="monitor_property_input" class="modal-input" placeholder="1025482128">
                            </div>
                            <div class="sub-field">
                                <label>Brand:</label>
                                <input type="text" id="monitor_brand_input" class="modal-input" placeholder="Acer">
                            </div>
                            <div class="status-toggle-group" id="monitor_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
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
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
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
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
                            </div>
                        </div>

                        <div class="form-group peripheral-group">
                            <label class="group-title">AVR (Automatic Voltage Regulator)</label>
                            <div class="sub-field">
                                <label>Brand:</label>
                                <input type="text" id="avr_brand_input" class="modal-input" placeholder="Acer">
                            </div>
                            <div class="status-toggle-group" id="avr_toggle">
                                <button type="button" class="status-btn active" data-type="working" onclick="toggleStatus(this)">Working</button>
                                <button type="button" class="status-btn" data-type="repair" onclick="toggleStatus(this)">For Repair</button>
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
                <h3>Condemned this Unit?</h3>
            </div>

            <div class="modal-body">
                <p class="condemn-warning">
                    Are you sure you want to condemn <strong>[PC-01]</strong>? This unit will be marked as permanently unusable. This action will be logged in the <strong>History Management</strong> section.
                </p>

                <div class="condemn-grid">
                    <div class="condemn-info">
                        <div class="form-group">
                            <label>Set Tag:</label>
                            <input type="text" class="modal-input readonly-input" value="PC-01" readonly>
                        </div>
                        <div class="form-group">
                            <label>Set ID:</label>
                            <input type="text" class="modal-input readonly-input" value="1025478521" readonly>
                        </div>
                    </div>

                    <div class="condemn-action">
                        <div class="form-group">
                            <label>Action Taken:</label>
                            <div class="checkbox-grid">
                                <label class="check-container"><input type="checkbox"> <span>Hardware Failure (Non-repairable)</span></label>
                                <label class="check-container"><input type="checkbox"> <span>Significant Physical Damage</span></label>
                                <label class="check-container"><input type="checkbox"> <span>System Obsolescence (End of Life)</span></label>
                                <label class="check-container"><input type="checkbox"> <span>Other (Please specify...)</span></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Remarks:</label>
                            <textarea class="modal-textarea" placeholder="Provide specific details for the audit log..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('condemnModal')">Cancel</button>
                <button class="btn-confirm-condemn"><i class="fas fa-trash-alt"></i> Condemn</button>
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
                        <input type="text" class="modal-input search-sm" placeholder="Search">
                        <div class="select-all-row">
                            <label class="check-container select-all-text"><input type="checkbox"> <span>Select All</span></label>
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
                                <tbody>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>PC-01</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>PC-02</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>PC-03</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>PC-04</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>PC-05</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>PC-06</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="transfer-list-card">
                        <h4>Facility Asset List</h4>
                        <input type="text" class="modal-input search-sm" placeholder="Search">
                        <div class="select-all-row">
                            <label class="check-container select-all-text"><input type="checkbox"> <span>Select All</span></label>
                        </div>
                        <div class="transfer-table-container">
                            <table class="transfer-table">
                                <thead>
                                    <tr>
                                        <th>Set Tag</th>
                                        <th>Property ID</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>FA-01</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>FA-02</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>FA-03</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>FA-04</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>FA-05</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                    <tr>
                                        <td><label class="check-container"><input type="checkbox"> <span>FA-06</span></label></td>
                                        <td>12548298</td>
                                        <td><span class="badge green">Working</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="transfer-form-col">
                        <div class="form-group">
                            <label>Source Room:</label>
                            <input type="text" class="modal-input readonly-input" value="Room 104" readonly>
                        </div>
                        <div class="form-group">
                            <label>Target Lab:</label>
                            <div class="select-wrapper">
                                <select class="modal-input custom-select">
                                    <option>Lab Room</option>
                                    <option>Room 105</option>
                                    <option>Room 106</option>
                                </select>
                                <i class="fas fa-filter select-icon"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Action Taken:</label>
                            <div class="checkbox-grid">
                                <label class="check-container"><input type="checkbox"> <span>Replacement for Broken Unit</span></label>
                                <label class="check-container"><input type="checkbox"> <span>Hardware Upgrade / Swap</span></label>
                                <label class="check-container"><input type="checkbox"> <span>Lab Capacity Expansion</span></label>
                                <label class="check-container"><input type="checkbox"> <span>Other (Please specify...)</span></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Remarks:</label>
                            <textarea class="modal-textarea" placeholder="Provide specific details for this status update..."></textarea>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('transferModal')">Cancel</button>
                <button class="btn-confirm-transfer"><i class="fas fa-check-circle"></i> Confirm</button>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/assets_management.js?v=<?php echo time(); ?>"></script>
</body>

</html>