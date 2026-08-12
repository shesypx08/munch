(function () {
    const messages = {
        privacy: {
            title: "Privacy Policy",
            text: "Munch keeps your personal details private and only uses them to support orders, reservations, and account service."
        },
        terms: {
            title: "Terms and Conditions",
            text: "By using Munch, please place orders and reservations responsibly. Prices, availability, and booking times may change when needed."
        },
        instagram: {
            title: "Instagram",
            text: "Follow us on Instagram @munch.my for updates, food photos, and promotions."
        },
        facebook: {
            title: "Facebook",
            text: "Follow us on Facebook @Munch.my for the latest Munch news and offers."
        },
        twitter: {
            title: "Twitter",
            text: "Follow us on Twitter @munch.my for quick updates and announcements."
        },
        app: {
            title: "App Platform",
            text: "The system is upgraded and the app platform will be available in the future."
        }
    };

    function ensureModal() {
        let modal = document.querySelector(".munch-popup");

        if (modal) return modal;

        modal = document.createElement("div");
        modal.className = "munch-popup";
        modal.innerHTML = `
            <div class="munch-popup-box" role="dialog" aria-modal="true" aria-labelledby="munch-popup-title">
                <button class="munch-popup-close" type="button" aria-label="Close popup">&times;</button>
                <h2 id="munch-popup-title"></h2>
                <p></p>
            </div>
        `;

        document.body.appendChild(modal);

        modal.addEventListener("click", function (event) {
            if (
                event.target === modal ||
                event.target.classList.contains("munch-popup-close")
            ) {
                modal.classList.remove("show");
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                modal.classList.remove("show");
            }
        });

        return modal;
    }

    function showPopup(message) {
        const modal = ensureModal();
        modal.querySelector("h2").textContent = message.title;
        modal.querySelector("p").textContent = message.text;
        modal.classList.add("show");
    }

    function getMessageForLink(link) {
        const text = (link.textContent || "").toLowerCase();
        const icon = link.querySelector("i");
        const image = link.querySelector("img");

        if (text.includes("privacy")) return messages.privacy;
        if (text.includes("terms")) return messages.terms;
        if (icon && icon.classList.contains("fa-instagram")) return messages.instagram;
        if (icon && icon.classList.contains("fa-facebook")) return messages.facebook;
        if (icon && icon.classList.contains("fa-twitter")) return messages.twitter;

        if (image) {
            const imageText = ((image.getAttribute("alt") || "") + " " + (image.getAttribute("src") || "")).toLowerCase();
            if (imageText.includes("store") || imageText.includes("play-store") || imageText.includes("app-store")) {
                return messages.app;
            }
        }

        return null;
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("footer a, #download-app-section .download-btn").forEach(function (link) {
            const message = getMessageForLink(link);
            if (!message) return;

            link.addEventListener("click", function (event) {
                event.preventDefault();
                showPopup(message);
            });
        });
    });
})();
