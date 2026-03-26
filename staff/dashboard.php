<?php
session_start();
include '../includes/db.php';

// --- NEW: QUICK SEARCH AJAX ENDPOINT ---
if (isset($_GET['ajax_quick_search'])) {
    header('Content-Type: application/json');
    $q = trim($_GET['ajax_quick_search']);

    // Regex matches formats like PC-01(104) or FA-02(104)
    if (preg_match('/^(PC|FA)-(.+)\((.+)\)$/i', $q, $matches)) {
        $type = strtoupper($matches[1]); // PC or FA
        $tag = mysqli_real_escape_string($conn, trim($matches[2]));
        $room = mysqli_real_escape_string($conn, trim($matches[3]));

        // 1. Find the Lab ID based on the room number
        $lab_res = mysqli_query($conn, "SELECT lab_id FROM laboratories WHERE lab_room = '$room'");
        if ($lab_res && $lab_res->num_rows > 0) {
            $lab_id = $lab_res->fetch_assoc()['lab_id'];

            // 2. Find the Specific Unit/Asset ID
            if ($type === 'PC') {
                $item_res = mysqli_query($conn, "SELECT set_id FROM units WHERE set_tag = '$tag' AND lab_id = '$lab_id'");
                if ($item_res && $item_res->num_rows > 0) {
                    $item_id = $item_res->fetch_assoc()['set_id'];
                    // Change 'tab=units' if your actual tab parameter is named differently
                    echo json_encode(['success' => true, 'url' => "assets_management.php?lab_id=$lab_id&tab=units&id=$item_id"]);
                    exit;
                }
            } else if ($type === 'FA') {
                $item_res = mysqli_query($conn, "SELECT asset_id FROM assets WHERE asset_tag = '$tag' AND lab_id = '$lab_id'");
                if ($item_res && $item_res->num_rows > 0) {
                    $item_id = $item_res->fetch_assoc()['asset_id'];
                    // Change 'tab=assets' if your actual tab parameter is named differently
                    echo json_encode(['success' => true, 'url' => "assets_management.php?lab_id=$lab_id&tab=assets&id=$item_id"]);
                    exit;
                }
            }
        }
    }
    // If we reach here, the format was wrong or the item doesn't exist
    echo json_encode(['success' => false, 'message' => 'Item not found. Please ensure format is exactly PC-XX(Room) or FA-XX(Room).']);
    exit;
}
// ---------------------------------------

$workingCount = 0;
$repairCount = 0;
$condemnCount = 0;
$totalUnits = 0;

// 1. JOIN units with specs to get the specs_purchase date
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

        // A. Age Check (Global "For Condemn" calculation)
        if (!empty($purchase_date)) {
            $purchase_time = new DateTime($purchase_date);
            $age = $today->diff($purchase_time)->y;

            if ($age >= 5) {
                $condemnCount++;
            }
        }

        // B. Physical Status Check
        if ($status === 'working') {
            $workingCount++;
        } elseif ($status === 'for repair') {
            $repairCount++;
        }

        // C. Total Active Units
        $totalUnits++;
    }
}

// 2. Total Facility Assets (Table: assets)
$assetsQuery = "SELECT COUNT(*) as total FROM assets WHERE asset_status != 'Condemned'";
$assetsResult = $conn->query($assetsQuery);
$totalAssets = ($assetsResult) ? $assetsResult->fetch_assoc()['total'] : 0;

// 3. Total Computer Labs (Table: laboratories)
$labsQuery = "SELECT COUNT(*) as total FROM laboratories WHERE lab_status != 'Archived'";
$labsResult = $conn->query($labsQuery);
$totalLabs = ($labsResult) ? $labsResult->fetch_assoc()['total'] : 0;

// 4. Total Active Users (Table: users)
$usersQuery = "SELECT COUNT(*) as total FROM users WHERE user_status = 'Active'";
$usersResult = $conn->query($usersQuery);
$totalUsers = ($usersResult) ? $usersResult->fetch_assoc()['total'] : 0;

$totalLabsFormatted = str_pad($totalLabs, 2, '0', STR_PAD_LEFT);
$totalAssetsFormatted = str_pad($totalAssets, 2, '0', STR_PAD_LEFT);
$totalUsersFormatted = str_pad($totalUsers, 2, '0', STR_PAD_LEFT);

