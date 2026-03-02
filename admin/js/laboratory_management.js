
/**
 * ------------------------------------------------------------------
 * 1. MODAL FUNCTIONS
 * Handles opening and closing the modals.
 * ------------------------------------------------------------------
 */

// Opens the modal by its ID
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // 'flex' is usually best for centering modal content, but use 'block' if your CSS requires it
        modal.style.display = 'flex'; 
    }
}

// Closes the modal by its ID
function closeModal(modalId) {
    if (modalId && typeof modalId === 'string') {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    const scheduleModal = document.getElementById('scheduleModal');
    if (scheduleModal) {
        scheduleModal.style.display = 'none';
    }

    // 3. Always re-enable scrolling just in case
    document.body.style.overflow = "auto";
}

// Bonus: Close the modal if the user clicks anywhere outside the white box (on the dark overlay)
window.addEventListener('click', function(event) {
    // You can add other modal IDs here if you have them (like editLabModal or archiveLabModal)
    const addLabModal = document.getElementById('addLabModal');
    
    if (event.target === addLabModal) {
        closeModal('addLabModal');
    }
});

// Global Click Listener: Close modal if user clicks outside the modal content
window.onclick = function(event) {
    const addModal = document.getElementById('addLabModal');
    const editModal = document.getElementById('editLabModal');
    const archiveModal = document.getElementById('archiveLabModal'); // Added this
    
    if (event.target === addModal) addModal.style.display = 'none';
    if (event.target === editModal) editModal.style.display = 'none';
    if (event.target === archiveModal) archiveModal.style.display = 'none'; // Added this
}

/**
 * ------------------------------------------------------------------
 * 2. EDIT MODAL LOGIC (Populate Data)
 * Triggered by the inline onclick="..." on the Edit Button
 * ------------------------------------------------------------------
 */
function openEditModal(button) {
    // 1. Grab the data attributes attached to the button we clicked
    const roomName = button.getAttribute('data-name');
    const roomNumber = button.getAttribute('data-room');
    const totalUnits = button.getAttribute('data-units');

    // 2. Fill the inputs inside the modal
    document.getElementById('edit_room_name').value = roomName;
    document.getElementById('edit_room_number').value = roomNumber;
    document.getElementById('edit_total_units').value = totalUnits;
    
    // 3. Fill the hidden input so the PHP knows which record to update
    document.getElementById('original_room_number').value = roomNumber;

    // 4. Open the modal
    openModal('editLabModal'); // Assuming your modal wrapper has id="editLabModal"
}
/**
 * ------------------------------------------------------------------
 * 3. ARCHIVE MODAL LOGIC (Populate Data)
 * Triggered by the inline onclick="..." on the Archive Button
 * ------------------------------------------------------------------
 */
function openArchiveModal(buttonElement) {
    // 1. Get data from the clicked button
    const roomName = buttonElement.getAttribute('data-name');
    const roomNumber = buttonElement.getAttribute('data-room');
    const totalUnits = buttonElement.getAttribute('data-units');

    // 2. Populate the modal inputs
    document.getElementById('archive_room_name').value = roomName;
    document.getElementById('archive_room_number').value = roomNumber;
    document.getElementById('archive_total_units').value = totalUnits;
    
    // 3. Update the warning text
    document.getElementById('archive_room_name_display').textContent = roomName;

    // 4. Show the modal
    openModal('archiveLabModal');
}

/**
 * ------------------------------------------------------------------
 * 1. GLOBAL STATE & HELPERS
 * ------------------------------------------------------------------
 */
let currentSelectedRoom = ''; 

// Helper to safely update text in both Desktop and Mobile views
function updateUI(id, val) {
    const el = document.getElementById(id);
    if(el) el.textContent = val || 0;
}

/**
 * ------------------------------------------------------------------
 * 2. SELECT ROOM & DISPLAY LOGIC (Live Stats + Schedule)
 * ------------------------------------------------------------------
 */
function selectRoom(element, roomNumber) {
    currentSelectedRoom = roomNumber;

    // A. Visual Selection: Remove 'active' from all, add to currently clicked
    document.querySelectorAll('.room-item, .m-room-card').forEach(el => el.classList.remove('active'));
    if (element) element.classList.add('active');

    // B. Update Schedule Title
    const st = document.getElementById('schedule-title');
    if(st) st.textContent = 'Room ' + roomNumber + ' Schedule';

    const schedDisplay = document.getElementById('schedule-display');
    if(schedDisplay) {
        schedDisplay.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:#8c52ff; font-size: 1.5rem;"></i>';
    }

    // C. Fetch Data from Database
    fetch(`../includes/get_room_stats.php?room=${roomNumber}`)
        .then(response => response.json())
        .then(data => {
            // 1. Update Desktop Stats
            updateUI('val-working', data.working);
            updateUI('val-repair', data.repair);
            updateUI('val-condemned', data.condemn);
            updateUI('val-total', data.total_units);
            updateUI('val-assets', data.total_assets);

            // 2. Update Mobile Stats
            updateUI('m-val-working', data.working);
            updateUI('m-val-repair', data.repair);
            updateUI('m-val-condemned', data.condemn);
            updateUI('m-val-total', data.total_units);

            // 3. Display Schedule Image (Fits 300px, No Scroll)
            if (data.schedule && data.schedule !== '') {
               schedDisplay.innerHTML = `
        <img src="${data.schedule}" 
             class="schedule-img-fit" 
             style="cursor: zoom-in;" 
             onclick="openScheduleModal(this.src)">`;
            } else {
                schedDisplay.innerHTML = `<p style="color: #999; font-size: 0.9rem;">No schedule uploaded for Room ${roomNumber}.</p>`;
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            if(schedDisplay) schedDisplay.innerHTML = '<p style="color:red;">Error loading data.</p>';
        });
}

/**
 * ------------------------------------------------------------------
 * 3. SCHEDULE UPLOAD (PREVIEW & AJAX)
 * ------------------------------------------------------------------
 */
const scheduleInput = document.getElementById('scheduleInput');
const scheduleDisplay = document.getElementById('schedule-display');

if (scheduleInput && scheduleDisplay) {
    scheduleInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (!currentSelectedRoom) {
                alert("Please select a room first!");
                this.value = '';
                return;
            }

            // 1. Instant UI Feedback: Local Preview (Same fit-logic as DB load)
            const reader = new FileReader();
            reader.onload = function(event) {
                scheduleDisplay.innerHTML = `
                    <img src="${event.target.result}" class="schedule-img-fit" style="opacity: 0.4;">
                    <div id="upload-status" style="position: absolute; background: rgba(140, 82, 255, 0.9); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem;">
                        <i class="fas fa-sync fa-spin"></i> Saving...
                    </div>`;
            };
            reader.readAsDataURL(file);

            // 2. AJAX Upload
            const formData = new FormData();
            formData.append('schedule_image', file);
            formData.append('room_number', currentSelectedRoom); 

            fetch('includes/upload_schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                 scheduleDisplay.innerHTML = `
        <img src="${data.file_path}" 
             class="schedule-img-fit" 
             style="cursor: zoom-in;" 
             onclick="openScheduleModal(this.src)">`;
                } else {
                    alert("Upload failed: " + data.error);
                    selectRoom(null, currentSelectedRoom); 
                }
            })
            .catch(error => {
                console.error("Upload error:", error);
                selectRoom(null, currentSelectedRoom);
            });
        }
    });
}

/**
 * ------------------------------------------------------------------
 * 4. AUTO-SELECT FIRST ITEM ON LOAD
 * ------------------------------------------------------------------
 */
document.addEventListener('DOMContentLoaded', function() {
    // Look for the first row in your table/list
    const firstRoom = document.querySelector('.room-item'); 
    if (firstRoom) {
        // This triggers the click event automatically
        firstRoom.click(); 
    }
});

let currentQRRoom = '';
 
function openEditModal(button) {
    // Grab the data, including the lab_id
    const labId = button.getAttribute('data-id');
    const roomName = button.getAttribute('data-name');
    const roomNumber = button.getAttribute('data-room');
    const totalUnits = button.getAttribute('data-units');

    // Fill the Edit Modal inputs
    document.getElementById('edit_lab_id').value = labId; // Storing the ID!
    document.getElementById('edit_room_name').value = roomName;
    document.getElementById('edit_room_number').value = roomNumber;
    document.getElementById('edit_total_units').value = totalUnits;
    document.getElementById('original_room_number').value = roomNumber;

    openModal('editLabModal');
}

// 2. When clicking the QR button inside the Edit Modal:
function openQrModal() {
    // Steal the ID and Room Number right out of the Edit Modal's hidden fields!
    let labId = document.getElementById('edit_lab_id').value;
    let roomNumber = document.getElementById('edit_room_number').value;

    if (!labId) {
        console.error("No Lab ID found!");
        return;
    }

    currentQRRoom = roomNumber;

    closeModal('editLabModal');
    
    // Update the QR Modal Title with the Room Number
    document.getElementById('qrModalTitle').innerText = `Room ${roomNumber} - QR Code`;
    
    // Clear old QR code
    const qrContainer = document.getElementById('qrcode-container');
    qrContainer.innerHTML = ''; 

    // Build the URL using the lab_id!
    let basePath = window.location.href.substring(0, window.location.href.lastIndexOf("/"));
    let targetUrl = `${basePath}/assets_management.php?lab_id=${labId}`;

    // Generate QR
    new QRCode(qrContainer, {
        text: targetUrl,
        width: 200,
        height: 200,
        colorDark: "#8c52ff",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });

    // Open QR Modal
    openModal('qrModal');
}

// Function to download the QR code when "Export" is clicked
function exportQRCode() {
    // Find the canvas image generated by the library
    const qrCanvas = document.querySelector('#qrcode-container canvas');
    
    if (qrCanvas) {
        // Convert the canvas to a downloadable image URL
        const imageUrl = qrCanvas.toDataURL("image/png");
        
        // Create a temporary link to trigger the download
        const downloadLink = document.createElement("a");
        downloadLink.href = imageUrl;
        downloadLink.download = `LabCare_Room_${currentQRRoom}_QR.png`;
        
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
}

function openScheduleModal(imgSrc) {
    const modal = document.getElementById('scheduleModal');
    const modalImg = document.getElementById('modalImg');
    
    if(modal && modalImg) {
        modalImg.src = imgSrc;
        modal.style.display = "flex"; // Shows the modal
        document.body.style.overflow = "hidden"; // Stops the background from scrolling
    }
}


function searchLaboratories() {
    // 1. Get the search query and make it lowercase
    const input = document.getElementById("labSearchInput");
    const filter = input.value.toLowerCase();
    
    // 2. Grab all the laboratory items (Desktop rows and Mobile cards)
    const items = document.querySelectorAll('.room-item, .m-room-card');
    
    // 3. Loop through each item
    items.forEach(item => {
        // Get the text content (Room Number and Name)
        const textValue = item.textContent || item.innerText;
        
        // 4. If the text includes the search query, show it; otherwise, hide it
        if (textValue.toLowerCase().includes(filter)) {
            // Check if it's a table row or a div to maintain layout
            if (item.tagName === 'TR') {
                item.style.display = ''; // Default for table rows
            } else {
                item.style.display = 'flex'; // Default for mobile cards
            }
        } else {
            item.style.display = 'none';
        }
    });
}