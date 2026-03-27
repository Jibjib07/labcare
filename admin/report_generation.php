<?php

/**
 * 1. AJAX HANDLER - MUST BE AT THE ABSOLUTE TOP
 */
if (isset($_POST['action'])) {
    ob_start();
    header('Content-Type: application/json');

    require_once dirname(__FILE__) . '/../includes/db.php';
    session_start();

    $response = ['success' => false, 'data' => [], 'prepared_by' => 'System Administrator'];

    if (isset($_SESSION['user_fname']) && isset($_SESSION['user_lname'])) {
        $response['prepared_by'] = $_SESSION['user_fname'] . ' ' . $_SESSION['user_lname'];
    }

    // Capture Date Parameters
    $asOfDate = $_POST['asOfDate'] ?? date('Y-m-d');
    $fromDate = $_POST['fromDate'] ?? date('Y-m-d');
    $toDate   = $_POST['toDate'] ?? date('Y-m-d');
    $subTab   = $_POST['subTab'] ?? 'units'; 
    $type     = $_POST['type'] ?? 'status';

    try {
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception("Database connection failed.");
        }

        /**
         * ACTION: fetch_snapshot_rooms
         */
        if ($_POST['action'] === 'fetch_snapshot_rooms') {
            $dateConstraint = ($type === 'condemned') 
                ? "log_date BETWEEN ? AND ?" 
                : "log_date <= ?";

            $query = "
                SELECT DISTINCT l.lab_id, l.lab_room 
                FROM laboratories l
                WHERE l.lab_id IN (
                    SELECT DISTINCT lab_id FROM units_log WHERE $dateConstraint
                    UNION
                    SELECT DISTINCT lab_id FROM assets_log WHERE $dateConstraint
                )
                ORDER BY l.lab_room ASC
            ";

            $stmt = $conn->prepare($query);
            if ($type === 'condemned') {
                $stmt->bind_param("ssss", $fromDate, $toDate, $fromDate, $toDate);
            } else {
                $stmt->bind_param("ss", $asOfDate, $asOfDate);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            $response['success'] = true;
        }

        /**
         * ACTION: generate_snapshot_report
         */
        if ($_POST['action'] === 'generate_snapshot_report') {

            if ($type === 'inventory') {
                // FIXED: ID removed from supply name for Inventory Reports
                $query = "
                    SELECT sl.supply_name AS set_tag, 
                           sl.supply_status AS set_status, 
                           'N/A' AS lab_room, sl.log_date
                    FROM supply_log sl
                    INNER JOIN (
                        SELECT supply_id, MAX(log_id) as max_log_id
                        FROM supply_log
                        WHERE log_date <= ?
                        GROUP BY supply_id
                    ) latest ON sl.log_id = latest.max_log_id
                    WHERE sl.supply_avail = 'Current'
                    ORDER BY sl.supply_name ASC
                ";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $asOfDate);
            } else {
                $table = ($subTab === 'assets') ? 'assets_log' : 'units_log';
                $idCol = ($subTab === 'assets') ? 'asset_id' : 'set_id';
                $tagCol = ($subTab === 'assets') ? 'asset_tag' : 'set_tag';
                $statusCol = ($subTab === 'assets') ? 'asset_status' : 'set_status';
                
                $prefix = ($subTab === 'assets') ? 'FA-' : 'PC-';
                $labId = $_POST['labId'] ?? 'all';

                if ($type === 'condemned') {
                    $query = "
                        SELECT CONCAT('$prefix', log.$tagCol, ' <small style=\"color: #777; font-weight: normal;\">(', log.$idCol, ')</small>') AS set_tag, 
                               log.$statusCol AS set_status, log.lab_room, log.log_date
                        FROM $table log
                        WHERE log.$statusCol = 'Condemned'
                        AND log.log_date BETWEEN ? AND ?
                    ";
                } else {
                    $query = "
                        SELECT CONCAT('$prefix', log.$tagCol, ' <small style=\"color: #777; font-weight: normal;\">(', log.$idCol, ')</small>') AS set_tag, 
                               log.$statusCol AS set_status, log.lab_room, log.log_date
                        FROM $table log
                        INNER JOIN (
                            SELECT $idCol, MAX(log_id) as max_log_id
                            FROM $table
                            WHERE log_date <= ?
                            GROUP BY $idCol
                        ) latest ON log.log_id = latest.max_log_id
                        WHERE log.$statusCol IN ('Working', 'For Repair')
                    ";
                }

                if (!empty($labId) && $labId !== 'all') {
                    $query .= " AND log.lab_id = ? ";
                }

                $query .= " ORDER BY log.$tagCol ASC";
                $stmt = $conn->prepare($query);
                
                if ($type === 'condemned') {
                    if (!empty($labId) && $labId !== 'all') {
                        $stmt->bind_param("ssi", $fromDate, $toDate, $labId);
                    } else {
                        $stmt->bind_param("ss", $fromDate, $toDate);
                    }
                } else {
                    if (!empty($labId) && $labId !== 'all') {
                        $stmt->bind_param("si", $asOfDate, $labId);
                    } else {
                        $stmt->bind_param("s", $asOfDate);
                    }
                }
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            $response['success'] = true;
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    ob_end_clean();
    echo json_encode($response);
    exit;
}

require_once '../includes/db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Generation - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/report_generation.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Report Generation Management</h1>
            <p>Analytics and Formal Documentation System</p>
        </div>

        <div class="report-layout" id="reportLayout">
            <div class="panel white-panel form-panel" id="formPanel">
                <div class="panel-header-row">
                    <h3 class="panel-title">Generate Report</h3>
                    <button class="btn-generate" id="generateReportBtn">
                        <i class="fas fa-sync-alt"></i> Generate
                    </button>
                </div>

                <div class="main-tabs">
                    <button class="tab-btn active" data-tab="status">Status</button>
                    <button class="tab-btn" data-tab="condemned">Condemned</button>
                    <button class="tab-btn" data-tab="inventory">Inventory</button>
                </div>

                <form class="filter-form" onsubmit="return false;">
                    <div class="form-group" id="snapshotDateGroup">
                        <label>As of Date (Snapshot)</label>
                        <input type="date" id="snapshotDate" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div id="dateRangeGroup" style="display: none; gap: 10px; flex-direction: column;">
                        <div class="form-group">
                            <label>From Date</label>
                            <input type="date" id="fromDate" class="form-input" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="form-group">
                            <label>To Date</label>
                            <input type="date" id="toDate" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-group" id="labRoomGroup">
                        <label>Select Computer Laboratory Room</label>
                        <select class="form-select" id="labRoomSelect">
                            <option value="all">All Laboratories</option>
                        </select>
                    </div>
                </form>

                <div class="sub-tabs-wrapper" id="subTabsWrapper">
                    <button class="sub-tab active" data-sub="units">Computer Units</button>
                    <button class="sub-tab" data-sub="assets">Facility Assets</button>
                </div>

                <div class="visualization-area">
                    <div class="chart-container" id="statusChartContainer">
                        <div class="donut-chart" id="mainDonutChart"></div>
                        <div class="chart-legend-box" id="statusLegend">
                            <div class="legend-pill">
                                <div class="legend-content">
                                    <span class="dot green" id="legendColor1"></span>
                                    <span id="legendText1">Working</span>
                                </div>
                                <span class="count" id="countWorking">0</span>
                            </div>
                            <div class="legend-pill">
                                <div class="legend-content">
                                    <span class="dot yellow" id="legendColor2"></span>
                                    <span id="legendText2">For Repair</span>
                                </div>
                                <span class="count" id="countRepair">0</span>
                            </div>
                        </div>
                    </div>

                    <div id="condemnedCountPanel" class="condemned-display-panel" style="display: none;">
                        <div class="condemned-card">
                            <h2 class="condemned-number" id="totalCondemnedCount">0</h2>
                            <p class="condemned-label" id="condemnedLabel">Number of Condemned</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel white-panel preview-panel" id="previewPanel">
                
                <div class="mobile-back-container">
                    <button class="btn-back" id="backToGenerateBtn">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <span class="back-text">Back to Generate</span>
                </div>

                <div class="panel-header-row">
                    <h3 class="panel-title">Preview Content</h3> 
                    
                    <button class="btn-export" id="exportReportBtn">
                        <i class="fas fa-file-export"></i> <span class="export-text">Export Data</span>
                    </button>
                </div>

                <div class="report-document-container">
                    <div class="preview-content" id="reportPreviewArea">
                        <div class="empty-state">
                            <i class="fas fa-file-invoice fa-3x" style="color: #ddd; margin-bottom: 15px;"></i>
                            <p>Select criteria and click <strong>Generate</strong> to view the formal report preview.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/report_generation.js?v=<?php echo time(); ?>"></script>
</body>

</html>