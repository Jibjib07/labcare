document.addEventListener('DOMContentLoaded', function() {
    // --- UI ELEMENTS ---
    const tabBtns = document.querySelectorAll('.tab-btn');
    const subTabBtns = document.querySelectorAll('.sub-tab');
    const snapshotDateInput = document.getElementById('snapshotDate');
    const labRoomSelect = document.getElementById('labRoomSelect');
    const generateBtn = document.getElementById('generateReportBtn');
    const reportPreviewArea = document.getElementById('reportPreviewArea');
    const exportBtn = document.getElementById('exportReportBtn');
    
    const labRoomGroup = document.getElementById('labRoomGroup');
    const subTabsWrapper = document.getElementById('subTabsWrapper');
    const statusChartContainer = document.getElementById('statusChartContainer');
    const mainDonutChart = document.getElementById('mainDonutChart');
    const condemnedCountPanel = document.getElementById('condemnedCountPanel');
    const statusLegend = document.getElementById('statusLegend');

    /**
     * 1. CORE FETCH UTILITY
     */
    function fetchReportData(type, subTab) {
        const formData = new FormData();
        formData.append('action', 'generate_snapshot_report');
        formData.append('asOfDate', snapshotDateInput.value);
        formData.append('labId', labRoomSelect.value);
        formData.append('type', type);
        formData.append('subTab', subTab);

        return fetch(window.location.href, {
            method: 'POST',
            body: formData
        }).then(response => response.json());
    }

    /**
     * 2. REFRESH ROOM LIST
     */
    function refreshRoomList() {
        const selectedDate = snapshotDateInput.value;
        const activeTab = document.querySelector('.tab-btn.active').getAttribute('data-tab');
        const savedRoomId = labRoomSelect.value;

        if (!selectedDate) return;

        // Refresh visuals immediately if in Inventory (no rooms to fetch)
        if (activeTab === 'inventory') {
            updateVisualsOnly();
            return;
        }

        const formData = new FormData();
        formData.append('action', 'fetch_snapshot_rooms');
        formData.append('asOfDate', selectedDate);

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            labRoomSelect.innerHTML = '<option value="all">All Laboratories</option>';
            
            if (res.success && res.data.length > 0) {
                res.data.forEach(room => {
                    const opt = document.createElement('option');
                    opt.value = room.lab_id;
                    opt.textContent = room.lab_room; 
                    labRoomSelect.appendChild(opt);
                });

                if (savedRoomId && savedRoomId !== 'all') {
                    const exists = res.data.some(r => r.lab_id == savedRoomId);
                    if (exists) labRoomSelect.value = savedRoomId;
                }
            }
            updateVisualsOnly(); 
        });
    }

    /**
     * 3. MAIN GENERATION LOGIC
     */
    function generateFormalReport() {
        const activeTab = document.querySelector('.tab-btn.active').getAttribute('data-tab');
        const isAllLabs = labRoomSelect.value === 'all';
        
        reportPreviewArea.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Generating Formal Report...</p></div>';

        if (activeTab === 'status' || activeTab === 'condemned') {
            Promise.all([
                fetchReportData(activeTab, 'units'),
                fetchReportData(activeTab, 'assets')
            ]).then(([unitsRes, assetsRes]) => {
                const data = {
                    units: unitsRes.success ? unitsRes.data : [],
                    assets: assetsRes.success ? assetsRes.data : []
                };
                
                if (activeTab === 'status') {
                    if (isAllLabs) renderSummaryOnlyReport(data);
                    else renderDetailedRoomReport(data);
                } else if (activeTab === 'condemned') {
                    if (isAllLabs) renderCondemnedSummaryOnly(data);
                    else renderCondemnedDetailed(data);
                }
            });
        } else if (activeTab === 'inventory') {
            fetchReportData('inventory', 'units').then(res => {
                if (res.success) {
                    renderInventoryReport(res.data);
                }
            });
        }
    }

    /**
     * 4A. STATUS SUMMARY RENDERER
     */
    function renderSummaryOnlyReport(data) {
        const dateObj = new Date(snapshotDateInput.value);
        const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        const u = calculateStats(data.units);
        const a = calculateStats(data.assets);

        let html = `<div class="report-paper" style="min-height: auto;">`;
        html += getReportHeaderHTML();
        html += `
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; margin: 0; font-weight: 800;">COMPUTER LABORATORY REPORT</h2>
                <h3 style="font-size: 16px; margin: 0; font-weight: 800;">CONSOLIDATED SUMMARY</h3>
                <p style="font-size: 16px; margin: 5px 0;">All Laboratories</p>
                <p style="font-size: 15px; margin: 10px 0;">As of ${formattedDate}</p>
            </div>
            ${generateSummaryTableHTML("Computer Units Summary", u)}
            ${generateSummaryTableHTML("Assets Summary", a)}
            ${generateSignatureHTML()}
        </div>`;
        reportPreviewArea.innerHTML = html;
    }

    /**
     * 4B. STATUS DETAILED RENDERER
     */
    function renderDetailedRoomReport(data) {
        const dateObj = new Date(snapshotDateInput.value);
        const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        const labName = labRoomSelect.options[labRoomSelect.selectedIndex].text;
        const u = calculateStats(data.units);
        const a = calculateStats(data.assets);

        let html = `<div class="report-paper" style="min-height: auto;">`;
        html += getReportHeaderHTML();
        html += `
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; margin: 0; font-weight: 800;">COMPUTER LABORATORY REPORT</h2>
                <h3 style="font-size: 16px; margin: 0; font-weight: 800;">UNITS AND ASSETS STATUS</h3>
                <p style="font-size: 16px; margin: 5px 0;">Room: ${labName}</p>
                <p style="font-size: 15px; margin: 10px 0;">As of ${formattedDate}</p>
            </div>
            ${generateSummaryTableHTML("Computer Units Summary", u)}
            ${generateSummaryTableHTML("Assets Summary", a)}
            <div class="page-break" style="page-break-before: always; margin-top: 20px;"></div>
            <div class="print-only-header">${getReportHeaderHTML()}</div>
            <h4 class="table-label">Computer Units List</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead><tr><th style="width: 60%;">Tag</th><th>Status</th></tr></thead>
                <tbody>${renderListRows(data.units)}</tbody>
            </table>
            <h4 class="table-label">Facility Assets List</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead><tr><th style="width: 60%;">Tag</th><th>Status</th></tr></thead>
                <tbody>${renderListRows(data.assets)}</tbody>
            </table>
            ${generateSignatureHTML()}
        </div>`;
        reportPreviewArea.innerHTML = html;
    }

    /**
     * 4C. CONDEMNED SUMMARY RENDERER
     */
    function renderCondemnedSummaryOnly(data) {
        const dateObj = new Date(snapshotDateInput.value);
        const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        const uCount = data.units.length;
        const aCount = data.assets.length;

        let html = `<div class="report-paper" style="min-height: auto;">`;
        html += getCondemnedHeaderHTML(); 
        html += `
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; margin: 0; font-weight: 800;">COMPUTER LABORATORY REPORT</h2>
                <h3 style="font-size: 16px; margin: 0; font-weight: 800;">CONDEMNED STATUS SUMMARY</h3>
                <p style="font-size: 16px; margin: 5px 0;">All Laboratories</p>
                <p style="font-size: 15px; margin: 10px 0;">As of ${formattedDate}</p>
            </div>
            <h4 class="table-label">Computer Units Summary</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead><tr><th style="width: 40%;">Status</th><th style="width: 30%;">Count</th><th style="width: 30%;">Percentage %</th></tr></thead>
                <tbody>
                    <tr><td>Condemned</td><td style="text-align:center;">${uCount}</td><td style="text-align:center;">100%</td></tr>
                    <tr class="row-total"><td>Total</td><td style="text-align:center;">${uCount}</td><td style="text-align:center;">100%</td></tr>
                </tbody>
            </table>
            <h4 class="table-label">Assets Summary</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead><tr><th style="width: 40%;">Status</th><th style="width: 30%;">Count</th><th style="width: 30%;">Percentage %</th></tr></thead>
                <tbody>
                    <tr><td>Condemned</td><td style="text-align:center;">${aCount}</td><td style="text-align:center;">100%</td></tr>
                    <tr class="row-total"><td>Total</td><td style="text-align:center;">${aCount}</td><td style="text-align:center;">100%</td></tr>
                </tbody>
            </table>
            ${generateSignatureHTML()}
        </div>`;
        reportPreviewArea.innerHTML = html;
    }

    /**
     * 4D. CONDEMNED DETAILED RENDERER
     */
    function renderCondemnedDetailed(data) {
        const dateObj = new Date(snapshotDateInput.value);
        const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        const labName = labRoomSelect.options[labRoomSelect.selectedIndex].text;

        let html = `<div class="report-paper" style="min-height: auto;">`;
        html += getCondemnedHeaderHTML();
        html += `
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; margin: 0; font-weight: 800;">COMPUTER LABORATORY REPORT</h2>
                <h3 style="font-size: 16px; margin: 0; font-weight: 800;">CONDEMNED STATUS</h3>
                <p style="font-size: 16px; margin: 5px 0;">Room: ${labName}</p>
                <p style="font-size: 15px; margin: 10px 0;">As of ${formattedDate}</p>
            </div>
            <h4 class="table-label">Computer Units Summary</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead><tr><th style="width: 40%;">Status</th><th style="width: 30%;">Count</th><th style="width: 30%;">Percentage %</th></tr></thead>
                <tbody>
                    <tr><td>Condemned</td><td style="text-align:center;">${data.units.length}</td><td style="text-align:center;">100%</td></tr>
                    <tr class="row-total"><td>Total</td><td style="text-align:center;">${data.units.length}</td><td style="text-align:center;">100%</td></tr>
                </tbody>
            </table>
            <h4 class="table-label">Assets Summary</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead><tr><th style="width: 40%;">Status</th><th style="width: 30%;">Count</th><th style="width: 30%;">Percentage %</th></tr></thead>
                <tbody>
                    <tr><td>Condemned</td><td style="text-align:center;">${data.assets.length}</td><td style="text-align:center;">100%</td></tr>
                    <tr class="row-total"><td>Total</td><td style="text-align:center;">${data.assets.length}</td><td style="text-align:center;">100%</td></tr>
                </tbody>
            </table>
            <div class="page-break" style="page-break-before: always; margin-top: 20px;"></div>
            <div class="print-only-header">${getCondemnedHeaderHTML()}</div>
            <h4 class="table-label">Computer Units List</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead><tr><th style="width: 60%;">Tag</th><th>Status</th></tr></thead>
                <tbody>${renderListRows(data.units)}</tbody>
            </table>
            <h4 class="table-label">Facility Assets List</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead><tr><th style="width: 60%;">Tag</th><th>Status</th></tr></thead>
                <tbody>${renderListRows(data.assets)}</tbody>
            </table>
            ${generateSignatureHTML()}
        </div>`;
        reportPreviewArea.innerHTML = html;
    }

    /**
     * 4E. INVENTORY REPORT RENDERER
     */
    function renderInventoryReport(data) {
        const dateObj = new Date(snapshotDateInput.value);
        const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        
        let inStock = 0, outStock = 0;
        data.forEach(item => {
            if (item.set_status === 'In Stock') inStock++;
            else outStock++;
        });
        const total = data.length;
        const stats = {
            total, inStock, outStock,
            ip: total > 0 ? Math.round((inStock / total) * 100) : 0,
            op: total > 0 ? Math.round((outStock / total) * 100) : 0
        };

        let html = `<div class="report-paper" style="min-height: auto;">`;
        html += getReportHeaderHTML();
        html += `
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; margin: 0; font-weight: 800;">COMPUTER LABORATORY REPORT</h2>
                <h3 style="font-size: 16px; margin: 0; font-weight: 800;">SUPPLY INVENTORY SUMMARY</h3>
                <p style="font-size: 15px; margin: 10px 0;">As of ${formattedDate}</p>
            </div>
            <h4 class="table-label">Inventory Summary</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead>
                    <tr><th style="width: 40%;">Status</th><th style="width: 30%;">Count</th><th style="width: 30%;">Percentage %</th></tr>
                </thead>
                <tbody>
                    <tr><td>In Stock</td><td style="text-align:center;">${stats.inStock}</td><td style="text-align:center;">${stats.ip}%</td></tr>
                    <tr><td>Out of Stock</td><td style="text-align:center;">${stats.outStock}</td><td style="text-align:center;">${stats.op}%</td></tr>
                    <tr class="row-total"><td>Total Items</td><td style="text-align:center;">${stats.total}</td><td style="text-align:center;">100%</td></tr>
                </tbody>
            </table>
            <h4 class="table-label">Detailed Supply List</h4>
            <table class="doc-table">
                <thead><tr><th style="width: 60%;">Supply Name</th><th>Status</th></tr></thead>
                <tbody>
                    ${renderListRows(data)}
                </tbody>
            </table>
            ${generateSignatureHTML()}
        </div>`;
        reportPreviewArea.innerHTML = html;
    }

    // --- REUSABLE HTML FRAGMENTS ---

    function getReportHeaderHTML() {
        return `
            <div class="doc-header" style="display: flex; align-items: center; justify-content: center; margin-bottom: 20px; position: relative; font-family: 'Times New Roman', Times, serif;">
                <img src="../assets/cvsu_logo.png" style="width: 80px; height: 80px; position: absolute; left: 120px;">
                <div style="text-align: center;">
                    <p style="margin: 0; font-size: 14px;">Republic of the Philippines</p>
                    <h2 style="margin: 0; font-size: 20px; font-weight: 800; text-transform: uppercase;">CAVITE STATE UNIVERSITY</h2>
                    <h3 style="margin: 0; font-size: 16px; font-weight: bold;">Cavite City Campus</h3>
                    <p style="margin: 0; font-size: 13px;">Pulo II, Dalahican, Cavite City</p>
                </div>
            </div>
            <div class="doc-divider" style="border-bottom: 1.5px solid black; margin: 10px 0 20px 0;"></div>`;
    }

    function getCondemnedHeaderHTML() { return getReportHeaderHTML(); }

    function calculateStats(items) {
        let working = 0, repair = 0;
        (items || []).forEach(i => {
            if (['Working', 'In Stock'].includes(i.set_status)) working++;
            else repair++;
        });
        const total = (items || []).length;
        return {
            total, working, repair,
            wp: total > 0 ? Math.round((working / total) * 100) : 0,
            rp: total > 0 ? Math.round((repair / total) * 100) : 0
        };
    }

    function generateSummaryTableHTML(title, s) {
        return `
            <h4 class="table-label" style="margin-bottom: 5px;">${title}</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead>
                    <tr><th style="width: 40%;">Status</th><th style="width: 30%;">Count</th><th style="width: 30%;">Percentage %</th></tr>
                </thead>
                <tbody>
                    <tr><td>Working</td><td style="text-align:center;">${s.working}</td><td style="text-align:center;">${s.wp}%</td></tr>
                    <tr><td>For Repair</td><td style="text-align:center;">${s.repair}</td><td style="text-align:center;">${s.rp}%</td></tr>
                    <tr class="row-total"><td>Total</td><td style="text-align:center;">${s.total}</td><td style="text-align:center;">100%</td></tr>
                </tbody>
            </table>`;
    }

    function renderListRows(items) {
        if (!items || items.length === 0) return '<tr><td colspan="2" style="text-align:center;">No records found.</td></tr>';
        return items.map(item => `<tr><td>${item.set_tag}</td><td style="text-align:center;">${item.set_status}</td></tr>`).join('');
    }

    function generateSignatureHTML() {
        return `
            <div class="signature-section" style="margin-top: 30px; display: flex; justify-content: flex-end;">
                <div class="sig-box" style="width: 250px; text-align: center;">
                    <p style="margin-bottom: 40px; font-size: 13px;">Prepared By:</p>
                    <div class="sig-line" style="border-top: 1px solid black; font-weight: bold; text-transform: uppercase; padding-top: 5px; font-size: 13px;">COMPUTER LAB CUSTODIAN</div>
                </div>
            </div>`;
    }

    /**
     * 5. QUIET UI UPDATER
     */
    function updateVisualsOnly() {
        const activeTab = document.querySelector('.tab-btn.active').getAttribute('data-tab');
        const activeSub = (activeTab === 'inventory') ? 'units' : document.querySelector('.sub-tab.active').getAttribute('data-sub');

        fetchReportData(activeTab, activeSub).then(res => {
            if (res.success) {
                const data = res.data;

                // Handle Condemned Panel
                if (activeTab === 'condemned') {
                    const condemnedDisplay = document.getElementById('totalCondemnedCount');
                    const condemnedLabel = document.getElementById('condemnedLabel');
                    if (condemnedDisplay) condemnedDisplay.textContent = data.length;
                    if (condemnedLabel) {
                        condemnedLabel.textContent = activeSub === 'units' ? 'Number of Condemned Units' : 'Number of Condemned Assets';
                    }
                    return;
                }

                const countLabel1 = document.getElementById('legendText1');
                const countLabel2 = document.getElementById('legendText2');
                const dot1 = document.getElementById('legendColor1');
                const dot2 = document.getElementById('legendColor2');
                const countWorking = document.getElementById('countWorking');
                const countRepair = document.getElementById('countRepair');

                let posLabel = "Working";
                let negLabel = "For Repair";
                let posColor = "#4CAF50"; // Green
                let negColor = "#FFC107"; // Yellow

                // TERM & COLOR OVERRIDE FOR INVENTORY
                if (activeTab === 'inventory') {
                    posLabel = "In Stock";
                    negLabel = "Out of Stock";
                    negColor = "#F44336"; // Red
                }

                // Update UI Labels and Legend Colors
                if (countLabel1) countLabel1.textContent = posLabel;
                if (countLabel2) countLabel2.textContent = negLabel;
                if (dot1) dot1.style.backgroundColor = posColor;
                if (dot2) dot2.style.backgroundColor = negColor;

                let posCount = 0, negCount = 0;
                data.forEach(item => {
                    if (item.set_status === posLabel) posCount++;
                    else negCount++;
                });

                if(countWorking) countWorking.textContent = posCount;
                if(countRepair) countRepair.textContent = negCount;
                
                const total = posCount + negCount;
                
                // CHART POSITION UPDATE:
                // We swap the logic so that the "Negative" color (Red/Yellow) 
                // starts at 0% (top/right) and the "Positive" color (Green) follows.
                const negPercent = total > 0 ? (negCount / total) * 100 : 0;
                
                if (mainDonutChart) {
                    mainDonutChart.style.background = total > 0 
                        ? `conic-gradient(${negColor} 0% ${negPercent}%, ${posColor} ${negPercent}% 100%)`
                        : '#eee';
                }
            }
        });
    }

    // --- 6. EVENT LISTENERS ---

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const tab = this.getAttribute('data-tab');
            
            labRoomGroup.style.display = (tab === 'inventory') ? 'none' : 'block';
            subTabsWrapper.style.display = (tab === 'inventory') ? 'none' : 'flex';
            statusChartContainer.style.display = (tab === 'condemned') ? 'none' : 'flex';
            condemnedCountPanel.style.display = (tab === 'condemned') ? 'flex' : 'none';
            statusLegend.style.display = (tab === 'condemned') ? 'none' : 'block';
            
            // IF INVENTORY: Trigger direct visual update (no rooms needed)
            // IF OTHER: Refresh room list first
            if (tab === 'inventory') {
                updateVisualsOnly();
            } else {
                refreshRoomList();
            }
        });
    });

    subTabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            subTabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            updateVisualsOnly();
        });
    });

    snapshotDateInput.addEventListener('change', refreshRoomList);
    labRoomSelect.addEventListener('change', updateVisualsOnly);
    generateBtn.addEventListener('click', generateFormalReport);

    if (snapshotDateInput.value) refreshRoomList();
    if (exportBtn) exportBtn.addEventListener('click', () => window.print());
});