let currentEditingSetId = null;   // For Computer Units
let currentSelectedFAId = null;   // For Facility Assets

// ==========================================
// TOAST NOTIFICATION SYSTEM
// ==========================================

// Check for pending toasts when the page loads
document.addEventListener('DOMContentLoaded', () => {
    const pendingToast = sessionStorage.getItem('pendingToast');
    if (pendingToast) {
        const toastData = JSON.parse(pendingToast);
        showNotification(toastData.title, toastData.message, toastData.type);
        sessionStorage.removeItem('pendingToast'); // Clear it so it only shows once
    }

    // Add event listeners for asset items
    document.querySelectorAll('#assetListContainer .asset-item:not(.missing-id)').forEach(item => {
        item.addEventListener('click', function() {
            const setId = this.getAttribute('data-set-id');
            if (setId) selectUnit(this, setId);
        });
    });

    document.querySelectorAll('#facilityListContainer .asset-item').forEach(item => {
        item.addEventListener('click', function() {
            const assetId = this.getAttribute('data-asset-id');
            if (assetId) selectFacilityAsset(this, assetId);
        });
    });
});

function showNotification(title, message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

    toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${iconClass}"></i></div>
        <div class="toast-content">
            <h4>${title}</h4>
            <p>${message}</p>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Helper function to trigger a reload and show a toast afterward
function reloadWithToast(title, message, type = 'success') {
    sessionStorage.setItem('pendingToast', JSON.stringify({ title, message, type }));
    location.reload(); 
}

// ==========================================
// STATUS DETERMINATION FUNCTIONS
// ==========================================

/**
 * Determines the display status for a unit based on data completeness and repair status
 * @param {Object} unitData - Unit data object with specs_property, monitor_property, and status fields
 * @returns {string} Display status
 */
function determineUnitDisplayStatus(unitData) {
    // 1. Check if property IDs are missing (highest priority)
    if (!unitData.specs_property || !unitData.monitor_property) {
        return 'No Property ID';
    }

    // 2. Check for repair status (from database)
    if (unitData.set_status === 'For Repair') {
        return 'For Repair';
    }

    // 3. Check for condemn status
    if (unitData.set_status === 'For Condemn' || unitData.set_status === 'Condemned') {
        return unitData.set_status;
    }

    // 4. Default to Working
    return 'Working';
}

/**
 * Gets the appropriate badge class for a status
 * @param {string} status - Status string
 * @returns {string} CSS class for badge
 */
function getStatusBadgeClass(status) {
    const statusMap = {
        'Working': 'badge green',
        'Condemned': 'badge red',
        'For Condemn': 'badge red',
        'For Repair': 'badge yellow',
        'No Property ID': 'badge purple'
    };
    return statusMap[status] || 'badge gray';
}

// SINGLE, UNIFIED CLICK LISTENER
window.addEventListener('click', function(event) {
    // 1. Close Modals
    const modals = ['addComputerModal', 'condemnModal', 'transferModal', 'missingIdModal', 'addFacilityAssetModal'];
    modals.forEach(id => {
        const modal = document.getElementById(id);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // 2. Close Filter Dropdown
    if (!event.target.matches('.filter-btn') && !event.target.closest('.filter-btn')) {
        const filterMenu = document.getElementById("filterMenu");
        if (filterMenu && filterMenu.classList.contains('show')) {
            filterMenu.classList.remove('show');
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
    const computerView = document.getElementById('view-computer');
    const facilityView = document.getElementById('view-facility');

    // --- NEW: Reset selections so the modal doesn't get confused ---
    currentEditingSetId = null;
    currentSelectedFAId = null;

    // Remove 'active' class from ALL list items in both sections
    document.querySelectorAll('.asset-item').forEach(item => item.classList.remove('active'));
    
    // --- ensure the facility right panel remains visible when the section is shown ---
    // we used to hide it here; removing that keeps the panel present with the default header text

    if (viewName === 'computer') {
        computerView.style.display = 'block';
        facilityView.style.display = 'none';
    } else if (viewName === 'facility') {
        computerView.style.display = 'none';
        facilityView.style.display = 'block';
        const right = document.getElementById('view-facility-right');
        if (right) right.style.display = 'block';
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
    const contents = document.querySelectorAll('.specs-content-box .tab-content');
    contents.forEach(content => {
        content.style.display = 'none';
    });

    // 2. Show the specific tab content requested
    const selectedContent = document.getElementById('tab-' + tabId);
    if (selectedContent) {
        selectedContent.style.display = 'block';
    } else {
        console.error('Tab content not found: tab-' + tabId);
    }

    // 3. Remove 'active' class from all tab buttons
    const buttons = document.querySelectorAll('.spec-tab');
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });

    // 4. Add 'active' class to the clicked button
    if (btnElement) {
        btnElement.classList.add('active');
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
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex'; 

        if (modalId === 'transferModal') {
            const labId = args[0];
            // Clear previous inputs/checkboxes before populating
            resetTransferModal();
            populateTransferModal(labId);
        }
    }
}

async function populateTransferModal(labId) {
    // 1. Reference all UI elements
    const unitBody = document.getElementById('transferUnitsTableBody');
    const assetBody = document.getElementById('transferAssetsTableBody');
    const targetLabSelect = document.getElementById('transfer_target_lab');
    const sourceInput = document.getElementById('transfer_source_room');

    // 2. Clear current state and show loading
    unitBody.innerHTML = '<tr><td colspan="3" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading Units...</td></tr>';
    assetBody.innerHTML = '<tr><td colspan="3" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading Assets...</td></tr>';
    targetLabSelect.innerHTML = '<option value="">Loading Labs...</option>';

    try {
        // 3. Fetch data from the PHP provider
        const response = await fetch(`includes/get_assets_for_transfer.php?lab_id=${labId}`);
        
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();

        // 4. Set the Source Room Name (e.g., "Computer Lab 1 (Room 104)")
        sourceInput.value = data.source_name || "Unknown Laboratory";

        // 5. Populate Computer Unit List
        if (data.units && data.units.length > 0) {
            unitBody.innerHTML = data.units.map(unit => `
                <tr>
                    <td>
                        <label class="check-container">
                            <input type="checkbox" class="transfer-unit-checkbox" value="${unit.set_id}"> 
                            <span>PC-${unit.set_tag}</span>
                        </label>
                    </td>
                    <td>${unit.set_id}</td>
                    <td><span class="badge ${unit.set_status.toLowerCase() === 'working' ? 'green' : 'yellow'}">${unit.set_status}</span></td>
                </tr>
            `).join('');
        } else {
            unitBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color: #888;">No transferable units in this lab.</td></tr>';
        }

        // 6. Populate Facility Asset List
        if (data.facility && data.facility.length > 0) {
            assetBody.innerHTML = data.facility.map(asset => `
                <tr>
                    <td>
                        <label class="check-container">
                            <input type="checkbox" class="transfer-asset-checkbox" value="${asset.asset_id}"> 
                            <span>${asset.asset_tag}</span>
                        </label>
                    </td>
                    <td>${asset.asset_id}</td>
                    <td><span class="badge ${asset.asset_status.toLowerCase() === 'working' ? 'green' : 'yellow'}">${asset.asset_status}</span></td>
                </tr>
            `).join('');
        } else {
            assetBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color: #888;">No facility assets found.</td></tr>';
        }

        // 7. Populate Target Lab Dropdown (Name (Room) formatting)
        targetLabSelect.innerHTML = '<option value="">Select Target Lab</option>';
        if (data.labs && data.labs.length > 0) {
            data.labs.forEach(lab => {
                const option = document.createElement('option');
                option.value = lab.lab_id;
                option.textContent = lab.full_display; // Formatted as "Name (Room)" from PHP
                targetLabSelect.appendChild(option);
            });
        } else {
            targetLabSelect.innerHTML = '<option value="">No available labs for transfer</option>';
        }

    } catch (error) {
        console.error('Transfer Modal Error:', error);
        const errorRow = '<tr><td colspan="3" style="text-align:center; color: red;">Failed to load data.</td></tr>';
        unitBody.innerHTML = errorRow;
        assetBody.innerHTML = errorRow;
        targetLabSelect.innerHTML = '<option value="">Error loading labs</option>';
    }
}

// Utility to clear modal state
function resetTransferModal() {
    document.querySelectorAll('#transferModal input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('#transferModal .modal-textarea').forEach(ta => ta.value = '');
    const select = document.querySelector('#transferModal .custom-select');
    if (select) select.selectedIndex = 0;
}

function filterTransferList(tbodyId, query) {
    const rows = document.querySelectorAll(`#${tbodyId} tr`);
    const q = query.toLowerCase().trim();

    rows.forEach(row => {
        if (row.cells.length < 2) return; 

        // 2. Perform the search
        const text = row.innerText.toLowerCase();
        const isMatch = text.includes(q);
        row.style.display = isMatch ? '' : 'none';

        if (!isMatch) {
            const cb = row.querySelector('input[type="checkbox"]');
            if (cb) cb.checked = false;
        }
    });

    const selectAllId = (tbodyId === 'transferUnitsTableBody') ? 'selectAllUnits' : 'selectAllAssets';
    const selectAllCb = document.getElementById(selectAllId);
    if (selectAllCb) selectAllCb.checked = false;
}

function toggleTransferSelection(type) {
    const isChecked = type === 'unit' 
        ? document.getElementById('selectAllUnits').checked 
        : document.getElementById('selectAllAssets').checked;
    
    const selector = type === 'unit' ? '.transfer-unit-checkbox' : '.transfer-asset-checkbox';
    
    document.querySelectorAll(selector).forEach(cb => {
        // Only toggle checkboxes that aren't hidden by a search filter
        if (cb.closest('tr').style.display !== 'none') {
            cb.checked = isChecked;
        }
    });
}

async function submitTransfer() {
    const targetLabId = document.getElementById('transfer_target_lab').value;
    const remarks = document.getElementById('transfer_remarks').value;
    
    // 1. Collect selected Units
    const selectedUnits = Array.from(document.querySelectorAll('.transfer-unit-checkbox:checked'))
                               .map(cb => cb.value);
                               
    // 2. Collect selected Assets
    const selectedAssets = Array.from(document.querySelectorAll('.transfer-asset-checkbox:checked'))
                                .map(cb => cb.value);

    // 3. Collect checked reasons (Changed selector to match standard naming)
    // Ensure your HTML container has id="transfer_actions"
    const actions = Array.from(document.querySelectorAll('#transfer_actions input[type="checkbox"]:checked'))
                         .map(cb => cb.value);

    // Validation
    if (!targetLabId) {
        showNotification('Required', 'Please select a destination laboratory.', 'error');
        return;
    }
    if (selectedUnits.length === 0 && selectedAssets.length === 0) {
        showNotification('Selection Empty', 'Please select at least one item to transfer.', 'error');
        return;
    }

    // Prepare Data - KEYS UPDATED TO MATCH PHP
    const formData = new FormData();
    formData.append('target_lab_id', targetLabId);
    formData.append('remarks', remarks);
    formData.append('actions', JSON.stringify(actions)); // Changed 'reasons' to 'actions'
    formData.append('units', JSON.stringify(selectedUnits));
    formData.append('assets', JSON.stringify(selectedAssets));

    try {
        const response = await fetch('includes/process_transfer.php', {
            method: 'POST',
            body: formData
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
            closeModal('transferModal');
            if (typeof reloadWithToast === "function") {
                reloadWithToast('Success', 'Assets transferred and audit trail updated.', 'success');
            } else {
                location.reload();
            }
        } else {
            // Use result.error to match the PHP's catch block
            showNotification('Error', result.error || 'Transfer failed.', 'error');
        }
    } catch (error) {
        console.error('Transfer Error:', error);
        showNotification('Server Error', error.message || 'Could not connect to the server.', 'error');
    }
}

// Helper to get status badge HTML
function getStatusBadge(status) {
    const badgeClass = status === 'Working' ? 'green' : status === 'For Repair' ? 'yellow' : 'red';
    return `<span class="badge ${badgeClass}">${status}</span>`;
}
/**
 * Switch Modal Tabs
 */
function switchModalTab(tabId, btnElement) {
    // Hide all modal tab contents
    const contents = document.querySelectorAll('.modal-tab-content');
    contents.forEach(content => {
        content.style.display = 'none';
    });

    // Show selected
    const selectedContent = document.getElementById(tabId);
    if (selectedContent) selectedContent.style.display = 'block';

    // Update active button state inside the modal nav
    const buttons = document.querySelectorAll('.modal-tabs-nav .spec-tab');
    buttons.forEach(btn => btn.classList.remove('active'));
    if (btnElement) btnElement.classList.add('active');
}
// Close the Modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * ENABLE/DISABLE TAB BUTTONS
 */
function setTabsDisabled(disabled) {
    const tabs = document.querySelectorAll('.specs-content-box .spec-tab');
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
    const buttons = group.querySelectorAll('.status-btn');
    
    // 3. Turn OFF 'active' class for all buttons in this group
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    // 4. Turn ON 'active' class ONLY for the clicked button
    clickedBtn.classList.add('active');
}

// ==========================================
// A. COMPUTER UNITS SEARCH & FILTER
// ==========================================
let currentComputerFilter = 'All';

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
    const items = document.querySelectorAll('#assetListContainer .asset-item');
    
    items.forEach(item => {
        const nameEl = item.querySelector('.item-name');
        const badgeEl = item.querySelector('.badge');
        if (!nameEl || !badgeEl) return;

        const name = nameEl.textContent.toLowerCase();
        const status = badgeEl.textContent.trim();
        
        const matchesSearch = name.includes(filterText);
        const matchesFilter = (currentComputerFilter === 'All' || status === currentComputerFilter);
        
        item.style.display = (matchesSearch && matchesFilter) ? 'flex' : 'none';
    });
}

// ==========================================
// B. FACILITY ASSETS SEARCH & FILTER
// ==========================================
let currentFAFilter = 'All';

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
    const items = document.querySelectorAll('#facilityListContainer .asset-item');
    
    items.forEach(item => {
        const nameEl = item.querySelector('.item-name');
        const badgeEl = item.querySelector('.badge');
        if (!nameEl || !badgeEl) return;

        const name = nameEl.textContent.toLowerCase();
        const status = badgeEl.textContent.trim();
        
        const matchesSearch = name.includes(filterText);
        const matchesFilter = (currentFAFilter === 'All' || status === currentFAFilter);
        
        item.style.display = (matchesSearch && matchesFilter) ? 'flex' : 'none';
    });
}

/**
 * ------------------------------------------------------------------
 * 7. UNIVERSAL EDIT MODE TOGGLE 
 * ------------------------------------------------------------------
 */

// Variable to store which PC is currently active
let currentSelectedUnitId = '';

function selectFacilityAsset(element, assetId) {
    currentSelectedFAId = assetId;
    currentEditingSetId = null; // Clear Computer ID

    document.querySelectorAll('#facilityListContainer .asset-item').forEach(item => item.classList.remove('active'));
    if (element) element.classList.add('active');

    // 1. Remove the 'active' class from all items in the list
    const allItems = document.querySelectorAll('.asset-item');
    allItems.forEach(item => {
        item.classList.remove('active');
    });

    // 2. Add the 'active' class to the clicked row
    element.classList.add('active');

    // 3. (Optional) You can update the right panel title here
    const unitName = element.querySelector('.item-name').innerText;
    document.querySelector('.right-panel .section-header-row h3').innerText = unitName + " Details";

    console.log("Selected Set ID:", currentSelectedUnitId);
    // In the future, you can make an AJAX call here to fetch the specific specs for this set_id
}

/**
 * Detects the first available unit number by scanning the existing list.
 */
function calculateNextUnitNumber() {
    const unitElements = document.querySelectorAll('#assetListContainer .item-name');
    let existingNumbers = [];
    
    unitElements.forEach(el => {
        const text = el.innerText;
        // Only target actual PC units
        if (text.includes('PC-')) {
            const match = text.match(/\d+/);
            if (match) existingNumbers.push(parseInt(match[0], 10));
        }
    });
    
    existingNumbers.sort((a, b) => a - b);
    availableNumbersList = [];
    
    // Build the next 50 available gaps/numbers
    for (let i = 1; i <= 200; i++) {
        if (!existingNumbers.includes(i)) {
            availableNumbersList.push(i.toString().padStart(2, '0'));
        }
        if (availableNumbersList.length >= 50) break;
    }

    // Always trigger the UI update immediately after calculating
    updateBulkUnitNumbers();
}

/**
 * Opens the add computer modal with default settings.
 */
function openAddModal() {
    document.getElementById('bulk_count').value = 2;
    toggleAddMode('single'); 
    document.getElementById('addComputerModal').style.display = 'flex';
}

/**
 * Calculates the computer age based on the purchase date in the add modal.
 */
function calculateComputerAge() {
    const dateInput = document.getElementById('purchase_date_input');
    const displayInput = document.getElementById('computer_age_display');

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
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        
        dateInput.value = `${yyyy}-${mm}-${dd}`;
        purchaseDate = today; // Reset our calculation variable to today
    }

    // Calculate the difference in years
    let years = today.getFullYear() - purchaseDate.getFullYear();
    let months = today.getMonth() - purchaseDate.getMonth();

    // Adjust down by 1 year if the exact purchase month/day hasn't happened yet this year
    if (months < 0 || (months === 0 && today.getDate() < purchaseDate.getDate())) {
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
        return activeBtn.getAttribute('data-type') === 'repair' ? 'For Repair' : 'Working';
    }
    return 'Working'; // Fallback just in case
}

function submitNewUnit() {
    const labRoom = document.getElementById('room_number_input').value;
    const urlParams = new URLSearchParams(window.location.search);
    const labId = urlParams.get('lab_id') || 0; 

    // Determine tags to send
    let unitTags = [];
    if (isBulkMode) {
        const count = parseInt(document.getElementById('bulk_count').value) || 2;
        unitTags = availableNumbersList.slice(0, count);
    } else {
        unitTags = [document.getElementById('smart_unit_no').value];
    }

    if (unitTags.length === 0) return;

    // ... [Keep your status toggle collection] ...
    const statusButtons = document.querySelectorAll('.status-btn.active');
    let statusArray = [];
    statusButtons.forEach(btn => statusArray.push(btn.getAttribute('data-type')));

    const formData = new FormData();
    // Send the array of tags as a JSON string
    formData.append('unit_tags', JSON.stringify(unitTags)); 
    formData.append('lab_id', labId);
    formData.append('lab_room', labRoom);
    statusArray.forEach(status => formData.append('statuses[]', status));

    // --- NEW: APPEND SPECS DATA ---
    formData.append('property_id', document.getElementById('spec_property').value);
    formData.append('cpu', document.getElementById('spec_cpu').value);
    formData.append('brand', document.getElementById('spec_brand').value);
    formData.append('os', document.getElementById('spec_os').value);
    formData.append('purchase_date', document.getElementById('purchase_date_input').value);
    formData.append('gpu', document.getElementById('spec_gpu').value);
    formData.append('ram', document.getElementById('spec_ram').value);
    formData.append('storage', document.getElementById('spec_storage').value);
    formData.append('capacity', document.getElementById('spec_capacity').value);

    // --- NEW: APPEND PORTS DATA ---
    formData.append('usb_ports', document.getElementById('usb_ports_count').value);
    formData.append('usb_status', getActiveToggle('usb_toggle'));
    formData.append('wifi_status', getActiveToggle('wifi_toggle'));
    formData.append('mic_status', getActiveToggle('mic_toggle'));
    formData.append('hdmi_status', getActiveToggle('hdmi_toggle'));
    formData.append('headphone_status', getActiveToggle('headphone_toggle'));
    formData.append('display_status', getActiveToggle('display_toggle'));
    formData.append('inline_status', getActiveToggle('inline_toggle'));
    formData.append('ethernet_status', getActiveToggle('ethernet_toggle'));

    // --- NEW: APPEND HEALTH DATA ---
    let rawAgeStr = document.getElementById('computer_age_display').value;
    let comAge = parseInt(rawAgeStr); 
    if (isNaN(comAge)) comAge = 0;

    formData.append('com_age', comAge);
    formData.append('disk_health', getActiveToggle('disk_toggle'));
    formData.append('num_repair', document.getElementById('num_repair_input').value || 0);
    formData.append('power_health', getActiveToggle('power_toggle'));

    // --- NEW: APPEND PERIPHERALS DATA ---
    formData.append('monitor_property', document.getElementById('monitor_property_input').value);
    formData.append('monitor_brand', document.getElementById('monitor_brand_input').value);
    formData.append('monitor_status', getActiveToggle('monitor_toggle'));

    formData.append('mouse_brand', document.getElementById('mouse_brand_input').value);
    formData.append('mouse_status', getActiveToggle('mouse_toggle'));

    formData.append('keyboard_brand', document.getElementById('keyboard_brand_input').value);
    formData.append('keyboard_status', getActiveToggle('keyboard_toggle'));

    formData.append('avr_brand', document.getElementById('avr_brand_input').value);
    formData.append('avr_status', getActiveToggle('avr_toggle'));

fetch('includes/insert_unit.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal, save toast to memory, and reload!
            closeModal('addComputerModal'); 
            reloadWithToast('Units Added', 'New units were added successfully.', 'success');
        } else {
            showNotification('Database Error', data.error, 'error');
        }
    })
    .catch(error => showNotification('Connection Error', 'Failed to save units.', 'error'));
}

let isBulkMode = false;
let availableNumbersList = []; // Stores the next available sequence

function toggleAddMode(mode) {
    const circleSingle = document.getElementById('circle_single');
    const circleMultiple = document.getElementById('circle_multiple');
    const bulkContainer = document.getElementById('bulk_input_container');
    const propIdInput = document.getElementById('spec_property');

    if (mode === 'single') {
        isBulkMode = false;
        circleSingle.classList.add('checked');
        circleMultiple.classList.remove('checked');
        bulkContainer.style.display = 'none';
        
        propIdInput.disabled = false;
        propIdInput.style.background = '#fff';
    } else {
        isBulkMode = true;
        circleMultiple.classList.add('checked');
        circleSingle.classList.remove('checked');
        bulkContainer.style.display = 'flex';
        
        propIdInput.value = '';
        propIdInput.disabled = true;
        propIdInput.style.background = '#f4f4f4';
    }
    
    // Crucial fix: Always recalculate and update the screen when switching modes
    calculateNextUnitNumber(); 
}

function updateBulkUnitNumbers() {
    const inputField = document.getElementById('smart_unit_no');
    
    if (!isBulkMode) {
        // Single Mode: Just show the first available number
        inputField.value = availableNumbersList[0] || '';
    } else {
        // Bulk Mode: Read the quantity input and show the sequence
        let count = parseInt(document.getElementById('bulk_count').value, 10);
        
        // Safety net if the user deletes the number
        if (isNaN(count) || count < 1) {
            count = 1; 
        }
        
        const previewNumbers = availableNumbersList.slice(0, count);
        inputField.value = previewNumbers.join(', ');
    }
}

// ==========================================
// 5. RIGHT PANEL: VIEW & EDIT DETAILS (AJAX)
// ==========================================

function selectUnit(element, setId) {
    currentEditingSetId = setId;

    document.querySelectorAll('#assetListContainer .asset-item').forEach(item => item.classList.remove('active'));
    if (element) element.classList.add('active');

    const unitName = element ? element.querySelector('.item-name').innerText : 'Unit';
    document.querySelector('.right-panel .section-header-row h3').innerHTML = `${unitName} Details`;

    // Enable the tabs now that a unit is selected
    setTabsDisabled(false);

    fetch(`includes/get_unit_details.php?set_id=${setId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateRightPanel(data.data);
            resetUIToViewMode(); // Safely resets UI WITHOUT triggering another fetch
        } else {
            console.error("Error fetching details:", data.error);
        }
    })
    .catch(error => console.error('Fetch Error:', error));
}

function populateRightPanel(data) {
    for (const [key, value] of Object.entries(data)) {
        
        // 1. Text & Inputs (view_specs_cpu / edit_specs_cpu)
        const viewEl = document.getElementById('view_' + key);
        const editEl = document.getElementById('edit_' + key);

        if (viewEl) {
            if(key === 'com_age') {
                viewEl.innerText = value !== null ? value + ' Years' : '0 Years';
            } else {
                viewEl.innerText = value || 'N/A';
            }
        }
        if (editEl) editEl.value = value || '';

        // 2. Status Pills & Toggles (pill_usb_status / toggle_usb_status)
        const toggleGroup = document.getElementById('toggle_' + key);
        if (toggleGroup) {
            const pill = document.getElementById('pill_' + key);
            if (pill) {
                pill.innerText = value || 'Unknown';
                let pillColor = 'purple';
                if(value === 'Working') pillColor = 'green';
                if(value === 'For Condemn') pillColor = 'red';
                if(value === 'For Repair') pillColor = 'orange';
                pill.className = `status-pill view-mode ${pillColor}`;
            }

            toggleGroup.querySelectorAll('.status-btn').forEach(btn => {
                btn.classList.remove('active');
                const targetType = (value === 'For Repair') ? 'repair' : 'working';
                if (btn.getAttribute('data-type') === targetType) btn.classList.add('active');
            });
        }
    }
}

function toggleEditMode() {
    if (!currentEditingSetId) {
        alert("Please select a unit from the list first.");
        return;
    }

    const btn = document.getElementById('editToggleButton');
    const textSpan = document.getElementById('editText');
    const btnCancel = document.getElementById('btnCancelEdit');

    if (textSpan.innerText === "Edit") {
        // TURN ON EDIT MODE
        textSpan.innerText = "Save";
        btn.innerHTML = `<i class="fas fa-save"></i> <span id="editText">Save</span>`;
        btn.style.backgroundColor = "#4caf50"; 
        if (btnCancel) btnCancel.style.display = 'inline-block';

        // Hide static text, show input fields
        document.querySelectorAll('.specs-content-box .view-mode').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.specs-content-box .edit-mode').forEach(el => {
            el.style.display = el.classList.contains('status-toggle-group') ? 'flex' : 'block';
        });
    } else {
        // USER CLICKED "SAVE"
        saveUnitDetails();
    }
}

function cancelEditMode() {
    // Only re-fetch if the user actually clicks the "Cancel" button to wipe out unsaved typing
    if (currentEditingSetId) {
        const activeItem = document.querySelector('#assetListContainer .asset-item.active');
        selectUnit(activeItem, currentEditingSetId);
    }
}

function saveUnitDetails() {
    const formData = new FormData();
    formData.append('set_id', currentEditingSetId);

    // Gather text from all the inputs
    document.querySelectorAll('.specs-content-box input[id^="edit_"]').forEach(input => {
        const dbColumn = input.id.replace('edit_', '');
        formData.append(dbColumn, input.value);
    });

    // Gather values from all the "Working / For Repair" toggles
    document.querySelectorAll('.specs-content-box .status-toggle-group[id^="toggle_"]').forEach(group => {
        const dbColumn = group.id.replace('toggle_', '');
        const activeBtn = group.querySelector('.status-btn.active');
        const val = activeBtn && activeBtn.getAttribute('data-type') === 'repair' ? 'For Repair' : 'Working';
        formData.append(dbColumn, val);
    });

fetch('includes/update_unit.php', { method: 'POST', body: formData })
    .then(res => res.json())
.then(data => {
        if(data.success) {
            reloadWithToast('Update Successful', 'Changes to the unit have been saved.', 'success');
        } else {
            showNotification('Update Failed', data.error, 'error');
        }
    })
    .catch(err => showNotification('Connection Error', 'Failed to update unit.', 'error'));
}

function resetUIToViewMode() {
    const btn = document.getElementById('editToggleButton');
    const btnCancel = document.getElementById('btnCancelEdit');

    if (btn) {
        btn.innerHTML = `<i class="fas fa-pen"></i> <span id="editText">Edit</span>`;
        btn.style.backgroundColor = ""; 
    }
    if (btnCancel) btnCancel.style.display = "none";

    document.querySelectorAll('.specs-content-box .view-mode').forEach(el => el.style.display = '');
    document.querySelectorAll('.specs-content-box .edit-mode').forEach(el => el.style.display = 'none');
}

// ==========================================
// RIGHT PANEL AGE CALCULATOR
// ==========================================

/**
 * Calculates the computer age for the edit mode based on the purchase date.
 */
function calculateEditComputerAge() {
    const dateInput = document.getElementById('edit_specs_purchase');
    const ageInput = document.getElementById('edit_com_age');
    
    if (!dateInput || !dateInput.value || !ageInput) return;

    let purchaseDate = new Date(dateInput.value);
    const today = new Date();
    
    today.setHours(0, 0, 0, 0);
    purchaseDate.setHours(0, 0, 0, 0);

    // Prevent future dates
    if (purchaseDate > today) {
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        dateInput.value = `${yyyy}-${mm}-${dd}`;
        purchaseDate = today;
    }

    let years = today.getFullYear() - purchaseDate.getFullYear();
    let months = today.getMonth() - purchaseDate.getMonth();
    
    if (months < 0 || (months === 0 && today.getDate() < purchaseDate.getDate())) {
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
    let assetTag = 'Unknown Item';

    // 1. Reliable View Detection
    const viewFacility = document.getElementById('view-facility');
    const viewComputer = document.getElementById('view-computer');
    
    // offsetParent is null if the element or any parent is display:none
    const isFacilityView = viewFacility && viewFacility.offsetParent !== null;
    const isComputerView = viewComputer && viewComputer.offsetParent !== null;

    // 2. Assign ID based on the VISIBLE view
    if (isFacilityView) {
        activeId = currentSelectedFAId;
        // Find the active name in the facility list
        const activeItem = document.querySelector('#view-facility .asset-item.active .item-name');
        if (activeItem) assetTag = activeItem.innerText;
    } else if (isComputerView) {
        activeId = currentEditingSetId;
        // Find the active name in the computer list
        const activeItem = document.querySelector('#view-computer .asset-item.active .item-name');
        if (activeItem) assetTag = activeItem.innerText;
    }

    // 3. Validation
    if (!activeId) {
        showNotification('Selection Required', 'Please select an item from the current list first.', 'error');
        return;
    }

    // 4. Populate Modal Fields
    document.getElementById('condemn_display_name').innerText = assetTag;
    document.getElementById('condemn_set_tag').value = assetTag;
    document.getElementById('condemn_set_id').value = activeId;

    // 5. Update Labels
    const modalTitle = document.getElementById('condemn_modal_title');
    const tagLabel = document.getElementById('condemn_tag_label');
    const idLabel = document.getElementById('condemn_id_label');

    if (isFacilityView) {
        modalTitle.innerText = 'Condemn this Asset?';
        tagLabel.innerText = 'Asset Tag:';
        idLabel.innerText = 'Asset ID:';
    } else {
        modalTitle.innerText = 'Condemn this Unit?';
        tagLabel.innerText = 'Set Tag:';
        idLabel.innerText = 'Set ID:';
    }

    // 6. Reset fields
    document.querySelectorAll('input[name="condemn_reason"]').forEach(cb => cb.checked = false);
    document.getElementById('condemn_remarks').value = '';

    openModal('condemnModal');
}
function submitCondemnAction() {
    // 1. Get the ID from the modal input (where we just stored it in openCondemnModal)
    const setId = document.getElementById('condemn_set_id').value;
    const remarks = document.getElementById('condemn_remarks').value;
    
    // 2. Collect reasons
    const reasons = [];
    document.querySelectorAll('input[name="condemn_reason"]:checked').forEach(cb => {
        reasons.push(cb.value);
    });

    // 3. Validation
    if (reasons.length === 0 && remarks.trim() === "") {
        showNotification('Validation Error', 'Please select a reason or provide remarks.', 'error');
        return;
    }

    // 4. Determine Target
    const isFacilityView = document.getElementById('view-facility').offsetParent !== null;
    const targetUrl = isFacilityView ? 'includes/condemn_facility_asset.php' : 'includes/condemn_unit.php';

    // 5. Build Form Data
    const formData = new FormData();
    if (isFacilityView) {
        formData.append('asset_id', setId); // PHP expects asset_id
    } else {
        formData.append('set_id', setId);   // PHP expects set_id
    }
    formData.append('reasons', JSON.stringify(reasons));
    formData.append('remarks', remarks);

    // 6. Execution
    fetch(targetUrl, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeModal('condemnModal');
            // Using location.reload() to refresh the lists
            location.reload(); 
        } else {
            showNotification('Database Error', data.error || 'Update failed', 'error');
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        showNotification('Connection Error', 'Failed to reach server.', 'error');
    });
}

// ==========================================
// 7. FINALIZE DEPLOYMENT LOGIC (Missing IDs)
// ==========================================

function openMissingIdModal(roomNumber) {
    console.log("1. Fetching missing IDs for Room:", roomNumber);

    fetch(`includes/get_missing_ids.php?room=${roomNumber}`)
    .then(res => res.text()) // Read as raw text to catch PHP errors
    .then(text => {
        console.log("2. Server responded with:", text);
        try {
            const data = JSON.parse(text);
            
            if(data.success) {
                console.log("3. Data parsed successfully! Building table...");
                const tbody = document.getElementById('missingIdsTableBody');
                tbody.innerHTML = '';
                document.getElementById('missing_count_text').innerText = data.units.length;
                
                data.units.forEach(unit => {
                    const sysId = unit.specs_property || '';
                    const monId = unit.monitor_property || '';
                    
                    let badgeClass = 'pending';
                    let badgeText = 'Pending';
                    
                    if (sysId && monId) {
                        badgeClass = (unit.status === 'For Repair') ? 'yellow' : 'green';
                        badgeText = unit.status;
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="font-weight: bold; font-size: 13px; color: #333;">PC-${unit.set_tag}</td>
                        <td><input type="text" class="serial-input sys-input" data-id="${unit.set_id}" value="${sysId}" placeholder="Enter Serial..." oninput="checkSerialInputs(this)"></td>
                        <td><input type="text" class="serial-input mon-input" data-id="${unit.set_id}" value="${monId}" placeholder="Enter Serial..." oninput="checkSerialInputs(this)"></td>
                        <td><span class="badge ${badgeClass} status-badge" data-original-status="${unit.status}">${badgeText}</span></td>
                    `;
                    tbody.appendChild(tr);
                });
                console.log("4. Opening Modal!");
                openModal('missingIdModal');
            } else {
                alert("Database Error: " + data.error);
            }
        } catch(e) {
            console.error("JSON Parse Error: The server did not return valid JSON.", e);
            alert("Server error occurred. Please press F12 and check the Console tab.");
        }
    })
    .catch(err => {
        console.error("Fetch request totally failed:", err);
    });
}

