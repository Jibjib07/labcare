let isInitialLoad = true;
let currentSelectedId = null;
let currentSelectedType = null;
let currentSelectedName = "";
let currentSelectedRoom = "";

document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("main-search-input");

  if (searchInput) {
    searchInput.addEventListener("keyup", applyFilters);
  }

  // --- ROW CLICK & DRILL-DOWN LOGIC ---
  document.addEventListener("click", function (e) {
    const row = e.target.closest(".selectable-row");
    if (!row) return;

    // 1. Highlight Row Selection (Desktop & Mobile)
    document
      .querySelectorAll(".selectable-row")
      .forEach((r) => r.classList.remove("active-row"));
    row.classList.add("active-row");

    const type = row.dataset.type;
    const id = row.dataset.id;
    const itemName = row.dataset.tag;
    
    // Extract Room Name if it exists in the row
    const roomEl = row.querySelector(".room-text");
    const itemRoom = roomEl ? roomEl.textContent.replace(/[()]/g, '').trim() : "";

    // Save globals for Export
    currentSelectedId = id;
    currentSelectedType = type;
    currentSelectedName = itemName;
    currentSelectedRoom = itemRoom;

    // 2. Mobile Swap Logic (Drill-Down)
    if (window.innerWidth <= 900) {
      document.querySelector(".left-panel").classList.add("mobile-hide");
      document.getElementById("right-panel").classList.add("mobile-active");

      const mobileTitle = document.getElementById("mobile-detail-title");
      if (mobileTitle) mobileTitle.textContent = `${itemName} Details`;

      window.scrollTo(0, 0);
    }

    // 3. Load Timeline Content and Handle Export Buttons
    document
      .querySelectorAll(".history-view")
      .forEach((v) => (v.style.display = "none"));

    // Hide both buttons initially
    const btnExportActive = document.getElementById("btn-export-active");
    const btnExportRetired = document.getElementById("btn-export-retired");
    if(btnExportActive) btnExportActive.style.display = "none";
    if(btnExportRetired) btnExportRetired.style.display = "none";

    if (type === "archive") {
      const archivePanel = document.getElementById("view-archives-details");
      if (archivePanel) archivePanel.style.display = "block";
      fetchArchiveData(id);
    } else if (type === "retired") {
      const retiredPanel = document.getElementById("view-retired-timeline");
      if (retiredPanel) {
        retiredPanel.style.display = "block";
        const headerEl = document.getElementById("retired-title");
        if (headerEl && itemName)
          headerEl.textContent = `Retirement Record: ${itemName}`;

        const retiredBody = retiredPanel.querySelector(".data-body");
        if (retiredBody)
          retiredBody.innerHTML = `<div style="text-align:center; padding: 40px; color: #757575;"><em>Loading history...</em></div>`;
          
        if(btnExportRetired) btnExportRetired.style.display = "flex"; // Show Export
      }
      fetchTimelineData(id, "retired");
    } else {
      const fullPanel = document.getElementById("view-full-timeline");
      if (fullPanel) {
        fullPanel.style.display = "block";
        const timelineEl = document.getElementById("timeline-title");
        if (timelineEl && itemName)
          timelineEl.textContent = `Activity Timeline: ${itemName}`;
          
        if(btnExportActive) btnExportActive.style.display = "flex"; // Show Export
        fetchTimelineData(id, type);
      }
    }
  });

  selectFirstVisibleRow();
  isInitialLoad = false;
});

// --- MOBILE BACK BUTTON LOGIC ---
function closeMobileDetails() {
  if (window.innerWidth <= 900) {
    document.querySelector(".left-panel").classList.remove("mobile-hide");
    document.getElementById("right-panel").classList.remove("mobile-active");

    // Clear the active highlight when going back to the list
    document
      .querySelectorAll(".selectable-row")
      .forEach((r) => r.classList.remove("active-row"));
  }
}

