document.addEventListener("DOMContentLoaded", function () {
  const supplyListContainer = document.getElementById("supplyListContainer");
  const tableRows = document.querySelectorAll(".supply-row");
  const searchInput = document.getElementById("tableSearch");
  const statusFilter = document.getElementById("statusFilter");

  // --- 1. SINGLE LISTENER FOR ROW CLICKS ---
  if (supplyListContainer) {
    supplyListContainer.addEventListener("click", function (e) {
      const row = e.target.closest(".supply-row");
      if (row) {
        handleRowSelection(row);
      }
    });
  }

  // --- 2. FILTER & AUTO-SELECT LOGIC ---
  function selectFirstVisibleRow() {
    // Stop auto-selection on mobile devices
    if (window.innerWidth <= 900) return;

    const urlParams = new URLSearchParams(window.location.search);
    const targetId = urlParams.get("id");

    let rowToSelect = null;

    if (targetId) {
      rowToSelect = Array.from(tableRows).find(
        (row) => row.getAttribute("data-id") === targetId
      );
    }

    if (!rowToSelect || rowToSelect.style.display === "none") {
      rowToSelect = Array.from(tableRows).find(
        (row) => row.style.display !== "none"
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

    tableRows.forEach((row) => {
      const nameCell = row.querySelector(".item-name");
      const statusCell = row.querySelector(".badge");
      const supplyName = nameCell ? nameCell.innerText.toLowerCase() : "";
      const rowStatusText = statusCell ? statusCell.innerText.trim().toLowerCase() : "";

      // Gate A: Search bar match
      const matchesSearch = supplyName.includes(searchTerm);

      // Gate B: Status dropdown match
      const filterStatusText = selectedStockValue.replace(/_/g, " ");
      const matchesStock = 
        selectedStockValue === "all" || 
        rowStatusText === filterStatusText;

      if (matchesSearch && matchesStock) {
        row.style.setProperty("display", "flex", "important");
      } else {
        row.style.setProperty("display", "none", "important");
      }
    });

    selectFirstVisibleRow();
  }

  // --- 3. EVENT LISTENERS FOR SEARCH/FILTER ---
  if (searchInput) searchInput.addEventListener("input", filterTable);
  if (statusFilter) statusFilter.addEventListener("change", filterTable);

  // --- 4. AJAX ROW SELECTION ---
  function handleRowSelection(row) {
    if (!row) return;

    const id = row.getAttribute("data-id");

    // UI Highlight
    document
      .querySelectorAll(".supply-row")
      .forEach((r) => r.classList.remove("active-row"));
    row.classList.add("active-row");

    // MOBILE PANEL SWITCH
    if (window.innerWidth <= 900) {
      const layout = document.querySelector(".supply-layout");
      if (layout) layout.classList.add("mobile-show-details");
    }

    // Fetch Data
    fetch(`supply_inventory.php?fetch_id=${id}`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          updateRightPanel(data.supply, data.history);
        }
      })
      .catch((err) => console.error("Fetch error:", err));
  }

  function updateRightPanel(supply, history) {
    // 1. Update Basic Details
    const nameElement = document.getElementById("view_supply_name");
    if (nameElement) nameElement.innerText = supply.supply_name;

    const statusContainer = document.getElementById("view_supply_status");
    if (statusContainer) {
      const pillColor = supply.supply_status === "In Stock" ? "green" : "red";
      statusContainer.innerHTML = `<span class="status-pill ${pillColor}">${supply.supply_status}</span>`;
    }

    // 2. Update Activity Feed
    const feed = document.getElementById("activityFeed");
    if (!feed) return;

    feed.innerHTML = ""; 

    if (history && history.length > 0) {
      history.forEach((log, index) => {
        let badgeColor = "gray";
        if (log.activity.includes("In Stock")) badgeColor = "green";
        if (log.activity.includes("Out of Stock")) badgeColor = "red";

        const card = document.createElement("div");
        card.className = "activity-card";
        card.style.padding = "15px 20px";
        card.style.backgroundColor = "#fff";

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

  // --- 5. INITIALIZATION ---
  filterTable();

  // Clean URL parameters after loading if needed
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has("id")) {
    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({ path: cleanUrl }, "", cleanUrl);
  }
});

// Mobile helper: Back to list
function closeMobileDetails() {
  const layout = document.querySelector(".supply-layout");
  if (layout) {
    layout.classList.remove("mobile-show-details");
  }
}