function saveCurrentState() {
  const isFacilityView =
    document.getElementById("view-facility").style.display === "block";
  const state = {
    view: isFacilityView ? "facility" : "computer",
    id: isFacilityView ? currentSelectedFAId : currentEditingSetId,
  };
  localStorage.setItem("assets_state", JSON.stringify(state));
}

let currentEditingSetId = null; // For Computer Units
let currentSelectedFAId = null; // For Facility Assets
let isEditModeActive = false;

// Check if we were redirected from the Archive Blocked modal
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get("auto_open") === "transfer") {
  const labId = urlParams.get("lab_id");
  if (labId) {
    // Automatically open the transfer modal!
    openModal("transferModal", labId);

    // Clean up the URL bar so it doesn't re-open if they refresh
    window.history.replaceState(
      {},
      document.title,
      `assets_management.php?lab_id=${labId}`,
    );
  }
}
// ==========================================
// TOAST NOTIFICATION SYSTEM
// ==========================================

document.addEventListener("DOMContentLoaded", () => {
  const isMobile = window.innerWidth <= 768;
  const savedState = JSON.parse(localStorage.getItem("assets_state"));

  if (savedState) {
    const computerView = document.getElementById("view-computer");
    const facilityView = document.getElementById("view-facility");

    if (savedState.view === "computer") {
      computerView.style.display = "block";
      facilityView.style.display = "none";

      const item = document.querySelector(
        `.asset-item[data-set-id="${savedState.id}"]`,
      );

      // Only auto-select if NOT on mobile, OR if we specifically want to restore a deep-link
      if (item && !isMobile) {
        selectUnit(item, savedState.id);
      } else if (item) {
        // On mobile, just highlight it in the list but don't open the details
        item.classList.add("active");
      }
    } else {
      computerView.style.display = "none";
      facilityView.style.display = "block";

      const item = document.querySelector(
        `.asset-item[data-asset-id="${savedState.id}"]`,
      );

      if (item && !isMobile) {
        selectFacilityAsset(item, savedState.id);
      } else if (item) {
        item.classList.add("active");
      }
    }
    localStorage.removeItem("assets_state");
  } else {
    // 2. Default startup (No memory found)
    if (isMobile) {
      // On mobile, show the computer list container but DON'T call switchView
      // because switchView contains auto-select logic.
      document.getElementById("view-computer").style.display = "block";
      document.getElementById("view-facility").style.display = "none";
    } else {
      switchView("computer");
    }
  }

  // 3. Toast logic
  const pendingToast = sessionStorage.getItem("pendingToast");
  if (pendingToast) {
    const toastData = JSON.parse(pendingToast);
    showNotification(toastData.title, toastData.message, toastData.type);
    sessionStorage.removeItem("pendingToast");
  }

  // 4. Re-attach click listeners
  attachAssetClickListeners();
});

// Helper to keep the DOMContentLoaded clean
// Helper to keep the DOMContentLoaded clean
function attachAssetClickListeners() {
  // 1. PC List Clicks
  document
    .querySelectorAll("#assetListContainer .asset-item:not(.missing-id)")
    .forEach((item) => {
      item.addEventListener("click", function () {
        // --- NEW: Block selection if editing ---
        if (isEditModeActive) {
          showNotification(
            "Action Blocked",
            "Please save or cancel your current edits before selecting another item.",
            "error",
          );
          return;
        }
        // ---------------------------------------
        const setId = this.getAttribute("data-set-id");
        if (setId) selectUnit(this, setId);
      });
    });

  // 2. FA List Clicks
  document
    .querySelectorAll("#facilityListContainer .asset-item")
    .forEach((item) => {
      item.addEventListener("click", function () {
        // --- NEW: Block selection if editing ---
        if (isEditModeActive) {
          showNotification(
            "Action Blocked",
            "Please save or cancel your current edits before selecting another item.",
            "error",
          );
          return;
        }
        // ---------------------------------------
        const assetId = this.getAttribute("data-asset-id");
        if (assetId) selectFacilityAsset(this, assetId);
      });
    });
}

function showNotification(title, message, type = "success") {
  const container = document.getElementById("toast-container");
  if (!container) return;

  const typeClass = type === "danger" || type === "error" ? "error" : "success";
  const toast = document.createElement("div");
  toast.className = `toast toast-${typeClass}`;

  const iconClass =
    typeClass === "success" ? "fa-check-circle" : "fa-exclamation-circle";

  toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${iconClass}"></i></div>
        <div class="toast-content">
            <h4>${title}</h4>
            <p>${message}</p>
        </div>
    `;

  container.appendChild(toast);

  // After 3.5 seconds, start the fade-out animation
  setTimeout(() => {
    toast.classList.add("fade-out");

    // Use animationend because we are using @keyframes fadeOut
    toast.addEventListener(
      "animationend",
      (e) => {
        if (e.animationName === "fadeOut") {
          toast.remove();
        }
      },
      { once: true },
    );

    // Backup removal
    setTimeout(() => {
      if (toast.parentNode) toast.remove();
    }, 600);
  }, 3500);
}

function reloadWithToast(title, message, type = "success") {
  // If you have a saveCurrentState function, keep it here
  if (typeof saveCurrentState === "function") saveCurrentState();

  sessionStorage.setItem(
    "pendingToast",
    JSON.stringify({ title, message, type }),
  );
  location.reload();
}

// --- 3. ON PAGE LOAD: CHECK FOR PENDING TOASTS ---
window.addEventListener("load", () => {
  const pending = sessionStorage.getItem("pendingToast");
  if (pending) {
    const data = JSON.parse(pending);
    showNotification(data.title, data.message, data.type);
    sessionStorage.removeItem("pendingToast");
  }
});

// SINGLE, UNIFIED CLICK LISTENER
window.addEventListener("click", function (event) {
  // 1. Close Modals
  const modals = [
    "addComputerModal",
    "condemnModal",
    "transferModal",
    "missingIdModal",
    "addFacilityAssetModal",
    "logStatusModal", // <-- ADDED HERE!
  ];

  modals.forEach((id) => {
    const modal = document.getElementById(id);
    // Added "modal &&" safety check so it doesn't crash if one is missing
    if (modal && event.target === modal) {
      modal.style.display = "none";
    }
  });

  // 2. Close Filter Dropdown
  if (
    !event.target.matches(".filter-btn") &&
    !event.target.closest(".filter-btn")
  ) {
    const filterMenu = document.getElementById("filterMenu");
    if (filterMenu && filterMenu.classList.contains("show")) {
      filterMenu.classList.remove("show");
    }
  }
});
/**
 * ------------------------------------------------------------------
 * 1. MAIN VIEW SWITCHER
 * Toggles between "Computer Unit" and "Facility Assets" sections.
 * ------------------------------------------------------------------
 */
function switchView(viewName) {
  forceCloseEditMode();
  const computerView = document.getElementById("view-computer");
  const facilityView = document.getElementById("view-facility");

  // NEW: Check if screen is mobile
  const isMobile = window.innerWidth <= 768;

  currentEditingSetId = null;
  currentSelectedFAId = null;

  document
    .querySelectorAll(".asset-item")
    .forEach((item) => item.classList.remove("active"));

  if (viewName === "computer") {
    computerView.style.display = "block";
    facilityView.style.display = "none";

    // UPDATED: Only auto-select if NOT on mobile
    if (!isMobile) {
      const firstUnit = document.querySelector(
        "#assetListContainer .asset-item:not([style*='display: none'])",
      );
      if (firstUnit) {
        const setId = firstUnit.getAttribute("data-set-id");
        selectUnit(firstUnit, setId);
      }
    }
  } else if (viewName === "facility") {
    computerView.style.display = "none";
    facilityView.style.display = "block";

    // UPDATED: Only auto-select if NOT on mobile
    if (!isMobile) {
      const firstAsset = document.querySelector(
        "#facilityListContainer .asset-item:not([style*='display: none'])",
      );
      if (firstAsset) {
        const assetId = firstAsset.getAttribute("data-asset-id");
        selectFacilityAsset(firstAsset, assetId);
      }
    }
  }
}

/**
 * ------------------------------------------------------------------
 * 2. SPECIFICATION TABS SWITCHER (Details Panel)
 * Switches between Identity, External Ports, Health, and Peripherals.
 * ------------------------------------------------------------------
 * @param {string} tabId - The unique part of the ID (e.g., 'identity', 'external')
 * @param {HTMLElement} btnElement - The button that was clicked (to set active state)
 */
function switchTab(tabId, btnElement) {
  // 1. Hide all tab content divs inside the specs box
  const contents = document.querySelectorAll(".specs-content-box .tab-content");
  contents.forEach((content) => {
    content.style.display = "none";
  });

  // 2. Show the specific tab content requested
  const selectedContent = document.getElementById("tab-" + tabId);
  if (selectedContent) {
    selectedContent.style.display = "block";
  } else {
    console.error("Tab content not found: tab-" + tabId);
  }

  // 3. Remove 'active' class from all tab buttons
  const buttons = document.querySelectorAll(".spec-tab");
  buttons.forEach((btn) => {
    btn.classList.remove("active");
  });

  // 4. Add 'active' class to the clicked button
  if (btnElement) {
    btnElement.classList.add("active");
  }
}

/**
 * ------------------------------------------------------------------
 * 3. MODAL POPUP LOGIC (Add New Computer)
 * Handles opening, closing, and clicking outside to close.
 * ------------------------------------------------------------------
 */

// Open the Modal
function openModal(modalId, ...args) {
  // --- FIXED: Do NOT cancel edit mode if they are opening the Save/Log modal! ---
  if (modalId !== "logStatusModal") {
    forceCloseEditMode();
  }
  // -----------------------------------------------------------------------------

  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "flex";

    if (modalId === "transferModal") {
      const labId = args[0];
      // Clear previous inputs/checkboxes before populating
      resetTransferModal();
      populateTransferModal(labId);
    }
  }
}

async function populateTransferModal(labId) {
  // 1. Reference all UI elements
  const unitBody = document.getElementById("transferUnitsTableBody");
  const assetBody = document.getElementById("transferAssetsTableBody");
  const targetLabSelect = document.getElementById("transfer_target_lab");
  const sourceInput = document.getElementById("transfer_source_room");

  // 2. Clear current state and show loading
  unitBody.innerHTML =
    '<tr><td colspan="3" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading Units...</td></tr>';
  assetBody.innerHTML =
    '<tr><td colspan="3" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading Assets...</td></tr>';
  targetLabSelect.innerHTML = '<option value="">Loading Labs...</option>';

  try {
    // 3. Fetch data from the PHP provider
    const response = await fetch(
      `includes/get_assets_for_transfer.php?lab_id=${labId}`,
    );

    if (!response.ok) throw new Error("Network response was not ok");

    const data = await response.json();

    // 4. Set the Source Room Name (e.g., "Computer Lab 1 (Room 104)")
    sourceInput.value = data.source_name || "Unknown Laboratory";

    // 5. Populate Computer Unit List
    if (data.units && data.units.length > 0) {
      unitBody.innerHTML = data.units
        .map(
          (unit) => `
                <tr>
                    <td>
                        <label class="check-container">
                            <input type="checkbox" class="transfer-unit-checkbox" value="${unit.set_id}"> 
                            <span>PC-${unit.set_tag}</span>
                        </label>
                    </td>
                    <td>${unit.set_id}</td>
                    <td><span class="badge ${unit.set_status.toLowerCase() === "working" ? "green" : "yellow"}">${unit.set_status}</span></td>
                </tr>
            `,
        )
        .join("");
    } else {
      unitBody.innerHTML =
        '<tr><td colspan="3" style="text-align:center; color: #888;">No transferable units in this lab.</td></tr>';
    }

    // 6. Populate Facility Asset List
    if (data.facility && data.facility.length > 0) {
      assetBody.innerHTML = data.facility
        .map(
          (asset) => `
                <tr>
                    <td>
                        <label class="check-container">
                            <input type="checkbox" class="transfer-asset-checkbox" value="${asset.asset_id}"> 
                            <span>FA-${asset.asset_tag}</span>
                        </label>
                    </td>
                    <td>${asset.asset_id}</td>
                    <td><span class="badge ${asset.asset_status.toLowerCase() === "working" ? "green" : "yellow"}">${asset.asset_status}</span></td>
                </tr>
            `,
        )
        .join("");
    } else {
      assetBody.innerHTML =
        '<tr><td colspan="3" style="text-align:center; color: #888;">No facility assets found.</td></tr>';
    }

    // 7. Populate Target Lab Dropdown (Name (Room) formatting)
    targetLabSelect.innerHTML = '<option value="">Select Target Lab</option>';
    if (data.labs && data.labs.length > 0) {
      data.labs.forEach((lab) => {
        const option = document.createElement("option");
        option.value = lab.lab_id;
        option.textContent = lab.full_display; // Formatted as "Name (Room)" from PHP
        targetLabSelect.appendChild(option);
      });
    } else {
      targetLabSelect.innerHTML =
        '<option value="">No available labs for transfer</option>';
    }
  } catch (error) {
    console.error("Transfer Modal Error:", error);
    const errorRow =
      '<tr><td colspan="3" style="text-align:center; color: red;">Failed to load data.</td></tr>';
    unitBody.innerHTML = errorRow;
    assetBody.innerHTML = errorRow;
    targetLabSelect.innerHTML = '<option value="">Error loading labs</option>';
  }
}

// Utility to clear modal state
function resetTransferModal() {
  document
    .querySelectorAll('#transferModal input[type="checkbox"]')
    .forEach((cb) => (cb.checked = false));
  document
    .querySelectorAll("#transferModal .modal-textarea")
    .forEach((ta) => (ta.value = ""));
  const select = document.querySelector("#transferModal .custom-select");
  if (select) select.selectedIndex = 0;
}

function filterTransferList(tbodyId, query) {
  const rows = document.querySelectorAll(`#${tbodyId} tr`);
  const q = query.toLowerCase().trim();

  rows.forEach((row) => {
    if (row.cells.length < 2) return;

    // 2. Perform the search
    const text = row.innerText.toLowerCase();
    const isMatch = text.includes(q);
    row.style.display = isMatch ? "" : "none";

    if (!isMatch) {
      const cb = row.querySelector('input[type="checkbox"]');
      if (cb) cb.checked = false;
    }
  });

  const selectAllId =
    tbodyId === "transferUnitsTableBody" ? "selectAllUnits" : "selectAllAssets";
  const selectAllCb = document.getElementById(selectAllId);
  if (selectAllCb) selectAllCb.checked = false;
}

