<?php
include '../includes/admin_auth.php';
include '../includes/db.php';
$workingCount = 0;
$repairCount = 0;
$condemnCount = 0;

// 2. Query to count how many units exist for each status
// Make sure 'units' is your correct table name and 'set_status' is your column name
$statusQuery = "SELECT set_status, COUNT(*) as total FROM units GROUP BY set_status";
$statusResult = $conn->query($statusQuery);

if ($statusResult && $statusResult->num_rows > 0) {
    while ($row = $statusResult->fetch_assoc()) {
        $status = $row['set_status'];
        $total = $row['total'];

        // 3. Assign the count to the correct variable
        if ($status === 'Working') {
            $workingCount = $total;
        } elseif ($status === 'For Repair') {
            $repairCount = $total;
        } elseif ($status === 'For Condemn') {
            $condemnCount = $total;
        }
    }
}

$unitsQuery = "SELECT COUNT(*) as total FROM units WHERE set_status != 'Condemned'";
$unitsResult = $conn->query($unitsQuery);
$totalUnits = ($unitsResult) ? $unitsResult->fetch_assoc()['total'] : 0;

// 2. Total Facility Assets (Table: assets)
$assetsQuery = "SELECT COUNT(*) as total FROM assets WHERE asset_status != 'Condemned'";
$assetsResult = $conn->query($assetsQuery);
$totalAssets = ($assetsResult) ? $assetsResult->fetch_assoc()['total'] : 0;

// 3. Total Computer Labs (Table: laboratories)
$labsQuery = "SELECT COUNT(*) as total FROM laboratories WHERE lab_status != 'Archived'";
$labsResult = $conn->query($labsQuery);
$totalLabs = ($labsResult) ? $labsResult->fetch_assoc()['total'] : 0;

// 4. Total Active Users (Table: users)
// Assuming you have a 'status' column. If not, just use: SELECT COUNT(*) as total FROM users
$usersQuery = "SELECT COUNT(*) as total FROM users WHERE user_status = 'Active'";
$usersResult = $conn->query($usersQuery);
$totalUsers = ($usersResult) ? $usersResult->fetch_assoc()['total'] : 0;