// --- NAVIGATION & TAB LOGIC ---
function toggleNavView(btn) {
  const logNav = document.getElementById("log-nav-container");
  const retNav = document.getElementById("retirement-nav-container");
  const title = document.getElementById("nav-title");

  const isShowingLogs = logNav.style.display !== "none";

  if (isShowingLogs) {
    logNav.style.display = "none";
    retNav.style.display = "flex";
    title.textContent = "Retirement History";
    btn.textContent = "View Activity Logs";

    const firstRetBtn = retNav.querySelector(".main-nav-btn");
    switchHistoryTab("retired-units", firstRetBtn);
  } else {
    logNav.style.display = "flex";
    retNav.style.display = "none";
    title.textContent = "Activity Logs";
    btn.textContent = "View Retirement";

    const firstLogBtn = logNav.querySelector(".main-nav-btn");
    switchHistoryTab("unit", firstLogBtn);
  }
}

function switchHistoryTab(tabName, btnElement) {
  // 1. Update active button styling
  document
    .querySelectorAll(".main-nav-btn")
    .forEach((btn) => btn.classList.remove("active"));
  if (btnElement) btnElement.classList.add("active");

  // 2. Hide all tabs
  document
    .querySelectorAll(".tab-content")
    .forEach((tab) => (tab.style.display = "none"));

  const targetTab = document.getElementById(tabName + "-tab");
  if (targetTab) {
    targetTab.style.display = "block";

    // 3. CLEAR THE SEARCH BAR
    const searchInput = document.getElementById("main-search-input");
    if (searchInput) searchInput.value = "";

    // 4. CLEAR THE DATE RANGE
    const startDateInput = document.getElementById("filter-start-date");
    const endDateInput = document.getElementById("filter-end-date");
    if (startDateInput) startDateInput.value = "";
    if (endDateInput) endDateInput.value = "";

    // 5. Unhide all rows in the newly opened tab
    targetTab.querySelectorAll(".selectable-row").forEach((r) => {
      r.classList.remove("hidden-row");
      r.style.display = "";
    });

    // 6. Select the first visible row (Desktop only)
    isInitialLoad = true;
    selectFirstVisibleRow();
    isInitialLoad = false;
  }
}

// --- DESKTOP AUTO-SELECT LOGIC ---
function selectFirstVisibleRow() {
  const activeTab = Array.from(document.querySelectorAll(".tab-content")).find(
    (tab) => tab.style.display !== "none",
  );

  if (activeTab) {
    // Filter out rows that are hidden by our class
    const visibleRows = Array.from(
      activeTab.querySelectorAll(".selectable-row"),
    ).filter((row) => !row.classList.contains("hidden-row"));

    if (visibleRows.length > 0) {
      // ONLY auto-click if on Desktop
      if (window.innerWidth > 900) {
        visibleRows[0].click();
      }
    } else {
      // Hide active timelines and show "No records found"
      document
        .querySelectorAll(".history-view")
        .forEach((v) => (v.style.display = "none"));

      let targetViewId = "view-full-timeline";
      const tabId = activeTab.id;

      if (tabId === "archives-tab") {
        targetViewId = "view-archives-details";
      } else if (tabId.includes("retired")) {
        targetViewId = "view-retired-timeline";
      }

      const targetView = document.getElementById(targetViewId);
      if (targetView) {
        targetView.style.display = "block";
        const tbody = targetView.querySelector(".data-body");
        if (tbody) {
          tbody.innerHTML = `<div style="text-align:center; padding: 40px; color: #757575;">No records found matching your filter.</div>`;
        }
      }
    }
  }
}

// --- DATA FETCHING LOGIC ---
function fetchArchiveData(roomId) {
  const reasonBox = document.getElementById("archive-reason-text");
  const adminBox = document.getElementById("archived-by-name");
  const dateBox = document.getElementById("archive-date-text");

  if (!reasonBox) return;
  reasonBox.innerHTML = "<em>Loading archive details...</em>";

  fetch(
    `${window.location.pathname}?id=${encodeURIComponent(roomId)}&type=archive`,
  )
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        reasonBox.textContent = `"${data.reason || "No reason specified."}"`;
        if (adminBox)
          adminBox.textContent = data.admin || "System Administrator";
        if (dateBox) dateBox.textContent = data.date || "-";
      } else {
        reasonBox.innerHTML = `<span style="color:red;">${data.reason}</span>`;
      }
    })
    .catch(() => {
      reasonBox.innerHTML = "<em>Failed to load details.</em>";
    });
}

