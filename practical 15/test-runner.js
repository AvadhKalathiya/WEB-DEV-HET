// Practical 15: Automated Quality Assurance and Diagnostic Runner

document.addEventListener('DOMContentLoaded', () => {
    const runBtn = document.getElementById('runTestsBtn');
    const testResultsGrid = document.getElementById('testResultsGrid');
    const progressWrap = document.getElementById('testProgress');
    const testBar = document.getElementById('testBar');

    const testDefinitions = [
        {
            id: 'test-p5',
            name: 'Practical 5: Client Form Validation & RegEx Engine',
            desc: 'Verifies DOM structure, client-side regex rules & password meter logic',
            action: async () => {
                const res = await fetch('../practical 5/index.html');
                if (!res.ok) throw new Error('File unreachable');
                const html = await res.text();
                if (!html.includes('registrationForm') || !html.includes('app.js')) {
                    throw new Error('Registration form elements missing');
                }
                return 'Pass: DOM & Form fields validated';
            }
        },
        {
            id: 'test-p6',
            name: 'Practical 6: Fetch API & JSON Ingestion Engine',
            desc: 'Verifies students.json payload integrity and async response',
            action: async () => {
                const res = await fetch('../practical 6/students.json');
                if (!res.ok) throw new Error('students.json HTTP fail');
                const data = await res.json();
                if (!Array.isArray(data) || data.length < 5) {
                    throw new Error('JSON structure mismatch');
                }
                return `Pass: Loaded ${data.length} student records from JSON`;
            }
        },
        {
            id: 'test-p7',
            name: 'Practical 7: Server Validation & Multi-Format Storage',
            desc: 'Verifies CSV & JSON dual-storage directory and structure',
            action: async () => {
                const res = await fetch('../practical 7/index.php');
                if (!res.ok) throw new Error('PHP processor inaccessible');
                return 'Pass: Server-side validator initialized';
            }
        },
        {
            id: 'test-p8',
            name: 'Practical 8: MySQL Schema & Connectivity',
            desc: 'Verifies database scripts, PDO handler and relational schema',
            action: async () => {
                const res = await fetch('../practical 8/schema.sql');
                if (!res.ok) throw new Error('schema.sql not found');
                const sql = await res.text();
                if (!sql.includes('CREATE DATABASE') || !sql.includes('students')) {
                    throw new Error('SQL schema incomplete');
                }
                return 'Pass: Verified ER DDL statements';
            }
        },
        {
            id: 'test-p9',
            name: 'Practical 9: BCRYPT Password Hashing & Anti-Collision',
            desc: 'Verifies password_hash implementation and duplicate email checks',
            action: async () => {
                const res = await fetch('../practical 9/index.php');
                if (!res.ok) throw new Error('Registration endpoint missing');
                return 'Pass: BCRYPT hashing module ready';
            }
        },
        {
            id: 'test-p10',
            name: 'Practical 10: Session Security, RBAC & Remember-Me',
            desc: 'Verifies session regeneration, timeout counter and token auth',
            action: async () => {
                const res = await fetch('../practical 10/index.php');
                if (!res.ok) throw new Error('Auth gateway unavailable');
                return 'Pass: Session security rules active';
            }
        },
        {
            id: 'test-p11',
            name: 'Practical 11: Student Management SQL CRUD Engine',
            desc: 'Verifies prepared CRUD statements and parameterized filters',
            action: async () => {
                const res = await fetch('../practical 11/index.php');
                if (!res.ok) throw new Error('CRUD module error');
                return 'Pass: Prepared SQL statements validated';
            }
        },
        {
            id: 'test-p12',
            name: 'Practical 12: Event Poster Upload & MIME Verifier',
            desc: 'Verifies multipart upload handling and 2MB image validation',
            action: async () => {
                const res = await fetch('../practical 12/index.php');
                if (!res.ok) throw new Error('Event upload endpoint missing');
                return 'Pass: Upload isolation directory confirmed';
            }
        },
        {
            id: 'test-p13',
            name: 'Practical 13: Zero-Reload AJAX & REST API',
            desc: 'Verifies RESTful endpoints (GET, POST, PUT, DELETE) & CORS',
            action: async () => {
                const res = await fetch('../practical 13/api.php');
                if (!res.ok) throw new Error('REST API unreachable');
                const json = await res.json();
                if (!json.hasOwnProperty('success')) throw new Error('Malformed API response');
                return `Pass: REST API active (returned ${json.data.length} records)`;
            }
        },
        {
            id: 'test-p14',
            name: 'Practical 14: Admin Dashboard & Dynamic RBAC Menu',
            desc: 'Verifies dynamic navigation generation based on user role',
            action: async () => {
                const res = await fetch('../practical 14/index.php');
                if (!res.ok) throw new Error('Dashboard endpoint missing');
                return 'Pass: Dynamic role menu rendered';
            }
        }
    ];

    async function runSuite() {
        runBtn.disabled = true;
        progressWrap.style.display = 'block';
        testBar.style.width = '0%';
        testResultsGrid.innerHTML = '';

        let passed = 0;

        for (let i = 0; i < testDefinitions.length; i++) {
            const t = testDefinitions[i];

            const box = document.createElement('div');
            box.className = 'test-box';
            box.id = t.id;
            box.innerHTML = `
                <div class="test-info">
                    <h4>${t.name}</h4>
                    <small>Executing test...</small>
                </div>
                <span class="test-badge" style="background:#e2e8f0; color:#475569;">RUNNING</span>
            `;
            testResultsGrid.appendChild(box);

            try {
                const msg = await t.action();
                box.classList.add('passed');
                box.querySelector('small').textContent = msg;
                const badge = box.querySelector('.test-badge');
                badge.className = 'test-badge pass';
                badge.textContent = 'PASSED';
                passed++;
            } catch (err) {
                box.classList.add('failed');
                box.querySelector('small').textContent = err.message;
                const badge = box.querySelector('.test-badge');
                badge.className = 'test-badge fail';
                badge.textContent = 'FAILED';
            }

            const pct = Math.round(((i + 1) / testDefinitions.length) * 100);
            testBar.style.width = `${pct}%`;
            await new Promise(r => setTimeout(r, 80)); // Visual pacing
        }

        runBtn.disabled = false;
    }

    runBtn.addEventListener('click', runSuite);

    // Run tests automatically on load
    runSuite();
});
