// =============================
// MUNCH SEARCH SCRIPT - CHECKED FIX
// Works across customer pages.
// Put this before </body> after app.js/auth.js/cart.js/profile.js.
// =============================

(function () {
    let isFiltering = false;
    let mutationTimer = null;

    function normalize(text) {
        return String(text || "").toLowerCase().trim();
    }

    function currentPath() {
        return window.location.pathname.toLowerCase();
    }

    function isIndexPage() {
        const path = currentPath();

        return (
            path.includes("index") ||
            path.endsWith("/munch/") ||
            path.endsWith("/munch")
        );
    }

    function getConfig() {
        const path = currentPath();

        if (path.includes("customerorder")) {
            return {
                containerSelector: ".order-list",
                itemSelector: ".order-item",
                type: "customerOrders",
                emptyTitle: "No orders found.",
                emptyText: "No order matches your search."
            };
        }

        if (path.includes("order")) {
            return {
                containerSelector: "#order-menu-grid",
                itemSelector: ".customer-order-card",
                type: "orderMenu",
                emptyTitle: "No menu item found.",
                emptyText: "Try searching another dish name."
            };
        }

        if (path.includes("menu")) {
            return {
                containerSelector: "#menu-section",
                itemSelector: ".menu-card",
                type: "menu",
                emptyTitle: "No menu item found.",
                emptyText: "Try searching another menu name or category."
            };
        }

        if (isIndexPage()) {
            return {
                containerSelector: "#restaurants-section",
                itemSelector: ".restaurant",
                type: "index",
                emptyTitle: "No preview item found.",
                emptyText: "Click the search button or press Enter to search the full menu."
            };
        }

        return null;
    }

    function getContainer(config) {
        return config ? document.querySelector(config.containerSelector) : null;
    }

    function removeEmptyMessage(container) {
        if (!container) return;

        const oldMessages = container.querySelectorAll(".search-empty-message");
        oldMessages.forEach(function (msg) {
            msg.remove();
        });
    }

    function showEmptyMessage(container, title, text) {
        if (!container) return;

        removeEmptyMessage(container);

        const message = document.createElement("div");
        message.className = "search-empty-message menu-empty-message";
        message.innerHTML = `
            <h2>${title}</h2>
            <p>${text}</p>
            <a href="menu.php" class="btn">View Full Menu</a>
        `;

        container.appendChild(message);
    }

    function updateMenuCategoryVisibility(query) {
        const blocks = document.querySelectorAll(".menu-category-block");

        blocks.forEach(function (block) {
            const cards = block.querySelectorAll(".menu-card");
            let hasVisibleCard = false;

            cards.forEach(function (card) {
                if (card.style.display !== "none") {
                    hasVisibleCard = true;
                }
            });

            block.style.display = query === "" || hasVisibleCard ? "" : "none";
        });

        const categoryButtons = document.querySelector(".menu-category-container");
        if (categoryButtons) {
            categoryButtons.style.display = query === "" ? "" : "none";
        }
    }

    function filterPage(rawQuery) {
        const query = normalize(rawQuery);
        const config = getConfig();

        if (!config) return false;

        const container = getContainer(config);
        const items = document.querySelectorAll(config.itemSelector);

        if (!container || items.length === 0) {
            return false;
        }

        isFiltering = true;
        removeEmptyMessage(container);

        let visibleCount = 0;

        items.forEach(function (item) {
            const text = normalize(item.textContent);
            const isMatch = query === "" || text.includes(query);

            item.style.display = isMatch ? "" : "none";

            if (isMatch) {
                visibleCount++;
            }
        });

        if (config.type === "menu") {
            updateMenuCategoryVisibility(query);
        }

        if (visibleCount === 0 && query !== "") {
            showEmptyMessage(container, config.emptyTitle, config.emptyText);
        }

        window.setTimeout(function () {
            isFiltering = false;
        }, 30);

        return visibleCount > 0;
    }

    function setAllSearchBoxValues(value) {
        document.querySelectorAll(".search-box").forEach(function (input) {
            if (input.value !== value) {
                input.value = value;
            }
        });
    }

    function redirectToMenuSearch(query) {
        query = normalize(query);

        if (query === "") return;

        window.location.href = "menu.php?search=" + encodeURIComponent(query);
    }

    function setupSearchInputs() {
        const inputs = document.querySelectorAll(".search-box");

        inputs.forEach(function (input) {
            if (input.dataset.searchReady === "true") return;
            input.dataset.searchReady = "true";

            const wrapper = input.closest(".search") || input.parentElement;
            const button = wrapper ? wrapper.querySelector(".search-btn") : null;

            input.addEventListener("input", function () {
                const query = input.value;
                setAllSearchBoxValues(query);

                // Only filter while typing on pages with local searchable content.
                // On login/profile/reservation pages, do not redirect on every letter.
                if (getConfig()) {
                    filterPage(query);
                }
            });

            input.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();

                    const query = input.value;

                    if (isIndexPage()) {
                        redirectToMenuSearch(query);
                        return;
                    }

                    const hasLocalConfig = !!getConfig();
                    const foundLocal = hasLocalConfig ? filterPage(query) : false;

                    if (!foundLocal && normalize(query) !== "" && !currentPath().includes("menu")) {
                        redirectToMenuSearch(query);
                    }
                }
            });

            if (button) {
                button.addEventListener("click", function (event) {
                    event.preventDefault();

                    const query = input.value;

                    if (isIndexPage()) {
                        redirectToMenuSearch(query);
                        return;
                    }

                    const hasLocalConfig = !!getConfig();
                    const foundLocal = hasLocalConfig ? filterPage(query) : false;

                    if (!foundLocal && normalize(query) !== "" && !currentPath().includes("menu")) {
                        redirectToMenuSearch(query);
                    }
                });
            }
        });
    }

    function applySearchFromURL() {
        const params = new URLSearchParams(window.location.search);
        const query = params.get("search");

        if (!query) return;

        setAllSearchBoxValues(query);

        window.setTimeout(function () {
            filterPage(query);
        }, 500);
    }

    function watchDynamicCards() {
        const config = getConfig();

        if (!config) return;

        const container = getContainer(config);
        if (!container) return;

        const observer = new MutationObserver(function () {
            if (isFiltering) return;

            window.clearTimeout(mutationTimer);

            mutationTimer = window.setTimeout(function () {
                const firstInput = document.querySelector(".search-box");
                const query = firstInput ? firstInput.value : "";

                if (normalize(query) !== "") {
                    filterPage(query);
                }
            }, 120);
        });

        observer.observe(container, {
            childList: true,
            subtree: true
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        setupSearchInputs();
        applySearchFromURL();
        watchDynamicCards();
    });
})();