function toggleTransferSelection(type) {
  const isChecked =
    type === "unit"
      ? document.getElementById("selectAllUnits").checked
      : document.getElementById("selectAllAssets").checked;

  const selector =
    type === "unit" ? ".transfer-unit-checkbox" : ".transfer-asset-checkbox";

  document.querySelectorAll(selector).forEach((cb) => {
    // Only toggle checkboxes that aren't hidden by a search filter
    if (cb.closest("tr").style.display !== "none") {
      cb.checked = isChecked;
    }
  });
}

async function submitTransfer() {
  const targetLabId = document.getElementById("transfer_target_lab").value;
  const remarks = document.getElementById("transfer_remarks").value;

  // 1. Collect selected Units
  const selectedUnits = Array.from(
    document.querySelectorAll(".transfer-unit-checkbox:checked"),
  ).map((cb) => cb.value);

  // 2. Collect selected Assets
  const selectedAssets = Array.from(
    document.querySelectorAll(".transfer-asset-checkbox:checked"),
  ).map((cb) => cb.value);

  // 3. Collect checked reasons (Changed selector to match standard naming)
  // Ensure your HTML container has id="transfer_actions"
  const actions = Array.from(
    document.querySelectorAll(
      '#transfer_actions input[type="checkbox"]:checked',
    ),
  ).map((cb) => cb.value);

  // Validation
  if (!targetLabId) {
    showNotification(
      "Required",
      "Please select a destination laboratory.",
      "error",
    );
    return;
  }
  if (selectedUnits.length === 0 && selectedAssets.length === 0) {
    showNotification(
      "Selection Empty",
      "Please select at least one item to transfer.",
      "error",
    );
    return;
  }

  // Prepare Data - KEYS UPDATED TO MATCH PHP
  const formData = new FormData();
  formData.append("target_lab_id", targetLabId);
  formData.append("remarks", remarks);
  formData.append("actions", JSON.stringify(actions)); // Changed 'reasons' to 'actions'
  formData.append("units", JSON.stringify(selectedUnits));
  formData.append("assets", JSON.stringify(selectedAssets));

  try {
    const response = await fetch("includes/process_transfer.php", {
      method: "POST",
      body: formData,
    });

    // Safety check for non-JSON responses (like PHP errors)
    const text = await response.text();
    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error("Server returned non-JSON:", text);
      throw new Error("Server error. Check console.");
    }

    if (result.success) {
      closeModal("transferModal");
      if (typeof reloadWithToast === "function") {
        reloadWithToast(
          "Success",
          "Assets transferred and audit trail updated.",
          "success",
        );
      } else {
        location.reload();
      }
    } else {
      // Use result.error to match the PHP's catch block
      showNotification("Error", result.error || "Transfer failed.", "error");
    }
  } catch (error) {
    console.error("Transfer Error:", error);
    showNotification(
      "Server Error",
      error.message || "Could not connect to the server.",
      "error",
    );
  }
}

/**
 * Switch Modal Tabs
 */
function switchModalTab(tabId, btnElement) {
  // Hide all modal tab contents
  const contents = document.querySelectorAll(".modal-tab-content");
  contents.forEach((content) => {
    content.style.display = "none";
  });

  // Show selected
  const selectedContent = document.getElementById(tabId);
  if (selectedContent) selectedContent.style.display = "block";

  // Update active button state inside the modal nav
  const buttons = document.querySelectorAll(".modal-tabs-nav .spec-tab");
  buttons.forEach((btn) => btn.classList.remove("active"));
  if (btnElement) btnElement.classList.add("active");
}
// Close the Modal
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "none";
  }
}

/**
 * ENABLE/DISABLE TAB BUTTONS
 */
function setTabsDisabled(disabled) {
  const tabs = document.querySelectorAll(".specs-content-box .spec-tab");
  tabs.forEach((tab, index) => {
    // Keep first tab (Identity) always enabled, disable others
    if (index > 0) {
      tab.disabled = disabled;
    }
  });
}

/**
 * TOGGLE STATUS BUTTONS
 */
function toggleStatus(clickedBtn) {
  // 1. Find the container (div) holding this pair of buttons
  const group = clickedBtn.parentElement;

  // 2. Find ALL buttons inside this specific container
  const buttons = group.querySelectorAll(".status-btn");

  // 3. Turn OFF 'active' class for all buttons in this group
  buttons.forEach((btn) => {
    btn.classList.remove("active");
  });

  // 4. Turn ON 'active' class ONLY for the clicked button
  clickedBtn.classList.add("active");
}

// ==========================================
// A. COMPUTER UNITS SEARCH & FILTER
// ==========================================
let currentComputerFilter = "All";

function toggleFilterMenu() {
  document.getElementById("filterMenu").classList.toggle("show");
}

function filterAssets(status) {
  currentComputerFilter = status;
  document.getElementById("filterMenu").classList.remove("show");
  applyComputerFilters();
}

function searchAssets() {
  applyComputerFilters();
}

function applyComputerFilters() {
  const input = document.getElementById("searchInput");
  if (!input) return;
  const filterText = input.value.toLowerCase();

  // ONLY target computer units
  const items = document.querySelectorAll("#assetListContainer .asset-item");

  items.forEach((item) => {
    const nameEl = item.querySelector(".item-name");
    const badgeEl = item.querySelector(".badge");
    if (!nameEl || !badgeEl) return;

    const name = nameEl.textContent.toLowerCase();
    const physicalStatus = badgeEl.textContent.trim();

    // Grab our new hidden data attribute!
    const isForCondemn = item.getAttribute("data-is-condemn") === "true";

    const matchesSearch = name.includes(filterText);

    // --- NEW: ADVANCED FILTER LOGIC ---
    let matchesFilter = false;

    if (currentComputerFilter === "All") {
      matchesFilter = true;
    } else if (currentComputerFilter === "For Condemn") {
      // Check the hidden flag instead of the visual text
      matchesFilter = isForCondemn;
    } else {
      // For 'Working', 'For Repair', and 'No Property ID'
      matchesFilter = physicalStatus === currentComputerFilter;
    }
    // ----------------------------------

    item.style.display = matchesSearch && matchesFilter ? "flex" : "none";
  });
}

// ==========================================
// B. FACILITY ASSETS SEARCH & FILTER
// ==========================================
let currentFAFilter = "All";

function toggleFAFilterMenu() {
  const menu = document.getElementById("faFilterMenu");
  if (menu) menu.classList.toggle("show");
}

function filterFAAssets(status) {
  currentFAFilter = status;
  const menu = document.getElementById("faFilterMenu");
  if (menu) menu.classList.remove("show");
  applyFAFilters();
}

function searchFAAssets() {
  applyFAFilters();
}

function applyFAFilters() {
  // Uses a UNIQUE ID for the Facility search bar
  const input = document.getElementById("faSearchInput");
  if (!input) return;
  const filterText = input.value.toLowerCase();

  // ONLY target facility assets container
  const items = document.querySelectorAll("#facilityListContainer .asset-item");

  items.forEach((item) => {
    const nameEl = item.querySelector(".item-name");
    const badgeEl = item.querySelector(".badge");
    if (!nameEl || !badgeEl) return;

    const name = nameEl.textContent.toLowerCase();
    const status = badgeEl.textContent.trim();

    const matchesSearch = name.includes(filterText);
    const matchesFilter =
      currentFAFilter === "All" || status === currentFAFilter;

    item.style.display = matchesSearch && matchesFilter ? "flex" : "none";
  });
}

/**
 * ------------------------------------------------------------------
 * 7. UNIVERSAL EDIT MODE TOGGLE
 * ------------------------------------------------------------------
 */

/**
 * Detects the first available unit number by scanning the existing list.
 */
function calculateNextUnitNumber() {
  const unitElements = document.querySelectorAll(
    "#assetListContainer .item-name",
  );
  let existingNumbers = [];

  unitElements.forEach((el) => {
    const text = el.innerText;
    // Only target actual PC units
    if (text.includes("PC-")) {
      const match = text.match(/\d+/);
      if (match) existingNumbers.push(parseInt(match[0], 10));
    }
  });

  existingNumbers.sort((a, b) => a - b);
  availableNumbersList = [];

  // Build the next 50 available gaps/numbers
  for (let i = 1; i <= 200; i++) {
    if (!existingNumbers.includes(i)) {
      availableNumbersList.push(i.toString().padStart(2, "0"));
    }
    if (availableNumbersList.length >= 50) break;
  }

  // Always trigger the UI update immediately after calculating
  updateBulkUnitNumbers();
}

