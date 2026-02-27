document.addEventListener('DOMContentLoaded', function() {
    
    // --- Elements ---
    const toggleBtn = document.getElementById('mobile-menu-toggle');
    const closeBtn = document.getElementById('mobile-menu-close');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    // --- Functions ---
    
    function openMenu() {
        if(sidebar) sidebar.classList.add('active');
        if(overlay) overlay.classList.add('active');
        // Prevent body from scrolling when menu is open
        document.body.style.overflow = 'hidden'; 
    }

    function closeMenu() {
        if(sidebar) sidebar.classList.remove('active');
        if(overlay) overlay.classList.remove('active');
        // Restore body scrolling
        document.body.style.overflow = '';
    }

    // --- Event Listeners ---
    
    if(toggleBtn) {
        toggleBtn.addEventListener('click', openMenu);
    }

    if(closeBtn) {
        closeBtn.addEventListener('click', closeMenu);
    }

    if(overlay) {
        overlay.addEventListener('click', closeMenu);
    }

    // Optional: Close menu on window resize if switching to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });
});