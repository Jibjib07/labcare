document.addEventListener("DOMContentLoaded", function () {
  const supplyListContainer = document.getElementById("supplyListContainer");
  const tableRows = document.querySelectorAll(".supply-row");
  const isMobile = window.innerWidth <= 900;

  // --- Global State Trackers ---
  let isEditModeActive = false;
  let snapID = "";     // Tracks the ID of the selected supply
  let snapName = "";   // Tracks the Name for modal display
  let snapStatus = ""; // Tracks the Status for conditional logic

  // --- Helper: Force Close Edit Mode ---
  function forceCloseSupplyEditMode() {
    if (isEditModeActive) {
      isEditModeActive = false;
      const viewArea = document.getElementById("view-mode");
      const editArea = document.getElementById("edit-mode");

      if (editArea) editArea.style.display = "none";
      if (viewArea) viewArea.style.display = "block";

      const activeRow = document.querySelector(".supply-row.active-row");
      if (activeRow) {
        handleRowSelection(activeRow);
      }
    }
  }

  // --- 1. TOAST SYSTEM ---
  function showNotification(title, message, type = "success") {
    const container = document.getElementById("toast-container");
    if (!container) return;

    const typeClass = type === "danger" || type === "error" ? "error" : "success";
    const toast = document.createElement("div");
    toast.className = `toast toast-${typeClass}`;

    const iconClass = typeClass === "success" ? "fa-check-circle" : "fa-exclamation-circle";

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
      toast.addEventListener("animationend", (e) => {
        if (e.animationName === "fadeOut") toast.remove();
      }, { once: true });

      setTimeout(() => { if (toast.parentNode) toast.remove(); }, 600);
    }, 3500);
  }

  // Handle PHP Redirect Success/Error Messages
  const phpSuccess = document.getElementById("php_success");
  if (phpSuccess) {
    const messages = {
      added: "New supply added to inventory.",
      updated: "Supply details have been updated.",
      archived: "Item has been moved to Archive.",
      restored: "Item has been restored to Current Inventory."
    };
    showNotification("Action Successful", messages[phpSuccess.value] || "Inventory updated.", "success");
  }

  // --- 2. MULTI-LAYER FILTER & AUTO-SELECT LOGIC ---
  const searchInput = document.getElementById("tableSearch");
  const statusFilter = document.getElementById("statusFilter");
  const switchBtns = document.querySelectorAll(".switch-btn");
  let currentAvailFilter = "Current";

  function selectFirstVisibleRow() {
    if (isMobile) return;

    const urlParams = new URLSearchParams(window.location.search);
    const targetId = urlParams.get("id");

    let rowToSelect = null;
    if (targetId) {
      rowToSelect = Array.from(tableRows).find(row => row.getAttribute("data-id") === targetId);
    }

    if (!rowToSelect || rowToSelect.style.display === "none") {
      rowToSelect = Array.from(tableRows).find(row => row.style.display !== "none");
    }

    if (rowToSelect) {
      handleRowSelection(rowToSelect);
    }
  }

  function filterTable() {
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
    const selectedStockValue = statusFilter ? statusFilter.value : "all";
    const activeTab = currentAvailFilter;

    tableRows.forEach((row) => {
      const rowAvail = row.getAttribute("data-avail");
      const supplyName = row.querySelector(".item-name")?.innerText.toLowerCase() || "";
      const rowStatusText = row.querySelector(".badge")?.innerText.trim().toLowerCase() || "";

      const matchesTab = rowAvail === activeTab;
      const matchesSearch = supplyName.includes(searchTerm);
      const filterStatusText = selectedStockValue.replace(/_/g, " ");
      const matchesStock = activeTab === "Archived" || selectedStockValue === "all" || rowStatusText === filterStatusText;

      if (matchesTab && matchesSearch && matchesStock) {
        row.style.setProperty("display", "flex", "important");
      } else {
        row.style.setProperty("display", "none", "important");
      }
    });

    selectFirstVisibleRow();
  }

  function applyTabUI(value) {
    switchBtns.forEach((b) => {
      b.classList.remove("active");
      if (b.getAttribute("data-value") === value) b.classList.add("active");
    });

    currentAvailFilter = value;
    const editWrapper = document.getElementById("edit-action-wrapper");
    const archWrapper = document.getElementById("archive-action-wrapper");
    const restWrapper = document.getElementById("restore-action-wrapper");

    if (currentAvailFilter === "Archived") {
      if (statusFilter) statusFilter.classList.add("hidden-filter");
      if (editWrapper) editWrapper.style.display = "none";
      if (archWrapper) archWrapper.style.display = "none";
      if (restWrapper) restWrapper.style.display = "inline-block";
    } else {
      if (statusFilter) statusFilter.classList.remove("hidden-filter");
      if (editWrapper) editWrapper.style.display = "inline-block";
      if (archWrapper) archWrapper.style.display = "inline-block";
      if (restWrapper) restWrapper.style.display = "none";
    }
  }

  switchBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      forceCloseSupplyEditMode();
      applyTabUI(this.getAttribute("data-value"));
      filterTable();
    });
  });

  if (searchInput) searchInput.addEventListener("input", filterTable);
  if (statusFilter) statusFilter.addEventListener("change", filterTable);

  // --- 3. MODAL LOGIC (FIXED ID BINDING) ---
  const addModal = document.getElementById("addSupplyModal");
  const archiveConfirmModal = document.getElementById("archiveConfirmModal");
  const restoreConfirmModal = document.getElementById("restoreConfirmModal");

  // Add Button Click
  if (document.getElementById("openModalBtn")) {
    document.getElementById("openModalBtn").onclick = () => {
      forceCloseSupplyEditMode();
      addModal.style.setProperty("display", "flex", "important");
    };
  }

  // Archive Trigger
  if (document.getElementById("archiveTrigger")) {
    document.getElementById("archiveTrigger").onclick = function () {
      forceCloseSupplyEditMode();
      document.getElementById("confirm_supply_name").innerText = snapName;
      // Force ID into the archive hidden field
      document.getElementById("archive_supply_id").value = snapID;
      archiveConfirmModal.style.setProperty("display", "flex", "important");
    };
  }

  // Restore Trigger
  if (document.getElementById("restoreTrigger")) {
    document.getElementById("restoreTrigger").onclick = function () {
      forceCloseSupplyEditMode();
      document.getElementById("restore_confirm_supply_name").innerText = snapName;
      // Force ID into the restore hidden field
      document.getElementById("restore_supply_id").value = snapID;
      restoreConfirmModal.style.setProperty("display", "flex", "important");
    };
  }

  // Submit Archive via Hidden Button
  if (document.getElementById("finalArchiveBtn")) {
    document.getElementById("finalArchiveBtn").onclick = () => {
      document.getElementById("hiddenArchiveSubmit").click();
    };
  }

  // Close All Modals
  document.querySelectorAll(".close-modal, .btn-modal-cancel, #cancelArchiveConfirm, #cancelRestoreConfirm").forEach((btn) => {
    btn.onclick = function () {
      document.querySelectorAll(".modal-overlay").forEach(m => m.style.setProperty("display", "none", "important"));
    };
  });

  // --- 4. AJAX ROW SELECTION ---
  if (supplyListContainer) {
    supplyListContainer.addEventListener("click", function (e) {
      const row = e.target.closest(".supply-row");
      if (row) handleRowSelection(row);
    });
  }

  function handleRowSelection(row) {
    if (!row) return;

    if (isEditModeActive) {
      showNotification("Action Blocked", "Please save or cancel edits.", "error");
      return;
    }

    const id = row.getAttribute("data-id");
    document.querySelectorAll(".supply-row").forEach((r) => r.classList.remove("active-row"));
    row.classList.add("active-row");

    if (isMobile) {
      const layout = document.querySelector(".supply-layout");
      if (layout) layout.classList.add("mobile-show-details");
    }

    fetch(`supply_inventory.php?fetch_id=${id}`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          // LOCK DATA IN GLOBALS
          snapID = data.supply.supply_id;
          snapName = data.supply.supply_name.trim();
          snapStatus = data.supply.supply_status.trim();
          
          updateRightPanel(data.supply, data.history);
        }
      })
      .catch((err) => console.error("Fetch error:", err));
  }

  function updateRightPanel(supply, history) {
    const nameElement = document.getElementById("view_supply_name");
    if (nameElement) nameElement.innerText = supply.supply_name;

    const statusContainer = document.getElementById("view_supply_status");
    if (statusContainer) {
      const pillColor = supply.supply_status === "In Stock" ? "green" : "red";
      statusContainer.innerHTML = `<span class="status-pill ${pillColor}">${supply.supply_status}</span>`;
    }

    // Populate hidden inputs for forms
    document.getElementById("edit_supply_id").value = snapID;
    document.getElementById("archive_supply_id").value = snapID;
    document.getElementById("restore_supply_id").value = snapID;
    document.getElementById("edit_supply_name").value = snapName;
    document.getElementById("original_status").value = snapStatus;

    const feed = document.getElementById("activityFeed");
    if (!feed) return;
    feed.innerHTML = "";

    if (history && history.length > 0) {
      history.forEach((log, index) => {
        const card = document.createElement("div");
        card.className = "activity-card";
        if (index < history.length - 1) card.style.borderBottom = "1px solid #f0f0f0";

        // --- DATE FORMATTER: Parses "03/27/2026 12:00 AM" into "March 27, 2026" ---
        let formattedDate = log.date;
        const parsedDate = new Date(log.date);
        if (!isNaN(parsedDate)) {
            formattedDate = parsedDate.toLocaleDateString('en-US', {
                month: 'long',
                day: '2-digit',
                year: 'numeric'
            });
        }

        // --- UPDATED BADGE LOGIC FOR RESTORED AND ARCHIVED ---
        let badgeClass = "red";
        if (log.activity.includes('In Stock') || log.activity === "Added") {
            badgeClass = "green";
        } else if (log.activity === "Archived" || log.activity === "Restored") {
            badgeClass = "gray";
        }

        // --- UPDATED LAYOUT: Name and Date Stacked Vertically ---
        card.innerHTML = `
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
              <strong style="color: #1b4d3e; font-size: 13px;">
                <i class="fas fa-user-circle" style="margin-right: 4px;"></i>${log.user || "System"}
              </strong> 
              <span style="color: #999; font-size: 11px;">
                <i class="far fa-clock" style="margin-right: 4px;"></i>${formattedDate}
              </span>
            </div>
            <span class="badge ${badgeClass}" style="flex-shrink: 0; margin-left: 10px;">${log.activity}</span>
          </div>
          <div class="activity-remark-bubble">
            <p style="margin: 0; font-size: 13px; color: #444; line-height: 1.5; font-style: italic;">"${log.remarks || "No specific remarks provided."}"</p>
          </div>
        `;
        feed.appendChild(card);
      });
    } else {
      feed.innerHTML = `<div style="text-align:center; color:#bbb; padding: 40px;"><i class="fas fa-history" style="font-size: 24px; display: block; margin-bottom: 10px; color: #eee;"></i>No history found.</div>`;
    }
  }

  // --- 5. EDIT MODE LOGIC ---
  const viewArea = document.getElementById("view-mode");
  const editArea = document.getElementById("edit-mode");
  const statusBtns = document.querySelectorAll(".status-option-btn");
  const statusInput = document.getElementById("stock_status_value");

  if (document.getElementById("editTrigger")) {
    document.getElementById("editTrigger").onclick = () => {
      isEditModeActive = true;
      document.getElementById("update_remarks").value = "";
      viewArea.style.display = "none";
      editArea.style.display = "block";

      statusBtns.forEach((b) => b.classList.remove("active"));
      const targetBtn = document.querySelector(`.status-option-btn[data-value="${snapStatus}"]`);
      if (targetBtn) {
        targetBtn.classList.add("active");
        statusInput.value = snapStatus;
      }
    };
  }

  statusBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      statusBtns.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      statusInput.value = this.getAttribute("data-value");
    });
  });

  if (document.getElementById("cancelEdit")) {
    document.getElementById("cancelEdit").onclick = () => {
      isEditModeActive = false;
      editArea.style.display = "none";
      viewArea.style.display = "block";
    };
  }

  if (document.getElementById("updateForm")) {
    document.getElementById("updateForm").onsubmit = function (e) {
      const currentStatus = statusInput.value;
      const remarks = document.getElementById("update_remarks").value.trim();
      if (currentStatus !== snapStatus && remarks === "") {
        e.preventDefault();
        showNotification("Remarks Required", "Status changes must include a descriptive remark.", "error");
        document.getElementById("update_remarks").style.borderColor = "#f44336";
        return false;
      }
      return true;
    };
  }

  // --- 6. INITIALIZATION ---
  const urlParams = new URLSearchParams(window.location.search);
  const targetTab = urlParams.get("tab");
  const targetId = urlParams.get("id");

  applyTabUI(targetTab === "Archived" ? "Archived" : "Current");
  filterTable();

  // URL Cleaning
  if (urlParams.has("success") || targetId || targetTab) {
    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({ path: cleanUrl }, "", cleanUrl);
  }
});

// --- Mobile Navigation Function ---
function closeMobileDetails() {
  const layout = document.querySelector(".supply-layout");
  if (layout) layout.classList.remove("mobile-show-details");
}