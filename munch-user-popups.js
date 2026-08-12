// =============================
// MUNCH USER-FRIENDLY POPUPS + FORM VALIDATION
// Replaces browser "localhost says" alerts on presentation pages.
// =============================
(function () {
    function ensurePopupStyles() {
        if (document.getElementById('munchUserPopupStyles')) return;
        const style = document.createElement('style');
        style.id = 'munchUserPopupStyles';
        style.textContent = `
            .munch-popup-overlay {
                position: fixed;
                inset: 0;
                z-index: 99999;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                background: rgba(15, 23, 42, .45);
                backdrop-filter: blur(.25rem);
            }
            .munch-popup-overlay.active { display: flex; }
            .munch-popup-card {
                width: min(430px, 100%);
                background: #fff;
                color: #18312f;
                border-radius: 1rem;
                padding: 1.7rem;
                box-shadow: 0 1.4rem 3rem rgba(0, 0, 0, .25);
                border: 1px solid rgba(0, 118, 118, .18);
                text-align: center;
                animation: munchPopupIn .18s ease-out both;
            }
            .munch-popup-icon {
                width: 3.4rem;
                height: 3.4rem;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 1rem;
                background: rgba(0, 118, 118, .12);
                color: #007676;
                font-size: 1.45rem;
            }
            .munch-popup-card h3 {
                margin: 0 0 .6rem;
                font-size: 1.35rem;
                color: #007676;
            }
            .munch-popup-card p {
                margin: 0 0 1.25rem;
                line-height: 1.6;
                color: #36524f;
                white-space: pre-line;
            }
            .munch-popup-card button {
                border: none;
                border-radius: 999px;
                padding: .8rem 1.4rem;
                background: #007676;
                color: #fff;
                font-weight: 800;
                cursor: pointer;
            }
            .munch-popup-card button:hover { filter: brightness(.95); }
            @keyframes munchPopupIn {
                from { opacity: 0; transform: translateY(.6rem) scale(.98); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }
        `;
        document.head.appendChild(style);
    }

    function showPopup(message, title) {
        ensurePopupStyles();
        let overlay = document.getElementById('munchUserPopup');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'munchUserPopup';
            overlay.className = 'munch-popup-overlay';
            overlay.innerHTML = `
                <div class="munch-popup-card" role="dialog" aria-modal="true">
                    <div class="munch-popup-icon"><i class="fa-solid fa-circle-info"></i></div>
                    <h3 id="munchPopupTitle">Notice</h3>
                    <p id="munchPopupMessage">Please check your input.</p>
                    <button type="button" id="munchPopupOk">OK</button>
                </div>
            `;
            document.body.appendChild(overlay);
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) overlay.classList.remove('active');
            });
            overlay.querySelector('#munchPopupOk').addEventListener('click', function () {
                overlay.classList.remove('active');
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') overlay.classList.remove('active');
            });
        }
        overlay.querySelector('#munchPopupTitle').textContent = title || 'Please check this';
        overlay.querySelector('#munchPopupMessage').textContent = String(message || 'Please check your input.');
        overlay.classList.add('active');
    }

    window.MunchPopup = showPopup;

    // Use custom popup for normal JS alerts on pages that include this file.
    window.alert = function (message) {
        showPopup(message, 'Munch Notice');
    };

    function digits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function bindValidation(form, rules) {
        if (!form || form.dataset.munchValidationReady === 'true') return;
        form.dataset.munchValidationReady = 'true';
        form.addEventListener('submit', function (event) {
            const requiredFields = Array.from(form.querySelectorAll('[required]')).filter(function (field) {
                return !field.disabled && field.offsetParent !== null;
            });

            for (const field of requiredFields) {
                const type = (field.type || '').toLowerCase();
                const isRadioOrCheckbox = type === 'radio' || type === 'checkbox';
                const fieldOk = isRadioOrCheckbox
                    ? form.querySelector('[name="' + field.name + '"]:checked')
                    : String(field.value || '').trim() !== '';

                if (!fieldOk) {
                    event.preventDefault();
                    const label = form.querySelector('label[for="' + field.id + '"]');
                    const fieldName = label ? label.textContent.trim() : (field.name || 'required field');
                    showPopup('Please complete ' + fieldName + '.', 'Form Error');
                    if (typeof field.focus === 'function') field.focus();
                    return;
                }

                if (type === 'email' && field.value && !/^\S+@\S+\.\S+$/.test(field.value.trim())) {
                    event.preventDefault();
                    showPopup('Please enter a valid email address such as name@email.com.', 'Invalid Email');
                    field.focus();
                    return;
                }
            }

            for (const rule of rules) {
                const result = rule();
                if (result && result.message) {
                    event.preventDefault();
                    showPopup(result.message, result.title || 'Form Error');
                    if (result.field && typeof result.field.focus === 'function') result.field.focus();
                    return;
                }
            }
        });
    }

    function setupPageValidations() {
        const customerSignup = document.querySelector('form[action="customerSignUp.php"]');
        bindValidation(customerSignup, [
            function () {
                const phone = document.getElementById('phoneNo');
                const count = digits(phone && phone.value).length;
                if (count < 10 || count > 11) return { field: phone, title: 'Invalid Phone Number', message: 'Phone number must be 10 to 11 digits. Example: 0123456789.' };
            },
            function () {
                const pass = document.getElementById('custPassword');
                if (pass && pass.value.length < 8) return { field: pass, title: 'Weak Password', message: 'Password must be at least 8 characters.' };
            },
            function () {
                const pass = document.getElementById('custPassword');
                const confirm = document.getElementById('confirmPassword');
                if (pass && confirm && pass.value !== confirm.value) return { field: confirm, title: 'Password Mismatch', message: 'Password and confirm password do not match.' };
            }
        ]);

        const staffSignup = document.querySelector('form[action="StaffSignUp.php"]');
        bindValidation(staffSignup, [
            function () {
                const phone = document.getElementById('staffPhone');
                const count = digits(phone && phone.value).length;
                if (count < 10 || count > 11) return { field: phone, title: 'Invalid Phone Number', message: 'Staff phone number must be 10 to 11 digits.' };
            },
            function () {
                const pass = document.getElementById('staffPassword');
                const confirm = document.getElementById('staffConfirmPassword');
                if (pass && confirm && pass.value !== confirm.value) return { field: confirm, title: 'Password Mismatch', message: 'Password and confirm password do not match.' };
            }
        ]);

        const reservationForm = document.getElementById('reservation-form');
        bindValidation(reservationForm, [
            function () {
                const phone = document.getElementById('customer-phone');
                const count = digits(phone && phone.value).length;
                if (count < 10 || count > 11) return { field: phone, title: 'Invalid Phone Number', message: 'Reservation phone number must be 10 to 11 digits. Example: 0123456789.' };
            }
        ]);
    }

    setupPageValidations();
    document.addEventListener('DOMContentLoaded', setupPageValidations);
})();
