// =============================
// CUSTOMER AUTH SEPARATE PAGE TRANSITION
// login -> signup: slide RIGHT
// signup -> login: slide LEFT
// =============================

(function () {
    function setupSeparateAuthTransition() {
        document.querySelectorAll(".auth-page-switch").forEach(function (link) {
            if (link.dataset.ready === "true") return;
            link.dataset.ready = "true";

            link.addEventListener("click", function (event) {
                event.preventDefault();

                const target = link.getAttribute("href");
                const direction = link.dataset.direction || "right";

                if (!target) return;

                if (direction === "left") {
                    document.body.classList.add("auth-exit-left");
                } else {
                    document.body.classList.add("auth-exit-right");
                }

                setTimeout(function () {
                    window.location.href = target;
                }, 430);
            });
        });
    }

    document.addEventListener("DOMContentLoaded", setupSeparateAuthTransition);
})();