// Optional: Format numbers to have a leading zero if they are single digits (e.g., "8" becomes "08")
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

        <div class="desktop-dashboard-layout">
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

                <div class="panel white-panel">
                    <h3 class="section-title">Resource Summary</h3>
                    <hr class="divider">
                    <div class="resource-grid">
                        <div class="res-item">
                            <i class="fas fa-desktop"></i>
                            <h2><?php echo $totalUnits; ?></h2><span>Total Computer Units</span>
                        </div>
                        <div class="res-item">
                            <i class="fas fa-print"></i>
                            <h2><?php echo $totalAssetsFormatted; ?></h2><span>Total Facility Assets</span>
                        </div>
                        <div class="res-item">
                            <i class="fas fa-warehouse"></i>
                            <h2><?php echo $totalLabsFormatted; ?></h2><span>Total Computer Labs</span>
                        </div>
                        <div class="res-item">
                            <i class="fas fa-users"></i>
                            <h2><?php echo $totalUsersFormatted; ?></h2><span>Total Active Users</span>
                        </div>
                    </div>
                </div>

                <div class="panel white-panel supply-panel">
                    <h3 class="section-title">Supply Inventory Summary</h3>
                    <div class="supply-list-container">
                        <?php
                        // Query the supplies table.
                        // The ORDER BY CASE statement forces "Out of Stock" items to appear at the top of the list
                        $supplyQuery = "SELECT supply_name, supply_status FROM supply 
                        ORDER BY CASE WHEN supply_status = 'Out of Stock' THEN 1 ELSE 2 END, supply_name ASC";

                        $supplyResult = $conn->query($supplyQuery);

                        if ($supplyResult && $supplyResult->num_rows > 0) {
                            while ($supply = $supplyResult->fetch_assoc()) {
                                $name = htmlspecialchars($supply['supply_name']);
                                $status = htmlspecialchars($supply['supply_status']);

                                // Determine CSS classes based on the text status
                                if ($status === 'In Stock') {
                                    $itemClass = "in-stock";
                                    $statusSpan = "<span class='green-text'>In Stock</span>";
                                } else {
                                    $itemClass = "out-stock";
                                    // Out of stock doesn't get the green-text class
                                    $statusSpan = "<span>Out of Stock</span>";
                                }

                                // Output the dynamic HTML block
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
                            // Fallback if the table is empty
                            echo "<div style='padding: 15px; color: #888; text-align: center;'>No supply data available.</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-bottom-grid">
                <div class="panel white-panel table-panel">
                    <div class="panel-header-row">
                        <h3 class="section-title">Recent Report</h3>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Report by</th>
                                <th>Report Date</th>
                                <th>Room Number</th>
                                <th>Tag</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recentReportsQuery = "
        (SELECT 
            uh.report_actor AS reporter, 
            uh.report_date AS report_date, 
            l.lab_room AS room, 
            CONCAT('PC-', u.set_tag) AS tag, 
            u.set_status AS status 
        FROM units u
        JOIN laboratories l ON u.lab_id = l.lab_id
        JOIN (
            SELECT set_id, MAX(report_date) as max_date 
            FROM unit_history 
            GROUP BY set_id
        ) latest ON u.set_id = latest.set_id
        JOIN unit_history uh ON latest.set_id = uh.set_id AND latest.max_date = uh.report_date
        WHERE u.set_status = 'For Repair')

        UNION ALL

        (SELECT 
            ah.report_actor AS reporter, 
            ah.report_date AS report_date, 
            l.lab_room AS room, 
            CONCAT('FA-', a.asset_tag) AS tag, 
            a.asset_status AS status 
        FROM assets a
        JOIN laboratories l ON a.lab_id = l.lab_id
        JOIN (
            SELECT asset_id, MAX(report_date) as max_date 
            FROM asset_history 
            GROUP BY asset_id
        ) latest ON a.asset_id = latest.asset_id
        JOIN asset_history ah ON latest.asset_id = ah.asset_id AND latest.max_date = ah.report_date
        WHERE a.asset_status = 'For Repair')

        ORDER BY report_date DESC 
        LIMIT 6
    ";

                            $reportsResult = $conn->query($recentReportsQuery);

                            if ($reportsResult && $reportsResult->num_rows > 0):
                                while ($report = $reportsResult->fetch_assoc()):
                                    // Format the date to MM/DD/YYYY
                                    $formattedDate = date('m/d/Y', strtotime($report['report_date']));
                                    $reporterName = htmlspecialchars($report['reporter'] ?: 'Unknown');
                                    $roomNum = htmlspecialchars($report['room']);
                                    $tag = htmlspecialchars($report['tag']);
                                    $status = htmlspecialchars($report['status']);

                                    // Dynamic badge color (just in case you add other statuses to this table later)
                                    $badgeClass = ($status === 'For Repair') ? 'yellow' : (($status === 'Condemned') ? 'red' : 'green');
                            ?>
                                    <tr>
                                        <td><?php echo $reporterName; ?></td>
                                        <td><?php echo $formattedDate; ?></td>
                                        <td><?php echo $roomNum; ?></td>
                                        <td><?php echo $tag; ?></td>
                                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $status; ?></span></td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 20px; color: #888;">No recent repair reports found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

            // --- NEW: UPDATE THE LINK URL ---
            const detailsLink = document.getElementById('viewLabDetailsLink');
            if (detailsLink) {
                detailsLink.href = `assets_management.php?lab_id=${encodeURIComponent(labId)}`;
            }

            try {
                // CHANGED: Send lab_id to the PHP script instead of room string
                const response = await fetch(`../includes/get_room_stats.php?lab_id=${encodeURIComponent(labId)}`);

                const rawText = await response.text();
                const data = JSON.parse(rawText);

                // Update the Legend Numbers
                document.getElementById('dashLegendWorking').innerText = data.working;
                document.getElementById('dashLegendRepair').innerText = data.repair;
                document.getElementById('dashLegendCondemn').innerText = data.condemn;

                // Pass numbers to update the CSS Donut Chart
                updateDonutChartVisual(data.working, data.repair, data.condemn);

            } catch (error) {
                console.error('Error fetching room stats for dashboard:', error);
            }
        }

        function updateDonutChartVisual(working, repair, condemn) {
            const chart = document.getElementById('dashboardDonutChart');
            if (!chart) return;

            // Convert strings to integers just to be safe
            const w = parseInt(working) || 0;
            const r = parseInt(repair) || 0;
            const c = parseInt(condemn) || 0;

            const total = w + r + c;

            if (total === 0) {
                // If room is completely empty, make the chart solid gray
                chart.style.background = `conic-gradient(#e0e0e0 0% 100%)`;
                return;
            }

            // Calculate percentages
            const workPct = (w / total) * 100;
            const repPct = (r / total) * 100;

            // Exact colors from your CSS
            const colorGreen = '#4caf50';
            const colorYellow = '#ffc107';
            const colorRed = '#ff0400';

            const point1 = workPct;
            const point2 = workPct + repPct;

            // Inject the new gradient
            chart.style.background = `conic-gradient(
        ${colorGreen} 0% ${point1}%, 
        ${colorYellow} ${point1}% ${point2}%, 
        ${colorRed} ${point2}% 100%
    )`;
        }

        // Run it once immediately when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            console.log("Dashboard loaded, initializing chart...");
            if (document.getElementById('dashboardRoomSelect')) {
                updateDashboardLabStats();
            }
        });
    </script>
</body>

</html>