/**
 * Switch History Tabs (Visual Toggle Only)
 * Handles the pill-shaped buttons: Maintenance Logs | System Archives | Asset Retirement
 */
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

  // Show the one you clicked
  const target = document.getElementById(tabName + "-tab");
  if (target) {
    target.style.display = "block";
  }
}
