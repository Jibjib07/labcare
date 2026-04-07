<?php
include '../includes/admin_auth.php';
include '../includes/db.php';

// --- 1. DATA AGGREGATION: GLOBAL STATUS & COUNTERS ---
$workingCount = 0;
$repairCount = 0;
$condemnCount = 0;
$totalSets = 0;

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
            if ($age >= 5) {
                $condemnCount++;
            }
        }

        if ($status === 'working') {
            $workingCount++;
        } elseif ($status === 'for repair') {
            $repairCount++;
        }
        $totalSets++;
    }
}

// ---------------------------------------------------------
// ANALYTICS 1: Top 5 Most Common Issues
// ---------------------------------------------------------
$issuesQuery = "
    SELECT report_affected, COUNT(*) as issue_count 
    FROM unit_history 
    WHERE report_affected NOT IN ('Entire Set', 'Unspecified Issue') 
      AND report_affected IS NOT NULL
    GROUP BY report_affected 
    ORDER BY issue_count DESC 
    LIMIT 5
";
$issuesResult = $conn->query($issuesQuery);
$issueLabels = []; $issueCounts = [];
if ($issuesResult) {
    while ($row = $issuesResult->fetch_assoc()) {
        $issueLabels[] = $row['report_affected'];
        $issueCounts[] = (int)$row['issue_count'];
    }
}

// ---------------------------------------------------------
// ANALYTICS 2: Room Priority (Attention Needed)
// ---------------------------------------------------------
$roomAttentionQuery = "
    SELECT l.lab_room, 
    (SELECT COUNT(*) FROM units u WHERE u.lab_id = l.lab_id AND u.set_status = 'For Repair') as unit_repairs,
    (SELECT COUNT(*) FROM assets a WHERE a.lab_id = l.lab_id AND a.asset_status = 'For Repair') as asset_repairs
    FROM laboratories l
    WHERE l.lab_status != 'Archived'
    HAVING (unit_repairs + asset_repairs) > 0
    ORDER BY (unit_repairs + asset_repairs) DESC
    LIMIT 5";
$roomAttentionResult = $conn->query($roomAttentionQuery);
$prioLabels = []; $prioUnits = []; $prioAssets = [];
if ($roomAttentionResult) {
    while($row = $roomAttentionResult->fetch_assoc()){
        $prioLabels[] = $row['lab_room'];
        $prioUnits[] = (int)$row['unit_repairs'];
        $prioAssets[] = (int)$row['asset_repairs'];
    }
}

// ---------------------------------------------------------
// ANALYTICS 3: Segmented Maintenance Trends (REPAIR INCIDENTS)
// Logic: Count the total number of 'For Repair' log records
// ---------------------------------------------------------
$monthsList = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
$unitTrendData = array_fill(0, 12, 0);
$assetTrendData = array_fill(0, 12, 0);

