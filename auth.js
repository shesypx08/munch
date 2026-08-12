// MUNCH AUTH + ROLE SCRIPT - customer, staff, owner
(function () {
    const CUSTOMER_LOGIN_URL = "customer-login.html";
    const STAFF_LOGIN_URL = "staff-login.html";
    const OWNER_LOGIN_URL = "owner-login.html";
    const STAFF_DASHBOARD_URL = "staff-dashboard.html";
    const OWNER_DASHBOARD_URL = "owner-dashboard.html";

    function getJSON(url) {
        return fetch(url, { method: "GET", cache: "no-store" })
            .then(res => res.json())
            .catch(() => ({ loggedIn: false }));
    }

    function checkCustomerLogin() { return getJSON("checkLogin.php").then(d => d.loggedIn === true); }
    function checkStaffLogin() { return getJSON("checkStaffLogin.php").then(d => d.loggedIn === true); }
    function checkOwnerLogin() { return getJSON("checkOwnerLogin.php").then(d => d.loggedIn === true); }

    function createRoleModal() {
        if (document.querySelector(".role-modal-overlay")) return;

        const modal = document.createElement("div");
        modal.className = "role-modal-overlay";
        modal.innerHTML = `
            <div class="role-modal" role="dialog" aria-modal="true">
                <button class="role-close-btn" type="button" aria-label="Close role popup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="role-modal-header">
                    <span class="section-highlight">Login Required</span>
                    <h2>Continue as</h2>
                    <p>Please choose your role before accessing protected Munch features.</p>
                </div>
                <div class="role-option-grid">
                    <a href="${CUSTOMER_LOGIN_URL}" class="role-option" data-role="customer">
                        <i class="fa-solid fa-user"></i><h3>Customer</h3>
                        <p>Order food, manage profile, view payments, and check reservations.</p>
                        <span class="role-small-link">Customer login <i class="fa-solid fa-arrow-right"></i></span>
                    </a>
                    <a href="${STAFF_LOGIN_URL}" class="role-option" data-role="staff">
                        <i class="fa-solid fa-user-tie"></i><h3>Staff</h3>
                        <p>Access order management, payments, and kitchen status.</p>
                        <span class="role-small-link">Staff login <i class="fa-solid fa-arrow-right"></i></span>
                    </a>
                    <a href="${OWNER_LOGIN_URL}" class="role-option" data-role="owner">
                        <i class="fa-solid fa-crown"></i><h3>Owner</h3>
                        <p>Login using staff ID registered in the owner subtype table.</p>
                        <span class="role-small-link">Owner login <i class="fa-solid fa-arrow-right"></i></span>
                    </a>
                </div>
            </div>`;
        document.body.appendChild(modal);
        modal.addEventListener("click", e => {
            if (e.target === modal || e.target.closest(".role-close-btn")) closeRoleModal();
        });
        document.addEventListener("keydown", e => { if (e.key === "Escape") closeRoleModal(); });
    }

    function openRoleModal(targetPage) {
        createRoleModal();
        const modal = document.querySelector(".role-modal-overlay");
        const target = targetPage || "profile.html";
        modal.querySelector('[data-role="customer"]').href = CUSTOMER_LOGIN_URL + "?redirect=" + encodeURIComponent(target);
        modal.querySelector('[data-role="staff"]').href = STAFF_LOGIN_URL + "?redirect=" + encodeURIComponent(STAFF_DASHBOARD_URL);
        modal.querySelector('[data-role="owner"]').href = OWNER_LOGIN_URL + "?redirect=" + encodeURIComponent(OWNER_DASHBOARD_URL);
        modal.classList.add("active");
    }

    function closeRoleModal() {
        const modal = document.querySelector(".role-modal-overlay");
        if (modal) modal.classList.remove("active");
    }

    function loginRedirectForRole(role, target) {
        if (role === "staff") return STAFF_LOGIN_URL + "?redirect=" + encodeURIComponent(target || STAFF_DASHBOARD_URL);
        if (role === "owner") return OWNER_LOGIN_URL + "?redirect=" + encodeURIComponent(target || OWNER_DASHBOARD_URL);
        return CUSTOMER_LOGIN_URL + "?redirect=" + encodeURIComponent(target || "profile.html");
    }

    function goToDashboardForExistingRole(role) {
        if (role === "staff") window.location.href = STAFF_DASHBOARD_URL;
        else if (role === "owner") window.location.href = OWNER_DASHBOARD_URL;
    }

    function handleCustomerProtectedTarget(target) {
        // Customer-only links should not ask a logged-in staff/owner to login again.
        // If staff/owner is already logged in and clicks Home -> Profile, send them back to their own dashboard.
        checkCustomerLogin().then(customerOK => {
            if (customerOK) {
                window.location.href = target;
                return;
            }

            checkStaffLogin().then(staffOK => {
                if (staffOK) {
                    goToDashboardForExistingRole("staff");
                    return;
                }

                checkOwnerLogin().then(ownerOK => {
                    if (ownerOK) {
                        goToDashboardForExistingRole("owner");
                        return;
                    }

                    openRoleModal(target);
                });
            });
        });
    }

    function setupProtectedLinks() {
        document.querySelectorAll('[data-protected="true"], .protected-link, .protected-button, .require-login-reservation').forEach(item => {
            if (item.dataset.authReady === "true") return;
            item.dataset.authReady = "true";
            item.addEventListener("click", function (event) {
                event.preventDefault();
                const target = item.dataset.target || item.getAttribute("href") || "profile.html";
                const roleRequired = item.dataset.roleRequired || "customer";

                if (roleRequired === "staff") {
                    checkStaffLogin().then(ok => ok ? window.location.href = target : window.location.href = loginRedirectForRole("staff", target));
                    return;
                }

                if (roleRequired === "owner") {
                    checkOwnerLogin().then(ok => ok ? window.location.href = target : window.location.href = loginRedirectForRole("owner", target));
                    return;
                }

                handleCustomerProtectedTarget(target);
            });
        });
    }

    function protectCurrentPage() {
        const role = document.body.dataset.pageRequiresRole;
        if (!role) return;

        const currentPage = window.location.pathname.split("/").pop() || "index.html";

        if (role === "customer") {
            checkCustomerLogin().then(ok => {
                if (ok) return;

                checkStaffLogin().then(staffOK => {
                    if (staffOK) {
                        window.location.href = STAFF_DASHBOARD_URL;
                        return;
                    }

                    checkOwnerLogin().then(ownerOK => {
                        if (ownerOK) window.location.href = OWNER_DASHBOARD_URL;
                        else openRoleModal(currentPage);
                    });
                });
            });
        }

        if (role === "staff") checkStaffLogin().then(ok => { if (!ok) window.location.href = loginRedirectForRole("staff", currentPage); });
        if (role === "owner") checkOwnerLogin().then(ok => { if (!ok) window.location.href = loginRedirectForRole("owner", currentPage); });
    }

    function setupLogoutButtons() {
        document.querySelectorAll("[data-logout-role]").forEach(btn => {
            if (btn.dataset.logoutReady === "true") return;
            btn.dataset.logoutReady = "true";

            btn.addEventListener("click", e => {
                e.preventDefault();
                const role = btn.dataset.logoutRole;

                if (role === "staff") window.location.href = "staff-logout.php";
                else if (role === "owner") window.location.href = "owner-logout.php";
                else window.location.href = "logout.php";
            });
        });
    }

    function setupRedirectInputs() {
        const input = document.querySelector('input[name="redirect"]');
        if (!input) return;

        const redirect = new URLSearchParams(window.location.search).get("redirect");
        if (redirect) input.value = redirect;
    }

    function setupMobileNav() {
        const toggler = document.querySelector(".nav-toggler");
        const links = document.querySelector(".links-container");

        if (!toggler || !links || toggler.dataset.navReady === "true") return;

        toggler.dataset.navReady = "true";
        toggler.addEventListener("click", () => {
            toggler.classList.toggle("active");
            links.classList.toggle("active");
        });
    }

    // Expose a tiny safe helper so cart.js can show the same login popup instead of redirecting.
    window.MunchAuth = {
        checkCustomerLogin,
        checkStaffLogin,
        checkOwnerLogin,
        openRoleModal,
        closeRoleModal,
        handleCustomerProtectedTarget
    };

    document.addEventListener("DOMContentLoaded", function () {
        protectCurrentPage();
        setupProtectedLinks();
        setupLogoutButtons();
        setupRedirectInputs();
        setupMobileNav();

        if (typeof AOS !== "undefined") AOS.init({ duration: 800, once: true });
    });
})();
