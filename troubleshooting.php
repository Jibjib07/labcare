<?php include 'includes/db.php'; ?>
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

    <div class="main-content">
        
        <div class="page-header">
            <h1>Troubleshooting Management</h1>
            <p>Diagnose technical issues and access guided solutions for common hardware problems.</p>
        </div>

        <div class="troubleshoot-layout">
            
            <div class="panel white-panel left-list-panel">
                <div class="panel-header-row">
                    <h3>Existing Guide List</h3>
                    <button class="btn-green-add"><i class="fas fa-plus-circle"></i> Add</button>
                </div>

                <div class="search-filter-row">
                    <input type="text" class="search-input" placeholder="Search a guide">
                    <div class="filter-dropdown">
                        <span>Filter</span> <i class="fas fa-filter"></i>
                    </div>
                </div>

                <div class="status-toggle-row">
                    <label class="radio-label">
                        <input type="radio" name="status_filter" value="available" checked> Available
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="status_filter" value="archived"> Archived
                    </label>
                </div>

                <div class="table-container">
                    <table class="guide-table">
                        <thead>
                            <tr>
                                <th>Issue Title</th>
                                <th>Category</th>
                                <th>Date Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="active-row">
                                <td>System Overheating & Shutdown</td>
                                <td>Hardware Problem</td>
                                <td>01/11/2022</td>
                            </tr>
                            <tr>
                                <td>Monitor having blue screen</td>
                                <td>Software / OS Issues</td>
                                <td>01/11/2022</td>
                            </tr>
                            <tr>
                                <td>Computer won't power on</td>
                                <td>Power & Connection Errors</td>
                                <td>01/11/2022</td>
                            </tr>
                            <tr>
                                <td>Mouse is not detecting</td>
                                <td>Peripheral Device Issues</td>
                                <td>01/11/2022</td>
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

            <div class="panel white-panel right-detail-panel">
                <div class="panel-header-row">
                    <h3>Guide Full Details</h3>
                    <div class="action-buttons">
                        <button class="btn-edit"><i class="fas fa-pen"></i> Edit</button>
                        <button class="btn-archive"><i class="fas fa-box-archive"></i> Archive</button>
                    </div>
                </div>

                <div class="detail-content">
                    
                    <div class="detail-group">
                        <label>Issue Title:</label>
                        <div class="detail-box">System Overheating & Shutdown</div>
                    </div>

                    <div class="detail-group">
                        <label>Summary Description:</label>
                        <div class="detail-box">
                            The computer makes excessive noise during operation and abruptly powers off after 10-15 minutes of use due to thermal throttling.
                        </div>
                    </div>

                    <div class="detail-group">
                        <label>Possible Causes:</label>
                        <div class="detail-box">
                            <ul class="bullet-list">
                                <li>Accumulated dust clogging the heatsink.</li>
                                <li>Dried out thermal paste on the CPU.</li>
                                <li>Broken chassis fan blade causing vibration.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="detail-group">
                        <label>Step-by-Step Solution:</label>
                        <div class="detail-box">
                            <ul class="bullet-list">
                                <li>Open the case and clean dust using compressed air.</li>
                                <li>Check if all fans are spinning freely.</li>
                                <li>Remove the CPU cooler and re-apply fresh thermal paste.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="detail-group">
                        <label>Preventive Measures:</label>
                        <div class="detail-box">
                            Perform deep cleaning of internal components every semester to prevent dust buildup.
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script src="js/sidebar.js?v=<?php echo time(); ?>"></script>
</body>
</html>