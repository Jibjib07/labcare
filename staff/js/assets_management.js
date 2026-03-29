let currentEditingSetId = null; // For Computer Units
let currentSelectedFAId = null; // For Facility Assets
let isEditModeActive = false; // <-- ADDED: Tracker for Edit/Report Mode

// Check for pending toasts when the page loads
document.addEventListener("DOMContentLoaded", () => {
  const pendingToast = sessionStorage.getItem("pendingToast");
  if (pendingToast) {
    const toastData = JSON.parse(pendingToast);
    showNotification(toastData.title, toastData.message, toastData.type);
    sessionStorage.removeItem("pendingToast");
  }

  // Add event listeners for asset items
  document
    .querySelectorAll("#assetListContainer .asset-item:not(.missing-id)")
    .forEach((item) => {
      item.addEventListener("click", function () {
        // --- ADDED: Blocker for PC List ---
        if (isEditModeActive) {
          showNotification(
            "Action Blocked",
            "Please save or cancel your current report before selecting another item.",
            "error"
          );
          return;
        }
        // ----------------------------------
        const setId = this.getAttribute("data-set-id");
        if (setId) selectUnit(this, setId);
      });
    });

  document
    .querySelectorAll("#facilityListContainer .asset-item")
    .forEach((item) => {
      item.addEventListener("click", function () {
        // --- ADDED: Blocker for FA List ---
        if (isEditModeActive) {
          showNotification(
            "Action Blocked",
            "Please save or cancel your current report before selecting another item.",
            "error"
          );
          return;
        }
        // ----------------------------------
        const assetId = this.getAttribute("data-asset-id");
        if (assetId) selectFacilityAsset(this, assetId);
      });
    });

  // Trigger initial auto-select on page load
  const urlParams = new URLSearchParams(window.location.search);
  const targetTab = urlParams.get("tab");

  if (targetTab === "assets") {
    switchView("facility");
  } else {
    switchView("computer"); // Defaults to computer
  }
});

