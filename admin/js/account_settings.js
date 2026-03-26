document.addEventListener("DOMContentLoaded", function () {
  const toastContainer = document.getElementById("toast-container");

  function showToast(title, message, type = "success") {
    const toast = document.getElementById("authToast");
    const icon = document.getElementById("toast-icon");
    const titleText = document.getElementById("toast-title");
    const msgText = document.getElementById("toast-message");

    titleText.innerText = title;
    msgText.innerHTML = message;

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

  const profileForm = document.querySelector('form[method="POST"]');

  if (profileForm) {
    profileForm.addEventListener("submit", function (e) {
      const name = profileForm.querySelector('input[name="full_name"]').value.trim();
      const email = profileForm.querySelector('input[name="email"]').value.trim();

      if (!name || !email) {
        e.preventDefault();
        showToast("Missing Fields", "Please fill out all required fields.", "error");
        return;
      }

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        e.preventDefault();
        showToast("Invalid Email", "Please enter a valid email address.", "error");
        return;
      }
    });
  }

  // ✅ PROFILE SAVE FEEDBACK
  const phpSuccess = document.getElementById("php_success");
  if (phpSuccess && phpSuccess.value === "saved") {
    showToast("Profile Saved", "Your personal details have been updated.");
  }

  // 🔹 PHP ERROR
  const phpError = document.getElementById("php_error");
  if (phpError && phpError.value) {
    showToast("Missing Fields", phpError.value, "error");
  }

  // 🔥 EDIT → SAVE LOGIC (ADDED ONLY THIS PART)
  const editSaveBtn = document.getElementById("editSaveBtn");
  const hiddenSubmit = document.getElementById("hiddenSubmit");
  const fullNameInput = document.getElementById("full_name");
  const emailInput = document.getElementById("email");

  if (editSaveBtn && hiddenSubmit && fullNameInput && emailInput) {
    let isEditing = false;

    editSaveBtn.addEventListener("click", function () {
      if (!isEditing) {
        // Enable editing
        fullNameInput.removeAttribute("readonly");
        emailInput.removeAttribute("readonly");

        editSaveBtn.innerHTML = `
          <i class="fas fa-save"></i>
          <span>Save</span>
        `;

        isEditing = true;
      } else {
        // Submit form
        hiddenSubmit.click();
      }
    });
  }

  // 🔐 RESET MODAL LOGIC (UNCHANGED)
  const resetBtn = document.getElementById("resetBtn");
  const resetModal = document.getElementById("reset-modal");
  const btnCancelReset = document.getElementById("btn-cancel-reset");
  const confirmResetBtn = document.getElementById("btn-confirm-reset");

  if (resetBtn) {
    resetBtn.addEventListener("click", () => {
      document.getElementById("reset-name").value =
        document.querySelector('input[name="full_name"]').value;

      document.getElementById("reset-email").value =
        document.querySelector('input[name="email"]').value;

      resetModal.style.display = "flex";
    });
  }

  btnCancelReset.addEventListener("click", () => {
    resetModal.style.display = "none";
  });

  resetModal.addEventListener("click", (e) => {
    if (e.target === resetModal) {
      resetModal.style.display = "none";
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      resetModal.style.display = "none";
    }
  });

  // SEND RESET LINK
  if (confirmResetBtn) {
    confirmResetBtn.addEventListener("click", async () => {
      confirmResetBtn.disabled = true;
      confirmResetBtn.classList.add("loading");

      const email = document.getElementById("reset-email").value;
      const body = new URLSearchParams({
        action: "self_send_reset",
        csrf_token: window.csrfToken,
        email: email
      });

      try {
        const res = await fetch(window.location.href, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: body.toString()
        });

        const data = await res.json();

        if (data.status === "success") {
          showToast(
            "Email Sent",
            `A password reset link has been sent to <strong>${email}</strong>. Check your inbox.`,
            "success"
          );

          resetModal.style.display = "none";

          if (data.csrf_token) window.csrfToken = data.csrf_token;

        } else {
          showToast(
            "Request Failed",
            data.message || "Unable to send email. Try again later.",
            "error"
          );
        }
      } finally {
        confirmResetBtn.disabled = false;
        confirmResetBtn.classList.remove("loading");
      }
    });
  }
});