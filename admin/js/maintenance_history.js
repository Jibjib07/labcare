/**
 * Switch History Tabs (Visual Toggle Only)
 * Handles the pill-shaped buttons: Maintenance Logs | System Archives | Asset Retirement
 */
function switchHistoryTab(tabName, btnElement) {
  // 1. Visual Toggle (Matches your current CSS classes)
  const buttons = document.querySelectorAll(".toggle-link");
  buttons.forEach((btn) => btn.classList.remove("active"));
  btnElement.classList.add("active");

  // 2. Content Toggle (The new logic)
  // Hide all sections
  document.querySelectorAll(".tab-content").forEach((tab) => {
    tab.style.display = "none";
  });

  // Show the one you clicked
  const target = document.getElementById(tabName + "-tab");
  if (target) {
    target.style.display = "block";
  }
}