function showNotification(title, message, type = "success") {
  const container = document.getElementById("toast-container");
  if (!container) return;

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  const iconClass =
    type === "success" ? "fa-check-circle" : "fa-exclamation-circle";

  toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${iconClass}"></i></div>
        <div class="toast-content">
            <h4>${title}</h4>
            <p>${message}</p>
        </div>
    `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add("fade-out");
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

function reloadWithToast(title, message, type = "success") {
  sessionStorage.setItem(
    "pendingToast",
    JSON.stringify({ title, message, type }),
  );
  location.reload();
}

// ==========================================
// UNIVERSAL UI CONTROLS
// ==========================================

window.addEventListener("click", function (event) {
  const modals = ["logStatusModal"];
  modals.forEach((id) => {
    const modal = document.getElementById(id);
    if (modal && event.target === modal) {
      modal.style.display = "none";
    }
  });

  if (
    !event.target.matches(".filter-btn") &&
    !event.target.closest(".filter-btn")
  ) {
    const filterMenu = document.getElementById("filterMenu");
    if (filterMenu && filterMenu.classList.contains("show")) {
      filterMenu.classList.remove("show");
    }
    const faFilterMenu = document.getElementById("faFilterMenu");
    if (faFilterMenu && faFilterMenu.classList.contains("show")) {
      faFilterMenu.classList.remove("show");
    }
  }
});

function switchView(viewName) {
  // --- ADDED: Auto-cancel edit mode if they switch main tabs ---
  if (isEditModeActive) {
      if (document.getElementById("view-facility").style.display === "block") {
          cancelFAReportMode();
      } else {
          cancelReportMode();
      }
  }
  // -------------------------------------------------------------

  const computerView = document.getElementById("view-computer");
  const facilityView = document.getElementById("view-facility");

  // Reset states to prevent leakage
  currentEditingSetId = null;
  currentSelectedFAId = null;
  currentHasIssues = false;
  pendingAffectedString = "";

  document
    .querySelectorAll(".asset-item")
    .forEach((item) => item.classList.remove("active"));

  if (viewName === "computer") {
    computerView.style.display = "block";
    facilityView.style.display = "none";
    autoSelectFirstVisibleItem("computer");
  } else if (viewName === "facility") {
    computerView.style.display = "none";
    facilityView.style.display = "block";
    const right = document.getElementById("view-facility-right");
    if (right) right.style.display = "block";
    autoSelectFirstVisibleItem("facility");
  }
}

function switchTab(tabId, btnElement) {
  const contents = document.querySelectorAll(".specs-content-box .tab-content");
  contents.forEach((content) => (content.style.display = "none"));

  const selectedContent = document.getElementById("tab-" + tabId);
  if (selectedContent) selectedContent.style.display = "block";

  const buttons = document.querySelectorAll(".spec-tab");
  buttons.forEach((btn) => btn.classList.remove("active"));

  if (btnElement) btnElement.classList.add("active");
}

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.style.display = "flex";
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.style.display = "none";
}

function setTabsDisabled(disabled) {
  const tabs = document.querySelectorAll(".specs-content-box .spec-tab");
  tabs.forEach((tab, index) => {
    if (index > 0) tab.disabled = disabled;
  });
}

function toggleStatus(clickedBtn) {
  const group = clickedBtn.parentElement;
  const buttons = group.querySelectorAll(".status-btn");
  buttons.forEach((btn) => btn.classList.remove("active"));
  clickedBtn.classList.add("active");
}

function toggleStatusFA(element) {
  const parent = element.parentElement;
  parent
    .querySelectorAll(".status-btn")
    .forEach((btn) => btn.classList.remove("active"));
  element.classList.add("active");
}

function closeMobileDetails() {
  const computerView = document
    .getElementById("view-computer")
    .querySelector(".split-layout");
  const facilityView = document
    .getElementById("view-facility")
    .querySelector(".split-layout");

  if (computerView) computerView.classList.remove("mobile-show-details");
  if (facilityView) facilityView.classList.remove("mobile-show-details");
}

// ==========================================
// AUTO-SELECT HELPER (UPGRADED FOR DASHBOARD URL PARAMS)
// ==========================================

function autoSelectFirstVisibleItem(viewType) {
  if (window.innerWidth <= 768) return;

  const containerId =
    viewType === "computer" ? "assetListContainer" : "facilityListContainer";
  const items = document.querySelectorAll(`#${containerId} .asset-item`);

  // 1. Check the URL for parameters sent by the Dashboard
  const urlParams = new URLSearchParams(window.location.search);
  const targetId = urlParams.get("id");
  const targetTab = urlParams.get("tab");

  let itemToSelect = null;

  // 2. If the URL has an ID for the current tab, find that exact row
  if (targetId) {
    if (
      (viewType === "computer" && targetTab === "units") ||
      (viewType === "facility" && targetTab === "assets")
    ) {
      const targetAttr =
        viewType === "computer" ? "data-set-id" : "data-asset-id";
      itemToSelect = document.querySelector(
        `#${containerId} .asset-item[${targetAttr}="${targetId}"]`,
      );
    }
  }

  // 3. Fallback: If no URL ID (or item wasn't found), just pick the first visible item
  if (!itemToSelect) {
    for (let item of items) {
      if (item.style.display !== "none") {
        itemToSelect = item;
        break;
      }
    }
  }

  // 4. Click the item and scroll it into view!
  if (itemToSelect) {
    if (viewType === "computer") {
      const setId = itemToSelect.getAttribute("data-set-id");
      if (setId) selectUnit(itemToSelect, setId);
    } else {
      const assetId = itemToSelect.getAttribute("data-asset-id");
      if (assetId) selectFacilityAsset(itemToSelect, assetId);
    }

    // Smoothly scroll down the list so the selected item is perfectly centered
    setTimeout(() => {
      itemToSelect.scrollIntoView({ behavior: "smooth", block: "center" });
    }, 150);
  } else {
    // Failsafe if the list is completely empty
    const headerQuery =
      viewType === "computer"
        ? "#view-computer .right-panel .section-header-row h3"
        : "#view-facility-right .section-header-row h3";
    const rightPanelHeader = document.querySelector(headerQuery);
    if (rightPanelHeader) rightPanelHeader.innerHTML = "No Items Found";
  }
}

// ==========================================
// SEARCH & FILTER
// ==========================================
let currentComputerFilter = "All";
let currentFAFilter = "All";

function toggleFilterMenu() {
  document.getElementById("filterMenu").classList.toggle("show");
}
function toggleFAFilterMenu() {
  const menu = document.getElementById("faFilterMenu");
  if (menu) menu.classList.toggle("show");
}

function filterAssets(status) {
  currentComputerFilter = status;
  document.getElementById("filterMenu").classList.remove("show");
  applyComputerFilters();
}

function filterFAAssets(status) {
  currentFAFilter = status;
  const menu = document.getElementById("faFilterMenu");
  if (menu) menu.classList.remove("show");
  applyFAFilters();
}

function searchAssets() {
  applyComputerFilters();
}
function searchFAAssets() {
  applyFAFilters();
}

