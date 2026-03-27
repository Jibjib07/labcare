document.addEventListener("DOMContentLoaded", function () {
  const supplyListContainer = document.getElementById("supplyListContainer");
  const tableRows = document.querySelectorAll(".supply-row");
  const isMobile = window.innerWidth <= 900;

  // --- 1. SEARCH & FILTER LOGIC ---
  const searchInput = document.getElementById("tableSearch");
  const statusFilter = document.getElementById("statusFilter");

  function selectFirstVisibleRow() {
    if (isMobile) return;
    const firstVisible = Array.from(tableRows).find(row => row.style.display !== "none");
    if (firstVisible) handleRowSelection(firstVisible);
  }

  function filterTable() {
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
    const selectedStockValue = statusFilter ? statusFilter.value : "all";

    tableRows.forEach((row) => {
      const supplyName = row.querySelector(".item-name")?.innerText.toLowerCase() || "";
      const rowStatusText = row.querySelector(".badge")?.innerText.trim().toLowerCase() || "";

      const matchesSearch = supplyName.includes(searchTerm);
      const filterStatusText = selectedStockValue.replace(/_/g, " ");
      const matchesStock = selectedStockValue === "all" || rowStatusText === filterStatusText;

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

  // --- 2. AJAX ROW SELECTION ---
  if (supplyListContainer) {
    supplyListContainer.addEventListener("click", function (e) {
      const row = e.target.closest(".supply-row");
      if (row) handleRowSelection(row);
    });
  }

  function handleRowSelection(row) {
    if (!row) return;

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
          updateRightPanel(data.supply, data.history);
        }
      })
      .catch((err) => console.error("Fetch error:", err));
  }

  // --- 3. UI RENDERING ---
  function updateRightPanel(supply, history) {
    const nameElement = document.getElementById("view_supply_name");
    if (nameElement) nameElement.innerText = supply.supply_name;

    const feed = document.getElementById("activityFeed");
    if (!feed) return;
    feed.innerHTML = "";

    if (history && history.length > 0) {
      history.forEach((log, index) => {
        const card = document.createElement("div");
        card.className = "activity-card";
        if (index < history.length - 1) card.style.borderBottom = "1px solid #f0f0f0";

        // Format Date
        let formattedDate = log.date;
        const parsedDate = new Date(log.date);
        if (!isNaN(parsedDate)) {
          formattedDate = parsedDate.toLocaleDateString('en-US', {
            month: 'long', day: '2-digit', year: 'numeric'
          });
        }

        // Status Badge Logic
        let badgeClass = "red";
        if (log.activity.includes('In Stock') || log.activity === "Added") {
          badgeClass = "green";
        } else if (log.activity === "Archived" || log.activity === "Restored") {
          badgeClass = "gray";
        }

        card.innerHTML = `
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; padding: 10px;">
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
          <div class="activity-remark-bubble" style="padding: 0 10px 10px 10px;">
            <p style="margin: 0; font-size: 13px; color: #444; line-height: 1.5; font-style: italic;">"${log.remarks || "No specific remarks provided."}"</p>
          </div>
        `;
        feed.appendChild(card);
      });
    } else {
      feed.innerHTML = `<div style="text-align:center; color:#bbb; padding: 40px;"><i class="fas fa-history" style="font-size: 24px; display: block; margin-bottom: 10px; color: #eee;"></i>No history found.</div>`;
    }
  }

  // Initial Run
  filterTable();
});

function closeMobileDetails() {
  const layout = document.querySelector(".supply-layout");
  if (layout) layout.classList.remove("mobile-show-details");
}