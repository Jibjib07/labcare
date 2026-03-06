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
    // Get the main container elements
    const computerView = document.getElementById('view-computer');
    const facilityView = document.getElementById('view-facility');
    
    // Get the toggle buttons (optional, if you want to force active state via JS)
    // Note: The HTML usually handles the 'active' class via PHP or static HTML, 
    // but this ensures the view logic works.

    if (viewName === 'computer') {
        // Show Computer View
        computerView.style.display = 'block';
        facilityView.style.display = 'none';
        
        // Update URL (Optional: helps if user refreshes page)
        // history.pushState(null, null, '?view=computer');

    } else if (viewName === 'facility') {
        // Show Facility View
        computerView.style.display = 'none';
        facilityView.style.display = 'block';
        
        // Update URL (Optional)
        // history.pushState(null, null, '?view=facility');
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
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex'; // Uses flex to center the content
    }
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

/**
 * ------------------------------------------------------------------
 * 4. FILTER MENU LOGIC
 * ------------------------------------------------------------------
 */

// Toggle the visibility of the dropdown
function toggleFilterMenu() {
    document.getElementById("filterMenu").classList.toggle("show");
}

// Filter the asset list items based on the badge text
function filterAssets(status) {
    const items = document.querySelectorAll('.asset-item');
    
    items.forEach(item => {
        const badge = item.querySelector('.badge');
        if (!badge) return;
        
        const badgeText = badge.textContent.trim();
        
        // Show if "All" is selected or if the badge text matches exactly
        if (status === 'All' || badgeText === status) {
            item.style.display = 'flex'; // Use flex to retain your original styling
        } else {
            item.style.display = 'none'; // Hide non-matching items
        }
    });

    // Close the dropdown menu after selecting an option
    document.getElementById("filterMenu").classList.remove("show");
}


/**
 * ------------------------------------------------------------------
 * 5. SEARCH LOGIC
 * Filters the asset list based purely on the unit name (e.g., PC-01)
 * ------------------------------------------------------------------
 */
function searchAssets() {
    // 1. Get the search query and make it lowercase for case-insensitive matching
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    
    // 2. Grab all the asset items in the list
    const items = document.querySelectorAll('.asset-list .asset-item');
    
    // 3. Loop through each item to check its name
    items.forEach(item => {
        const itemNameElement = item.querySelector('.item-name');
        
        if (itemNameElement) {
            const textValue = itemNameElement.textContent || itemNameElement.innerText;
            
            // 4. If the name includes the search text, show it. Otherwise, hide it.
            if (textValue.toLowerCase().includes(filter)) {
                item.style.display = 'flex'; // Keep your flex styling intact
            } else {
                item.style.display = 'none';
            }
        }
    });
}

/**
 * ------------------------------------------------------------------
 * 7. UNIVERSAL EDIT MODE TOGGLE 
 * ------------------------------------------------------------------
 */

// Variable to store which PC is currently active
let currentSelectedUnitId = '';