// Dynamically updates the badge when the user finishes typing both IDs
function checkSerialInputs(inputElement) {
    const tr = inputElement.closest('tr');
    const sysVal = tr.querySelector('.sys-input').value.trim();
    const monVal = tr.querySelector('.mon-input').value.trim();
    const badge = tr.querySelector('.status-badge');
    const origStatus = badge.getAttribute('data-original-status');

    if (sysVal !== '' && monVal !== '') {
        badge.className = `badge ${origStatus === 'For Repair' ? 'yellow' : 'green'} status-badge`;
        badge.innerText = origStatus;
    } else {
        badge.className = 'badge pending status-badge';
        badge.innerText = 'Pending';
    }
}

function finalizeDeployment() {
    const rows = document.querySelectorAll('#missingIdsTableBody tr');
    let payload = [];

    // Gather the data
    rows.forEach(tr => {
        const sysInput = tr.querySelector('.sys-input');
        const monInput = tr.querySelector('.mon-input');
        
        if(sysInput && monInput) {
            payload.push({
                set_id: sysInput.getAttribute('data-id'),
                specs_property: sysInput.value.trim(),
                monitor_property: monInput.value.trim()
            });
        }
    });

    if (payload.length === 0) {
        alert("No units found to update!");
        return;
    }

    // FIX: Use FormData instead of a raw JSON body, so the server accepts it!
    const formData = new FormData();
    formData.append('payload', JSON.stringify(payload));

fetch('includes/save_missing_ids.php', { method: 'POST', body: formData })
    .then(res => res.text()) 
    .then(text => {
        try {
            const data = JSON.parse(text);
            if(data.success) {
                closeModal('missingIdModal');
                reloadWithToast('Deployment Finalized', 'Property IDs successfully assigned.', 'success');
            } else {
                showNotification('Finalization Failed', data.error, 'error');
            }
        } catch(e) {
            showNotification('Server Error', 'Invalid response from server.', 'error');
        }
    })
    .catch(err => showNotification('Connection Error', 'Failed to finalize deployment.', 'error'));
}

