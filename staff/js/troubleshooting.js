document.addEventListener("DOMContentLoaded", function () {
  // Core UI Elements
  const tableBody = document.getElementById("guideTableBody");
  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("categoryFilter");
  const statusValueInput = document.getElementById("statusValue");
  const detailView = document.getElementById("detailView");
  const actionButtons = document.getElementById("actionButtons"); // Used to hide the container

  // Data State
  const categoriesListInput = document.getElementById("categoryList");
  const categories = categoriesListInput ? JSON.parse(categoriesListInput.value || "[]") : [];
  let currentGuideId = null;
  let currentGuideData = null;
  let skipAnimation = false;

  // --- UTILITIES ---

  function adjustTextareaHeight(el) {
    if (!el || el.tagName !== "TEXTAREA") return;
    el.style.height = "45px";
    el.style.height = el.scrollHeight + "px";
  }

  // --- LIST LOGIC ---

  function refreshTable(autoSelectFirst = true) {
    const search = searchInput.value;
    const category = categoryFilter.value;
    const status = statusValueInput ? statusValueInput.value : "Available";

    skipAnimation = true;

    fetch(`troubleshooting.php?ajax_filter=1&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&status=${status}`)
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
          if (actionButtons) actionButtons.style.display = "none";
        } else if (currentGuideId) {
          const activeRow = tableBody.querySelector(`.guide-item[data-id="${currentGuideId}"]`);
          if (activeRow) activeRow.classList.add("active-row");
        }
        setTimeout(() => { skipAnimation = false; }, 50);
      })
      .catch((err) => console.error("Error loading list:", err));
  }

  // --- SELECTION & RENDERING ---

  tableBody.onclick = (e) => {
    const row = e.target.closest(".guide-item");
    if (!row) return;

    // MOBILE SWAP LOGIC: Replaces list with details and scrolls to top
    if (window.innerWidth <= 768) {
      document.querySelector(".troubleshoot-layout").classList.add("show-mobile-detail");
      window.scrollTo({ top: 0, behavior: 'smooth' }); 
    }

    if (!skipAnimation) {
      const rightPanel = document.querySelector(".right-detail-panel");
      if (rightPanel) {
          rightPanel.style.animation = "none";
          void rightPanel.offsetWidth;
          rightPanel.style.animation = null;
      }
    }

    document.querySelectorAll(".guide-item").forEach((r) => r.classList.remove("active-row"));
    row.classList.add("active-row");

    currentGuideId = row.getAttribute("data-id");
    fetch(`troubleshooting.php?get_details=${currentGuideId}`)
      .then((res) => res.json())
      .then((data) => {
        currentGuideData = data;
        
        // Ensure action buttons are hidden in Staff View
        if (actionButtons) actionButtons.style.display = "none";

        renderDetails(data);
        detailView.querySelectorAll(".detail-textarea").forEach(adjustTextareaHeight);
      });
  };

  function renderDetails(data) {
    let categoryOptions = categories
      .map((cat) => `<option value="${cat}" ${cat === data.issue_catego ? "selected" : ""}>${cat}</option>`)
      .join("");

    // All inputs are explicitly set to readonly/disabled for Staff View
    detailView.innerHTML = `
            <div class="detail-group"><label>Category</label><select class="detail-select" name="issue_catego" disabled>${categoryOptions}</select></div>
            <div class="detail-group"><label>Issue Title</label><input type="text" class="detail-input" name="issue_title" value="${data.issue_title}" readonly></div>
            <div class="detail-group"><label>Summary Description</label><textarea class="detail-textarea" name="issue_summary" readonly>${data.issue_summary}</textarea></div>
            <div class="detail-group"><label>Possible Causes</label><textarea class="detail-textarea" name="issue_cause" readonly>${data.issue_cause}</textarea></div>
            <div class="detail-group"><label>Step-by-Step Solution</label><textarea class="detail-textarea" name="issue_solutio" readonly>${data.issue_solutio}</textarea></div>
            <div class="detail-group"><label>Preventive Measures</label><textarea class="detail-textarea" name="issue_preven" readonly>${data.issue_preven || ""}</textarea></div>`;
  }

  // --- EVENT LISTENERS ---
  // (Removed status button toggle click listeners)

  searchInput.oninput = () => refreshTable(true);
  categoryFilter.onchange = () => refreshTable(true);

  // Initial load
  refreshTable(true);
});

// --- Mobile Navigation Function ---
function closeMobileDetails() {
  const layout = document.querySelector(".troubleshoot-layout");
  if (layout) {
    layout.classList.remove("show-mobile-detail");
    // Reset the scroll position
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}