/**
 * Calculates the computer age based on the purchase date in the add modal.
 */
function calculateComputerAge() {
  const dateInput = document.getElementById("purchase_date_input");
  const displayInput = document.getElementById("computer_age_display");

  if (!dateInput.value) {
    displayInput.value = "";
    return;
  }

  let purchaseDate = new Date(dateInput.value);
  const today = new Date();

  // Strip the time portion so we are purely comparing the calendar days
  today.setHours(0, 0, 0, 0);
  purchaseDate.setHours(0, 0, 0, 0);

  // FIX: If the user selects a date in the future, automatically snap it back to TODAY
  if (purchaseDate > today) {
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const dd = String(today.getDate()).padStart(2, "0");

    dateInput.value = `${yyyy}-${mm}-${dd}`;
    purchaseDate = today; // Reset our calculation variable to today
  }

  // Calculate the difference in years
  let years = today.getFullYear() - purchaseDate.getFullYear();
  let months = today.getMonth() - purchaseDate.getMonth();

  // Adjust down by 1 year if the exact purchase month/day hasn't happened yet this year
  if (
    months < 0 ||
    (months === 0 && today.getDate() < purchaseDate.getDate())
  ) {
    years--;
  }

  // FIX: Fallback to ensure it never goes below 0 (Less than a year = 0)
  if (years < 0) {
    years = 0;
  }

  // Output ONLY the year integer (e.g., "0", "1", "5")
  displayInput.value = years;
}

function getActiveToggle(groupId) {
  const activeBtn = document.querySelector(`#${groupId} .status-btn.active`);
  if (activeBtn) {
    // Maps the HTML 'data-type' directly to your Database exact wording
    return activeBtn.getAttribute("data-type") === "repair"
      ? "For Repair"
      : "Working";
  }
  return "Working"; // Fallback just in case
}

function submitNewUnit() {
  const labRoom = document.getElementById("room_number_input").value;
  const urlParams = new URLSearchParams(window.location.search);
  const labId = urlParams.get("lab_id") || 0;

  // Determine tags to send
  let unitTags = [];
  if (isBulkMode) {
    const count = parseInt(document.getElementById("bulk_count").value) || 2;
    unitTags = availableNumbersList.slice(0, count);
  } else {
    unitTags = [document.getElementById("smart_unit_no").value];
  }

  if (unitTags.length === 0) return;

  // ... [Keep your status toggle collection] ...
  const statusButtons = document.querySelectorAll(".status-btn.active");
  let statusArray = [];
  statusButtons.forEach((btn) =>
    statusArray.push(btn.getAttribute("data-type")),
  );

  const formData = new FormData();
  // Send the array of tags as a JSON string
  formData.append("unit_tags", JSON.stringify(unitTags));
  formData.append("lab_id", labId);
  formData.append("lab_room", labRoom);
  statusArray.forEach((status) => formData.append("statuses[]", status));

  // --- NEW: APPEND SPECS DATA ---
  formData.append(
    "property_id",
    document.getElementById("spec_property").value,
  );
  formData.append("cpu", document.getElementById("spec_cpu").value);
  formData.append("brand", document.getElementById("spec_brand").value);
  formData.append("os", document.getElementById("spec_os").value);
  formData.append(
    "purchase_date",
    document.getElementById("purchase_date_input").value,
  );
  formData.append("gpu", document.getElementById("spec_gpu").value);
  formData.append("ram", document.getElementById("spec_ram").value);
  formData.append("storage", document.getElementById("spec_storage").value);
  formData.append("capacity", document.getElementById("spec_capacity").value);

  // --- NEW: APPEND PORTS DATA ---
  formData.append(
    "usb_ports",
    document.getElementById("usb_ports_count").value,
  );
  formData.append("usb_status", getActiveToggle("usb_toggle"));
  formData.append("wifi_status", getActiveToggle("wifi_toggle"));
  formData.append("mic_status", getActiveToggle("mic_toggle"));
  formData.append("hdmi_status", getActiveToggle("hdmi_toggle"));
  formData.append("headphone_status", getActiveToggle("headphone_toggle"));
  formData.append("display_status", getActiveToggle("display_toggle"));
  formData.append("inline_status", getActiveToggle("inline_toggle"));
  formData.append("ethernet_status", getActiveToggle("ethernet_toggle"));

  // --- NEW: APPEND HEALTH DATA ---
  let rawAgeStr = document.getElementById("computer_age_display").value;
  let comAge = parseInt(rawAgeStr);
  if (isNaN(comAge)) comAge = 0;

  formData.append("com_age", comAge);
  formData.append("disk_health", getActiveToggle("disk_toggle"));
  formData.append(
    "num_repair",
    document.getElementById("num_repair_input").value || 0,
  );
  formData.append("power_health", getActiveToggle("power_toggle"));

  // --- NEW: APPEND PERIPHERALS DATA ---
  formData.append(
    "monitor_property",
    document.getElementById("monitor_property_input").value,
  );
  formData.append(
    "monitor_brand",
    document.getElementById("monitor_brand_input").value,
  );
  formData.append("monitor_status", getActiveToggle("monitor_toggle"));

  formData.append(
    "mouse_brand",
    document.getElementById("mouse_brand_input").value,
  );
  formData.append("mouse_status", getActiveToggle("mouse_toggle"));

  formData.append(
    "keyboard_brand",
    document.getElementById("keyboard_brand_input").value,
  );
  formData.append("keyboard_status", getActiveToggle("keyboard_toggle"));

  formData.append(
    "avr_brand",
    document.getElementById("avr_brand_input").value,
  );
  formData.append("avr_status", getActiveToggle("avr_toggle"));

  fetch("includes/insert_unit.php", { method: "POST", body: formData })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Close modal, save toast to memory, and reload!
        closeModal("addComputerModal");
        reloadWithToast(
          "Units Added",
          "New units were added successfully.",
          "success",
        );
      } else {
        showNotification("Database Error", data.error, "error");
      }
    })
    .catch((error) =>
      showNotification("Connection Error", "Failed to save units.", "error"),
    );
}

let isBulkMode = false;
let availableNumbersList = []; // Stores the next available sequence

function toggleAddMode(mode) {
  const circleSingle = document.getElementById("circle_single");
  const circleMultiple = document.getElementById("circle_multiple");
  const bulkContainer = document.getElementById("bulk_input_container");
  const propIdInput = document.getElementById("spec_property");

  if (mode === "single") {
    isBulkMode = false;
    circleSingle.classList.add("checked");
    circleMultiple.classList.remove("checked");
    bulkContainer.style.display = "none";

    propIdInput.disabled = false;
    propIdInput.style.background = "#fff";
  } else {
    isBulkMode = true;
    circleMultiple.classList.add("checked");
    circleSingle.classList.remove("checked");
    bulkContainer.style.display = "flex";

    propIdInput.value = "";
    propIdInput.disabled = true;
    propIdInput.style.background = "#f4f4f4";
  }

  // Crucial fix: Always recalculate and update the screen when switching modes
  calculateNextUnitNumber();
}

function updateBulkUnitNumbers() {
  const inputField = document.getElementById("smart_unit_no");

  if (!isBulkMode) {
    // Single Mode: Just show the first available number
    inputField.value = availableNumbersList[0] || "";
  } else {
    // Bulk Mode: Read the quantity input and show the sequence
    let count = parseInt(document.getElementById("bulk_count").value, 10);

    // Safety net if the user deletes the number
    if (isNaN(count) || count < 1) {
      count = 1;
    }

    const previewNumbers = availableNumbersList.slice(0, count);
    inputField.value = previewNumbers.join(", ");
  }
}

// ==========================================
// 5. RIGHT PANEL: VIEW & EDIT DETAILS (AJAX)
// ==========================================

function selectUnit(element, setId) {
  if (isEditModeActive) {
    showNotification(
      "Action Blocked",
      "Please save or cancel your current edits before selecting another item.",
      "error",
    );
    return;
  }
  currentEditingSetId = setId;

  document
    .querySelectorAll("#assetListContainer .asset-item")
    .forEach((item) => item.classList.remove("active"));
  if (element) element.classList.add("active");

  const unitName = element
    ? element.querySelector(".item-name").innerText
    : "Unit";
  document.querySelector(".right-panel .section-header-row h3").innerHTML =
    `${unitName} Details`;

  setTabsDisabled(false);

  fetch(`includes/get_unit_details.php?set_id=${setId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        populateRightPanel(data.data);
        resetUIToViewMode();
        const historyBody = document.getElementById("pc_activity_log_body");
        if (historyBody) {
          historyBody.innerHTML = ""; // Clear out the loading/empty text

          if (data.history && data.history.length > 0) {
            data.history.forEach((log, index) => {
              let badgeColor = "gray";
              if (
                log.report_status === "Resolved" ||
                log.report_status === "Working"
              )
                badgeColor = "green";
              if (
                log.report_status === "Reported" ||
                log.report_status === "For Repair"
              )
                badgeColor = "yellow";
              if (log.report_status === "Condemned") badgeColor = "red";

              const item = document.createElement("div");
              item.style.padding = "15px 20px";
              item.style.backgroundColor = "#fff";

              // Add a divider line between logs, but not after the very last one
              if (index < data.history.length - 1) {
                item.style.borderBottom = "1px solid #eaeaea";
              }

              item.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <div style="font-size: 13px; color: #555;">
                                    <strong style="color: #1b4d3e; font-size: 14px;"><i class="fas fa-user-circle"></i> ${log.report_actor || "System"}</strong> 
                                    <span style="margin-left: 8px; color: #888;"><i class="far fa-clock"></i> ${log.formatted_date}</span>
                                </div>
                                <span class="badge ${badgeColor}">${log.report_status || "Logged"}</span>
                            </div>
                            <div style="font-size: 13px; color: #333;">
                                <div style="margin-bottom: 8px;"><strong>Affected:</strong> <span style="color: #d32f2f; font-weight: 500;">${log.report_affected || "N/A"}</span></div>
                                <div style="background: #f4f6f8; padding: 10px 12px; border-radius: 6px; color: #555; border-left: 3px solid #ccc;">
                                    <em>"${log.report_remarks || "No remarks provided"}"</em>
                                </div>
                            </div>
                        `;
              historyBody.appendChild(item);
            });
          } else {
            historyBody.innerHTML =
              '<div style="text-align:center; color:#888; padding: 25px;">No recent maintenance activity found.</div>';
          }
        }
        // --- DYNAMIC RESOLVE BUTTON LOGIC (MOVED INSIDE FETCH) ---
        const btnResolve = document.getElementById("btnResolve");
        if (btnResolve) {
          const d = data.data;
          // Scanner: If ANY part is broken, light up the Resolve button!
          const isBroken =
            d.set_status === "For Repair" ||
            d.disk_health === "Poor" ||
            d.disk_health === "For Repair" ||
            d.power_health === "For Repair" ||
            d.usb_status === "For Repair" ||
            d.wifi_status === "For Repair" ||
            d.mic_status === "For Repair" ||
            d.hdmi_status === "For Repair" ||
            d.headphone_status === "For Repair" ||
            d.display_status === "For Repair" ||
            d.inline_status === "For Repair" ||
            d.ethernet_status === "For Repair" ||
            d.monitor_status === "For Repair" ||
            d.mouse_status === "For Repair" ||
            d.keyboard_status === "For Repair" ||
            d.avr_status === "For Repair";

          if (isBroken) {
            // Make button active and green
            btnResolve.className = "btn-confirm";
            btnResolve.style.marginLeft = "8px";
            btnResolve.onclick = () => openResolveModal("pc");
          } else {
            // Make button greyed out
            btnResolve.className = "btn-resolve hide-on-mobile";
            btnResolve.style.marginLeft = "0";
            btnResolve.onclick = null;
          }
        }
        // --------------------------------------------------------
      } else {
        console.error("Error fetching details:", data.error);
      }
    })
    .catch((error) => console.error("Fetch Error:", error));

  if (window.innerWidth <= 768) {
    document
      .getElementById("view-computer")
      .querySelector(".split-layout")
      .classList.add("mobile-show-details");
  }
}

