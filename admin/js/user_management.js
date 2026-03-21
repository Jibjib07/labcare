document.addEventListener("DOMContentLoaded", () => {
  // 1. Map Elements
  const listContainer = document.getElementById("user-list-container");
  const searchInput = document.getElementById("search-input");
  const statusFilter = document.getElementById("status-filter");
  const listTitle = document.getElementById("list-status-title");

  const infoName = document.getElementById("info-name");
  const infoEmail = document.getElementById("info-email");
  const infoRole = document.getElementById("info-role");
  const infoStatus = document.getElementById("info-status");

  const btnAction1 = document.querySelector(
    "#info-action-buttons button:nth-child(1)",
  ); // Edit/Save
  const btnAction2 = document.querySelector(
    "#info-action-buttons button:nth-child(2)",
  ); // Deactivate/Cancel

  let currentItem = null;
  let isEditing = false;

  // 2. Modals Map
  const addModal = document.getElementById("add-user-modal");
  const deactivateModal = document.getElementById("deactivate-modal");

  // Email Validator
  const isValidEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  };

  // Auto-load first item if exists
  function loadInitialUser() {
    const firstItem = document.querySelector(".user-list-item");
    if (firstItem) {
      currentItem = firstItem;
      populateInfoPanel(currentItem);
    }
  }

  function populateInfoPanel(item) {
    infoName.innerText = item.getAttribute("data-name");
    infoEmail.innerText = item.getAttribute("data-email");
    infoRole.innerText = item.getAttribute("data-role");
    infoStatus.innerText = item.getAttribute("data-status");
    updateStatusVisuals();
  }

  loadInitialUser();

  // 3. LIST CLICKS
  if (listContainer) {
    listContainer.addEventListener("click", (e) => {
      const item = e.target.closest(".user-list-item");
      if (!item) return;

      if (currentItem) currentItem.classList.remove("selected");
      item.classList.add("selected");
      currentItem = item;

      if (isEditing) toggleEditMode();
      populateInfoPanel(item);
    });
  }

  function updateStatusVisuals() {
    const status = infoStatus.innerText.trim();
    if (status === "Active") {
      infoStatus.className = "info-value status-bg";
      btnAction2.className = "btn-deactivate";
      btnAction2.innerHTML = `<i class="fas fa-user-slash"></i> Deactivate`;
    } else {
      infoStatus.className = "info-value status-bg deact-bg";
      btnAction2.className = "btn-green-add";
      btnAction2.innerHTML = `<i class="fas fa-undo"></i> Re-activate`;
    }
  }

  // 4. EDIT LOGIC
  function toggleEditMode() {
    isEditing = !isEditing;

    if (isEditing) {
      infoName.innerHTML = `<input type="text" id="edit-name" value="${infoName.dataset.val}" class="edit-gray-input">`;
      infoEmail.innerHTML = `<input type="email" id="edit-email" value="${infoEmail.dataset.val}" class="edit-gray-input">`;

      const currentRole = infoRole.dataset.val;
      infoRole.innerHTML = `
                <div class="role-toggle" style="width: 100%; border: 1px solid #4caf50;">
                    <button type="button" class="role-btn ${currentRole === "Admin" ? "active" : ""}" data-val="Admin" style="flex: 1;">Admin</button>
                    <button type="button" class="role-btn ${currentRole === "Staff" ? "active" : ""}" data-val="Staff" style="flex: 1;">Staff</button>
                    <input type="hidden" id="edit-role-val" value="${currentRole}">
                </div>
            `;

      const editRoleBtns = infoRole.querySelectorAll(".role-btn");
      const editRoleInput = document.getElementById("edit-role-val");

      editRoleBtns.forEach((btn) => {
        btn.addEventListener("click", (e) => {
          editRoleBtns.forEach((b) => b.classList.remove("active"));
          e.target.classList.add("active");
          editRoleInput.value = e.target.getAttribute("data-val");
        });
      });

      btnAction1.className = "btn-green-add";
      btnAction1.innerHTML = `<i class="fas fa-check-circle"></i> Save`;
      btnAction2.className = "btn-cancel-new";
      btnAction2.innerHTML = `Cancel`;
    } else {
      infoName.innerText = infoName.dataset.val;
      infoEmail.innerText = infoEmail.dataset.val;
      infoRole.innerText = infoRole.dataset.val;
      updateStatusVisuals();

      btnAction1.className = "btn-edit";
      btnAction1.innerHTML = `<i class="fas fa-pen"></i> Edit`;
    }
  }

  if (btnAction1) {
    btnAction1.addEventListener("click", async () => {
      if (!currentItem) return alert("Please select a user first.");

      if (isEditing) {
        const newName = document.getElementById("edit-name").value.trim();
        const newEmail = document.getElementById("edit-email").value.trim();
        const newRole = document.getElementById("edit-role-val").value;

        // Validation
        if (!newName) return alert("Name cannot be empty.");
        if (!isValidEmail(newEmail))
          return alert("Please enter a valid email address.");

        try {
          // ==========================================
          // BACKEND TODO: ADD UPDATE FETCH API CALL HERE
          // const response = await fetch('api/update_user.php', { method: 'POST', body: ... });
          // if (!response.ok) throw new Error('Failed to update user');
          // ==========================================

          // Only update the UI if the fetch was successful
          currentItem.setAttribute("data-name", newName);
          currentItem.setAttribute("data-role", newRole);
          currentItem.setAttribute("data-email", newEmail);
          currentItem.querySelector(".fw-bold").innerText = newName;
          currentItem.querySelector(".text-gray").innerText = `| ${newRole}`;

          infoName.dataset.val = newName;
          infoEmail.dataset.val = newEmail;
          infoRole.dataset.val = newRole;

          toggleEditMode();
        } catch (error) {
          console.error("Update Error:", error);
          alert("An error occurred while saving the user. Please try again.");
        }
      } else {
        infoName.dataset.val = infoName.innerText;
        infoEmail.dataset.val = infoEmail.innerText;
        infoRole.dataset.val = infoRole.innerText;
        toggleEditMode();
      }
    });
  }

  // 5. DEACTIVATE / RE-ACTIVATE LOGIC
  if (btnAction2) {
    btnAction2.addEventListener("click", () => {
      if (isEditing) {
        toggleEditMode();
        return;
      }
      if (!currentItem) return alert("Please select a user first.");

      const currentStatus = currentItem.getAttribute("data-status");

      if (currentStatus === "Active") {
        document.getElementById("deact-name").value = infoName.innerText;
        document.getElementById("deact-email").value = infoEmail.innerText;
        document.getElementById("deact-role").value = infoRole.innerText;
        deactivateModal.style.display = "flex";
      } else {
        if (confirm("Are you sure you want to re-activate this user?")) {
          changeUserStatus("Active");
        }
      }
    });
  }

  document
    .getElementById("btn-cancel-modal")
    .addEventListener("click", () => (deactivateModal.style.display = "none"));

  document
    .getElementById("btn-confirm-deactivate")
    .addEventListener("click", () => {
      changeUserStatus("Deactivated");
    });

  async function changeUserStatus(newStatus) {
    const userId = currentItem.getAttribute("data-id");

    try {
      // ==============================================
      // BACKEND TODO: ADD STATUS UPDATE FETCH API CALL HERE
      // const response = await fetch('api/update_status.php', { method: 'POST', body: ... });
      // if (!response.ok) throw new Error('Failed to update status');
      // ==============================================

      currentItem.setAttribute("data-status", newStatus);
      const badgeClass = newStatus === "Active" ? "active" : "deactivated";
      currentItem.querySelector(".user-list-status").innerHTML =
        `<span class="badge ${badgeClass}">${newStatus}</span>`;

      infoStatus.innerText = newStatus;
      updateStatusVisuals();
      applyFilters();
      deactivateModal.style.display = "none";
    } catch (error) {
      console.error("Status Update Error:", error);
      alert(
        "Could not update user status. Please check your connection and try again.",
      );
      deactivateModal.style.display = "none";
    }
  }

  // 6. ADD USER MODAL LOGIC
  document
    .getElementById("btn-open-add-modal")
    .addEventListener("click", () => (addModal.style.display = "flex"));
  document.getElementById("btn-cancel-add").addEventListener("click", () => {
    addModal.style.display = "none";
    document.getElementById("add-user-form").reset();
  });

  document.querySelectorAll("#add-user-modal .role-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      document
        .querySelectorAll("#add-user-modal .role-btn")
        .forEach((b) => b.classList.remove("active"));
      e.target.classList.add("active");
      document.getElementById("add-role").value =
        e.target.getAttribute("data-val");
    });
  });

  document
    .getElementById("btn-confirm-add")
    .addEventListener("click", async () => {
      const name = document.getElementById("add-name").value.trim();
      const email = document.getElementById("add-email").value.trim();
      const password = document.getElementById("add-password").value;
      const confirmPw = document.getElementById("add-confirm-password").value;
      const role = document.getElementById("add-role").value;

      // ERROR TRAPPING: Strict Validation
      if (!name || !email || !password)
        return alert("Please fill out all required fields.");
      if (!isValidEmail(email))
        return alert("Please enter a valid email address.");
      if (password.length < 6)
        return alert("Password must be at least 6 characters long.");
      if (password !== confirmPw) return alert("Passwords do not match.");

      try {
        // =======================================
        // BACKEND TODO: ADD INSERT FETCH API CALL HERE
        // const response = await fetch('api/add_user.php', { method: 'POST', body: ... });
        // if (!response.ok) throw new Error('Failed to create user');
        // const newDbId = await response.json(); // Get the real ID from database
        // =======================================

        const dummyId = Math.floor(Math.random() * 1000); // Backend: Replace with newDbId

        const newItem = document.createElement("div");
        newItem.className = "user-list-item";
        newItem.setAttribute("data-id", dummyId);
        newItem.setAttribute("data-name", name);
        newItem.setAttribute("data-role", role);
        newItem.setAttribute("data-email", email);
        newItem.setAttribute("data-status", "Active");

        newItem.innerHTML = `
                <div class="user-list-info">
                    <span class="fw-bold">${name}</span> <span class="text-gray">| ${role}</span>
                </div>
                <div class="user-list-status">
                    <span class="badge active">Active</span>
                </div>
            `;

        listContainer.prepend(newItem);
        addModal.style.display = "none";
        document.getElementById("add-user-form").reset();

        // Reset Toggle UI to Staff
        document
          .querySelectorAll("#add-user-modal .role-btn")
          .forEach((b) => b.classList.remove("active"));
        document
          .querySelector('#add-user-modal .role-btn[data-val="Staff"]')
          .classList.add("active");

        // Auto-select newly added user
        newItem.click();
      } catch (error) {
        console.error("Creation Error:", error);
        alert("Failed to create the new user. Please try again.");
      }
    });

  // 7. FILTER LOGIC
  function applyFilters() {
    const term = searchInput.value.toLowerCase();
    const status = statusFilter.value;

    if (status === "All") listTitle.innerText = "All";
    else listTitle.innerText = status;

    document.querySelectorAll(".user-list-item").forEach((item) => {
      const name = item.getAttribute("data-name").toLowerCase();
      const itemStatus = item.getAttribute("data-status");

      const matchSearch = name.includes(term);
      const matchStatus = status === "All" || itemStatus === status;

      item.style.display = matchSearch && matchStatus ? "flex" : "none";
    });
  }

  searchInput.addEventListener("input", applyFilters);
  statusFilter.addEventListener("change", applyFilters);
});
