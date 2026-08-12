// =============================
// MUNCH NAV ORDER SAFETY FIX
// This repairs wrong cached/old links at runtime.
// Top navbar "order" text -> customerOrder.html
// Cart icon -> order.html
// Order food / order now buttons -> order.html
// =============================

(function () {
    function fixOrderNavigation() {
        document.querySelectorAll("a.links").forEach(function (link) {
            if (link.textContent.trim().toLowerCase() === "order") {
                link.href = "customerOrder.html";
                link.dataset.target = "customerOrder.html";
                link.dataset.protected = "true";
                link.classList.add("protected-link");
            }
        });

        document.querySelectorAll("a.footer-links").forEach(function (link) {
            if (link.textContent.trim().toLowerCase() === "your orders") {
                link.href = "customerOrder.html";
                link.dataset.target = "customerOrder.html";
                link.dataset.protected = "true";
                link.classList.add("protected-link");
            }
        });

        document.querySelectorAll("a.cart").forEach(function (cart) {
            cart.href = "order.html";
            cart.dataset.target = "order.html";
            cart.dataset.protected = "true";
            cart.classList.add("protected-link");
        });

        document.querySelectorAll("button.protected-button, button[data-protected='true']").forEach(function (button) {
            const text = button.textContent.trim().toLowerCase();

            if (text === "order food" || text === "order now") {
                button.dataset.target = "order.html";
            }
        });

        document.querySelectorAll("a").forEach(function (link) {
            const text = link.textContent.trim().toLowerCase();

            if (text === "view cart" || text === "manage payment" || text === "your cart") {
                link.href = "order.html";
                link.dataset.target = "order.html";
            }
        });
    }

    document.addEventListener("DOMContentLoaded", fixOrderNavigation);
})();