$roomsQuery = "SELECT lab_id, lab_room FROM laboratories ORDER BY lab_room ASC";
$roomsResult = $conn->query($roomsQuery);
$dropdownRooms = [];
if ($roomsResult) {
    while ($r = $roomsResult->fetch_assoc()) {
        $dropdownRooms[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LabCare</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <div class="page-header">
            <h1>Dashboard</h1>
            <p class="desktop-only-text">Real-time management of computer laboratory units and supplies, optimized for seamless admin and staff coordination across all devices.</p>
            <p class="mobile-only-text">Overview of daily tasks and laboratory status.</p>
        </div>

        <div class="mobile-dashboard-layout">
            <div class="mobile-stats-grid">
                <div class="m-stat-card dark-green">
                    <div class="m-card-content">
                        <h2>902</h2><span>Total Computer Units</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-desktop"></i></div>
                </div>
                <div class="m-stat-card light-green">
                    <div class="m-card-content">
                        <h2>452</h2><span>Total Working Units</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="m-stat-card gray">
                    <div class="m-card-content">
                        <h2>45</h2><span>Total Facility Assets</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-print"></i></div>
                </div>
                <div class="m-stat-card yellow">
                    <div class="m-card-content">
                        <h2>452</h2><span>Total For Repair Units</span>
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

            <div class="mobile-section">
                <h3 class="section-title">Computer Laboratory Overview</h3>
                <div class="mobile-room-list">
                    <div class="m-room-item">
                        <div class="m-room-header">
                            <h4>Com Lab 1</h4><span class="working-units">Working Units: <strong>25</strong></span>
                        </div>
                        <div class="m-room-details"><span class="room-badge">Room 104</span><a href="laboratory_management.php" class="room-link-btn"><i class="fas fa-chevron-right"></i></a></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-layout">
            <div class="dashboard-top-grid">

                <div class="panel transparent-panel status-col">
                    <h3 class="section-title">Unit Status Summary</h3>
                    <div class="status-stack">
                        <div class="status-card green">
                            <div class="icon-circle-bg"><i class="fas fa-check-circle"></i></div>
                            <div class="card-info-right">
                                <h2><?php echo $workingCount; ?></h2><span>Total Working Units</span>
                            </div>
                        </div>
                        <div class="status-card yellow">
                            <div class="icon-circle-bg"><i class="fas fa-wrench"></i></div>
                            <div class="card-info-right">
                                <h2><?php echo $repairCount; ?></h2><span>Total For Repair Units</span>
                            </div>
                        </div>
                        <div class="status-card red">
                            <div class="icon-circle-bg"><i class="fas fa-trash-alt"></i></div>
                            <div class="card-info-right">
                                <h2><?php echo $condemnCount; ?></h2><span>Total For Condemn Units</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel white-panel search-panel" style="display: flex; flex-direction: column;">
                    <h3 class="section-title">Device Quick Search</h3>

                    <div class="search-container" style="margin-top: 35px; display: flex; flex-direction: column; flex: 1; justify-content: flex-start;">
                        <label for="quickSearchInput" style="display: block; font-size: 13px; font-weight: 700; color: #000; margin-bottom: 8px;">Enter Property ID:</label>

                        <input type="text" id="quickSearchInput" placeholder="e.g., PC-01(104) or FA-01(104)"
                            style="width: 100%; padding: 12px 15px; border-radius: 8px; border: 1px solid #eaeaea; background-color: #f4f4f4; margin-bottom: 20px; box-sizing: border-box; font-size: 14px; color: #333; outline: none;">

                        <button onclick="performQuickSearch()"
                            style="width: 100%; padding: 14px; border-radius: 8px; background-color: #4caf50; color: white; border: none; font-weight: 600; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background 0.2s;">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>

                <div class="panel white-panel supply-panel">
                    <h3 class="section-title">Supply Inventory Summary</h3>
                    <div class="supply-list-container">
                        <?php
                        $supplyQuery = "SELECT supply_name, supply_status 
                FROM supply 
                WHERE supply_avail = 'Current'
                ORDER BY CASE WHEN supply_status = 'Out of Stock' THEN 1 ELSE 2 END, 
                         supply_name ASC";

                        $supplyResult = $conn->query($supplyQuery);

                        if ($supplyResult && $supplyResult->num_rows > 0) {
                            while ($supply = $supplyResult->fetch_assoc()) {
                                $name = htmlspecialchars($supply['supply_name']);
                                $status = htmlspecialchars($supply['supply_status']);

                                if ($status === 'In Stock') {
                                    $itemClass = "in-stock";
                                    $statusSpan = "<span class='green-text'>In Stock</span>";
                                } else {
                                    $itemClass = "out-stock";
                                    $statusSpan = "<span>Out of Stock</span>";
                                }

                                echo "
                            <div class='supply-item {$itemClass}'>
                                <div class='supply-accent'></div>
                                <div class='supply-info'>
                                    <h4>{$name}</h4>
                                    {$statusSpan}
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

            <div class="dashboard-bottom-grid">
                <div class="panel white-panel table-panel">
                    <div class="panel-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 class="section-title" style="margin: 0; font-size: 16px;">Recent Report</h3>
                    </div>

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
                        ORDER BY report_date DESC LIMIT 6
                    ";

                    $reportsResult = $conn->query($recentReportsQuery);
                    $reportsData = [];
                    if ($reportsResult && $reportsResult->num_rows > 0) {
                        while ($row = $reportsResult->fetch_assoc()) {
                            $reportsData[] = $row;
                        }
                    }
                    ?>

                    <div class="recent-reports-feed" style="border: 1px solid #eaeaea; border-radius: 8px; background: #fafafa; max-height: 350px; overflow-y: auto;">
                        <?php if (!empty($reportsData)): ?>
                            <?php
                            $totalReports = count($reportsData);
                            foreach ($reportsData as $index => $report):
                                $formattedDate = date('M d, Y', strtotime($report['report_date']));
                                $reporterName = htmlspecialchars($report['reporter'] ?: 'System');
                                $badgeClass = ($report['status'] === 'For Repair') ? 'yellow' : (($report['status'] === 'Condemned') ? 'red' : 'green');
                                $borderStyle = ($index < $totalReports - 1) ? 'border-bottom: 1px solid #eaeaea;' : '';
                            ?>
                                <div style="padding: 15px 20px; background-color: #fff; <?php echo $borderStyle; ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                        <div style="font-size: 13px; color: #555;">
                                            <strong style="color: #1b4d3e; font-size: 14px;">
                                                <i class="fas fa-user-circle"></i> <?php echo $reporterName; ?>
                                            </strong>
                                            <span style="margin-left: 8px; color: #888;">
                                                <i class="far fa-clock"></i> <?php echo $formattedDate; ?>
                                            </span>
                                        </div>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($report['status']); ?></span>
                                    </div>

                                    <div style="font-size: 13px; color: #333; display: flex; gap: 20px;">
                                        <div>
                                            <strong>Unit/Asset:</strong>
                                            <span style="color: #d32f2f; font-weight: 600; margin-left: 4px;">
                                                <?php echo htmlspecialchars($report['tag']); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong>Location:</strong>
                                            <span style="color: #555; margin-left: 4px;">
                                                Room <?php echo htmlspecialchars($report['room']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 30px; color: #888;">
                                <i class="fas fa-clipboard-check" style="font-size: 24px; color: #ddd; margin-bottom: 10px; display: block;"></i>
                                No recent repair reports found.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="panel white-panel">
                    <h3 class="section-title">Computer Lab Overview</h3>

                    <div class="chart-controls">
                        <select class="room-select" id="dashboardRoomSelect" onchange="updateDashboardLabStats()">
                            <?php foreach ($dropdownRooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['lab_id']); ?>">
                                    Room <?php echo htmlspecialchars($room['lab_room']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="chart-container">
                        <div class="donut-chart-large" id="dashboardDonutChart"></div>
                    </div>

                    <div class="legend-row">
                        <span class="legend-pill green"><span class="dot"></span> Working <span id="dashLegendWorking">0</span></span>
                        <span class="legend-pill yellow"><span class="dot"></span> For Repair <span id="dashLegendRepair">0</span></span>
                        <span class="legend-pill red"><span class="dot"></span> For Condemn <span id="dashLegendCondemn">0</span></span>
                    </div>

                    <div class="view-details-link">
                        <a href="#" id="viewLabDetailsLink">View Details <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>

    <script>
        async function updateDashboardLabStats() {
            const roomSelect = document.getElementById('dashboardRoomSelect');
            if (!roomSelect) return;

            const labId = roomSelect.value;
            const detailsLink = document.getElementById('viewLabDetailsLink');
            if (detailsLink) {
                detailsLink.href = `assets_management.php?lab_id=${encodeURIComponent(labId)}`;
            }

            try {
                const response = await fetch(`../includes/get_room_stats.php?lab_id=${encodeURIComponent(labId)}`);
                const rawText = await response.text();
                const data = JSON.parse(rawText);

                document.getElementById('dashLegendWorking').innerText = data.working;
                document.getElementById('dashLegendRepair').innerText = data.repair;
                document.getElementById('dashLegendCondemn').innerText = data.condemn;

                updateDonutChartVisual(data.working, data.repair, data.condemn);

            } catch (error) {
                console.error('Error fetching room stats for dashboard:', error);
            }
        }

        function updateDonutChartVisual(working, repair, condemn) {
            const chart = document.getElementById('dashboardDonutChart');
            if (!chart) return;

            const w = parseInt(working) || 0;
            const r = parseInt(repair) || 0;
            const total = w + r;

            if (total === 0) {
                chart.style.background = `conic-gradient(#e0e0e0 0% 100%)`;
                return;
            }

            const workPct = (w / total) * 100;
            const colorGreen = '#4caf50';
            const colorYellow = '#ffc107';

            chart.style.background = `conic-gradient(
        ${colorGreen} 0% ${workPct}%, 
        ${colorYellow} ${workPct}% 100%
    )`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('dashboardRoomSelect')) {
                updateDashboardLabStats();
            }
        });

        // --- FIXED: QUICK SEARCH FUNCTIONALITY USING AJAX ---
        async function performQuickSearch() {
            const searchInput = document.getElementById('quickSearchInput');
            const query = searchInput.value.trim();

            if (query) {
                try {
                    // Send query to the PHP AJAX handler at the top of this file
                    const response = await fetch(`dashboard.php?ajax_quick_search=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (data.success) {
                        // Redirect to the exact unit/asset URL returned by PHP
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
                alert("Please enter a Property ID first.");
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