// Count every log record where a unit was marked 'For Repair'
$resU = $conn->query("
    SELECT MONTH(log_date) as m, COUNT(*) as c 
    FROM units_log 
    WHERE YEAR(log_date) = YEAR(CURDATE()) 
    AND set_status = 'For Repair'
    GROUP BY m
");
if($resU) {
    while($row = $resU->fetch_assoc()) { 
        $unitTrendData[$row['m']-1] = (int)$row['c']; 
    }
}

// Count every log record where an asset was marked 'For Repair'
$resA = $conn->query("
    SELECT MONTH(log_date) as m, COUNT(*) as c 
    FROM assets_log 
    WHERE YEAR(log_date) = YEAR(CURDATE()) 
    AND asset_status = 'For Repair'
    GROUP BY m
");
if($resA) {
    while($row = $resA->fetch_assoc()) { 
        $assetTrendData[$row['m']-1] = (int)$row['c']; 
    }
}

$currentMonthIndex = (int)date('n'); 
$displayMonths = array_slice($monthsList, 0, $currentMonthIndex);
$displayUnitData = array_slice($unitTrendData, 0, $currentMonthIndex);
$displayAssetData = array_slice($assetTrendData, 0, $currentMonthIndex);

// ---------------------------------------------------------
// ANALYTICS 4: Aging Forecast (Stacked Bar)
// ---------------------------------------------------------
$ageForecastQuery = "
    SELECT l.lab_room,
    SUM(CASE WHEN DATEDIFF(CURDATE(), s.specs_purchase) / 365 < 3 THEN 1 ELSE 0 END) as healthy,
    SUM(CASE WHEN DATEDIFF(CURDATE(), s.specs_purchase) / 365 BETWEEN 3 AND 5 THEN 1 ELSE 0 END) as aging,
    SUM(CASE WHEN DATEDIFF(CURDATE(), s.specs_purchase) / 365 >= 5 THEN 1 ELSE 0 END) as critical
    FROM units u
    JOIN laboratories l ON u.lab_id = l.lab_id
    JOIN specs s ON u.set_id = s.set_id
    WHERE u.set_status != 'Condemned'
    GROUP BY l.lab_id LIMIT 5";
$ageResult = $conn->query($ageForecastQuery);
$ageLabels = []; $healthyData = []; $agingData = []; $criticalData = [];
if ($ageResult) {
    while($row = $ageResult->fetch_assoc()){
        $ageLabels[] = $row['lab_room'];
        $healthyData[] = (int)$row['healthy'];
        $agingData[] = (int)$row['aging'];
        $criticalData[] = (int)$row['critical'];
    }
}

// --- RESOURCE TOTALS ---
$assetsQuery = "SELECT COUNT(*) as total FROM assets WHERE asset_status != 'Condemned'";
$assetsResult = $conn->query($assetsQuery);
$totalAssets = ($assetsResult) ? $assetsResult->fetch_assoc()['total'] : 0;

$labsQuery = "SELECT COUNT(*) as total FROM laboratories WHERE lab_status != 'Archived'";
$labsResult = $conn->query($labsQuery);
$totalLabs = ($labsResult) ? $labsResult->fetch_assoc()['total'] : 0;

$usersQuery = "SELECT COUNT(*) as total FROM users WHERE user_status = 'Active'";
$usersResult = $conn->query($usersQuery);
$totalUsers = ($usersResult) ? $usersResult->fetch_assoc()['total'] : 0;

$totalLabsFormatted = str_pad($totalLabs, 2, '0', STR_PAD_LEFT);
$totalAssetsFormatted = str_pad($totalAssets, 2, '0', STR_PAD_LEFT);
$totalUsersFormatted = str_pad($totalUsers, 2, '0', STR_PAD_LEFT);

$roomsQuery = "SELECT lab_id, lab_room FROM laboratories WHERE lab_status != 'Archived' ORDER BY lab_room ASC";
$roomsResult = $conn->query($roomsQuery);
$dropdownRooms = [];
if ($roomsResult) {
    while ($r = $roomsResult->fetch_assoc()) { $dropdownRooms[] = $r; }
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .panel.white-panel {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0,0,0,0.03);
            padding: 24px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .panel.white-panel:hover {
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
        }
        .section-title {
            font-size: 1.1rem;
            color: #1b4d3e;
            font-weight: 700;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .chart-container {
            margin-top: 15px;
        }
        .dashboard-bottom-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-top: 24px;
        }
        .report-item-card {
            padding: 16px;
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
        @media (max-width: 1024px) {
            .dashboard-bottom-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p class="desktop-only-text">Real-time management of computer laboratory units and supplies, optimized for seamless admin coordination.</p>
            <p class="mobile-only-text">Overview of daily tasks and laboratory status.</p>
        </div>

        <div class="dashboard-layout">
            <div class="dashboard-top-grid">
                <div class="panel transparent-panel status-col">
                    <h3 class="section-title" style="border:none;">Unit Status Summary</h3>
                    <div class="status-stack">
                        <div class="status-card green" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);">
                            <div class="icon-circle-bg"><i class="fas fa-check-circle"></i></div>
                            <div class="card-info-right"><h2><?php echo $workingCount; ?></h2><span>Total Working Sets</span></div>
                        </div>
                        <div class="status-card yellow" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2);">
                            <div class="icon-circle-bg"><i class="fas fa-wrench"></i></div>
                            <div class="card-info-right"><h2><?php echo $repairCount; ?></h2><span>Total For Repair Sets</span></div>
                        </div>
                        <div class="status-card red" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(244, 67, 54, 0.2);">
                            <div class="icon-circle-bg"><i class="fas fa-trash-alt"></i></div>
                            <div class="card-info-right"><h2><?php echo $condemnCount; ?></h2><span>Total For Condemn Sets</span></div>
                        </div>
                    </div>
                </div>

                <div class="panel white-panel">
                    <h3 class="section-title">Resource Summary</h3>
                    <div class="resource-grid">
                        <div class="res-item"><i class="fas fa-desktop" style="color: #1b4d3e;"></i><h2><?php echo $totalSets; ?></h2><span>Total Sets</span></div>
                        <div class="res-item"><i class="fas fa-print" style="color: #2196f3;"></i><h2><?php echo $totalAssetsFormatted; ?></h2><span>Facility Assets</span></div>
                        <div class="res-item"><i class="fas fa-warehouse" style="color: #ff9800;"></i><h2><?php echo $totalLabsFormatted; ?></h2><span>Total Rooms</span></div>
                        <div class="res-item"><i class="fas fa-users" style="color: #9c27b0;"></i><h2><?php echo $totalUsersFormatted; ?></h2><span>Active Users</span></div>
                    </div>
                </div>

                <div class="panel white-panel supply-panel">
                    <h3 class="section-title">Supply Inventory</h3>
                    <div class="supply-list-container">
                        <?php
                        $supplyQuery = "SELECT supply_name, supply_status FROM supply WHERE supply_avail = 'Current' ORDER BY CASE WHEN supply_status = 'Out of Stock' THEN 1 ELSE 2 END LIMIT 4";
                        $supplyResult = $conn->query($supplyQuery);
                        if ($supplyResult && $supplyResult->num_rows > 0) {
                            while ($supply = $supplyResult->fetch_assoc()) {
                                $cl = ($supply['supply_status'] === 'In Stock') ? "in-stock" : "out-stock";
                                $sp = ($supply['supply_status'] === 'In Stock') ? "<span class='green-text' style='font-weight:600;'><i class='fas fa-check-circle'></i> In Stock</span>" : "<span style='color:#f44336; font-weight:600;'><i class='fas fa-times-circle'></i> Out of Stock</span>";
                                echo "<div class='supply-item $cl' style='border-radius:8px;'><div class='supply-accent'></div><div class='supply-info'><h4>".htmlspecialchars($supply['supply_name'])."</h4>$sp</div></div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-bottom-grid">
                <div class="panel white-panel table-panel">
                    <div class="panel-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 class="section-title" style="margin: 0; border:none; padding:0;">Recent Reports</h3>
                        <a href="maintenance_history.php" class="view-all" style="color: #4caf50; font-size: 13px; font-weight: bold; text-decoration: none;"><i class="fas fa-external-link-alt"></i> View All</a>
                    </div>
                    <div class="recent-reports-feed" style="max-height: 350px; overflow-y: auto; padding-right: 5px;">
                        <?php
                        $recentReportsQuery = "
                            (SELECT uh.report_actor AS reporter, uh.report_date AS report_date, l.lab_room AS room, CONCAT('PC-', u.set_tag) AS tag, u.set_status AS status 
                            FROM units u JOIN laboratories l ON u.lab_id = l.lab_id JOIN unit_history uh ON u.set_id = uh.set_id
                            WHERE u.set_status = 'For Repair' AND uh.report_date = (SELECT MAX(report_date) FROM unit_history WHERE set_id = u.set_id))
                            UNION ALL
                            (SELECT ah.report_actor AS reporter, ah.report_date AS report_date, l.lab_room AS room, CONCAT('FA-', a.asset_tag) AS tag, a.asset_status AS status 
                            FROM assets a JOIN laboratories l ON a.lab_id = l.lab_id JOIN asset_history ah ON a.asset_id = ah.asset_id
                            WHERE a.asset_status = 'For Repair' AND ah.report_date = (SELECT MAX(report_date) FROM asset_history WHERE asset_id = a.asset_id))
                            ORDER BY report_date DESC LIMIT 5";
                        $reportsResult = $conn->query($recentReportsQuery);
                        if ($reportsResult && $reportsResult->num_rows > 0):
                            while ($report = $reportsResult->fetch_assoc()):
                                $badge = ($report['status'] === 'For Repair') ? 'yellow' : 'green'; 
                                $formattedDate = date('M d, Y - g:i A', strtotime($report['report_date']));
                                ?>
                                <div class="report-item-card">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div style="font-size: 13px; color: #444;">
                                            <strong style="color: #1b4d3e; font-size: 14px;"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($report['reporter'] ?: 'System'); ?></strong>
                                            <div style="margin-top: 4px; color: #777; font-size: 12px;">
                                                <i class="far fa-clock" style="color:#4caf50;"></i> <?php echo $formattedDate; ?>
                                            </div>
                                        </div>
                                        <span class="badge <?php echo $badge; ?>" style="padding: 5px 10px; border-radius: 20px; font-weight: bold;"><?php echo htmlspecialchars($report['status']); ?></span>
                                    </div>
                                    <div style="font-size: 13px; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ddd;">
                                        <span style="color:#d32f2f; font-weight:700;"><i class="fas fa-microchip"></i> <?php echo $report['tag']; ?></span> 
                                        <span style="margin: 0 8px; color: #ccc;">|</span> 
                                        <span style="color:#555; font-weight:600;"><i class="fas fa-map-marker-alt"></i><?php echo $report['room']; ?></span>
                                    </div>
                                </div>
                            <?php endwhile;
                        else: echo "<div style='text-align: center; padding: 40px; color: #aaa;'><i class='fas fa-clipboard-check' style='font-size:30px; margin-bottom:10px;'></i><br>No recent reports found.</div>"; endif; ?>
                    </div>
                </div>

                <div class="panel white-panel">
                    <h3 class="section-title">Room Overview Visualizer</h3>
                    <div class="chart-controls" style="margin-bottom: 20px;">
                        <select class="room-select" id="dashboardRoomSelect" onchange="updateDashboardLabStats()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-weight: bold; color: #1b4d3e;">
                            <?php foreach ($dropdownRooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['lab_id']); ?>"><?php echo htmlspecialchars($room['lab_room']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="chart-container"><div class="donut-chart-large" id="dashboardDonutChart" style="box-shadow: inset 0 0 20px rgba(0,0,0,0.05);"></div></div>
                    
                    <div class="legend-row" style="margin-top: 25px; display: flex; justify-content: center; gap: 15px;">
                        <span class="legend-pill green" style="padding: 6px 12px; border-radius: 20px;"><span class="dot"></span> Working <strong id="dashLegendWorking">0</strong></span>
                        <span class="legend-pill yellow" style="padding: 6px 12px; border-radius: 20px;"><span class="dot"></span> Repair <strong id="dashLegendRepair">0</strong></span>
                    </div>
                    
                    <div style="text-align: center; margin-top: 15px; color: #f44336; font-size: 13px; font-weight: 600;">
                        Units identified for condemnation: <span id="dashLegendCondemn">0</span>
                    </div>

                    <div class="view-details-link" style="margin-top: 15px; text-align: center;">
                        <a href="#" id="viewLabDetailsLink" style="font-weight: bold; color: #4caf50; text-decoration: none;">View Full Room Details <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <div class="dashboard-bottom-grid">
                
                <div class="panel white-panel">
                    <h3 class="section-title">Most Common Issues</h3>
                    <div class="chart-container" style="height: 260px; position: relative;">
                        <canvas id="issuesBarChart"></canvas>
                    </div>
                </div>

                <div class="panel white-panel">
                    <h3 class="section-title">Room Attention Needed</h3>
                    <p style="font-size: 12px; color: #888; margin-top: -15px; margin-bottom: 10px;">Rooms ranked by current repair volume</p>
                    <div class="chart-container" style="height: 250px; position: relative;">
                        <canvas id="priorityChart"></canvas>
                    </div>
                </div>

                <div class="panel white-panel">
                    <h3 class="section-title">Maintenance Trends (<?php echo date('Y'); ?>)</h3>
                    <p style="font-size: 12px; color: #888; margin-top: -15px; margin-bottom: 10px;">Monthly repair incidence from logs</p>
                    <div class="chart-container" style="height: 250px; position: relative;">
                        <canvas id="trendLineChart"></canvas>
                    </div>
                </div>

                <div class="panel white-panel">
                    <h3 class="section-title">Equipment Aging Forecast</h3>
                    <p style="font-size: 12px; color: #888; margin-top: -15px; margin-bottom: 10px;">Computer unit age based on acquisition date</p>
                    <div class="chart-container" style="height: 250px; position: relative;">
                        <canvas id="agingForecastChart"></canvas>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script>
        // --- 1. ROOM OVERVIEW DONUT LOGIC ---
        async function updateDashboardLabStats() {
            const roomSelect = document.getElementById('dashboardRoomSelect');
            if (!roomSelect) return;
            const labId = roomSelect.value;
            const detailsLink = document.getElementById('viewLabDetailsLink');
            if (detailsLink) detailsLink.href = `assets_management.php?lab_id=${encodeURIComponent(labId)}`;
            try {
                const response = await fetch(`../includes/get_room_stats.php?lab_id=${encodeURIComponent(labId)}`);
                const data = await response.json();
                
                document.getElementById('dashLegendWorking').innerText = data.working;
                document.getElementById('dashLegendRepair').innerText = data.repair;
                document.getElementById('dashLegendCondemn').innerText = data.condemn;
                
                updateDonutChartVisual(data.working, data.repair);
            } catch (error) { console.error('Error:', error); }
        }

        function updateDonutChartVisual(working, repair) {
            const chart = document.getElementById('dashboardDonutChart');
            if (!chart) return;
            const w = parseInt(working) || 0;
            const r = parseInt(repair) || 0;
            const total = w + r;
            
            if (total === 0) { 
                chart.style.background = `conic-gradient(#f0f0f0 0% 100%)`; 
                return; 
            }
            
            const workPct = (w / total) * 100;
            chart.style.background = `conic-gradient(#4caf50 0% ${workPct}%, #ffc107 ${workPct}% 100%)`;
        }

        // --- 2. CHART.JS INITIALIZATIONS ---
        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('dashboardRoomSelect')) updateDashboardLabStats();

            Chart.defaults.font.family = "'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
            Chart.defaults.color = '#666';

            // 1. Most Common Issues
            new Chart(document.getElementById('issuesBarChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($issueLabels); ?>,
                    datasets: [{ 
                        label: 'Reported Incidents', 
                        data: <?php echo json_encode($issueCounts); ?>, 
                        backgroundColor: 'rgba(76, 175, 80, 0.85)', 
                        hoverBackgroundColor: '#1b4d3e',
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { borderDash: [2, 4], color: '#f0f0f0' } },
                        x: { grid: { display: false } }
                    },
                    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1b4d3e', padding: 10 } } 
                }
            });

            // 2. Room Priority
            new Chart(document.getElementById('priorityChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($prioLabels); ?>,
                    datasets: [
                        { label: 'PC Sets', data: <?php echo json_encode($prioUnits); ?>, backgroundColor: '#ffc107', borderRadius: 4, barPercentage: 0.7 },
                        { label: 'Assets', data: <?php echo json_encode($prioAssets); ?>, backgroundColor: '#2196f3', borderRadius: 4, barPercentage: 0.7 }
                    ]
                },
                options: { 
                    indexAxis: 'y', 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    scales: { 
                        x: { stacked: true, grid: { borderDash: [2, 4], color: '#f0f0f0' } }, 
                        y: { stacked: true, grid: { display: false } } 
                    },
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }, tooltip: { mode: 'index' } } 
                }
            });

            // 3. Maintenance Trends
            new Chart(document.getElementById('trendLineChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($displayMonths); ?>,
                    datasets: [
                        { 
                            label: 'PC Sets', 
                            data: <?php echo json_encode($displayUnitData); ?>, 
                            borderColor: '#4caf50', 
                            backgroundColor: 'rgba(76, 175, 80, 0.15)', 
                            tension: 0.4, fill: true, borderWidth: 3,
                            pointBackgroundColor: '#fff', pointBorderColor: '#4caf50', pointBorderWidth: 2, pointRadius: 4
                        },
                        { 
                            label: 'Assets', 
                            data: <?php echo json_encode($displayAssetData); ?>, 
                            borderColor: '#2196f3', 
                            backgroundColor: 'rgba(33, 150, 243, 0.15)', 
                            tension: 0.4, fill: true, borderWidth: 3,
                            pointBackgroundColor: '#fff', pointBorderColor: '#2196f3', pointBorderWidth: 2, pointRadius: 4
                        }
                    ]
                },
                options: { 
                    responsive: true, maintainAspectRatio: false, 
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { borderDash: [2, 4], color: '#f0f0f0' } },
                        x: { grid: { display: false } }
                    },
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }, tooltip: { mode: 'index', intersect: false } } 
                }
            });

            // 4. Aging Forecast
            new Chart(document.getElementById('agingForecastChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($ageLabels); ?>,
                    datasets: [
                        { label: '0-3 Yrs (Good)', data: <?php echo json_encode($healthyData); ?>, backgroundColor: 'rgba(76, 175, 80, 0.9)', barPercentage: 0.6 },
                        { label: '3-5 Yrs (Aging)', data: <?php echo json_encode($agingData); ?>, backgroundColor: 'rgba(255, 193, 7, 0.9)', barPercentage: 0.6 },
                        { label: '5+ Yrs (Critical)', data: <?php echo json_encode($criticalData); ?>, backgroundColor: 'rgba(244, 67, 54, 0.9)', barPercentage: 0.6 }
                    ]
                },
                options: { 
                    responsive: true, maintainAspectRatio: false, 
                    scales: { 
                        x: { stacked: true, grid: { display: false } }, 
                        y: { stacked: true, beginAtZero: true, grid: { borderDash: [2, 4], color: '#f0f0f0' } } 
                    },
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }, tooltip: { mode: 'index' } }
                }
            });
        });
    </script>
</body>
</html>