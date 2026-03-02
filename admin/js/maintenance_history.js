document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.querySelector(".search-input");

  // 1. DYNAMIC SEARCH (Searches only the currently active tab)
  searchInput.addEventListener("keyup", function () {
    const searchTerm = this.value.toLowerCase();

    // Safely find the visible tab instead of relying on inline styles
    const tabs = document.querySelectorAll(".tab-content");
    let activeTab = null;
    tabs.forEach((tab) => {
      if (window.getComputedStyle(tab).display !== "none") {
        activeTab = tab;
      }
    });

    if (activeTab) {
      const rows = activeTab.querySelectorAll("tbody tr");
      rows.forEach((row) => {
        row.style.display = row.textContent.toLowerCase().includes(searchTerm)
          ? ""
          : "none";
      });
    }
  });

  // 2. UNIFIED CLICK HANDLER
  document.addEventListener("click", function (e) {
    const row = e.target.closest(".selectable-row");
    if (!row) return;

    // Highlight Row
    document
      .querySelectorAll(".selectable-row")
      .forEach((r) => r.classList.remove("active-row"));
    row.classList.add("active-row");

    // Hide all right-side views
    document
      .querySelectorAll(".history-view")
      .forEach((v) => (v.style.display = "none"));

    // Identify the active Tab Type
    const activeTab = document
      .querySelector(".toggle-link.active")
      .getAttribute("onclick");

    if (activeTab.includes("archives")) {
      // Show Archive Mode
      const view = document.getElementById("view-archives-details");
      if (view) view.style.display = "block";

      // Set the Room Number in the Header
      const roomId = row.dataset.roomNum;
      document.getElementById("archive-room-id").textContent =
        `[Room ${roomId}]`;
      fetchArchiveData(roomId);
    } else if (activeTab.includes("retired")) {
      // Show Retired Mode (4 Columns)
      const view = document.getElementById("view-retired-timeline");
      if (view) view.style.display = "block";
      updateTagLabels(row);

      // --- NEW: Trigger the AJAX Fetch ---
      const targetId = row.dataset.id || row.dataset.propId;
      fetchTimelineData(targetId, "retired");
    } else {
      // Show Full Maintenance Mode (6 Columns)
      const view = document.getElementById("view-full-timeline");
      if (view) view.style.display = "block";
      updateTagLabels(row);

      // --- NEW: Trigger the AJAX Fetch ---
      const targetId = row.dataset.unitId || row.dataset.propId;
      fetchTimelineData(targetId, "maintenance");
    }
  });
});

// =========================================
// MODAL LOGIC (Condemn Unit)
// =========================================

function openCondemnModal() {
  // Find the currently selected row
  const activeRow = document.querySelector(".selectable-row.active-row");

  if (!activeRow) {
    alert("Please select an item from the table first.");
    return;
  }

  // Extract data from the selected row
  const tag = activeRow.dataset.tag || activeRow.cells[1].innerText;
  // It checks for unit-id first, then falls back to prop-id for assets
  const id =
    activeRow.dataset.unitId ||
    activeRow.dataset.propId ||
    activeRow.cells[2].innerText;

  // Populate the modal fields
  document.getElementById("modal-tag-display").textContent = `[${tag}]`;
  document.getElementById("modal-set-tag").value = tag;
  document.getElementById("modal-set-id").value = id;

  // Show the modal
  document.getElementById("condemn-modal").style.display = "flex";
}

function closeCondemnModal() {
  document.getElementById("condemn-modal").style.display = "none";

  // Reset the form so it's clean for the next time
  document.getElementById("condemn-form").reset();
}

// Close modal if user clicks outside the white box
document
  .getElementById("condemn-modal")
  .addEventListener("click", function (e) {
    if (e.target === this) {
      closeCondemnModal();
    }
  });

