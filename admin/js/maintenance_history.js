document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("main-search-input");

    // 1. DYNAMIC SEARCH
    if (searchInput) {
        searchInput.addEventListener("keyup", applyFilters);
    }

    // 2. UNIFIED CLICK HANDLER FOR TABLE ROWS
    document.addEventListener("click", function (e) {
        const row = e.target.closest(".selectable-row");
        if (!row) return;

        // Highlight selected row
        document.querySelectorAll(".selectable-row").forEach((r) => r.classList.remove("active-row"));
        row.classList.add("active-row");

        // Hide all right-side panels initially
        document.querySelectorAll(".history-view").forEach((v) => (v.style.display = "none"));

        const type = row.dataset.type;
        const id = row.dataset.id;

        if (type === "archive") {
            const archivePanel = document.getElementById("view-archives-details");
            if (archivePanel) archivePanel.style.display = "block";
            fetchArchiveData(id);
        } else if (type === "retired") {
            const retiredPanel = document.getElementById("view-retired-timeline");
            if (retiredPanel) {
                retiredPanel.style.display = "block";
                const retiredBody = retiredPanel.querySelector(".data-body");
                if (retiredBody) retiredBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px;"><em>Loading history...</em></td></tr>`;
            }
            fetchTimelineData(id, "retired");
        } else {
            const fullPanel = document.getElementById("view-full-timeline");
            const timelineHead = document.getElementById("timeline-thead");

            if (fullPanel) {
                fullPanel.style.display = "block";

                // Dynamic Header for Inventory vs Logs
                if (type === "inventory") {
                    if (timelineHead) {
                        timelineHead.innerHTML = `<tr><th>Date</th><th>Activity</th><th>By</th><th>Remarks</th></tr>`;
                    }
                } else {
                    if (timelineHead) {
                        timelineHead.innerHTML = `<tr><th>Date</th><th>By</th><th>Affected</th><th>Action</th><th>Remarks</th><th>Status</th></tr>`;
                    }
                }
                fetchTimelineData(id, type);
            }
        }
    });

    selectFirstVisibleRow();
});

/**
 * TOGGLE VIEW LOGIC (Text Only)
 */
function toggleNavView(btn) {
    const logNav = document.getElementById('log-nav-container');
    const retNav = document.getElementById('retirement-nav-container');
    const title = document.getElementById('nav-title');
    
    const isShowingLogs = logNav.style.display !== 'none';

    if (isShowingLogs) {
        logNav.style.display = 'none';
        retNav.style.display = 'flex';
        title.textContent = "Retirement History";
        btn.textContent = "View Activity Logs"; // Simplified text
        
        const firstRetBtn = retNav.querySelector('.main-nav-btn');
        switchHistoryTab('retired-units', firstRetBtn);
    } else {
        logNav.style.display = 'flex';
        retNav.style.display = 'none';
        title.textContent = "Activity Logs";
        btn.textContent = "View Retirement"; // Simplified text
        
        const firstLogBtn = logNav.querySelector('.main-nav-btn');
        switchHistoryTab('unit', firstLogBtn);
    }
}

/**
 * MAIN TAB SWITCHER
 */
function switchHistoryTab(tabName, btnElement) {
    document.querySelectorAll(".main-nav-btn").forEach((btn) => btn.classList.remove("active"));
    if (btnElement) btnElement.classList.add("active");

    document.querySelectorAll(".tab-content").forEach((tab) => tab.style.display = "none");

    const targetTab = document.getElementById(tabName + "-tab");
    if (targetTab) {
        targetTab.style.display = "block";
        const searchInput = document.getElementById("main-search-input");
        if (searchInput) searchInput.value = "";
        
        targetTab.querySelectorAll(".selectable-row").forEach(r => r.style.display = "");
        selectFirstVisibleRow();
    }
}

/**
 * AUTO-SELECT FIRST VISIBLE ROW
 */
function selectFirstVisibleRow() {
    const activeTab = Array.from(document.querySelectorAll(".tab-content")).find(tab => tab.style.display !== "none");
    if (activeTab) {
        const visibleRows = Array.from(activeTab.querySelectorAll(".selectable-row")).filter(row => row.style.display !== "none");
        if (visibleRows.length > 0) {
            visibleRows[0].click();
        } else {
            document.querySelectorAll(".history-view").forEach(v => v.style.display = "none");
            document.querySelectorAll(".data-body").forEach(body => {
                body.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:40px; color:#999;">No records found.</td></tr>`;
            });
        }
    }
}

/**
 * AJAX: FETCH ARCHIVE
 */
function fetchArchiveData(roomId) {
    const reasonBox = document.getElementById("archive-reason-text");
    const adminBox = document.getElementById("archived-by-name");
    const roomHeader = document.getElementById("archive-room-id");

    if (!reasonBox) return;
    reasonBox.innerHTML = "<em>Loading archive details...</em>";

    fetch(`${window.location.pathname}?id=${encodeURIComponent(roomId)}&type=archive`)
        .then((res) => res.json())
        .then((data) => {
            if (data.status === "success") {
                reasonBox.textContent = data.reason || "No reason specified.";
                if (adminBox) adminBox.textContent = data.admin || "System Administrator";
                if (roomHeader && data.lab_name) {
                    roomHeader.textContent = `Room ${data.lab_room} (${data.lab_name})`;
                }
            } else {
                reasonBox.innerHTML = `<span style="color:red;">${data.reason}</span>`;
            }
        })
        .catch(() => {
            reasonBox.innerHTML = "<em>Failed to load details.</em>";
        });
}

/**
 * AJAX: FETCH TIMELINE
 */
function fetchTimelineData(id, type) {
    const isRetired = (type === "retired");
    const isInventory = (type === "inventory");
    const tbodySelector = isRetired ? "#view-retired-timeline .data-body" : "#view-full-timeline .data-body";
    const tbody = document.querySelector(tbodySelector);
    
    if (!tbody) return;

    const loadingColSpan = isInventory ? 4 : (isRetired ? 4 : 6);
    tbody.innerHTML = `<tr><td colspan="${loadingColSpan}" style="text-align:center; padding: 20px;"><em>Loading history...</em></td></tr>`;

    fetch(`${window.location.pathname}?id=${encodeURIComponent(id)}&type=${encodeURIComponent(type)}`)
        .then((res) => res.text())
        .then((html) => {
            tbody.innerHTML = html;
        })
        .catch(() => {
            const errorColSpan = isInventory ? 4 : (isRetired ? 4 : 6);
            tbody.innerHTML = `<tr><td colspan="${errorColSpan}" style="text-align:center; color:red;">Error loading history.</td></tr>`;
        });
}

/**
 * SEARCH FILTERING
 */
function applyFilters() {
    const searchTerm = document.getElementById("main-search-input").value.toLowerCase();
    const activeTab = Array.from(document.querySelectorAll(".tab-content")).find(tab => tab.style.display !== "none");
    if (!activeTab) return;

    const rows = activeTab.querySelectorAll(".selectable-row");
    rows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? "" : "none";
    });
}