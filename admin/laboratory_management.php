<?php
include '../includes/admin_auth.php';
include '../includes/db.php';

$add_error = '';
$edit_error = '';

// --- ADD NEW LABORATORY LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_lab'])) {
    $lab_name = $conn->real_escape_string(trim($_POST['lab_name']));
    $raw_room = trim($_POST['lab_room']);

    // Check if Not Applicable was checked
    if (strtoupper($raw_room) === 'N/A') {
        $lab_room = $lab_name; // As per your previous rule
    } else {
        $lab_room = "Room " . $raw_room; // Autogenerate the prefix
    }

    $check_query = "SELECT * FROM laboratories 
                    WHERE (lab_name = '$lab_name' OR lab_room = '$lab_room') 
                    AND LOWER(lab_status) = 'active'";

    $check_result = $conn->query($check_query);

    if ($check_result && $check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        if (strtolower($row['lab_name']) === strtolower($lab_name)) {
            $add_error = "The laboratory name '$lab_name' is already taken.";
        } else {
            $add_error = "Room Number '$lab_room' is already in use.";
        }
    } else {
        $insert_query = "INSERT INTO laboratories (lab_name, lab_room, lab_status) VALUES ('$lab_name', '$lab_room', 'Active')";
        if ($conn->query($insert_query)) {
            header("Location: laboratory_management.php?success=lab_added");
            exit();
        } else {
            $add_error = "Database Error: " . $conn->error;
        }
    }
}

