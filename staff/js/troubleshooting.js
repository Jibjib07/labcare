document.addEventListener("DOMContentLoaded", function () {
  // Core UI Elements
  const tableBody = document.getElementById("guideTableBody");
  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("categoryFilter");
  const statusToggleBtns = document.querySelectorAll(".status-toggle-btn");
  const statusValueInput = document.getElementById("statusValue");
  const detailView = document.getElementById("detailView");
  const actionButtons = document.getElementById("actionButtons");
  const editBtn = document.getElementById("mainEditBtn");
  const archiveToggleBtn = document.getElementById("archiveToggleBtn");
  const notificationContainer = document.getElementById(
    "notification-container",
  );

  // Modals
  const addModal = document.getElementById("addGuideModal");
  const openAddBtn = document.getElementById("openAddModal");
  const closeAddBtn = document.getElementById("closeAddModal");
  const addGuideForm = document.getElementById("addGuideForm");
  const submitCreateBtn = document.getElementById("submitCreateBtn");
  const archiveModal = document.getElementById("archiveConfirmModal");
  const closeArchiveBtn = document.getElementById("closeArchiveModal");
  const confirmArchiveBtn = document.getElementById("confirmArchiveBtn");
  const restoreModal = document.getElementById("restoreConfirmModal");
  const closeRestoreBtn = document.getElementById("closeRestoreModal");
  const confirmRestoreBtn = document.getElementById("confirmRestoreBtn");

  // Data State
  const categoriesListInput = document.getElementById("categoryList");
  const categories = categoriesListInput
    ? JSON.parse(categoriesListInput.value || "[]")
    : [];
  let currentGuideId = null;
  let currentGuideData = null;
  let skipAnimation = false;

  // --- UTILITIES ---

  function adjustTextareaHeight(el) {
    if (!el || el.tagName !== "TEXTAREA") return;
    el.style.height = "45px";
    el.style.height = el.scrollHeight + "px";
  }

  function showNotification(type, title, message) {
    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;

    let iconClass = "fa-info-circle";
    if (type === "success") iconClass = "fa-check-circle";
    if (type === "error") iconClass = "fa-exclamation-circle";
    if (type === "archive") iconClass = "fa-box-archive";

    toast.innerHTML = `
            <div class="toast-icon"><i class="fas ${iconClass}"></i></div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-sub">${message}</div>
            </div>`;
    notificationContainer.appendChild(toast);
    setTimeout(() => {
      toast.style.animation = "fadeOut 0.5s ease forwards";
      setTimeout(() => toast.remove(), 500);
    }, 4000);
  }

  // --- BUTTON STATE & VISIBILITY ---

  function updateButtonVisibility() {
    if (!editBtn || !archiveToggleBtn) return;

    const isArchived = statusValueInput.value === "Archived";

    if (isArchived) {
      editBtn.style.setProperty("display", "none", "important");
      archiveToggleBtn.innerHTML =
        '<i class="fas fa-undo"></i> <span>Restore</span>';
      archiveToggleBtn.className = "btn-restore";
      archiveToggleBtn.style.setProperty("display", "inline-flex", "important");
    } else {
      editBtn.style.setProperty("display", "inline-flex", "important");
      archiveToggleBtn.innerHTML =
        '<i class="fas fa-box-archive"></i> <span>Archive</span>';
      archiveToggleBtn.className = "btn-archive";
      archiveToggleBtn.style.setProperty("display", "inline-flex", "important");
    }
  }

  function resetEditButton() {
    if (!editBtn) return;
    editBtn.innerHTML = '<i class="fas fa-pen"></i> <span>Edit</span>';
    editBtn.className = "btn-edit";
    editBtn.classList.replace("btn-save", "btn-edit");
    updateButtonVisibility();
  }

  function exitEditMode() {
    const cancelBtn = document.getElementById("editCancelBtn");

    detailView
      .querySelectorAll(".view-mode")
      .forEach((el) => (el.style.display = "block"));
    detailView
      .querySelectorAll(".edit-mode")
      .forEach((el) => (el.style.display = "none"));

    resetEditButton();
    if (cancelBtn) cancelBtn.remove();
  }

  function cancelEdit() {
    if (currentGuideData) {
      renderDetails(currentGuideData);
      exitEditMode();
      detailView
        .querySelectorAll(".detail-textarea.edit-mode")
        .forEach(adjustTextareaHeight);
    }
  }

  // --- LIST LOGIC ---

  function refreshTable(autoSelectFirst = true) {
    const search = searchInput.value;
    const category = categoryFilter.value;
    const status = statusValueInput ? statusValueInput.value : "Available";

    if (document.getElementById("editCancelBtn")) {
      exitEditMode();
    }

    skipAnimation = true;
    updateButtonVisibility();

    fetch(
      `troubleshooting.php?ajax_filter=1&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&status=${status}`,
    )
      .then((res) => res.text())
      .then((html) => {
        tableBody.innerHTML = html;
        const firstRow = tableBody.querySelector(".guide-item");

        if (autoSelectFirst && firstRow) {
          if (window.innerWidth > 768) {
            firstRow.click();
          }
        } else if (!firstRow) {
          detailView.innerHTML = "";
          actionButtons.style.display = "none";
        } else if (currentGuideId) {
          const activeRow = tableBody.querySelector(
            `.guide-item[data-id="${currentGuideId}"]`,
          );
          if (activeRow) activeRow.classList.add("active-row");
        }
        setTimeout(() => {
          skipAnimation = false;
        }, 50);
      })
      .catch((err) => console.error("Error loading list:", err));
  }

  // --- SELECTION & RENDERING ---

  tableBody.onclick = (e) => {
    const row = e.target.closest(".guide-item");
    if (!row) return;

    if (window.innerWidth <= 768) {
      document
        .querySelector(".troubleshoot-layout")
        .classList.add("show-mobile-detail");
      window.scrollTo({ top: 0, behavior: "smooth" });
    }

    if (document.getElementById("editCancelBtn")) {
      exitEditMode();
    }

    if (!skipAnimation) {
      const rightPanel = document.querySelector(".right-detail-panel");
      rightPanel.style.animation = "none";
      void rightPanel.offsetWidth;
      rightPanel.style.animation = null;
    }

    document
      .querySelectorAll(".guide-item")
      .forEach((r) => r.classList.remove("active-row"));
    row.classList.add("active-row");

    currentGuideId = row.getAttribute("data-id");
    fetch(`troubleshooting.php?get_details=${currentGuideId}`)
      .then((res) => res.json())
      .then((data) => {
        currentGuideData = data;
        actionButtons.style.display = "flex";

        updateButtonVisibility();

        renderDetails(data);
        detailView
          .querySelectorAll(".detail-textarea.edit-mode")
          .forEach(adjustTextareaHeight);
      });
  };

  function renderDetails(data) {
    let categoryOptions = categories
      .map(
        (cat) =>
          `<option value="${cat}" ${cat === data.issue_catego ? "selected" : ""}>${cat}</option>`,
      )
      .join("");

    detailView.innerHTML = `
            <div class="detail-group">
                <label>Category</label>
                <div class="view-mode">${data.issue_catego}</div>
                <select class="detail-select edit-mode" name="issue_catego" style="display:none;">${categoryOptions}</select>
            </div>
            <div class="detail-group">
                <label>Issue Title</label>
                <div class="view-mode">${data.issue_title}</div>
                <input type="text" class="detail-input edit-mode" name="issue_title" value="${data.issue_title}" style="display:none;">
            </div>
            <div class="detail-group">
                <label>Summary Description</label>
                <div class="view-mode">${data.issue_summary || "-"}</div>
                <textarea class="detail-textarea edit-mode" name="issue_summary" style="display:none;">${data.issue_summary}</textarea>
            </div>
            <div class="detail-group">
                <label>Possible Causes</label>
                <div class="view-mode">${data.issue_cause || "-"}</div>
                <textarea class="detail-textarea edit-mode" name="issue_cause" style="display:none;">${data.issue_cause}</textarea>
            </div>
            <div class="detail-group">
                <label>Step-by-Step Solution</label>
                <div class="view-mode">${data.issue_solutio || "-"}</div>
                <textarea class="detail-textarea edit-mode" name="issue_solutio" style="display:none;">${data.issue_solutio}</textarea>
            </div>
            <div class="detail-group">
                <label>Preventive Measures</label>
                <div class="view-mode">${data.issue_preven || "-"}</div>
                <textarea class="detail-textarea edit-mode" name="issue_preven" style="display:none;">${data.issue_preven || ""}</textarea>
            </div>`;
  }

  detailView.addEventListener("input", (e) => {
    if (e.target.classList.contains("detail-textarea")) {
      adjustTextareaHeight(e.target);
    }
  });

  addGuideForm.addEventListener("input", (e) => {
    if (e.target.tagName === "TEXTAREA") {
      adjustTextareaHeight(e.target);
    }
  });

  // --- EDIT, SAVE & CANCEL ACTION ---

  if (editBtn) {
    editBtn.onclick = () => {
      const isEditing = editBtn.classList.contains("btn-save");

      if (!isEditing) {
        // --- ENTERING EDIT MODE ---
        detailView
          .querySelectorAll(".view-mode")
          .forEach((el) => (el.style.display = "none"));
        detailView
          .querySelectorAll(".edit-mode")
          .forEach((el) => (el.style.display = "block"));

        editBtn.innerHTML = '<i class="fas fa-save"></i> <span>Save</span>';
        editBtn.classList.replace("btn-edit", "btn-save");

        archiveToggleBtn.style.setProperty("display", "none", "important");

        if (!document.getElementById("editCancelBtn")) {
          const cancelBtn = document.createElement("button");
          cancelBtn.id = "editCancelBtn";
          cancelBtn.className = "btn-cancel-edit";
          cancelBtn.innerHTML =
            '<i class="fas fa-times"></i> <span>Cancel</span>';

          cancelBtn.onclick = (e) => {
            e.stopPropagation();
            cancelEdit();
          };
          editBtn.parentNode.insertBefore(cancelBtn, editBtn);
        }

        detailView
          .querySelectorAll(".detail-textarea.edit-mode")
          .forEach(adjustTextareaHeight);
      } else {
        // --- SAVING MODE ---
        const formData = new FormData();
        formData.append("update_guide", "1");
        formData.append("guide_id", currentGuideId);

        detailView
          .querySelectorAll(".edit-mode")
          .forEach((f) => formData.append(f.name, f.value));

        fetch("troubleshooting.php", { method: "POST", body: formData })
          .then((res) => res.json())
          .then((result) => {
            if (result.status === "success") {
              showNotification(
                "success",
                "Guide Updated",
                "Changes saved successfully.",
              );
              const updatedData = {};
              formData.forEach((value, key) => {
                updatedData[key] = value;
              });
              currentGuideData = { ...currentGuideData, ...updatedData };
              exitEditMode();
              renderDetails(currentGuideData);
              refreshTable(false);
            }
          });
      }
    };
  }

  // --- MODAL CONTROLS ---

  if (openAddBtn) openAddBtn.onclick = () => (addModal.style.display = "flex");
  if (closeAddBtn) {
    closeAddBtn.onclick = () => {
      addModal.style.display = "none";
      addGuideForm.reset();
      addGuideForm
        .querySelectorAll("textarea")
        .forEach((t) => (t.style.height = "45px"));
    };
  }

  if (submitCreateBtn) {
    submitCreateBtn.onclick = (e) => {
      e.preventDefault();

      const titleInput = addGuideForm
        .querySelector('input[name="issue_title"]')
        .value.trim();
      const categorySelect = addGuideForm
        .querySelector('select[name="issue_catego"]')
        .value.trim();

      if (titleInput === "") {
        showNotification(
          "error",
          "Validation Error",
          "Issue Title cannot be empty.",
        );
        return;
      }
      if (categorySelect === "") {
        showNotification(
          "error",
          "Validation Error",
          "Please select a Category.",
        );
        return;
      }

      const formData = new FormData(addGuideForm);
      formData.append("create_guide", "1");

      fetch("troubleshooting.php", { method: "POST", body: formData })
        .then((res) => res.json())
        .then((result) => {
          if (result.status === "success") {
            showNotification(
              "success",
              "Guide Published",
              "New guide added to the list.",
            );
            closeAddBtn.onclick();
            refreshTable(true);
          } else {
            showNotification("error", "Action Failed", result.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showNotification(
            "error",
            "System Error",
            "An unexpected error occurred.",
          );
        });
    };
  }

  if (archiveToggleBtn) {
    archiveToggleBtn.onclick = () => {
      if (statusValueInput.value === "Available") {
        document.getElementById("archiveIssueTitle").value =
          currentGuideData.issue_title;
        document.getElementById("archiveCategory").value =
          currentGuideData.issue_catego;
        archiveModal.style.display = "flex";
      } else {
        document.getElementById("restoreIssueTitle").value =
          currentGuideData.issue_title;
        document.getElementById("restoreCategory").value =
          currentGuideData.issue_catego;
        restoreModal.style.display = "flex";
      }
    };
  }

  if (closeArchiveBtn)
    closeArchiveBtn.onclick = () => (archiveModal.style.display = "none");
  if (closeRestoreBtn)
    closeRestoreBtn.onclick = () => (restoreModal.style.display = "none");

  if (confirmArchiveBtn) {
    confirmArchiveBtn.onclick = () => {
      const formData = new FormData();
      formData.append("archive_guide", "1");
      formData.append("guide_id", currentGuideId);
      fetch("troubleshooting.php", { method: "POST", body: formData })
        .then((res) => res.json())
        .then((result) => {
          if (result.status === "success") {
            showNotification(
              "success",
              "Guide Archived",
              "Moved to archived list.",
            );
            archiveModal.style.display = "none";
            refreshTable(true);
          }
        });
    };
  }

  if (confirmRestoreBtn) {
    confirmRestoreBtn.onclick = () => {
      const formData = new FormData();
      formData.append("restore_guide", "1");
      formData.append("guide_id", currentGuideId);
      fetch("troubleshooting.php", { method: "POST", body: formData })
        .then((res) => res.json())
        .then((result) => {
          if (result.status === "success") {
            showNotification(
              "success",
              "Guide Restored",
              "Moved back to active list.",
            );
            restoreModal.style.display = "none";
            refreshTable(true);
          }
        });
    };
  }

  statusToggleBtns.forEach((btn) => {
    btn.onclick = function () {
      statusToggleBtns.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      statusValueInput.value = this.getAttribute("data-status");
      refreshTable(true);
    };
  });

  searchInput.oninput = () => refreshTable(true);
  categoryFilter.onchange = () => refreshTable(true);

  refreshTable(true);
});

function closeMobileDetails() {
  const layout = document.querySelector(".troubleshoot-layout");
  if (layout) {
    layout.classList.remove("show-mobile-detail");
    window.scrollTo({ top: 0, behavior: "smooth" });
  }
}