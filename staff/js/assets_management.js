let currentEditingSetId = null; // For Computer Units
let currentSelectedFAId = null; // For Facility Assets

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
        const setId = this.getAttribute("data-set-id");
        if (setId) selectUnit(this, setId);
      });
    });

  document
    .querySelectorAll("#facilityListContainer .asset-item")
    .forEach((item) => {
      item.addEventListener("click", function () {
        const assetId = this.getAttribute("data-asset-id");
        if (assetId) selectFacilityAsset(this, assetId);
      });
    });
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
  const computerView = document.getElementById("view-computer");
  const facilityView = document.getElementById("view-facility");

  currentEditingSetId = null;
  currentSelectedFAId = null;

  document
    .querySelectorAll(".asset-item")
    .forEach((item) => item.classList.remove("active"));

  if (viewName === "computer") {
    computerView.style.display = "block";
    facilityView.style.display = "none";
  } else if (viewName === "facility") {
    computerView.style.display = "none";
    facilityView.style.display = "block";
    const right = document.getElementById("view-facility-right");
    if (right) right.style.display = "block";
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
}

// ==========================================
// DATA FETCHING & RENDERING (COMPUTER UNITS)
// ==========================================

function selectUnit(element, setId) {
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

        // --- POPULATE RECENT ACTIVITY LOGS (MOBILE-SAFE CARD FEED) ---
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
  const changes = [];
  const affectedNames = [];

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

  document
    .querySelectorAll(".specs-content-box .status-toggle-group")
    .forEach((group) => {
      if (group.style.display !== "none") {
        const dbColumn = group.id.replace("toggle_", "");
        const originalPill = document.getElementById("pill_" + dbColumn);
        const activeBtn = group.querySelector(".status-btn.active");

        if (originalPill && activeBtn) {
          const oldStatus = originalPill.innerText.trim();

          // Convert back end value to human readable string
          let newStatus =
            activeBtn.getAttribute("data-type") === "repair"
              ? "For Repair"
              : "Working";
          if (dbColumn === "disk_health") {
            newStatus =
              activeBtn.getAttribute("data-type") === "repair"
                ? "Poor"
                : "Healthy";
          }

          if (oldStatus !== newStatus) {
            const niceName = nameMap[dbColumn] || dbColumn;
            changes.push(`${niceName}: ${newStatus}`);
            if (newStatus === "For Repair" || newStatus === "Poor")
              affectedNames.push(niceName);
          }
        }
      }
    });

  if (changes.length === 0)
    changes.push("No specific component changes detected (General Update)");

  affectedNames.sort();
  pendingAffectedString =
    affectedNames.length > 0 ? affectedNames.join(", ") : "Entire Unit";

  const activeItem = document.querySelector(
    "#assetListContainer .asset-item.active .item-name",
  );
  const unitName = activeItem ? activeItem.innerText : "Unknown Unit";

  const listContainer = document.getElementById("logStatusChangeList");
  listContainer.innerHTML = changes
    .map((c) => `<div class="log-change-item">${c}</div>`)
    .join("");
  document.getElementById("logStatusUnitName").innerText = `[${unitName}]`;
  document.getElementById("logStatusRemarks").value = "";

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

  const changes = [];
  if (oldStatus !== newStatus) {
    changes.push(`Asset Status: ${newStatus}`);
  } else {
    changes.push("No status change detected (General Update)");
  }

  const headerTitle = document.getElementById("view_fa_header_title").innerText;
  const assetName = headerTitle.replace(" Details", "").trim();

  const listContainer = document.getElementById("logStatusChangeList");
  listContainer.innerHTML = changes
    .map((c) => `<div class="log-change-item">${c}</div>`)
    .join("");
  document.getElementById("logStatusUnitName").innerText = `[${assetName}]`;
  document.getElementById("logStatusRemarks").value = "";

  openModal("logStatusModal");
}

// --- SUBMIT WORKFLOW FOR BOTH ---
function confirmLogStatus() {
  const remarks = document.getElementById("logStatusRemarks").value.trim();
  const saveBtn = document.querySelector("#logStatusModal .btn-confirm");

  const originalBtnHTML = saveBtn.innerHTML;
  saveBtn.disabled = true;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  // A. IF FACILITY ASSET
  if (currentReportType === "fa") {
    if (
      !remarks &&
      document
        .getElementById("logStatusChangeList")
        .innerText.includes("No status change")
    ) {
      showNotification(
        "Remarks Required",
        "Please provide a description of the issue.",
        "error",
      );
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalBtnHTML;
      return;
    }

    const activeBtn = document.querySelector(
      "#toggle_fa_status .status-btn.active",
    );
    const newStatus = activeBtn
      ? activeBtn.getAttribute("data-type")
      : "Working";

    const formData = new FormData();
    formData.append("asset_id", currentSelectedFAId);
    formData.append("remarks", remarks);
    formData.append("report_status", newStatus);

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
  if (!remarks && pendingAffectedString === "Entire Unit") {
    showNotification(
      "Remarks Required",
      "Please provide a description of the issue.",
      "error",
    );
    saveBtn.disabled = false;
    saveBtn.innerHTML = originalBtnHTML;
    return;
  }

  const formData = new FormData();
  formData.append("set_id", currentEditingSetId);
  formData.append("remarks", remarks);
  formData.append("report_affected", pendingAffectedString);

  document
    .querySelectorAll(".specs-content-box .status-toggle-group")
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
      showNotification("Connection Error", "Could not reach server.", "error");
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalBtnHTML;
    });
}