function selectUnit(element, setId) {
    currentSelectedUnitId = setId;

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
 * Detects the first available unit number by scanning the existing list
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

function openAddModal() {
    document.getElementById('bulk_count').value = 2;
    toggleAddMode('single'); 
    document.getElementById('addComputerModal').style.display = 'flex';
}

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
// 6. CONDEMN UNIT LOGIC
// ==========================================

function openCondemnModal() {
    if (!currentEditingSetId) {
        alert("Please select a unit from the list first.");
        return;
    }

    // Get the active unit's name from the list
    const activeItem = document.querySelector('#assetListContainer .asset-item.active .item-name');
    const setTag = activeItem ? activeItem.innerText : 'Unknown Unit';

    // Populate the modal fields
    document.getElementById('condemn_display_name').innerText = setTag;
    document.getElementById('condemn_set_tag').value = setTag;
    document.getElementById('condemn_set_id').value = currentEditingSetId;

    // Reset the checkboxes and remarks
    document.querySelectorAll('input[name="condemn_reason"]').forEach(cb => cb.checked = false);
    document.getElementById('condemn_remarks').value = '';

    openModal('condemnModal');
}

function submitCondemn() {
    const setId = document.getElementById('condemn_set_id').value;
    let reasons = [];
    document.querySelectorAll('input[name="condemn_reason"]:checked').forEach(cb => reasons.push(cb.value));
    const remarks = document.getElementById('condemn_remarks').value;

    // Validation using the new Toast system
    if (reasons.length === 0 && remarks.trim() === "") {
        showNotification('Validation Error', 'Please select a reason or provide remarks before condemning.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('set_id', setId);
    formData.append('reasons', JSON.stringify(reasons));
    formData.append('remarks', remarks);

    fetch('includes/condemn_unit.php', { method: 'POST', body: formData })
    .then(res => res.json())
.then(data => {
        if (data.success) {
            closeModal('condemnModal');
            reloadWithToast('Unit Condemned', 'Unit has been permanently marked as Condemned.', 'success');
        } else {
            showNotification('Database Error', data.error, 'error');
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        showNotification('Connection Error', 'Failed to connect to the server.', 'error');
    });
}

// ==========================================
// 7. RIGHT PANEL AGE CALCULATOR
// ==========================================
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
// 8. FINALIZE DEPLOYMENT LOGIC (Missing IDs)
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
    const brandEl = document.getElementById('fa_brand');
    const statusEl = document.getElementById('fa_status');
    const tagEl = document.getElementById('fa_set_tag');

    // Validation check
    if (!nameEl.value.trim()) {
        showNotification('Input Required', 'Please enter a Device Name', 'error');
        return;
    }

    if (!window.currentPendingFAId) {
        showNotification('System Error', 'Asset ID not generated. Re-open modal.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('asset_id', window.currentPendingFAId); 
    formData.append('asset_tag', tagEl.value); 
    formData.append('asset_name', nameEl.value);
    formData.append('asset_brand', brandEl.value);
    formData.append('asset_status', statusEl.value);
    formData.append('lab_id', labId);

    fetch('includes/add_facility_asset.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            closeModal('addFacilityAssetModal');
            reloadWithToast('Asset Added', 'New asset successfully created.', 'success');
        } else {
            showNotification('Database Error', data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Connection Error', 'Failed to reach server.', 'error');
    });
}

let currentSelectedFAId = null;

function selectFacilityAsset(element, assetId) {
    // 1. Manage Active Class in the Left List
    document.querySelectorAll('#facilityListContainer .asset-item').forEach(item => {
        item.classList.remove('active');
    });
    if (element) {
        element.classList.add('active');
    }

    // 2. Show the Right Panel
    const rightPanel = document.getElementById('view-facility-right');
    rightPanel.style.display = 'block';

    // 3. Fetch data from your PHP file
    fetch(`includes/get_facility_asset_details.php?asset_id=${assetId}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const asset = data.data;

            // Update Text Placeholders
            // Format: FA-01 - Television
            document.getElementById('view_fa_header_title').innerText = `FA-${asset.asset_tag} - ${asset.asset_name}`;
            document.getElementById('view_fa_tag').innerText = `FA-${asset.asset_tag}`;
            document.getElementById('view_fa_brand').innerText = asset.asset_brand || 'N/A';
            document.getElementById('view_fa_status').innerText = asset.asset_status;

            // Update Status Colors Dynamically
            const statusBox = document.getElementById('view_fa_status_box');
            
            // Remove existing status classes first
            statusBox.className = 'detail-box'; 

            if (asset.asset_status === 'Working') {
                statusBox.classList.add('status-box-green');
            } else if (asset.asset_status === 'For Repair') {
                statusBox.classList.add('status-box-yellow');
            } else {
                statusBox.classList.add('status-box-red');
            }

        } else {
            console.error('Error:', data.error);
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