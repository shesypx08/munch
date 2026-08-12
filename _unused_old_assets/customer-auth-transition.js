// =============================
// CUSTOMER LOGIN / SIGNUP TRANSITION ONLY
// No backend logic changed.
// It only toggles .active on the auth slider.
// =============================

(function () {
    function setupCustomerAuthTransition() {
        const slider = document.getElementById("customerAuthSlider");
        const registerBtn = document.getElementById("customerRegisterBtn");
        const loginBtn = document.getElementById("customerLoginBtn");

        if (!slider) return;

        const params = new URLSearchParams(window.location.search);
        const mode = params.get("mode");

        if (document.body.classList.contains("show-signup-first") || mode === "signup" || window.location.pathname.toLowerCase().includes("signup")) {
            slider.classList.add("active");
        }

        if (registerBtn) {
            registerBtn.addEventListener("click", function () {
                slider.classList.add("active");
                document.body.classList.add("show-signup-first");
                history.replaceState(null, "", "customer-signup.html");
            });
        }

        if (loginBtn) {
            loginBtn.addEventListener("click", function () {
                slider.classList.remove("active");
                document.body.classList.remove("show-signup-first");
                history.replaceState(null, "", "customer-login.html");
            });
        }

        // Keep old text links working if any are cached in browser
        document.querySelectorAll('a[href="customer-signup.html"]').forEach(function (link) {
            link.addEventListener("click", function (event) {
                event.preventDefault();
                slider.classList.add("active");
                document.body.classList.add("show-signup-first");
                history.replaceState(null, "", "customer-signup.html");
            });
        });

        document.querySelectorAll('a[href="customer-login.html"]').forEach(function (link) {
            link.addEventListener("click", function (event) {
                event.preventDefault();
                slider.classList.remove("active");
                document.body.classList.remove("show-signup-first");
                history.replaceState(null, "", "customer-login.html");
            });
        });
    }

    document.addEventListener("DOMContentLoaded", setupCustomerAuthTransition);
})();