// --- EDIT LABORATORY LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_lab'])) {
    
    // CRITICAL FIX: These two variables were missing from the previous version
    $lab_id = $conn->real_escape_string($_POST['edit_lab_id']);
    $original_room = $conn->real_escape_string($_POST['original_room_number']);
    
    $new_name = $conn->real_escape_string(trim($_POST['edit_room_name']));
    $raw_new_room = trim($_POST['edit_room_number']);

    // Handle "Not Applicable" logic
    if (strtoupper($raw_new_room) === 'N/A') {
        $new_room = $new_name;
    } else {
        $new_room = "Room " . $raw_new_room; // Autogenerate the prefix
    }

    // Check for duplicates, excluding the current room
    $check_query = "SELECT * FROM laboratories 
                    WHERE (lab_name = '$new_name' OR lab_room = '$new_room') 
                    AND LOWER(lab_status) = 'active' 
                    AND lab_id != '$lab_id'";

    $check_result = $conn->query($check_query);

    if ($check_result && $check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        if (strtolower($row['lab_name']) === strtolower($new_name)) {
            $edit_error = "The name '$new_name' is already taken by another room.";
        } else {
            $edit_error = "Room Number '$new_room' is already assigned elsewhere.";
        }
    } else {
        // Execute the update
        $update_query = "UPDATE laboratories SET lab_name = '$new_name', lab_room = '$new_room' WHERE lab_id = '$lab_id'";

        if ($conn->query($update_query)) {
            // If the room number changed, cascade the update to units and assets
            if ($original_room !== $new_room) {
                $conn->query("UPDATE units SET lab_room = '$new_room' WHERE lab_room = '$original_room'");
                $conn->query("UPDATE assets SET lab_room = '$new_room' WHERE lab_room = '$original_room'");
            }
            header("Location: laboratory_management.php?success=lab_updated");
            exit();
        } else {
            $edit_error = "Database Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Management - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/laboratory_management.css?v=<?php echo time(); ?>">

</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Room Management</h1>
            <p>Monitor room deployment, resource counts, and asset archival states across the campus.</p>
        </div>

        <div class="mobile-lab-layout">
            <div class="mobile-actions">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search room..." id="mobileLabSearchInput" onkeyup="searchLaboratories()">
                </div>
                <button class="btn-green-add mobile-add-btn" onclick="openModal('addLabModal')">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <div class="mobile-stats-grid">
                <div class="m-stat-card green">
                    <div class="m-card-content">
                        <h2 id="m-val-working">0</h2>
                        <span>Working Sets</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-check-circle"></i></div>
                </div>

                <div class="m-stat-card yellow">
                    <div class="m-card-content">
                        <h2 id="m-val-repair">0</h2>
                        <span>For Repair Sets</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-wrench"></i></div>
                </div>

                <div class="m-stat-card red">
                    <div class="m-card-content">
                        <h2 id="m-val-condemned">0</h2>
                        <span>For Condemn Sets</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-trash-alt"></i></div>
                </div>

                <div class="m-stat-card dark">
                    <div class="m-card-content">
                        <h2 id="m-val-total">0</h2>
                        <span>Total Computer Sets</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-desktop"></i></div>
                </div>
            </div>

            <h3 class="section-title">Room List</h3>
            <div class="mobile-room-list">
                <?php
                // Run the exact same query as the desktop view to fetch the active labs
                $mobile_query = "SELECT 
                    l.lab_id, 
                    l.lab_name, 
                    l.lab_room, 
                    l.lab_status, 
                    COUNT(u.set_ID) as total_units 
                  FROM laboratories l
                  LEFT JOIN units u ON l.lab_room = u.lab_room
                  WHERE l.lab_status != 'Archived' 
                  GROUP BY l.lab_id, l.lab_name, l.lab_room, l.lab_status
                  ORDER BY l.lab_room ASC";

                $mobile_result = $conn->query($mobile_query);

                if ($mobile_result && $mobile_result->num_rows > 0):
                    while ($row = $mobile_result->fetch_assoc()):
                        $activeClass = (strtolower($row['lab_status']) === 'active') ? 'active' : '';
                        $units = $row['total_units'];
                ?>
                        <div class="m-room-card <?= $activeClass ?>" onclick="selectRoom(this, '<?= htmlspecialchars($row['lab_room']) ?>')">

                            <div class="m-room-info">
                                <h4><?= htmlspecialchars($row['lab_name']) ?></h4>
                                <?php if (strtolower($row['lab_room']) !== strtolower($row['lab_name'])): ?>
                                    <span class="room-badge"><?= htmlspecialchars($row['lab_room']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="m-room-actions">
                                <button class="action-btn edit-btn"
                                    data-id="<?= htmlspecialchars($row['lab_id']) ?>"
                                    data-name="<?= htmlspecialchars($row['lab_name']) ?>"
                                    data-room="<?= htmlspecialchars($row['lab_room']) ?>"
                                    data-units="<?= $units ?>"
                                    onclick="event.stopPropagation(); openEditModal(this)">
                                    <i class="fas fa-pen"></i>
                                </button>

                                <button type="button" class="action-btn view-btn"
                                    onclick="event.stopPropagation(); window.location.href='assets_management.php?lab_id=<?= htmlspecialchars($row['lab_id']) ?>'">
                                    <i class="fas fa-hand-pointer"></i>
                                </button>

                                <button type="button" class="action-btn delete-btn" onclick="event.stopPropagation(); requestArchiveLab(
            '<?= isset($row['lab_id']) ? $row['lab_id'] : 0 ?>', 
            '<?= addslashes(htmlspecialchars($row['lab_name'] ?? 'Unknown')) ?>', 
            '<?= addslashes(htmlspecialchars($row['lab_room'] ?? 'Unknown')) ?>'
        )">
                                    <i class="fas fa-archive"></i>
                                </button>
                            </div>
                        </div>
                    <?php
                    endwhile;
                else:
                    ?>
                    <div class="empty-state" style="padding: 20px; text-align: center; color: #666; background: white; border-radius: 8px;">
                        No active rooms found. Click '+' to create one.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="desktop-lab-layout">
            <div class="lab-layout-grid">

                <div class="panel white-panel room-panel">
                    <div class="panel-header">
                        <h3>Campus Room List</h3>
                        <button class="btn-green-add" onclick="openModal('addLabModal')">
                            <i class="fas fa-plus-circle"></i> Add
                        </button>
                    </div>

                    <div class="search-wrapper">
                        <input type="text"
                            id="labSearchInput"
                            class="search-bar"
                            placeholder="Search a room..."
                            oninput="searchLaboratories()">
                    </div>

                    <div class="room-list-container">
                        <?php

                        $query = "SELECT 
        l.lab_id, 
        l.lab_name, 
        l.lab_room, 
        l.lab_status, 
        COUNT(u.set_ID) as total_units 
      FROM laboratories l
      LEFT JOIN units u ON l.lab_room = u.lab_room
      WHERE l.lab_status != 'Archived' 
      GROUP BY l.lab_id, l.lab_name, l.lab_room, l.lab_status
      ORDER BY l.lab_room ASC";

                        $result = $conn->query($query);

                        if ($result && $result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                // Check if active for styling
                                $activeClass = (strtolower($row['lab_status']) === 'active') ? 'active' : '';
                                $units = $row['total_units'];
                        ?>
                                <div class="room-item <?= $activeClass ?>"
                                    onclick="selectRoom(this, '<?= htmlspecialchars($row['lab_room']) ?>')"
                                    style="cursor: pointer;">

                                    <div class="room-info">
                                        <span class="lab-name"><?= htmlspecialchars($row['lab_name']) ?></span>
                                        
                                        <?php if (strtolower($row['lab_room']) !== strtolower($row['lab_name'])): ?>
                                            <span class="room-badge"><?= htmlspecialchars($row['lab_room']) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="room-actions">
                                        <button class="action-btn edit-btn"
                                            data-id="<?= htmlspecialchars($row['lab_id']) ?>" data-name="<?= htmlspecialchars($row['lab_name']) ?>"
                                            data-room="<?= htmlspecialchars($row['lab_room']) ?>"
                                            data-units="<?= $units ?>"
                                            onclick="event.stopPropagation(); openEditModal(this)">
                                            <i class="fas fa-pen"></i>
                                        </button>

                                        <button type="button" class="action-btn view-btn"
                                            onclick="event.stopPropagation(); window.location.href='assets_management.php?lab_id=<?php echo htmlspecialchars($row['lab_id']); ?>'">
                                            <i class="fas fa-hand-pointer"></i>
                                        </button>

                                        <button class="action-btn delete-btn" onclick="requestArchiveLab(
                '<?php echo isset($row['lab_id']) ? $row['lab_id'] : 0; ?>', 
                '<?php echo addslashes(htmlspecialchars($row['lab_name'] ?? 'Unknown')); ?>', 
                '<?php echo addslashes(htmlspecialchars($row['lab_room'] ?? 'Unknown')); ?>'
            )">
                                            <i class="fas fa-archive"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php
                            endwhile;
                        else:
                            ?>
                            <div class="empty-state" style="padding: 20px; text-align: center; color: #666;">
                                No room found. Click 'Add' to create one.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="right-column">
                    <div class="stats-grid">
                        <div class="stat-card green">
                            <div class="icon-circle"><i class="fas fa-check-circle"></i></div>
                            <h2 id="val-working">0</h2>
                            <span>Working Sets</span>
                        </div>

                        <div class="stat-card yellow">
                            <div class="icon-circle"><i class="fas fa-wrench"></i></div>
                            <h2 id="val-repair">0</h2>
                            <span>For Repair Sets</span>
                        </div>

                        <div class="stat-card red">
                            <div class="icon-circle"><i class="fas fa-trash-alt"></i></div>
                            <h2 id="val-condemned">0</h2>
                            <span>For Condemn Sets</span>
                        </div>

                        <div class="stacked-stats-col">
                            <div class="stat-card dark small-card">
                                <div class="icon-circle small"><i class="fas fa-desktop"></i></div>
                                <h2 id="val-total">0</h2>
                                <span>Total Computer Sets</span>
                            </div>
                            <div class="stat-card light-gray small-card">
                                <div class="icon-circle small dark-icon"><i class="fas fa-box"></i></div>
                                <h2 class="dark-text" id="val-assets">0</h2>
                                <span class="dark-text">Total Assets</span>
                            </div>
                        </div>
                    </div>
                    <div class="panel white-panel schedule-panel">
                        <div class="panel-header">
                            <h3 id="schedule-title">Select a Room</h3>
                            <div class="schedule-actions">
                                <button id="btnClearSchedule" class="btn-red-outline" onclick="clearSchedule()" style="display: none;">
                                    <i class=""></i> Clear
                                </button>
                                <button class="btn-green-solid" onclick="document.getElementById('scheduleInput').click()">Upload</button>
                            </div>
                            <input type="file" id="scheduleInput" accept="image/*" style="display: none;">
                        </div>

                        <div id="schedule-display">
                            <p>Please select a room.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="addLabModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Add New Room</h3>
            </div>

            <div class="modal-body">
                <?php if (!empty($add_error)): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $add_error; ?></span>
                    </div>
                <?php endif; ?>

                <form id="addLabForm" method="POST" action="">

                    <div class="form-group">
                        <label>Room Name</label>
                        <input type="text" name="lab_name" class="modal-input" placeholder="e.g. Computer Lab 1" required>
                    </div>

                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label>Room Number</label>
                            <label style="font-size: 12px; color: #888; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                <input type="checkbox" id="no_room_number" onclick="toggleRoomNumber(this)"> Not Applicable
                            </label>
                        </div>
                        <input type="text" name="lab_room" id="lab_room_input" class="modal-input" placeholder="e.g. 104" required>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addLabModal')">Cancel</button>

                <button type="submit" name="create_lab" form="addLabForm" class="btn-create">
                    <i class="fas fa-plus-circle"></i> Create
                </button>
            </div>
        </div>
    </div>

    <div id="editLabModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header header-with-actions">
                <h3>Edit Room Details</h3>
                <div class="header-actions">

                </div>
            </div>

            <div class="modal-body">
                <?php if (!empty($edit_error)): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $edit_error; ?></span>
                    </div>
                <?php endif; ?>

                <form id="editLabForm" method="POST" action="">
                    <input type="hidden" id="edit_lab_id" name="edit_lab_id">
                    <input type="hidden" name="original_room_number" id="original_room_number">

                    <div class="form-group">
                        <label>Room Name</label>
                        <input type="text" name="edit_room_name" id="edit_room_name" class="modal-input" required>
                    </div>

                    <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label>Room Number</label>
                        <label style="font-size: 12px; color: #888; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" id="edit_no_room_number" onclick="toggleEditRoomNumber(this)"> Not Applicable
                        </label>
                    </div>
                    <input type="text" name="edit_room_number" id="edit_room_number" class="modal-input" required>
                </div>

                    <div class="form-group">
                        <label>Total Sets</label>
                        <input type="number" id="edit_total_units" class="modal-input" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editLabModal')">Cancel</button>
                <button type="submit" name="edit_lab" form="editLabForm" class="btn-create"><i class="fas fa-pen"></i> Edit</button>
            </div>
        </div>
    </div>



    <div id="archiveLabModal" class="modal-overlay" style="display: none;">
        <div class="modal-container archive-modal-width">
            <div class="modal-header">
                <h3>Archive Room?</h3>
            </div>
            <div class="modal-body">
                <p class="archive-warning-text">
                    Are you sure you want to archive <strong id="archive_room_name_display">[Room Name]</strong>? 
                    All equipment must be redeployed or retired before a room can be archived.
                </p>
                <form id="archiveLabForm">
                    <input type="hidden" id="archive_lab_id">

                    <div class="modal-grid-layout">
                        <div class="left-col">
                            <div class="form-group">
                                <label>Room Name</label>
                                <input type="text" id="archive_room_name" class="modal-input readonly-input" readonly>
                            </div>
                            <div class="form-group">
                                <label>Room Number</label>
                                <input type="text" id="archive_room_number" class="modal-input readonly-input" readonly>
                            </div>
                            <div class="form-group">
                                <label>Total Sets</label>
                                <input type="text" id="archive_total_units" class="modal-input readonly-input" readonly>
                            </div>
                        </div>
                        <div class="right-col">
                            <div class="form-group">
                                <label>Reason for Archiving:</label>
                                <div class="checkbox-group" id="archive_reasons_group">
                                    <label class="checkbox-item"><input type="checkbox" value="Permanent Decommision"> Permanent Decommision</label>
                                    <label class="checkbox-item"><input type="checkbox" value="Room Repurposed"> Room Repurposed</label>
                                    <label class="checkbox-item"><input type="checkbox" value="Combined with another Lab"> Combined with another Lab</label>
                                    <label class="checkbox-item"><input type="checkbox" value="Other"> Other (Please specify...)</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Remarks:</label>
                                <textarea id="archive_remarks" class="modal-input textarea-input" placeholder="Provide specific details for the audit log..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('archiveLabModal')">Cancel</button>
                <button type="button" class="btn-archive" onclick="submitArchiveLab()">
                    <i class="fas fa-archive"></i> Archive
                </button>
            </div>
        </div>
    </div>

    <div id="archiveBlockedModal" class="modal-overlay" style="display: none;">
        <div class="modal-container" style="max-width: 500px;">
            <div class="modal-header">
                <h3 style="color: #e53935;"><i class="fas fa-exclamation-triangle"></i> Archive Blocked: Active Assets Detected</h3>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <p style="font-size: 14px; color: #555; line-height: 1.5;">
                    To maintain a comprehensive audit trail, all equipment must be redeployed or retired before a laboratory can be archived. Please transfer the remaining assets to another active lab first.
                </p>
                <div style="display: flex; justify-content: space-between; background: #f9f9f9; padding: 15px; border-radius: 8px; margin-top: 15px; border: 1px solid #eee;">
                    <div><span style="font-size: 12px; color: #888;">Room Number</span><br><strong id="blocked_room_number" style="font-size: 16px; color: #333;">---</strong></div>
                    <div><span style="font-size: 12px; color: #888;">Total Sets</span><br><strong id="blocked_total_units" style="font-size: 16px; color: #333;">0</strong></div>
                    <div><span style="font-size: 12px; color: #888;">Total Assets</span><br><strong id="blocked_total_assets" style="font-size: 16px; color: #333;">0</strong></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('archiveBlockedModal')">Cancel</button>
                <a href="#" id="btn_redirect_transfer" class="btn-transfer" style="text-decoration: none; padding: 8px 20px; background-color: #4caf50; color: white; border-radius: 4px;"><i class="fas fa-exchange-alt"></i> Transfer</a>
            </div>
        </div>
    </div>

    <div id="scheduleModal" class="modal-overlay" onclick="closeModal()">
        <span class="close-modal">&times;</span>
        <div class="modal-content" onclick="event.stopPropagation()">
            <img id="modalImg" src="" alt="Full Schedule">
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/laboratory_management.js?v=<?php echo time(); ?>"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script> -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // If Add failed, open the Add modal automatically
            <?php if (!empty($add_error)): ?>
                document.getElementById('addLabModal').style.display = 'flex';
            <?php endif; ?>

            // If Edit failed, open the Edit modal automatically
            <?php if (!empty($edit_error)): ?>
                document.getElementById('editLabModal').style.display = 'flex';
                // Keep the values the user typed so they don't have to start over
                document.getElementById('edit_lab_id').value = "<?php echo isset($_POST['edit_lab_id']) ? htmlspecialchars($_POST['edit_lab_id']) : ''; ?>";
                document.getElementById('original_room_number').value = "<?php echo isset($_POST['original_room_number']) ? htmlspecialchars($_POST['original_room_number']) : ''; ?>";
                document.getElementById('edit_room_name').value = "<?php echo isset($_POST['edit_room_name']) ? htmlspecialchars($_POST['edit_room_name']) : ''; ?>";
                document.getElementById('edit_room_number').value = "<?php echo isset($_POST['edit_room_number']) ? htmlspecialchars($_POST['edit_room_number']) : ''; ?>";
            <?php endif; ?>
        });
    </script>
</body>

</html>