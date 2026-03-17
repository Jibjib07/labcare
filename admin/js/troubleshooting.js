document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('guideTableBody');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    
    // Target the new buttons and the hidden input
    const statusToggleBtns = document.querySelectorAll('.status-toggle-btn');
    const statusValueInput = document.getElementById('statusValue');

    const detailView = document.getElementById('detailView');
    const actionButtons = document.getElementById('actionButtons');
    const editBtn = document.getElementById('mainEditBtn');
    const archiveToggleBtn = document.getElementById('archiveToggleBtn');
    const notificationContainer = document.getElementById('notification-container');

    // Modals
    const addModal = document.getElementById('addGuideModal');
    const openAddBtn = document.getElementById('openAddModal');
    const closeAddBtn = document.getElementById('closeAddModal');
    const submitCreateBtn = document.getElementById('submitCreateBtn');
    const addGuideForm = document.getElementById('addGuideForm');
    const archiveModal = document.getElementById('archiveConfirmModal');
    const closeArchiveBtn = document.getElementById('closeArchiveModal');
    const confirmArchiveBtn = document.getElementById('confirmArchiveBtn');
    const archiveTitleInput = document.getElementById('archiveIssueTitle');
    const archiveCategoryInput = document.getElementById('archiveCategory');
    const restoreModal = document.getElementById('restoreConfirmModal');
    const closeRestoreBtn = document.getElementById('closeRestoreModal');
    const confirmRestoreBtn = document.getElementById('confirmRestoreBtn');
    const restoreTitleInput = document.getElementById('restoreIssueTitle');
    const restoreCategoryInput = document.getElementById('restoreCategory');

    const categoriesListInput = document.getElementById('categoryList');
    const categories = categoriesListInput ? JSON.parse(categoriesListInput.value || "[]") : [];

    let currentGuideId = null;
    let currentGuideData = null;

    function showNotification(type, title, message) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-box-archive';
        toast.innerHTML = `
            <div class="toast-icon"><i class="fas ${iconClass}"></i></div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-sub">${message}</div>
            </div>`;
        notificationContainer.appendChild(toast);
        setTimeout(() => { 
            toast.style.animation = "fadeOut 0.5s ease forwards";
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    if (openAddBtn) openAddBtn.onclick = () => addModal.style.display = 'flex';
    if (closeAddBtn) {
        closeAddBtn.onclick = () => {
            addModal.style.display = 'none';
            addGuideForm.reset();
        };
    }

    if (submitCreateBtn) {
        submitCreateBtn.onclick = () => {
            const formData = new FormData(addGuideForm);
            formData.append('create_guide', '1');
            fetch('troubleshooting.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    showNotification('success', 'Guide Published', 'Guide is now visible to staff.');
                    closeAddBtn.onclick(); 
                    refreshTable(); 
                }
            });
        };
    }

    // --- UPDATED TOGGLE LOGIC ---
    statusToggleBtns.forEach(btn => {
        btn.onclick = function() {
            statusToggleBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            statusValueInput.value = this.getAttribute('data-status');

            detailView.innerHTML = '<p class="placeholder-text" style="text-align:center; color:#888; margin-top:50px;">Select a guide to view details.</p>';
            actionButtons.style.display = 'none';
            resetEditButton();
            refreshTable();
        };
    });

    function refreshTable() {
        const search = searchInput.value;
        const category = categoryFilter.value;
        // Read from hidden input instead of radios
        const status = statusValueInput ? statusValueInput.value : 'Available';

        if (status === 'Archived') {
            archiveToggleBtn.innerHTML = '<i class="fas fa-undo"></i> Restore';
            archiveToggleBtn.className = 'btn-restore'; 
        } else {
            archiveToggleBtn.innerHTML = '<i class="fas fa-box-archive"></i> Archive';
            archiveToggleBtn.className = 'btn-archive';
        }

        fetch(`troubleshooting.php?ajax_filter=1&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&status=${status}`)
            .then(res => res.text())
            .then(html => { tableBody.innerHTML = html; })
            .catch(err => console.error('Error loading table:', err));
    }

    searchInput.oninput = refreshTable;
    categoryFilter.onchange = refreshTable;

    tableBody.onclick = (e) => {
        const row = e.target.closest('.guide-row');
        if (!row) return;
        document.querySelectorAll('.guide-row').forEach(r => r.classList.remove('active-row'));
        row.classList.add('active-row');
        currentGuideId = row.getAttribute('data-id');
        fetch(`troubleshooting.php?get_details=${currentGuideId}`)
            .then(res => res.json())
            .then(data => {
                currentGuideData = data;
                actionButtons.style.display = 'flex';
                resetEditButton();
                renderDetails(data);
            });
    };

    function renderDetails(data) {
        let categoryOptions = categories.map(cat => 
            `<option value="${cat}" ${cat === data.issue_catego ? 'selected' : ''}>${cat}</option>`
        ).join('');
        detailView.innerHTML = `
            <div class="detail-group"><label>Category</label><select class="detail-select" name="issue_catego" disabled>${categoryOptions}</select></div>
            <div class="detail-group"><label>Issue Title</label><input type="text" class="detail-input" name="issue_title" value="${data.issue_title}" readonly></div>
            <div class="detail-group"><label>Summary Description</label><textarea class="detail-textarea auto-height" name="issue_summary" readonly>${data.issue_summary}</textarea></div>
            <div class="detail-group"><label>Possible Causes</label><textarea class="detail-textarea auto-height" name="issue_cause" readonly>${data.issue_cause}</textarea></div>
            <div class="detail-group"><label>Step-by-Step Solution</label><textarea class="detail-textarea auto-height" name="issue_solutio" readonly>${data.issue_solutio}</textarea></div>
            <div class="detail-group"><label>Preventive Measures</label><textarea class="detail-textarea auto-height" name="issue_preven" readonly>${data.issue_preven || ''}</textarea></div>`;
    }

    if (editBtn) {
        editBtn.onclick = () => {
            const inputs = detailView.querySelectorAll('.detail-input, .detail-textarea');
            const select = detailView.querySelector('.detail-select');
            const isEditing = editBtn.classList.contains('btn-save');
            if (!isEditing) {
                inputs.forEach(el => el.removeAttribute('readonly'));
                if(select) select.removeAttribute('disabled');
                editBtn.innerHTML = '<i class="fas fa-save"></i> Save';
                editBtn.classList.replace('btn-edit', 'btn-save');
            } else {
                const formData = new FormData();
                formData.append('update_guide', '1');
                formData.append('guide_id', currentGuideId);
                detailView.querySelectorAll('[name]').forEach(f => formData.append(f.name, f.value));
                fetch('troubleshooting.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(result => {
                    if (result.status === 'success') {
                        showNotification('success', 'Guide Updated', 'Changes saved.');
                        resetEditButton();
                        refreshTable();
                    }
                });
            }
        };
    }

    function resetEditButton() {
        editBtn.innerHTML = '<i class="fas fa-pen"></i> Edit';
        editBtn.className = 'btn-edit';
    }

    refreshTable(); 
});