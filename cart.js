// =============================
// MUNCH CART SCRIPT WITH COMBO CUSTOMIZER + MENU ID FIX
// Fixes: "Menu ID is missing"
// Supports buttons with:
// data-menu-id, data-id, data-menuid, value, href ?id=, onclick('M001')
// =============================

(function () {
    const CUSTOM_COMBO_IDS = ["M058", "M059", "M060"];
    let menuCache = null;
    let currentComboMenuID = "";

    function formatRM(value) {
        return "RM" + Number(value || 0).toFixed(2);
    }

    function escapeHTML(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function updateCartBadge(count) {
        const badge = document.getElementById("cart-count-badge");
        if (badge) badge.textContent = count || 0;
    }

    function setCartStatus(text) {
        const status = document.getElementById("cart-status");
        if (status) status.textContent = text;
    }

    function requestCustomerLogin(targetPage) {
        if (window.MunchAuth && typeof window.MunchAuth.openRoleModal === "function") {
            window.MunchAuth.openRoleModal(targetPage || "order.html");
        } else {
            alert("Please login as a customer before adding items to cart.");
        }
    }

    function ensureCustomerLoggedIn() {
        if (window.MunchAuth && typeof window.MunchAuth.checkCustomerLogin === "function") {
            return window.MunchAuth.checkCustomerLogin();
        }

        return fetch("checkLogin.php", { method: "GET", cache: "no-store" })
            .then(function (response) { return response.json(); })
            .then(function (data) { return data.loggedIn === true; })
            .catch(function () { return false; });
    }

    function postForm(url, data) {
        const formData = new FormData();

        Object.keys(data || {}).forEach(function (key) {
            formData.append(key, data[key]);
        });

        return fetch(url, {
            method: "POST",
            body: formData,
            cache: "no-store"
        }).then(function (response) {
            return response.json();
        });
    }

    function getMenuIDFromElement(element) {
        if (!element) return "";

        let menuID =
            element.dataset.menuId ||
            element.dataset.menuid ||
            element.dataset.id ||
            element.dataset.menu ||
            element.getAttribute("data-menu-id") ||
            element.getAttribute("data-menuid") ||
            element.getAttribute("data-id") ||
            element.value ||
            "";

        if (!menuID && element.closest("[data-menu-id]")) {
            menuID = element.closest("[data-menu-id]").getAttribute("data-menu-id");
        }

        if (!menuID && element.closest("[data-id]")) {
            menuID = element.closest("[data-id]").getAttribute("data-id");
        }

        if (!menuID && element.getAttribute("href")) {
            try {
                const url = new URL(element.getAttribute("href"), window.location.href);
                menuID = url.searchParams.get("id") || url.searchParams.get("MENUID") || "";
            } catch (e) {}
        }

        if (!menuID && element.getAttribute("onclick")) {
            const match = element.getAttribute("onclick").match(/M\d{3}/i);
            if (match) menuID = match[0].toUpperCase();
        }

        return String(menuID || "").trim().toUpperCase();
    }

    function injectComboCSS() {
        if (document.getElementById("combo-customizer-style")) return;

        const style = document.createElement("style");
        style.id = "combo-customizer-style";
        style.textContent = `
            .combo-modal-overlay {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: none;
                justify-content: center;
                align-items: center;
                padding: 2rem;
                background: rgba(0, 0, 0, .55);
            }

            .combo-modal-overlay.active {
                display: flex;
            }

            .combo-modal {
                width: min(760px, 100%);
                max-height: 90vh;
                overflow-y: auto;
                background: var(--primary-color, #fff);
                border-radius: 1rem;
                padding: 2rem;
                box-shadow: 0 1rem 3rem rgba(0,0,0,.25);
                position: relative;
            }

            .combo-modal-close {
                position: absolute;
                top: 1rem;
                right: 1rem;
                border: none;
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 50%;
                background: var(--accent-color, #f5f5f5);
                cursor: pointer;
                color: var(--secondary-text-color, #007676);
                font-size: 1.1rem;
            }

            .combo-modal-header {
                margin-bottom: 1.5rem;
                padding-right: 2.5rem;
            }

            .combo-modal-header h2 {
                color: var(--secondary-text-color, #007676);
                margin: .7rem 0;
                font-size: 2rem;
            }

            .combo-modal-header p {
                line-height: 1.7rem;
                opacity: .78;
            }

            .combo-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .combo-field {
                display: grid;
                gap: .45rem;
            }

            .combo-field.combo-full {
                grid-column: 1 / -1;
            }

            .combo-field label {
                color: var(--secondary-text-color, #007676);
                font-weight: 700;
                font-size: .95rem;
            }

            .combo-field select,
            .combo-field input,
            .combo-field textarea {
                width: 100%;
                padding: .9rem 1rem;
                border: .1rem solid var(--border-color, #ddd);
                border-radius: .5rem;
                background: var(--accent-color, #f7f7f7);
                font-family: inherit;
                color: var(--primary-text-color, #111);
                outline: none;
            }

            .combo-field textarea {
                min-height: 5rem;
                resize: vertical;
            }

            .combo-modal-actions {
                display: flex;
                justify-content: flex-end;
                gap: 1rem;
                margin-top: 1.5rem;
                flex-wrap: wrap;
            }

            .combo-preview-box {
                margin-top: 1rem;
                background: var(--accent-color, #f7f7f7);
                border-radius: .7rem;
                padding: 1rem;
                line-height: 1.6rem;
                border-left: .25rem solid var(--secondary-color, #007676);
            }

            .cart-combo-request {
                display: block;
                margin-top: .45rem;
                max-width: 22rem;
                line-height: 1.35rem;
                opacity: .78;
                font-size: .82rem;
            }

            @media (max-width: 700px) {
                .combo-grid {
                    grid-template-columns: 1fr;
                }

                .combo-modal {
                    padding: 1.4rem;
                }
            }
        `;
        document.head.appendChild(style);
    }

    function ensureComboModal() {
        injectComboCSS();

        let overlay = document.getElementById("comboCustomizerOverlay");
        if (overlay) return overlay;

        overlay = document.createElement("div");
        overlay.id = "comboCustomizerOverlay";
        overlay.className = "combo-modal-overlay";
        overlay.innerHTML = `
            <div class="combo-modal" role="dialog" aria-modal="true" aria-labelledby="comboModalTitle">
                <button type="button" class="combo-modal-close" id="closeComboModal" aria-label="Close combo form">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="combo-modal-header">
                    <span class="section-highlight">Combo Customization</span>
                    <h2 id="comboModalTitle">Customize Combo Set</h2>
                    <p id="comboModalDesc">Choose the items included in this combo set before adding it to cart.</p>
                </div>

                <form id="comboCustomizerForm">
                    <div class="combo-grid" id="comboFields"></div>

                    <div class="combo-preview-box" id="comboPreviewBox">
                        Fill in the combo details and add it to your cart.
                    </div>

                    <div class="combo-modal-actions">
                        <button type="button" class="btn transparent" id="cancelComboModal">Cancel</button>
                        <button type="submit" class="btn">Add Customized Combo</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(overlay);

        overlay.addEventListener("click", function (event) {
            if (event.target === overlay) closeComboModal();
        });

        document.getElementById("closeComboModal").addEventListener("click", closeComboModal);
        document.getElementById("cancelComboModal").addEventListener("click", closeComboModal);

        document.getElementById("comboCustomizerForm").addEventListener("submit", function (event) {
            event.preventDefault();

            const comboID = String(currentComboMenuID || "").trim().toUpperCase();
            const details = buildComboDetails(comboID);

            if (!comboID) {
                alert("Combo Menu ID is missing. Please click the combo item again from the menu page.");
                return;
            }

            if (!details) {
                alert("Please complete the combo details first.");
                return;
            }

            // Important: add to cart BEFORE clearing currentComboMenuID.
            addToCart(comboID, 1, details);
            closeComboModal();
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") closeComboModal();
        });

        return overlay;
    }

    function closeComboModal() {
        const overlay = document.getElementById("comboCustomizerOverlay");
        if (overlay) overlay.classList.remove("active");
        currentComboMenuID = "";
    }

    function ensureMenuCache() {
        if (menuCache) return Promise.resolve(menuCache);

        return fetch("displayMenu.php", { cache: "no-store" })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.success || !data.items) {
                    throw new Error(data.message || "Could not load menu options.");
                }

                menuCache = data.items;
                return menuCache;
            });
    }

    function getItemsByCategory(category) {
        if (!menuCache) return [];

        return menuCache.filter(function (item) {
            return String(item.MENUCATEGORY || "").trim().toLowerCase() === category.toLowerCase();
        });
    }

    function getMainItemsByKeyword(keyword) {
        const main = getItemsByCategory("Main Dishes");
        const filtered = main.filter(function (item) {
            const text = (item.MENUNAME + " " + item.MENUDESC).toLowerCase();
            return text.includes(keyword.toLowerCase());
        });

        return filtered.length ? filtered : main;
    }

    function selectHTML(id, label, items, required) {
        const options = items.map(function (item) {
            return `<option value="${escapeHTML(item.MENUNAME)}">${escapeHTML(item.MENUNAME)}</option>`;
        }).join("");

        return `
            <div class="combo-field">
                <label for="${id}">${label}</label>
                <select id="${id}" ${required ? "required" : ""}>
                    <option value="">Choose ${label.toLowerCase()}</option>
                    ${options}
                </select>
            </div>
        `;
    }

    function inputHTML(id, label, type, value, min, max) {
        return `
            <div class="combo-field">
                <label for="${id}">${label}</label>
                <input id="${id}" type="${type}" value="${value || ""}" min="${min || ""}" max="${max || ""}" required>
            </div>
        `;
    }

    function textareaHTML(id, label) {
        return `
            <div class="combo-field combo-full">
                <label for="${id}">${label}</label>
                <textarea id="${id}" placeholder="Example: less spicy, separate gravy, no onion..."></textarea>
            </div>
        `;
    }

    function getValue(id) {
        return document.getElementById(id)?.value.trim() || "";
    }

    function setComboPreview(text) {
        const preview = document.getElementById("comboPreviewBox");
        if (preview) preview.textContent = text || "Fill in the combo details and add it to your cart.";
    }

    function setupPreviewListeners() {
        document.querySelectorAll("#comboFields select, #comboFields input, #comboFields textarea").forEach(function (field) {
            field.addEventListener("input", function () {
                setComboPreview(buildComboDetails(currentComboMenuID, true));
            });
        });

        setComboPreview(buildComboDetails(currentComboMenuID, true));
    }

    function openComboModal(menuID) {
        ensureMenuCache()
            .then(function () {
                currentComboMenuID = menuID;
                const overlay = ensureComboModal();
                const fields = document.getElementById("comboFields");
                const title = document.getElementById("comboModalTitle");
                const desc = document.getElementById("comboModalDesc");

                const nasi = getItemsByCategory("Nasi");
                const main = getItemsByCategory("Main Dishes");
                const chicken = getMainItemsByKeyword("ayam");
                const beef = getMainItemsByKeyword("daging");
                const vegetables = getItemsByCategory("Vegetables");
                const sides = getItemsByCategory("Side Dishes");
                const drinks = getItemsByCategory("Drinks");

                if (menuID === "M058") {
                    title.textContent = "Customize Family Combo Set";
                    desc.textContent = "Sharing set with rice, chicken, beef, vegetables, sides and drinks.";

                    fields.innerHTML = `
                        ${inputHTML("comboRiceQty", "How many rice portions?", "number", "4", "1", "30")}
                        ${selectHTML("comboRice", "Rice type", nasi, true)}
                        ${selectHTML("comboChicken", "Chicken choice", chicken, true)}
                        ${selectHTML("comboBeef", "Beef choice", beef, true)}
                        ${selectHTML("comboVegetable", "Vegetable choice", vegetables, true)}
                        ${selectHTML("comboSide", "Side dish choice", sides, true)}
                        ${inputHTML("comboDrinkQty", "How many drinks?", "number", "4", "1", "30")}
                        ${selectHTML("comboDrink", "Drink choice", drinks, true)}
                        ${textareaHTML("comboNotes", "Extra request")}
                    `;
                } else if (menuID === "M059") {
                    title.textContent = "Customize Couple Combo Set";
                    desc.textContent = "Two-person set with rice, main dishes, vegetables and two drinks.";

                    fields.innerHTML = `
                        ${selectHTML("comboRice", "Rice type", nasi, true)}
                        ${selectHTML("comboMain1", "Main dish 1", main, true)}
                        ${selectHTML("comboMain2", "Main dish 2", main, true)}
                        ${selectHTML("comboVegetable", "Vegetable choice", vegetables, true)}
                        ${selectHTML("comboDrink1", "Drink 1", drinks, true)}
                        ${selectHTML("comboDrink2", "Drink 2", drinks, true)}
                        ${textareaHTML("comboNotes", "Extra request")}
                    `;
                } else if (menuID === "M060") {
                    title.textContent = "Customize Catering Mini Combo";
                    desc.textContent = "Small event combo with mixed rice, mains, sides and drinks.";

                    fields.innerHTML = `
                        ${inputHTML("comboPax", "How many pax?", "number", "10", "5", "100")}
                        ${selectHTML("comboRice", "Rice type", nasi, true)}
                        ${inputHTML("comboRiceQty", "Rice portions", "number", "10", "1", "100")}
                        ${selectHTML("comboMain1", "Main dish 1", main, true)}
                        ${selectHTML("comboMain2", "Main dish 2", main, true)}
                        ${selectHTML("comboVegetable", "Vegetable choice", vegetables, true)}
                        ${selectHTML("comboSide", "Side dish choice", sides, true)}
                        ${inputHTML("comboDrinkQty", "How many drinks?", "number", "10", "1", "100")}
                        ${selectHTML("comboDrink", "Drink choice", drinks, true)}
                        ${textareaHTML("comboNotes", "Event / extra request")}
                    `;
                }

                setupPreviewListeners();
                overlay.classList.add("active");
            })
            .catch(function (error) {
                console.error("Combo modal error:", error);
                alert("Could not load combo choices. Please check displayMenu.php.");
            });
    }

    function buildComboDetails(menuID, allowPartial) {
        let details = "";

        if (menuID === "M058") {
            const riceQty = getValue("comboRiceQty");
            const rice = getValue("comboRice");
            const chicken = getValue("comboChicken");
            const beef = getValue("comboBeef");
            const vegetable = getValue("comboVegetable");
            const side = getValue("comboSide");
            const drinkQty = getValue("comboDrinkQty");
            const drink = getValue("comboDrink");
            const notes = getValue("comboNotes");

            if (!allowPartial && (!riceQty || !rice || !chicken || !beef || !vegetable || !side || !drinkQty || !drink)) return "";

            details = `Family Combo: Rice ${riceQty || "-"} portions - ${rice || "-"}; Chicken - ${chicken || "-"}; Beef - ${beef || "-"}; Vegetable - ${vegetable || "-"}; Side - ${side || "-"}; Drinks ${drinkQty || "-"} - ${drink || "-"}`;
            if (notes) details += `; Notes - ${notes}`;
        }

        if (menuID === "M059") {
            const rice = getValue("comboRice");
            const main1 = getValue("comboMain1");
            const main2 = getValue("comboMain2");
            const vegetable = getValue("comboVegetable");
            const drink1 = getValue("comboDrink1");
            const drink2 = getValue("comboDrink2");
            const notes = getValue("comboNotes");

            if (!allowPartial && (!rice || !main1 || !main2 || !vegetable || !drink1 || !drink2)) return "";

            details = `Couple Combo: Rice - ${rice || "-"}; Main 1 - ${main1 || "-"}; Main 2 - ${main2 || "-"}; Vegetable - ${vegetable || "-"}; Drink 1 - ${drink1 || "-"}; Drink 2 - ${drink2 || "-"}`;
            if (notes) details += `; Notes - ${notes}`;
        }

        if (menuID === "M060") {
            const pax = getValue("comboPax");
            const rice = getValue("comboRice");
            const riceQty = getValue("comboRiceQty");
            const main1 = getValue("comboMain1");
            const main2 = getValue("comboMain2");
            const vegetable = getValue("comboVegetable");
            const side = getValue("comboSide");
            const drinkQty = getValue("comboDrinkQty");
            const drink = getValue("comboDrink");
            const notes = getValue("comboNotes");

            if (!allowPartial && (!pax || !rice || !riceQty || !main1 || !main2 || !vegetable || !side || !drinkQty || !drink)) return "";

            details = `Mini Catering Combo: Pax - ${pax || "-"}; Rice ${riceQty || "-"} portions - ${rice || "-"}; Main 1 - ${main1 || "-"}; Main 2 - ${main2 || "-"}; Vegetable - ${vegetable || "-"}; Side - ${side || "-"}; Drinks ${drinkQty || "-"} - ${drink || "-"}`;
            if (notes) details += `; Notes - ${notes}`;
        }

        if (details.length > 250) details = details.substring(0, 247) + "...";

        return details;
    }

    function addToCart(menuID, quantity, comboDetails) {
        menuID = String(menuID || "").trim().toUpperCase();

        if (!menuID) {
            alert('Menu ID is missing. Please replace cart.js and make sure the clicked button has data-menu-id, for example data-menu-id="M058".');
            return;
        }

        ensureCustomerLoggedIn().then(function (loggedIn) {
            if (!loggedIn) {
                setCartStatus("Status: Login Required");
                requestCustomerLogin("order.html");
                return;
            }

            postForm("addToCart.php", {
                MENUID: menuID,
                menuID: menuID,
                QUANTITY: quantity || 1,
                COMBO_DETAILS: comboDetails || ""
            })
                .then(function (data) {
                    if (!data.success) {
                        console.error("Add to cart failed:", data);

                        if (data.requiresLogin) {
                            setCartStatus("Status: Login Required");
                            requestCustomerLogin("order.html");
                            return;
                        }

                        alert(data.message || "Could not add item to cart.");
                        return;
                    }

                    updateCartBadge(data.cartCount);
                    setCartStatus("Status: Item Added");

                    const isOrderPage = window.location.pathname.toLowerCase().includes("order");

                    if (isOrderPage) {
                        loadCart();
                        alert("Item added to cart!");
                    } else {
                        window.location.href = "order.html";
                    }
                })
                .catch(function (error) {
                    console.error("Add to cart error:", error);
                    alert("Could not add item to cart. Please check addToCart.php.");
                });
        });
    }

    function loadOrderMenu() {
        const grid = document.getElementById("order-menu-grid");
        if (!grid) return;

        fetch("displayMenu.php", { cache: "no-store" })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.success) {
                    grid.innerHTML = `<div class="menu-empty-message"><h2>Could not load menu.</h2><p>${data.message || "Please check displayMenu.php."}</p></div>`;
                    return;
                }

                menuCache = data.items || [];

                if (!data.items || data.items.length === 0) {
                    grid.innerHTML = `<div class="menu-empty-message"><h2>No menu found.</h2><p>Please add menu items into the menu table.</p></div>`;
                    return;
                }

                grid.innerHTML = "";

                data.items.forEach(function (item) {
                    const card = document.createElement("article");
                    card.className = "customer-order-card";
                    card.setAttribute("data-menu-id", item.MENUID);

                    const comboHint = CUSTOM_COMBO_IDS.includes(item.MENUID)
                        ? `<small class="cart-combo-request">This combo needs customization before adding to cart.</small>`
                        : "";

                    card.innerHTML = `
                        <img src="${item.IMAGE}" alt="${escapeHTML(item.MENUNAME)}">
                        <div class="customer-order-body">
                            <div class="order-card-top">
                                <h3>${escapeHTML(item.MENUNAME)}</h3>
                                <span>${formatRM(item.MENUPRICE)}</span>
                            </div>
                            <p>${escapeHTML(item.MENUDESC || "Freshly prepared Munch menu item.")}</p>
                            ${comboHint}
                            <button class="btn add-cart-btn" type="button" data-menu-id="${item.MENUID}">
                                ${CUSTOM_COMBO_IDS.includes(item.MENUID) ? "customize combo" : "add to cart"}
                            </button>
                        </div>
                    `;

                    grid.appendChild(card);
                });
            })
            .catch(function (error) {
                console.error("Menu loading error:", error);
                grid.innerHTML = `<div class="menu-empty-message"><h2>Menu loading error.</h2><p>Please check displayMenu.php.</p></div>`;
            });
    }

    function loadCart() {
        fetch("displayCart.php", { cache: "no-store" })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                updateCartBadge(data.cartCount || 0);

                const list = document.getElementById("cart-items-list");
                const subtotal = document.getElementById("cart-subtotal");
                const tax = document.getElementById("cart-tax");
                const total = document.getElementById("cart-total");

                if (!list) return;

                if (!data.success) {
                    list.innerHTML = `<li>${data.message || "Could not load cart."}</li>`;
                    return;
                }

                if (!data.items || data.items.length === 0) {
                    list.innerHTML = "<li>Your cart is empty.</li>";
                } else {
                    list.innerHTML = "";

                    data.items.forEach(function (item) {
                        const li = document.createElement("li");
                        li.className = "cart-line-item";

                        const requestHTML = item.REQUEST
                            ? `<small class="cart-combo-request"><strong>Combo details:</strong> ${escapeHTML(item.REQUEST)}</small>`
                            : "";

                        li.innerHTML = `
                            <div>
                                <strong>${escapeHTML(item.MENUNAME)}</strong>
                                <small>${formatRM(item.MENUPRICE)} each</small>
                                ${requestHTML}
                            </div>

                            <div class="cart-qty-controls">
                                <button type="button" data-action="minus" data-menu-id="${item.MENUID}">-</button>
                                <span>${item.QUANTITY}</span>
                                <button type="button" data-action="plus" data-menu-id="${item.MENUID}">+</button>
                                <button type="button" data-action="remove" data-menu-id="${item.MENUID}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>

                            <strong>${formatRM(item.SUBTOTAL)}</strong>
                        `;

                        list.appendChild(li);
                    });
                }

                if (subtotal) subtotal.textContent = formatRM(data.subtotal);
                if (tax) tax.textContent = formatRM(data.tax);
                if (total) total.textContent = formatRM(data.total);
            })
            .catch(function (error) {
                console.error("Cart loading error:", error);
                const list = document.getElementById("cart-items-list");
                if (list) list.innerHTML = "<li>Could not load cart. Please check displayCart.php.</li>";
            });
    }

    function handleAddButtonClick(button) {
        const menuID = getMenuIDFromElement(button);

        if (!menuID) {
            console.error("Clicked add-cart button without menu id:", button);
            alert('Menu ID is missing. This button needs data-menu-id="M001".');
            return;
        }

        ensureCustomerLoggedIn().then(function (loggedIn) {
            if (!loggedIn) {
                setCartStatus("Status: Login Required");
                requestCustomerLogin("order.html");
                return;
            }

            if (CUSTOM_COMBO_IDS.includes(menuID)) {
                openComboModal(menuID);
                return;
            }

            addToCart(menuID, 1, "");
        });
    }

    function setupAddToCartButtons() {
        document.querySelectorAll(".add-cart-btn, .add-to-cart-btn, [data-add-to-cart], .cart-btn").forEach(function (button) {
            if (button.dataset.ready === "true") return;
            button.dataset.ready = "true";

            const menuID = getMenuIDFromElement(button);
            if (CUSTOM_COMBO_IDS.includes(menuID)) {
                button.textContent = "customize combo";
            }

            button.addEventListener("click", function (event) {
                event.preventDefault();
                handleAddButtonClick(button);
            });
        });
    }

    function setupDelegatedAddToCart() {
        if (document.body.dataset.cartDelegationReady === "true") return;
        document.body.dataset.cartDelegationReady = "true";

        document.body.addEventListener("click", function (event) {
            const button = event.target.closest(".add-cart-btn, .add-to-cart-btn, [data-add-to-cart], .cart-btn");
            if (!button) return;

            if (button.dataset.ready === "true") return;

            event.preventDefault();
            handleAddButtonClick(button);
        });
    }

    function setupQuantityDelegation() {
        if (document.body.dataset.qtyDelegationReady === "true") return;
        document.body.dataset.qtyDelegationReady = "true";

        document.body.addEventListener("click", function (event) {
            const button = event.target.closest(".cart-qty-controls button");
            if (!button) return;

            event.preventDefault();

            postForm("updateCart.php", {
                MENUID: getMenuIDFromElement(button),
                ACTION: button.dataset.action
            })
                .then(function (data) {
                    if (!data.success) {
                        alert(data.message || "Could not update cart.");
                        return;
                    }

                    loadCart();
                })
                .catch(function (error) {
                    console.error("Cart update error:", error);
                    alert("Could not update cart. Please check updateCart.php.");
                });
        });
    }

    function setupClearCart() {
        const clearBtn = document.getElementById("clear-cart-btn");
        if (!clearBtn) return;

        clearBtn.addEventListener("click", function () {
            if (!confirm("Clear all cart items?")) return;

            postForm("updateCart.php", { ACTION: "clear" }).then(function () {
                loadCart();
                setCartStatus("Status: Cart Cleared");
            });
        });
    }

    function setupCheckout() {
        const checkoutBtn = document.getElementById("checkout-btn");
        if (!checkoutBtn) return;

        checkoutBtn.addEventListener("click", function () {
            const orderType = document.getElementById("order-type")?.value || "Dine In";
            const paymentMethod = document.getElementById("payment-method")?.value || "Cash at Counter";
            const specialRequest = document.getElementById("special-request")?.value || "";
            const deliveryAddress = document.getElementById("delivery-address")?.value || "";

            setCartStatus("Status: Processing Payment...");

            postForm("order.php", {
                orderType: orderType,
                paymentMethod: paymentMethod,
                specialRequest: specialRequest,
                deliveryAddress: deliveryAddress
            })
                .then(function (data) {
                    if (!data.success) {
                        setCartStatus("Status: Checkout Failed");
                        alert(data.message || "Could not place order.");
                        return;
                    }

                    setCartStatus("Status: Redirecting to Payment Gateway...");
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        alert("Order created! Order ID: " + data.orderID);
                        window.location.href = "customerOrder.html";
                    }
                })
                .catch(function (error) {
                    console.error("Checkout error:", error);
                    setCartStatus("Status: Checkout Error");
                    alert("Could not place order. Please check order.php.");
                });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        injectComboCSS();
        loadOrderMenu();
        loadCart();
        setupAddToCartButtons();
        setupDelegatedAddToCart();
        setupQuantityDelegation();
        setupClearCart();
        setupCheckout();

        // Re-scan after live content appears
        setTimeout(setupAddToCartButtons, 500);
        setTimeout(setupAddToCartButtons, 1200);
    });

    // Make it available for old inline onclick buttons if any exist
    window.addToCart = addToCart;
    window.addToCartItem = addToCart;
})();
