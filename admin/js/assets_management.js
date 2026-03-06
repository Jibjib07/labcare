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

// Global Window Click: Close modal if user clicks on the dark overlay (background)
window.onclick = function(event) {
    const modal = document.getElementById('addComputerModal');
    // Check if the element clicked IS the modal container (the dark background), not the form inside
    if (event.target === modal) {
        modal.style.display = 'none';
    }
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

// Global Window Click: Close modals and dropdowns if clicking outside
window.onclick = function(event) {
    // 1. Close Modal Logic (Handles BOTH modals now)
    const addModal = document.getElementById('addComputerModal');
    const condemnModal = document.getElementById('condemnModal');
    
    if (event.target === addModal) {
        addModal.style.display = 'none';
    }
    if (event.target === condemnModal) {
        condemnModal.style.display = 'none';
    }

    // 2. Close Filter Dropdown Logic
    if (!event.target.matches('.filter-btn') && !event.target.closest('.filter-btn')) {
        const filterMenu = document.getElementById("filterMenu");
        if (filterMenu && filterMenu.classList.contains('show')) {
            filterMenu.classList.remove('show');
        }
    }
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

// Global Window Click: Close modals and dropdowns if clicking outside
window.onclick = function(event) {
    // Array of all modal IDs
    const modals = ['addComputerModal', 'condemnModal', 'transferModal'];
    
    // Close Modals
    modals.forEach(id => {
        const modal = document.getElementById(id);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Close Filter Dropdown Logic
    if (!event.target.matches('.filter-btn') && !event.target.closest('.filter-btn')) {
        const filterMenu = document.getElementById("filterMenu");
        if (filterMenu && filterMenu.classList.contains('show')) {
            filterMenu.classList.remove('show');
        }
    }
}

/**
 * ------------------------------------------------------------------
 * 7. UNIVERSAL EDIT MODE TOGGLE 
 * ------------------------------------------------------------------
 */
let isEditMode = false;

function toggleEditMode() {
    isEditMode = !isEditMode;
    
    const btn = document.getElementById('editToggleButton');
    const btnResolve = document.getElementById('btnResolve');
    const btnCondemn = document.getElementById('btnCondemn');
    const btnCancel = document.getElementById('btnCancelEdit');

    const viewModes = document.querySelectorAll('.specs-content-box .view-mode');
    const editModes = document.querySelectorAll('.specs-content-box .edit-mode');

    if (isEditMode) {
        // --- SWITCH TO EDIT MODE ---
        btn.innerHTML = '<i class="fas fa-save"></i> <span id="editText">Save</span>';
        btn.classList.add('btn-save-active');
        
        // Hide Resolve/Condemn, Show Cancel
        if (btnResolve) btnResolve.style.display = 'none';
        if (btnCondemn) btnCondemn.style.display = 'none';
        if (btnCancel) btnCancel.style.display = 'flex';
        
        viewModes.forEach(el => el.style.display = 'none');
        editModes.forEach(el => {
            el.style.display = el.classList.contains('status-toggle-group') ? 'flex' : 'block';
        });
        
    } else {
        // --- SAVE AND SWITCH TO VIEW MODE ---
        btn.innerHTML = '<i class="fas fa-pen"></i> <span id="editText">Edit</span>';
        btn.classList.remove('btn-save-active');
        
        // Show Resolve/Condemn, Hide Cancel
        if (btnResolve) btnResolve.style.display = 'flex';
        if (btnCondemn) btnCondemn.style.display = 'flex';
        if (btnCancel) btnCancel.style.display = 'none';
        
        // Save Data Sync
        document.querySelectorAll('.detail-group').forEach(group => {
            // 1. Status Toggles
            const toggleGroup = group.querySelector('.status-toggle-group.edit-mode');
            const viewPill = group.querySelector('.status-pill.view-mode');
            if (toggleGroup && viewPill) {
                const activeBtn = toggleGroup.querySelector('.status-btn.active');
                if (activeBtn) {
                    const statusText = activeBtn.innerText;
                    viewPill.innerText = statusText;
                    viewPill.className = 'status-pill view-mode'; 
                    if (statusText === 'Working') viewPill.classList.add('green');
                    else if (statusText === 'For Repair') viewPill.classList.add('orange');
                    else viewPill.classList.add('red');
                }
            }

            // 2. Text Inputs
            const inputField = group.querySelector('.edit-input.edit-mode');
            const viewBox = group.querySelector('.detail-box.view-mode');
            if (inputField && viewBox) {
                if (inputField.parentElement.innerText.includes("Years")) {
                    viewBox.innerText = inputField.value + " Years";
                } else {
                    viewBox.innerText = inputField.value;
                }
            }
        });

        viewModes.forEach(el => el.style.display = 'block');
        editModes.forEach(el => el.style.display = 'none');
    }
}

// Cancels the edit without saving changes
function cancelEditMode() {
    isEditMode = false;
    
    const btn = document.getElementById('editToggleButton');
    const btnResolve = document.getElementById('btnResolve');
    const btnCondemn = document.getElementById('btnCondemn');
    const btnCancel = document.getElementById('btnCancelEdit');

    const viewModes = document.querySelectorAll('.specs-content-box .view-mode');
    const editModes = document.querySelectorAll('.specs-content-box .edit-mode');

    // Revert Buttons
    btn.innerHTML = '<i class="fas fa-pen"></i> <span id="editText">Edit</span>';
    btn.classList.remove('btn-save-active');
    if (btnResolve) btnResolve.style.display = 'flex';
    if (btnCondemn) btnCondemn.style.display = 'flex';
    if (btnCancel) btnCancel.style.display = 'none';

    // Discard input changes by resetting inputs to the original view text
    document.querySelectorAll('.detail-group').forEach(group => {
        const inputField = group.querySelector('.edit-input.edit-mode');
        const viewBox = group.querySelector('.detail-box.view-mode');
        
        if (inputField && viewBox) {
            let originalText = viewBox.innerText.replace(' Years', '').trim();
            inputField.value = originalText;
        }

        const toggleGroup = group.querySelector('.status-toggle-group.edit-mode');
        const viewPill = group.querySelector('.status-pill.view-mode');
        
        if (toggleGroup && viewPill) {
            const btns = toggleGroup.querySelectorAll('.status-btn');
            btns.forEach(b => b.classList.remove('active'));
            const originalBtn = Array.from(btns).find(b => b.innerText === viewPill.innerText);
            if (originalBtn) originalBtn.classList.add('active');
        }
    });

    // Hide inputs, show text
    viewModes.forEach(el => el.style.display = 'block');
    editModes.forEach(el => el.style.display = 'none');
}

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

    fetch('includes/insert_unit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Success! Unit saved.');
            location.reload();
        } else {
            alert('Database Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
    });
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