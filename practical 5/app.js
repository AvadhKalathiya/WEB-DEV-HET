// Practical 5: Frontend Form Validation with User-Friendly Error Handling

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registrationForm');
    const fields = {
        fullname: document.getElementById('fullname'),
        studentid: document.getElementById('studentid'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        department: document.getElementById('department'),
        semester: document.getElementById('semester'),
        password: document.getElementById('password'),
        confirmpassword: document.getElementById('confirmpassword'),
        terms: document.getElementById('terms')
    };

    const meterBar = document.getElementById('meterBar');
    const togglePassword = document.getElementById('togglePassword');
    const alertSuccess = document.getElementById('alertSuccess');
    const modal = document.getElementById('summaryModal');
    const modalData = document.getElementById('modalData');
    const closeModal = document.getElementById('closeModal');
    const modalOkBtn = document.getElementById('modalOkBtn');

    // Toggle Password Visibility
    togglePassword.addEventListener('click', () => {
        const type = fields.password.getAttribute('type') === 'password' ? 'text' : 'password';
        fields.password.setAttribute('type', type);
        togglePassword.querySelector('i').classList.toggle('fa-eye');
        togglePassword.querySelector('i').classList.toggle('fa-eye-slash');
    });

    // Helper: set field state
    function setFieldState(field, isValid, customMsg = null) {
        const group = document.getElementById(`group-${field.id}`);
        if (!group) return;
        const errorSpan = document.getElementById(`error-${field.id}`);

        if (isValid) {
            group.classList.remove('error');
            group.classList.add('success');
            if (errorSpan) errorSpan.style.display = 'none';
        } else {
            group.classList.remove('success');
            group.classList.add('error');
            if (errorSpan) {
                if (customMsg) errorSpan.textContent = customMsg;
                errorSpan.style.display = 'block';
            }
        }
    }

    // Validation Rules
    function validateFullname() {
        const val = fields.fullname.value.trim();
        const regex = /^[a-zA-Z\s]{3,50}$/;
        const isValid = regex.test(val);
        setFieldState(fields.fullname, isValid, val.length < 3 ? 'Name must be at least 3 characters.' : 'Name can only contain alphabets.');
        return isValid;
    }

    function validateStudentId() {
        const val = fields.studentid.value.trim().toUpperCase();
        fields.studentid.value = val;
        // CHARUSAT format: e.g. 23IT001 or 24CE055 or D23IT01
        const regex = /^[D]?[0-9]{2}[A-Z]{2,4}[0-9]{3}$/;
        const isValid = regex.test(val);
        setFieldState(fields.studentid, isValid, 'Invalid Student ID. Example format: 23IT001 or D23IT001');
        return isValid;
    }

    function validateEmail() {
        const val = fields.email.value.trim().toLowerCase();
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = regex.test(val);
        setFieldState(fields.email, isValid, 'Enter a valid email address (e.g., student@charusat.edu.in)');
        return isValid;
    }

    function validatePhone() {
        const val = fields.phone.value.trim();
        const regex = /^[6-9]\d{9}$/;
        const isValid = regex.test(val);
        setFieldState(fields.phone, isValid, 'Must be a valid 10-digit Indian mobile number starting with 6-9');
        return isValid;
    }

    function validateDepartment() {
        const isValid = fields.department.value !== '';
        setFieldState(fields.department, isValid, 'Please select your department.');
        return isValid;
    }

    function validateSemester() {
        const isValid = fields.semester.value !== '';
        setFieldState(fields.semester, isValid, 'Please select your semester.');
        return isValid;
    }

    function checkPasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[@$!%*?&#]/.test(password)) score++;

        // Update visual meter
        if (password.length === 0) {
            meterBar.style.width = '0%';
            meterBar.style.background = '#dc2626';
        } else if (score <= 2) {
            meterBar.style.width = '35%';
            meterBar.style.background = '#dc2626'; // Weak
        } else if (score <= 4) {
            meterBar.style.width = '70%';
            meterBar.style.background = '#f59e0b'; // Moderate
        } else {
            meterBar.style.width = '100%';
            meterBar.style.background = '#16a34a'; // Strong
        }
        return score >= 4;
    }

    function validatePassword() {
        const val = fields.password.value;
        const isStrong = checkPasswordStrength(val);
        setFieldState(fields.password, isStrong, 'Password must be 8+ characters with uppercase, lowercase, digit & symbol.');
        if (fields.confirmpassword.value.length > 0) {
            validateConfirmPassword();
        }
        return isStrong;
    }

    function validateConfirmPassword() {
        const val = fields.confirmpassword.value;
        const isValid = val === fields.password.value && val.length > 0;
        setFieldState(fields.confirmpassword, isValid, 'Passwords do not match.');
        return isValid;
    }

    function validateTerms() {
        const isValid = fields.terms.checked;
        setFieldState(fields.terms, isValid, 'You must accept the terms before submitting.');
        return isValid;
    }

    // Attach real-time input & blur listeners
    fields.fullname.addEventListener('input', validateFullname);
    fields.studentid.addEventListener('input', validateStudentId);
    fields.email.addEventListener('input', validateEmail);
    fields.phone.addEventListener('input', validatePhone);
    fields.department.addEventListener('change', validateDepartment);
    fields.semester.addEventListener('change', validateSemester);
    fields.password.addEventListener('input', validatePassword);
    fields.confirmpassword.addEventListener('input', validateConfirmPassword);
    fields.terms.addEventListener('change', validateTerms);

    // Form submission
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const vName = validateFullname();
        const vId = validateStudentId();
        const vEmail = validateEmail();
        const vPhone = validatePhone();
        const vDept = validateDepartment();
        const vSem = validateSemester();
        const vPass = validatePassword();
        const vCpass = validateConfirmPassword();
        const vTerms = validateTerms();

        const formIsValid = vName && vId && vEmail && vPhone && vDept && vSem && vPass && vCpass && vTerms;

        if (formIsValid) {
            alertSuccess.style.display = 'flex';

            modalData.innerHTML = `
                <p><strong>Full Name:</strong> ${fields.fullname.value}</p>
                <p><strong>Student ID:</strong> ${fields.studentid.value}</p>
                <p><strong>Email:</strong> ${fields.email.value}</p>
                <p><strong>Phone:</strong> ${fields.phone.value}</p>
                <p><strong>Department:</strong> ${fields.department.value}</p>
                <p><strong>Semester:</strong> ${fields.semester.value}</p>
                <p><strong>Terms:</strong> Accepted</p>
                <hr style="margin: 12px 0; border: none; border-top: 1px solid #e2e8f0;">
                <p style="color: #16a34a; font-weight: 600;"><i class="fa-solid fa-check-double"></i> All validations passed successfully.</p>
            `;
            modal.style.display = 'flex';
        } else {
            alertSuccess.style.display = 'none';
            // Scroll to the first error
            const firstError = document.querySelector('.form-group.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    form.addEventListener('reset', () => {
        alertSuccess.style.display = 'none';
        meterBar.style.width = '0%';
        Object.keys(fields).forEach(key => {
            const group = document.getElementById(`group-${fields[key].id}`);
            if (group) {
                group.classList.remove('error', 'success');
            }
        });
    });

    closeModal.addEventListener('click', () => modal.style.display = 'none');
    modalOkBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });
});