function fetchTimelineData(id, type) {
  const isRetired = type === "retired";
  const tbodySelector = isRetired
    ? "#view-retired-timeline .data-body"
    : "#view-full-timeline .data-body";
  const tbody = document.querySelector(tbodySelector);

  if (!tbody) return;

  tbody.innerHTML = `<div style="text-align:center; padding: 40px; color: #757575;"><em>Loading history...</em></div>`;

  fetch(
    `${window.location.pathname}?id=${encodeURIComponent(id)}&type=${encodeURIComponent(type)}`,
  )
    .then((res) => res.text())
    .then((html) => {
      tbody.innerHTML = html;
    })
    .catch(() => {
      tbody.innerHTML = `<div style="text-align:center; padding: 40px; color: red;">Error loading history.</div>`;
    });
}

// =========================================
// SEARCH & DATE FILTER LOGIC
// =========================================
function applyFilters() {
  const searchInput = document.getElementById("main-search-input");
  const startInput = document.getElementById("filter-start-date");
  const endInput = document.getElementById("filter-end-date");

  if (!searchInput) return; // Safety check

  const searchTerm = searchInput.value.toLowerCase().trim();
  const startDateVal = startInput ? startInput.value : "";
  const endDateVal = endInput ? endInput.value : "";

  // Safely parse the dates to midnight/end-of-day
  let startDate = null;
  let endDate = null;
  if (startDateVal) {
    startDate = new Date(startDateVal);
    startDate.setHours(0, 0, 0, 0);
  }
  if (endDateVal) {
    endDate = new Date(endDateVal);
    endDate.setHours(23, 59, 59, 999);
  }

  const activeTab = Array.from(document.querySelectorAll(".tab-content")).find(
    (tab) => tab.style.display !== "none",
  );
  if (!activeTab) return;

  const rows = activeTab.querySelectorAll(".selectable-row");

  rows.forEach((row) => {
    // 1. Text Search Check
    const text = row.textContent.toLowerCase();
    const matchesSearch = searchTerm === "" || text.includes(searchTerm);

    // 2. Date Range Check
    let matchesDate = true;
    if (startDate || endDate) {
      const dateEl = row.querySelector(".date-text");
      if (dateEl) {
        // Strip out any bullet points or spaces
        const rowDateStr = dateEl.textContent.replace("•", "").trim();
        const rowDate = new Date(rowDateStr);

        if (!isNaN(rowDate.getTime())) {
          rowDate.setHours(12, 0, 0, 0); // Set to noon to avoid timezone shifting
          if (startDate && rowDate < startDate) matchesDate = false;
          if (endDate && rowDate > endDate) matchesDate = false;
        }
      }
    }

    // 3. Apply the Nuclear CSS Class
    if (matchesSearch && matchesDate) {
      row.classList.remove("hidden-row");
    } else {
      row.classList.add("hidden-row");
    }
  });

  // Re-trigger auto-selection safely
  isInitialLoad = true;
  selectFirstVisibleRow();
  isInitialLoad = false;
}

// =========================================
// DROPDOWN TOGGLES
// =========================================
function toggleDateDropdown(e) {
  if (e) e.stopPropagation();
  document.getElementById("date-dropdown").classList.toggle("show");
  document.getElementById("dropdown-backdrop").classList.toggle("show");
}

function closeDateDropdown() {
  document.getElementById("date-dropdown").classList.remove("show");
  document.getElementById("dropdown-backdrop").classList.remove("show");
}

function clearDateFilter(e) {
  if (e) e.stopPropagation();
  document.getElementById("filter-start-date").value = "";
  document.getElementById("filter-end-date").value = "";
  applyFilters();
}

// =========================================
// EXPORT PRINT FUNCTIONALITY
// =========================================

function getReportHeaderHTML() {
  return `
    <div class="doc-header" style="position: relative; margin-bottom: 20px; font-family: 'Times New Roman', Times, serif; min-height: 80px; display: flex; align-items: center; justify-content: center;">
        <div style="position: absolute; left: 0; top: 0;">
            <img src="../assets/cvsu_logo.png" style="width: 80px; height: 80px;">
        </div>
        <div style="text-align: center; width: 100%; padding: 0 90px; box-sizing: border-box;">
            <p style="margin: 0; font-size: 14px;">Republic of the Philippines</p>
            <h2 style="margin: 0; font-size: 20px; font-weight: 800; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                CAVITE STATE UNIVERSITY
            </h2>
            <h3 style="margin: 0; font-size: 16px; font-weight: bold; font-family: 'Times New Roman', Times, serif;">
                Cavite City Campus
            </h3>
            <p style="margin: 0; font-size: 13px;">Pulo II, Dalahican, Cavite City</p>
        </div>
    </div>
    <div class="doc-divider" style="border-bottom: 1.5px solid black; margin: 10px 0 20px 0;"></div>`;
}

