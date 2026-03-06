document.addEventListener("DOMContentLoaded", function () {
  const tableRows = document.querySelectorAll(".supply-table tbody tr");

  // --- 1. FILTER LOGIC ---
  const searchInput = document.getElementById("tableSearch");
  const categoryFilter = document.getElementById("categoryFilter");
  const filterRadios = document.querySelectorAll(
    '.radio-group input[name="stock_status"]',
  );

  function filterTable() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedCategory = categoryFilter.value;

    const selectedStockRadio = document.querySelector(
      '.radio-group input[name="stock_status"]:checked',
    );
    const selectedStockValue = selectedStockRadio
      ? selectedStockRadio.value
      : "all";

    tableRows.forEach((row) => {
      // Get row data
      const supplyName = row.cells[0].innerText.toLowerCase();
      const rowCategory = row.getAttribute("data-category");
      const rowStatusText = row.cells[2].innerText.trim().toLowerCase();

      // Filter logic
      const matchesSearch = supplyName.includes(searchTerm);
      const matchesCategory =
        selectedCategory === "all" || rowCategory === selectedCategory;

      const filterStatusText = selectedStockValue.replace(/_/g, " ");

      const matchesStock =
        selectedStockValue === "all" || rowStatusText === filterStatusText;

      // Apply visibility
      row.style.display =
        matchesSearch && matchesCategory && matchesStock ? "" : "none";
    });
  }

  // Filter Listeners
  if (searchInput) searchInput.addEventListener("input", filterTable);
  if (categoryFilter) categoryFilter.addEventListener("change", filterTable);
  filterRadios.forEach((radio) =>
    radio.addEventListener("change", filterTable),
  );

  // Initialize filter on load
  filterTable();

  // --- 2. MODAL LOGIC ---
  const modal = document.getElementById("addSupplyModal");
  const openBtn = document.getElementById("openModalBtn");
  const closeButtons = document.querySelectorAll(
    ".close-modal, .btn-modal-cancel",
  );

  if (openBtn) openBtn.onclick = () => (modal.style.display = "flex");
  closeButtons.forEach((btn) => {
    btn.onclick = () => (modal.style.display = "none");
  });

  // --- 3. ROW CLICK & VIEW/EDIT SYNC ---
  tableRows.forEach((row) => {
    row.addEventListener("click", function () {
      // Highlight
      tableRows.forEach((r) => r.classList.remove("active-row"));
      this.classList.add("active-row");

      // Extract
      const id = this.getAttribute("data-id");
      const name = this.cells[0].innerText;
      const category = this.getAttribute("data-category");
      const status = this.cells[2].innerText.trim();

      // Update View Mode
      const viewName = document.getElementById("view_supply_name");
      const viewStatus = document.getElementById("view_supply_status");
      const viewCategory = document.getElementById("view_supply_category");

      if (viewName) viewName.innerText = name;
      if (viewStatus) viewStatus.innerText = status;
      if (viewCategory) viewCategory.innerText = category;

      // Update Edit Mode Form
      const editId = document.querySelector('input[name="supply_id"]');
      const editName = document.getElementById("edit_supply_name");
      const editCategory = document.getElementById("edit_supply_category");

      if (editId) editId.value = id;
      if (editName) editName.value = name;
      if (editCategory) editCategory.value = category;

      // Sync Edit Radios (Status)
      const editInStock = document.querySelector(
        '#edit-mode input[value="In Stock"]',
      );
      const editOutStock = document.querySelector(
        '#edit-mode input[value="Out of Stock"]',
      );
      if (status === "In Stock") {
        if (editInStock) editInStock.checked = true;
      } else {
        if (editOutStock) editOutStock.checked = true;
      }
    });
  });

  // --- 4. EDIT TOGGLE ---
  const viewArea = document.getElementById("view-mode");
  const editArea = document.getElementById("edit-mode");
  const editBtn = document.getElementById("editTrigger");
  const cancelBtn = document.getElementById("cancelEdit");

  if (editBtn) {
    editBtn.onclick = () => {
      viewArea.style.display = "none";
      editArea.style.display = "block";
    };
  }
  if (cancelBtn) {
    cancelBtn.onclick = () => {
      editArea.style.display = "none";
      viewArea.style.display = "block";
    };
  }
});
