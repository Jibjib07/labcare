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
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
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
 * 4. NAVIGATION LOGIC (View Button Only)
 * ------------------------------------------------------------------
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Select all view buttons
    const viewButtons = document.querySelectorAll('.view-btn');

    viewButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Stop the click from affecting parent elements
            e.stopPropagation(); 

            // 1. Get the specific room number from the clicked button
            const roomNumber = this.getAttribute('data-room');

            // 2. Redirect to Assets Management with that room number
            window.location.href = 'assets_management.php?room=' + roomNumber;
        });
    });
});

/**
 * ------------------------------------------------------------------
 * 5. ROW SELECTION LOGIC (Desktop & Mobile)
 * Updates stats when a row/card is clicked.
 * ------------------------------------------------------------------
 */

// Dummy data
const labData = {
    '104': { working: 42, repair: 8, condemned: 2, total: 52, assets: 10 },
    '105': { working: 35, repair: 5, condemned: 0, total: 40, assets: 12 },
    '106': { working: 30, repair: 2, condemned: 3, total: 35, assets: 8 },
    '107': { working: 48, repair: 1, condemned: 1, total: 50, assets: 15 },
    '108': { working: 25, repair: 5, condemned: 0, total: 30, assets: 5 },
    '109': { working: 40, repair: 3, condemned: 2, total: 45, assets: 9 },
    '110': { working: 42, repair: 0, condemned: 0, total: 42, assets: 11 }
};

function selectRoom(element, roomNumber) {
    // 1. Visual Selection: Remove 'active' from all, add to clicked
    // Handle Desktop Rows
    document.querySelectorAll('.room-item').forEach(row => row.classList.remove('active'));
    // Handle Mobile Cards
    document.querySelectorAll('.m-room-card').forEach(card => card.classList.remove('active'));

    // Add active class to the element that was clicked
    if (element) {
        element.classList.add('active');
    }

    // 2. Get Data
    const stats = labData[roomNumber] || { working: 0, repair: 0, condemned: 0, total: 0, assets: 0 };

    // 3. Update Desktop Stats
    const dw = document.getElementById('val-working');
    if(dw) dw.textContent = stats.working;
    
    const dr = document.getElementById('val-repair');
    if(dr) dr.textContent = stats.repair;
    
    const dc = document.getElementById('val-condemned');
    if(dc) dc.textContent = stats.condemned;
    
    const dt = document.getElementById('val-total');
    if(dt) dt.textContent = stats.total;
    
    const da = document.getElementById('val-assets');
    if(da) da.textContent = stats.assets;

    // 4. Update Mobile Stats
    const mw = document.getElementById('m-val-working');
    if(mw) mw.textContent = stats.working;
    
    const mr = document.getElementById('m-val-repair');
    if(mr) mr.textContent = stats.repair;
    
    const mc = document.getElementById('m-val-condemned');
    if(mc) mc.textContent = stats.condemned;
    
    const mt = document.getElementById('m-val-total');
    if(mt) mt.textContent = stats.total;

    // 5. Update Schedule Title (Desktop only)
    const st = document.getElementById('schedule-title');
    if(st) st.textContent = 'Room ' + roomNumber + ' Schedule';
}

document.addEventListener('DOMContentLoaded', function() {
    
    // ... your existing listeners for viewButtons and scheduleInput ...

    // --- AUTO-SELECT FIRST ROOM ---
    // This finds the very first room-item in your desktop list
    const firstRoom = document.querySelector('.room-item');
    
    if (firstRoom) {
        // We simulate a click on the first element
        // We use 'click()' because it triggers your existing selectRoom() function
        firstRoom.click();
    }
});

/**
 * ------------------------------------------------------------------
 * 6. SCHEDULE IMAGE UPLOAD PREVIEW
 * Opens file explorer and renders selected image in the placeholder
 * ------------------------------------------------------------------
 */
const scheduleInput = document.getElementById('scheduleInput');
const scheduleDisplay = document.getElementById('schedule-display');

if (scheduleInput && scheduleDisplay) {
    scheduleInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();

            reader.onload = function(event) {
                // Clear any existing placeholder text and inject the image
                scheduleDisplay.innerHTML = `
                    <img src="${event.target.result}" 
                         style="width: 100%; height: 100%; object-fit: contain; display: block;">
                `;
            };

            reader.readAsDataURL(file);
        }
    });
}


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

    // Close Edit Modal
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