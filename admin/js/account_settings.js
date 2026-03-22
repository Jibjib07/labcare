document.addEventListener("DOMContentLoaded", function () {
  const toastContainer = document.getElementById("toast-container");

  function showToast(title, message, type = "success", customIcon = null) {
    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;

    let icon;
    if (customIcon) {
      icon = customIcon;
    } else {
      icon = type === "success" ? "fa-check" : "fa-circle-exclamation";
    }

    toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-sub">${message}</div>
            </div>
        `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = "fadeOut 0.4s forwards";
      setTimeout(() => toast.remove(), 400);
    }, 3500);
  }

  const phpSuccess = document.getElementById("php_success");

  if (phpSuccess && phpSuccess.value === "saved") {
    showToast(
      "Profile Saved",
      "Your personal details have been updated.",
      "success",
    );
  }

  const resetBtn = document.getElementById("resetBtn");

  if (resetBtn) {
    resetBtn.addEventListener("click", function () {
      resetBtn.disabled = true;

      showToast(
        "Email Sent",
        "If an account exists, a reset link has been sent.",
        "success",
        "fa-envelope",
      );

      setTimeout(() => {
        resetBtn.disabled = false;
      }, 3000);
    });
  }
});