function populateRightPanel(data) {
  for (const [key, value] of Object.entries(data)) {
    // 1. Text & Inputs (view_specs_cpu / edit_specs_cpu)
    const viewEl = document.getElementById("view_" + key);
    const editEl = document.getElementById("edit_" + key);

    if (viewEl) {
      if (key === "com_age") {
        const ageVal = parseInt(value) || 0;
        viewEl.innerText = value !== null ? ageVal + " Years" : "0 Years";

        // --- INJECT FOR CONDEMN PILL STRICTLY IN HEALTH TAB ---
        const condemnBadge = document.getElementById("view_condemn_badge");
        if (condemnBadge) {
          if (ageVal >= 5) {
            condemnBadge.innerHTML =
              '<span class="badge red">For Condemn</span>';
          } else {
            condemnBadge.innerHTML = ""; // Hide if less than 5 years old
          }
        }
        // -----------------------------------------------------------
      } else {
        viewEl.innerText = value || "N/A";
      }
    }
    if (editEl) editEl.value = value || "";

    // 2. Status Pills & Toggles (pill_usb_status / toggle_usb_status)
    const toggleGroup = document.getElementById("toggle_" + key);
    if (toggleGroup) {
      const pill = document.getElementById("pill_" + key);

      if (pill) {
        // --- TRANSLATOR STRICTLY FOR DISK HEALTH ---
        let displayValue = value || "Unknown";

        if (key === "disk_health") {
          // Treat both "Working" and "Healthy" as Healthy
          if (value === "Working" || value === "Healthy")
            displayValue = "Healthy";
          // Treat both "For Repair" and "Poor" as Poor
          if (value === "For Repair" || value === "Poor") displayValue = "Poor";
        }

        pill.innerText = displayValue;
        // -------------------------------------------

        // Colors cleanly evaluate the raw database value
        let pillColor = "purple";
        if (value === "Working" || value === "Healthy") pillColor = "green";
        if (value === "For Condemn") pillColor = "red";
        if (value === "For Repair" || value === "Poor") pillColor = "orange";

        pill.className = `status-pill view-mode ${pillColor}`;
      }

      // Sync the underlying toggle buttons (Even if hidden in Admin Edit mode, Staff/Logic needs this accurate)
      toggleGroup.querySelectorAll(".status-btn").forEach((btn) => {
        btn.classList.remove("active");

        // Safely check if the database value implies a "repair/broken" state
        const targetType =
          value === "For Repair" || value === "Poor" ? "repair" : "working";

        if (btn.getAttribute("data-type") === targetType) {
          btn.classList.add("active");
        }
      });
    }
  }
}
function toggleEditMode() {
  // 1. Detect which view is currently visible
  const viewFacility = document.getElementById("view-facility");
  const isFacilityView = viewFacility && viewFacility.offsetParent !== null;

  // 2. Determine which ID and Button to validate
  const activeId = isFacilityView ? currentSelectedFAId : currentEditingSetId;
  const itemType = isFacilityView ? "asset" : "unit";

  if (!activeId) {
    alert(`Please select a ${itemType} from the list first.`);
    return;
  }

  // 3. FACILITY ASSET LOGIC
  if (isFacilityView) {
    toggleFAEditMode();
    return;
  }

  // 4. COMPUTER UNIT LOGIC
  const btn = document.getElementById("editToggleButton");
  const textSpan = document.getElementById("editText");
  const btnCancel = document.getElementById("btnCancelEdit");
  const btnCondemn = document.getElementById("btnCondemn");
  const btnResolve = document.getElementById("btnResolve");
  const backArrow = document.querySelector("#view-computer .mobile-back-btn");

  if (textSpan.textContent.trim() === "Edit") {
    // Switch to Save mode
    isEditModeActive = true;
    btn.innerHTML = `<i class="fas fa-save"></i> <span class="btn-text" id="editText">Save</span>`;
    btn.style.backgroundColor = "#4caf50";
    btn.style.color = "white";

    if (btnCancel) btnCancel.style.display = "inline-flex";
    if (btnCancel) btnCancel.classList.add("show-cancel");
    if (btnCondemn) btnCondemn.style.display = "none";
    if (btnResolve) btnResolve.style.display = "none";
    if (backArrow) backArrow.style.display = "none";

    // --- 1. HANDLE TEXT/INPUT VISIBILITY ---
    document
      .querySelectorAll(
        ".specs-content-box .view-mode:not(.status-pill):not(#view_com_age):not(#view_num_repair)",
      )
      .forEach((el) => (el.style.display = "none"));

    document
      .querySelectorAll(
        ".specs-content-box .edit-mode:not(.status-toggle-group)",
      )
      .forEach((el) => {
        if (
          el.id !== "edit_com_age" &&
          el.id !== "edit_num_repair" &&
          !el.querySelector("#edit_com_age") &&
          !el.querySelector("#edit_num_repair")
        ) {
          el.style.display = "block";
        }
      });

    // --- 2. THE REVISION: SELECTIVE STATUS TOGGLING ---
    // We look at every status row to decide if it should be a toggle or a label
    document
      .querySelectorAll(".specs-content-box .status-row")
      .forEach((row) => {
        const pill = row.querySelector(".status-pill");
        const toggleGroup = row.querySelector(".status-toggle-group");

        if (pill && toggleGroup) {
          const currentStatus = pill.innerText.trim();

          if (currentStatus === "Working" || currentStatus === "Healthy") {
            pill.style.display = "none";
            toggleGroup.style.display = "flex";
          } else {
            pill.style.display = ""; // <--- FIX: Changed from "block" to ""
            toggleGroup.style.display = "none";

            pill.classList.remove("green");
            pill.classList.add("yellow");
          }
        }
      });
  } else {
    // Already in Save mode
    openAdminLogModal("pc");
  }
}

function cancelEditMode() {
  // --- 1. TURN THE LOCK OFF FIRST! ---
  isEditModeActive = false;

  // --- 2. DETECT ACTIVE VIEW ---
  const viewFacility = document.getElementById("view-facility");
  const isFacilityView = viewFacility && viewFacility.offsetParent !== null;

  // --- 3. RELOAD THE CORRECT ITEM ---
  if (isFacilityView) {
    // Reload Facility Asset
    if (currentSelectedFAId) {
      const activeItem = document.querySelector(
        "#facilityListContainer .asset-item.active",
      );
      selectFacilityAsset(activeItem, currentSelectedFAId); // <--- CHANGE THIS LINE
    }
  } else {
    // Reload Computer Unit
    if (currentEditingSetId) {
      const activeItem = document.querySelector(
        "#assetListContainer .asset-item.active",
      );
      selectUnit(activeItem, currentEditingSetId);
    }
  }

  // 4. Force UI Reset just in case the select functions don't do it
  resetUIToViewMode();
}
function resetUIToViewMode() {
  isEditModeActive = false;

  // --- 1. RESET COMPUTER UNIT BUTTONS ---
  const btn = document.getElementById("editToggleButton");
  const btnCancel = document.getElementById("btnCancelEdit");
  const btnCondemn = document.getElementById("btnCondemn");
  const btnResolve = document.getElementById("btnResolve");
  const backArrow = document.querySelector("#view-computer .mobile-back-btn");

  if (btn) {
    btn.innerHTML = `<i class="fas fa-pen"></i> <span class="btn-text" id="editText">Edit</span>`;
    btn.style.backgroundColor = "";
    btn.style.color = "";
  }
  if (btnCancel) {
    btnCancel.classList.remove("show-cancel");
    btnCancel.style.display = "none";
  }
  if (btnCondemn) btnCondemn.style.display = "";
  if (btnResolve) btnResolve.style.display = "";
  if (backArrow) backArrow.style.display = "";

  // --- 2. RESET FACILITY ASSET BUTTONS ---
  const btnFA = document.getElementById("editToggleButtonFA");
  const btnCancelFA = document.getElementById("btnCancelEditFA");
  const btnCondemnFA = document.getElementById("btnCondemnFA");
  const btnResolveFA = document.getElementById("btnResolveFA");

  if (btnFA) {
    btnFA.innerHTML = `<i class="fas fa-pen"></i> <span class="btn-text" id="editTextFA">Edit</span>`;
    btnFA.style.backgroundColor = "";
    btnFA.style.color = "";
  }
  if (btnCancelFA) {
    btnCancelFA.classList.remove("show-cancel");
    btnCancelFA.style.display = "none";
  }
  if (btnCondemnFA) btnCondemnFA.style.display = "";
  if (btnResolveFA) btnResolveFA.style.display = "";

  document.querySelectorAll(".right-panel .view-mode").forEach((el) => {
    el.style.display = "";
  });

  // Hide all edit-mode elements (including inputs and status toggles)
  document.querySelectorAll(".right-panel .edit-mode").forEach((el) => {
    el.style.display = "none";
  });

  // 4. Clean up any leftover Modal highlighting
  const remarksInput = document.getElementById("general_remarks");
  if (remarksInput) remarksInput.style.border = "";
}

// ==========================================
// RIGHT PANEL AGE CALCULATOR
// ==========================================

/**
 * Calculates the computer age for the edit mode based on the purchase date.
 */
function calculateEditComputerAge() {
  const dateInput = document.getElementById("edit_specs_purchase");
  const ageInput = document.getElementById("edit_com_age");

  if (!dateInput || !dateInput.value || !ageInput) return;

  let purchaseDate = new Date(dateInput.value);
  const today = new Date();

  today.setHours(0, 0, 0, 0);
  purchaseDate.setHours(0, 0, 0, 0);

  // Prevent future dates
  if (purchaseDate > today) {
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const dd = String(today.getDate()).padStart(2, "0");
    dateInput.value = `${yyyy}-${mm}-${dd}`;
    purchaseDate = today;
  }

  let years = today.getFullYear() - purchaseDate.getFullYear();
  let months = today.getMonth() - purchaseDate.getMonth();

  if (
    months < 0 ||
    (months === 0 && today.getDate() < purchaseDate.getDate())
  ) {
    years--;
  }

  if (years < 0) years = 0;

  // Update the edit input box directly
  ageInput.value = years;
}

// ==========================================
// 6. CONDEMN UNIT LOGIC
// ==========================================

// Global variables to track which item is selected

