/**
 * ------------------------------------------------------------------
 * 1. MODAL FUNCTIONS
 * ------------------------------------------------------------------
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'flex'; 
}

function closeModal(modalId) {
    if (modalId && typeof modalId === 'string') {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    }
    const scheduleModal = document.getElementById('scheduleModal');
    if (scheduleModal) scheduleModal.style.display = 'none';
    document.body.style.overflow = "auto";
}

window.onclick = function(event) {
    const scheduleModal = document.getElementById('scheduleModal');
    if (event.target === scheduleModal) closeModal('scheduleModal');
}

/**
 * ------------------------------------------------------------------
 * 2. GLOBAL STATE & HELPERS
 * ------------------------------------------------------------------
 */
let currentSelectedRoom = ''; 

function updateUI(id, val) {
    const el = document.getElementById(id);
    if(el) el.textContent = val || 0;
}

/**
 * ------------------------------------------------------------------
 * 3. SELECT ROOM & DISPLAY LOGIC (Live Stats + Schedule View)
 * ------------------------------------------------------------------
 */
function selectRoom(element, roomNumber) {
    currentSelectedRoom = roomNumber;

    document.querySelectorAll('.room-item, .m-room-card').forEach(el => el.classList.remove('active'));
    if (element) element.classList.add('active');

    const roomNameElement = element ? element.querySelector('h4, .lab-name') : null;
    const roomName = roomNameElement ? roomNameElement.textContent.trim() : roomNumber;
    const st = document.getElementById('schedule-title');
    
    if (st) {
        if (roomNumber.toLowerCase() === roomName.toLowerCase()) {
            st.textContent = roomName + ' Schedule';
        } else {
            st.textContent = 'Room ' + roomNumber + ' Schedule';
        }
    }

    const schedDisplay = document.getElementById('schedule-display');
    if(schedDisplay) {
        schedDisplay.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:#8c52ff; font-size: 1.5rem;"></i>';
    }

    fetch(`../includes/get_room_stats.php?room=${roomNumber}`)
        .then(response => response.json())
        .then(data => {
            // Update Stats
            updateUI('val-working', data.working);
            updateUI('val-repair', data.repair);
            updateUI('val-condemned', data.condemn);
            updateUI('val-total', data.total_units);
            updateUI('val-assets', data.total_assets);
            updateUI('m-val-working', data.working);
            updateUI('m-val-repair', data.repair);
            updateUI('m-val-condemned', data.condemn);
            updateUI('m-val-total', data.total_units);

            // Display Schedule Image (View Only)
            if (data.schedule && data.schedule !== '') {
                schedDisplay.innerHTML = `
                    <img src="${data.schedule}" 
                         class="schedule-img-fit" 
                         style="cursor: zoom-in;" 
                         onclick="openScheduleModal(this.src)">`;
            } else {
                schedDisplay.innerHTML = `<p style="color: #999; font-size: 0.9rem;">No schedule uploaded for this room.</p>`;
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            if (schedDisplay) schedDisplay.innerHTML = '<p style="color:red;">Error loading data.</p>';
        });
}

function openScheduleModal(imgSrc) {
    const modal = document.getElementById('scheduleModal');
    const modalImg = document.getElementById('modalImg');
    if(modal && modalImg) {
        modalImg.src = imgSrc;
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
    }
}

/**
 * ------------------------------------------------------------------
 * 4. SEARCH & AUTO-LOAD
 * ------------------------------------------------------------------
 */
function searchLaboratories() {
    const input = document.getElementById("labSearchInput") || document.getElementById("mobileLabSearchInput");
    const filter = input.value.toLowerCase();
    const items = document.querySelectorAll('.room-item, .m-room-card');
    
    items.forEach(item => {
        const textValue = item.textContent || item.innerText;
        if (textValue.toLowerCase().includes(filter)) {
            item.style.display = (item.tagName === 'TR') ? '' : 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const firstRoom = document.querySelector('.room-item'); 
    if (firstRoom) firstRoom.click(); 
});

function clearSchedule() {
    if (!currentSelectedRoom) return;

    if (confirm(`Are you sure you want to clear the schedule for ${currentSelectedRoom}?`)) {
        const formData = new FormData();
        formData.append('room_number', currentSelectedRoom);

        fetch('includes/clear_schedule.php', { 
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const activeEl = document.querySelector('.room-item.active, .m-room-card.active');
                selectRoom(activeEl, currentSelectedRoom);
            } else {
                alert("Error: " + data.error);
            }
        })
        .catch(error => {
            console.error("Path Error:", error);
            alert("Could not connect to the clear script.");
        });
    }
}

function openScheduleModal(imgSrc) {
    const modal = document.getElementById('scheduleModal');
    const modalImg = document.getElementById('modalImg');
    
    if(modal && modalImg) {
        modalImg.src = imgSrc;
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
    }
}

/**
 * ------------------------------------------------------------------
 * 5. SEARCH & AUTO-LOAD
 * ------------------------------------------------------------------
 */
function searchLaboratories() {
    const input = document.getElementById("labSearchInput") || document.getElementById("mobileLabSearchInput");
    const filter = input.value.toLowerCase();
    const items = document.querySelectorAll('.room-item, .m-room-card');
    
    items.forEach(item => {
        const textValue = item.textContent || item.innerText;
        if (textValue.toLowerCase().includes(filter)) {
            item.style.display = (item.tagName === 'TR') ? '' : 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const firstRoom = document.querySelector('.room-item'); 
    if (firstRoom) {
        firstRoom.click(); 
    }
});