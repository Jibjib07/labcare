<?php
session_start();
require '../includes/staff_auth.php';
include '../includes/db.php';

// --- QUICK SEARCH AJAX ENDPOINT ---
if (isset($_GET['ajax_quick_search'])) {
    header('Content-Type: application/json');
    $q = trim($_GET['ajax_quick_search']);
    $q_safe = mysqli_real_escape_string($conn, $q);

    // Scenario 1: Search by exact Set ID or Asset ID (e.g., SET_0001_2026 or 45)
    $unit_res = mysqli_query($conn, "SELECT lab_id, set_id FROM units WHERE set_id = '$q_safe'");
    if ($unit_res && $unit_res->num_rows > 0) {
        $row = $unit_res->fetch_assoc();
        $lab_id = $row['lab_id'];
        $item_id = $row['set_id'];
        echo json_encode(['success' => true, 'url' => "assets_management.php?lab_id=$lab_id&tab=units&id=$item_id"]);
        exit;
    }

    $asset_res = mysqli_query($conn, "SELECT lab_id, asset_id FROM assets WHERE asset_id = '$q_safe'");
    if ($asset_res && $asset_res->num_rows > 0) {
        $row = $asset_res->fetch_assoc();
        $lab_id = $row['lab_id'];
        $item_id = $row['asset_id'];
        echo json_encode(['success' => true, 'url' => "assets_management.php?lab_id=$lab_id&tab=assets&id=$item_id"]);
        exit;
    }

    // Scenario 2: Search by Tag and Room (e.g., PC-01(104) or FA-01(Library))
    if (preg_match('/^(PC|FA)\s*-\s*(.+?)\s*\((.+?)\)$/i', $q, $matches)) {
        $type = strtoupper($matches[1]); // PC or FA
        $tag = mysqli_real_escape_string($conn, trim($matches[2]));
        $room_input = mysqli_real_escape_string($conn, trim($matches[3]));

        $lab_query = "SELECT lab_id FROM laboratories WHERE lab_room = '$room_input' OR lab_room = 'Room $room_input'";
        $lab_res = mysqli_query($conn, $lab_query);
        
        if ($lab_res && $lab_res->num_rows > 0) {
            $lab_id = $lab_res->fetch_assoc()['lab_id'];

            if ($type === 'PC') {
                $item_res = mysqli_query($conn, "SELECT set_id FROM units WHERE set_tag = '$tag' AND lab_id = '$lab_id'");
                if ($item_res && $item_res->num_rows > 0) {
                    $item_id = $item_res->fetch_assoc()['set_id'];
                    echo json_encode(['success' => true, 'url' => "assets_management.php?lab_id=$lab_id&tab=units&id=$item_id"]);
                    exit;
                }
            } else if ($type === 'FA') {
                $item_res = mysqli_query($conn, "SELECT asset_id FROM assets WHERE asset_tag = '$tag' AND lab_id = '$lab_id'");
                if ($item_res && $item_res->num_rows > 0) {
                    $item_id = $item_res->fetch_assoc()['asset_id'];
                    echo json_encode(['success' => true, 'url' => "assets_management.php?lab_id=$lab_id&tab=assets&id=$item_id"]);
                    exit;
                }
            }
            
            echo json_encode(['success' => false, 'message' => "The room was found, but the device tag '$type-$tag' does not exist in that room."]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => "Room '$room_input' not found."]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Format not recognized. Use exact Set ID (e.g. SET_0001_2026) or Tag (e.g. PC-01(Library)).']);
    exit;
}
// ---------------------------------------

$workingCount = 0;
$repairCount = 0;
$condemnCount = 0;
$totalUnits = 0;

// JOIN units with specs to get the specs_purchase date
$unitsQuery = "
    SELECT u.set_status, s.specs_purchase 
    FROM units u
    LEFT JOIN specs s ON u.set_id = s.set_id
    WHERE u.set_status != 'Condemned'
";
$unitsResult = $conn->query($unitsQuery);

if ($unitsResult && $unitsResult->num_rows > 0) {
    $today = new DateTime();

    while ($row = $unitsResult->fetch_assoc()) {
        $status = strtolower(trim($row['set_status']));
        $purchase_date = $row['specs_purchase'];

        if (!empty($purchase_date)) {
            $purchase_time = new DateTime($purchase_date);
            $age = $today->diff($purchase_time)->y;
            if ($age >= 5) { $condemnCount++; }
        }

        if ($status === 'working') {
            $workingCount++;
        } elseif ($status === 'for repair') {
            $repairCount++;
        }

        $totalUnits++;
    }
}

// Total Facility Assets
$assetsQuery = "SELECT COUNT(*) as total FROM assets WHERE asset_status != 'Condemned'";
$assetsResult = $conn->query($assetsQuery);
$totalAssets = ($assetsResult) ? $assetsResult->fetch_assoc()['total'] : 0;

// Total Computer Labs
$labsQuery = "SELECT COUNT(*) as total FROM laboratories WHERE lab_status != 'Archived'";
$labsResult = $conn->query($labsQuery);
$totalLabs = ($labsResult) ? $labsResult->fetch_assoc()['total'] : 0;

// Total Active Users
$usersQuery = "SELECT COUNT(*) as total FROM users WHERE user_status = 'Active'";
$usersResult = $conn->query($usersQuery);
$totalUsers = ($usersResult) ? $usersResult->fetch_assoc()['total'] : 0;

$totalLabsFormatted = str_pad($totalLabs, 2, '0', STR_PAD_LEFT);
$totalAssetsFormatted = str_pad($totalAssets, 2, '0', STR_PAD_LEFT);
$totalUsersFormatted = str_pad($totalUsers, 2, '0', STR_PAD_LEFT);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - LabCare</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">

    <style>
        /* TOP GRID: 3 Columns */
        .dashboard-top-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        /* BOTTOM GRID: Stretched Reports (2fr) & Supply (1fr) */
        .dashboard-bottom-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .panel.white-panel {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0,0,0,0.03);
            padding: 24px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .panel.white-panel:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .section-title {
            font-size: 1.05rem;
            color: #1b4d3e;
            font-weight: 700;
            margin-bottom: 20px;
            border-bottom: 2px solid #f4f4f4;
            padding-bottom: 10px;
        }

        .report-item-card {
            padding: 14px 16px;
            background-color: #fafafa;
            border: 1px solid #eaeaea;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: background 0.2s;
        }
        
        .report-item-card:hover {
            background-color: #f0f7f4;
            border-color: #c8e6c9;
        }

        .search-input-modern {
            width: 100%;
            padding: 14px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            margin-bottom: 15px;
            box-sizing: border-box;
            font-size: 14px;
            color: #333;
            outline: none;
            transition: border-color 0.2s, background-color 0.2s;
        }
        .search-input-modern:focus { border-color: #4caf50; background-color: #fff; }
        
        .btn-modern-green {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            background-color: #4caf50;
            color: white;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-modern-green:hover { background-color: #43a047; }

        @media (max-width: 1024px) {
            .dashboard-top-grid, .dashboard-bottom-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <div class="page-header">
            <h1>Staff Dashboard</h1>
            <p class="desktop-only-text">Real-time management of computer laboratory units and supplies, optimized for seamless coordination.</p>
        </div>

        <div class="mobile-dashboard-layout">
            <div class="mobile-stats-grid">
                <div class="m-stat-card dark-green">
                    <div class="m-card-content">
                        <h2><?php echo $totalUnits; ?></h2><span>Total Computer Sets</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-desktop"></i></div>
                </div>
                <div class="m-stat-card light-green">
                    <div class="m-card-content">
                        <h2><?php echo $workingCount; ?></h2><span>Total Working Sets</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="m-stat-card gray">
                    <div class="m-card-content">
                        <h2><?php echo $totalAssets; ?></h2><span>Total Facility Assets</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-print"></i></div>
                </div>
                <div class="m-stat-card yellow">
                    <div class="m-card-content">
                        <h2><?php echo $repairCount; ?></h2><span>Total For Repair Sets</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-wrench"></i></div>
                </div>
            </div>

            <div class="mobile-section">
                <h3 class="section-title">Quick Access</h3>
                <div class="quick-access-grid">
                    <a href="#" class="qa-btn">
                        <div class="qa-icon"><i class="fas fa-qrcode"></i></div><span>Scan Room</span>
                    </a>
                    <a href="report_generation.php" class="qa-btn">
                        <div class="qa-icon"><i class="fas fa-file-alt"></i></div><span>Make Report</span>
                    </a>
                    <a href="troubleshooting.php" class="qa-btn">
                        <div class="qa-icon"><i class="fas fa-book"></i></div><span>Guide</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="dashboard-layout">
            
            <div class="dashboard-top-grid">

                <div class="panel transparent-panel status-col" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="status-stack" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between; gap: 15px;">
                        <div class="status-card green" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.15); flex: 1;">
                            <div class="icon-circle-bg"><i class="fas fa-check-circle"></i></div>
                            <div class="card-info-right"><h2><?php echo $workingCount; ?></h2><span>Total Working</span></div>
                        </div>
                        <div class="status-card yellow" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.15); flex: 1;">
                            <div class="icon-circle-bg"><i class="fas fa-wrench"></i></div>
                            <div class="card-info-right"><h2><?php echo $repairCount; ?></h2><span>Total For Repair</span></div>
                        </div>
                        <div class="status-card red" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(244, 67, 54, 0.15); flex: 1;">
                            <div class="icon-circle-bg"><i class="fas fa-trash-alt"></i></div>
                            <div class="card-info-right"><h2><?php echo $condemnCount; ?></h2><span>Total For Condemn</span></div>
                        </div>
                    </div>
                </div>

                <div class="panel white-panel search-panel">
                    <h3 class="section-title">Device Quick Search</h3>
                    <div class="search-container" style="display: flex; flex-direction: column; flex: 1; justify-content: center;">
                        <label for="quickSearchInput" style="display: block; font-size: 13px; font-weight: 700; color: #555; margin-bottom: 10px;">Search Computer Set or Asset:</label>
                        <input type="text" id="quickSearchInput" class="search-input-modern" placeholder="e.g., SET_0001_2026 or PC-01(104)">
                        <button onclick="performQuickSearch()" class="btn-modern-green"><i class="fas fa-search"></i> Search Device</button>
                    </div>
                </div>

                <div class="panel white-panel">
                    <h3 class="section-title">Resource Summary</h3>
                    <div class="resource-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; height: 100%;">
                        <div class="res-item" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; background: #f9f9f9; border-radius: 8px; padding: 10px;">
                            <i class="fas fa-desktop" style="color: #1b4d3e; font-size: 20px; margin-bottom: 5px;"></i>
                            <h2 style="margin:0; font-size: 1.5rem;"><?php echo $totalUnits; ?></h2><span style="font-size:11px;">Total Units</span>
                        </div>
                        <div class="res-item" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; background: #f9f9f9; border-radius: 8px; padding: 10px;">
                            <i class="fas fa-print" style="color: #2196f3; font-size: 20px; margin-bottom: 5px;"></i>
                            <h2 style="margin:0; font-size: 1.5rem;"><?php echo $totalAssetsFormatted; ?></h2><span style="font-size:11px;">Facility Assets</span>
                        </div>
                        <div class="res-item" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; background: #f9f9f9; border-radius: 8px; padding: 10px;">
                            <i class="fas fa-warehouse" style="color: #ff9800; font-size: 20px; margin-bottom: 5px;"></i>
                            <h2 style="margin:0; font-size: 1.5rem;"><?php echo $totalLabsFormatted; ?></h2><span style="font-size:11px;">Total Rooms</span>
                        </div>
                        <div class="res-item" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; background: #f9f9f9; border-radius: 8px; padding: 10px;">
                            <i class="fas fa-users" style="color: #9c27b0; font-size: 20px; margin-bottom: 5px;"></i>
                            <h2 style="margin:0; font-size: 1.5rem;"><?php echo $totalUsersFormatted; ?></h2><span style="font-size:11px;">Active Users</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="dashboard-bottom-grid">
                
                <div class="panel white-panel table-panel">
                    <div class="panel-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 class="section-title" style="margin: 0; padding: 0; border: none;">Recent Reports</h3>
                    </div>
                    <div class="recent-reports-feed" style="max-height: 350px; overflow-y: auto; padding-right: 5px;">
                        <?php
                        $recentReportsQuery = "
                            (SELECT uh.report_actor AS reporter, uh.report_date AS report_date, l.lab_room AS room, CONCAT('PC-', u.set_tag) AS tag, u.set_status AS status 
                            FROM units u JOIN laboratories l ON u.lab_id = l.lab_id
                            JOIN (SELECT set_id, MAX(report_date) as max_date FROM unit_history GROUP BY set_id) latest ON u.set_id = latest.set_id
                            JOIN unit_history uh ON latest.set_id = uh.set_id AND latest.max_date = uh.report_date WHERE u.set_status = 'For Repair')
                            UNION ALL
                            (SELECT ah.report_actor AS reporter, ah.report_date AS report_date, l.lab_room AS room, CONCAT('FA-', a.asset_tag) AS tag, a.asset_status AS status 
                            FROM assets a JOIN laboratories l ON a.lab_id = l.lab_id
                            JOIN (SELECT asset_id, MAX(report_date) as max_date FROM asset_history GROUP BY asset_id) latest ON a.asset_id = latest.asset_id
                            JOIN asset_history ah ON latest.asset_id = ah.asset_id AND latest.max_date = ah.report_date WHERE a.asset_status = 'For Repair')
                            ORDER BY report_date DESC LIMIT 5";
                        $reportsResult = $conn->query($recentReportsQuery);
                        if ($reportsResult && $reportsResult->num_rows > 0):
                            while ($report = $reportsResult->fetch_assoc()):
                                $badgeClass = ($report['status'] === 'For Repair') ? 'yellow' : (($report['status'] === 'Condemned') ? 'red' : 'green');
                                $formattedDate = date('M d, Y - g:i A', strtotime($report['report_date']));
                            ?>
                                <div class="report-item-card">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div style="font-size: 13px; color: #444;">
                                            <strong style="color: #1b4d3e; font-size: 14px;"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($report['reporter'] ?: 'System'); ?></strong>
                                            <div style="margin-top: 4px; color: #777; font-size: 12px;"><i class="far fa-clock" style="color:#4caf50;"></i> <?php echo $formattedDate; ?></div>
                                        </div>
                                        <span class="badge <?php echo $badgeClass; ?>" style="padding: 4px 8px; border-radius: 20px; font-weight: bold;"><?php echo htmlspecialchars($report['status']); ?></span>
                                    </div>
                                    <div style="font-size: 13px; margin-top: 8px; padding-top: 8px; border-top: 1px dashed #ddd;">
                                        <span style="color:#d32f2f; font-weight:700;"><i class="fas fa-microchip"></i> <?php echo $report['tag']; ?></span> 
                                        <span style="margin: 0 8px; color: #ccc;">|</span> 
                                        <span style="color:#555; font-weight:600;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($report['room']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; else: echo "<div style='text-align: center; padding: 40px; color: #aaa;'><i class='fas fa-clipboard-check' style='font-size: 30px; margin-bottom: 10px;'></i><br>No recent reports found.</div>"; endif; ?>
                    </div>
                </div>

                <div class="panel white-panel supply-panel">
                    <h3 class="section-title">Supply Inventory</h3>
                    <div class="supply-list-container" style="flex: 1; display: flex; flex-direction: column; justify-content: flex-start; gap: 10px;">
                        <?php
                        $supplyQuery = "SELECT supply_name, supply_status FROM supply WHERE supply_avail = 'Current' ORDER BY CASE WHEN supply_status = 'Out of Stock' THEN 1 ELSE 2 END, supply_name ASC LIMIT 5";
                        $supplyResult = $conn->query($supplyQuery);
                        if ($supplyResult && $supplyResult->num_rows > 0) {
                            while ($supply = $supplyResult->fetch_assoc()) {
                                $sp = ($supply['supply_status'] === 'In Stock') ? "<span class='green-text' style='font-weight:600;'><i class='fas fa-check-circle'></i> In Stock</span>" : "<span style='color:#f44336; font-weight:600;'><i class='fas fa-times-circle'></i> Out of Stock</span>";
                                $bgClass = ($supply['supply_status'] === 'In Stock') ? "background-color: #ffffff; border: 1px solid #eaeaea;" : "background-color: #ffebee; border: 1px solid #ffebee;";
                                $accentColor = ($supply['supply_status'] === 'In Stock') ? "#4caf50" : "#f44336";

                                echo "
                                <div style='display: flex; align-items: center; {$bgClass} border-radius: 8px; padding: 12px 15px; position: relative;'>
                                    <div style='position: absolute; left: 15px; top: 12px; bottom: 12px; width: 4px; border-radius: 2px; background-color: {$accentColor};'></div>
                                    <div style='width: 100%; text-align: center; padding-left: 10px;'>
                                        <div style='margin:0; font-size:13px; font-weight: 700; color: #333;'>".htmlspecialchars($supply['supply_name'])."</div>
                                        <div style='font-size: 11px; margin-top: 3px;'>$sp</div>
                                    </div>
                                </div>";
                            }
                        } else {
                            echo "<div style='padding: 15px; color: #888; text-align: center;'>No supply data available.</div>";
                        }
                        ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script>
        async function performQuickSearch() {
            const searchInput = document.getElementById('quickSearchInput');
            const query = searchInput.value.trim();

            if (query) {
                try {
                    const response = await fetch(`dashboard.php?ajax_quick_search=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (data.success) {
                        window.location.href = data.url;
                    } else {
                        alert(data.message);
                        searchInput.focus();
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    alert("An error occurred while searching. Please try again.");
                }
            } else {
                alert("Please enter a Search Term first.");
                searchInput.focus();
            }
        }

        document.getElementById('quickSearchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performQuickSearch();
            }
        });
    </script>
</body>
</html>