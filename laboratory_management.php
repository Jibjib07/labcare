<?php include 'includes/db.php'; ?>
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
                <button class="btn-green-add mobile-add-btn" id="openModalBtnMobile">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <div class="mobile-stats-grid">
                <div class="m-stat-card green">
                    <div class="m-card-content"><h2>42</h2><span>Total Working Units</span></div>
                    <div class="m-card-icon"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="m-stat-card yellow">
                    <div class="m-card-content"><h2>8</h2><span>Total For Repair Units</span></div>
                    <div class="m-card-icon"><i class="fas fa-wrench"></i></div>
                </div>
                <div class="m-stat-card red">
                    <div class="m-card-content"><h2>2</h2><span>Total Condemned Units</span></div>
                    <div class="m-card-icon"><i class="fas fa-trash-alt"></i></div>
                </div>
                 <div class="m-stat-card dark">
                    <div class="m-card-content"><h2>52</h2><span>Total Computer Units</span></div>
                    <div class="m-card-icon"><i class="fas fa-desktop"></i></div>
                </div>
            </div>

            <h3 class="section-title">Room List</h3>
            <div class="mobile-room-list">
                <?php 
                $labs = [
                    ['name' => 'Computer Lab 1', 'room' => '104', 'active' => True],
                    ['name' => 'Computer Lab 2', 'room' => '105', 'active' => false],
                    ['name' => 'Computer Lab 3', 'room' => '106', 'active' => false],
                    ['name' => 'Computer Lab 4', 'room' => '107', 'active' => false],
                    ['name' => 'Computer Lab 5', 'room' => '108', 'active' => false],
                    ['name' => 'Computer Lab 6', 'room' => '109', 'active' => false],
                    ['name' => 'Computer Lab 7', 'room' => '110', 'active' => false],
                ];
                foreach($labs as $lab): ?>
                <div class="m-room-card">
                    <div class="m-room-info">
                        <h4><?= $lab['name'] ?></h4>
                        <span class="room-badge">Room <?= $lab['room'] ?></span>
                    </div>
                    <div class="m-room-actions">
                        <button class="m-action-btn"><i class="fas fa-chevron-right"></i></button>
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
                        <button class="btn-green-add" id="openModalBtn">
                            <i class="fas fa-plus-circle"></i> Add
                        </button>
                    </div>
                    
                    <input type="text" class="search-bar" placeholder="Search computer lab room...">

                    <div class="room-list-container">
                        <?php foreach($labs as $lab): 
                            $activeClass = $lab['active'] ? 'active' : '';
                        ?>
                        <div class="room-item <?= $activeClass ?>">
                            <div class="room-info">
                                <span class="lab-name"><?= $lab['name'] ?></span>
                                <span class="room-badge">Room <?= $lab['room'] ?></span>
                            </div>
                            <div class="room-actions">
                                <button class="action-btn edit-btn"><i class="fas fa-pen"></i></button>
                                <button class="action-btn view-btn"><i class="fas fa-hand-pointer"></i></button>
                                <button class="action-btn delete-btn"><i class="fas fa-archive"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pagination">
                        <span class="page-nav">< Previous</span>
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
                            <h2>42</h2>
                            <span>Total<br>Working Units</span>
                        </div>
                        <div class="stat-card yellow">
                            <div class="icon-circle"><i class="fas fa-wrench"></i></div>
                            <h2>8</h2>
                            <span>Total<br>For Repair Units</span>
                        </div>
                        <div class="stat-card red">
                            <div class="icon-circle"><i class="fas fa-trash-alt"></i></div>
                            <h2>2</h2>
                            <span>Total<br>Condemned Units</span>
                        </div>
                         <div class="stacked-stats-col">
                            <div class="stat-card dark small-card">
                                <div class="icon-circle small"><i class="fas fa-desktop"></i></div>
                                <h2>52</h2>
                                <span>Total<br>Computer Units</span>
                            </div>
                            <div class="stat-card light-gray small-card">
                                <div class="icon-circle small dark-icon"><i class="fas fa-box"></i></div>
                                <h2 class="dark-text">10</h2>
                                <span class="dark-text">Total Assets</span>
                            </div>
                        </div>
                    </div>

                    <div class="panel white-panel schedule-panel">
                        <div class="panel-header">
                            <h3>Room 104 Schedule</h3>
                            <button class="btn-green-solid"><i class="fas fa-image"></i> Upload</button>
                        </div>
                        <div class="schedule-placeholder"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="addLabModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Laboratory</h3>
                <span class="close-btn" id="closeModalBtn">&times;</span>
            </div>
            <form>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Laboratory Name</label>
                        <input type="text" class="modal-input" placeholder="Ex. Computer Lab 1">
                    </div>
                    <div class="form-group">
                        <label>Room Number</label>
                        <input type="text" class="modal-input" placeholder="Ex. 104">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelModalBtn">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/laboratory_management.js?v=<?php echo time(); ?>"></script>
</body>
</html>