function submitCondemn() {
  const id = document.getElementById("modal-set-id").value;
  const remarks = document.getElementById("modal-remarks").value;

  // Here we gather which checkboxes are checked
  const checkedBoxes = document.querySelectorAll(
    'input[name="action_taken"]:checked',
  );
  const actions = Array.from(checkedBoxes)
    .map((cb) => cb.value)
    .join(", ");

  if (!actions) {
    alert("Please select at least one Action Taken.");
    return;
  }

  console.log(
    "Submitting Condemn for ID:",
    id,
    "Actions:",
    actions,
    "Remarks:",
    remarks,
  );
  // TODO: Add AJAX call to process_condemn.php here

  // Close the modal after submission
  alert("Unit condemned successfully.");
  closeCondemnModal();
}

// =========================================
// GLOBAL FUNCTIONS
// =========================================

function updateTagLabels(row) {
  const tag = row.dataset.tag || row.cells[1].innerText;
  document.querySelectorAll(".selected-tag-label").forEach((el) => {
    el.textContent = `[${tag}]`;
  });
}

// Tab Switcher (The Pill Buttons)
// Tab Switcher (The Pill Buttons)
function switchHistoryTab(tabName, btnElement) {
  // 1. Toggle Active Button UI
  document
    .querySelectorAll(".toggle-link")
    .forEach((btn) => btn.classList.remove("active"));
  btnElement.classList.add("active");

  // 2. Show selected left-side table
  document
    .querySelectorAll(".tab-content")
    .forEach((tab) => (tab.style.display = "none"));
  const activeTab = document.getElementById(tabName + "-tab");
  if (activeTab) activeTab.style.display = "block";

  // 3. Update Placeholder
  const searchInput = document.querySelector(".search-input");
  if (tabName === "archives") {
    searchInput.placeholder = "Search room number...";
  } else if (tabName === "asset") {
    searchInput.placeholder = "Search Property ID...";
  } else {
    searchInput.placeholder = "Search a set tag...";
  }

  // 4. RESET RIGHT PANEL SKELETONS
  // Hide all right views first
  document
    .querySelectorAll(".history-view")
    .forEach((v) => (v.style.display = "none"));

  // Clear any active row highlights
  document
    .querySelectorAll(".selectable-row")
    .forEach((r) => r.classList.remove("active-row"));

  // Show the correct skeleton based on the tab clicked
  if (tabName === "archives") {
    document.getElementById("view-archives-details").style.display = "block";
    // Reset text back to placeholder state
    document.getElementById("archive-room-id").textContent = "";
    document.getElementById("archive-reason-text").innerHTML =
      "<em>Click a room on the left to view archive details.</em>";
    document.getElementById("archived-by-name").textContent = "-";
  } else if (tabName.includes("retired")) {
    document.getElementById("view-retired-timeline").style.display = "block";
    document
      .querySelectorAll(".selected-tag-label")
      .forEach((el) => (el.textContent = ""));
  } else {
    // Unit Logs and Asset Logs
    document.getElementById("view-full-timeline").style.display = "block";
    document
      .querySelectorAll(".selected-tag-label")
      .forEach((el) => (el.textContent = ""));
  }
}

// Function to fetch and display table rows
function fetchTimelineData(id, type) {
  // Target the correct table body based on the type
  const tbodyId =
    type === "retired"
      ? "#view-retired-timeline .data-body"
      : "#view-full-timeline .data-body";
  const tbody = document.querySelector(tbodyId);

  // Show a loading message while waiting for the database
  const colSpan = type === "retired" ? "4" : "6";
  tbody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center; padding: 20px;"><em>Loading history...</em></td></tr>`;

  // Fetch the data from your new PHP script
  fetch(`fetch_timeline.php?id=${id}&type=${type}`)
    .then((response) => response.text())
    .then((htmlData) => {
      // Inject the generated HTML rows straight into the table
      tbody.innerHTML = htmlData;
    })
    .catch((error) => {
      console.error("Error fetching data:", error);
      tbody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center; color: red;">Failed to load data. Please check your connection.</td></tr>`;
    });
}

