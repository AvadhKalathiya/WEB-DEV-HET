// Practical 13: AJAX / Fetch API Single-Page CRUD Controller

document.addEventListener('DOMContentLoaded', () => {
    let currentStudents = [];

    const asyncForm = document.getElementById('asyncForm');
    const studentDbId = document.getElementById('studentDbId');
    const studentId = document.getElementById('studentId');
    const fullName = document.getElementById('fullName');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');
    const deptSelect = document.getElementById('deptSelect');
    const semester = document.getElementById('semester');
    const cgpa = document.getElementById('cgpa');

    const formTitle = document.getElementById('formTitle');
    const saveBtnText = document.getElementById('saveBtnText');
    const cancelBtn = document.getElementById('cancelBtn');

    const liveSearchInput = document.getElementById('liveSearchInput');
    const refreshBtn = document.getElementById('refreshBtn');
    const recordCount = document.getElementById('recordCount');
    const studentTableBody = document.getElementById('studentTableBody');
    const toastContainer = document.getElementById('toastContainer');

    // Display Toast notification
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i> <span>${message}</span>`;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // Fetch all records
    async function loadStudents(searchQuery = '') {
        try {
            const url = searchQuery ? `api.php?search=${encodeURIComponent(searchQuery)}` : 'api.php';
            const response = await fetch(url, { cache: 'no-store' });
            const result = await response.json();

            if (result.success) {
                currentStudents = result.data;
                renderTable(currentStudents);
                recordCount.textContent = `Showing ${currentStudents.length} student records`;
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            console.error('Fetch error:', err);
            showToast('Failed to reach REST API endpoint', 'error');
        }
    }

    // Render table rows
    function renderTable(students) {
        studentTableBody.innerHTML = '';

        if (students.length === 0) {
            studentTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">No matching student records found.</td></tr>`;
            return;
        }

        students.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${s.student_id}</strong></td>
                <td>
                    ${s.full_name}<br>
                    <small style="color:#64748b;">${s.email}</small>
                </td>
                <td><span class="dept-tag">${s.department_code || 'IT'}</span></td>
                <td>Sem ${s.semester}</td>
                <td><span class="cgpa-pill">${parseFloat(s.cgpa).toFixed(2)}</span></td>
                <td>
                    <button class="btn-action edit" data-id="${s.id}" title="Edit without reload">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn-action delete" data-id="${s.id}" title="Delete without reload">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            `;
            studentTableBody.appendChild(tr);
        });
    }

    // Handle Form Submit (POST for create, PUT for update)
    async function handleFormSubmit(e) {
        e.preventDefault();

        const payload = {
            student_id: studentId.value.trim().toUpperCase(),
            full_name: fullName.value.trim(),
            email: email.value.trim().toLowerCase(),
            phone: phone.value.trim(),
            department_id: parseInt(deptSelect.value, 10),
            semester: parseInt(semester.value, 10),
            cgpa: parseFloat(cgpa.value) || 0.0
        };

        const isEdit = Boolean(studentDbId.value);
        if (isEdit) {
            payload.id = parseInt(studentDbId.value, 10);
        }

        const method = isEdit ? 'PUT' : 'POST';

        try {
            const response = await fetch('api.php', {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                resetForm();
                loadStudents(liveSearchInput.value);
            } else {
                showToast(result.message || 'Error occurred', 'error');
            }
        } catch (err) {
            console.error('API submission error:', err);
            showToast('Network error while processing request', 'error');
        }
    }

    // Reset Form to Add Mode
    function resetForm() {
        asyncForm.reset();
        studentDbId.value = '';
        formTitle.textContent = 'Quick Student Add';
        saveBtnText.textContent = 'Save Record';
        cancelBtn.style.display = 'none';
    }

    // Handle Table Actions (Edit & Delete delegation)
    studentTableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.btn-action.edit');
        const deleteBtn = e.target.closest('.btn-action.delete');

        if (editBtn) {
            const id = parseInt(editBtn.getAttribute('data-id'), 10);
            const target = currentStudents.find(s => parseInt(s.id, 10) === id);
            if (target) {
                studentDbId.value = target.id;
                studentId.value = target.student_id;
                fullName.value = target.full_name;
                email.value = target.email;
                phone.value = target.phone || '';
                deptSelect.value = target.department_id;
                semester.value = target.semester;
                cgpa.value = target.cgpa;

                formTitle.textContent = 'Edit Student (Async)';
                saveBtnText.textContent = 'Update Record';
                cancelBtn.style.display = 'inline-flex';
                asyncForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        if (deleteBtn) {
            const id = parseInt(deleteBtn.getAttribute('data-id'), 10);
            if (confirm('Delete this student via AJAX request?')) {
                try {
                    const response = await fetch('api.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    });
                    const result = await response.json();
                    if (result.success) {
                        showToast(result.message, 'success');
                        loadStudents(liveSearchInput.value);
                    } else {
                        showToast(result.message, 'error');
                    }
                } catch (err) {
                    showToast('Failed to delete student', 'error');
                }
            }
        }
    });

    // Live search listener
    let debounceTimer;
    liveSearchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            loadStudents(liveSearchInput.value.trim());
        }, 250);
    });

    // Refresh button
    refreshBtn.addEventListener('click', () => {
        liveSearchInput.value = '';
        loadStudents();
    });

    // Cancel edit
    cancelBtn.addEventListener('click', resetForm);

    // Form submit listener
    asyncForm.addEventListener('submit', handleFormSubmit);

    // Initial API fetch
    loadStudents();
});
