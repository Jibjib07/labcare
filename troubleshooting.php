<?php 
include 'includes/db.php'; 

$show_notification = false; 
$is_archived = false; 
$notif_title = "Guide Published";
$notif_msg = "Guide is now visible to staff.";

// --- 1. HANDLE SAVING NEW GUIDE ---
if (isset($_POST['create_guide'])) {
    $title = mysqli_real_escape_string($conn, $_POST['issue_title']);
    $category = mysqli_real_escape_string($conn, $_POST['issue_catego']);
    $summary = mysqli_real_escape_string($conn, $_POST['issue_summary']);
    $cause = mysqli_real_escape_string($conn, $_POST['issue_cause']);
    $solution = mysqli_real_escape_string($conn, $_POST['issue_solutio']);
    $preventive = mysqli_real_escape_string($conn, $_POST['issue_preven']);
    $date_created = date('Y-m-d');

    $query = "INSERT INTO troubleshooting (issue_title, issue_catego, issue_summary, issue_cause, issue_solutio, issue_preven, guide_date, guide_status) 
              VALUES ('$title', '$category', '$summary', '$cause', '$solution', '$preventive', '$date_created', 'Available')";
    
    if (mysqli_query($conn, $query)) { $show_notification = true; }
}

// --- 2. HANDLE UPDATING EXISTING GUIDE ---
if (isset($_POST['update_guide'])) {
    $id = mysqli_real_escape_string($conn, $_POST['guide_id']);
    $title = mysqli_real_escape_string($conn, $_POST['issue_title']);
    $category = mysqli_real_escape_string($conn, $_POST['issue_catego']); 
    $summary = mysqli_real_escape_string($conn, $_POST['issue_summary']);
    $cause = mysqli_real_escape_string($conn, $_POST['issue_cause']);
    $solution = mysqli_real_escape_string($conn, $_POST['issue_solutio']);
    $preventive = mysqli_real_escape_string($conn, $_POST['issue_preven']);

    $query = "UPDATE troubleshooting SET issue_title='$title', issue_catego='$category', issue_summary='$summary', issue_cause='$cause', issue_solutio='$solution', issue_preven='$preventive' WHERE guide_id='$id'";
    
    if (mysqli_query($conn, $query)) {
        $show_notification = true;
        $notif_title = "Guide Updated";
        $notif_msg = "Changes to guide have been saved.";
    }
}

// --- 3. HANDLE ARCHIVING GUIDE ---
if (isset($_POST['archive_guide'])) {
    $id = mysqli_real_escape_string($conn, $_POST['guide_id']);
    if (mysqli_query($conn, "UPDATE troubleshooting SET guide_status='Archived' WHERE guide_id='$id'")) {
        $show_notification = true;
        $is_archived = true; 
        $notif_title = "Guide Archived";
        $notif_msg = "Guide was removed from the list.";
    }
}

// --- 4. HANDLE RESTORING GUIDE ---
if (isset($_POST['restore_guide'])) {
    $id = mysqli_real_escape_string($conn, $_POST['guide_id']);
    if (mysqli_query($conn, "UPDATE troubleshooting SET guide_status='Available' WHERE guide_id='$id'")) {
        $show_notification = true;
        $notif_title = "Guide Restored";
        $notif_msg = "The guide has been moved back to the active list.";
    }
}

// --- 5. AJAX HANDLERS ---
if (isset($_GET['ajax_search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $cat_filter = mysqli_real_escape_string($conn, $_GET['category']);
    $status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'Available';
    
    $query = "SELECT * FROM troubleshooting WHERE guide_status = '$status_filter' AND (issue_title LIKE '%$search%' OR issue_catego LIKE '%$search%')";
    if (!empty($cat_filter)) { $query .= " AND issue_catego = '$cat_filter'"; }
    $query .= " ORDER BY guide_id DESC";
    
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr class='clickable-row' data-id='". $row['guide_id'] ."'>
                    <td>" . htmlspecialchars($row['issue_title']) . "</td>
                    <td>" . htmlspecialchars($row['issue_catego']) . "</td>
                    <td>" . date('m/d/Y', strtotime($row['guide_date'])) . "</td>
                  </tr>";
        }
    } else { echo "<tr><td colspan='3' style='text-align:center; padding: 20px;'>No matching guides found.</td></tr>"; }
    exit; 
}

