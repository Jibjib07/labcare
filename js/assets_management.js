/**
 * Switch Main Views (Computer vs Facility)
 */
function switchView(viewName) {
    const computerView = document.getElementById('view-computer');
    const facilityView = document.getElementById('view-facility');
    
    if (viewName === 'computer') {
        computerView.style.display = 'block';
        facilityView.style.display = 'none';
    } else {
        computerView.style.display = 'none';
        facilityView.style.display = 'block';
    }
}

/**
 * Switch Specification Tabs (Identity, External, Health, Peripherals)
 * @param {string} tabId - The ID of the content div to show
 * @param {HTMLElement} btnElement - The button that was clicked
 */
function switchTab(tabId, btnElement) {
    // 1. Hide all tab content divs
    const contents = document.querySelectorAll('.specs-content-box .tab-content');
    contents.forEach(content => {
        content.style.display = 'none';
    });

    // 2. Show the selected tab content
    const selectedContent = document.getElementById('tab-' + tabId);
    if (selectedContent) {
        selectedContent.style.display = 'block';
    }

    // 3. Remove 'active' class from all buttons
    const buttons = document.querySelectorAll('.spec-tab');
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });

    // 4. Add 'active' class to clicked button
    if (btnElement) {
        btnElement.classList.add('active');
    }
}