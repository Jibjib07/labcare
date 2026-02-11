document.addEventListener('DOMContentLoaded', function() {
    
    // --- Existing Modal Logic ---
    const modal = document.getElementById("addLabModal");
    const openBtn = document.getElementById("openModalBtn");
    const openBtnMobile = document.getElementById("openModalBtnMobile");
    const closeBtn = document.getElementById("closeModalBtn");
    const cancelBtn = document.getElementById("cancelModalBtn");

    function openModal() { modal.style.display = "flex"; }
    function closeModal() { modal.style.display = "none"; }

    if(openBtn) openBtn.addEventListener("click", openModal);
    if(openBtnMobile) openBtnMobile.addEventListener("click", openModal);
    if(closeBtn) closeBtn.addEventListener("click", closeModal);
    if(cancelBtn) cancelBtn.addEventListener("click", closeModal);

    window.addEventListener("click", function(event) {
        if (event.target == modal) closeModal();
    });

    // --- NEW: Redirect Logic for View Button ---
    const viewButtons = document.querySelectorAll('.view-btn');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            // In a real app, you'd pass the Room ID here (e.g., ?room=104)
            window.location.href = 'assets_management.php?room=104';
        });
    });
});