/**
 * Switch History Tabs (Visual Toggle Only)
 * Handles the pill-shaped buttons: Maintenance Logs | System Archives | Asset Retirement
 */
function switchHistoryTab(tabName, btnElement) {
    // 1. Get all toggle buttons in the left panel
    const buttons = document.querySelectorAll('.toggle-container .toggle-link');
    
    // 2. Remove 'active' class from all
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    // 3. Add 'active' class to clicked button
    if (btnElement) {
        btnElement.classList.add('active');
    }

    // 4. Console log for data fetching hook
    console.log("Switched to tab: " + tabName);
}