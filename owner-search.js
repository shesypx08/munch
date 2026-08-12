// =============================
// MUNCH OWNER SEARCH BAR
// Searches cards and table rows.
// =============================

(function () {
    function normalise(text) {
        return String(text || "").toLowerCase().replace(/\s+/g, " ").trim();
    }

    function getSearchableItems() {
        return Array.from(document.querySelectorAll([
            "tbody tr",
            ".owner-module-card",
            ".owner-panel-card",
            ".owner-report-card",
            ".owner-alert-card",
            ".staff-stat-card",
            ".owner-form-card",
            ".owner-timeline article"
        ].join(",")));
    }

    function ensureEmptyBox() {
        let box = document.getElementById("owner-search-empty-message");

        if (!box) {
            box = document.createElement("div");
            box.id = "owner-search-empty-message";
            box.className = "owner-search-empty-message";
            box.innerHTML = `
                <i class="fa-solid fa-magnifying-glass"></i>
                <h3>No matching owner data found</h3>
                <p>Try searching by menu ID, staff ID, customer name, reservation ID, category, payment method, or status.</p>
            `;

            const target =
                document.querySelector(".staff-management-section") ||
                document.querySelector("#staff-stats-section") ||
                document.body;

            target.appendChild(box);
        }

        return box;
    }

    function applySearch() {
        const input = document.querySelector(".search-box");
        if (!input) return;

        const query = normalise(input.value);
        const items = getSearchableItems();
        const emptyBox = ensureEmptyBox();

        let matchedCount = 0;

        items.forEach(function (item) {
            const matched = query === "" || normalise(item.textContent).includes(query);

            item.style.display = matched ? "" : "none";

            if (query !== "" && matched) matchedCount++;
        });

        if (query === "" || matchedCount > 0) {
            emptyBox.classList.remove("show");
        } else {
            emptyBox.classList.add("show");
        }
    }

    function setPlaceholder() {
        const input = document.querySelector(".search-box");
        if (!input) return;

        const page = window.location.pathname.toLowerCase();

        if (page.includes("menu-management")) input.placeholder = "Search menu ID, item, category, price...";
        else if (page.includes("employee-management")) input.placeholder = "Search staff ID, name, role, owner...";
        else if (page.includes("reports")) input.placeholder = "Search sales, item ranking, payment method...";
        else if (page.includes("booking-tracker")) input.placeholder = "Search reservation ID, customer, date, pax...";
        else input.placeholder = "Search owner dashboard data...";
    }

    function setupSearch() {
        const input = document.querySelector(".search-box");
        const button = document.querySelector(".search-btn");

        if (!input) return;

        setPlaceholder();

        input.addEventListener("input", applySearch);

        input.addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                applySearch();
            }

            if (event.key === "Escape") {
                input.value = "";
                applySearch();
            }
        });

        if (button) {
            button.setAttribute("type", "button");

            button.addEventListener("click", function (event) {
                event.preventDefault();
                applySearch();
            });
        }

        const observer = new MutationObserver(function () {
            applySearch();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        window.MunchOwnerSearch = { refresh: applySearch };

        applySearch();
    }

    document.addEventListener("DOMContentLoaded", setupSearch);
})();
