<?php
require '../includes/staff_auth.php';
include '../includes/db.php';

$add_error = '';
$edit_error = '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/laboratory_management.css?v=<?php echo time(); ?>">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Room Management</h1>
            <p>Monitor room deployment, resource counts, and workstation status.</p>
        </div>

        <div class="mobile-lab-layout">
            <div class="mobile-actions">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search room..." id="mobileLabSearchInput" onkeyup="searchLaboratories()">
                </div>
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
                ?>
                        <div class="m-room-card <?= $activeClass ?>" onclick="selectRoom(this, '<?= htmlspecialchars($row['lab_room']) ?>')">
                            <div class="m-room-info">
                                <h4><?= htmlspecialchars($row['lab_name']) ?></h4>
                                <?php if (strtolower($row['lab_room']) !== strtolower($row['lab_name'])): ?>
                                    <span class="room-badge"><?= htmlspecialchars($row['lab_room']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="m-room-actions">
                                <button type="button" class="action-btn view-btn"
                                    onclick="event.stopPropagation(); window.location.href='assets_management.php?lab_id=<?= htmlspecialchars($row['lab_id']) ?>'">
                                    <i class="fas fa-hand-pointer"></i>
                                </button>
                            </div>
                        </div>
                    <?php
                    endwhile;
                else:
                    ?>
                    <div class="empty-state">No active rooms found.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="desktop-lab-layout">
            <div class="lab-layout-grid">

                <div class="panel white-panel room-panel">
                    <div class="panel-header">
                        <h3>Campus Room List</h3>
                    </div>

                    <div class="search-wrapper">
                        <input type="text" id="labSearchInput" class="search-bar" placeholder="Search a room..." oninput="searchLaboratories()">
                    </div>

                    <div class="room-list-container">
                        <?php
                        $query = "SELECT 
                            l.lab_id, l.lab_name, l.lab_room, l.lab_status, 
                            COUNT(u.set_ID) as total_units 
                            FROM laboratories l
                            LEFT JOIN units u ON l.lab_room = u.lab_room
                            WHERE l.lab_status != 'Archived' 
                            GROUP BY l.lab_id, l.lab_name, l.lab_room, l.lab_status
                            ORDER BY l.lab_room ASC";

                        $result = $conn->query($query);

                        if ($result && $result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                $activeClass = (strtolower($row['lab_status']) === 'active') ? 'active' : '';
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
                                        <button type="button" class="action-btn view-btn"
                                            onclick="event.stopPropagation(); window.location.href='assets_management.php?lab_id=<?php echo htmlspecialchars($row['lab_id']); ?>'">
                                            <i class="fas fa-hand-pointer"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php
                            endwhile;
                        else:
                            ?>
                            <div class="empty-state">No rooms found.</div>
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
                            </div>

                        <div id="schedule-display">
                            <p>Please select a room.</p>
                        </div>
                    </div>
                </div>
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
</body>

</html>