<?php include 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Management - LabCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/maintenance_history.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>History Management</h1>
            <p>Centralized audit trail for maintenance logs, room archives, and retired assets.</p>
        </div>

        <div class="history-layout">
            
            <div class="panel white-panel left-history-panel">
                
                <div class="panel-header-row">
                    <h3>History Lists</h3>
                    <button class="btn-export"><i class="fas fa-file-export"></i> Export Data</button>
                </div>

                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Type a serial number....">
                </div>

                <div class="history-tabs">
                    <button class="tab-btn active">Maintenance Logs</button>
                    <button class="tab-btn">System Archives</button>
                    <button class="tab-btn">Asset Retirement</button>
                </div>

                <div class="table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Serial Number</th>
                                <th>Latest Maintenance Date</th>
                                <th>Room Number</th>
                                <th>Device ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="active-row">
                                <td>1215828</td>
                                <td>11/20/2025</td>
                                <td>104</td>
                                <td>PC-01</td>
                            </tr>
                            <?php 
                            $devices = [
                                ['5824785', '11/20/2025', '104', 'D-01'],
                                ['7851286', '11/20/2025', '104', 'PC-03'],
                                ['8547617', '11/20/2025', '104', 'PC-04'],
                                ['8574125', '11/20/2025', '104', 'PC-05'],
                                ['9872456', '11/20/2025', '104', 'PC-06'],
                                ['5782147', '11/20/2025', '104', 'PC-07'],
                                ['8571257', '11/20/2025', '104', 'PC-08'],
                                ['0125785', '11/20/2025', '104', 'PC-09'],
                                ['0025482', '11/20/2025', '104', 'PC-010'],
                            ];
                            foreach($devices as $dev): ?>
                            <tr>
                                <td><?= $dev[0] ?></td>
                                <td><?= $dev[1] ?></td>
                                <td><?= $dev[2] ?></td>
                                <td><?= $dev[3] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <span class="page-nav">< Previous</span>
                    <span class="page-num active">1</span>
                    <span class="page-num">2</span>
                    <span class="page-num">3</span>
                    <span class="page-nav">Next ></span>
                </div>
            </div>

            <div class="panel white-panel right-timeline-panel">
                
                <div class="panel-header-row">
                    <h3>Computer Unit Maintenance Timeline</h3>
                    <button class="btn-condemn"><i class="fas fa-trash-alt"></i> Condemn</button>
                </div>

                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Type a date...">
                </div>

                <div class="timeline-container">
                    <table class="timeline-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reported by</th>
                                <th>Affected</th>
                                <th>Remarks</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="timeline-row">
                                <td>11/19/2025</td>
                                <td>Jojo Pineda</td>
                                <td>
                                    <ul class="affected-list">
                                        <li>Monitor</li>
                                        <li>System Unit</li>
                                    </ul>
                                </td>
                                <td>System Grounded, Monitor Dead Pixels</td>
                                <td>
                                    <div class="status-cell">
                                        <span class="badge purple">Pending</span>
                                        <button class="btn-action"><i class="fas fa-hand-pointer"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="timeline-row">
                                <td>11/18/2025</td>
                                <td>Juan Dela Cruz</td>
                                <td>Monitor</td>
                                <td>Monitor Fixed</td>
                                <td><span class="badge green">Resolved</span></td>
                            </tr>
                            <tr class="timeline-row">
                                <td>11/17/2025</td>
                                <td>Jojo Pineda</td>
                                <td>Monitor</td>
                                <td>Mouse not working</td>
                                <td><span class="badge yellow">For Repair</span></td>
                            </tr>
                            <tr class="timeline-row">
                                <td>11/16/2025</td>
                                <td>Juan Dela Cruz</td>
                                <td>Keyboard</td>
                                <td>Missing keys</td>
                                <td><span class="badge green">Resolved</span></td>
                            </tr>
                            <tr class="timeline-row">
                                <td>11/15/2025</td>
                                <td>Juan Dela Cruz</td>
                                <td>AVR</td>
                                <td>Grounded</td>
                                <td><span class="badge green">Resolved</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <span class="page-nav">< Previous</span>
                    <span class="page-num active">1</span>
                    <span class="page-num">2</span>
                    <span class="page-num">3</span>
                    <span class="page-nav">Next ></span>
                </div>
            </div>

        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
</body>
</html>