function applyComputerFilters() {
  const input = document.getElementById("searchInput");
  if (!input) return;
  const filterText = input.value.toLowerCase();

  const items = document.querySelectorAll("#assetListContainer .asset-item");
  items.forEach((item) => {
    const nameEl = item.querySelector(".item-name");
    const badgeEl = item.querySelector(".badge");
    if (!nameEl || !badgeEl) return;

    const name = nameEl.textContent.toLowerCase();
    const physicalStatus = badgeEl.textContent.trim();
    const isForCondemn = item.getAttribute("data-is-condemn") === "true";

    const matchesSearch = name.includes(filterText);
    let matchesFilter = false;

    if (currentComputerFilter === "All") {
      matchesFilter = true;
    } else if (currentComputerFilter === "For Condemn") {
      matchesFilter = isForCondemn;
    } else {
      matchesFilter = physicalStatus === currentComputerFilter;
    }

    item.style.display = matchesSearch && matchesFilter ? "flex" : "none";
  });

  autoSelectFirstVisibleItem("computer");
}

function applyFAFilters() {
  const input = document.getElementById("faSearchInput");
  if (!input) return;
  const filterText = input.value.toLowerCase();

  const items = document.querySelectorAll("#facilityListContainer .asset-item");
  items.forEach((item) => {
    const nameEl = item.querySelector(".item-name");
    const badgeEl = item.querySelector(".badge");
    if (!nameEl || !badgeEl) return;

    const name = nameEl.textContent.toLowerCase();
    const status = badgeEl.textContent.trim();

    const matchesSearch = name.includes(filterText);
    const matchesFilter =
      currentFAFilter === "All" || status === currentFAFilter;

    item.style.display = matchesSearch && matchesFilter ? "flex" : "none";
  });

  autoSelectFirstVisibleItem("facility");
}

// ==========================================
// DATA FETCHING & RENDERING (COMPUTER UNITS)
// ==========================================

function selectUnit(element, setId) {
  // --- ADDED: Blocker inside selection function ---
  if (isEditModeActive) {
      showNotification(
          "Action Blocked",
          "Please save or cancel your current report before selecting another item.",
          "error"
      );
      return;
  }
  // ------------------------------------------------

  currentEditingSetId = setId;

  document
    .querySelectorAll("#assetListContainer .asset-item")
    .forEach((item) => item.classList.remove("active"));
  if (element) element.classList.add("active");

  const unitName = element
    ? element.querySelector(".item-name").innerText
    : "Unit";
  document.querySelector(".right-panel .section-header-row h3").innerHTML =
    `${unitName} Details`;

  setTabsDisabled(false);

  fetch(`includes/get_unit_details.php?set_id=${setId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        populateRightPanel(data.data);
        resetUIToViewMode();

        // --- POPULATE RECENT ACTIVITY LOGS ---
        const historyBody = document.getElementById("pc_activity_log_body");
        if (historyBody) {
          historyBody.innerHTML = "";

          if (data.history && data.history.length > 0) {
            data.history.forEach((log, index) => {
              let badgeColor = "gray";
              if (
                log.report_status === "Resolved" ||
                log.report_status === "Working"
              )
                badgeColor = "green";
              if (
                log.report_status === "Reported" ||
                log.report_status === "For Repair"
              )
                badgeColor = "yellow";
              if (log.report_status === "Condemned") badgeColor = "red";

              const item = document.createElement("div");
              item.style.padding = "15px 20px";
              item.style.backgroundColor = "#fff";

              if (index < data.history.length - 1) {
                item.style.borderBottom = "1px solid #eaeaea";
              }

              item.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <div style="font-size: 13px; color: #555;">
                                    <strong style="color: #1b4d3e; font-size: 14px;"><i class="fas fa-user-circle"></i> ${log.report_actor || "System"}</strong> 
                                    <span style="margin-left: 8px; color: #888;"><i class="far fa-clock"></i> ${log.formatted_date}</span>
                                </div>
                                <span class="badge ${badgeColor}">${log.report_status || "Logged"}</span>
                            </div>
                            <div style="font-size: 13px; color: #333;">
                                <div style="margin-bottom: 8px;"><strong>Affected:</strong> <span style="color: #d32f2f; font-weight: 500;">${log.report_affected || "N/A"}</span></div>
                                <div style="background: #f4f6f8; padding: 10px 12px; border-radius: 6px; color: #555; border-left: 3px solid #ccc;">
                                    <em>"${log.report_remarks || "No remarks provided"}"</em>
                                </div>
                            </div>
                        `;
              historyBody.appendChild(item);
            });
          } else {
            historyBody.innerHTML =
              '<div style="text-align:center; color:#888; padding: 25px;">No recent maintenance activity found.</div>';
          }
        }
      } else {
        console.error("Error fetching details:", data.error);
      }
    })
    .catch((error) => console.error("Fetch Error:", error));

  if (window.innerWidth <= 768) {
    document
      .getElementById("view-computer")
      .querySelector(".split-layout")
      .classList.add("mobile-show-details");
  }
}

