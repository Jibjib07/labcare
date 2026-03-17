document.addEventListener("DOMContentLoaded", function () {
  const supplyTableBody = document.querySelector(".supply-table tbody");
  const tableRows = document.querySelectorAll(".supply-table tbody tr");
  const toastContainer = document.getElementById("toast-container");

  let snapName = "";
  let snapStatus = "";

  // --- 1. TOAST SYSTEM ---
  function showToast(title, message, type = 'success') {
    const toast = document.createElement("div");
    toast.className = `toast toast-${type === 'success' ? 'success' : 'danger'}`;
    const icon = type === 'success' ? 'fa-check' : 'fa-box-archive';

    toast.innerHTML = `
      <div class="toast-icon"><i class="fas ${icon}"></i></div>
      <div class="toast-content">
        <div class="toast-title">${title}</div>
        <div class="toast-sub">${message}</div>
      </div>
    `;

    toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.style.animation = "fadeOut 0.5s forwards";
      setTimeout(() => toast.remove(), 500);
    }, 4000);
  }

  // PHP Redirect Notifications
  const phpSuccess = document.getElementById("php_success");
  if (phpSuccess) {
      let msg = "Inventory updated.";
      if(phpSuccess.value === 'added') msg = 'New supply added to inventory.';
      if(phpSuccess.value === 'updated') msg = 'Supply details have been updated.';
      if(phpSuccess.value === 'archived') msg = 'Item has been moved to Archive.';
      if(phpSuccess.value === 'restored') msg = 'Item has been restored to Current Inventory.';
      showToast("Action Successful", msg, "success");
  }

  // --- 2. MULTI-LAYER FILTER & AUTO-SELECT LOGIC ---
  const searchInput = document.getElementById("tableSearch");
  const statusFilter = document.getElementById("statusFilter");
  const switchBtns = document.querySelectorAll(".switch-btn");
  let currentAvailFilter = "Current"; 

  function selectFirstVisibleRow() {
    const urlParams = new URLSearchParams(window.location.search);
    const targetId = urlParams.get('id');

    let rowToSelect = null;
    if (targetId) {
        rowToSelect = Array.from(tableRows).find(row => row.getAttribute("data-id") === targetId);
    }

    if (!rowToSelect || rowToSelect.style.display === "none") {
        rowToSelect = Array.from(tableRows).find(row => row.style.display !== "none");
    }
    
    if (rowToSelect) {
      rowToSelect.click(); 
      rowToSelect.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
      const nameView = document.getElementById("view_supply_name");
      if (nameView) nameView.innerText = "No items found";
      const statusView = document.getElementById("view_supply_status");
      if (statusView) statusView.innerHTML = "-";
    }
  }

  function filterTable() {
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
    const selectedStockValue = statusFilter ? statusFilter.value : "all";

    tableRows.forEach((row) => {
      // FIXED: Adjusted to target the specific name cell class due to new PHP structure
      const nameCell = row.querySelector(".supply-name-cell");
      const statusCell = row.querySelector(".supply-status-cell");
      
      const supplyName = nameCell ? nameCell.innerText.toLowerCase() : "";
      const rowStatusText = statusCell ? statusCell.innerText.trim().toLowerCase() : "";
      const rowAvailValue = row.getAttribute("data-avail");

      const matchesSearch = supplyName.includes(searchTerm);
      const filterStatusText = selectedStockValue.replace(/_/g, " ");
      const matchesStock = (currentAvailFilter === "Archived") || (selectedStockValue === "all" || rowStatusText === filterStatusText);
      const matchesAvail = rowAvailValue === currentAvailFilter;

      row.style.display = matchesSearch && matchesStock && matchesAvail ? "" : "none";
    });

    selectFirstVisibleRow();
  }

  function applyTabUI(value) {
    switchBtns.forEach(b => {
        b.classList.remove("active");
        if(b.getAttribute("data-value") === value) b.classList.add("active");
    });
    
    currentAvailFilter = value;

    const editWrapper = document.getElementById("edit-action-wrapper");
    const archWrapper = document.getElementById("archive-action-wrapper");
    const restWrapper = document.getElementById("restore-action-wrapper");

    if (currentAvailFilter === "Archived") {
      if(statusFilter) statusFilter.classList.add("hidden-filter");
      if(editWrapper) editWrapper.style.display = "none";
      if(archWrapper) archWrapper.style.display = "none";
      if(restWrapper) restWrapper.style.display = "inline-block";
    } else {
      if(statusFilter) statusFilter.classList.remove("hidden-filter");
      if(editWrapper) editWrapper.style.display = "inline-block";
      if(archWrapper) archWrapper.style.display = "inline-block";
      if(restWrapper) restWrapper.style.display = "none";
    }
  }

  switchBtns.forEach(btn => {
    btn.addEventListener("click", function() {
        applyTabUI(this.getAttribute("data-value"));
        filterTable();
    });
  });

  if (searchInput) searchInput.addEventListener("input", filterTable);
  if (statusFilter) statusFilter.addEventListener("change", filterTable);

  // --- 3. STATUS BUTTON TOGGLE LOGIC ---
  const statusOptionBtns = document.querySelectorAll(".status-option-btn");
  const hiddenStatusInput = document.getElementById("stock_status_value");

  statusOptionBtns.forEach(btn => {
    btn.addEventListener("click", function() {
      statusOptionBtns.forEach(b => b.classList.remove("active"));
      this.classList.add("active");
      if(hiddenStatusInput) hiddenStatusInput.value = this.getAttribute("data-value");
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

  if (openBtn) openBtn.onclick = () => modal.style.setProperty("display", "flex", "important");

  if (archiveTrigger) {
    archiveTrigger.onclick = function() {
        // FIXED: Re-mapped to show the stored snapName
        const confirmNameDisp = document.getElementById("confirm_supply_name");
        if(confirmNameDisp) confirmNameDisp.innerText = snapName;
        archiveConfirmModal.style.setProperty("display", "flex", "important");
    };
  }

  if (finalArchiveBtn) {
    finalArchiveBtn.onclick = () => {
        const hiddenBtn = document.getElementById("hiddenArchiveSubmit");
        if(hiddenBtn) hiddenBtn.click();
    };
  }

  if (restoreTrigger) {
    restoreTrigger.onclick = function() {
        const restoreNameDisp = document.getElementById("restore_confirm_supply_name");
        if(restoreNameDisp) restoreNameDisp.innerText = snapName;
        restoreConfirmModal.style.setProperty("display", "flex", "important");
    };
  }

  document.querySelectorAll(".close-modal, .btn-modal-cancel, #cancelArchiveConfirm, #cancelRestoreConfirm").forEach((btn) => {
    btn.onclick = function() {
      modal.style.setProperty("display", "none", "important");
      archiveConfirmModal.style.setProperty("display", "none", "important");
      restoreConfirmModal.style.setProperty("display", "none", "important");
    };
  });

  window.addEventListener("click", function(event) {
    if (event.target === modal) modal.style.setProperty("display", "none", "important");
    if (event.target === archiveConfirmModal) archiveConfirmModal.style.setProperty("display", "none", "important");
    if (event.target === restoreConfirmModal) restoreConfirmModal.style.setProperty("display", "none", "important");
  });

  // --- 5. AJAX ROW CLICK ---
  if (supplyTableBody) {
    supplyTableBody.addEventListener("click", function (e) {
      const row = e.target.closest(".supply-row");
      if (row) {
        const id = row.getAttribute("data-id");
        document.querySelectorAll(".supply-row").forEach((r) => r.classList.remove("active-row"));
        row.classList.add("active-row");

        fetch(`supply_inventory.php?fetch_id=${id}`)
          .then((res) => res.json())
          .then((data) => {
            if (data.success) {
              snapName = data.supply.supply_name.trim(); // Stores name for modals
              snapStatus = data.supply.supply_status.trim();
              updateRightPanel(data.supply, data.history);
            }
          });
      }
    });
  }

  function updateRightPanel(supply, history) {
    document.getElementById("view_supply_name").innerText = supply.supply_name;
    const vStat = document.getElementById("view_supply_status");
    const bCls = (supply.supply_status === 'In Stock') ? 'badge green' : 'badge red';
    vStat.innerHTML = `<span class="${bCls}">${supply.supply_status}</span>`;

    document.getElementById("edit_supply_id").value = supply.supply_id;
    document.getElementById("archive_supply_id").value = supply.supply_id;
    document.getElementById("restore_supply_id").value = supply.supply_id;

    document.getElementById("edit_supply_name").value = supply.supply_name;
    document.getElementById("original_status").value = supply.supply_status;
    document.getElementById("stock_status_value").value = supply.supply_status;

    statusOptionBtns.forEach(btn => {
      btn.classList.remove("active");
      if(btn.getAttribute("data-value") === supply.supply_status) btn.classList.add("active");
    });

    const activityBody = document.querySelector(".activity-table tbody");
    if (activityBody) {
      activityBody.innerHTML = ""; 
      if (history && history.length > 0) {
        history.forEach((log) => {
          activityBody.innerHTML += `<tr><td>${log.date}</td><td><strong>${log.activity}</strong></td><td>${log.user}</td><td>${log.remarks}</td></tr>`;
        });
      } else {
        activityBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px;">No recent activity found.</td></tr>';
      }
    }
  }

  // --- 6. EDIT MODE ---
  const viewArea = document.getElementById("view-mode");
  const editArea = document.getElementById("edit-mode");

  if(document.getElementById("editTrigger")){
      document.getElementById("editTrigger").onclick = () => {
        document.getElementById("update_remarks").value = "";
        viewArea.style.display = "none";
        editArea.style.display = "block";
      };
  }

  if(document.getElementById("cancelEdit")){
      document.getElementById("cancelEdit").onclick = () => {
        editArea.style.display = "none";
        viewArea.style.display = "block";
      };
  }

  if(document.getElementById("updateForm")){
      document.getElementById("updateForm").onsubmit = function (e) {
        const currentStatus = document.getElementById("stock_status_value").value;
        const remarks = document.getElementById("update_remarks").value.trim();
        if (currentStatus !== snapStatus && remarks === "") {
            e.preventDefault();
            showToast("Remarks Required", "Status changes must include a descriptive remark.", "danger");
            return false;
        }
        return true; 
      };
  }

  // --- 7. POLISHED INITIALIZATION ---
  const urlParams = new URLSearchParams(window.location.search);
  const targetTab = urlParams.get('tab'); 
  const targetId = urlParams.get('id');

  if (targetTab === "Archived") {
    applyTabUI("Archived");
  } else {
    applyTabUI("Current");
  }

  filterTable();

  if (urlParams.has('success') || targetId || targetTab) {
    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({path: cleanUrl}, '', cleanUrl);
  }
});