let isInitialLoad = true;

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

    // 2. Mobile Swap Logic (Drill-Down)
    if (window.innerWidth <= 900) {
      document.querySelector(".left-panel").classList.add("mobile-hide");
      document.getElementById("right-panel").classList.add("mobile-active");

      const mobileTitle = document.getElementById("mobile-detail-title");
      if (mobileTitle) mobileTitle.textContent = `${itemName} Details`;

      window.scrollTo(0, 0);
    }

    // 3. Load Timeline Content
    document
      .querySelectorAll(".history-view")
      .forEach((v) => (v.style.display = "none"));

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
      }
      fetchTimelineData(id, "retired");
    } else {
      const fullPanel = document.getElementById("view-full-timeline");
      if (fullPanel) {
        fullPanel.style.display = "block";
        const timelineEl = document.getElementById("timeline-title");
        if (timelineEl && itemName)
          timelineEl.textContent = `Activity Timeline: ${itemName}`;
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
    const visibleRows = Array.from(
      activeTab.querySelectorAll(".selectable-row"),
    ).filter((row) => row.style.display !== "none");

    if (visibleRows.length > 0) {
      // ONLY click the first row automatically if the user is on Desktop
      if (window.innerWidth > 900) {
        visibleRows[0].click();
      }
    } else {
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
          tbody.innerHTML = `<div style="text-align:center; padding: 40px; color: #757575;">No records found to display.</div>`;
        }

        if (targetViewId === "view-archives-details") {
          if (document.getElementById("archive-reason-text"))
            document.getElementById("archive-reason-text").textContent =
              "No data available.";
          if (document.getElementById("archived-by-name"))
            document.getElementById("archived-by-name").textContent = "-";
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
// AUTO-SELECT FIRST ROW LOGIC
// =========================================

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

// =========================================
// DROPDOWN TOGGLES
// =========================================

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
// AUTO-SELECT FIRST ROW LOGIC
// =========================================

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

// =========================================
// DROPDOWN TOGGLES
// =========================================

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
// AUTO-SELECT FIRST ROW LOGIC
// =========================================

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