function openCondemnModal() {
  let activeId = null;
  let assetTag = "Unknown Item";

  // 1. Reliable View Detection
  const viewFacility = document.getElementById("view-facility");
  const viewComputer = document.getElementById("view-computer");

  // offsetParent is null if the element or any parent is display:none
  const isFacilityView = viewFacility && viewFacility.offsetParent !== null;
  const isComputerView = viewComputer && viewComputer.offsetParent !== null;

  // 2. Assign ID based on the VISIBLE view
  if (isFacilityView) {
    activeId = currentSelectedFAId;
    // Find the active name in the facility list
    const activeItem = document.querySelector(
      "#view-facility .asset-item.active .item-name",
    );
    if (activeItem) assetTag = activeItem.innerText;
  } else if (isComputerView) {
    activeId = currentEditingSetId;
    // Find the active name in the computer list
    const activeItem = document.querySelector(
      "#view-computer .asset-item.active .item-name",
    );
    if (activeItem) assetTag = activeItem.innerText;
  }

  // 3. Validation
  if (!activeId) {
    showNotification(
      "Selection Required",
      "Please select an item from the current list first.",
      "error",
    );
    return;
  }

  // 4. Populate Modal Fields
  document.getElementById("condemn_display_name").innerText = assetTag;
  document.getElementById("condemn_set_tag").value = assetTag;
  document.getElementById("condemn_set_id").value = activeId;

  // 5. Update Labels
  const modalTitle = document.getElementById("condemn_modal_title");
  const tagLabel = document.getElementById("condemn_tag_label");
  const idLabel = document.getElementById("condemn_id_label");

  if (isFacilityView) {
    modalTitle.innerText = "Condemn this Asset?";
    tagLabel.innerText = "Asset Tag:";
    idLabel.innerText = "Asset ID:";
  } else {
    modalTitle.innerText = "Condemn this Unit?";
    tagLabel.innerText = "Set Tag:";
    idLabel.innerText = "Set ID:";
  }

  // 6. Reset fields
  document
    .querySelectorAll('input[name="condemn_reason"]')
    .forEach((cb) => (cb.checked = false));
  document.getElementById("condemn_remarks").value = "";

  openModal("condemnModal");
}
function submitCondemnAction() {
  // 1. Get the ID from the modal input (where we just stored it in openCondemnModal)
  const setId = document.getElementById("condemn_set_id").value;
  const remarks = document.getElementById("condemn_remarks").value;

  // 2. Collect reasons
  const reasons = [];
  document
    .querySelectorAll('input[name="condemn_reason"]:checked')
    .forEach((cb) => {
      reasons.push(cb.value);
    });

  // 3. Validation
  if (reasons.length === 0 && remarks.trim() === "") {
    showNotification(
      "Validation Error",
      "Please select a reason or provide remarks.",
      "error",
    );
    return;
  }

  // 4. Determine Target
  const isFacilityView =
    document.getElementById("view-facility").offsetParent !== null;
  const targetUrl = isFacilityView
    ? "includes/condemn_facility_asset.php"
    : "includes/condemn_unit.php";

  // 5. Build Form Data
  const formData = new FormData();
  if (isFacilityView) {
    formData.append("asset_id", setId); // PHP expects asset_id
  } else {
    formData.append("set_id", setId); // PHP expects set_id
  }
  formData.append("reasons", JSON.stringify(reasons));
  formData.append("remarks", remarks);

  // 6. Execution
  fetch(targetUrl, { method: "POST", body: formData })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        closeModal("condemnModal");
        saveCurrentState(); // ADD THIS
        location.reload();
      } else {
        showNotification(
          "Database Error",
          data.error || "Update failed",
          "error",
        );
      }
    })
    .catch((err) => {
      console.error("Fetch error:", err);
      showNotification("Connection Error", "Failed to reach server.", "error");
    });
}

// ==========================================
// ADMIN LOG STATUS CHANGE WORKFLOW
// ==========================================
function openAdminLogModal(type) {
  currentReportType = type;

  const specChanges = []; // For grouped general updates (Brands, IDs, etc.)
  const statusChanges = []; // For individual broken components

  const nameMap = {
    specs_property: "Property ID",
    specs_cpu: "CPU",
    specs_brand: "Brand",
    specs_os: "OS",
    specs_gpu: "GPU",
    specs_ram: "RAM",
    specs_storage: "Storage Type",
    specs_capacity: "Storage Capacity",
    monitor_property: "Monitor Prop ID",
    monitor_brand: "Monitor Brand",
    mouse_brand: "Mouse Brand",
    keyboard_brand: "Keyboard Brand",
    avr_brand: "AVR Brand",
    usb_ports: "Available USB Ports",
    usb_status: "USB Port",
    wifi_status: "Wi-Fi",
    mic_status: "Microphone Jack",
    hdmi_status: "HDMI",
    headphone_status: "Headphone Jack",
    display_status: "Display Port",
    inline_status: "In-line Jack",
    ethernet_status: "Ethernet Port",
    disk_health: "Disk Health",
    power_health: "Power Supply",
    monitor_status: "Monitor",
    mouse_status: "Mouse",
    keyboard_status: "Keyboard",
    avr_status: "AVR",
    set_status: "Overall Unit Status",
  };

  if (type === "pc") {
    const activeItem = document.querySelector(
      "#assetListContainer .asset-item.active .item-name",
    );
    const activeItemText = activeItem ? activeItem.innerText : "Unit";
    const unitNameEl = document.getElementById("logStatusUnitName");
    if (unitNameEl) unitNameEl.innerText = `[${activeItemText}]`;

    // 1. SCAN STATUS TOGGLES (Only goes to statusChanges)
    document
      .querySelectorAll(".specs-content-box .status-toggle-group")
      .forEach((group) => {
        if (group.style.display !== "none") {
          const dbColumn = group.id.replace("toggle_", "");
          const originalPill = document.getElementById("pill_" + dbColumn);
          const activeBtn = group.querySelector(".status-btn.active");

          if (originalPill && activeBtn) {
            const oldStatus = originalPill.innerText.trim();
            let newStatus =
              activeBtn.getAttribute("data-type") === "repair"
                ? "For Repair"
                : "Working";
            if (dbColumn === "disk_health")
              newStatus =
                activeBtn.getAttribute("data-type") === "repair"
                  ? "Poor"
                  : "Healthy";

            if (oldStatus !== newStatus) {
              const niceName = nameMap[dbColumn] || dbColumn;
              statusChanges.push({
                name: niceName,
                old: oldStatus,
                new: newStatus,
              });
            }
          }
        }
      });

    // 2. SCAN SPEC INPUTS (Only goes to specChanges)
    document
      .querySelectorAll('.specs-content-box input[id^="edit_"]')
      .forEach((input) => {
        const dbColumn = input.id.replace("edit_", "");
        if (["com_age", "num_repair"].includes(dbColumn)) return;

        const viewEl = document.getElementById("view_" + dbColumn);
        if (viewEl) {
          let oldVal = viewEl.innerText.trim();
          if (oldVal === "N/A" || oldVal === "---") oldVal = "";
          let newVal = input.value.trim();

          if (oldVal !== newVal) {
            const niceName = nameMap[dbColumn] || dbColumn;
            specChanges.push(niceName);
          }
        }
      });
  } else if (type === "fa") {
    const headerTitle = document
      .getElementById("view_fa_header_title")
      .innerText.replace(" Details", "")
      .trim();
    const unitNameEl = document.getElementById("logStatusUnitName");
    if (unitNameEl) unitNameEl.innerText = `[${headerTitle}]`;

    // 1. FA STATUS SCANNER
    const originalPill = document.getElementById("original_fa_status");
    const activeBtn = document.querySelector(
      "#toggle_fa_status .status-btn.active",
    );

    if (
      originalPill &&
      activeBtn &&
      document.getElementById("toggle_fa_status").style.display !== "none"
    ) {
      const oldStatus = originalPill.value.trim();
      const newStatus =
        activeBtn.getAttribute("data-type") === "repair"
          ? "For Repair"
          : "Working";
      if (oldStatus !== newStatus) {
        statusChanges.push({
          name: "Asset Status",
          old: oldStatus,
          new: newStatus,
        });
      }
    }

    // 2. FA TEXT INPUT SCANNER
    const faInputs = [
      { id: "fa_name", label: "Device Name" },
      { id: "fa_property", label: "Property ID" },
      { id: "fa_brand", label: "Brand" },
    ];

    faInputs.forEach((field) => {
      const viewEl = document.getElementById("view_" + field.id);
      const inputEl = document.getElementById("edit_" + field.id);
      if (viewEl && inputEl) {
        let oldVal = viewEl.innerText.trim();
        if (oldVal === "N/A" || oldVal === "---") oldVal = "";
        let newVal = inputEl.value.trim();

        if (oldVal !== newVal) {
          specChanges.push(field.label);
        }
      }
    });
  }

  // 3. SET THE GLOBAL AFFECTED STRING FOR THE DB (Cleaned up)
  window.pendingAffectedString =
    specChanges.length > 0 ? specChanges.join(", ") : "General Update";

  let htmlContent = "";

  // 4. A. Grouped Specs Block
  if (
    specChanges.length > 0 ||
    (specChanges.length === 0 && statusChanges.length === 0)
  ) {
    const affectedList =
      specChanges.length > 0
        ? specChanges.join("<br>")
        : "General / Minor Edits";
    htmlContent += `
            <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
                <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #333;">Details & Specifications Updated:</h4>
                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1; font-size: 13px; color: #555; background: #f4f4f4; padding: 10px; border-radius: 6px;">
                        <strong>Affected Fields:</strong><br>${affectedList}
                    </div>
                    <div style="flex: 2;">
                        <textarea id="general_remarks" class="log-remark-input" data-name="Details Update" placeholder="Optional: Notes for these changes..." style="width: 100%; height: 100%; min-height: 60px; padding: 10px; border-radius: 6px; border: 1px solid #ddd; resize: none; font-size: 13px;"></textarea>
                    </div>
                </div>
            </div>
        `;
  }

  // 5. B. Individual Status Blocks
  if (statusChanges.length > 0) {
    statusChanges.forEach((stat) => {
      const pillBg = "#fff3e0";
      const pillColor = "#e65100";

      htmlContent += `
                <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 14px; color: #f57f17;">
                            <i class="fas fa-exclamation-circle"></i> Status Update
                        </h4>
                        <span style="background-color: ${pillBg}; color: ${pillColor}; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 700;">
                            ${stat.new}
                        </span>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div style="flex: 1; font-size: 13px; color: #555; background: #f4f4f4; padding: 10px; border-radius: 6px;">
                            <strong>Affected:</strong><br>
                            <span style="display: inline-block; margin-top: 5px;">${stat.name}</span>
                        </div>
                        <div style="flex: 2; display: flex; flex-direction: column; gap: 5px;">
                            <label style="font-size: 13px; font-weight: 600; color: #333;">Remarks:</label>
                            <textarea class="log-remark-input status-remark-field" data-name="${stat.name}" placeholder="Optional: Why is this marked for repair?" style="width: 100%; height: 60px; padding: 10px; border-radius: 6px; border: 1px solid #ddd; resize: none; font-size: 13px; box-sizing: border-box;"></textarea>
                        </div>
                    </div>
                </div>
            `;
    });
  }

  const contentEl = document.getElementById("logStatusDynamicContent");
  if (contentEl) contentEl.innerHTML = htmlContent;

  openModal("logStatusModal");
}

