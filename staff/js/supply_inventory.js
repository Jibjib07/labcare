document.addEventListener("DOMContentLoaded", function () {
  const supplyListContainer = document.getElementById("supplyListContainer");
  const tableRows = document.querySelectorAll(".supply-row");
  const isMobile = window.innerWidth <= 900;

  // --- Global State Trackers ---
  let snapID = "";
  let snapName = "";
  let snapStatus = "";
  let snapQuantity = 0;

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

    setTimeout(() => {
      toast.classList.add("fade-out");
      toast.addEventListener(
        "animationend",
        (e) => {
          if (e.animationName === "fadeOut") toast.remove();
        },
        { once: true },
      );

      setTimeout(() => {
        if (toast.parentNode) toast.remove();
      }, 600);
    }, 3500);
  }

  // SUCCESS MESSAGES
  const phpSuccess = document.getElementById("php_success");
  if (phpSuccess) {
    const messages = {
      transaction: "Inventory transaction recorded successfully.",
    };
    showNotification(
      "Action Successful",
      messages[phpSuccess.value] || "Inventory updated.",
      "success",
    );
  }

  // ERROR MESSAGES
  const phpError = document.getElementById("php_error");
  if (phpError) {
    const errMessages = {
      insufficient_stock: "Not enough stock to complete the release.",
    };
    showNotification(
      "Action Blocked",
      errMessages[phpError.value] || "An error occurred.",
      "error",
    );
  }

  // --- 2. SEARCH & FILTER LOGIC ---
  const searchInput = document.getElementById("tableSearch");
  const statusFilter = document.getElementById("statusFilter");

  function selectFirstVisibleRow() {
    if (isMobile) return;

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
    }
  }

  function filterTable() {
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
    const selectedStockValue = statusFilter ? statusFilter.value : "all";

    tableRows.forEach((row) => {
      const supplyName =
        row.querySelector(".item-name")?.innerText.toLowerCase() || "";
      const rowStatusText =
        row.querySelector(".badge")?.innerText.trim().toLowerCase() || "";

      const matchesSearch = supplyName.includes(searchTerm);
      const filterStatusText = selectedStockValue.replace(/_/g, " ");
      const matchesStock =
        selectedStockValue === "all" || rowStatusText === filterStatusText;

      if (matchesSearch && matchesStock) {
        row.style.setProperty("display", "flex", "important");
      } else {
        row.style.setProperty("display", "none", "important");
      }
    });

    selectFirstVisibleRow();
  }

  if (searchInput) searchInput.addEventListener("input", filterTable);
  if (statusFilter) statusFilter.addEventListener("change", filterTable);

  // --- 3. MODAL LOGIC & ERROR TRAPPING ---
  const transactionModal = document.getElementById("transactionModal");
  const transRemarks = document.getElementById("trans_remarks");

  // TRANSACTION MODAL TRIGGER
  if (document.getElementById("transactionTrigger")) {
    document.getElementById("transactionTrigger").onclick = function () {
      if (!snapID) {
        showNotification(
          "Action Blocked",
          "Please select an item from the list first.",
          "error",
        );
        return;
      }

      document.getElementById("trans_modal_title").innerText =
        `Update ${snapName} Stock`;
      document.getElementById("trans_supply_id").value = snapID;
      document.getElementById("trans_quantity").value = "1";
      document.getElementById("trans_remarks").value = "";

      // Default to Release
      document.getElementById("trans_type").value = "release";
      document
        .querySelectorAll(".trans-tab")
        .forEach((t) => t.classList.remove("active"));

      const releaseTab = document.querySelector(
        '.trans-tab[data-type="release"]',
      );
      if (releaseTab) releaseTab.classList.add("active");

      document.getElementById("trans_modal_desc").innerText =
        "Removing items for use in a laboratory.";

      // Enforce required remarks for Release
      if (transRemarks) {
        transRemarks.required = true;
        transRemarks.placeholder = "State the reason for releasing this stock.";
      }

      transactionModal.style.setProperty("display", "flex", "important");
    };
  }

  // TRANSACTION FORM SUBMIT VALIDATION (QUANTITY TRAPPING)
  const transactionForm = document.getElementById("transactionForm");
  if (transactionForm) {
    transactionForm.onsubmit = function (e) {
      const transTypeInput = document.getElementById("trans_type");
      const type = transTypeInput ? transTypeInput.value : "release";
      const reqQty =
        parseInt(document.getElementById("trans_quantity").value) || 0;

      if (type === "release") {
        if (snapQuantity <= 0) {
          e.preventDefault();
          showNotification(
            "Action Blocked",
            "Cannot release stock. Item is currently out of stock.",
            "error",
          );
          return false;
        }
        if (reqQty > snapQuantity) {
          e.preventDefault();
          showNotification(
            "Action Blocked",
            `Cannot release more than available stock (${snapQuantity}).`,
            "error",
          );
          return false;
        }
      }
      return true;
    };
  }

  // Close modals
  document
    .querySelectorAll(".close-modal, .btn-modal-cancel")
    .forEach((btn) => {
      btn.onclick = function () {
        document
          .querySelectorAll(".modal-overlay")
          .forEach((m) => m.style.setProperty("display", "none", "important"));
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

    const id = row.getAttribute("data-id");
    document
      .querySelectorAll(".supply-row")
      .forEach((r) => r.classList.remove("active-row"));
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
          snapQuantity = parseInt(data.supply.supply_quantity) || 0;

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
      const displayStatus = supply.supply_status;
      const fontColor = displayStatus === "In Stock" ? "#4caf50" : "#f44336";
      statusContainer.innerHTML = `<span style="color: ${fontColor}; font-weight: bold;">${displayStatus}</span>`;
    }

    const quantityContainer = document.getElementById("view_supply_quantity");
    if (quantityContainer) {
      quantityContainer.innerText = snapQuantity;
    }

    const feed = document.getElementById("activityFeed");
    if (!feed) return;
    feed.innerHTML = "";

    if (history && history.length > 0) {
      history.forEach((log, index) => {
        const card = document.createElement("div");
        card.className = "activity-card";
        if (index < history.length - 1)
          card.style.borderBottom = "1px solid #f0f0f0";

        let formattedDate = log.date;
        const parsedDate = new Date(log.date);
        if (!isNaN(parsedDate)) {
          formattedDate = parsedDate.toLocaleDateString("en-US", {
            month: "long",
            day: "2-digit",
            year: "numeric",
          });
        }

        let badgeClass = "gray";
        if (
          log.activity.includes("In Stock") ||
          log.activity.includes("Stock Replenished") ||
          log.activity.includes("Added")
        ) {
          badgeClass = "green";
        } else if (
          log.activity.includes("Out of Stock") ||
          log.activity.includes("Stock Released")
        ) {
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

  // --- 5. INITIALIZATION ---
  filterTable();

  const urlParams = new URLSearchParams(window.location.search);
  if (
    urlParams.has("success") ||
    urlParams.has("error") ||
    urlParams.has("id")
  ) {
    const cleanUrl =
      window.location.protocol +
      "//" +
      window.location.host +
      window.location.pathname;
    window.history.replaceState({ path: cleanUrl }, "", cleanUrl);
  }
});

function closeMobileDetails() {
  const layout = document.querySelector(".supply-layout");
  if (layout) layout.classList.remove("mobile-show-details");
}
