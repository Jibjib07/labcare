document.addEventListener('DOMContentLoaded', function() {
    // Core UI Elements
    const tableBody = document.getElementById('guideTableBody');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
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
    const addGuideForm = document.getElementById('addGuideForm');
    const submitCreateBtn = document.getElementById('submitCreateBtn');
    
    const archiveModal = document.getElementById('archiveConfirmModal');
    const closeArchiveBtn = document.getElementById('closeArchiveModal');
    const confirmArchiveBtn = document.getElementById('confirmArchiveBtn');
    
    const restoreModal = document.getElementById('restoreConfirmModal');
    const closeRestoreBtn = document.getElementById('closeRestoreModal');
    const confirmRestoreBtn = document.getElementById('confirmRestoreBtn');

    // Data State
    const categoriesListInput = document.getElementById('categoryList');
    const categories = categoriesListInput ? JSON.parse(categoriesListInput.value || "[]") : [];
    let currentGuideId = null;
    let currentGuideData = null;
    
    // Animation Control Flag
    let skipAnimation = false;

    // --- UTILITIES ---

    function adjustTextareaHeight(el) {
        if (!el || el.tagName !== 'TEXTAREA') return;
        el.style.height = '45px'; 
        el.style.height = el.scrollHeight + 'px';
    }

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

    function resetEditButton() {
        if (editBtn) {
            editBtn.innerHTML = '<i class="fas fa-pen"></i> Edit';
            editBtn.className = 'btn-edit';
            editBtn.style.display = 'inline-block';
        }
    }

    // --- TABLE LOGIC ---
    function refreshTable(autoSelectFirst = true) {
        const search = searchInput.value;
        const category = categoryFilter.value;
        const status = statusValueInput ? statusValueInput.value : 'Available';

        // Trigger skip flag for filter/search operations
        skipAnimation = true;

        if (status === 'Archived') {
            archiveToggleBtn.innerHTML = '<i class="fas fa-undo"></i> Restore';
            archiveToggleBtn.className = 'btn-restore'; 
            if (editBtn) editBtn.style.display = 'none';
        } else {
            archiveToggleBtn.innerHTML = '<i class="fas fa-box-archive"></i> Archive';
            archiveToggleBtn.className = 'btn-archive';
            if (editBtn) editBtn.style.display = 'inline-block';
        }

        fetch(`troubleshooting.php?ajax_filter=1&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&status=${status}`)
            .then(res => res.text())
            .then(html => { 
                tableBody.innerHTML = html; 
                const firstRow = tableBody.querySelector('.guide-row');
                
                if (autoSelectFirst && firstRow) {
                    firstRow.click();
                } else if (!firstRow) {
                    detailView.innerHTML = '';
                    actionButtons.style.display = 'none';
                } else if (currentGuideId) {
                    const activeRow = tableBody.querySelector(`[data-id="${currentGuideId}"]`);
                    if (activeRow) activeRow.classList.add('active-row');
                }
                // Reset flag after click event has processed
                setTimeout(() => { skipAnimation = false; }, 50);
            })
            .catch(err => console.error('Error loading table:', err));
    }

    // --- SELECTION & RENDERING ---
    tableBody.onclick = (e) => {
        const row = e.target.closest('.guide-row');
        if (!row) return;

        // Apply animation ONLY if not skipping
        if (!skipAnimation) {
            const rightPanel = document.querySelector('.right-detail-panel');
            rightPanel.style.animation = 'none';
            void rightPanel.offsetWidth; 
            rightPanel.style.animation = null;
        }

        document.querySelectorAll('.guide-row').forEach(r => r.classList.remove('active-row'));
        row.classList.add('active-row');
        
        currentGuideId = row.getAttribute('data-id');
        fetch(`troubleshooting.php?get_details=${currentGuideId}`)
            .then(res => res.json())
            .then(data => {
                currentGuideData = data;
                actionButtons.style.display = 'flex';
                
                if (statusValueInput.value === 'Archived') {
                    if (editBtn) editBtn.style.display = 'none';
                } else {
                    resetEditButton();
                }

                renderDetails(data);
                detailView.querySelectorAll('.detail-textarea').forEach(adjustTextareaHeight);
            });
    };

    function renderDetails(data) {
        let categoryOptions = categories.map(cat => 
            `<option value="${cat}" ${cat === data.issue_catego ? 'selected' : ''}>${cat}</option>`
        ).join('');

        detailView.innerHTML = `
            <div class="detail-group"><label>Category</label><select class="detail-select" name="issue_catego" disabled>${categoryOptions}</select></div>
            <div class="detail-group"><label>Issue Title</label><input type="text" class="detail-input" name="issue_title" value="${data.issue_title}" readonly></div>
            <div class="detail-group"><label>Summary Description</label><textarea class="detail-textarea" name="issue_summary" readonly>${data.issue_summary}</textarea></div>
            <div class="detail-group"><label>Possible Causes</label><textarea class="detail-textarea" name="issue_cause" readonly>${data.issue_cause}</textarea></div>
            <div class="detail-group"><label>Step-by-Step Solution</label><textarea class="detail-textarea" name="issue_solutio" readonly>${data.issue_solutio}</textarea></div>
            <div class="detail-group"><label>Preventive Measures</label><textarea class="detail-textarea" name="issue_preven" readonly>${data.issue_preven || ''}</textarea></div>`;
    }

    detailView.addEventListener('input', (e) => {
        if (e.target.classList.contains('detail-textarea')) {
            adjustTextareaHeight(e.target);
        }
    });

    addGuideForm.addEventListener('input', (e) => {
        if (e.target.tagName === 'TEXTAREA') {
            adjustTextareaHeight(e.target);
        }
    });

    // --- EDIT & SAVE ACTION ---
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
                detailView.querySelectorAll('.detail-textarea').forEach(adjustTextareaHeight);
            } else {
                const formData = new FormData();
                formData.append('update_guide', '1');
                formData.append('guide_id', currentGuideId);
                detailView.querySelectorAll('[name]').forEach(f => formData.append(f.name, f.value));

                fetch('troubleshooting.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(result => {
                    if (result.status === 'success') {
                        showNotification('success', 'Guide Updated', 'Changes saved successfully.');
                        inputs.forEach(el => el.setAttribute('readonly', true));
                        if(select) select.setAttribute('disabled', true);
                        resetEditButton();
                        refreshTable(false); 
                    }
                });
            }
        };
    }

    // --- MODAL & FILTER CONTROLS ---

    if (openAddBtn) openAddBtn.onclick = () => addModal.style.display = 'flex';
    if (closeAddBtn) {
        closeAddBtn.onclick = () => { 
            addModal.style.display = 'none'; 
            addGuideForm.reset(); 
            addGuideForm.querySelectorAll('textarea').forEach(t => t.style.height = '45px');
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
                    showNotification('success', 'Guide Published', 'New guide added to the list.');
                    closeAddBtn.onclick(); 
                    refreshTable(true); 
                }
            });
        };
    }

    if (archiveToggleBtn) {
        archiveToggleBtn.onclick = () => {
            if (statusValueInput.value === 'Available') {
                document.getElementById('archiveIssueTitle').value = currentGuideData.issue_title;
                document.getElementById('archiveCategory').value = currentGuideData.issue_catego;
                archiveModal.style.display = 'flex';
            } else {
                document.getElementById('restoreIssueTitle').value = currentGuideData.issue_title;
                document.getElementById('restoreCategory').value = currentGuideData.issue_catego;
                restoreModal.style.display = 'flex';
            }
        };
    }

    if (closeArchiveBtn) closeArchiveBtn.onclick = () => archiveModal.style.display = 'none';
    if (closeRestoreBtn) closeRestoreBtn.onclick = () => restoreModal.style.display = 'none';

    if (confirmArchiveBtn) {
        confirmArchiveBtn.onclick = () => {
            const formData = new FormData();
            formData.append('archive_guide', '1');
            formData.append('guide_id', currentGuideId);
            fetch('troubleshooting.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    showNotification('success', 'Guide Archived', 'Moved to archived list.');
                    archiveModal.style.display = 'none';
                    refreshTable(true);
                }
            });
        };
    }

    if (confirmRestoreBtn) {
        confirmRestoreBtn.onclick = () => {
            const formData = new FormData();
            formData.append('restore_guide', '1');
            formData.append('guide_id', currentGuideId);
            fetch('troubleshooting.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    showNotification('success', 'Guide Restored', 'Moved back to active list.');
                    restoreModal.style.display = 'none';
                    refreshTable(true);
                }
            });
        };
    }

    statusToggleBtns.forEach(btn => {
        btn.onclick = function() {
            statusToggleBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            statusValueInput.value = this.getAttribute('data-status');
            refreshTable(true);
        };
    });

    searchInput.oninput = () => refreshTable(true);
    categoryFilter.onchange = () => refreshTable(true);

    refreshTable(true); 
});