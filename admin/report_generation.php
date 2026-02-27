<?php include 'includes/db.php'; ?>
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
            <p>Generate and export detailed analytics on laboratory performance and asset conditions.</p>
        </div>

        <div class="report-layout">
            
            <div class="panel white-panel filter-panel">
                <div class="panel-header-row">
                    <h3>Report Filter Selection</h3>
                    <button class="btn-green"><i class="fas fa-filter"></i> Apply Filter</button>
                </div>

                <form class="filter-form">
                    <div class="form-group">
                        <label>Select Computer Laboratory Room</label>
                        <select class="form-select">
                            <option>Lab Room List</option>
                            <option>Room 104</option>
                            <option>Room 105</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date Range</label>
                        <select class="form-select">
                            <option>Select Range</option>
                            <option>This Week</option>
                            <option>This Month</option>
                            <option>Custom</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Starting Date</label>
                        <div class="date-input-wrapper">
                            <input type="date" class="form-input" placeholder="MM/DD/YYYY">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>End Date</label>
                        <div class="date-input-wrapper">
                            <input type="date" class="form-input" placeholder="MM/DD/YYYY">
                        </div>
                    </div>
                </form>
            </div>

            <div class="panel white-panel preview-panel">
                <div class="panel-header-row">
                    <h3>Maintenance Report Preview</h3>
                    <button class="btn-green"><i class="fas fa-file-export"></i> Export Data</button>
                </div>

                <div class="chart-section">
                    <h4>Units & Assets Status Distribution From Room 104</h4>
                    
                    <div class="chart-wrapper">
                        <div class="donut-chart"></div>
                        
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-label"><span class="dot green"></span> Working</div>
                                <div class="legend-value">45</div>
                            </div>
                            <div class="legend-item">
                                <div class="legend-label"><span class="dot yellow"></span> For Repair</div>
                                <div class="legend-value">1</div>
                            </div>
                            <div class="legend-item">
                                <div class="legend-label"><span class="dot red"></span> Condemned</div>
                                <div class="legend-value">1</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="summary-section">
                    <h3>Summary Table</h3>
                    <div class="table-wrapper">
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Unit Count</th>
                                    <th>Percentage %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Working</td>
                                    <td>42</td>
                                    <td>89%</td>
                                </tr>
                                <tr>
                                    <td>For Repair</td>
                                    <td>7</td>
                                    <td>10%</td>
                                </tr>
                                <tr>
                                    <td>Condemned</td>
                                    <td>1</td>
                                    <td>1%</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Total Inventory Count</td>
                                    <td>50</td>
                                    <td>100%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
</body>
</html>