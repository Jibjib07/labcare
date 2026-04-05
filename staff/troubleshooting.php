<?php
require '../includes/staff_auth.php';
include '../includes/db.php';

// 1. AJAX Endpoint: Fetch Single Guide Details
if (isset($_GET['get_details'])) {
    $id = intval($_GET['get_details']);
    $res = mysqli_query($conn, "SELECT * FROM troubleshooting WHERE guide_id = $id");
    $data = mysqli_fetch_assoc($res);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// 2. AJAX Endpoint: Multi-Filter Search
if (isset($_GET['ajax_filter'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $category = mysqli_real_escape_string($conn, $_GET['category']);

    $query = "SELECT * FROM troubleshooting WHERE guide_status = '$status'";
    if (!empty($search)) $query .= " AND issue_title LIKE '%$search%'";
    if (!empty($category)) $query .= " AND issue_catego = '$category'";

    $query .= " ORDER BY issue_title ASC";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $isFirst = true;
        while ($row = mysqli_fetch_assoc($result)) {
            $firstClass = $isFirst ? 'auto-click' : '';
            echo "
            <div class='supply-row asset-item guide-item $firstClass' data-id='{$row['guide_id']}'>
                <div class='item-info'>
                    <span class='supply-name-cell item-name'>" . htmlspecialchars($row['issue_title']) . "</span>
                </div>
                <div class='supply-status-cell'>
                    <span class='badge-text'>" . htmlspecialchars($row['issue_catego']) . "</span>
                </div>
            </div>";
            $isFirst = false;
        }
    } else {
        echo "<div style='text-align:center; padding: 40px; color: #999; font-size: 13px;'>No results found.</div>";
    }
    exit;
}

// REMOVED: Endpoints for Update, Archive, Restore, and Create

$categories = ["Hardware Problem", "Software / OS Issues", "Power & Connection Errors", "Peripheral Device Issues"];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Troubleshooting Guide - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/troubleshooting.css?v=<?php echo time(); ?>">

    <style>
        .back-btn {
            background: white;
            border: 1px solid #ddd;
            width: 40px;
            height: 40px;
            border-radius: 6px;
            color: #333;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .back-btn:hover {
            background: #f5f5f5;
        }

        @media (max-width: 768px) {
            .troubleshoot-layout {
                display: block;
                width: 100%;
            }

            .left-list-panel {
                width: 100%;
                display: block;
            }

            .right-detail-panel {
                width: 100%;
                display: none;
                margin: 0;
            }

            .troubleshoot-layout.show-mobile-detail .left-list-panel {
                display: none;
            }

            .troubleshoot-layout.show-mobile-detail .right-detail-panel {
                display: block;
            }

            .mobile-back-row {
                display: flex !important;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>
    <div id="notification-container" class="notification-container"></div>
    <div class="main-content">
        <div class="page-header">
            <h1>Troubleshooting Guide</h1>
            <p>Diagnose technical issues and access guided solutions for common hardware problems.</p>
        </div>
        <div class="troubleshoot-layout">
            <div class="panel white-panel left-list-panel">
                <div class="panel-header-row">
                    <h3>Existing Guide List</h3>
                    <input type="hidden" id="categoryList" value='<?php echo json_encode($categories); ?>'>
                </div>

                <div class="status-toggle-row">
                    <input type="hidden" id="statusValue" value="Available">
                </div>

                <div class="search-filter-row">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search">
                    <select id="categoryFilter" class="filter-dropdown" style="width: 100%;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="table-container list-container" id="guideTableBody">
                </div>
            </div>

            <div class="panel white-panel right-detail-panel">
                <div class="mobile-back-row" style="display: none; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <button type="button" class="back-btn" onclick="closeMobileDetails()">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h3 style="margin:0; font-size:16px;">Back to List</h3>
                </div>

                <div class="panel-header-row">
                    <h3>Guide Full Details</h3>
                </div>
                <div id="detailView" class="detail-content"></div>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/troubleshooting.js"></script>
</body>

</html>