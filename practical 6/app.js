// Practical 6: Fetch API with live JSON filtering, searching, and sorting

document.addEventListener('DOMContentLoaded', () => {
    let allStudents = [];

    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const deptFilter = document.getElementById('deptFilter');
    const semFilter = document.getElementById('semFilter');
    const sortBy = document.getElementById('sortBy');
    const reloadBtn = document.getElementById('reloadBtn');

    const totalCount = document.getElementById('totalCount');
    const filteredCount = document.getElementById('filteredCount');
    const avgCgpa = document.getElementById('avgCgpa');

    const studentGrid = document.getElementById('studentGrid');
    const loadingState = document.getElementById('loadingState');
    const errorState = document.getElementById('errorState');
    const errorText = document.getElementById('errorText');
    const noResults = document.getElementById('noResults');

    // Fetch JSON data
    async function loadStudentData() {
        showLoading(true);
        showError(false);

        try {
            const response = await fetch('students.json', { cache: 'no-store' });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            allStudents = await response.json();

            populateDepartmentFilter(allStudents);
            applyFilters();
        } catch (err) {
            console.error('Fetch error:', err);
            showError(true, `Could not load students.json: ${err.message}`);
        } finally {
            showLoading(false);
        }
    }

    function populateDepartmentFilter(students) {
        const departments = [...new Set(students.map(s => s.department))].sort();
        // Clear previous options except "ALL"
        deptFilter.innerHTML = '<option value="ALL">All Departments</option>';
        departments.forEach(dept => {
            const opt = document.createElement('option');
            opt.value = dept;
            opt.textContent = dept;
            deptFilter.appendChild(opt);
        });
    }

    function applyFilters() {
        const query = searchInput.value.trim().toLowerCase();
        const selectedDept = deptFilter.value;
        const selectedSem = semFilter.value;
        const selectedSort = sortBy.value;

        let filtered = allStudents.filter(student => {
            // Text search across Name, ID, Department, and Skills
            const matchesQuery = !query || 
                student.name.toLowerCase().includes(query) ||
                student.id.toLowerCase().includes(query) ||
                student.department.toLowerCase().includes(query) ||
                (student.skills && student.skills.some(skill => skill.toLowerCase().includes(query)));

            // Department filter
            const matchesDept = selectedDept === 'ALL' || student.department === selectedDept;

            // Semester filter
            const matchesSem = selectedSem === 'ALL' || student.semester.toString() === selectedSem;

            return matchesQuery && matchesDept && matchesSem;
        });

        // Sorting
        filtered.sort((a, b) => {
            if (selectedSort === 'name-asc') return a.name.localeCompare(b.name);
            if (selectedSort === 'name-desc') return b.name.localeCompare(a.name);
            if (selectedSort === 'cgpa-desc') return b.cgpa - a.cgpa;
            if (selectedSort === 'cgpa-asc') return a.cgpa - b.cgpa;
            return 0;
        });

        updateStats(filtered);
        renderStudents(filtered);
    }

    function updateStats(filtered) {
        totalCount.textContent = allStudents.length;
        filteredCount.textContent = filtered.length;

        if (filtered.length > 0) {
            const sum = filtered.reduce((acc, curr) => acc + (curr.cgpa || 0), 0);
            avgCgpa.textContent = (sum / filtered.length).toFixed(2);
        } else {
            avgCgpa.textContent = '0.00';
        }
    }

    function renderStudents(students) {
        studentGrid.innerHTML = '';

        if (students.length === 0) {
            noResults.style.display = 'block';
            return;
        }
        noResults.style.display = 'none';

        students.forEach(s => {
            const card = document.createElement('div');
            card.className = 'student-card';

            const skillsHtml = s.skills && s.skills.length > 0
                ? s.skills.map(sk => `<span class="skill-tag">${sk}</span>`).join('')
                : '';

            card.innerHTML = `
                <div class="card-top">
                    <div class="student-meta">
                        <h3>${s.name}</h3>
                        <span class="student-id">${s.id}</span>
                    </div>
                    <span class="status-badge ${s.status}">${s.status}</span>
                </div>
                <div class="card-info">
                    <p><i class="fa-solid fa-graduation-cap"></i> ${s.department}</p>
                    <p><i class="fa-solid fa-layer-group"></i> Semester: <strong>${s.semester}</strong></p>
                    <p><i class="fa-solid fa-star"></i> CGPA: <span class="cgpa-pill">${s.cgpa.toFixed(2)}</span></p>
                    <p><i class="fa-solid fa-envelope"></i> ${s.email}</p>
                </div>
                ${skillsHtml ? `<div class="skills-wrapper">${skillsHtml}</div>` : ''}
            `;
            studentGrid.appendChild(card);
        });
    }

    function showLoading(show) {
        loadingState.style.display = show ? 'block' : 'none';
        if (show) {
            studentGrid.innerHTML = '';
            noResults.style.display = 'none';
        }
    }

    function showError(show, msg = '') {
        errorState.style.display = show ? 'block' : 'none';
        if (msg) errorText.textContent = msg;
    }

    // Event Listeners
    searchInput.addEventListener('input', applyFilters);
    clearSearch.addEventListener('click', () => {
        searchInput.value = '';
        applyFilters();
    });
    deptFilter.addEventListener('change', applyFilters);
    semFilter.addEventListener('change', applyFilters);
    sortBy.addEventListener('change', applyFilters);
    reloadBtn.addEventListener('click', loadStudentData);

    // Initial Load
    loadStudentData();
});
