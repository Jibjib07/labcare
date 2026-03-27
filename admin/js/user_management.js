document.addEventListener("DOMContentLoaded", () => {
  let csrfToken =
    document.querySelector('input[name="csrf_token"]')?.value || "";

  function showToast(title, message, type = "success") {
    const toast = document.getElementById("authToast");
    const icon = document.getElementById("toast-icon");
    const titleText = document.getElementById("toast-title");
    const msgText = document.getElementById("toast-message");

    titleText.innerText = title;
    msgText.innerText = message;

    if (type === "error") {
      icon.classList.add("error");
      icon.innerHTML = `<i class="fas fa-exclamation-circle"></i>`;
    } else {
      icon.classList.remove("error");
      icon.innerHTML = `<i class="fas fa-check-circle"></i>`;
    }

    toast.classList.add("active");

    setTimeout(() => {
      toast.classList.remove("active");
    }, 5000);
  }

  const storedToast = sessionStorage.getItem("toastMessage");
  if (storedToast) {
    const toastData = JSON.parse(storedToast);
    showToast(toastData.title, toastData.message, toastData.type);
    sessionStorage.removeItem("toastMessage");
  }

  // ======== POST DATA WITH ROTATED CSRF + RETRY + SESSION HANDLING ========
  async function postData(action, data, retry = true) {
    data.append("action", action);
    data.delete("csrf_token");
    data.append("csrf_token", csrfToken);

    try {
      const res = await fetch("user_management.php", {
        method: "POST",
        body: data,
      });
      const result = await res.json();

      if (result.session_expired) {
        showToast("Session Expired", "Please login again.", "error");
        setTimeout(() => (window.location.href = "../login.php"), 1500);
        return {
          status: "error",
        };
      }

      if (result.csrf_token) {
        csrfToken = result.csrf_token;
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        if (tokenInput) tokenInput.value = csrfToken;
      }

      if (result.message === "Access denied") {
        showToast("Access Denied", "You no longer have permission.", "error");
        setTimeout(() => (window.location.href = "../login.php"), 2000);
        return {
          status: "error",
        };
      }

      if (result.message === "Invalid CSRF token" && retry) {
        return postData(action, data, false);
      }

      return result;
    } catch (error) {
      console.error("Network Error:", error);
      showToast(
        "Network Issue",
        "Connection failed. Please check your internet or try again.",
        "error",
      );
      return {
        status: "error",
        message: "network_error",
      };
    }
  }

  // ======== ELEMENTS ========
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
  );
  const btnAction2 = document.querySelector(
    "#info-action-buttons button:nth-child(2)",
  );
  const resetBtn = document.getElementById("resetBtn"); // Recovery Button

  const addModal = document.getElementById("add-user-modal");
  const deactivateModal = document.getElementById("deactivate-modal");
  const resetModal = document.getElementById("reset-modal");
  const btnConfirmReset = document.getElementById("btn-confirm-reset");

  let currentItem = null;
  let isEditing = false;

  const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

  function setLoading(button, text = "Processing...") {
    button.disabled = true;
    button.dataset.original = button.innerHTML;
    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${text}`;
  }

  function resetButton(button) {
    button.disabled = false;
    button.innerHTML = button.dataset.original;
  }

  function requireSelection() {
    if (!currentItem) {
      showToast("No User Selected", "Please select a user first.", "error");
      return false;
    }
    return true;
  }

  function loadInitialUser() {
    const firstItem = document.querySelector(".user-list-item");
    if (firstItem) {
      currentItem = firstItem;
      populateInfoPanel(currentItem);
    }
  }

  function populateInfoPanel(item) {
    const selectedUserId = item.getAttribute("data-id");
    const loggedInId = document.getElementById("logged-in-admin-id").value;

    infoName.innerText = item.getAttribute("data-name");
    infoEmail.innerText = item.getAttribute("data-email");
    infoRole.innerText = item.getAttribute("data-role");
    infoStatus.innerText = item.getAttribute("data-status");

    // Logic: Hide controls if viewing SELF
    const isSelf = Number(selectedUserId) === Number(loggedInId);

    if (isSelf) {
      btnAction2.style.display = "none"; // Hide Deactivate
      document.querySelector(".security-panel").style.visibility = "hidden"; // Hide Recovery
      document.querySelector(".security-panel").style.opacity = "0";
    } else {
      btnAction2.style.display = "inline-block";
      document.querySelector(".security-panel").style.visibility = "visible";
      document.querySelector(".security-panel").style.opacity = "1";
      updateStatusVisuals();
    }

    infoName.dataset.val = infoName.innerText;
    infoEmail.dataset.val = infoEmail.innerText;
    infoRole.dataset.val = infoRole.innerText;

    updateStatusVisuals();
  }

  function updateStatusVisuals() {
    const status = infoStatus.innerText.trim();
    if (status === "Active") {
      infoStatus.className = "info-value status-bg";
      btnAction2.className = "btn-deactivate";
      btnAction2.innerHTML = `<i class="fas fa-user-slash"></i> <span>Deactivate</span>`;
    } else {
      infoStatus.className = "info-value status-bg deact-bg";
      btnAction2.className = "btn-green-add";
      btnAction2.innerHTML = `<i class="fas fa-undo"></i> <span>Re-activate</span>`;
    }
  }

  loadInitialUser();

  if (listContainer) {
    listContainer.addEventListener("click", (e) => {
      const item = e.target.closest(".user-list-item");
      if (!item) return;

      if (currentItem) currentItem.classList.remove("selected");
      item.classList.add("selected");
      currentItem = item;

      if (isEditing) toggleEditMode();
      populateInfoPanel(item);

      // 🟢 MOBILE TRANSITION LOGIC
      if (window.innerWidth <= 900) {
        const userName = item.getAttribute("data-name");
        const detailTitle = document.getElementById("mobile-detail-title");
        if (detailTitle) detailTitle.innerText = userName;

        document
          .querySelector(".user-layout")
          .classList.add("show-mobile-details");
        window.scrollTo({ top: 0, behavior: "smooth" });
      }
    });
  }

  // 🟢 MOBILE BACK BUTTON LOGIC
  const mobileBackBtn = document.getElementById("mobile-back-btn");
  if (mobileBackBtn) {
    mobileBackBtn.addEventListener("click", () => {
      document
        .querySelector(".user-layout")
        .classList.remove("show-mobile-details");
    });
  }

  function updateStatusVisuals() {
    const status = infoStatus.innerText.trim();
    if (status === "Active") {
      infoStatus.className = "info-value status-bg";
      btnAction2.className = "btn-deactivate";
      btnAction2.innerHTML = `<span class="desktop-text"><i class="fas fa-user-slash"></i> Deactivate</span><span class="mobile-icon"><i class="fas fa-user-slash"></i></span>`;
    } else {
      infoStatus.className = "info-value status-bg deact-bg";
      btnAction2.className = "btn-green-add";
      btnAction2.innerHTML = `<span class="desktop-text"><i class="fas fa-undo"></i> Re-activate</span><span class="mobile-icon"><i class="fas fa-undo"></i></span>`;
    }
  }

  function toggleEditMode() {
    isEditing = !isEditing;

    if (isEditing) {
      const selectedUserId = currentItem.getAttribute("data-id");
      const loggedInId = document.getElementById("logged-in-admin-id").value;

      infoName.innerHTML = `<input type="text" id="edit-name" value="${infoName.dataset.val}" class="edit-gray-input">`;
      infoEmail.innerHTML = `<input type="email" id="edit-email" value="${infoEmail.dataset.val}" class="edit-gray-input">`;

      const currentRole = infoRole.dataset.val;
      const isSelf = Number(selectedUserId) === Number(loggedInId);
      const disabledAttr = isSelf
        ? 'style="opacity: 0.6; pointer-events: none; cursor: not-allowed;"'
        : "";
      const selfNote = isSelf
        ? '<br><span style="color: #f39c12; font-size: 11px;">You cannot change your own role.</span>'
        : "";

      infoRole.innerHTML = `
            <div class="role-toggle" ${disabledAttr}>
                <button type="button" class="role-btn ${currentRole === "Admin" ? "active" : ""}" data-val="Admin" style="flex: 1;">Admin</button>
                <button type="button" class="role-btn ${currentRole === "Staff" ? "active" : ""}" data-val="Staff" style="flex: 1;">Staff</button>
                <input type="hidden" id="edit-role-val" value="${currentRole}">
            </div>
            ${selfNote}
        `;

      infoRole.querySelectorAll(".role-btn").forEach((btn) => {
        btn.addEventListener("click", (e) => {
          infoRole
            .querySelectorAll(".role-btn")
            .forEach((b) => b.classList.remove("active"));
          e.target.classList.add("active");
          document.getElementById("edit-role-val").value = e.target.dataset.val;
        });
      });

      // 🟢 Save and Cancel buttons (Desktop original + Mobile specific icons)
      btnAction1.className = "btn-green-add";
      btnAction1.innerHTML = `<span class="desktop-text"><i class="fas fa-check-circle"></i> Save</span><span class="mobile-icon"><i class="fas fa-save"></i></span>`;

      btnAction2.style.display = "inline-flex";
      btnAction2.className = "btn-cancel-new";
      btnAction2.innerHTML = `<span class="desktop-text">Cancel</span><span class="mobile-icon"><i class="fas fa-times"></i></span>`;
    } else {
      infoName.innerText = infoName.dataset.val;
      infoEmail.innerText = infoEmail.dataset.val;
      infoRole.innerText = infoRole.dataset.val;
      updateStatusVisuals();

      // 🟢 Restore Edit button
      btnAction1.className = "btn-edit";
      btnAction1.innerHTML = `<span class="desktop-text"><i class="fas fa-pen"></i> Edit</span><span class="mobile-icon"><i class="fas fa-pen"></i></span>`;
      populateInfoPanel(currentItem);
    }
  }

  // Action: Save Edit
  if (btnAction1) {
    btnAction1.addEventListener("click", async () => {
      if (!requireSelection()) return;

      if (isEditing) {
        const newName = document.getElementById("edit-name").value.trim();
        const newEmail = document.getElementById("edit-email").value.trim();
        const newRole = document.getElementById("edit-role-val").value;

        if (!newName || !newEmail || !newRole) {
          showToast("Missing Fields", "Please fill out all fields.", "error");
          return;
        }
        if (!isValidEmail(newEmail)) {
          showToast("Email Error", "Please enter a valid email.", "error");
          return;
        }

        try {
          setLoading(btnAction1, "Saving...");
          const formData = new FormData();
          formData.append("id", currentItem.dataset.id);
          formData.append("name", newName);
          formData.append("email", newEmail);
          formData.append("role", newRole);

          const data = await postData("update_user", formData);

          if (data.status !== "success")
            throw new Error(data.message || "Update failed");

          sessionStorage.setItem(
            "toastMessage",
            JSON.stringify({
              title: "User Updated",
              message: "User information updated successfully.",
              type: "success",
            }),
          );
          location.reload();
        } catch (err) {
          showToast("Update Failed", err.message, "error");
          resetButton(btnAction1);
        }
      } else {
        toggleEditMode();
      }
    });
  }

  // Action: Deactivate/Cancel
  if (btnAction2) {
    btnAction2.addEventListener("click", () => {
      if (isEditing) {
        toggleEditMode();
        return;
      }
      if (!requireSelection()) return;

      const currentStatus = currentItem.dataset.status;
      document.getElementById("deact-name").value = currentItem.dataset.name;
      document.getElementById("deact-email").value = currentItem.dataset.email;
      document.getElementById("deact-role").value = currentItem.dataset.role;

      const modalTitle = deactivateModal.querySelector("h2");
      const confirmBtn = document.getElementById("btn-confirm-deactivate");

      if (currentStatus === "Active") {
        modalTitle.innerText = "Deactivate this User";
        confirmBtn.innerHTML = `<i class="fas fa-user-slash"></i> Deactivate`;
        confirmBtn.className = "btn-red";
        deactivateModal.setAttribute("data-action", "deactivate");
      } else {
        modalTitle.innerText = "Re-activate this User";
        confirmBtn.innerHTML = `<i class="fas fa-undo"></i> Re-activate`;
        confirmBtn.className = "btn-green-add";
        deactivateModal.setAttribute("data-action", "reactivate");
      }

      deactivateModal.style.display = "flex";
    });
  }

  // ACTION: SEND RESET LINK
  let resetArmed = false;
  let resetTimeout = null;

  if (btnConfirmReset) {
    btnConfirmReset.addEventListener("click", async function () {
      // STEP 1: ARM STATE (FIRST CLICK)
      if (!resetArmed) {
        resetArmed = true;

        this.disabled = true;
        this.dataset.original = this.innerHTML;
        this.innerHTML = `<i class="fas fa-hourglass-half"></i> Confirming...`;

        showToast("Confirm Action", "Click again to send reset link.", "error");

        // 2-second delay lock
        resetTimeout = setTimeout(() => {
          this.disabled = false;
          this.innerHTML = `<i class="fas fa-lock"></i> Send Link`;
        }, 2000);

        return;
      }

      // STEP 2: ACTUAL EXECUTION (SECOND CLICK)
      try {
        setLoading(this, "Sending Link...");

        const formData = new FormData();
        formData.append("id", currentItem.dataset.id);

        const result = await postData("admin_send_reset", formData);

        if (result.status === "success") {
          showToast(
            "Link Sent",
            `A secure recovery link has been sent to ${currentItem.dataset.email}.`,
            "success",
          );
          resetModal.style.display = "none";
        } else {
          showToast(
            "Failed",
            result.message || "Could not send reset link.",
            "error",
          );
        }
      } catch (err) {
        showToast(
          "Request Failed",
          err.message || "Unexpected error occurred. Please try again.",
          "error",
        );
      } finally {
        resetButton(this);
        resetArmed = false;
        clearTimeout(resetTimeout);
      }
    });
  }

  document.getElementById("btn-cancel-reset").addEventListener("click", () => {
    resetModal.style.display = "none";

    // Reset state
    resetArmed = false;
    clearTimeout(resetTimeout);

    btnConfirmReset.disabled = false;
    btnConfirmReset.innerHTML = `<i class="fas fa-lock"></i> Send Link`;
  });

  if (resetBtn) {
    resetBtn.addEventListener("click", () => {
      if (!requireSelection()) return;

      document.getElementById("reset-name").value = currentItem.dataset.name;
      document.getElementById("reset-email").value = currentItem.dataset.email;

      // Reset state every open
      resetArmed = false;
      clearTimeout(resetTimeout);
      btnConfirmReset.disabled = false;
      btnConfirmReset.innerHTML = `<i class="fas fa-lock"></i> Send Link`;

      resetModal.style.display = "flex";
    });
  }

  // Modals & Filters Logic...
  document
    .getElementById("btn-cancel-modal")
    .addEventListener("click", () => (deactivateModal.style.display = "none"));

  document
    .getElementById("btn-confirm-deactivate")
    .addEventListener("click", async () => {
      const action = deactivateModal.dataset.action;
      const newStatus = action === "deactivate" ? "Deactivated" : "Active";
      const confirmBtn = document.getElementById("btn-confirm-deactivate");

      try {
        const formData = new FormData();
        formData.append("id", currentItem.dataset.id);
        formData.append("status", newStatus);

        setLoading(confirmBtn, "Updating...");
        const data = await postData("update_status", formData);
        if (data.status !== "success") throw new Error(data.message);

        sessionStorage.setItem(
          "toastMessage",
          JSON.stringify({
            title: "Success",
            message: `User is now ${newStatus.toLowerCase()}.`,
            type: "success",
          }),
        );
        location.reload();
      } catch (err) {
        resetButton(confirmBtn);
        showToast("Update Failed", err.message, "error");
      }
    });

  // Add User Logic
  document
    .getElementById("btn-open-add-modal")
    .addEventListener("click", () => (addModal.style.display = "flex"));
  document.getElementById("btn-cancel-add").addEventListener("click", () => {
    addModal.style.display = "none";
    document.getElementById("add-user-form").reset();
  });

  document
    .getElementById("btn-confirm-add")
    .addEventListener("click", async () => {
      const name = document.getElementById("add-name").value.trim();
      const email = document.getElementById("add-email").value.trim();
      const password = document.getElementById("add-password").value;
      const confirmPw = document.getElementById("add-confirm-password").value;
      const role = document.getElementById("add-role").value;

      if (!name || !email || !password) {
        showToast(
          "Missing Fields",
          "Please fill out all required fields.",
          "error",
        );
        return;
      }

      const passwordRegex = {
        upper: /[A-Z]/,
        lower: /[a-z]/,
        number: /[0-9]/,
        special: /[\W_]/,
      };

      if (
        !passwordRegex.upper.test(password) ||
        !passwordRegex.lower.test(password) ||
        !passwordRegex.number.test(password) ||
        !passwordRegex.special.test(password) ||
        password.length < 8
      ) {
        showToast(
          "Password Error",
          "Password does not meet security requirements.",
          "error",
        );
        return;
      }

      if (password !== confirmPw) {
        showToast("Password Error", "Passwords do not match.", "error");
        return;
      }

      try {
        const formData = new FormData();
        formData.append("name", name);
        formData.append("email", email);
        formData.append("password", password);
        formData.append("role", role);

        const data = await postData("add_user", formData);
        if (data.status !== "success") throw new Error(data.message);

        sessionStorage.setItem(
          "toastMessage",
          JSON.stringify({
            title: "User Created",
            message: "New user added successfully.",
            type: "success",
          }),
        );
        location.reload();
      } catch (err) {
        showToast("Creation Failed", err.message, "error");
      }
    });

  // Filters
  searchInput.addEventListener("input", applyFilters);
  statusFilter.addEventListener("change", applyFilters);

  function applyFilters() {
    const term = searchInput.value.toLowerCase();
    const status = statusFilter.value;
    document.querySelectorAll(".user-list-item").forEach((item) => {
      const name = item.dataset.name.toLowerCase();
      const itemStatus = item.dataset.status;
      item.style.display =
        name.includes(term) && (status === "All" || itemStatus === status)
          ? "flex"
          : "none";
    });
  }

  // Toggle password visibility
  const togglePasswordIcons = document.querySelectorAll(".toggle-password");

  togglePasswordIcons.forEach((icon) => {
    icon.addEventListener("click", function () {
      const targetId = this.getAttribute("data-target");
      const inputField = document.getElementById(targetId);

      if (inputField.type === "password") {
        // Show password
        inputField.type = "text";
        this.classList.remove("fa-eye");
        this.classList.add("fa-eye-slash");
      } else {
        // Hide password
        inputField.type = "password";
        this.classList.remove("fa-eye-slash");
        this.classList.add("fa-eye");
      }
    });
  });
});
