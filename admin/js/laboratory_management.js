
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
    if (roomNumber.toLowerCase().startsWith('room ')) {
    document.getElementById('edit_room_number').value = roomNumber.substring(5); // Removes "Room "
    } else {
        document.getElementById('edit_room_number').value = roomNumber;
    }
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

    // B. Update Schedule Title (Handles "N/A" rooms like Library)
    const roomNameElement = element ? element.querySelector('h4, .lab-name') : null;
    const roomName = roomNameElement ? roomNameElement.textContent.trim() : roomNumber;
    const st = document.getElementById('schedule-title');
    
    if (st) {
        if (roomNumber.toLowerCase() === roomName.toLowerCase()) {
            st.textContent = roomName + ' Schedule';
        } else {
            st.textContent = roomNumber + ' Schedule';
        }
    }

    const schedDisplay = document.getElementById('schedule-display');
    const clearBtn = document.getElementById('btnClearSchedule');

    if(schedDisplay) {
        schedDisplay.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:#8c52ff; font-size: 1.5rem;"></i>';
    }

    // C. Fetch Data from Database
    fetch(`../includes/get_room_stats.php?room=${roomNumber}`)
        .then(response => response.json())
        .then(data => {
            // 1. Update Stats (Desktop & Mobile)
            updateUI('val-working', data.working);
            updateUI('val-repair', data.repair);
            updateUI('val-condemned', data.condemn);
            updateUI('val-total', data.total_units);
            updateUI('val-assets', data.total_assets);
            updateUI('m-val-working', data.working);
            updateUI('m-val-repair', data.repair);
            updateUI('m-val-condemned', data.condemn);
            updateUI('m-val-total', data.total_units);

            // 2. Handle Schedule Image and "Clear" Button Visibility
            // Inside fetch(...get_room_stats.php).then(data => { ...
            if (data.schedule && data.schedule !== '') {
                if (clearBtn) clearBtn.style.display = 'inline-flex';
                schedDisplay.innerHTML = `<img src="${data.schedule}" class="schedule-img-fit" onclick="openScheduleModal(this.src)">`;
            } else {
                // This is the part that runs after you successfully clear the DB
                if (clearBtn) clearBtn.style.display = 'none';
                schedDisplay.innerHTML = `<p style="color: #999; font-size: 0.9rem;">No schedule uploaded.</p>`;
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            if (clearBtn) clearBtn.style.display = 'none';
            if (schedDisplay) schedDisplay.innerHTML = '<p style="color:red;">Error loading data.</p>';
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

            // 1. Instant UI Feedback: Local Preview (Faded version)
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

            // Using 'includes/upload_schedule.php' as per your admin folder reference
            fetch('includes/upload_schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    /**
                     * REFRESH LOGIC:
                     * Instead of just updating the image, we find the active room element 
                     * and call selectRoom() again. This re-fetches the stats from the DB
                     * and automatically shows the "Clear" button.
                     */
                    const activeEl = document.querySelector('.room-item.active, .m-room-card.active');
                    selectRoom(activeEl, currentSelectedRoom);
                    
                    // Reset the input so the same file can be uploaded again if cleared
                    scheduleInput.value = '';
                } else {
                    alert("Upload failed: " + data.error);
                    // Revert UI to previous state
                    const activeEl = document.querySelector('.room-item.active, .m-room-card.active');
                    selectRoom(activeEl, currentSelectedRoom); 
                }
            })
            .catch(error => {
                console.error("Upload error:", error);
                alert("Could not connect to the upload script.");
                const activeEl = document.querySelector('.room-item.active, .m-room-card.active');
                selectRoom(activeEl, currentSelectedRoom);
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

// let currentQRRoom = '';
 
function openEditModal(button) {
    // Grab the data
    const labId = button.getAttribute('data-id');
    const roomName = button.getAttribute('data-name');
    const roomNumber = button.getAttribute('data-room');
    const totalUnits = button.getAttribute('data-units');

    // Fill standard inputs
    document.getElementById('edit_lab_id').value = labId;
    document.getElementById('edit_room_name').value = roomName;
    document.getElementById('edit_total_units').value = totalUnits;
    document.getElementById('original_room_number').value = roomNumber;

// --- NEW: Handle the N/A logic for Edit Modal ---
const editRoomInput = document.getElementById('edit_room_number');
const editNaCheckbox = document.getElementById('edit_no_room_number');

// If the Room Number equals the Room Name, treat it as "Not Applicable"
if (roomNumber.toLowerCase() === roomName.toLowerCase()) {
    editNaCheckbox.checked = true;
    editRoomInput.value = 'N/A'; // Still show N/A in the input box so the user understands
    editRoomInput.style.backgroundColor = "#f5f5f5";
    editRoomInput.style.cursor = "not-allowed";
    editRoomInput.readOnly = true;
} else {
        editNaCheckbox.checked = false;
        editRoomInput.value = roomNumber;
        editRoomInput.style.backgroundColor = "#fff";
        editRoomInput.style.cursor = "text";
        editRoomInput.readOnly = false;
    }

    openModal('editLabModal');
}

// // 2. When clicking the QR button inside the Edit Modal:
// function openQrModal() {
//     // Steal the ID and Room Number right out of the Edit Modal's hidden fields!
//     let labId = document.getElementById('edit_lab_id').value;
//     let roomNumber = document.getElementById('edit_room_number').value;

//     if (!labId) {
//         console.error("No Lab ID found!");
//         return;
//     }

//     currentQRRoom = roomNumber;

//     closeModal('editLabModal');
    
//     // Update the QR Modal Title with the Room Number
//     document.getElementById('qrModalTitle').innerText = `Room ${roomNumber} - QR Code`;
    
//     // Clear old QR code
//     const qrContainer = document.getElementById('qrcode-container');
//     qrContainer.innerHTML = ''; 

//     // Build the URL using the lab_id!
//     let basePath = window.location.href.substring(0, window.location.href.lastIndexOf("/"));
//     let targetUrl = `${basePath}/assets_management.php?lab_id=${labId}`;

//     // Generate QR
//     new QRCode(qrContainer, {
//         text: targetUrl,
//         width: 200,
//         height: 200,
//         colorDark: "#8c52ff",
//         colorLight: "#ffffff",
//         correctLevel: QRCode.CorrectLevel.H
//     });

//     // Open QR Modal
//     openModal('qrModal');
// }

// // Function to download the QR code when "Export" is clicked
// function exportQRCode() {
//     // Find the canvas image generated by the library
//     const qrCanvas = document.querySelector('#qrcode-container canvas');
    
//     if (qrCanvas) {
//         // Convert the canvas to a downloadable image URL
//         const imageUrl = qrCanvas.toDataURL("image/png");
        
//         // Create a temporary link to trigger the download
//         const downloadLink = document.createElement("a");
//         downloadLink.href = imageUrl;
//         downloadLink.download = `LabCare_Room_${currentQRRoom}_QR.png`;
        
//         document.body.appendChild(downloadLink);
//         downloadLink.click();
//         document.body.removeChild(downloadLink);
//     }
// }

function openScheduleModal(imgSrc) {
    const modal = document.getElementById('scheduleModal');
    const modalImg = document.getElementById('modalImg');
    
    if(modal && modalImg) {
        modalImg.src = imgSrc;
        modal.style.display = "flex"; // Shows the modal
        document.body.style.overflow = "hidden"; // Stops the background from scrolling
    }
}


function searchLaboratories() { // You can rename this to searchRooms
    const input = document.getElementById("labSearchInput");
    const filter = input.value.toLowerCase();
    const items = document.querySelectorAll('.room-item, .m-room-card');
    
    items.forEach(item => {
        const textValue = item.textContent || item.innerText;
        // The logic remains the same, but the user-facing search now feels broader
        if (textValue.toLowerCase().includes(filter)) {
            item.style.display = (item.tagName === 'TR') ? '' : 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

async function requestArchiveLab(labId, labName, labRoom) {
    try {
        // 1. Check if the lab has assets
        const response = await fetch(`includes/check_lab_assets.php?lab_id=${labId}`);
        const data = await response.json();

        // 2. Decide which modal to show
        if (data.total_units > 0 || data.total_assets > 0) {
            // Show the BLOCKED Modal
            document.getElementById('blocked_room_number').innerText = labRoom;
            document.getElementById('blocked_total_units').innerText = data.total_units;
            document.getElementById('blocked_total_assets').innerText = data.total_assets;
            
            // Set the Transfer button to pass a special URL parameter
            document.getElementById('btn_redirect_transfer').href = `assets_management.php?lab_id=${labId}&auto_open=transfer`;
            
            document.getElementById('archiveBlockedModal').style.display = 'flex';
        } else {
            // Show the ARCHIVE Modal (Because it is empty)
            document.getElementById('archive_lab_id').value = labId;
            document.getElementById('archive_room_name_display').innerText = labName;
            document.getElementById('archive_room_name').value = labName;
            document.getElementById('archive_room_number').value = labRoom;
            document.getElementById('archive_total_units').value = '0';
            
            document.getElementById('archiveLabModal').style.display = 'flex';
        }
    } catch (error) {
        console.error('Failed to check lab assets:', error);
    }
}

async function submitArchiveLab() {
    const labId = document.getElementById('archive_lab_id').value;
    const remarks = document.getElementById('archive_remarks').value;
    
    // Gather checked reasons
    const reasons = [];
    document.querySelectorAll('#archive_reasons_group input[type="checkbox"]:checked').forEach(cb => {
        reasons.push(cb.value);
    });

    if (reasons.length === 0 && remarks.trim() === '') {
        alert('Please select a reason or provide remarks.');
        return;
    }

    const formData = new FormData();
    formData.append('lab_id', labId);
    formData.append('reasons', JSON.stringify(reasons));
    formData.append('remarks', remarks);

    try {
        const response = await fetch('includes/archive_lab.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            // Use your toast notification logic here!
            sessionStorage.setItem('pendingToast', JSON.stringify({ title: 'Archived', message: 'Laboratory successfully archived.', type: 'success' }));
            location.reload();
        } else {
            alert('Failed to archive: ' + result.error);
        }
    } catch (error) {
        console.error(error);
    }
}

function toggleRoomNumber(checkbox) {
    const roomInput = document.getElementById('lab_room_input');
    
    if (checkbox.checked) {
        // Disable the input and set a placeholder value
        roomInput.value = "N/A";
        roomInput.style.backgroundColor = "#f5f5f5";
        roomInput.style.cursor = "not-allowed";
        roomInput.readOnly = true;
    } else {
        // Re-enable the input
        roomInput.value = "";
        roomInput.style.backgroundColor = "#fff";
        roomInput.style.cursor = "text";
        roomInput.readOnly = false;
        roomInput.focus();
    }
}

function toggleEditRoomNumber(checkbox) {
    const roomInput = document.getElementById('edit_room_number');
    
    if (checkbox.checked) {
        roomInput.value = "N/A";
        roomInput.style.backgroundColor = "#f5f5f5";
        roomInput.style.cursor = "not-allowed";
        roomInput.readOnly = true;
    } else {
        // If they uncheck it, clear the field so they can type a real number
        roomInput.value = "";
        roomInput.style.backgroundColor = "#fff";
        roomInput.style.cursor = "text";
        roomInput.readOnly = false;
        roomInput.focus();
    }
}

function clearSchedule() {
    if (!currentSelectedRoom) return;

    if (confirm(`Are you sure you want to clear the schedule for ${currentSelectedRoom}?`)) {
        const formData = new FormData();
        formData.append('room_number', currentSelectedRoom);

        // REMOVE the ../ because the includes folder is inside the admin folder with your page
        fetch('includes/clear_schedule.php', { 
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh UI
                const activeEl = document.querySelector('.room-item.active, .m-room-card.active');
                selectRoom(activeEl, currentSelectedRoom);
            } else {
                alert("Error: " + data.error);
            }
        })
        .catch(error => {
            console.error("Path Error:", error);
            alert("Still can't find the file. Check the console!");
        });
    }
}