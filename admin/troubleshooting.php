<?php
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
            echo "<tr class='guide-row $firstClass' data-id='{$row['guide_id']}'>
                    <td>
                        <div class='guide-info-wrapper'>
                            <span class='guide-title-text'>" . htmlspecialchars($row['issue_title']) . "</span>
                            <div class='badge-container'>
                                <span class='guide-category-badge'>" . htmlspecialchars($row['issue_catego']) . "</span>
                            </div>
                        </div>
                    </td>
                  </tr>";
            $isFirst = false;
        }
    } else {
        echo "<tr><td style='text-align:center; padding: 20px;'>No results found.</td></tr>";
    }
    exit;
}

// 3. AJAX Endpoint: Update Existing Guide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_guide'])) {
    $id = intval($_POST['guide_id']);
    $title = mysqli_real_escape_string($conn, $_POST['issue_title']);
    $category = mysqli_real_escape_string($conn, $_POST['issue_catego']);
    $summary = mysqli_real_escape_string($conn, $_POST['issue_summary']);
    $cause = mysqli_real_escape_string($conn, $_POST['issue_cause']);
    $solution = mysqli_real_escape_string($conn, $_POST['issue_solutio']);
    $preventive = mysqli_real_escape_string($conn, $_POST['issue_preven']);

    $update_query = "UPDATE troubleshooting SET 
                    issue_title = '$title', 
                    issue_catego = '$category', 
                    issue_summary = '$summary', 
                    issue_cause = '$cause', 
                    issue_solutio = '$solution', 
                    issue_preven = '$preventive' 
                    WHERE guide_id = $id";

    if (mysqli_query($conn, $update_query)) {
        echo json_encode(['status' => 'success', 'message' => 'Guide updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

// 4. AJAX Endpoint: Archive Guide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_guide'])) {
    $id = intval($_POST['guide_id']);
    $archive_query = "UPDATE troubleshooting SET guide_status = 'Archived' WHERE guide_id = $id";
    if (mysqli_query($conn, $archive_query)) {
        echo json_encode(['status' => 'success', 'message' => 'Guide archived successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

// 5. AJAX Endpoint: Restore Guide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_guide'])) {
    $id = intval($_POST['guide_id']);
    $restore_query = "UPDATE troubleshooting SET guide_status = 'Available' WHERE guide_id = $id";
    if (mysqli_query($conn, $restore_query)) {
        echo json_encode(['status' => 'success', 'message' => 'Guide restored successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

// 6. CREATE NEW GUIDE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_guide'])) {
    $title = mysqli_real_escape_string($conn, $_POST['issue_title']);
    $category = mysqli_real_escape_string($conn, $_POST['issue_catego']);
    $summary = mysqli_real_escape_string($conn, $_POST['issue_summary']);
    $cause = mysqli_real_escape_string($conn, $_POST['issue_cause']);
    $solution = mysqli_real_escape_string($conn, $_POST['issue_solutio']);
    $preventive = mysqli_real_escape_string($conn, $_POST['issue_preven']);
    $insert_query = "INSERT INTO troubleshooting (issue_title, issue_catego, issue_summary, issue_cause, issue_solutio, issue_preven, guide_status) 
                     VALUES ('$title', '$category', '$summary', '$cause', '$solution', '$preventive', 'Available')";
    if (mysqli_query($conn, $insert_query)) {
        echo json_encode(['status' => 'success', 'message' => 'New guide created successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}
$categories = ["Hardware Problem", "Software / OS Issues", "Power & Connection Errors", "Peripheral Device Issues"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Troubleshooting Management - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/troubleshooting.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div id="notification-container" class="notification-container"></div>
    <div class="main-content">
        <div class="page-header">
            <h1>Troubleshooting Management</h1>
            <p>Diagnose technical issues and access guided solutions for common hardware problems.</p>
        </div>
        <div class="troubleshoot-layout">
            <div class="panel white-panel left-list-panel">
                <div class="panel-header-row">
                    <h3>Existing Guide List</h3>
                    <input type="hidden" id="categoryList" value='<?php echo json_encode($categories); ?>'>
                    <button class="btn-green-add" id="openAddModal"><i class="fas fa-plus-circle"></i> Add</button>
                </div>
                <div class="status-toggle-row">
                    <div class="toggle-group">
                        <button type="button" class="status-toggle-btn active" data-status="Available">Available</button>
                        <button type="button" class="status-toggle-btn" data-status="Archived">Archived</button>
                    </div>
                    <input type="hidden" id="statusValue" value="Available">
                </div>
                <div class="search-filter-row">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search a guide">
                    <select id="categoryFilter" class="filter-dropdown">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="table-container">
                    <table class="guide-table">
                        <tbody id="guideTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="panel white-panel right-detail-panel">
                <div class="panel-header-row">
                    <h3>Guide Full Details</h3>
                    <div id="actionButtons" class="action-buttons" style="display:none;">
                        <button class="btn-edit" id="mainEditBtn"><i class="fas fa-pen"></i> Edit</button>
                        <button class="btn-archive" id="archiveToggleBtn"></button>
                    </div>
                </div>
                <div id="detailView" class="detail-content"></div>
            </div>
        </div>
    </div>
    <div id="addGuideModal" class="modal-overlay" style="display:none;">
        <div class="modal-content white-panel">
            <div class="panel-header-row">
                <h3>Adding New Guide</h3>
                <div class="action-buttons">
                    <button type="button" class="btn-cancel" id="closeAddModal">Cancel</button>
                    <button type="button" class="btn-green-add" id="submitCreateBtn"><i class="fas fa-plus-circle"></i> Create</button>
                </div>
            </div>
            <hr class="modal-divider">
            <form id="addGuideForm" class="modal-form">
                <div class="form-group"><label>Issue Title</label><input type="text" name="issue_title" placeholder="Input field" required></div>
                <div class="form-group"><label>Category</label>
                    <select name="issue_catego">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Summary Description</label><input type="text" name="issue_summary" placeholder="Input field"></div>
                <div class="form-group"><label>Possible Causes</label><textarea name="issue_cause" placeholder="Input field" rows="3"></textarea></div>
                <div class="form-group"><label>Step by Step Solution</label><textarea name="issue_solutio" placeholder="Input field" rows="3"></textarea></div>
                <div class="form-group"><label>Preventive Measure</label><textarea name="issue_preven" placeholder="Input field" rows="2"></textarea></div>
            </form>
        </div>
    </div>
    <div id="archiveConfirmModal" class="modal-overlay" style="display:none;">
        <div class="modal-content white-panel modal-confirm">
            <h3>Archive this Troubleshooting Guide?</h3>
            <p style="font-size: 13px; color: #666; margin-bottom: 20px;">No longer visible in the active list. Can be restored later.</p>
            <div class="form-group"><label>Issue Title</label><input type="text" id="archiveIssueTitle" class="detail-input" readonly></div>
            <div class="form-group" style="margin-bottom: 25px;"><label>Category</label><input type="text" id="archiveCategory" class="detail-input" readonly></div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-cancel" id="closeArchiveModal">Cancel</button>
                <button type="button" class="btn-archive" id="confirmArchiveBtn"><i class="fas fa-box-archive"></i> Archive</button>
            </div>
        </div>
    </div>
    <div id="restoreConfirmModal" class="modal-overlay" style="display:none;">
        <div class="modal-content white-panel modal-confirm">
            <h3>Restore Troubleshooting Guide?</h3>
            <p style="font-size: 13px; color: #666; margin-bottom: 20px;">This guide will be returned to the active list and visible to all staff.</p>
            <div class="form-group"><label>Issue Title</label><input type="text" id="restoreIssueTitle" class="detail-input" readonly></div>
            <div class="form-group" style="margin-bottom: 25px;"><label>Category</label><input type="text" id="restoreCategory" class="detail-input" readonly></div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-cancel" id="closeRestoreModal">Cancel</button>
                <button type="button" class="btn-green-add" id="confirmRestoreBtn"><i class="fas fa-check-circle"></i> Confirm</button>
            </div>
        </div>
    </div>
    <script src="js/troubleshooting.js"></script>
</body>
</html>