function populateRightPanel(data) {
  for (const [key, value] of Object.entries(data)) {
    const viewEl = document.getElementById("view_" + key);

    if (viewEl) {
      if (key === "com_age") {
        const ageVal = parseInt(value) || 0;
        viewEl.innerText = value !== null ? ageVal + " Years" : "0 Years";

        const condemnBadge = document.getElementById("view_condemn_badge");
        if (condemnBadge) {
          if (ageVal >= 5) {
            condemnBadge.innerHTML =
              '<span class="badge red">For Condemn</span>';
          } else {
            condemnBadge.innerHTML = "";
          }
        }
      } else {
        viewEl.innerText = value || "N/A";
      }
    }

    const toggleGroup = document.getElementById("toggle_" + key);
    if (toggleGroup) {
      const pill = document.getElementById("pill_" + key);

      if (pill) {
        let displayValue = value || "Unknown";

        if (key === "disk_health") {
          if (value === "Working" || value === "Healthy")
            displayValue = "Healthy";
          if (value === "For Repair" || value === "Poor") displayValue = "Poor";
        }

        pill.innerText = displayValue;

        let pillColor = "purple";
        if (value === "Working" || value === "Healthy") pillColor = "green";
        if (value === "For Condemn") pillColor = "red";
        if (value === "For Repair" || value === "Poor") pillColor = "orange";

        pill.className = `status-pill view-mode ${pillColor}`;
      }

      toggleGroup.querySelectorAll(".status-btn").forEach((btn) => {
        btn.classList.remove("active");

        const targetType =
          value === "For Repair" || value === "Poor" ? "repair" : "working";

        if (btn.getAttribute("data-type") === targetType) {
          btn.classList.add("active");
        }
      });
    }
  }
}

function resetUIToViewMode() {
  isEditModeActive = false; // <-- ADDED: Turn off the lock
  
  const btn = document.getElementById("btnReport");
  const btnCancel = document.getElementById("btnCancelReport");

  if (btn)
    btn.innerHTML = `<i class="fas fa-edit"></i> <span id="reportText">Report</span>`;
  if (btnCancel) btnCancel.style.display = "none";

  document
    .querySelectorAll(".specs-content-box .view-mode")
    .forEach((el) => (el.style.display = ""));
  document
    .querySelectorAll(".specs-content-box .edit-mode")
    .forEach((el) => (el.style.display = "none"));
}

// ==========================================
// DATA FETCHING & RENDERING (FACILITY ASSETS)
// ==========================================

