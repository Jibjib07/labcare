document.addEventListener("DOMContentLoaded", function () {
  const supplyListContainer = document.getElementById("supplyListContainer");
  const tableRows = document.querySelectorAll(".supply-row");
  const isMobile = window.innerWidth <= 900;

  // --- Global State Trackers ---
  let isEditModeActive = false;
  let snapID = "";     
  let snapName = "";   
  let snapStatus = ""; 
  let snapQuantity = 0; // NEW: Track the currently loaded quantity

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

  // SUCCESS MESSAGES
  const phpSuccess = document.getElementById("php_success");
  if (phpSuccess) {
    const messages = {
      added: "New supply added to inventory.",
      updated: "Supply name has been updated.",
      archived: "Item has been moved to Archive.",
      restored: "Item has been restored to Current Inventory.",
      transaction: "Inventory transaction recorded successfully."
    };
    showNotification("Action Successful", messages[phpSuccess.value] || "Inventory updated.", "success");
  }

  // ERROR MESSAGES (NEW)
  const phpError = document.getElementById("php_error");
  if (phpError) {
      const errMessages = {
          empty_name: "Supply name cannot be empty.",
          insufficient_stock: "Not enough stock to complete the release."
      };
      showNotification("Action Blocked", errMessages[phpError.value] || "An error occurred.", "error");
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
    const transWrapper = document.getElementById("transaction-action-wrapper");

    if (currentAvailFilter === "Archived") {
      if (statusFilter) statusFilter.classList.add("hidden-filter");
      if (editWrapper) editWrapper.style.display = "none";
      if (archWrapper) archWrapper.style.display = "none";
      if (transWrapper) transWrapper.style.display = "none";
      if (restWrapper) restWrapper.style.display = "inline-block";
    } else {
      if (statusFilter) statusFilter.classList.remove("hidden-filter");
      if (editWrapper) editWrapper.style.display = "inline-block";
      if (archWrapper) archWrapper.style.display = "inline-block";
      if (transWrapper) transWrapper.style.display = "inline-block";
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

  // --- 3. MODAL LOGIC & ERROR TRAPPING ---
  const addModal = document.getElementById("addSupplyModal");
  const archiveConfirmModal = document.getElementById("archiveConfirmModal");
  const restoreConfirmModal = document.getElementById("restoreConfirmModal");
  const transactionModal = document.getElementById("transactionModal");
  const transRemarks = document.getElementById("trans_remarks");

  if (document.getElementById("openModalBtn")) {
    document.getElementById("openModalBtn").onclick = () => {
      forceCloseSupplyEditMode();
      addModal.style.setProperty("display", "flex", "important");
    };
  }

  if (document.getElementById("archiveTrigger")) {
    document.getElementById("archiveTrigger").onclick = function () {
      forceCloseSupplyEditMode();
      document.getElementById("confirm_supply_name").innerText = snapName;
      document.getElementById("archive_supply_id").value = snapID;
      archiveConfirmModal.style.setProperty("display", "flex", "important");
    };
  }

  if (document.getElementById("restoreTrigger")) {
    document.getElementById("restoreTrigger").onclick = function () {
      forceCloseSupplyEditMode();
      document.getElementById("restore_confirm_supply_name").innerText = snapName;
      document.getElementById("restore_supply_id").value = snapID;
      restoreConfirmModal.style.setProperty("display", "flex", "important");
    };
  }

  // TRANSACTION MODAL TRIGGER
  if (document.getElementById("transactionTrigger")) {
    document.getElementById("transactionTrigger").onclick = function () {
      forceCloseSupplyEditMode();
      document.getElementById("trans_modal_title").innerText = `Update ${snapName} Stock`;
      document.getElementById("trans_supply_id").value = snapID;
      document.getElementById("trans_quantity").value = "1";
      document.getElementById("trans_remarks").value = "";
      
      // Default to Release
      document.getElementById("trans_type").value = "release";
      document.querySelectorAll(".trans-tab").forEach(t => t.classList.remove("active"));
      document.querySelector('.trans-tab[data-type="release"]').classList.add("active");
      document.getElementById("trans_modal_desc").innerText = "Removing items for use in a laboratory.";
      
      // Enforce required remarks for Release
      transRemarks.required = true;
      transRemarks.placeholder = "Explain the reason...";

      transactionModal.style.setProperty("display", "flex", "important");
    };
  }

  // TRANSACTION TABS TOGGLE (REMARKS REQUIREMENT LOGIC)
  const transTabs = document.querySelectorAll(".trans-tab");
  const transTypeInput = document.getElementById("trans_type");
  const transDesc = document.getElementById("trans_modal_desc");

  transTabs.forEach(tab => {
      tab.addEventListener("click", function() {
          transTabs.forEach(t => t.classList.remove("active"));
          this.classList.add("active");
          
          const type = this.getAttribute("data-type");
          transTypeInput.value = type;
          
          if (type === "release") {
              transDesc.innerText = "Removing items for use in a laboratory.";
              transRemarks.required = true;
              transRemarks.placeholder = "Explain the reason...";
          } else {
              transDesc.innerText = "Adding items back to the inventory.";
              transRemarks.required = false; // Remove requirement for Restock
              transRemarks.placeholder = "Optional: Add a note or leave blank...";
          }
      });
  });

  // TRANSACTION FORM SUBMIT VALIDATION (QUANTITY TRAPPING)
  const transactionForm = document.getElementById("transactionForm");
  if (transactionForm) {
      transactionForm.onsubmit = function (e) {
          const type = transTypeInput.value;
          const reqQty = parseInt(document.getElementById("trans_quantity").value) || 0;

          if (type === "release") {
              if (snapQuantity <= 0) {
                  e.preventDefault();
                  showNotification("Action Blocked", "Cannot release stock. Item is currently out of stock.", "error");
                  return false;
              }
              if (reqQty > snapQuantity) {
                  e.preventDefault();
                  showNotification("Action Blocked", `Cannot release more than available stock (${snapQuantity}).`, "error");
                  return false;
              }
          }
          return true;
      };
  }

  if (document.getElementById("finalArchiveBtn")) {
    document.getElementById("finalArchiveBtn").onclick = () => {
      document.getElementById("hiddenArchiveSubmit").click();
    };
  }

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
          snapQuantity = parseInt(data.supply.supply_quantity) || 0; // Save quantity globally for error trapping
          
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
      const displayStatus = supply.supply_avail === "Archived" ? "Archived" : supply.supply_status;
      const fontColor = displayStatus === "In Stock" ? "#4caf50" : (displayStatus === "Archived" ? "#555" : "#f44336");
      statusContainer.innerHTML = `<span>${displayStatus}</span>`;
    }

    const quantityContainer = document.getElementById("view_supply_quantity");
    if (quantityContainer) {
        quantityContainer.innerText = snapQuantity;
    }

    document.getElementById("edit_supply_id").value = snapID;
    document.getElementById("archive_supply_id").value = snapID;
    document.getElementById("restore_supply_id").value = snapID;
    document.getElementById("edit_supply_name").value = snapName;
    document.getElementById("original_status").value = snapStatus;

    const isArchived = supply.supply_avail === "Archived";
    document.getElementById("transaction-action-wrapper").style.display = isArchived ? "none" : "inline-block";

    const feed = document.getElementById("activityFeed");
    if (!feed) return;
    feed.innerHTML = "";

    if (history && history.length > 0) {
      history.forEach((log, index) => {
        const card = document.createElement("div");
        card.className = "activity-card";
        if (index < history.length - 1) card.style.borderBottom = "1px solid #f0f0f0";

        let formattedDate = log.date;
        const parsedDate = new Date(log.date);
        if (!isNaN(parsedDate)) {
            formattedDate = parsedDate.toLocaleDateString('en-US', {
                month: 'long',
                day: '2-digit',
                year: 'numeric'
            });
        }

        let badgeClass = "gray"; 
        if (log.activity.includes('In Stock') || log.activity.includes('Stock Replenished')) {
            badgeClass = "green";
        } else if (log.activity.includes('Out of Stock') || log.activity.includes('Stock Released')) {
            badgeClass = "red";
        }

        let borderColor = "#e0e0e0"; 
        if (badgeClass === "green") borderColor = "#4caf50";
        if (badgeClass === "red") borderColor = "#f44336";

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
          <div class="activity-remark-bubble" style="border-left-color: ${borderColor};">
            <p style="margin: 0; font-size: 13px; color: #444; line-height: 1.5; font-style: italic;">"${log.remarks || "No specific remarks provided."}"</p>
          </div>
        `;
        feed.appendChild(card);
      });
    } else {
      feed.innerHTML = `<div style="text-align:center; color:#bbb; padding: 40px;"><i class="fas fa-history" style="font-size: 24px; display: block; margin-bottom: 10px; color: #eee;"></i>No history found.</div>`;
    }
  }

  // --- 5. SIMPLIFIED EDIT MODE LOGIC ---
  const viewArea = document.getElementById("view-mode");
  const editArea = document.getElementById("edit-mode");

  if (document.getElementById("editTrigger")) {
    document.getElementById("editTrigger").onclick = () => {
      isEditModeActive = true;
      viewArea.style.display = "none";
      editArea.style.display = "block";
    };
  }

  if (document.getElementById("cancelEdit")) {
    document.getElementById("cancelEdit").onclick = () => {
      isEditModeActive = false;
      editArea.style.display = "none";
      viewArea.style.display = "block";
    };
  }

  // --- 6. INITIALIZATION ---
  const urlParams = new URLSearchParams(window.location.search);
  const targetTab = urlParams.get("tab");
  const targetId = urlParams.get("id");

  applyTabUI(targetTab === "Archived" ? "Archived" : "Current");
  filterTable();

  if (urlParams.has("success") || urlParams.has("error") || targetId || targetTab) {
    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({ path: cleanUrl }, "", cleanUrl);
  }
});

function closeMobileDetails() {
  const layout = document.querySelector(".supply-layout");
  if (layout) layout.classList.remove("mobile-show-details");
}