function confirmLogStatus() {
  const saveBtn = document.querySelector("#logStatusModal .btn-confirm");
  const formData = new FormData();

  // --- 1. ROUTING: PC vs FA ---
  let targetUrl = "";
  if (currentReportType === "pc") {
    targetUrl = "includes/update_unit.php";
    formData.append("set_id", currentEditingSetId);
  } else {
    targetUrl = "includes/update_facility_asset.php";
    formData.append("asset_id", currentSelectedFAId);
  }

  // --- 2. HANDLE GENERAL REMARKS ---
  const generalRemarksInput = document.getElementById("general_remarks");
  if (generalRemarksInput) {
    let userNotes = generalRemarksInput.value.trim();
    let finalGeneral = `Details Update. Notes: ${userNotes || "None provided"}`;
    formData.append("general_remarks", finalGeneral);
  } else {
    formData.append("general_remarks", "");
  }

  // --- 3. HANDLE STATUS REMARKS ---
  const statusRemarkInputs = document.querySelectorAll(
    "#logStatusDynamicContent .status-remark-field",
  );
  let statusLogsArray = [];

  statusRemarkInputs.forEach((input) => {
    let userNotes = input.value.trim();
    const fieldName = input.getAttribute("data-name");
    let finalStatus = `Status Update. Notes: ${userNotes || "None provided"}`;
    statusLogsArray.push({
      component: fieldName,
      remark: finalStatus,
    });
  });

  formData.append("status_logs", JSON.stringify(statusLogsArray));
  formData.append("report_affected_general", window.pendingAffectedString);

  // --- UI Loading State ---
  const originalBtnHTML = saveBtn.innerHTML;
  saveBtn.disabled = true;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  // --- 4. GATHER DB DATA ---
  document
    .querySelectorAll('.specs-content-box input[id^="edit_"]')
    .forEach((input) => {
      formData.append(input.id.replace("edit_", ""), input.value);
    });

  document
    .querySelectorAll(".specs-content-box .status-toggle-group")
    .forEach((group) => {
      const dbColumn = group.id.replace("toggle_", "");
      const activeBtn = group.querySelector(".status-btn.active");

      if (group.style.display !== "none" && activeBtn) {
        let val =
          activeBtn.getAttribute("data-type") === "repair"
            ? "For Repair"
            : "Working";
        if (dbColumn === "disk_health")
          val =
            activeBtn.getAttribute("data-type") === "repair"
              ? "Poor"
              : "Healthy";
        formData.append(dbColumn, val);
      } else {
        const pill = document.getElementById("pill_" + dbColumn);
        formData.append(dbColumn, pill ? pill.innerText.trim() : "For Repair");
      }
    });

  // --- 5. SEND TO THE CORRECT PHP SCRIPT ---
  fetch(targetUrl, { method: "POST", body: formData })
    .then((res) => res.json())
    .then((data) => {
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalBtnHTML;
      if (data.success) {
        closeModal("logStatusModal");
        reloadWithToast("Success", "Updated successfully", "success");
      } else {
        showNotification("Error", data.error, "error");
      }
    })
    .catch((err) => {
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalBtnHTML;
      showNotification("Connection Error", "Could not reach server.", "error");
    });
}
// ==========================================
// 7. FINALIZE DEPLOYMENT LOGIC (Missing IDs)
// ==========================================

function openMissingIdModal(roomNumber) {
  forceCloseEditMode();
  fetch(`includes/get_missing_ids.php?room=${roomNumber}`)
    .then((res) => res.text())
    .then((text) => {
      try {
        const data = JSON.parse(text);

        if (data.success) {
          const tbody = document.getElementById("missingIdsTableBody");
          tbody.innerHTML = "";
          document.getElementById("missing_count_text").innerText =
            data.units.length;

          data.units.forEach((unit) => {
            const sysId = unit.specs_property || "";
            const monId = unit.monitor_property || "";

            let badgeClass = "purple";
            let badgeText = "No Property ID";

            if (sysId && monId) {
              badgeClass = unit.status === "For Repair" ? "yellow" : "green";
              badgeText = unit.status;
            }

            // Upgraded to Mobile-Safe Card Layout instead of a Table Row!
            const card = document.createElement("div");
            card.className = "missing-id-card";
            card.style.cssText =
              "background: #fff; border: 1px solid #eaeaea; border-radius: 8px; padding: 15px; margin-bottom: 15px; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);";

            card.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px;">
                    <strong style="font-size: 16px; color: #1b4d3e;"><i class="fas fa-desktop"></i> PC-${unit.set_tag}</strong>
                    <span class="badge ${badgeClass} status-badge" data-original-status="${unit.status}">${badgeText}</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 5px;">
                    <div>
                        <label style="font-size: 12px; color: #666; font-weight: 600; display: block; margin-bottom: 5px;">System Unit Property ID</label>
                        <input type="text" class="modal-input serial-input sys-input" data-id="${unit.set_id}" value="${sysId}" placeholder="Enter system unit ID..." oninput="checkSerialInputs(this)" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #666; font-weight: 600; display: block; margin-bottom: 5px;">Monitor Property ID</label>
                        <input type="text" class="modal-input serial-input mon-input" data-id="${unit.set_id}" value="${monId}" placeholder="Enter monitor ID..." oninput="checkSerialInputs(this)" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>
            `;
            tbody.appendChild(card);
          });

          openModal("missingIdModal");
        } else {
          alert("Database Error: " + data.error);
        }
      } catch (e) {
        console.error("JSON Parse Error", e);
      }
    })
    .catch((err) => console.error("Fetch request failed:", err));
}

// Dynamically updates the badge
function checkSerialInputs(inputElement) {
  // Finds the parent card instead of a table row
  const container =
    inputElement.closest(".missing-id-card") || inputElement.closest("tr");
  if (!container) return;

  const sysVal = container.querySelector(".sys-input").value.trim();
  const monVal = container.querySelector(".mon-input").value.trim();
  const badge = container.querySelector(".status-badge");
  const origStatus = badge.getAttribute("data-original-status");

  if (sysVal !== "" && monVal !== "") {
    badge.className = `badge ${origStatus === "For Repair" ? "yellow" : "green"} status-badge`;
    badge.innerText = origStatus;
  } else {
    badge.className = "badge purple status-badge";
    badge.innerText = "No Property ID";
  }
}

function finalizeDeployment() {
  // Scans for the new cards!
  const rows = document.querySelectorAll(
    "#missingIdsTableBody .missing-id-card, #missingIdsTableBody tr",
  );
  let payload = [];

  rows.forEach((container) => {
    const sysInput = container.querySelector(".sys-input");
    const monInput = container.querySelector(".mon-input");

    if (sysInput && monInput) {
      payload.push({
        set_id: sysInput.getAttribute("data-id"),
        specs_property: sysInput.value.trim(),
        monitor_property: monInput.value.trim(),
      });
    }
  });

  if (payload.length === 0) {
    alert("No units found to update!");
    return;
  }

  const formData = new FormData();
  formData.append("payload", JSON.stringify(payload));

  fetch("includes/save_missing_ids.php", { method: "POST", body: formData })
    .then((res) => res.text())
    .then((text) => {
      try {
        const data = JSON.parse(text);
        if (data.success) {
          closeModal("missingIdModal");
          reloadWithToast(
            "Deployment Finalized",
            "Property IDs successfully assigned.",
            "success",
          );
        } else {
          showNotification("Finalization Failed", data.error, "error");
        }
      } catch (e) {
        showNotification(
          "Server Error",
          "Invalid response from server.",
          "error",
        );
      }
    })
    .catch((err) =>
      showNotification(
        "Connection Error",
        "Failed to finalize deployment.",
        "error",
      ),
    );
}

// ==========================================
// 10. FACILITY ASSETS LOGIC
// ==========================================

// This function dynamically changes the background color of the status select box
function updateFAStatusColor(selectElement) {
  if (selectElement.value === "Working") {
    selectElement.style.backgroundColor = "#e8f5e9"; // Light Green
    selectElement.style.color = "#2e7d32"; // Dark Green
    selectElement.style.borderColor = "#c8e6c9";
  } else if (selectElement.value === "For Repair") {
    selectElement.style.backgroundColor = "#fff3e0"; // Light Orange
    selectElement.style.color = "#e65100"; // Dark Orange
    selectElement.style.borderColor = "#ffe0b2";
  }
}

// Opens the modal and fetches the next available FA-XX tag
function openFacilityAssetModal() {
  forceCloseEditMode();
  // 1. Get the lab_id from the URL (e.g., assets_management.php?lab_id=12)
  const urlParams = new URLSearchParams(window.location.search);
  const labId = urlParams.get("lab_id");

  if (!labId) {
    showNotification("System Error", "Lab ID missing from URL", "error");
    return;
  }

  // Set loading state in the tag input
  document.getElementById("fa_set_tag").value = "Loading...";

  // 2. Fetch the next available ID/Tag using the lab_id
  fetch(`includes/get_next_fa_tag.php?lab_id=${labId}`)
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        // Set the short tag (e.g., "01") in the input
        document.getElementById("fa_set_tag").value = data.next_tag;
        // Store the full string ID (e.g., "FA-0001-2026") in a global variable
        window.currentPendingFAId = data.next_id;
      } else {
        showNotification("Error", "Could not generate next tag", "error");
      }
    })
    .catch((err) =>
      showNotification("Connection Error", "Server is unreachable", "error"),
    );

  // 3. Open modal using 'flex' so your CSS centering works
  document.getElementById("addFacilityAssetModal").style.display = "flex";
}

function submitFacilityAsset() {
  const urlParams = new URLSearchParams(window.location.search);
  const labId = urlParams.get("lab_id");

  // Grab elements
  const nameEl = document.getElementById("fa_asset_name");
  const propertyEl = document.getElementById("fa_asset_property");
  const brandEl = document.getElementById("fa_brand");
  const statusEl = document.getElementById("fa_status");
  const tagEl = document.getElementById("fa_set_tag");

  // --- COMPREHENSIVE ERROR HANDLING ---

  // 1. Check Device Name
  if (!nameEl.value.trim()) {
    showNotification(
      "Input Required",
      "Please enter a Device Name (e.g., Printer).",
      "error",
    );
    nameEl.focus();
    return;
  }

  // 2. Check Property ID
  if (!propertyEl.value.trim()) {
    showNotification(
      "Input Required",
      "Please enter the Property ID or Serial Number.",
      "error",
    );
    propertyEl.focus();
    return;
  }

  // 3. Check Brand
  if (!brandEl.value.trim()) {
    showNotification(
      "Input Required",
      'Please enter a Brand (use "N/A" if unknown).',
      "error",
    );
    brandEl.focus();
    return;
  }

  // 4. Check Lab ID (System check)
  if (!labId) {
    showNotification(
      "System Error",
      "Laboratory context lost. Please refresh the page.",
      "error",
    );
    return;
  }

  // 5. Check generated ID
  if (!window.currentPendingFAId) {
    showNotification(
      "System Error",
      "Asset ID not generated. Please re-open the modal.",
      "error",
    );
    return;
  }

  // --- PREPARE DATA ---
  const formData = new FormData();
  formData.append("asset_id", window.currentPendingFAId);
  formData.append("asset_tag", tagEl.value);
  formData.append("asset_name", nameEl.value.trim());
  formData.append("asset_property", propertyEl.value.trim());
  formData.append("asset_brand", brandEl.value.trim());
  formData.append("asset_status", statusEl.value);
  formData.append("lab_id", labId);

  // Visual feedback: Disable button to prevent double submission
  const submitBtn = document.querySelector(".btn-finalize");
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

  fetch("includes/add_facility_asset.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        closeModal("addFacilityAssetModal");
        // Clear fields for next use
        nameEl.value = "";
        propertyEl.value = "";
        brandEl.value = "";
        reloadWithToast(
          "Asset Added",
          "New asset successfully created.",
          "success",
        );
      } else {
        // Error from PHP (e.g., duplicate property ID)
        showNotification(
          "Database Error",
          data.error || "Failed to save asset.",
          "error",
        );
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      }
    })
    .catch((err) => {
      console.error(err);
      showNotification(
        "Connection Error",
        "Failed to reach server. Check your connection.",
        "error",
      );
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    });
}

function selectFacilityAsset(element, assetId) {
  if (isEditModeActive) {
    showNotification(
      "Action Blocked",
      "Please save or cancel your current edits before selecting another item.",
      "error",
    );
    return;
  }

  // 1. Set the correct active IDs and reset states
  currentSelectedFAId = assetId;
  currentEditingSetId = null;

  // 2. UI Reset: Toggle "active" highlight on the left list
  document
    .querySelectorAll("#facilityListContainer .asset-item")
    .forEach((item) => item.classList.remove("active"));
  if (element) element.classList.add("active");

  // Ensure the right panel is visible
  const rightPanel = document.getElementById("view-facility-right");
  if (rightPanel) rightPanel.style.display = "block";

  // 3. Fetch the data from the server
  fetch(`includes/get_facility_asset_details.php?asset_id=${assetId}`)
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        const asset = data.data;

        // --- POPULATE TEXT BOXES (Mapped to new grid IDs) ---
        document.getElementById("view_fa_header_title").innerText =
          `FA-${asset.asset_tag} Details`;

        document.getElementById("view_fa_property").innerText =
          asset.asset_property || "N/A";
        document.getElementById("view_fa_name").innerText =
          asset.asset_name || "N/A";
        document.getElementById("view_fa_brand").innerText =
          asset.asset_brand || "N/A";

        // --- POPULATE STATUS PILL ---
        const statusPill = document.getElementById("pill_fa_status");
        statusPill.innerText = asset.asset_status;
        statusPill.className = "status-pill view-mode"; // Reset classes

        // 2. FIX: Dynamic Status Coloring uses "orange" to perfectly match CU
        if (asset.asset_status === "Working") {
          statusPill.classList.add("green");
        } else if (
          asset.asset_status === "For Repair" ||
          asset.asset_status === "Missing Parts"
        ) {
          statusPill.classList.add("orange"); // Changed from yellow to orange
        } else {
          statusPill.classList.add("red");
        }

        // Set hidden value for the Log Modal scanner
        const origStatusEl = document.getElementById("original_fa_status");
        if (origStatusEl) origStatusEl.value = asset.asset_status;

        // --- RESOLVE BUTTON LOGIC ---
        const btnResolveFA = document.getElementById("btnResolveFA");
        if (btnResolveFA) {
          btnResolveFA.style.display = "inline-flex"; // Always show it

          if (
            asset.asset_status === "For Repair" ||
            asset.asset_status === "Missing Parts"
          ) {
            // ACTIVE STATE: Green highlight
            btnResolveFA.className = "btn-confirm";
            btnResolveFA.style.opacity = "1";
            btnResolveFA.style.cursor = "pointer";
            btnResolveFA.onclick = () => openResolveModal("fa");
          } else {
            // DISABLED STATE: Grey border
            btnResolveFA.className = "btn-resolve hide-on-mobile";
            btnResolveFA.style.opacity = "1";
            btnResolveFA.style.cursor = "not-allowed";
            btnResolveFA.onclick = null;
          }
        }

        // --- POPULATE FA RECENT ACTIVITY LOGS ---
        const historyBody = document.getElementById("fa_activity_log_body");
        if (historyBody) {
          historyBody.innerHTML = "";

          if (data.history && data.history.length > 0) {
            data.history.forEach((log, index) => {
              // Color Logic for Badges
              let badgeColor = "gray";
              if (["Resolved", "Working"].includes(log.report_status))
                badgeColor = "green";
              if (
                ["Reported", "For Repair", "Missing Parts"].includes(
                  log.report_status,
                )
              )
                badgeColor = "yellow";
              if (log.report_status === "Condemned") badgeColor = "red";

              const card = document.createElement("div");
              card.className = "activity-card";
              card.style.padding = "15px 20px";
              card.style.backgroundColor = "#fff";
              if (index < data.history.length - 1)
                card.style.borderBottom = "1px solid #f0f0f0";

              card.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div style="font-size: 13px; color: #555;">
                        <strong style="color: #1b4d3e;"><i class="fas fa-user-circle"></i> ${log.report_actor || "System"}</strong> 
                        <span style="margin-left: 10px; color: #999; font-size: 11px;"><i class="far fa-clock"></i> ${log.formatted_date}</span>
                    </div>
                    <span class="badge ${badgeColor}">${log.report_status || "Logged"}</span>
                </div>
                
                <div style="font-size: 13px; color: #333; margin-bottom: 6px;">
                    <strong>Affected:</strong> <span style="color: #d32f2f; font-weight: 500;">${log.report_affected || "N/A"}</span>
                </div>

                <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; border-left: 4px solid #4caf50;">
                    <p style="margin: 0; font-size: 13px; color: #444; line-height: 1.5; font-style: italic;">
                        "${log.report_remarks || "Resolved without specific remarks."}"
                    </p>
                </div>
              `;
              historyBody.appendChild(card);
            });
          } else {
            historyBody.innerHTML = `
              <div style="text-align:center; color:#bbb; padding: 40px;">
                <i class="fas fa-clipboard-list" style="font-size: 24px; display: block; margin-bottom: 10px; color: #eee;"></i>
                No maintenance history found for this asset.
              </div>`;
          }
        }

        // 4. Force Reset Edit Mode (Universal sync fix)
        resetUIToViewMode();
      } else {
        showNotification("Error", data.error, "error");
      }
    })
    .catch((err) => {
      console.error("Fetch error:", err);
      showNotification(
        "Connection Error",
        "Failed to load asset details.",
        "error",
      );
    });

  // Handle Mobile View slide-over
  if (window.innerWidth <= 768) {
    const splitLayout = document.querySelector("#view-facility .split-layout");
    if (splitLayout) splitLayout.classList.add("mobile-show-details");
  }
}

