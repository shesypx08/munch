// =============================
// CUSTOMER AUTH SLIDE ONLY
// No backend changes. Only intercepts the switch links.
// =============================

(function () {
    function setupCustomerAuthSlide() {
        const slider = document.getElementById("customerAuthSlider");
        const registerLink = document.getElementById("showCustomerRegister");
        const loginLink = document.getElementById("showCustomerLogin");
        const heading = document.getElementById("authSideHeading");
        const line = document.getElementById("authSideLine");
        const content = document.querySelector(".customer-auth-page .auth-content");

        if (!slider) return;

        function updateSideText(mode) {
            if (!heading || !line) return;

            if (content) content.classList.add("switching");

            setTimeout(function () {
                if (mode === "register") {
                    heading.textContent = "Create your customer account";
                    line.textContent = "Sign up once and use your account to order food, make reservations, and manage your customer profile.";
                } else {
                    heading.textContent = "Welcome back to Munch";
                    line.textContent = "Log in to view your profile, make reservations, place orders, and enjoy a faster checkout experience.";
                }

                if (content) content.classList.remove("switching");
            }, 120);
        }

        function showRegister(updateUrl) {
            slider.classList.add("active");
            document.body.classList.add("customer-register-first");
            updateSideText("register");

            if (updateUrl) {
                history.replaceState(null, "", "customer-signup.html");
            }
        }

        function showLogin(updateUrl) {
            slider.classList.remove("active");
            document.body.classList.remove("customer-register-first");
            updateSideText("login");

            if (updateUrl) {
                history.replaceState(null, "", "customer-login.html");
            }
        }

        if (document.body.classList.contains("customer-register-first") || window.location.pathname.toLowerCase().includes("signup")) {
            showRegister(false);
        } else {
            showLogin(false);
        }

        if (registerLink) {
            registerLink.addEventListener("click", function (event) {
                event.preventDefault();
                showRegister(true);
            });
        }

        if (loginLink) {
            loginLink.addEventListener("click", function (event) {
                event.preventDefault();
                showLogin(true);
            });
        }
    }

    document.addEventListener("DOMContentLoaded", setupCustomerAuthSlide);
})();
