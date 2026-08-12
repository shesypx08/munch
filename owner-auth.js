// =============================
// MUNCH OWNER AUTH SCRIPT
// Uses current DB: staff supertype + owner subtype.
// Does not replace your customer/staff auth.js.
// =============================

(function () {
    function getJSON(url) {
        return fetch(url, { method: "GET", cache: "no-store" })
            .then(function (response) { return response.json(); })
            .catch(function () { return { loggedIn: false }; });
    }

    function checkOwnerLogin() {
        return getJSON("checkOwnerLogin.php").then(function (data) {
            return data.loggedIn === true;
        });
    }

    function protectOwnerPage() {
        if (document.body.dataset.pageRequiresRole !== "owner") return;

        const currentPage = window.location.pathname.split("/").pop() || "owner-dashboard.html";

        checkOwnerLogin().then(function (loggedIn) {
            if (!loggedIn) {
                window.location.href = "owner-login.html?redirect=" + encodeURIComponent(currentPage);
            }
        });
    }

    function setupOwnerLinks() {
        document.querySelectorAll('[data-role-required="owner"], [data-logout-role="owner"]').forEach(function (item) {
            if (item.dataset.ownerAuthReady === "true") return;
            item.dataset.ownerAuthReady = "true";

            if (item.dataset.logoutRole === "owner") {
                item.addEventListener("click", function (event) {
                    event.preventDefault();
                    window.location.href = "owner-logout.php";
                });
                return;
            }

            item.addEventListener("click", function (event) {
                event.preventDefault();

                const target = item.dataset.target || item.getAttribute("href") || "owner-dashboard.html";

                checkOwnerLogin().then(function (loggedIn) {
                    if (loggedIn) {
                        window.location.href = target;
                    } else {
                        window.location.href = "owner-login.html?redirect=" + encodeURIComponent(target);
                    }
                });
            });
        });
    }

    function setupRedirectInput() {
        const input = document.querySelector('input[name="redirect"]');
        if (!input) return;

        const redirect = new URLSearchParams(window.location.search).get("redirect");
        if (redirect) input.value = redirect;
    }

    function setupMobileNav() {
        const toggler = document.querySelector(".nav-toggler");
        const links = document.querySelector(".links-container");

        if (!toggler || !links || toggler.dataset.ownerNavReady === "true") return;

        toggler.dataset.ownerNavReady = "true";

        toggler.addEventListener("click", function () {
            toggler.classList.toggle("active");
            links.classList.toggle("active");
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        protectOwnerPage();
        setupOwnerLinks();
        setupRedirectInput();
        setupMobileNav();

        if (typeof AOS !== "undefined") {
            AOS.init({ duration: 800, once: true });
        }
    });
})();
