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
      
      const rawText = await res.text();
      let result;

      try {
        result = JSON.parse(rawText);
      } catch (e) {
        console.error("CRITICAL: Server did not return JSON. Raw output:", rawText);
        throw new Error("Server output invalid. Check console (F12) for details.");
      }

      if (result.session_expired) {
        showToast("Session Expired", "Please login again.", "error");
        setTimeout(() => (window.location.href = "../login.php"), 1500);
        return { status: "error" };
      }

      if (result.csrf_token) {
        csrfToken = result.csrf_token;
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        if (tokenInput) tokenInput.value = csrfToken;
      }

      if (result.message === "Access denied") {
        showToast("Access Denied", "You no longer have permission.", "error");
        setTimeout(() => (window.location.href = "../login.php"), 2000);
        return { status: "error" };
      }

      if (result.message === "Invalid CSRF token" && retry) {
        return postData(action, data, false);
      }

      return result;
    } catch (error) {
      console.error("Network Error:", error);
      return {
        status: "error",
        message: error.message || "Connection failed. Please check your internet.",
      };
    }
  }

  // ======== ELEMENTS ========
  const listContainer = document.getElementById("user-list-container");
  const searchInput = document.getElementById("search-input");
  const statusFilter = document.getElementById("status-filter");

  const infoName = document.getElementById("info-name");
  const infoEmail = document.getElementById("info-email");
  const infoRole = document.getElementById("info-role");
  const infoStatus = document.getElementById("info-status");

  const btnAction1 = document.querySelector("#info-action-buttons button:nth-child(1)");
  const btnAction2 = document.querySelector("#info-action-buttons button:nth-child(2)");

  const addModal = document.getElementById("add-user-modal");
  const deactivateModal = document.getElementById("deactivate-modal");

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
    const targetRole = item.getAttribute("data-role");
    const loggedInId = document.getElementById("logged-in-admin-id").value;

    infoName.innerText = item.getAttribute("data-name");
    infoEmail.innerText = item.getAttribute("data-email");
    infoRole.innerText = targetRole;
    infoStatus.innerText = item.getAttribute("data-status");

    infoName.dataset.val = infoName.innerText;
    infoEmail.dataset.val = infoEmail.innerText;
    infoRole.dataset.val = infoRole.innerText;

    const isSelf = Number(selectedUserId) === Number(loggedInId);
    const isOtherAdmin = targetRole === 'Admin' && !isSelf;

    // Security Logic: Hide controls based on role constraints
    if (isOtherAdmin) {
      btnAction1.style.display = "none"; // Admin cannot edit other admins
      btnAction2.style.display = "none"; // Admin cannot deactivate other admins
    } else if (isSelf) {
      btnAction1.style.display = "inline-block"; // Admin can edit self
      btnAction2.style.display = "none"; // Admin cannot deactivate self
    } else {
      btnAction1.style.display = "inline-block"; // Admin can edit staff
      btnAction2.style.display = "inline-block"; // Admin can deactivate staff
      updateStatusVisuals();
    }
  }

  function updateStatusVisuals() {
    const status = infoStatus.innerText.trim();
    if (status === "Active") {
      infoStatus.className = "info-value status-bg";
      if (btnAction2) {
        btnAction2.className = "btn-deactivate";
        btnAction2.innerHTML = `<span class="desktop-text"><i class="fas fa-user-slash"></i> Deactivate</span><span class="mobile-icon"><i class="fas fa-user-slash"></i></span>`;
      }
    } else {
      infoStatus.className = "info-value status-bg deact-bg";
      if (btnAction2) {
        btnAction2.className = "btn-green-add";
        btnAction2.innerHTML = `<span class="desktop-text"><i class="fas fa-undo"></i> Re-activate</span><span class="mobile-icon"><i class="fas fa-undo"></i></span>`;
      }
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

  const mobileBackBtn = document.getElementById("mobile-back-btn");
  if (mobileBackBtn) {
    mobileBackBtn.addEventListener("click", () => {
      document
        .querySelector(".user-layout")
        .classList.remove("show-mobile-details");
    });
  }

  function toggleEditMode() {
    isEditing = !isEditing;

    if (isEditing) {
      // ONLY Name and Email become inputs. Role remains static text.
      infoName.innerHTML = `<input type="text" id="edit-name" value="${infoName.dataset.val}" class="edit-gray-input">`;
      infoEmail.innerHTML = `<input type="email" id="edit-email" value="${infoEmail.dataset.val}" class="edit-gray-input">`;

      btnAction1.className = "btn-green-add";
      btnAction1.innerHTML = `<span class="desktop-text"><i class="fas fa-check-circle"></i> Save</span><span class="mobile-icon"><i class="fas fa-save"></i></span>`;

      if(btnAction2) {
        btnAction2.style.display = "inline-flex";
        btnAction2.className = "btn-cancel-new";
        btnAction2.innerHTML = `<span class="desktop-text">Cancel</span><span class="mobile-icon"><i class="fas fa-times"></i></span>`;
      }
    } else {
      infoName.innerText = infoName.dataset.val;
      infoEmail.innerText = infoEmail.dataset.val;
      
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

        if (!newName || !newEmail) {
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
          // Removed role append - it is no longer submitted during updates

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

  // Role Toggle Logic (For Adding New User ONLY)
  const addRoleBtns = document.querySelectorAll("#add-user-form .role-btn");
  const addRoleInput = document.getElementById("add-role");

  addRoleBtns.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      addRoleBtns.forEach((b) => b.classList.remove("active"));
      e.target.classList.add("active");
      addRoleInput.value = e.target.dataset.val;
    });
  });

  // ======== INLINE PASSWORD VALIDATION ========
  const addPassword = document.getElementById("add-password");
  const addConfirm = document.getElementById("add-confirm-password");
  const pwFeedback = document.getElementById("password-feedback");
  const confirmFeedback = document.getElementById("confirm-password-feedback");

  function getMissingPasswordRules(val) {
    const rules = [];
    if (val.length < 8) rules.push("8+ chars");
    if (!/[A-Z]/.test(val)) rules.push("uppercase");
    if (!/[a-z]/.test(val)) rules.push("lowercase");
    if (!/[0-9]/.test(val)) rules.push("number");
    if (!/[\W_]/.test(val)) rules.push("special char");
    return rules;
  }

  addPassword.addEventListener("input", (e) => {
    const val = e.target.value;
    if (!val) {
      pwFeedback.textContent = "";
      addPassword.classList.remove("input-error", "input-success");
      return;
    }

    const missing = getMissingPasswordRules(val);
    if (missing.length > 0) {
      pwFeedback.textContent = "Needs: " + missing.join(", ");
      pwFeedback.className = "inline-feedback error-text";
      addPassword.classList.add("input-error");
      addPassword.classList.remove("input-success");
    } else {
      pwFeedback.innerHTML = `<i class="fas fa-check-circle"></i> Strong password`;
      pwFeedback.className = "inline-feedback success-text";
      addPassword.classList.add("input-success");
      addPassword.classList.remove("input-error");
    }

    if (addConfirm.value) addConfirm.dispatchEvent(new Event("input"));
  });

  addConfirm.addEventListener("input", (e) => {
    const val = e.target.value;
    if (!val) {
      confirmFeedback.textContent = "";
      addConfirm.classList.remove("input-error", "input-success");
      return;
    }

    if (val !== addPassword.value) {
      confirmFeedback.textContent = "Passwords do not match.";
      confirmFeedback.className = "inline-feedback error-text";
      addConfirm.classList.add("input-error");
      addConfirm.classList.remove("input-success");
    } else {
      confirmFeedback.innerHTML = `<i class="fas fa-check-circle"></i> Passwords match`;
      confirmFeedback.className = "inline-feedback success-text";
      addConfirm.classList.add("input-success");
      addConfirm.classList.remove("input-error");
    }
  });

  // Add User Logic
  document
    .getElementById("btn-open-add-modal")
    .addEventListener("click", () => (addModal.style.display = "flex"));
    
  document.getElementById("btn-cancel-add").addEventListener("click", () => {
    addModal.style.display = "none";
    document.getElementById("add-user-form").reset();
    
    // Clear validation styling when cancel is clicked
    addPassword.classList.remove("input-error", "input-success");
    addConfirm.classList.remove("input-error", "input-success");
    pwFeedback.textContent = "";
    confirmFeedback.textContent = "";
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
        showToast("Missing Fields", "Please fill out all required fields.", "error");
        return;
      }

      if (!isValidEmail(email)) {
        showToast("Email Error", "Please enter a valid email address.", "error");
        return;
      }

      const missingRules = getMissingPasswordRules(password);
      if (missingRules.length > 0) {
        showToast("Weak Password", "Please fix the highlighted password errors.", "error");
        addPassword.classList.add("input-error");
        return;
      }

      if (password !== confirmPw) {
        showToast("Password Mismatch", "Passwords do not match.", "error");
        addConfirm.classList.add("input-error");
        return;
      }

      const confirmBtn = document.getElementById("btn-confirm-add");

      try {
        setLoading(confirmBtn, "Creating...");

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
      } finally {
        resetButton(confirmBtn);
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
        inputField.type = "text";
        this.classList.remove("fa-eye");
        this.classList.add("fa-eye-slash");
      } else {
        inputField.type = "password";
        this.classList.remove("fa-eye-slash");
        this.classList.add("fa-eye");
      }
    });
  });
});