// Function to fetch and display Archive Details (JSON)
function fetchArchiveData(roomId) {
  const reasonBox = document.getElementById("archive-reason-text");
  const adminBox = document.getElementById("archived-by-name");

  // Show a loading state in the text boxes
  reasonBox.innerHTML = "<em>Loading archive details...</em>";
  adminBox.innerHTML = "<em>Loading...</em>";

  // Fetch the data from PHP
  fetch(`fetch_timeline.php?id=${roomId}&type=archive`)
    .then((response) => response.json()) // Parse the response as JSON
    .then((data) => {
      if (data.status === "success") {
        // Inject the fresh database text into the boxes
        reasonBox.textContent = data.reason;
        adminBox.textContent = data.admin;
      } else {
        reasonBox.innerHTML = `<em>${data.reason}</em>`;
        adminBox.textContent = data.admin;
      }
    })
    .catch((error) => {
      console.error("Error fetching archive data:", error);
      reasonBox.innerHTML =
        "<em style='color: red;'>Failed to load archive details.</em>";
      adminBox.textContent = "-";
    });
}

// =========================================
// FILTER LOGIC (Text + Date combined)
// =========================================

function toggleDateFilter() {
  const popover = document.getElementById("date-filter-popover");
  popover.style.display = popover.style.display === "none" ? "block" : "none";
}

function clearDateFilter() {
  document.getElementById("filter-start-date").value = "";
  document.getElementById("filter-end-date").value = "";
  applyFilters(); // Re-run filters to show all rows
  toggleDateFilter(); // Close popover
}

function applyFilters() {
  const searchTerm = document
    .getElementById("main-search-input")
    .value.toLowerCase();
  const startDateVal = document.getElementById("filter-start-date").value;
  const endDateVal = document.getElementById("filter-end-date").value;

  // Convert string inputs to actual Date objects for comparison
  const start = startDateVal ? new Date(startDateVal) : null;
  const end = endDateVal ? new Date(endDateVal) : null;
  if (end) end.setHours(23, 59, 59); // Ensure it includes the entire end day

  // Safely find the visible tab
  const tabs = document.querySelectorAll(".tab-content");
  let activeTab = null;
  tabs.forEach((tab) => {
    if (window.getComputedStyle(tab).display !== "none") {
      activeTab = tab;
    }
  });

  if (!activeTab) return;

  // Determine which column holds the Date based on the active tab
  // (Arrays start at 0, so column 4 is index 3)
  let dateColIndex = 3; // Default: Unit Logs / Asset Logs (Latest Maintenance Date)
  if (activeTab.id === "archives-tab" || activeTab.id.includes("retired")) {
    dateColIndex = 2; // Archives and Retired tabs have the Date in column 3
  }

  // Filter the rows
  const rows = activeTab.querySelectorAll("tbody tr");
  rows.forEach((row) => {
    // Text Match
    const textMatch = row.textContent.toLowerCase().includes(searchTerm);

    // Date Match
    let dateMatch = true;
    if (start || end) {
      const dateCell = row.cells[dateColIndex];
      if (dateCell) {
        // Convert table text like "11/20/2025" to a Date object
        const rowDate = new Date(dateCell.textContent.trim());

        if (start && rowDate < start) dateMatch = false;
        if (end && rowDate > end) dateMatch = false;
      }
    }

    // Show row ONLY if it matches BOTH text search and date range
    row.style.display = textMatch && dateMatch ? "" : "none";
  });

  // Optional: Hide the popover after clicking Apply
  document.getElementById("date-filter-popover").style.display = "none";
}

// Close date popover if clicked outside
document.addEventListener("click", function (e) {
  const popover = document.getElementById("date-filter-popover");
  const filterBtn = document.querySelector(".btn-filter-date");
  if (
    popover.style.display === "block" &&
    !popover.contains(e.target) &&
    !filterBtn.contains(e.target)
  ) {
    popover.style.display = "none";
  }
});