// ==========================================
// FACILITY ASSETS: EDIT & TRANSFER LOGIC
// ==========================================

function toggleFAEditMode() {
  const btn = document.getElementById("editToggleButtonFA");
  const textSpan = document.getElementById("editTextFA");
  const btnCancel = document.getElementById("btnCancelEditFA");
  const btnCondemn = document.getElementById("btnCondemnFA");
  const btnResolve = document.getElementById("btnResolveFA");

  if (textSpan.textContent.trim() === "Edit") {
    isEditModeActive = true;
    btn.innerHTML = `<i class="fas fa-save"></i> <span class="btn-text" id="editTextFA">Save</span>`;
    btn.style.backgroundColor = "#4caf50";
    btn.style.color = "white";

    if (btnCancel) {
      btnCancel.style.display = "inline-flex";
      btnCancel.classList.add("show-cancel");
    }
    if (btnCondemn) btnCondemn.style.display = "none";
    if (btnResolve) btnResolve.style.display = "none";

    // Sync Text Inputs (Grab text from view mode and put it into the input boxes)
    const faInputs = ["fa_property", "fa_name", "fa_brand"];
    faInputs.forEach((id) => {
      const viewEl = document.getElementById("view_" + id);
      const editEl = document.getElementById("edit_" + id);
      if (viewEl && editEl) {
        let val = viewEl.innerText.trim();
        if (val === "---" || val === "N/A") val = "";
        editEl.value = val;
      }
    });

    // Hide View Mode, Show Edit Mode
    document
      .querySelectorAll("#view-facility-right .view-mode:not(.status-pill)")
      .forEach((el) => (el.style.display = "none"));
    document
      .querySelectorAll(
        "#view-facility-right .edit-mode:not(.status-toggle-group)",
      )
      .forEach((el) => (el.style.display = "block"));

    // Handle Status Toggle Lock
    const pill = document.getElementById("pill_fa_status");
    const toggleGroup = document.getElementById("toggle_fa_status");

    if (pill && toggleGroup) {
      const currentStatus = pill.innerText.trim();
      if (currentStatus === "Working" || currentStatus === "Healthy") {
        pill.style.display = "none";
        toggleGroup.style.display = "flex";
      } else {
        pill.style.display = ""; // <--- FIX: Changed from "block" to ""
        toggleGroup.style.display = "none";
        pill.classList.remove("green");
        pill.classList.add("yellow");
      }
    }
  } else {
    openAdminLogModal("fa");
  }
}

// 3. Process Transfer (Left exactly as you had it - it works perfectly!)
async function processTransfer() {
  const targetLabId = document.getElementById("transfer_target_lab").value;
  const remarks = document.getElementById("transfer_remarks").value;

  const selectedUnits = Array.from(
    document.querySelectorAll(".transfer-unit-checkbox:checked"),
  ).map((cb) => cb.value);
  const selectedAssets = Array.from(
    document.querySelectorAll(".transfer-asset-checkbox:checked"),
  ).map((cb) => cb.value);
  const actions = Array.from(
    document.querySelectorAll(
      '#transfer_actions input[type="checkbox"]:checked',
    ),
  ).map((cb) => cb.value);

  if (!targetLabId) {
    showNotification("Required", "Please select a target laboratory.", "error");
    return;
  }
  if (selectedUnits.length === 0 && selectedAssets.length === 0) {
    showNotification(
      "Selection Empty",
      "Please select at least one item to transfer.",
      "error",
    );
    return;
  }

  const formData = new FormData();
  formData.append("target_lab_id", targetLabId);
  formData.append("remarks", remarks);
  formData.append("actions", JSON.stringify(actions));
  formData.append("units", JSON.stringify(selectedUnits));
  formData.append("assets", JSON.stringify(selectedAssets));

  try {
    const response = await fetch("includes/process_transfer.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      closeModal("transferModal");
      if (typeof reloadWithToast === "function") {
        reloadWithToast(
          "Success",
          "Transfer completed and history logged.",
          "success",
        );
      } else {
        location.reload();
      }
    } else {
      showNotification("Error", result.error || "Transfer failed.", "error");
    }
  } catch (error) {
    console.error("Transfer Error:", error);
    showNotification(
      "Server Error",
      "Failed to connect to the server.",
      "error",
    );
  }
}

