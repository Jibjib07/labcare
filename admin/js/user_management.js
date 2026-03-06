document.addEventListener("DOMContentLoaded", () => {
  // 0. Setup Table Layout automatically
  const pagination = document.querySelector(".pagination");
  if (pagination) pagination.style.display = "none";
  const tableContainer = document.querySelector(".table-container");
  if (tableContainer) {
    tableContainer.style.maxHeight = "250px";
    tableContainer.style.overflowY = "auto";
  }

  // 1. Map Elements
  const tbody = document.querySelector(".user-table tbody");
  const btnAdd = document.querySelector(".btn-green-add");

  const infoValues = document.querySelectorAll(".user-info-panel .info-value");
  if (infoValues.length < 4) return;
  const infoName = infoValues[0];
  const infoStatus = infoValues[1];
  const infoEmail = infoValues[2];
  const infoRole = infoValues[3];

  const btnAction1 = document.querySelector(
    ".user-info-panel .action-buttons button:nth-child(1)",
  ); // Edit/Save
  const btnAction2 = document.querySelector(
    ".user-info-panel .action-buttons button:nth-child(2)",
  ); // Deactivate/Cancel

  let currentRow = null;
  let isEditing = false;
  let tempStatus = "";

  // 2. Target the Modal from PHP
  const modal = document.getElementById("deactivate-modal");

  // 3. ROW CLICK (View Details)
  if (tbody) {
    tbody.querySelectorAll("tr").forEach((row) => {
      if (row.classList.contains("empty-row")) return;

      if (row.cells.length < 4) {
        const td = document.createElement("td");
        td.style.display = "none";
        td.innerText =
          row.cells[0].innerText.replace(/[^a-zA-Z]/g, "").toLowerCase() +
          "@gmail.com";
        row.appendChild(td);
      }
    });

    tbody.addEventListener("click", (e) => {
      const tr = e.target.closest("tr");
      if (!tr) return;
      if (tr.classList.contains("empty-row")) return;

      if (currentRow) currentRow.classList.remove("active-row");
      tr.classList.add("active-row");
      currentRow = tr;

      if (isEditing) toggleEditMode();

      infoName.innerText = tr.cells[0].innerText;
      infoRole.innerText = tr.cells[1].innerText;
      infoStatus.innerText = tr.cells[2].innerText.trim();
      infoEmail.innerText = tr.cells[3].innerText;

      updateStatusVisuals();
    });
  }

  function updateStatusVisuals() {
    infoStatus.style.padding = "0";
    infoStatus.style.background = "transparent";

    if (!infoStatus.innerText.trim()) return;

    if (infoStatus.innerText.trim() === "Active") {
      infoStatus.innerHTML = `<span style="background-color:#4CAF50; color:white; padding:8px 25px; border-radius:6px; font-weight:600; display:inline-block;">Active</span>`;
    } else {
      infoStatus.innerHTML = `<span style="background-color:#9E9E9E; color:white; padding:8px 25px; border-radius:6px; font-weight:600; display:inline-block;">Deactivated</span>`;
    }
  }

  // 4. EDIT LOGIC
  function toggleEditMode() {
    isEditing = !isEditing;

    if (isEditing) {
      [infoName, infoEmail, infoRole, infoStatus].forEach((el) => {
        el.style.background = "transparent";
        el.style.padding = "0";
      });

      infoName.innerHTML = `<input type="text" id="edit-name" value="${infoName.dataset.val}" class="edit-gray-input">`;
      infoEmail.innerHTML = `<input type="email" id="edit-email" value="${infoEmail.dataset.val}" class="edit-gray-input">`;
      infoRole.innerHTML = `
                <select id="edit-role" class="edit-gray-input edit-gray-select">
                    <option value="Staff" ${infoRole.dataset.val === "Staff" ? "selected" : ""}>Staff</option>
                    <option value="Admin" ${infoRole.dataset.val === "Admin" ? "selected" : ""}>Admin</option>
                </select>
            `;

      const isAct = tempStatus === "Active";
      infoStatus.innerHTML = `
                <div class="status-toggle-wrapper">
                    <button type="button" id="toggle-act" class="status-btn ${isAct ? "active-green" : ""}">Activate</button>
                    <button type="button" id="toggle-deact" class="status-btn ${!isAct ? "active-gray" : ""}">Deactivate</button>
                </div>
            `;

      document.getElementById("toggle-act").addEventListener("click", (e) => {
        tempStatus = "Active";
        e.target.classList.add("active-green");
        document.getElementById("toggle-deact").classList.remove("active-gray");
      });
      document.getElementById("toggle-deact").addEventListener("click", (e) => {
        tempStatus = "Deactivated";
        e.target.classList.add("active-gray");
        document.getElementById("toggle-act").classList.remove("active-green");
      });

      btnAction1.className = "btn-save-new";
      btnAction1.innerHTML = `<i class="fas fa-check-circle"></i> Save`;
      btnAction2.className = "btn-cancel-new";
      btnAction2.innerHTML = `Cancel`;
    } else {
      [infoName, infoEmail, infoRole].forEach((el) => {
        el.style.background = "";
        el.style.padding = "";
      });

      infoName.innerText = infoName.dataset.val;
      infoEmail.innerText = infoEmail.dataset.val;
      infoRole.innerText = infoRole.dataset.val;
      infoStatus.innerText = infoStatus.dataset.val;

      updateStatusVisuals();

      btnAction1.className = "btn-edit";
      btnAction1.innerHTML = `<i class="fas fa-pen"></i> Edit`;
      btnAction2.className = "btn-deactivate";
      btnAction2.innerHTML = `<i class="fas fa-user-slash"></i> Deactivate`;
    }
  }

  if (btnAction1) {
    btnAction1.addEventListener("click", () => {
      if (!currentRow)
        return alert("Please click a user row from the list first.");

      if (isEditing) {
        const userId = currentRow.getAttribute("data-id");
        const newName = document.getElementById("edit-name").value;
        const newEmail = document.getElementById("edit-email").value;
        const newRole = document.getElementById("edit-role").value;

        // ==========================================
        // TODO: ADD UPDATE FETCH API CALL HERE
        // fetch('api/update_user.php', { ... })
        // ==========================================

        currentRow.cells[0].innerText = newName;
        currentRow.cells[1].innerText = newRole;
        currentRow.cells[3].innerText = newEmail;

        const badgeClass = tempStatus === "Active" ? "active" : "deactivated";
        const badgeColor = tempStatus === "Active" ? "#4CAF50" : "#9E9E9E";
        currentRow.cells[2].innerHTML = `<span class="badge ${badgeClass}" style="background-color:${badgeColor}; padding:4px 12px; border-radius:12px; color:white; font-size:11px; font-weight:700; display:inline-block;">${tempStatus}</span>`;

        infoName.dataset.val = newName;
        infoEmail.dataset.val = newEmail;
        infoRole.dataset.val = newRole;
        infoStatus.dataset.val = tempStatus;

        toggleEditMode();
      } else {
        // Enter Edit Mode
        infoName.dataset.val = infoName.innerText;
        infoEmail.dataset.val = infoEmail.innerText;
        infoRole.dataset.val = infoRole.innerText;
        infoStatus.dataset.val = infoStatus.innerText.trim();
        tempStatus = infoStatus.dataset.val;
        toggleEditMode();
      }
    });
  }

  // 5. DEACTIVATE LOGIC
  if (btnAction2) {
    btnAction2.addEventListener("click", () => {
      if (isEditing) {
        toggleEditMode();
        return;
      }
      if (!currentRow)
        return alert("Please click a user row from the list first.");
      if (currentRow.cells[2].innerText.includes("Deactivated"))
        return alert("This user is already deactivated.");

      document.getElementById("modal-name").value = infoName.innerText;
      document.getElementById("modal-email").value = infoEmail.innerText;
      document.getElementById("modal-role").value = infoRole.innerText;
      modal.style.display = "flex";
    });
  }

  document
    .getElementById("btn-cancel-modal")
    .addEventListener("click", () => (modal.style.display = "none"));

  document
    .getElementById("btn-confirm-deactivate")
    .addEventListener("click", () => {
      const userId = currentRow.getAttribute("data-id");

      // ==============================================
      // TODO: ADD DEACTIVATE FETCH API CALL HERE
      // fetch('api/deactivate_user.php', { ... })
      // ==============================================

      currentRow.cells[2].innerHTML = `<span class="badge deactivated" style="background-color:#9E9E9E; padding:4px 12px; border-radius:12px; color:white; font-size:11px; font-weight:700; display:inline-block;">Deactivated</span>`;
      infoStatus.innerText = "Deactivated";
      updateStatusVisuals();
      modal.style.display = "none";
    });

  // 6. ADD USER
  if (btnAdd) {
    btnAdd.addEventListener("click", (e) => {
      e.preventDefault();

      const inputs = document.querySelectorAll(".user-form .form-input");
      const name = inputs[0].value;
      const email = inputs[1].value;
      const password = inputs[2].value;
      const confirmPassword = inputs[3].value;

      const roleEl =
        document.querySelector('input[name="role"]:checked') ||
        document.querySelector('input[name="add-role"]:checked');
      const role = roleEl ? roleEl.value : "Staff";

      const statusSelect = document.querySelector(".user-form .form-select");
      const status = statusSelect ? statusSelect.value : "Active";

      if (!name || !email)
        return alert("Please enter both a Name and an Email Address.");
      if (password !== confirmPassword) return alert("Passwords do not match!");

      // =======================================
      // ADD INSERT FETCH API CALL HERE
      // fetch('api/add_user.php', { ... })
      // =======================================

      const dummyId = Math.floor(Math.random() * 1000); // Fake DB ID for testing

      const statusHTML =
        status === "Active"
          ? `<span class="badge active" style="background-color:#4CAF50; padding:4px 12px; border-radius:12px; color:white; font-size:11px; font-weight:700; display:inline-block;">Active</span>`
          : `<span class="badge deactivated" style="background-color:#9E9E9E; padding:4px 12px; border-radius:12px; color:white; font-size:11px; font-weight:700; display:inline-block;">Deactivated</span>`;

      const newRow = document.createElement("tr");
      newRow.style.cursor = "pointer";
      newRow.setAttribute("data-id", dummyId);
      newRow.innerHTML = `<td>${name}</td><td>${role}</td><td>${statusHTML}</td><td style="display:none;">${email}</td>`;

      tbody.prepend(newRow);

      const emptyRow = tbody.querySelector(".empty-row");
      if (emptyRow) emptyRow.remove();

      if (tableContainer) tableContainer.scrollTop = 0;
      document.querySelector(".user-form").reset();
    });
  }

  // 7. FILTER LOGIC
  const filterRow = document.querySelector(".search-filter-row");
  if (filterRow) {
    const oldFilterBtn = filterRow.querySelector(".filter-btn");
    if (oldFilterBtn && oldFilterBtn.tagName === "BUTTON") {
      oldFilterBtn.outerHTML = `
                <select id="role-filter" class="filter-btn" style="cursor:pointer; outline:none; font-family:inherit;">
                    <option value="All">Filter</option>
                    <option value="Staff">Staff</option>
                    <option value="Admin">Admin</option>
                </select>
            `;
    }
  }

  const searchInput = document.querySelector(".search-input");
  function applyFilters() {
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
    const roleFilter = document.getElementById("role-filter");
    const selectedRole = roleFilter ? roleFilter.value : "All";

    tbody.querySelectorAll("tr").forEach((row) => {
      const name = row.cells[0].innerText.toLowerCase();
      const role = row.cells[1].innerText;
      const email = row.cells[3] ? row.cells[3].innerText.toLowerCase() : "";

      const matchesSearch =
        name.includes(searchTerm) || email.includes(searchTerm);
      const matchesRole = selectedRole === "All" || role === selectedRole;

      row.style.display = matchesSearch && matchesRole ? "" : "none";
    });
  }

  if (searchInput) searchInput.addEventListener("input", applyFilters);
  document.addEventListener("change", (e) => {
    if (e.target.id === "role-filter") applyFilters();
  });
});