// ==========================================
// 10. FACILITY ASSETS LOGIC
// ==========================================

// This function dynamically changes the background color of the status select box
function updateFAStatusColor(selectElement) {
    if (selectElement.value === 'Working') {
        selectElement.style.backgroundColor = '#e8f5e9'; // Light Green
        selectElement.style.color = '#2e7d32'; // Dark Green
        selectElement.style.borderColor = '#c8e6c9';
    } else if (selectElement.value === 'For Repair') {
        selectElement.style.backgroundColor = '#fff3e0'; // Light Orange
        selectElement.style.color = '#e65100'; // Dark Orange
        selectElement.style.borderColor = '#ffe0b2';
    }
}

// Opens the modal and fetches the next available FA-XX tag
function openFacilityAssetModal() {
    // 1. Get the lab_id from the URL (e.g., assets_management.php?lab_id=12)
    const urlParams = new URLSearchParams(window.location.search);
    const labId = urlParams.get('lab_id'); 
    
    if (!labId) {
        showNotification('System Error', 'Lab ID missing from URL', 'error');
        return;
    }

    // Set loading state in the tag input
    document.getElementById('fa_set_tag').value = "Loading...";

    // 2. Fetch the next available ID/Tag using the lab_id
    fetch(`includes/get_next_fa_tag.php?lab_id=${labId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Set the short tag (e.g., "01") in the input
                document.getElementById('fa_set_tag').value = data.next_tag;
                // Store the full string ID (e.g., "FA-0001-2026") in a global variable
                window.currentPendingFAId = data.next_id; 
            } else {
                showNotification('Error', 'Could not generate next tag', 'error');
            }
        })
        .catch(err => showNotification('Connection Error', 'Server is unreachable', 'error'));

    // 3. Open modal using 'flex' so your CSS centering works
    document.getElementById('addFacilityAssetModal').style.display = 'flex';
}

function submitFacilityAsset() {
    const urlParams = new URLSearchParams(window.location.search);
    const labId = urlParams.get('lab_id');
    
    // Grab elements
    const nameEl = document.getElementById('fa_asset_name');
    const propertyEl = document.getElementById('fa_asset_property');
    const brandEl = document.getElementById('fa_brand');
    const statusEl = document.getElementById('fa_status');
    const tagEl = document.getElementById('fa_set_tag');

    // --- COMPREHENSIVE ERROR HANDLING ---
    
    // 1. Check Device Name
    if (!nameEl.value.trim()) {
        showNotification('Input Required', 'Please enter a Device Name (e.g., Printer).', 'error');
        nameEl.focus();
        return;
    }

    // 2. Check Property ID
    if (!propertyEl.value.trim()) {
        showNotification('Input Required', 'Please enter the Property ID or Serial Number.', 'error');
        propertyEl.focus();
        return;
    }

    // 3. Check Brand
    if (!brandEl.value.trim()) {
        showNotification('Input Required', 'Please enter a Brand (use "N/A" if unknown).', 'error');
        brandEl.focus();
        return;
    }

    // 4. Check Lab ID (System check)
    if (!labId) {
        showNotification('System Error', 'Laboratory context lost. Please refresh the page.', 'error');
        return;
    }

    // 5. Check generated ID
    if (!window.currentPendingFAId) {
        showNotification('System Error', 'Asset ID not generated. Please re-open the modal.', 'error');
        return;
    }

    // --- PREPARE DATA ---
    const formData = new FormData();
    formData.append('asset_id', window.currentPendingFAId); 
    formData.append('asset_tag', tagEl.value); 
    formData.append('asset_name', nameEl.value.trim());
    formData.append('asset_property', propertyEl.value.trim());
    formData.append('asset_brand', brandEl.value.trim());
    formData.append('asset_status', statusEl.value);
    formData.append('lab_id', labId);

    // Visual feedback: Disable button to prevent double submission
    const submitBtn = document.querySelector('.btn-finalize');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

    fetch('includes/add_facility_asset.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            closeModal('addFacilityAssetModal');
            // Clear fields for next use
            nameEl.value = '';
            propertyEl.value = '';
            brandEl.value = '';
            reloadWithToast('Asset Added', 'New asset successfully created.', 'success');
        } else {
            // Error from PHP (e.g., duplicate property ID)
            showNotification('Database Error', data.error || 'Failed to save asset.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Connection Error', 'Failed to reach server. Check your connection.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}

function selectFacilityAsset(element, assetId) {
    // 1. Update Global ID immediately
    currentSelectedFAId = assetId; 
    currentEditingSetId = null; 

    // 2. UI: Active List Item
    document.querySelectorAll('.asset-item').forEach(item => item.classList.remove('active'));
    if (element) element.classList.add('active');

    // 3. Show Right Panel
    const rightPanel = document.getElementById('view-facility-right');
    rightPanel.style.display = 'block';

    // 4. Fetch Details
    fetch(`includes/get_facility_asset_details.php?asset_id=${assetId}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const asset = data.data;

            // Update Labels
            document.getElementById('view_fa_header_title').innerText = `FA-${asset.asset_tag} - ${asset.asset_name} Details`;
            document.getElementById('view_fa_tag').innerText = asset.asset_property || 'N/A';
            document.getElementById('view_fa_brand').innerText = asset.asset_brand || 'N/A';
            document.getElementById('view_fa_status').innerText = asset.asset_status;

            // 5. Update Status Box Colors
            const statusBox = document.getElementById('view_fa_status_box');
            // Remove all possible status classes first
            statusBox.classList.remove('status-box-green', 'status-box-yellow', 'status-box-red');
            statusBox.classList.add('detail-box'); 

            // Matching logic
            if (asset.asset_status === 'Working') {
                statusBox.classList.add('status-box-green');
            } else if (asset.asset_status === 'For Repair') {
                statusBox.classList.add('status-box-yellow'); // This links to your CSS
            } else {
                statusBox.classList.add('status-box-red');
            }

            // Optional: Reload Activity Log Table here if you have that function
            // fetchFAActivityLog(assetId);

        } else {
            showNotification('Error', data.error, 'error');
        }
    })
    .catch(err => console.error('Fetch error:', err));
}