function selectFacilityAsset(element, assetId) {
  // --- ADDED: Blocker inside selection function ---
  if (isEditModeActive) {
      showNotification(
          "Action Blocked",
          "Please save or cancel your current report before selecting another item.",
          "error"
      );
      return;
  }
  // ------------------------------------------------

  currentSelectedFAId = assetId;
  currentEditingSetId = null;

  document
    .querySelectorAll(".asset-item")
    .forEach((item) => item.classList.remove("active"));
  if (element) element.classList.add("active");

  const rightPanel = document.getElementById("view-facility-right");
  rightPanel.style.display = "block";

  fetch(`includes/get_facility_asset_details.php?asset_id=${assetId}`)
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        const asset = data.data;

        document.getElementById("view_fa_header_title").innerText =
          `FA-${asset.asset_tag} - ${asset.asset_name} Details`;
        document.getElementById("view_fa_tag").innerText =
          asset.asset_property || "N/A";
        document.getElementById("view_fa_brand").innerText =
          asset.asset_brand || "N/A";
        document.getElementById("view_fa_status").innerText =
          asset.asset_status;

        const statusBox = document.getElementById("view_fa_status_box");
        statusBox.classList.remove(
          "status-box-green",
          "status-box-yellow",
          "status-box-red",
        );
        statusBox.classList.add("detail-box");

        if (asset.asset_status === "Working") {
          statusBox.classList.add("status-box-green");
        } else if (asset.asset_status === "For Repair") {
          statusBox.classList.add("status-box-yellow");
        } else {
          statusBox.classList.add("status-box-red");
        }

        // --- POPULATE FA RECENT ACTIVITY LOGS ---
        const historyBody = document.getElementById("fa_activity_log_body");
        if (historyBody) {
          historyBody.innerHTML = "";

          if (data.history && data.history.length > 0) {
            data.history.forEach((log, index) => {
              let badgeColor = "gray";
              if (
                log.report_status === "Resolved" ||
                log.report_status === "Working"
              )
                badgeColor = "green";
              if (
                log.report_status === "Reported" ||
                log.report_status === "For Repair"
              )
                badgeColor = "yellow";
              if (log.report_status === "Condemned") badgeColor = "red";

              const item = document.createElement("div");
              item.style.padding = "15px 20px";
              item.style.backgroundColor = "#fff";

              if (index < data.history.length - 1) {
                item.style.borderBottom = "1px solid #eaeaea";
              }

              item.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <div style="font-size: 13px; color: #555;">
                                    <strong style="color: #1b4d3e; font-size: 14px;"><i class="fas fa-user-circle"></i> ${log.report_actor || "System"}</strong> 
                                    <span style="margin-left: 8px; color: #888;"><i class="far fa-clock"></i> ${log.formatted_date}</span>
                                </div>
                                <span class="badge ${badgeColor}">${log.report_status || "Logged"}</span>
                            </div>
                            <div style="font-size: 13px; color: #333;">
                                <div style="background: #f4f6f8; padding: 10px 12px; border-radius: 6px; color: #555; border-left: 3px solid #ccc;">
                                    <em>"${log.report_remarks || "No remarks provided"}"</em>
                                </div>
                            </div>
                        `;
              historyBody.appendChild(item);
            });
          } else {
            historyBody.innerHTML =
              '<div style="text-align:center; color:#888; padding: 25px;">No recent maintenance activity found.</div>';
          }
        }
        
        // --- ADDED: Turn off edit mode lock after loading new FA details ---
        isEditModeActive = false; 
        
      } else {
        showNotification("Error", data.error, "error");
      }
    })
    .catch((err) => console.error("Fetch error:", err));

  if (window.innerWidth <= 768) {
    document
      .getElementById("view-facility")
      .querySelector(".split-layout")
      .classList.add("mobile-show-details");
  }
}

// ==========================================
// STAFF REPORT MODE WORKFLOW
// ==========================================

let pendingAffectedString = "";
let currentReportType = "pc";
let currentHasIssues = false;

// --- COMPUTER UNITS REPORT ---
function toggleReportMode() {
  if (!currentEditingSetId) {
    alert("Please select a unit from the list first.");
    return;
  }

  const btn = document.getElementById("btnReport");
  const textSpan = document.getElementById("reportText");
  const btnCancel = document.getElementById("btnCancelReport");

  if (textSpan.innerText === "Report") {
    isEditModeActive = true; // <-- ADDED: Turn ON the lock
    
    textSpan.innerText = "Submit";
    btn.innerHTML = `<i class="fas fa-paper-plane"></i> <span id="reportText">Submit</span>`;
    btnCancel.style.display = "inline-block";

    document
      .querySelectorAll(".specs-content-box .view-mode.status-pill")
      .forEach((el) => (el.style.display = "none"));
    document
      .querySelectorAll(".specs-content-box .edit-mode.status-toggle-group")
      .forEach((el) => (el.style.display = "flex"));
  } else {
    openLogStatusModal();
  }
}

function cancelReportMode() {
  isEditModeActive = false; // <-- ADDED: Turn OFF the lock

  if (currentEditingSetId) {
    const activeItem = document.querySelector(
      "#assetListContainer .asset-item.active",
    );
    selectUnit(activeItem, currentEditingSetId);
  }

  const btn = document.getElementById("btnReport");
  const btnCancel = document.getElementById("btnCancelReport");
  btn.innerHTML = `<i class="fas fa-edit"></i> <span id="reportText">Report</span>`;
  btnCancel.style.display = "none";
}

function openLogStatusModal() {
  currentReportType = "pc";
  currentHasIssues = false;
  const statusChanges = [];

  const nameMap = {
    usb_status: "USB Port",
    wifi_status: "Wi-Fi",
    mic_status: "Microphone Jack",
    hdmi_status: "HDMI",
    headphone_status: "Headphone Jack",
    display_status: "Display Port",
    inline_status: "In-line Jack",
    ethernet_status: "Ethernet Port",
    disk_health: "Disk Health",
    power_health: "Power Supply",
    monitor_status: "Monitor",
    mouse_status: "Mouse",
    keyboard_status: "Keyboard",
    avr_status: "AVR",
  };

  // Strictly target #view-computer to avoid state leakage
  document
    .querySelectorAll("#view-computer .specs-content-box .status-toggle-group")
    .forEach((group) => {
      if (group.style.display !== "none") {
        const dbColumn = group.id.replace("toggle_", "");
        const originalPill = document.getElementById("pill_" + dbColumn);
        const activeBtn = group.querySelector(".status-btn.active");

        if (originalPill && activeBtn) {
          const oldStatus = originalPill.innerText.trim();
          let newStatus =
            activeBtn.getAttribute("data-type") === "repair"
              ? "For Repair"
              : "Working";
          if (dbColumn === "disk_health")
            newStatus =
              activeBtn.getAttribute("data-type") === "repair"
                ? "Poor"
                : "Healthy";

          if (oldStatus !== newStatus) {
            const niceName = nameMap[dbColumn] || dbColumn;
            statusChanges.push({
              name: niceName,
              old: oldStatus,
              new: newStatus,
            });
            if (newStatus === "For Repair" || newStatus === "Poor")
              currentHasIssues = true;
          }
        }
      }
    });

  pendingAffectedString =
    statusChanges.length > 0
      ? statusChanges.map((c) => c.name).join(", ")
      : "Entire Unit";

  const activeItem = document.querySelector(
    "#assetListContainer .asset-item.active .item-name",
  );
  const unitName = activeItem ? activeItem.innerText : "Unknown Unit";
  document.getElementById("logStatusUnitName").innerText = `[${unitName}]`;

  // --- Build Admin-Style Cards with Embedded Remarks ---
  let htmlContent = "";

  if (statusChanges.length > 0) {
    statusChanges.forEach((stat) => {
      const isRepair = stat.new === "For Repair" || stat.new === "Poor";
      const pillBg = isRepair ? "#fff3e0" : "#e8f5e9";
      const pillColor = isRepair ? "#e65100" : "#2e7d32";
      const icon = isRepair ? "fa-exclamation-circle" : "fa-check-circle";
      const placeholderText = isRepair
        ? "REQUIRED: Why is this marked for repair?"
        : "Optional: General notes...";
      const bgTint = isRepair ? "#fffbfa" : "#fff";

      htmlContent += `
        <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                <h4 style="margin: 0; font-size: 14px; color: ${pillColor};">
                    <i class="fas ${icon}"></i> Status Update
                </h4>
                <span style="background-color: ${pillBg}; color: ${pillColor}; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 700;">
                    ${stat.new}
                </span>
            </div>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px; font-size: 13px; color: #555; background: #f4f4f4; padding: 10px; border-radius: 6px;">
                    <strong>Affected:</strong><br>
                    <span style="display: inline-block; margin-top: 5px;">${stat.name}</span>
                </div>
                <div style="flex: 2; min-width: 200px; display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 13px; font-weight: 600; color: #333;">Remarks:</label>
                    <textarea class="log-remark-input status-remark-field" data-name="${stat.name}" data-issue="${isRepair}" placeholder="${placeholderText}" style="width: 100%; height: 60px; padding: 10px; border-radius: 6px; border: 1px solid #ddd; resize: none; font-size: 13px; box-sizing: border-box; background-color: ${bgTint};"></textarea>
                </div>
            </div>
        </div>
      `;
    });
  } else {
    htmlContent += `
      <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
          <div style="text-align: center; margin-bottom: 15px;">
              <i class="fas fa-check-circle" style="color: #4caf50; font-size: 24px; margin-bottom: 10px;"></i>
              <h4 style="margin: 0; font-size: 14px; color: #333;">No Issues Found</h4>
              <p style="font-size: 13px; color: #666; margin-top: 5px;">No status changes were detected. You can submit this as a routine check.</p>
          </div>
          <div style="display: flex; flex-direction: column; gap: 5px; border-top: 1px solid #eee; padding-top: 15px;">
              <label style="font-size: 13px; font-weight: 600; color: #333;">Additional Remarks (Optional):</label>
              <textarea class="log-remark-input general-remark-field" placeholder="Optional: Any general notes..." style="width: 100%; height: 60px; padding: 10px; border-radius: 6px; border: 1px solid #ddd; resize: none; font-size: 13px; box-sizing: border-box;"></textarea>
          </div>
      </div>
    `;
  }

  document.getElementById("logStatusChangeList").innerHTML = htmlContent;
  openModal("logStatusModal");
}

// --- FACILITY ASSETS REPORT ---
function toggleFAReportMode() {
  if (!currentSelectedFAId) {
    alert("Please select an asset from the list first.");
    return;
  }

  const btn = document.getElementById("btnReportFA");
  const textSpan = document.getElementById("reportTextFA");
  const btnCancel = document.getElementById("btnCancelReportFA");

  if (textSpan.innerText.trim() === "Report") {
    isEditModeActive = true; // <-- ADDED: Turn ON the lock
    
    textSpan.innerText = "Submit";
    btn.innerHTML = `<i class="fas fa-paper-plane"></i> <span id="reportTextFA">Submit</span>`;
    btnCancel.style.display = "inline-block";

    const currentStatus = document
      .getElementById("view_fa_status")
      .innerText.trim();
    document
      .querySelectorAll("#toggle_fa_status .status-btn")
      .forEach((btn) => {
        btn.classList.remove("active");
        if (btn.getAttribute("data-type") === currentStatus)
          btn.classList.add("active");
      });

    document
      .querySelectorAll(".view-mode-fa")
      .forEach((el) => (el.style.display = "none"));
    document
      .querySelectorAll(".edit-mode-fa")
      .forEach((el) => (el.style.display = "flex"));
  } else {
    openFALogStatusModal();
  }
}

function cancelFAReportMode() {
  isEditModeActive = false; // <-- ADDED: Turn OFF the lock
  
  if (currentSelectedFAId) {
    const activeItem = document.querySelector(
      "#facilityListContainer .asset-item.active",
    );
    selectFacilityAsset(activeItem, currentSelectedFAId);
  }
  const btn = document.getElementById("btnReportFA");
  const btnCancel = document.getElementById("btnCancelReportFA");
  if (btn)
    btn.innerHTML = `<i class="fas fa-edit"></i> <span id="reportTextFA">Report</span>`;
  if (btnCancel) btnCancel.style.display = "none";

  document
    .querySelectorAll(".view-mode-fa")
    .forEach((el) => (el.style.display = ""));
  document
    .querySelectorAll(".edit-mode-fa")
    .forEach((el) => (el.style.display = "none"));
}

function openFALogStatusModal() {
  currentReportType = "fa";
  const oldStatus = document.getElementById("view_fa_status").innerText.trim();
  const activeBtn = document.querySelector(
    "#toggle_fa_status .status-btn.active",
  );
  const newStatus = activeBtn ? activeBtn.getAttribute("data-type") : "Working";

  currentHasIssues =
    newStatus === "For Repair" ||
    newStatus === "repair" ||
    newStatus === "Missing Parts";

  const headerTitle = document.getElementById("view_fa_header_title").innerText;
  const assetName = headerTitle.replace(" Details", "").trim();
  document.getElementById("logStatusUnitName").innerText = `[${assetName}]`;

  let htmlContent = "";
  const formatNewStatus = currentHasIssues ? "For Repair" : "Working";

  if (oldStatus !== formatNewStatus) {
    const pillBg = currentHasIssues ? "#fff3e0" : "#e8f5e9";
    const pillColor = currentHasIssues ? "#e65100" : "#2e7d32";
    const icon = currentHasIssues ? "fa-exclamation-circle" : "fa-check-circle";
    const placeholderText = currentHasIssues
      ? "REQUIRED: Why is this marked for repair?"
      : "Optional: General notes...";
    const bgTint = currentHasIssues ? "#fffbfa" : "#fff";

    htmlContent += `
        <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                <h4 style="margin: 0; font-size: 14px; color: ${pillColor};">
                    <i class="fas ${icon}"></i> Status Update
                </h4>
                <span style="background-color: ${pillBg}; color: ${pillColor}; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 700;">
                    ${formatNewStatus}
                </span>
            </div>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px; font-size: 13px; color: #555; background: #f4f4f4; padding: 10px; border-radius: 6px;">
                    <strong>Affected:</strong><br>
                    <span style="display: inline-block; margin-top: 5px;">${assetName}</span>
                </div>
                <div style="flex: 2; min-width: 200px; display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 13px; font-weight: 600; color: #333;">Remarks:</label>
                    <textarea class="log-remark-input status-remark-field" data-name="${assetName}" data-issue="${currentHasIssues}" placeholder="${placeholderText}" style="width: 100%; height: 60px; padding: 10px; border-radius: 6px; border: 1px solid #ddd; resize: none; font-size: 13px; box-sizing: border-box; background-color: ${bgTint};"></textarea>
                </div>
            </div>
        </div>
     `;
  } else {
    htmlContent += `
      <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
          <div style="text-align: center; margin-bottom: 15px;">
              <i class="fas fa-check-circle" style="color: #4caf50; font-size: 24px; margin-bottom: 10px;"></i>
              <h4 style="margin: 0; font-size: 14px; color: #333;">No Issues Found</h4>
              <p style="font-size: 13px; color: #666; margin-top: 5px;">No status changes were detected. You can submit this as a routine check.</p>
          </div>
          <div style="display: flex; flex-direction: column; gap: 5px; border-top: 1px solid #eee; padding-top: 15px;">
              <label style="font-size: 13px; font-weight: 600; color: #333;">Additional Remarks (Optional):</label>
              <textarea class="log-remark-input general-remark-field" placeholder="Optional: Any general notes..." style="width: 100%; height: 60px; padding: 10px; border-radius: 6px; border: 1px solid #ddd; resize: none; font-size: 13px; box-sizing: border-box;"></textarea>
          </div>
      </div>
    `;
  }

  document.getElementById("logStatusChangeList").innerHTML = htmlContent;
  openModal("logStatusModal");
}

// --- SUBMISSION HANDLER ---
function confirmLogStatus() {
  const saveBtn = document.querySelector("#logStatusModal .btn-confirm");
  let allValid = true;

  // 1. Gather & Validate Card Textareas
  document.querySelectorAll(".status-remark-field").forEach((textarea) => {
    const val = textarea.value.trim();
    const isIssue = textarea.getAttribute("data-issue") === "true";
    if (isIssue && val === "") {
      allValid = false;
      textarea.style.border = "1px solid #f44336"; // Highlight empty required fields
    } else {
      textarea.style.border = "1px solid #ddd";
    }
  });

  // Stop submission if a required box is empty
  if (!allValid) {
    showNotification(
      "Remarks Required",
      "Please describe the issue for all broken components.",
      "error",
    );
    return;
  }

  // --- UI Loading State ---
  const originalBtnHTML = saveBtn.innerHTML;
  saveBtn.disabled = true;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  // A. IF FACILITY ASSET
  if (currentReportType === "fa") {
    const activeBtn = document.querySelector(
      "#toggle_fa_status .status-btn.active",
    );
    const newStatus = activeBtn
      ? activeBtn.getAttribute("data-type")
      : "Working";
    const formattedStatus =
      newStatus === "repair" || newStatus === "For Repair"
        ? "For Repair"
        : "Working";

    // Format Remarks and Log Status
    const remarksBox =
      document.querySelector(".status-remark-field") ||
      document.querySelector(".general-remark-field");
    let faRemark = remarksBox ? remarksBox.value.trim() : "";
    if (!currentHasIssues && faRemark === "")
      faRemark = "Routine check. No issues found.";

    // Set to "Updated" if there are no issues!
    const historyStatus = currentHasIssues ? "For Repair" : "Updated";

    const formData = new FormData();
    formData.append("asset_id", currentSelectedFAId);
    formData.append("remarks", faRemark);
    formData.append("report_status", historyStatus); // Goes to History log
    formData.append("overall_status", formattedStatus); // Goes to Asset table

    fetch("includes/staff_log_report_fa.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          closeModal("logStatusModal");
          reloadWithToast(
            "Report Logged",
            "Facility asset maintenance request submitted.",
            "success",
          );
        } else {
          showNotification(
            "Error",
            data.error || "Failed to save log.",
            "error",
          );
          saveBtn.disabled = false;
          saveBtn.innerHTML = originalBtnHTML;
        }
      })
      .catch((err) => {
        showNotification(
          "Connection Error",
          "Could not reach server.",
          "error",
        );
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalBtnHTML;
      });
    return;
  }

  // B. IF COMPUTER UNIT
  if (currentReportType === "pc") {
    let componentLogs = []; // JSON array of individual logs

    if (currentHasIssues) {
      // Log each broken component individually
      document.querySelectorAll(".status-remark-field").forEach((textarea) => {
        const val = textarea.value.trim();
        const isIssue = textarea.getAttribute("data-issue") === "true";
        const compName = textarea.getAttribute("data-name");

        if (isIssue) {
          componentLogs.push({
            affected: compName,
            remark: val,
            status: "For Repair",
          });
        }
      });

      // Also grab optional general remarks if they typed them
      const generalBox = document.querySelector(".general-remark-field");
      if (generalBox && generalBox.value.trim() !== "") {
        componentLogs.push({
          affected: "General",
          remark: generalBox.value.trim(),
          status: "Updated",
        });
      }
    } else {
      // No Issues: Log the entire unit as "Updated"
      const generalBox = document.querySelector(".general-remark-field");
      let genRemark =
        generalBox && generalBox.value.trim() !== ""
          ? generalBox.value.trim()
          : "Routine check. No issues found.";
      componentLogs.push({
        affected: "Entire Unit",
        remark: genRemark,
        status: "Updated",
      });
    }

    const formData = new FormData();
    formData.append("set_id", currentEditingSetId);
    formData.append(
      "overall_status",
      currentHasIssues ? "For Repair" : "Working",
    );
    formData.append("component_logs", JSON.stringify(componentLogs));

    // Scoped query to avoid FA leakage into PC data
    document
      .querySelectorAll(
        "#view-computer .specs-content-box .status-toggle-group",
      )
      .forEach((group) => {
        if (group.style.display !== "none") {
          const dbColumn = group.id.replace("toggle_", "");
          const activeBtn = group.querySelector(".status-btn.active");
          if (activeBtn) {
            const val =
              activeBtn.getAttribute("data-type") === "repair"
                ? "For Repair"
                : "Working";
            formData.append(dbColumn, val);
          }
        }
      });

    fetch("includes/staff_log_report.php", { method: "POST", body: formData })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          closeModal("logStatusModal");
          reloadWithToast(
            "Report Logged",
            "Maintenance request submitted successfully.",
            "success",
          );
        } else {
          showNotification(
            "Database Error",
            data.error || "Failed to save log.",
            "error",
          );
          saveBtn.disabled = false;
          saveBtn.innerHTML = originalBtnHTML;
        }
      })
      .catch((err) => {
        showNotification(
          "Connection Error",
          "Could not reach server.",
          "error",
        );
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalBtnHTML;
      });
  }
}