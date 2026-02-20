<?php
include 'includes/db.php';

// Should be auto change based on db
$labs = [
    ['name' => 'Computer Lab 1', 'room' => '104', 'active' => True, 'units' => 42],
    ['name' => 'Computer Lab 2', 'room' => '105', 'active' => false, 'units' => 40],
    ['name' => 'Computer Lab 3', 'room' => '106', 'active' => false, 'units' => 35],
    ['name' => 'Computer Lab 4', 'room' => '107', 'active' => false, 'units' => 50],
    ['name' => 'Computer Lab 5', 'room' => '108', 'active' => false, 'units' => 30],
    ['name' => 'Computer Lab 6', 'room' => '109', 'active' => false, 'units' => 45],
    ['name' => 'Computer Lab 7', 'room' => '110', 'active' => false, 'units' => 42],
];
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
            <h1>Computer Laboratory Management</h1>
            <p class="desktop-only-text">Monitor laboratory deployment, resource counts, and room archival states.</p>
            <p class="mobile-only-text">Manage lab rooms and inventory status.</p>
        </div>

        <div class="mobile-lab-layout">
            <div class="mobile-actions">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search room...">
                </div>
                <button class="btn-green-add mobile-add-btn" onclick="openModal('addLabModal')">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <div class="mobile-stats-grid">
                <div class="m-stat-card green">
                    <div class="m-card-content">
                        <h2 id="m-val-working">42</h2>
                        <span>Working Units</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-check-circle"></i></div>
                </div>

                <div class="m-stat-card yellow">
                    <div class="m-card-content">
                        <h2 id="m-val-repair">0</h2>
                        <span>For Repair Units</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-wrench"></i></div>
                </div>

                <div class="m-stat-card red">
                    <div class="m-card-content">
                        <h2 id="m-val-condemned">0</h2>
                        <span>For Condemn Units</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-trash-alt"></i></div>
                </div>

                <div class="m-stat-card dark">
                    <div class="m-card-content">
                        <h2 id="m-val-total">0</h2>
                        <span>Total Computer Units</span>
                    </div>
                    <div class="m-card-icon"><i class="fas fa-desktop"></i></div>
                </div>
            </div>

            <h3 class="section-title">Room List</h3>
            <div class="mobile-room-list">
                <?php foreach ($labs as $lab):
                    $activeClass = $lab['active'] ? 'active' : '';
                    $units = isset($lab['units']) ? $lab['units'] : 0;
                ?>
                    <div class="m-room-card <?= $activeClass ?>"
                        onclick="selectRoom(this, '<?= $lab['room'] ?>')">

                        <div class="m-room-info">
                            <h4><?= $lab['name'] ?></h4>
                            <span class="room-badge">Room <?= $lab['room'] ?></span>
                        </div>

                        <button class="action-btn edit-btn"
                            data-name="<?= $lab['name'] ?>"
                            data-room="<?= $lab['room'] ?>"
                            data-units="<?= $units ?>"
                            onclick="event.stopPropagation(); openEditModal(this)">
                            <i class="fas fa-pen"></i>
                        </button>

                        <div class="m-room-actions">
                            <button class="action-btn view-btn"
                                data-room="<?= $lab['room'] ?>"
                                onclick="event.stopPropagation()">
                                <i class="fas fa-hand-pointer"></i>
                            </button>

                            <button class="action-btn delete-btn"
                                data-name="<?= $lab['name'] ?>"
                                data-room="<?= $lab['room'] ?>"
                                data-units="<?= $units ?>"
                                onclick="event.stopPropagation(); openArchiveModal(this)">
                                <i class="fas fa-archive"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="desktop-lab-layout">
            <div class="lab-layout-grid">

                <div class="panel white-panel room-panel">
                    <div class="panel-header">
                        <h3>Computer Laboratory Room List</h3>
                        <button class="btn-green-add" onclick="openModal('addLabModal')">
                            <i class="fas fa-plus-circle"></i> Add
                        </button>
                    </div>

                    <input type="text" class="search-bar" placeholder="Search computer lab room...">

                    <div class="room-list-container">
                        <?php foreach ($labs as $lab):
                            $activeClass = $lab['active'] ? 'active' : '';
                            $units = isset($lab['units']) ? $lab['units'] : 0;
                        ?>
                            <div class="room-item <?= $activeClass ?>"
                                onclick="selectRoom(this, '<?= $lab['room'] ?>')"
                                style="cursor: pointer;">

                                <div class="room-info">
                                    <span class="lab-name"><?= $lab['name'] ?></span>
                                    <span class="room-badge">Room <?= $lab['room'] ?></span>
                                </div>

                                <div class="room-actions">
                                    <button class="action-btn edit-btn"
                                        data-name="<?= $lab['name'] ?>"
                                        data-room="<?= $lab['room'] ?>"
                                        data-units="<?= $units ?>"
                                        onclick="event.stopPropagation(); openEditModal(this)">
                                        <i class="fas fa-pen"></i>
                                    </button>

                                    <button class="action-btn view-btn"
                                        data-room="<?= $lab['room'] ?>"
                                        onclick="event.stopPropagation()">
                                        <i class="fas fa-hand-pointer"></i>
                                    </button>

                                    <button class="action-btn delete-btn"
                                        data-name="<?= $lab['name'] ?>"
                                        data-room="<?= $lab['room'] ?>"
                                        data-units="<?= $units ?>"
                                        onclick="event.stopPropagation(); openArchiveModal(this)">
                                        <i class="fas fa-archive"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="pagination">
                        <span class="page-nav">
                            < Previous</span>
                                <span class="page-num active">1</span>
                                <span class="page-num">2</span>
                                <span class="page-num">3</span>
                                <span class="page-nav">Next ></span>
                    </div>
                </div>

                <div class="right-column">
                    <div class="stats-grid">
                        <div class="stat-card green">
                            <div class="icon-circle"><i class="fas fa-check-circle"></i></div>
                            <h2 id="val-working">0</h2>
                            <span>Working Units</span>
                        </div>

                        <div class="stat-card yellow">
                            <div class="icon-circle"><i class="fas fa-wrench"></i></div>
                            <h2 id="val-repair">0</h2>
                            <span>For Repair Units</span>
                        </div>

                        <div class="stat-card red">
                            <div class="icon-circle"><i class="fas fa-trash-alt"></i></div>
                            <h2 id="val-condemned">0</h2>
                            <span>For Condemn Units</span>
                        </div>

                        <div class="stacked-stats-col">
                            <div class="stat-card dark small-card">
                                <div class="icon-circle small"><i class="fas fa-desktop"></i></div>
                                <h2 id="val-total">0</h2>
                                <span>Total Computer Units</span>
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
                            <h3 id="schedule-title">Loading Schedule...</h3>
                            <button class="btn-green-solid" onclick="document.getElementById('scheduleInput').click()">
                                <i class="fas fa-image"></i> Upload
                            </button>
                            <input type="file" id="scheduleInput" accept="image/*" style="display: none;">
                        </div>
                        <div class="schedule-placeholder" id="schedule-display"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="addLabModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Add New Computer Laboratory</h3>
            </div>

            <div class="modal-body">
                <form id="addLabForm">
                    <div class="form-group">
                        <label>Room Name</label>
                        <input type="text" class="modal-input" placeholder="e.g. Computer Lab 1">
                    </div>

                    <div class="form-group">
                        <label>Room Number</label>
                        <input type="text" class="modal-input" placeholder="e.g. 104">
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('addLabModal')">Cancel</button>
                <button class="btn-create"><i class="fas fa-plus-circle"></i> Create</button>
            </div>
        </div>
    </div>

    <div id="editLabModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header header-with-actions">
                <h3>View Computer Laboratory</h3>
                <div class="header-actions">
                    <button class="icon-btn-purple"><i class="fas fa-qrcode"></i> QR</button>
                    <button class="icon-btn-orange-solid"><i class="fas fa-box-archive"></i></button>
                </div>
            </div>

            <div class="modal-body">
                <form id="editLabForm">
                    <div class="form-group">
                        <label>Room Name</label>
                        <input type="text" id="edit_room_name" class="modal-input">
                    </div>

                    <div class="form-group">
                        <label>Room Number</label>
                        <input type="text" id="edit_room_number" class="modal-input">
                    </div>

                    <div class="form-group">
                        <label>Total Units</label>
                        <input type="number" id="edit_total_units" class="modal-input">
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('editLabModal')">Cancel</button>
                <button class="btn-create"><i class="fas fa-pen"></i> Edit</button>
            </div>
        </div>
    </div>

    <div id="archiveLabModal" class="modal-overlay">
        <div class="modal-container archive-modal-width">
            <div class="modal-header">
                <h3>Archive Computer Laboratory?</h3>
            </div>

            <div class="modal-body">
                <p class="archive-warning-text">
                    Are you sure you want to archive <strong id="archive_room_name_display">[Room Name]</strong>?
                    This will hide the laboratory from the active dashboard and Staff view.
                    All associated records and maintenance history will be preserved in the <strong>History Management</strong> section.
                </p>

                <form id="archiveLabForm">
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
                                <label>Total Units</label>
                                <input type="text" id="archive_total_units" class="modal-input readonly-input" readonly>
                            </div>
                        </div>

                        <div class="right-col">
                            <div class="form-group">
                                <label>Reason for Archiving:</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="reason" value="renovation"> Room Renovation/Maintenance
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="reason" value="semester_end"> End of Semester/Academic Year
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="reason" value="relocation"> Relocation of Assets
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="reason" value="other"> Other (Please specify...)
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Remarks:</label>
                                <textarea class="modal-input textarea-input" placeholder="Provide specific details for the audit log..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('archiveLabModal')">Cancel</button>
                <button class="btn-archive"><i class="fas fa-box-archive"></i> Archive</button>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/laboratory_management.js?v=<?php echo time(); ?>"></script>
</body>

</html>