// Helper to switch CSS classes for buttons
function updateStatusUI(status) {
    const btnWorking = document.getElementById('status_btn_working');
    const btnRepair = document.getElementById('status_btn_repair');

    // Reset and Toggle classes
    btnWorking.classList.toggle('active-working', status === 'Working');
    btnRepair.classList.toggle('active-repair', status === 'For Repair');
}

// Helper functions for Edit mode toggling (We will wire up the actual Save later)
function toggleFAEditMode() {
    // We will build this in the next step!
    alert("Edit mode UI triggered! We will build the save logic next.");
}

function cancelFAEditMode() {
    document.querySelectorAll('.view-mode-fa').forEach(el => el.style.display = '');
    document.querySelectorAll('.edit-mode-fa').forEach(el => el.style.display = 'none');
    
    const btn = document.getElementById('editFABtn');
    if(btn) {
        btn.innerHTML = `<i class="fas fa-pen"></i> <span id="editFAText">Edit</span>`;
        btn.style.backgroundColor = "#4caf50";
    }
    const cancelBtn = document.getElementById('cancelFAEditBtn');
    if(cancelBtn) cancelBtn.style.display = 'none';
}

async function processTransfer() {
    // 1. Get Form Values
    const targetLabId = document.getElementById('transfer_target_lab').value;
    const remarks = document.getElementById('transfer_remarks').value;
    
    // 2. Collect Checked Units (matches the tbody id in your HTML)
    const selectedUnits = Array.from(document.querySelectorAll('.transfer-unit-checkbox:checked'))
                               .map(cb => cb.value);
                               
    // 3. Collect Checked Assets
    const selectedAssets = Array.from(document.querySelectorAll('.transfer-asset-checkbox:checked'))
                                .map(cb => cb.value);

    // 4. Collect Actions/Reasons (matches id="transfer_actions" in your HTML)
    const actions = Array.from(document.querySelectorAll('#transfer_actions input[type="checkbox"]:checked'))
                         .map(cb => cb.value);

    // 5. Validation
    if (!targetLabId) {
        showNotification('Required', 'Please select a target laboratory.', 'error');
        return;
    }
    if (selectedUnits.length === 0 && selectedAssets.length === 0) {
        showNotification('Selection Empty', 'Please select at least one item to transfer.', 'error');
        return;
    }

    // 6. Data Preparation
    const formData = new FormData();
    formData.append('target_lab_id', targetLabId);
    formData.append('remarks', remarks);
    formData.append('actions', JSON.stringify(actions));
    formData.append('units', JSON.stringify(selectedUnits));
    formData.append('assets', JSON.stringify(selectedAssets));

    try {
        const response = await fetch('includes/process_transfer.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();

        if (result.success) {
            closeModal('transferModal');
            if (typeof reloadWithToast === "function") {
                reloadWithToast('Success', 'Transfer completed and history logged.', 'success');
            } else {
                location.reload();
            }
        } else {
            showNotification('Error', result.error || 'Transfer failed.', 'error');
        }
    } catch (error) {
        console.error('Transfer Error:', error);
        showNotification('Server Error', 'Failed to connect to the server.', 'error');
    }
}

// 1. Open Edit Mode
function openFAEditMode() {
    if (!currentSelectedFAId) return; // Ensure an asset is selected

    // Toggle visibility
    document.getElementById('fa-view-mode').style.display = 'none';
    document.getElementById('fa-edit-mode').style.display = 'block';

    // 1. Get the text from the View Header (e.g., "FA-01 - Television Details")
    const fullHeader = document.getElementById('view_fa_header_title').innerText;

    // 2. Set the Edit Mode Header to match exactly
    document.getElementById('edit_fa_header_title').innerText = fullHeader;

    // 3. Extract ONLY the asset name for the input field
    let assetName = fullHeader.includes(' - ') ? fullHeader.split(' - ')[1] : fullHeader;
    assetName = assetName.replace(' Details', '').trim();

    // 4. Populate the text inputs
    document.getElementById('edit_fa_name').value = assetName;
    document.getElementById('edit_fa_property').value = document.getElementById('view_fa_tag').innerText;
    document.getElementById('edit_fa_brand').value = document.getElementById('view_fa_brand').innerText;

    // 5. Handle Status Button UI
    const currentStatus = document.getElementById('view_fa_status').innerText.trim();
    
    // First, remove 'active' class from both buttons
    const buttons = document.querySelectorAll('#fa-edit-mode .status-btn');
    buttons.forEach(btn => btn.classList.remove('active'));

    // Find the button that matches the current status and activate it
    buttons.forEach(btn => {
        if (btn.getAttribute('data-type') === currentStatus) {
            btn.classList.add('active');
        }
    });
}

// 2. Close Edit Mode (Cancel)
function closeFAEditMode() {
    document.getElementById('fa-view-mode').style.display = 'block';
    document.getElementById('fa-edit-mode').style.display = 'none';
}



function toggleStatusFA(element) {
    // 1. Remove 'active' from all buttons in this specific group
    const parent = element.parentElement;
    parent.querySelectorAll('.status-btn').forEach(btn => btn.classList.remove('active'));

    // 2. Add 'active' to the clicked button
    element.classList.add('active');
    
    // Note: In your saveFAChanges(), you should now pull the value from 
    // the button that has the .active class.
}

function showFacilityAssets() {
    // 1. Hide other sections (e.g., Computer Units)
    document.getElementById('computer-units-section').style.display = 'none';
    
    // 2. Show the Facility section
    document.getElementById('facility-assets-section').style.display = 'block';

    // 3. THE MISSING PIECE: Actually go get the data!
    loadFacilityAssets(); 
}
async function saveFAChanges() {
    const saveBtn = document.querySelector('#fa-edit-mode .btn-edit');
    const name = document.getElementById('edit_fa_name').value.trim();
    const property = document.getElementById('edit_fa_property').value.trim();
    const brand = document.getElementById('edit_fa_brand').value.trim();
    
    // Get status from the active toggle button
    const activeBtn = document.querySelector('#fa-edit-mode .status-btn.active');
    const status = activeBtn ? activeBtn.getAttribute('data-type') : 'Working';

    if (!name || !property) {
        showNotification('Required', 'Device Name and Property ID cannot be empty.', 'error');
        return;
    }

    // Start Loading State
    const originalBtnHTML = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const formData = new FormData();
    formData.append('asset_id', currentSelectedFAId);
    formData.append('asset_name', name);
    formData.append('asset_property', property);
    formData.append('asset_brand', brand);
    formData.append('asset_status', status);

    try {
        const response = await fetch('includes/update_facility_asset.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();

        if (result.success) {
            reloadWithToast('Updated', 'Asset details saved.', 'success');
        } else {
            showNotification('Error', result.error, 'error');
        }
    } catch (error) {
        console.error('Save Error:', error);
        showNotification('Error', 'Could not reach server.', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalBtnHTML;
    }
}

async function loadFacilityAssets(idToSelect = null) {
    try {
        // 1. Get the lab_id from the URL so we query the right room
        const urlParams = new URLSearchParams(window.location.search);
        const labId = urlParams.get('lab_id') || 0;

        // 2. Fetch fresh data using the lab_id
        const response = await fetch(`includes/get_facility_assets_list.php?lab_id=${labId}&t=${Date.now()}`);
        const data = await response.json();

        if (data.success) {
            // 3. Target the EXACT ID from your HTML
            const listContainer = document.getElementById('facilityListContainer');
            if (!listContainer) return;

            listContainer.innerHTML = ''; // Wipe the old list

            if (data.assets.length === 0) {
                listContainer.innerHTML = "<div style='padding: 20px; text-align: center; color: #666;'>No facility assets found for this lab.</div>";
                return;
            }

            data.assets.forEach(asset => {
                const isActive = (idToSelect && asset.asset_id == idToSelect) ? 'active' : '';
                
                // 4. Match the Badge classes perfectly to your PHP
                let badgeClass = 'green';
                if (asset.asset_status === 'For Repair') badgeClass = 'yellow';
                if (asset.asset_status === 'Condemned' || asset.asset_status === 'For Condemn') badgeClass = 'red';

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
        console.error('Error refreshing list:', error);
    }
}