// =========================================
// EXPORT PRINT FUNCTIONALITY
// =========================================

function getReportHeaderHTML() {
  return `
    <div class="doc-header" style="position: relative; margin-bottom: 20px; font-family: 'Times New Roman', Times, serif; min-height: 80px; display: flex; align-items: center; justify-content: center;">
        <div style="position: absolute; left: 0; top: 0;">
            <img src="../assets/cvsu_logo.png" style="width: 80px; height: 80px;">
        </div>
        <div style="text-align: center; width: 100%; padding: 0 90px; box-sizing: border-box;">
            <p style="margin: 0; font-size: 14px;">Republic of the Philippines</p>
            <h2 style="margin: 0; font-size: 20px; font-weight: 800; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                CAVITE STATE UNIVERSITY
            </h2>
            <h3 style="margin: 0; font-size: 16px; font-weight: bold; font-family: 'Times New Roman', Times, serif;">
                Cavite City Campus
            </h3>
            <p style="margin: 0; font-size: 13px;">Pulo II, Dalahican, Cavite City</p>
        </div>
    </div>
    <div class="doc-divider" style="border-bottom: 1.5px solid black; margin: 10px 0 20px 0;"></div>`;
}


function exportTimeline() {
    if (!currentSelectedId || !currentSelectedType) return;

    // 1. Determine which timeline container is visible
    const isRetired = currentSelectedType === "retired";
    const containerId = isRetired ? "view-retired-timeline" : "view-full-timeline";
    const container = document.getElementById(containerId);
    
    // 2. Extract Data from the DOM cards
    const cards = container.querySelectorAll('.timeline-card');
    
// --- UPDATED TOAST CHECKS ---
    if (container.innerHTML.includes("Loading history...")) {
        showToast("Export Pending", "Please wait for the data to finish loading.", "warning");
        return;
    }
    if (cards.length === 0 || container.innerHTML.includes("No activity history found")) {
        showToast("No Data Available", "There are no records to export for this item.", "warning");
        return;
    }
    // ----------------------------

    let rowsHTML = "";
    let currentStatusDisplay = "-"; // Used specifically for the Inventory layout
    
    cards.forEach((card, index) => {
        // Extract specific fields based on your HTML structure
        const actor = card.querySelector('.user-info strong')?.innerText || '-';
        const dateStr = card.querySelector('.card-date')?.innerText.replace('•', '').trim() || '-';
        const rawActionOrStatus = card.querySelector('.status-pill')?.innerText || '-';
        const remarks = card.querySelector('.remarks-box')?.innerText.replace(/"/g, '') || '-';
        
        let action = "-";
        let affected = "-";
        let status = "-";
        
        const subItems = card.querySelectorAll('.info-item');
        subItems.forEach(item => {
            const text = item.innerText;
            if (text.includes('Action:')) action = text.replace('Action:', '').trim();
            if (text.includes('Affected:')) affected = text.replace('Affected:', '').trim();
            if (text.includes('Status:')) status = text.replace('Status:', '').trim();
        });

        // 3. Build Rows Based on Item Type
        if (currentSelectedType === "inventory") {
            action = rawActionOrStatus; // In Inventory, badge is Action
            
            // Capture the status from the very first (most recent) card to display at the top of the document
            if (index === 0) {
                currentStatusDisplay = status;
            }

            // 4-Column Layout for Inventory
            rowsHTML += `
                <tr>
                    <td class="text-center">${dateStr}</td>
                    <td class="text-center">${actor}</td>
                    <td class="text-center">${action}</td>
                    <td>${remarks}</td>
                </tr>
            `;
        } else {
            status = rawActionOrStatus; // In Units/Assets, badge is Status
            
            // 6-Column Layout for Units/Assets
            rowsHTML += `
                <tr>
                    <td>${dateStr}</td>
                    <td>${actor}</td>
                    <td>${action}</td>
                    <td>${affected}</td>
                    <td>${remarks}</td>
                    <td>${status}</td>
                </tr>
            `;
        }
    });

    // 4. Build the final HTML document based on the type
    let printHTML = "";

    if (currentSelectedType === "inventory") {
        // --- INVENTORY TEMPLATE ---
        printHTML = `
        <html>
            <head>
                <title>Inventory Log - ${currentSelectedName}</title>
                <style>
                    body { font-family: 'Times New Roman', serif; padding: 20px; color: #000; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
                    th, td { border: 1px solid black; padding: 10px; vertical-align: top; }
                    th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
                    .text-center { text-align: center; }
                    
                    @media print {
                        @page { margin: 0.5in; size: portrait; }
                        body { padding: 0; }
                    }
                </style>
            </head>
            <body>
                ${getReportHeaderHTML()}
                
                <div style="text-align: center; margin-bottom: 25px;">
                    <h2 style="font-size: 18px; font-weight: 800; text-transform: uppercase; margin-bottom: 5px;">COMPUTER LABORATORY AUDIT TRACKING</h2>
                    <h3 style="font-size: 16px; font-weight: 800; text-transform: uppercase; margin-top: 0;">INVENTORY LOG</h3>
                    <p style="margin: 15px 0 0 0; font-weight: bold; font-size: 15px;">${currentSelectedName}</p>
                    <p style="margin: 2px 0 0 0; font-size: 14px; font-weight: bold;">Current Status: <span style="font-weight: normal;">${currentStatusDisplay}</span></p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th width="15%">Date</th>
                            <th width="20%">Actor</th>
                            <th width="25%">Action</th>
                            <th width="40%">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHTML}
                    </tbody>
                </table>
            </body>
        </html>`;

    } else {
        // --- UNITS / ASSETS TEMPLATE ---
        let roomDisplay = currentSelectedRoom ? `<p style="margin: 0; font-family: 'Times New Roman', serif;">${currentSelectedRoom}</p>` : "";
        printHTML = `
        <html>
            <head>
                <title>Audit Log - ${currentSelectedName}</title>
                <style>
                    body { font-family: 'Times New Roman', serif; padding: 20px; color: #000; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
                    th, td { border: 1px solid black; padding: 10px; text-align: left; vertical-align: top; }
                    th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
                    
                    @media print {
                        @page { margin: 0.5in; size: portrait; }
                        body { padding: 0; }
                    }
                </style>
            </head>
            <body>
                ${getReportHeaderHTML()}
                
                <div style="text-align: center; margin-bottom: 25px;">
                    <h2 style="font-size: 18px; font-weight: 800; text-transform: uppercase; margin-bottom: 5px;">COMPUTER LABORATORY AUDIT TRACKING</h2>
                    <h3 style="font-size: 16px; font-weight: 800; text-transform: uppercase; margin-top: 0;">ACTIVITY LOG</h3>
                    <p style="margin: 5px 0; font-weight: bold; font-size: 15px;">${currentSelectedName}</p>
                    ${roomDisplay}
                </div>

                <table>
                    <thead>
                        <tr>
                            <th width="12%">Date</th>
                            <th width="15%">Actor</th>
                            <th width="18%">Action</th>
                            <th width="15%">Affected</th>
                            <th width="25%">Remarks</th>
                            <th width="15%">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHTML}
                    </tbody>
                </table>
            </body>
        </html>`;
    }

    // 5. Inject into a hidden iframe and trigger print
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none'; // Keep it hidden
    document.body.appendChild(iframe);
    
    iframe.contentDocument.open();
    iframe.contentDocument.write(printHTML);
    iframe.contentDocument.close();
    
    // Allow images to load before calling print, then clean up
    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        
        // Remove the iframe after the print dialog closes
        setTimeout(() => {
            document.body.removeChild(iframe);
        }, 1000);
    }, 500);


// --- TOAST NOTIFICATION LOGIC ---
function showToast(title, message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Choose icon based on type
    let icon = 'fa-check';
    if (type === 'warning') icon = 'fa-exclamation';
    if (type === 'error') icon = 'fa-times';

    // Build the new UI structure
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${icon}"></i>
        </div>
        <div class="toast-content">
            <p class="toast-title">${title}</p>
            <p class="toast-message">${message}</p>
        </div>
    `;
    
    container.appendChild(toast);

    // Automatically remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => {
            if (container.contains(toast)) container.removeChild(toast);
        }, 300); // Wait for fade-out animation
    }, 3000);
}

}