async function loadFacilityAssets(idToSelect = null) {
  try {
    // 1. Get the lab_id from the URL so we query the right room
    const urlParams = new URLSearchParams(window.location.search);
    const labId = urlParams.get("lab_id") || 0;

    // 2. Fetch fresh data using the lab_id
    const response = await fetch(
      `includes/get_facility_assets_list.php?lab_id=${labId}&t=${Date.now()}`,
    );
    const data = await response.json();

    if (data.success) {
      // 3. Target the EXACT ID from your HTML
      const listContainer = document.getElementById("facilityListContainer");
      if (!listContainer) return;

      listContainer.innerHTML = ""; // Wipe the old list

      if (data.assets.length === 0) {
        listContainer.innerHTML =
          "<div style='padding: 20px; text-align: center; color: #666;'>No facility assets found for this lab.</div>";
        return;
      }

      data.assets.forEach((asset) => {
        const isActive =
          idToSelect && asset.asset_id == idToSelect ? "active" : "";

        // 4. Match the Badge classes perfectly to your PHP
        let badgeClass = "green";
        if (asset.asset_status === "For Repair") badgeClass = "yellow";
        if (
          asset.asset_status === "Condemned" ||
          asset.asset_status === "For Condemn"
        )
          badgeClass = "red";

        // 5. Build the exact HTML layout used in your PHP loop
        listContainer.innerHTML += `
                    <div class="asset-item ${isActive}" data-asset-id="${asset.asset_id}" onclick="selectFacilityAsset(this, '${asset.asset_id}')">
                        <div class="asset-info">
                            <div class="item-name">FA-${asset.asset_tag}</div>
                        </div>
                        <div class="asset-status">
                            <span class="badge ${badgeClass}">${asset.asset_status}</span>
                        </div>
                    </div>
                `;
      });
    }
  } catch (error) {
    console.error("Error refreshing list:", error);
  }
}

// --- NEW: Mobile Screen Transition Logic ---
function closeMobileDetails() {
  const computerView = document
    .getElementById("view-computer")
    .querySelector(".split-layout");
  const facilityView = document
    .getElementById("view-facility")
    .querySelector(".split-layout");

  if (computerView) computerView.classList.remove("mobile-show-details");
  if (facilityView) facilityView.classList.remove("mobile-show-details");
}

function submitResolve() {
  const resolveType = document
    .getElementById("resolveModal")
    .getAttribute("data-resolve-type");

  const cards = document.querySelectorAll(
    "#resolveTableBody .resolve-card, #resolveTableBody tr",
  );

  const payload = [];
  let hasValidationError = false; // <-- NEW: Tracker for missing remarks

  // Scan cards to find ONLY the parts where the toggle is set to "working"
  cards.forEach((card) => {
    const toggleGroup = card.querySelector(".status-toggle-group");

    if (
      toggleGroup &&
      (toggleGroup.getAttribute("data-current-state") === "working" ||
        toggleGroup.getAttribute("data-current-state") === "healthy")
    ) {
      const columnId = toggleGroup.id.replace("resolve_toggle_", "");
      const componentName = card
        .querySelector(".resolve-comp-name")
        .innerText.trim();

      const remarksInput = card.querySelector(".resolve-admin-remarks");
      const adminRemarks = remarksInput.value.trim();

      // --- NEW: STRICT VALIDATION CHECK ---
      if (adminRemarks === "") {
        hasValidationError = true;
        remarksInput.style.border = "2px solid #f44336"; // Highlight the empty box in red!
      } else {
        remarksInput.style.border = "1px solid #4caf50"; // Keep green if filled
      }
      // ------------------------------------

      payload.push({
        column: columnId,
        componentName: componentName,
        adminRemarks: adminRemarks,
      });
    }
  });

  // --- NEW: STOP SUBMISSION IF VALIDATION FAILED ---
  if (hasValidationError) {
    showNotification(
      "Remarks Required",
      "Please describe how you fixed the items marked as Working.",
      "error",
    );
    return; // Stops the save!
  }

  if (payload.length === 0) {
    showNotification(
      "No Changes",
      "You must toggle at least one component to Working/Healthy to log a fix.",
      "error",
    );
    return;
  }

  const btn = document.querySelector("#resolveModal .btn-confirm");
  const origHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  // --- GRAB THE LAB ID FROM THE URL OR PAGE ---
  const urlParams = new URLSearchParams(window.location.search);
  let currentLabId = urlParams.get("lab_id");
  if (!currentLabId) {
    const addBtn = document.querySelector(".btn-green-add");
    if (addBtn) {
      const match = addBtn.getAttribute("onclick").match(/\d+/);
      if (match) currentLabId = match[0];
    }
  }

  const formData = new FormData();
  formData.append("type", resolveType);
  formData.append(
    "id",
    resolveType === "pc" ? currentEditingSetId : currentSelectedFAId,
  );
  formData.append("lab_id", currentLabId || 0);
  formData.append("resolutions", JSON.stringify(payload));

  fetch("includes/resolve_issue.php", { method: "POST", body: formData })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        closeModal("resolveModal");
        reloadWithToast(
          "Log Saved",
          "Status changed to Resolved. History updated.",
          "success",
        );
      } else {
        showNotification("Error", data.error, "error");
        btn.disabled = false;
        btn.innerHTML = origHtml;
      }
    })
    .catch((err) => {
      showNotification("Connection Error", "Failed to reach server.", "error");
      btn.disabled = false;
      btn.innerHTML = origHtml;
    });
}

// ==========================================
// RESOLVE MAINTENANCE WORKFLOW
// ==========================================
function openResolveModal(type) {
  // 1. Set the type attribute
  document
    .getElementById("resolveModal")
    .setAttribute("data-resolve-type", type);

  const listContainer = document.getElementById("resolveTableBody");
  listContainer.innerHTML =
    '<div style="text-align:center; padding: 30px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

  // 2. SCOPED selection to avoid picking the wrong active item
  const containerId =
    type === "pc" ? "#assetListContainer" : "#facilityListContainer";
  const activeItem = document.querySelector(
    `${containerId} .asset-item.active .item-name`,
  );

  document.getElementById("resolveUnitName").innerText =
    `[${activeItem ? activeItem.innerText : "Unit"}]`;

  const setId = type === "pc" ? currentEditingSetId : currentSelectedFAId;

  if (!setId) {
    showNotification("Error", "No unit or asset selected.", "error");
    return;
  }

  // 3. Fetching
  fetch(`includes/get_broken_components.php?type=${type}&id=${setId}`)
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        listContainer.innerHTML = "";

        if (data.components.length === 0) {
          listContainer.innerHTML =
            '<div style="text-align:center; padding: 30px; color: #666;">This item is not currently marked for repair.</div>';
          return;
        }

        data.components.forEach((comp) => {
          // --- FA SPECIAL CASE ---
          // If it's a facility asset, the 'name' might just be the asset type (e.g., 'Printer')
          const componentName = type === "fa" ? ` ${comp.name}` : comp.name;

          let workingText = "Working";
          let brokenText = "For Repair";

          if (comp.db_column === "disk_health") {
            workingText = "Healthy";
            brokenText = "Poor";
          }

          const card = document.createElement("div");
          card.className = "resolve-card";
          // I suggest moving these styles to assets_management.css instead of inline
          card.style.cssText =
            "background: #fff; border: 1px solid #eaeaea; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);";

          card.innerHTML = `
              <div style="display: flex; flex-direction: column; gap: 12px;">
                  <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 8px;">
                      <strong style="font-size: 16px; color: #1b4d3e;" class="resolve-comp-name">
                        <i class="fas fa-wrench" style="margin-right: 5px; color: #e65100;"></i> ${componentName}
                      </strong>
                  </div>
                  
                  <div style="font-size: 13px; color: #666; background: #f9f9f9; padding: 10px 12px; border-left: 3px solid #ccc; border-radius: 4px;">
                      <strong>Reporter Remarks:</strong> <em>"${comp.reporter_remarks || "No remarks provided."}"</em>
                  </div>
                  
                  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: start; margin-top: 5px;">
                      <div>
                          <label style="font-size: 12px; color: #666; font-weight: 600; display: block; margin-bottom: 5px;">Set Status:</label>
                          <div class="status-toggle-group" id="resolve_toggle_${comp.db_column}" data-current-state="repair" style="margin:0; width: 100%;">
                              <button type="button" class="status-btn" data-type="working" onclick="toggleResolveStatus(this, '${comp.db_column}', 'working')">${workingText}</button>
                              <button type="button" class="status-btn active" data-type="repair" onclick="toggleResolveStatus(this, '${comp.db_column}', 'repair')">${brokenText}</button>
                          </div>
                      </div>
                      <div>
                          <label style="font-size: 12px; color: #666; font-weight: 600; display: block; margin-bottom: 5px;">Admin Fix Remarks:</label>
                          <input type="text" id="resolve_remarks_${comp.db_column}" 
                                 class="edit-input resolve-admin-remarks" 
                                 placeholder="Available when fixed..." 
                                 disabled 
                                 style="background:#f4f4f4; cursor:not-allowed; width: 100%; box-sizing: border-box; margin:0;">
                      </div>
                  </div>
              </div>
          `;
          listContainer.appendChild(card);
        });
        openModal("resolveModal");
      } else {
        showNotification("Database Error", data.error, "error");
      }
    })
    .catch((err) => {
      console.error("Fetch Error:", err);
      showNotification(
        "Server Error",
        "Could not load broken components. Check network logs.",
        "error",
      );
    });
}

function toggleResolveStatus(btn, column, state) {
  // 1. Locate the parent containers
  const group = btn.closest(".status-toggle-group");
  const parentCard = btn.closest(".resolve-card");

  // Safety check: ensure we are inside a card
  if (!parentCard) return;

  // 2. Find the specific remarks input inside THIS card
  // We use querySelector instead of getElementById for better scoping
  const remarksInput = parentCard.querySelector(`#resolve_remarks_${column}`);

  // 3. Toggle Button Active States
  group
    .querySelectorAll(".status-btn")
    .forEach((b) => b.classList.remove("active"));
  btn.classList.add("active");

  // 4. Update state attribute for easy data collection later
  group.setAttribute("data-current-state", state);

  // 5. Visual & Functional Logic for Remarks
  if (state === "working" || state === "healthy") {
    // ENABLE: Asset is fixed
    remarksInput.disabled = false;
    remarksInput.style.backgroundColor = "#ffffff";
    remarksInput.style.border = "1px solid #4caf50"; // Green border to signal "Ready to Fix"
    remarksInput.style.cursor = "text";
    remarksInput.placeholder = "Describe how you fixed this...";

    // Add a slight "pop" effect so the user knows they can type now
    remarksInput.focus();
  } else {
    // DISABLE: Asset is still broken
    remarksInput.disabled = true;
    remarksInput.value = ""; // Clear existing text
    remarksInput.style.backgroundColor = "#f4f4f4";
    remarksInput.style.border = "1px solid #ddd";
    remarksInput.style.cursor = "not-allowed";
    remarksInput.placeholder = "Available when fixed...";
  }
}
// --- NEW HELPER: Silently force close edit mode ---
function forceCloseEditMode() {
  if (isEditModeActive) {
    isEditModeActive = false; // Turn off the lock instantly

    // Check if we are in Facility view or PC view
    const viewFacility = document.getElementById("view-facility");
    const isFacilityView = viewFacility && viewFacility.offsetParent !== null;

    if (isFacilityView) {
      cancelEditMode(); // Closes Facility edit
    } else {
      // Closes PC edit and reloads the original data to wipe unsaved text
      if (currentEditingSetId) {
        const activeItem = document.querySelector(
          "#assetListContainer .asset-item.active",
        );
        if (activeItem) selectUnit(activeItem, currentEditingSetId);
      }
    }
  }
}
