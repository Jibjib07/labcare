document.addEventListener("DOMContentLoaded", function () {
  const supplyListContainer = document.getElementById("supplyListContainer");
  const tableRows = document.querySelectorAll(".supply-row");
  let isEditModeActive = false;
  let snapName = "";
  let snapStatus = "";

  function forceCloseSupplyEditMode() {
    if (isEditModeActive) {
      isEditModeActive = false; // Turn off the lock instantly

      const viewArea = document.getElementById("view-mode");
      const editArea = document.getElementById("edit-mode");

      if (editArea) editArea.style.display = "none";
      if (viewArea) viewArea.style.display = "block";

      // Reload the active item to wipe out any unsaved typing
      const activeRow = document.querySelector(".supply-row.active-row");
      if (activeRow) {
        handleRowSelection(activeRow);
      }
    }
  }

  // --- SINGLE, CLEAN LISTENER FOR ROW CLICKS ---
  if (supplyListContainer) {
    supplyListContainer.addEventListener("click", function (e) {
      const row = e.target.closest(".supply-row");
      if (row) {
        handleRowSelection(row); // Pass the click to the handler
      }
    });
  }
  // --- 1. TOAST SYSTEM ---
  function showNotification(title, message, type = "success") {
    const container = document.getElementById("toast-container");
    if (!container) return;

    const typeClass =
      type === "danger" || type === "error" ? "error" : "success";
    const toast = document.createElement("div");
    toast.className = `toast toast-${typeClass}`;

    const iconClass =
      typeClass === "success" ? "fa-check-circle" : "fa-exclamation-circle";

    toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${iconClass}"></i></div>
        <div class="toast-content">
            <h4>${title}</h4>
            <p>${message}</p>
        </div>
    `;

    container.appendChild(toast);

    // After 3.5 seconds, start the fade-out animation
    setTimeout(() => {
      toast.classList.add("fade-out");

      // Use animationend because we are using @keyframes fadeOut
      toast.addEventListener(
        "animationend",
        (e) => {
          if (e.animationName === "fadeOut") {
            toast.remove();
          }
        },
        { once: true },
      );

      // Backup removal
      setTimeout(() => {
        if (toast.parentNode) toast.remove();
      }, 600);
    }, 3500);
  }
  // PHP Redirect Notifications
  const phpSuccess = document.getElementById("php_success");
  if (phpSuccess) {
    let msg = "Inventory updated.";
    if (phpSuccess.value === "added") msg = "New supply added to inventory.";
    if (phpSuccess.value === "updated")
      msg = "Supply details have been updated.";
    if (phpSuccess.value === "archived")
      msg = "Item has been moved to Archive.";
    if (phpSuccess.value === "restored")
      msg = "Item has been restored to Current Inventory.";

    // CHANGE THIS: from showToast to showNotification
    showNotification("Action Successful", msg, "success");
  }

  // --- 2. MULTI-LAYER FILTER & AUTO-SELECT LOGIC ---
  const searchInput = document.getElementById("tableSearch");
  const statusFilter = document.getElementById("statusFilter");
  const switchBtns = document.querySelectorAll(".switch-btn");
  let currentAvailFilter = "Current";

  function selectFirstVisibleRow() {
    // NEW: Stop auto-selection if we are on a mobile device
    if (window.innerWidth <= 900) return;

    const urlParams = new URLSearchParams(window.location.search);
    const targetId = urlParams.get("id");

    let rowToSelect = null;

    if (targetId) {
      rowToSelect = Array.from(tableRows).find(
        (row) => row.getAttribute("data-id") === targetId,
      );
    }

    if (!rowToSelect || rowToSelect.style.display === "none") {
      rowToSelect = Array.from(tableRows).find(
        (row) => row.style.display !== "none",
      );
    }

    if (rowToSelect) {
      handleRowSelection(rowToSelect);
      rowToSelect.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
  }
  function filterTable() {
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
    const selectedStockValue = statusFilter ? statusFilter.value : "all";

    // This variable tells us which tab is actually clicked (Current or Archived)
    // It should be updated whenever you click a switch-btn
    const activeTab = currentAvailFilter;

    tableRows.forEach((row) => {
      // 1. Get the availability of THIS specific row from the attribute
      const rowAvail = row.getAttribute("data-avail");

      // 2. Get the name and status for search/dropdown filtering
      const nameCell = row.querySelector(".item-name");
      const statusCell = row.querySelector(".badge");
      const supplyName = nameCell ? nameCell.innerText.toLowerCase() : "";
      const rowStatusText = statusCell
        ? statusCell.innerText.trim().toLowerCase()
        : "";

      // --- THE LOGIC GATES ---

      // Gate A: Does it belong in the current Tab? (CRITICAL FIX)
      const matchesTab = rowAvail === activeTab;

      // Gate B: Does it match the Search bar?
      const matchesSearch = supplyName.includes(searchTerm);

      // Gate C: Does it match the Status dropdown?
      // (We usually skip this for Archived items since they are all 'Out of Stock')
      const filterStatusText = selectedStockValue.replace(/_/g, " ");
      const matchesStock =
        activeTab === "Archived" ||
        selectedStockValue === "all" ||
        rowStatusText === filterStatusText;

      // --- THE FINAL DECISION ---
      if (matchesTab && matchesSearch && matchesStock) {
        row.style.setProperty("display", "flex", "important");
      } else {
        row.style.setProperty("display", "none", "important");
      }
    });

    // Always re-select the top item of the NEW filtered list
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
      const newValue = this.getAttribute("data-value"); // "Current" or "Archived"

      // Update UI of buttons
      switchBtns.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");

      // Update the global variable
      currentAvailFilter = newValue;

      // Run the filter
      filterTable();

      // Toggle the visibility of the status dropdown (optional)
      if (statusFilter) {
        statusFilter.style.display = newValue === "Archived" ? "none" : "block";
      }
    });
  });

  // --- 4. MODAL LOGIC ---
  const modal = document.getElementById("addSupplyModal");
  const openBtn = document.getElementById("openModalBtn");
  const archiveConfirmModal = document.getElementById("archiveConfirmModal");
  const archiveTrigger = document.getElementById("archiveTrigger");
  const finalArchiveBtn = document.getElementById("finalArchiveBtn");
  const restoreConfirmModal = document.getElementById("restoreConfirmModal");
  const restoreTrigger = document.getElementById("restoreTrigger");

  if (openBtn) {
    openBtn.onclick = () => {
      forceCloseSupplyEditMode();
      modal.style.setProperty("display", "flex", "important");
    };
  }

  if (archiveTrigger) {
    archiveTrigger.onclick = function () {
      forceCloseSupplyEditMode();
      // FIXED: Re-mapped to show the stored snapName
      const confirmNameDisp = document.getElementById("confirm_supply_name");
      if (confirmNameDisp) confirmNameDisp.innerText = snapName;
      archiveConfirmModal.style.setProperty("display", "flex", "important");
    };
  }

  if (finalArchiveBtn) {
    finalArchiveBtn.onclick = () => {
      const hiddenBtn = document.getElementById("hiddenArchiveSubmit");
      if (hiddenBtn) hiddenBtn.click();
    };
  }

  if (restoreTrigger) {
    restoreTrigger.onclick = function () {
      forceCloseSupplyEditMode();
      const restoreNameDisp = document.getElementById(
        "restore_confirm_supply_name",
      );
      if (restoreNameDisp) restoreNameDisp.innerText = snapName;
      restoreConfirmModal.style.setProperty("display", "flex", "important");
    };
  }

  document
    .querySelectorAll(
      ".close-modal, .btn-modal-cancel, #cancelArchiveConfirm, #cancelRestoreConfirm",
    )
    .forEach((btn) => {
      btn.onclick = function () {
        modal.style.setProperty("display", "none", "important");
        archiveConfirmModal.style.setProperty("display", "none", "important");
        restoreConfirmModal.style.setProperty("display", "none", "important");
      };
    });

  window.addEventListener("click", function (event) {
    if (event.target === modal)
      modal.style.setProperty("display", "none", "important");
    if (event.target === archiveConfirmModal)
      archiveConfirmModal.style.setProperty("display", "none", "important");
    if (event.target === restoreConfirmModal)
      restoreConfirmModal.style.setProperty("display", "none", "important");
  });

  // --- 5. AJAX ROW SELECTION ---

  /**
   * Handles the UI highlight and data fetching for a selected row
   * @param {HTMLElement} row - The .supply-row element selected
   */
  function handleRowSelection(row) {
    if (!row) return;

    if (isEditModeActive) {
      showNotification(
        "Action Blocked",
        "Please save or cancel edits.",
        "error",
      );
      return;
    }

    const id = row.getAttribute("data-id");

    // 1. UI Highlight
    document
      .querySelectorAll(".supply-row")
      .forEach((r) => r.classList.remove("active-row"));
    row.classList.add("active-row");

    // --- THE MOBILE FIX ---
    // If screen is mobile, flip the view to the right panel
    if (window.innerWidth <= 900) {
      const layout = document.querySelector(".supply-layout");
      if (layout) {
        layout.classList.add("mobile-show-details");
      }
    }

    // 2. Fetch Data
    fetch(`supply_inventory.php?fetch_id=${id}`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          snapName = data.supply.supply_name.trim();
          snapStatus = data.supply.supply_status.trim();
          updateRightPanel(data.supply, data.history);
        }
      })
      .catch((err) => console.error("Fetch error:", err));
  }

  function updateRightPanel(supply, history) {
    // --- 1. UPDATE BASIC DETAILS & STATUS PILL ---
    const nameElement = document.getElementById("view_supply_name");
    if (nameElement) nameElement.innerText = supply.supply_name;

    const statusContainer = document.getElementById("view_supply_status");
    if (statusContainer) {
      // We use 'status-pill' here to get the soft glow look from the Asset Module
      const pillColor = supply.supply_status === "In Stock" ? "green" : "red";
      statusContainer.innerHTML = `<span class="status-pill ${pillColor}">${supply.supply_status}</span>`;
    }

    // --- 2. UPDATE HIDDEN INPUTS FOR FORMS ---
    const editId = document.getElementById("edit_supply_id");
    const archiveId = document.getElementById("archive_supply_id");
    const editName = document.getElementById("edit_supply_name");
    const oldStatus = document.getElementById("original_status");

    if (editId) editId.value = supply.supply_id;
    if (archiveId) archiveId.value = supply.supply_id;
    if (editName) editName.value = supply.supply_name;
    if (oldStatus) oldStatus.value = supply.supply_status;

    // --- 3. UPDATE ACTIVITY FEED (ASSET STYLE CARDS) ---
    const feed = document.getElementById("activityFeed");
    if (!feed) return;

    feed.innerHTML = ""; // Clear existing content

    if (history && history.length > 0) {
      history.forEach((log, index) => {
        // Internal card badge color logic
        let badgeColor = "gray";
        if (log.activity.includes("In Stock")) badgeColor = "green";
        if (log.activity.includes("Out of Stock")) badgeColor = "red";

        const card = document.createElement("div");
        card.className = "activity-card";
        card.style.padding = "15px 20px";
        card.style.backgroundColor = "#fff";

        // Add separator line except for the last item
        if (index < history.length - 1) {
          card.style.borderBottom = "1px solid #f0f0f0";
        }

        card.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <div style="font-size: 13px; color: #555;">
                        <strong style="color: #1b4d3e;"><i class="fas fa-user-circle"></i> ${log.user || "System"}</strong> 
                        <span style="margin-left: 10px; color: #999; font-size: 11px;"><i class="far fa-clock"></i> ${log.date}</span>
                    </div>
                    <span class="badge ${badgeColor}">${log.activity}</span>
                </div>
                <div class="activity-remark-bubble">
                    <p style="margin: 0; font-size: 13px; color: #444; line-height: 1.5; font-style: italic;">
                        "${log.remarks || "Updated without specific remarks."}"
                    </p>
                </div>
            `;
        feed.appendChild(card);
      });
    } else {
      feed.innerHTML = `
            <div style="text-align:center; color:#bbb; padding: 40px;">
                <i class="fas fa-history" style="font-size: 24px; display: block; margin-bottom: 10px; color: #eee;"></i>
                No history found for this supply item.
            </div>`;
    }
  }

  // --- 6. EDIT MODE & AUTO-SELECT LOGIC ---
  // --- 6. EDIT MODE & AUTO-SELECT LOGIC ---
  const viewArea = document.getElementById("view-mode");
  const editArea = document.getElementById("edit-mode");
  const statusBtns = document.querySelectorAll(".status-option-btn");
  const statusInput = document.getElementById("stock_status_value"); // ONLY DECLARE THIS ONCE

  // 1. When the user clicks the Green "Edit" button
  if (document.getElementById("editTrigger")) {
    document.getElementById("editTrigger").onclick = () => {
      isEditModeActive = true;
      document.getElementById("update_remarks").value = "";

      viewArea.style.display = "none";
      editArea.style.display = "block";

      const currentStatus = document
        .getElementById("original_status")
        .value.trim();

      statusBtns.forEach((b) => b.classList.remove("active"));

      const targetBtn = document.querySelector(
        `.status-option-btn[data-value="${currentStatus}"]`,
      );
      if (targetBtn) {
        targetBtn.classList.add("active");
        statusInput.value = currentStatus; // Sets initial value
      } else {
        statusInput.value = ""; // Failsafe if currently blank
      }
    };
  }

  // 2. When the user clicks a Status Toggle Button inside Edit Mode
  statusBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      // Light up the correct button visually
      statusBtns.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");

      // CRITICAL: Actually inject the value into the hidden input for PHP!
      statusInput.value = this.getAttribute("data-value");
    });
  });

  // 2. When the user clicks a Status Toggle Button inside Edit Mode
  statusBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      // Remove active from all
      statusBtns.forEach((b) => b.classList.remove("active"));
      // Add active to the clicked one
      this.classList.add("active");
      // Update hidden input for PHP
      statusInput.value = this.getAttribute("data-value");
    });
  });

  // 3. When the user clicks Cancel
  if (document.getElementById("cancelEdit")) {
    document.getElementById("cancelEdit").onclick = () => {
      isEditModeActive = false; //
      editArea.style.display = "none";
      viewArea.style.display = "block";
    };
  }

  // 4. Form Submission & Validation
  if (document.getElementById("updateForm")) {
    document.getElementById("updateForm").onsubmit = function (e) {
      const currentStatus = document.getElementById("stock_status_value").value;
      const remarks = document.getElementById("update_remarks").value.trim();

      // If status changed, a remark is strictly required
      if (currentStatus !== snapStatus && remarks === "") {
        e.preventDefault();
        showNotification(
          "Remarks Required",
          "Status changes must include a descriptive remark.",
          "error",
        );

        // Highlight the textarea to show them where they messed up
        document.getElementById("update_remarks").style.borderColor = "#f44336";
        return false;
      }
      return true;
    };
  }
  // --- 7. POLISHED INITIALIZATION ---
  const urlParams = new URLSearchParams(window.location.search);
  const targetTab = urlParams.get("tab");
  const targetId = urlParams.get("id");

  if (targetTab === "Archived") {
    applyTabUI("Archived");
  } else {
    applyTabUI("Current");
  }

  filterTable();

  if (urlParams.has("success") || targetId || targetTab) {
    const cleanUrl =
      window.location.protocol +
      "//" +
      window.location.host +
      window.location.pathname;
    window.history.replaceState({ path: cleanUrl }, "", cleanUrl);
  }
});

// 1. Function to close details and return to list
function closeMobileDetails() {
  const layout = document.querySelector(".supply-layout");
  if (layout) {
    layout.classList.remove("mobile-show-details");
  }
}

// 2. Modify your existing "Select Item" function
// Find the function where you fetch data (likely fetchSupplyDetails)
// and add this line at the very end:
function onItemSelected() {
  // ... existing select logic ...

  // ADD THIS FOR MOBILE
  if (window.innerWidth <= 900) {
    document
      .querySelector(".supply-layout")
      .classList.add("mobile-show-details");
  }
}

// 3. Prevent Auto-Selection on Mobile Entry
document.addEventListener("DOMContentLoaded", () => {
  const isMobile = window.innerWidth <= 900;

  // Find where you auto-select the first item (e.g., in switchView)
  // Wrap it in: if (!isMobile) { ... selectFirstItem() ... }
});