if (isset($_GET['get_details'])) {
    $id = mysqli_real_escape_string($conn, $_GET['guide_id']);
    $result = mysqli_query($conn, "SELECT * FROM troubleshooting WHERE guide_id = '$id'");
    if ($row = mysqli_fetch_assoc($result)) { echo json_encode($row); }
    exit;
}

$guide_result = mysqli_query($conn, "SELECT * FROM troubleshooting WHERE guide_status = 'Available' ORDER BY guide_id DESC");
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
<body class="bg-light">
    <?php include 'includes/sidebar.php'; ?>

    <div id="toast" class="toast-notification <?php echo ($is_archived) ? 'archived-toast' : ''; ?>">
        <div class="toast-icon"><img src="assets/mdi--bookmark-box.svg" alt="Icon" width="24"></div>
        <div class="toast-text">
            <h4 id="toastTitle"><?php echo $notif_title; ?></h4>
            <p id="toastMsg"><?php echo $notif_msg; ?></p>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Troubleshooting Management</h1>
            <p>Diagnose technical issues and access guided solutions for common hardware problems.</p>
        </div>

        <div class="troubleshoot-layout">
            
            <div class="card-panel list-container">
                <div class="panel-header">
                    <h3>Existing Guide List</h3>
                    <button class="btn-green-add" id="openModalBtn"><i class="fas fa-plus-circle"></i> Add</button>
                </div>

                <div class="filter-controls">
                    <div class="search-row">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search a guide...">
                        <div class="filter-container">
                            <div class="filter-dropdown" id="filterBtn">
                                <span id="filterBtnText">Filter</span> <i class="fas fa-filter"></i>
                            </div>
                            <div class="filter-options" id="filterMenu">
                                <div class="filter-item active-filter" onclick="applyFilter(this, '')">All Categories</div>
                                <div class="filter-item" onclick="applyFilter(this, 'Hardware Problems')">Hardware Problems</div>
                                <div class="filter-item" onclick="applyFilter(this, 'Software / OS Issues')">Software / OS Issues</div>
                                <div class="filter-item" onclick="applyFilter(this, 'Power & Connection Errors')">Power & Connection Errors</div>
                                <div class="filter-item" onclick="applyFilter(this, 'Peripheral Device Issues')">Peripheral Device Issues</div>
                            </div>
                        </div>
                    </div>
                    <div class="radio-row">
                        <label class="radio-label"><input type="radio" name="st" value="Available" checked onchange="fetchFilteredData()"> Available</label>
                        <label class="radio-label"><input type="radio" name="st" value="Archived" onchange="fetchFilteredData()"> Archived</label>
                    </div>
                </div>

                <div class="table-area">
                    <table class="guide-table">
                        <thead>
                            <tr><th style="width: 45%;">Issue Title</th><th style="width: 30%;">Category</th><th style="width: 25%;">Date Created</th></tr>
                        </thead>
                        <tbody id="guideTableBody">
                            <?php while($row = mysqli_fetch_assoc($guide_result)): ?>
                                <tr class="clickable-row" data-id="<?php echo $row['guide_id']; ?>">
                                    <td><?php echo htmlspecialchars($row['issue_title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['issue_catego']); ?></td>
                                    <td><?php echo date('m/d/Y', strtotime($row['guide_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="right-column-wrapper">
                <form action="troubleshooting.php" method="POST" class="card-panel detail-panel" id="mainDetailForm">
                    <input type="hidden" name="guide_id" id="editGuideId">
                    <div class="panel-header detail-header">
                        <h3>Guide Full Details</h3>
                        <div class="action-buttons" id="viewModeButtons">
                            <button type="button" class="btn-edit" id="startEditBtn"><i class="fas fa-pen"></i> Edit</button>
                            <button type="button" class="btn-archive" id="openArchiveBtn"><i class="fas fa-box-archive"></i> Archive</button>
                        </div>
                        <div class="action-buttons" id="restoreModeButtons" style="display: none;">
                            <button type="button" class="btn-green-add" id="openRestoreBtn">
                                <i class="fas fa-plus-circle"></i> Restore
                            </button>
                        </div>
                        <div class="action-buttons" id="editModeButtons" style="display: none;">
                            <button type="button" class="btn-cancel" id="cancelEditBtn">Cancel</button>
                            <button type="submit" name="update_guide" class="btn-save-update"><i class="fas fa-check-circle"></i> Save Update</button>
                        </div>
                    </div>
                    
                    <div class="detail-content" id="detailPanelBody">
                        <div class="empty-state">Select a guide from the list to view its full details.</div>
                    </div>

                    <div id="archiveOverlay" class="archive-confirm-overlay" style="display: none;">
                        <div class="archive-confirm-card">
                            <h3>Archive this Troubleshooting Guide?</h3>
                            <p>This guide will no longer be visible in the active list.</p>
                            <div class="confirm-info-group">
                                <label>Issue Title</label>
                                <div class="confirm-val" id="confirmTitle">-</div>
                            </div>
                            <div class="confirm-info-group">
                                <label>Category</label>
                                <div class="confirm-val" id="confirmCategory">-</div>
                            </div>
                            <div class="confirm-actions">
                                <button type="button" class="btn-cancel" id="closeArchiveBtn">Cancel</button>
                                <button type="submit" name="archive_guide" class="btn-archive-confirm">Archive</button>
                            </div>
                        </div>
                    </div>

                    <div id="restoreOverlay" class="archive-confirm-overlay" style="display: none;">
                        <div class="archive-confirm-card">
                            <h3>Restore Troubleshooting Guide?</h3>
                            <p>This guide will be returned to the active list.</p>
                            <div class="confirm-info-group">
                                <label>Issue Title</label>
                                <div class="confirm-val" id="restoreConfirmTitle">-</div>
                            </div>
                            <div class="confirm-info-group">
                                <label>Category</label>
                                <div class="confirm-val" id="restoreConfirmCategory">-</div>
                            </div>
                            <div class="confirm-actions">
                                <button type="button" class="btn-cancel" id="closeRestoreBtn">Cancel</button>
                                <button type="submit" name="restore_guide" class="btn-green-add" style="border:none;">
                                    <i class="fas fa-check-circle"></i> Confirm
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <div id="addGuideModal" class="guide-modal-overlay">
        <div class="guide-modal-card">
            <form action="troubleshooting.php" method="POST">
                <div class="guide-modal-header">
                    <h3>Adding New Guide</h3>
                    <button type="submit" name="create_guide" class="btn-create-submit"><i class="fas fa-plus"></i> Create</button>
                </div>
                <div class="guide-modal-body">
                    <div class="modal-input-group"><label>Issue Title</label><input type="text" name="issue_title" required></div>
                    <div class="modal-input-group"><label>Category</label>
                        <select name="issue_catego" style="width: 50%;">
                            <option value="Hardware Problems">Hardware Problems</option>
                            <option value="Software / OS Issues">Software / OS Issues</option>
                            <option value="Power & Connection Errors">Power & Connection Errors</option>
                            <option value="Peripheral Device Issues">Peripheral Device Issues</option>
                        </select>
                    </div>
                    <div class="modal-input-group"><label>Summary Description</label><input type="text" name="issue_summary" required></div>
                    <div class="modal-input-group"><label>Possible Causes</label><textarea name="issue_cause" required></textarea></div>
                    <div class="modal-input-group"><label>Step by Step Solution</label><textarea name="issue_solutio" required></textarea></div>
                    <div class="modal-input-group"><label>Preventive Measure</label><textarea name="issue_preven" required></textarea></div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const toast = document.getElementById('toast');
        <?php if($show_notification): ?>
            toast.classList.add('active');
            setTimeout(() => { toast.classList.remove('active'); }, 4000);
        <?php endif; ?>

        const tableBody = document.getElementById('guideTableBody');
        const detailPanel = document.getElementById('detailPanelBody');
        const viewButtons = document.getElementById('viewModeButtons');
        const editButtons = document.getElementById('editModeButtons');
        const restoreButtons = document.getElementById('restoreModeButtons');
        const archiveOverlay = document.getElementById('archiveOverlay');
        const restoreOverlay = document.getElementById('restoreOverlay');
        let currentGuideData = null;

        tableBody.addEventListener('click', (e) => {
            const row = e.target.closest('.clickable-row');
            if (!row) return;
            cancelEdit(); 
            document.querySelectorAll('.clickable-row').forEach(r => r.classList.remove('active-row'));
            row.classList.add('active-row');
            
            fetch(`troubleshooting.php?get_details=1&guide_id=${row.getAttribute('data-id')}`)
                .then(res => res.json())
                .then(data => {
                    currentGuideData = data;
                    renderViewMode();
                });
        });

        function renderViewMode() {
            archiveOverlay.style.display = 'none';
            restoreOverlay.style.display = 'none';
            const selectedStatus = document.querySelector('input[name="st"]:checked').value;

            if (selectedStatus === 'Archived') {
                viewButtons.style.display = 'none';
                restoreButtons.style.display = 'flex';
            } else {
                viewButtons.style.display = 'flex';
                restoreButtons.style.display = 'none';
            }
            
            editButtons.style.display = 'none';
            detailPanel.innerHTML = `
                <div class="detail-section"><label>Category:</label><div class="info-box" style="width: 50%;">${currentGuideData.issue_catego}</div></div>
                <div class="detail-section"><label>Issue Title:</label><div class="info-box title-text">${currentGuideData.issue_title}</div></div>
                <div class="detail-section"><label>Summary Description:</label><div class="info-box">${currentGuideData.issue_summary}</div></div>
                <div class="detail-section"><label>Possible Causes:</label><div class="info-box"><ul class="bullet-list">${currentGuideData.issue_cause.split('\n').map(li => li.trim() ? `<li>${li}</li>` : '').join('')}</ul></div></div>
                <div class="detail-section"><label>Step-by-Step Solution:</label><div class="info-box"><ul class="bullet-list">${currentGuideData.issue_solutio.split('\n').map(li => li.trim() ? `<li>${li}</li>` : '').join('')}</ul></div></div>
                <div class="detail-section"><label>Preventive Measures:</label><div class="info-box">${currentGuideData.issue_preven}</div></div>
            `;
        }

        document.getElementById('startEditBtn').onclick = () => {
            if(!currentGuideData) return;
            viewButtons.style.display = 'none';
            restoreButtons.style.display = 'none';
            editButtons.style.display = 'flex';
            document.getElementById('editGuideId').value = currentGuideData.guide_id;
            
            detailPanel.innerHTML = `
                <div class="detail-section">
                    <label>Category:</label>
                    <select name="issue_catego" class="edit-input" style="width: 50%;">
                        <option value="Hardware Problems" ${currentGuideData.issue_catego === 'Hardware Problems' ? 'selected' : ''}>Hardware Problems</option>
                        <option value="Software / OS Issues" ${currentGuideData.issue_catego === 'Software / OS Issues' ? 'selected' : ''}>Software / OS Issues</option>
                        <option value="Power & Connection Errors" ${currentGuideData.issue_catego === 'Power & Connection Errors' ? 'selected' : ''}>Power & Connection Errors</option>
                        <option value="Peripheral Device Issues" ${currentGuideData.issue_catego === 'Peripheral Device Issues' ? 'selected' : ''}>Peripheral Device Issues</option>
                    </select>
                </div>
                <div class="detail-section"><label>Issue Title:</label><input type="text" name="issue_title" class="edit-input" value="${currentGuideData.issue_title}"></div>
                <div class="detail-section"><label>Summary Description:</label><textarea name="issue_summary" class="edit-textarea">${currentGuideData.issue_summary}</textarea></div>
                <div class="detail-section"><label>Possible Causes:</label><textarea name="issue_cause" class="edit-textarea large">${currentGuideData.issue_cause}</textarea></div>
                <div class="detail-section"><label>Step-by-Step Solution:</label><textarea name="issue_solutio" class="edit-textarea large">${currentGuideData.issue_solutio}</textarea></div>
                <div class="detail-section"><label>Preventive Measures:</label><textarea name="issue_preven" class="edit-textarea">${currentGuideData.issue_preven}</textarea></div>
            `;
        };

        document.getElementById('openArchiveBtn').onclick = () => {
            if(!currentGuideData) return;
            document.getElementById('confirmTitle').innerText = currentGuideData.issue_title;
            document.getElementById('confirmCategory').innerText = currentGuideData.issue_catego;
            document.getElementById('editGuideId').value = currentGuideData.guide_id;
            archiveOverlay.style.display = 'flex';
        };

        document.getElementById('openRestoreBtn').onclick = () => {
            if(!currentGuideData) return;
            document.getElementById('restoreConfirmTitle').innerText = currentGuideData.issue_title;
            document.getElementById('restoreConfirmCategory').innerText = currentGuideData.issue_catego;
            document.getElementById('editGuideId').value = currentGuideData.guide_id;
            restoreOverlay.style.display = 'flex';
        };

        document.getElementById('closeArchiveBtn').onclick = () => archiveOverlay.style.display = 'none';
        document.getElementById('closeRestoreBtn').onclick = () => restoreOverlay.style.display = 'none';

        function cancelEdit() {
            archiveOverlay.style.display = 'none';
            restoreOverlay.style.display = 'none';
            if(currentGuideData) renderViewMode();
        }
        document.getElementById('cancelEditBtn').onclick = cancelEdit;

        const searchInput = document.getElementById('searchInput');
        const filterBtnText = document.getElementById('filterBtnText');
        const filterMenu = document.getElementById('filterMenu');
        let currentCategory = '';
        
        document.getElementById('filterBtn').onclick = (e) => { e.stopPropagation(); filterMenu.style.display = filterMenu.style.display === 'block' ? 'none' : 'block'; };
        window.onclick = (e) => { 
            filterMenu.style.display = 'none'; 
            if (e.target == modal) modal.style.display = "none";
        };
        searchInput.oninput = () => fetchFilteredData();

        function applyFilter(element, cat) { 
            currentCategory = cat; 
            filterBtnText.innerText = cat === '' ? 'Filter' : cat;
            document.querySelectorAll('.filter-item').forEach(item => item.classList.remove('active-filter'));
            element.classList.add('active-filter');
            fetchFilteredData();
        }

        function fetchFilteredData() {
            const searchTerm = searchInput.value;
            const selectedStatus = document.querySelector('input[name="st"]:checked').value;
            
            const encodedCategory = encodeURIComponent(currentCategory);

            fetch(`troubleshooting.php?ajax_search=1&search=${searchTerm}&category=${encodedCategory}&status=${selectedStatus}`)
                .then(res => res.text()).then(data => { tableBody.innerHTML = data; });
        }

        const modal = document.getElementById("addGuideModal");
        document.getElementById("openModalBtn").onclick = () => modal.style.display = "flex";
    </script>
</body>
</html>