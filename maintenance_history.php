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
            <h1 class="page-title">History Management</h1>
            <p>Centralized audit trail for maintenance logs, room archives, and retired assets.</p>
        </div>

        <div class="view-section">
            <div class="split-layout">

                <div class="panel white-panel left-panel">

                    <div class="section-header-row">
                        <h3>History Lists</h3>
                        <button class="btn-green-export"><i class="fas fa-file-export"></i> Export Data</button>
                    </div>

                    <div class="search-filter-row">
                        <input type="text" class="search-input" placeholder="Type a serial number....">
                    </div>

                    <div class="toggle-container">
                        <button class="toggle-link active" onclick="switchHistoryTab('maintenance', this)">Maintenance Logs</button>
                        <button class="toggle-link" onclick="switchHistoryTab('archives', this)">System Archives</button>
                        <button class="toggle-link" onclick="switchHistoryTab('retirement', this)">Asset Retirement</button>
                    </div>

                    <div class="table-container">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Property ID</th>
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
                                <tr>
                                    <td>5824785</td>
                                    <td>11/20/2025</td>
                                    <td>104</td>
                                    <td>D-01</td>
                                </tr>
                                <tr>
                                    <td>7851286</td>
                                    <td>11/20/2025</td>
                                    <td>104</td>
                                    <td>PC-03</td>
                                </tr>
                                <tr>
                                    <td>8547617</td>
                                    <td>11/20/2025</td>
                                    <td>104</td>
                                    <td>PC-04</td>
                                </tr>
                                <tr>
                                    <td>8574125</td>
                                    <td>11/20/2025</td>
                                    <td>104</td>
                                    <td>PC-05</td>
                                </tr>
                                <tr>
                                    <td>9872456</td>
                                    <td>11/20/2025</td>
                                    <td>104</td>
                                    <td>PC-06</td>
                                </tr>
                                <tr>
                                    <td>5782147</td>
                                    <td>11/20/2025</td>
                                    <td>104</td>
                                    <td>PC-07</td>
                                </tr>
                                <tr>
                                    <td>8571257</td>
                                    <td>11/20/2025</td>
                                    <td>104</td>
                                    <td>PC-08</td>
                                </tr>
                                <tr>
                                    <td>0125785</td>
                                    <td>11/20/2025</td>
                                    <td>104</td>
                                    <td>PC-09</td>
                                </tr>
                                <tr>
                                    <td>0025482</td>
                                    <td>11/20/2025</td>
                                    <td>104</td>
                                    <td>PC-010</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-row">
                        <span class="page-nav">
                            < Previous</span>
                                <span class="page-num active">1</span>
                                <span class="page-num">2</span>
                                <span class="page-num">3</span>
                                <span class="page-nav">Next ></span>
                    </div>
                </div>

                <div class="panel white-panel right-panel">

                    <div class="section-header-row">
                        <h3>Computer Unit Maintenance Timeline</h3>
                        <button class="btn-red-condemn"><i class="fas fa-trash-alt"></i> Condemn</button>
                    </div>

                    <div class="search-filter-row">
                        <input type="text" class="search-input" placeholder="Type a date...">
                    </div>

                    <div class="table-container">
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
                                <tr>
                                    <td>11/19/2025</td>
                                    <td>Jojo Pineda</td>
                                    <td>
                                        <ul class="table-list">
                                            <li>Monitor</li>
                                            <li>System Unit</li>
                                        </ul>
                                    </td>
                                    <td>System Grounded, Monitor Dead Pixels</td>
                                    <td>
                                        <div class="status-action-row">
                                            <span class="badge purple">Pending</span>
                                            <button class="icon-btn"><i class="fas fa-hand-pointer"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>11/18/2025</td>
                                    <td>Juan Dela Cruz</td>
                                    <td>Monitor</td>
                                    <td>Monitor Fixed</td>
                                    <td><span class="badge green">Resolved</span></td>
                                </tr>
                                <tr>
                                    <td>11/17/2025</td>
                                    <td>Jojo Pineda</td>
                                    <td>Monitor</td>
                                    <td>Mouse not working</td>
                                    <td><span class="badge orange">For Repair</span></td>
                                </tr>
                                <tr>
                                    <td>11/16/2025</td>
                                    <td>Juan Dela Cruz</td>
                                    <td>Keyboard</td>
                                    <td>Missing keys</td>
                                    <td><span class="badge green">Resolved</span></td>
                                </tr>
                                <tr>
                                    <td>11/15/2025</td>
                                    <td>Juan Dela Cruz</td>
                                    <td>AVR</td>
                                    <td>Grounded</td>
                                    <td><span class="badge green">Resolved</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-row">
                        <span class="page-nav">
                            < Previous</span>
                                <span class="page-num active">1</span>
                                <span class="page-num">2</span>
                                <span class="page-num">3</span>
                                <span class="page-nav">Next ></span>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="js/maintenance_history.js?v=<?php echo time(); ?>"></script>
</body>

</html>