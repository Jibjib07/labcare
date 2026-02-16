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